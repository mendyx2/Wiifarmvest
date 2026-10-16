<?php

namespace App\Services;

use App\Models\Stockist;
use App\Models\User;
use App\Models\WelcomePackage;
use Illuminate\Support\Facades\DB;
use RuntimeException;


class WelcomePackageService
{ 
    
    public function createForUser(User $user, float $amount, string $source, string $trx, string $details): ?WelcomePackage
    {
        if ($amount <= 0) {
            return null;
        }

        return WelcomePackage::create([
            'user_id' => $user->id,
            'amount'  => round($amount, 2),
            'code'    => $this->generateUniqueCode(),
            'source'  => $source,
            'status'  => WelcomePackage::STATUS_PENDING,
            'trx'     => $trx,
        ]);
    }

    /**
     * Resolve a redemption code to a still-redeemable package.
     *
     * @throws RuntimeException if the code is unknown or already redeemed.
     */
    public function findByCode(string $code): WelcomePackage
    {
        $package = WelcomePackage::where('code', strtoupper(trim($code)))->first();

        if (!$package) {
            throw new RuntimeException('Welcome package code not found.');
        }

        if ($package->isRedeemed()) {
            throw new RuntimeException('This welcome package has already been redeemed.');
        }

        return $package;
    }

    /**
     * Redeem a package on behalf of a stockist: the stockist is reimbursed
     * the package amount for handing the welcome value to the customer in
     * person, exactly like an invoice redemption.
     *
     * @throws RuntimeException if the package was redeemed concurrently.
     */
    public function redeem(WelcomePackage $package, Stockist $stockist): WelcomePackage
    {
        return DB::transaction(function () use ($package, $stockist) {
            $locked = WelcomePackage::lockForUpdate()->findOrFail($package->id);

            if ($locked->isRedeemed()) {
                throw new RuntimeException('This welcome package has already been redeemed.');
            }

            $locked->status                 = WelcomePackage::STATUS_REDEEMED;
            $locked->redeemed_by_stockist_id = $stockist->id;
            $locked->redeemed_at             = now();
            $locked->save();

           
            $stockist->addToWallet((float) $locked->amount);

            notify($locked->user, 'PROJECT_PURCHASED', [
                'shopping'     => 'Welcome package redemption',
                'amount'       => showAmount($locked->amount, currencyFormat: false),
                'trx'          => $locked->trx,
                'post_balance' => showAmount($locked->user->balance, currencyFormat: false),
            ]);

            return $locked;
        });
    }

    /**
     * Generate a short, human-presentable, guaranteed-unique redemption code.
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = getTrx(10);
        } while (WelcomePackage::where('code', $code)->exists());

        return $code;
    }
}

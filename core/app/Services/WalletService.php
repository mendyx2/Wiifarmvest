<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;


class WalletService
{
   
    public const WALLETS = ['balance', 'product_wallet', 'savings_wallet', 'visa', 'autoship'];

    /**
     * Bonus accumulators a member may move into a wallet (Bonus Transfer).
     */
    public const BONUS_WALLETS = [
        'direct_bonus', 'indirect_bonus', 'upgrade_bonus', 'unilevel_bonus', 'awards',
        'matching_bonus', 'autoship', 'repurchase_award', 'key_in_bonus', 'acb',
        'affiliate_bonus', 'stockist_rebate', 'stockist_sponsor_bonus',
    ];

    /**
     * Take money out of a wallet.
     *
     * @param  array $ledger extra `transactions` columns, e.g. ['bonus_type' => 6]
     * @throws InsufficientBalanceException when the wallet can't cover $amount.
     */
    public function debit(
        User|int $user,
        float $amount,
        string $remark,
        string $details,
        ?string $trx = null,
        string $wallet = 'balance',
        float $charge = 0,
        array $ledger = [],
    ): Transaction {
        return $this->move($user, $amount, '-', $remark, $details, $trx, $wallet, $charge, $ledger);
    }

    
    public function credit(
        User|int $user,
        float $amount,
        string $remark,
        string $details,
        ?string $trx = null,
        string $wallet = 'balance',
        float $charge = 0,
        array $ledger = [],
    ): Transaction {
        return $this->move($user, $amount, '+', $remark, $details, $trx, $wallet, $charge, $ledger);
    }

    
    public function transferBonus(
        User|int $user,
        string $bonus,
        float $amount,
        string $details,
        string $to = 'balance',
        array $ledger = [],
    ): Transaction {
        if (!in_array($bonus, self::BONUS_WALLETS, true) || $bonus === $to) {
            throw new InvalidArgumentException("Cannot transfer [{$bonus}] into [{$to}].");
        }

        $this->assertAmount($amount);

        $userId    = $user instanceof User ? $user->id : $user;
        $remaining = null;

        $transaction = DB::transaction(function () use ($userId, $bonus, $amount, $details, $to, $ledger, &$remaining) {
            $locked    = User::lockForUpdate()->findOrFail($userId);
            $available = (float) $locked->{$bonus};

            if (round($available - $amount, 8) < 0) {
                throw new InsufficientBalanceException($bonus, $available, $amount);
            }

            $remaining        = round($available - $amount, 8);
            $locked->{$bonus} = $remaining;
            $locked->save();

            return $this->move($locked, $amount, '+', 'bonus_transfer', $details, null, $to, 0, $ledger);
        });

        if ($user instanceof User) {
            $user->{$to}    = $transaction->post_balance;
            $user->{$bonus} = $remaining;
            $user->syncOriginalAttributes([$to, $bonus]);
        }

        return $transaction;
    }

    private function move(
        User|int $user,
        float $amount,
        string $type,
        string $remark,
        string $details,
        ?string $trx,
        string $wallet,
        float $charge,
        array $ledger = [],
    ): Transaction {
        $isWallet = in_array($wallet, self::WALLETS, true);
        $isBonus  = in_array($wallet, self::BONUS_WALLETS, true);

        if (!$isWallet && !($isBonus && $type === '+')) {
            throw new InvalidArgumentException("Cannot move money " . ($type === '+' ? 'into' : 'out of') . " [{$wallet}].");
        }

        $this->assertAmount($amount);

        $userId = $user instanceof User ? $user->id : $user;

        $transaction = DB::transaction(function () use ($userId, $amount, $type, $remark, $details, $trx, $wallet, $charge, $ledger) {
            $locked  = User::lockForUpdate()->findOrFail($userId);
            $current = (float) $locked->{$wallet};

            if ($type === '-' && round($current - $amount, 8) < 0) {
                throw new InsufficientBalanceException($wallet, $current, $amount);
            }

            $locked->{$wallet} = round($type === '+' ? $current + $amount : $current - $amount, 8);
            $locked->save();

            $txn               = new Transaction();
            $txn->user_id      = $locked->id;
            $txn->amount       = $amount;
            $txn->charge       = $charge;
            $txn->post_balance = $locked->{$wallet};
            $txn->trx_type     = $type;
            $txn->details      = $details;
            $txn->remark       = $remark;
            $txn->trx          = $trx ?? getTrx();

            foreach ($ledger as $column => $value) {
                $txn->{$column} = $value;
            }

            $txn->save();

            return $txn;
        });

        
        if ($user instanceof User) {
            $user->{$wallet} = $transaction->post_balance;
            $user->syncOriginalAttribute($wallet);
        }

        return $transaction;
    }

   
    private function assertAmount(float $amount): void
    {
        if (!is_finite($amount) || $amount <= 0) {
            throw new InvalidArgumentException('Wallet amount must be a positive number.');
        }
    }
}

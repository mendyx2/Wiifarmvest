<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stockist;
use App\Models\User;

/**
 * Stockist Sponsor Rebate Service
 *
 * Blueprint: Commerce Pool (CP) — Stockist Sponsorship Rebate: 3.00%
 *
 * When a stockist sells or redeems product inventory, the distributor who
 * introduced/sponsored that stockist earns a 3.00% override commission
 * on the PV volume transacted.
 *
 * Formula: sponsor_bonus = PV × 0.03 × ₦600/PV × quantity
 */
class StockistSponsorBonusService
{
    public function __construct(private readonly WalletService $wallet)
    {
    }

    /**
     * Credit the sponsor of a stockist for product volume transacted.
     *
     * @param  Stockist  $stockist   the stockist who completed the transaction
     * @param  Product   $product    the product sold/redeemed
     * @param  int       $quantity   number of units
     * @param  string    $trx        transaction reference
     * @param  string    $source     description (e.g., "Invoice redemption #INV-123")
     * @return float     the amount credited (0.00 if no sponsor is linked)
     */
    public function credit(
        Stockist $stockist,
        Product $product,
        int $quantity,
        string $trx,
        string $source,
    ): float {
        $sponsorUserId = $stockist->sponsor_user_id;

        if (!$sponsorUserId) {
            return 0.0;
        }

        $sponsor = User::find($sponsorUserId);
        if (!$sponsor) {
            return 0.0;
        }

        $amount = $this->calculateBonus($product, $quantity);

        if ($amount <= 0) {
            return 0.0;
        }

        $this->wallet->credit(
            $sponsor,
            $amount,
            'stockist_sponsor_bonus',
            sprintf(
                'Stockist sponsor rebate (%.2f%%) — %s: %s × %d via %s',
                Stockist::SPONSOR_BONUS_PERCENT,
                $source,
                $product->name,
                $quantity,
                $stockist->business_name ?? 'Stockist #' . $stockist->id
            ),
            $trx,
            wallet: 'stockist_sponsor_bonus',
            ledger: ['bonus_type' => 30],
        );

        return $amount;
    }

    /**
     * Calculate the sponsor bonus for a product without paying it.
     *
     * Formula: PV × (3.00 / 100) × ₦600/PV × quantity
     */
    public function calculateBonus(Product $product, int $quantity): float
    {
        $pv = (float) ($product->pv ?? 0);
        if ($pv <= 0 || $quantity <= 0) {
            return 0.0;
        }

        return round(($pv * (Stockist::SPONSOR_BONUS_PERCENT / 100) * rDollar() * $quantity), 2);
    }
}

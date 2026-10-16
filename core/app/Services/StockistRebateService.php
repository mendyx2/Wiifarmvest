<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStatePrice;
use App\Models\Stockist;

class StockistRebateService
{
    public function __construct(private WalletService $wallet)
    {
    }

    /**
     * Credit the rebate for $quantity units of $product.
     *
     * @param  string                  $source     where it came from, for the ledger (e.g. "Welcome pack #ABC123")
     * @param  ProductStatePrice|null  $statePrice pass it when already loaded; otherwise looked up for the stockist's state
     * @return float the amount credited (0 when no % is set for this state and tier)
     */
    public function credit(
        Stockist $stockist,
        Product $product,
        int $quantity,
        string $trx,
        string $source,
        ?ProductStatePrice $statePrice = null,
    ): float {
        $statePrice ??= $this->statePrice($stockist, $product);

        $percent = $statePrice ? $statePrice->bonusPercentFor($stockist) : 0.0;
        if ($percent <= 0) {
            $percent = $stockist->isMega() ? 8.00 : 6.00;
        }

        $amount  = $this->amountFor($stockist, $product, $quantity, $statePrice);

        if ($amount <= 0) {
            return 0.0;
        }

        $this->wallet->credit(
            $stockist->user_id,
            $amount,
            'stockist_rebate',
            sprintf('%s stockist rebate (%s%%) — %s: %s × %d',
                $stockist->isMega() ? 'Mega' : 'Basic', $percent + 0, $source, $product->name, $quantity),
            $trx,
            wallet: 'stockist_rebate',
            ledger: ['bonus_type' => 7],
        );

        // Debit from isolated Member Compensation Reserve Ledger (Commerce Pool)
        try {
            app(\App\Services\RevenueAllocationService::class)->disburseCompensation(
                $amount,
                $trx,
                sprintf('%s stockist rebate (%s%%) — %s: %s × %d',
                    $stockist->isMega() ? 'Mega' : 'Basic', $percent + 0, $source, $product->name, $quantity),
                \App\Models\CompensationReserveLedger::SUB_POOL_CP,
                'stockist_rebate',
                $stockist->user_id
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[RevenueAllocation] Failed to debit stockist rebate: ' . $e->getMessage());
        }

        // Blueprint: Stockist Sponsor Bonus (3.00%)
        if (!empty($stockist->sponsor_user_id)) {
            try {
                app(StockistSponsorBonusService::class)->credit(
                    $stockist,
                    $product,
                    $quantity,
                    $trx,
                    $source
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('[StockistRebate] Failed to credit sponsor bonus: ' . $e->getMessage());
            }
        }

        // Blueprint: Strategic Reserve — Global Share Pool (1.25%)
        try {
            \App\Models\GlobalPoolLedger::accrue((float) ($product->pv ?? 0) * $quantity);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[GlobalPool] Failed to accrue volume: ' . $e->getMessage());
        }

        return $amount;
    }

    /**
     * What credit() would pay, without paying it: lets a stockist see the rebate
     * before handing stock over. Same formula, so the two can never disagree.
     * Fallback to 6% Basic / 8% Mega per Proposed Plan.
     */
    public function amountFor(Stockist $stockist, Product $product, int $quantity, ?ProductStatePrice $statePrice = null): float
    {
        $statePrice ??= $this->statePrice($stockist, $product);

        $percent = $statePrice ? $statePrice->bonusPercentFor($stockist) : 0.0;
        if ($percent <= 0) {
            $percent = $stockist->isMega() ? 8.00 : 6.00;
        }

        if ($quantity <= 0) {
            return 0.0;
        }

        return round(($percent / 100) * (float) ($product->pv ?? 0) * rDollar() * $quantity, 2);
    }

    private function statePrice(Stockist $stockist, Product $product): ?ProductStatePrice
    {
        return ProductStatePrice::where('state_id', $stockist->state_id)
            ->where('product_id', $product->id)
            ->first();
    }
}

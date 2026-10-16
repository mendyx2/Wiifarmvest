<?php

namespace App\Jobs;

use App\Models\FarmCycle;
use App\Models\SavingsProduct;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessFarmCycleMaturity implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300;

    public function __construct(public FarmCycle $cycle)
    {
        $this->onQueue('farm_maturity');
    }

    public function handle(): void
    {
        $cycleId = $this->cycle->id;
        $count   = 0;

        DB::transaction(function () use ($cycleId, &$count) {
            // Lock the cycle row first — prevents concurrent jobs from double-processing
            $cycle = FarmCycle::lockForUpdate()->find($cycleId);

            if (!$cycle || $cycle->payout_processed) {
                return;
            }

            $yieldRate = $cycle->actual_yield
                ?? (($cycle->min_yield + $cycle->max_yield) / 2);

            // Fetch savings inside the transaction so they reflect committed state
            $savings = SavingsProduct::where('farm_cycle_id', $cycle->id)
                            ->where('status', 'active')
                            ->get();

            foreach ($savings as $saving) {
                $user = User::lockForUpdate()->find($saving->user_id);
                if (!$user) {
                    continue;
                }

                $principal    = (float) $saving->principal;
                $startDate    = $saving->start_date ?? $saving->created_at->toDate();
                $maturityDate = $cycle->maturity_date ?? now()->toDate();
                $days         = max(1, $startDate->diffInDays($maturityDate));
                $interest     = round($principal * ($yieldRate / 100) * ($days / 365), 2);
                $totalPayout  = $principal + $interest;

                $saving->status          = 'matured';
                $saving->interest_earned = $interest;
                $saving->balance         = $totalPayout;
                $saving->matured_at      = now();
                $saving->save();

                $user->balance        += $totalPayout;
                $user->savings_wallet  = max(0, $user->savings_wallet - $principal);
                $user->save();

                $txn               = new Transaction();
                $txn->user_id      = $user->id;
                $txn->amount       = $totalPayout;
                $txn->post_balance = $user->balance;
                $txn->charge       = 0;
                $txn->trx_type     = '+';
                $txn->details      = 'Farm Yield maturity payout: ' . $cycle->name
                                     . ' (yield ' . number_format($yieldRate, 2) . '%)';
                $txn->trx          = getTrx();
                $txn->remark       = 'farm_maturity_payout';
                $txn->save();

                $count++;
            }

            $cycle->status           = 'matured';
            $cycle->matured_at       = now();
            $cycle->payout_processed = true;
            $cycle->save();
        });

        Log::info("Farm cycle [{$cycleId}] maturity payout processed for {$count} savings products.");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Farm cycle maturity job failed for cycle {$this->cycle->id}: " . $exception->getMessage());
    }
}

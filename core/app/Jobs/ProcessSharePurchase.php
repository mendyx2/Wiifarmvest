<?php

namespace App\Jobs;

use App\Models\Rinvestment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessSharePurchase implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public int $planId,
        public int $units,
        public float $unitCost
    ) {
        $this->onQueue('share-purchases');
    }

    public function handle(): void
    {
        $user = User::find($this->userId);
        $plan = Plan::find($this->planId);

        if (!$user || !$plan) {
            return;
        }

        $totalCost = $this->unitCost * $this->units;
        $trx = function_exists('getTrx') ? getTrx() : uniqid('trx_', true);

        DB::transaction(function () use ($user, $plan, $totalCost, $trx) {
            $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

            if (!$lockedUser || $lockedUser->balance < $totalCost) {
                throw new \Exception('Insufficient balance for queued share purchase.');
            }

            $lockedUser->balance -= $totalCost;
            $lockedUser->save();

            $rinvestment = new Rinvestment();
            $rinvestment->five = $totalCost;
            $rinvestment->plan_id = $plan->id;
            $rinvestment->user_id = $lockedUser->id;
            $rinvestment->unit_cost = $this->unitCost;
            $rinvestment->units = $this->units;
            $rinvestment->trx = $trx;
            $rinvestment->status = 1;
            $rinvestment->save();

            $transaction = new Transaction();
            $transaction->user_id = $lockedUser->id;
            $transaction->amount = $totalCost;
            $transaction->trx_type = '-';
            $transaction->details = 'Purchased ' . $plan->name . ' shares';
            $transaction->remark = 'share_purchase';
            $transaction->trx = $trx;
            $transaction->post_balance = $lockedUser->balance;
            $transaction->save();
        });
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error('ProcessSharePurchase failed for user ' . $this->userId . ': ' . $exception->getMessage());
    }
}

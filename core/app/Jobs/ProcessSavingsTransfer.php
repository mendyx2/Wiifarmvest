<?php

namespace App\Jobs;

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

class ProcessSavingsTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(public SavingsProduct $saving, public int $adminId)
    {
        $this->onQueue('savings_transfer');
    }

    public function handle(): void
    {
        DB::transaction(function () {
            $saving = SavingsProduct::lockForUpdate()->find($this->saving->id);

            if (!$saving || $saving->status === 'closed') {
                return;
            }

            if (!$saving->maturity_date || !$saving->maturity_date->isPast()) {
                Log::warning("ProcessSavingsTransfer: maturity date not reached for saving [{$saving->id}]. Aborting.");
                return;
            }

            $user = User::lockForUpdate()->find($saving->user_id);

            if (!$user) {
                Log::warning("ProcessSavingsTransfer: user not found for saving [{$saving->id}].");
                return;
            }

            $total = (float) $saving->balance + (float) $saving->interest_earned;

            $user->balance        += $total;
            $user->savings_wallet  = max(0, (float) $user->savings_wallet - (float) $saving->principal);
            $user->save();

            $saving->status    = 'closed';
            $saving->closed_at = now();
            $saving->save();

            $txn               = new Transaction();
            $txn->user_id      = $user->id;
            $txn->amount       = $total;
            $txn->post_balance = $user->balance;
            $txn->charge       = 0;
            $txn->trx_type     = '+';
            $txn->details      = 'Admin transferred savings & interest to Money Box: ' . $saving->name . ' (ref: ' . $saving->reference . ')';
            $txn->trx          = getTrx();
            $txn->remark       = 'admin_savings_transfer';
            $txn->save();
        });

        Log::info("Savings transfer processed: saving [{$this->saving->id}] by admin [{$this->adminId}].");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessSavingsTransfer failed for saving [{$this->saving->id}]: " . $exception->getMessage());
    }
}

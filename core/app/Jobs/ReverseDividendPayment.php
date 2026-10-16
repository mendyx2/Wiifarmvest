<?php

namespace App\Jobs;

use App\Models\DividendBatch;
use App\Models\Shtransaction;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReverseDividendPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
 
    public int $tries = 1; // No retries — reversal must not run twice

    public function __construct(public DividendBatch $batch, public int $reversedById)
    {
        $this->onQueue('dividends');
    }

    public function handle(): void
    {
        $batch = DividendBatch::find($this->batch->id);

        if (!$batch || $batch->status != 'completed') {
            Log::warning("ReverseDividendPayment skipped: batch #{$this->batch->id} is not in completed state.");
            return;
        }

        // Lock the batch row to prevent concurrent reversals
        DB::transaction(function () use ($batch) {
            $locked = DividendBatch::where('id', $batch->id)
                ->where('status', 'completed')
                ->lockForUpdate()
                ->first();

            if (!$locked) {
                return; // Another process already changed the status
            }

            // Mark as processing reversal immediately to block duplicates
            $locked->update(['status' => 'processing']);

            $transactions = Shtransaction::where('dividend_batch_id', $batch->id)
                ->where('status', 'completed')
                ->get();

            foreach ($transactions as $txn) {
                
                // Deduct from user's shares and balance
                $user = $txn->user()->lockForUpdate()->first();

                if ($user) {
                    $user->decrement('shares', $txn->amount);
                    $user->decrement('balance', $txn->amount);

                    $post_balance = $user->fresh()->balance;

                    $transaction               = new Transaction();
                    $transaction->user_id      = $user->id;
                    $transaction->amount       = $txn->amount;
                    $transaction->charge       = 0;
                    $transaction->trx_type     = '-';
                    $transaction->details      = 'Dividend reversal - Batch #' . $batch->id;
                    $transaction->remark       = 'dividend_reversal';
                    $transaction->trx          = 'REV-' . $txn->reference;
                    $transaction->bonus_type   = 9;
                    $transaction->post_balance = $post_balance;
                    $transaction->save();
                }

                // Mark the shtransaction as reversed
                $txn->update(['status' => 'reversed']);
            }

            // Mark batch as reversed
            $batch->update([
                'status'         => 'reversed',
                'reversed_at'    => now(),
                'reversed_by_id' => $this->reversedById,
            ]);
        });
    }

    public function failed(\Throwable $exception): void
    {
        $batch = DividendBatch::find($this->batch->id);

        if ($batch && $batch->status === 'processing') {
            $batch->update(['status' => 'completed']); // Roll back status so it can be retried
        }

        Log::error("ReverseDividendPayment job failed for batch #{$this->batch->id}: " . $exception->getMessage());
    }
}

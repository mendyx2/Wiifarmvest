<?php

namespace App\Jobs;

use App\Models\DividendBatch;
use App\Models\User;
use App\Models\Rinvestment;
use App\Models\Shtransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
 
class ProcessDividendPayment implements ShouldQueue
{ 
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public DividendBatch $batch)
    {
        $this->onQueue('dividends');
    }

    public function handle(): void
    {
        // Verify batch still exists and check status
        $batch = DividendBatch::find($this->batch->id);

        if (!$batch || $batch->status == 'cancelled') {
            return;
        }

        // Update batch status to processing
        $batch->update(['status' => 'processing']);

        try {
            // Fetch all relevant rinvestments based on scope
            $investments = DividendBatch::investmentsQuery($batch->scope, $batch->start_date, $batch->end_date, $batch->plan_id)->with('plan')->get();
            $processedCount = 0;

            // Wrap all investment processing in a transaction
            // If any investment fails, all changes are rolled back
            
            DB::transaction(function () use ($investments, $batch, &$processedCount) {
                foreach ($investments as $investment) {
                    // Check if batch is still valid (might be cancelled)
                    $currentBatch = DividendBatch::find($this->batch->id);
                    if (!$currentBatch || $currentBatch->status == 'cancelled') {
                        throw new \Exception('Batch was cancelled during processing.');
                    }

                    // Idempotency guard: skip if this investment was already paid for this batch
                    $alreadyPaid = Shtransaction::where('rinvestment_id', $investment->id)
                        ->where('dividend_batch_id', $batch->id)
                        ->lockForUpdate()
                        ->exists();

                    if ($alreadyPaid) {
                        $processedCount++;
                        continue;
                    }

                    // Calculate dividend amount
                    $dividendAmount = $investment->units * $batch->amount_per_unit;

                    // Each investment gets its own unique reference
                    $trx = Shtransaction::generateReference();

                    // Create transaction record
                    Shtransaction::create([
                        'user_id' => $investment->user_id,
                        'rinvestment_id' => $investment->id,
                        'dividend_batch_id' => $batch->id,
                        'amount' => $dividendAmount,
                        'amount_per_unit' => $batch->amount_per_unit,
                        'units_held' => $investment->units,
                        'description' => "Dividend Payment - {$investment->plan->name}",
                        'type' => 'credit',
                        'reference' => $trx,
                        'status' => 'completed',
                        'paid_at' => now(),
                        'metadata' => [
                            'batch_id' => $batch->id,
                            'plan_name' => $investment->plan->name,
                        ],
                    ]);

                    // Update user shares and balance with lockForUpdate for concurrency safety
                    $user = $investment->user()->lockForUpdate()->first();
                    if ($user) {
                        $user->increment('shares', $dividendAmount);
                        $user->increment('balance', $dividendAmount);

                        $post_balance = $user->fresh()->balance;
                        balance_TransactionReturn($user->id, 'Shares dividend received', $dividendAmount, 'shares_divident', $trx, $post_balance);
                    }

                    $processedCount++;
                }
            });

            // Update batch with results
            $metadata = $batch->metadata ?? [];
            $metadata['processed_at'] = now();

            $batch->update([
                'processed_count' => $processedCount,
                'failed_count' => 0,
                'status' => 'completed',
                'metadata' => $metadata,
            ]);

        } catch (\Exception $e) {
            // Update batch to failed status
            $metadata = $batch->metadata ?? [];
            $metadata['error'] = $e->getMessage();

            $batch->update([
                'status' => 'failed',
                'metadata' => $metadata,
            ]);

            \Log::error("Critical error in dividend processing for batch {$batch->id}: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Handle job failure
        $batch = DividendBatch::find($this->batch->id);

        if ($batch) {
            $metadata = $batch->metadata ?? [];
            $metadata['job_failed'] = true;
            $metadata['failure_reason'] = $exception->getMessage();

            $batch->update([
                'status' => 'failed',
                'metadata' => $metadata,
            ]);
        }

        \Log::error("Dividend payment job failed for batch {$this->batch->id}: " . $exception->getMessage());
    }
}

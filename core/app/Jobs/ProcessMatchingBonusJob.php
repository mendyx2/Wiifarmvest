<?php

namespace App\Jobs;

use App\Contracts\MatchingBonusServiceInterface;
use App\Models\Matrix;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessMatchingBonusJob
 *
 * Processes all available matching-bonus matches for a single Matrix row.
 * Dispatched by DispatchMatchingBonusCommand (artisan matching:dispatch).
 *
 * Queue   : matching
 * Retries : 3 attempts with 60-second backoff between each
 * Timeout : 30 seconds — one matrix should never take longer
 *
 * Idempotency: the service re-fetches the matrix inside a locked transaction,
 * recalculates available matches from live PV values, and no-ops if matches = 0.
 * Safe to retry without double-payment.
 */
class ProcessMatchingBonusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
 
    public int $tries   = 3;
    public int $backoff = 60;
    public int $timeout = 30;

    public function __construct(private readonly Matrix $matrix)
    {
        $this->onQueue('matching');
    }

    /**
     * MatchingBonusServiceInterface is resolved from the container — the concrete
     * class is bound in AppServiceProvider, satisfying the Dependency Inversion principle.
     */
    public function handle(MatchingBonusServiceInterface $service): void
    { 
        $service->processMatrix($this->matrix);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[MatchingBonus] Job permanently failed after max retries.', [
            'matrix_id' => $this->matrix->id,
            'user_id'   => $this->matrix->user_id,
            'error'     => $e->getMessage(),
            'trace'     => $e->getTraceAsString(),
        ]);
    }
}

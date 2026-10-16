<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMatchingBonusJob;
use App\Models\Matrix;
use App\Services\MatchingBonusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;



class DispatchMatchingBonusCommand extends Command
{
    protected $signature   = 'matching:dispatch';
    protected $description = 'Dispatch matching-bonus jobs for all eligible user matrices.';

    public function handle(): int 
    {
        $threshold = MatchingBonusService::MATCHING_NUMBER;
        $dispatched = 0;

        $this->info("Querying matrices with pv_left_pairing >= {$threshold} AND pv_right_pairing >= {$threshold}...");

        try { 
            Matrix::where('stage_id', 1)
                ->where('pv_left_pairing',  '>=', $threshold)
                ->where('pv_right_pairing', '>=', $threshold)
                ->chunkById(200, function ($matrices) use (&$dispatched) {
                    foreach ($matrices as $matrix) {
                        ProcessMatchingBonusJob::dispatch($matrix);
                        $dispatched++;
                    }
                });
        } catch (\Throwable $e) {
            Log::error('[MatchingBonus] DispatchMatchingBonusCommand failed.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->error('Dispatch failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Dispatched {$dispatched} matching-bonus job(s) to the [matching] queue.");

        Log::info('[MatchingBonus] Dispatch complete.', ['dispatched' => $dispatched]);

        return self::SUCCESS;
    }
}

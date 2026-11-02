<?php

namespace App\Console\Commands;

use App\Models\MatrixStage;
use App\Services\MatrixReconnectionService;
use Illuminate\Console\Command;

/**
 * Reconnect overtaken downline on one matrix stage, as the cron URLs
 * /recon/{stage} and /reconAll/{stage} do (MatrixReconnectionService).
 * It used to be hard-wired to one member (#37) and called an undefined
 * service, so it failed every time.
 */
class DispatchReconnectionJobs extends Command
{
    protected $signature = 'matrix:dispatch-reconnections
                            {--stage= : The stage id to process (required)}
                            {--immediate : Only the immediate downline (as the /recon URL does); default is the whole downline (as /reconAll does)}';

    protected $description = 'Reconnect overtaken downline members on one matrix stage';

    public function handle(MatrixReconnectionService $reconnection): int
    {
        $stage = (int) $this->option('stage');
        if (!$stage || !MatrixStage::whereKey($stage)->exists()) {
            $this->error('Give an existing stage id: --stage=2');
            return self::FAILURE;
        }

        $count = $this->option('immediate') ? $reconnection->reconnectImmediate($stage) : $reconnection->reconnectAll($stage);
        $this->info("Checked $count matrix record(s) on stage $stage.");

        return self::SUCCESS;
    }
}

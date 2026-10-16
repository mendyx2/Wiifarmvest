<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAcbBonusJob;
use App\Models\RepurchaseAwardCredit;
use Illuminate\Console\Command;

class ProcessAcbBonusCommand extends Command
{
    protected $signature   = 'acb:process';
    protected $description = 'Dispatch ACB bonus jobs for all unprocessed repurchase award credits.';

    public function handle(): int
    {
        $pending = RepurchaseAwardCredit::where('acb_processed', false)->pluck('id');

        if ($pending->isEmpty()) {
            $this->info('No pending ACB credits found.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($pending as $creditId) {
            ProcessAcbBonusJob::dispatch($creditId);
            $count++;
        }

        $this->info("Dispatched {$count} ACB job(s).");
        return Command::SUCCESS;
    }
}

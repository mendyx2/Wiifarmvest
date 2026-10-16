<?php

namespace App\Console\Commands;

use App\Services\LeadershipBonusService;
use Illuminate\Console\Command;

class ProcessLeadershipBonusCommand extends Command
{
    protected $signature   = 'leadership:process';
    protected $description = 'Process pending leadership bonus payments and qualify eligible leaders.';

    public function handle(LeadershipBonusService $service): int
    {
        $this->info('Starting leadership bonus processing...');
        $result = $service->processPending();

        $this->info("Qualified: {$result['qualified']} leader(s).");
        $this->info("Paid: {$result['paid']} leadership bonus log(s).");
        $this->info("Failed: {$result['failed']}.");
        $this->info("Notified: {$result['notified']} new leader(s).");

        return Command::SUCCESS;
    }
}

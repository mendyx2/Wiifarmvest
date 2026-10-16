<?php

namespace App\Console\Commands;

use App\Services\GlobalSharePoolService;
use Illuminate\Console\Command;

class DistributeGlobalSharePoolCommand extends Command
{
    protected $signature   = 'pool:distribute {--period= : Pool period e.g. Q4-2026}';
    protected $description = 'Distribute the global share pool to qualified leaders for a period.';

    public function handle(GlobalSharePoolService $service): int
    {
        $period = $this->option('period') ?: null;

        $this->info('Starting global share pool distribution...');
        $result = $service->distribute($period);

        $this->info("Period: {$result['period']}");
        $this->info("Status: {$result['status']}");

        if (isset($result['pool'])) {
            $this->info("Pool Balance: ₦" . number_format($result['pool'], 2));
        }
        if (isset($result['qualified'])) {
            $this->info("Qualified Leaders: {$result['qualified']}");
        }
        if (isset($result['paid'])) {
            $this->info("Paid: {$result['paid']}");
        }
        if (isset($result['per_share'])) {
            $this->info("Per Share: ₦" . number_format($result['per_share'], 2));
        }

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\Usdt\TronGridUnavailable;
use App\Services\Usdt\TronAddress;
use App\Services\Usdt\UsdtPayments;
use Illuminate\Console\Command;

/**
 * Finds USDT (TRC20) payments into the Wiifarm address that members did not
 * report, and settles each (App\Services\Usdt\UsdtPayments::scan). Runs
 * every minute from the scheduler; the member's payment page does the same
 * for its own deposit while it is open.
 */
class ScanUsdtPayments extends Command
{
    protected $signature = 'usdt:scan';

    protected $description = 'Look for USDT (TRC20) payments to the Wiifarm address and credit the deposits they pay';

    public function handle(UsdtPayments $usdt): int
    {
        // Not "if the gateway is on": deposits started before it was switched off may still be paid
        if (!TronAddress::isValid(UsdtPayments::settings()['address'])) {
            $this->line('No USDT address is set: nothing to scan.');
            return self::SUCCESS;
        }

        try {
            $credited = $usdt->scan();
        } catch (TronGridUnavailable $e) {
            $this->warn('TronGrid unavailable: ' . $e->getMessage());
            return self::SUCCESS;
        }

        $this->info("Credited $credited deposit(s).");

        return self::SUCCESS;
    }
}

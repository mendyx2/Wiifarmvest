<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Run payment processing every day at midnight
// Queues one ProcessPayment job per eligible user
Schedule::command('payments:dispatch')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/payments-dispatch.log'));

Schedule::command('award:check-qualifications')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/award-check.log'));

// Retry any ACB upline bonuses that were missed while the queue was down
Schedule::command('acb:process')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/acb-process.log'));

// Full database backup — gzip-compressed, timestamped, auto-purges after 30 days
Schedule::command('db:backup')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/db-backup.log'));

// Dispatch matching-bonus jobs for all eligible user matrices.
// Runs at 01:30 daily — after payment dispatch (00:00) and before award check (02:00).
Schedule::command('matching:dispatch')
    ->dailyAt('01:30')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/matching-dispatch.log'));

// Sweep unclaimed autoship balances to admin.
// Fires at 23:00 every night; the command's internal guard ensures it only
// acts when it is BOTH the last day of the month AND within hour 23.
Schedule::command('autoship:sweep')
    ->dailyAt('23:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/autoship-sweep.log'));

// USDT (TRC20) payments members did not report (App\Services\Usdt\UsdtPayments::scan)
Schedule::command('usdt:scan')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->appendOutputTo(storage_path('logs/usdt-scan.log'));

// Global Share Pool: on the 1st of January, April, July and October, pay out the
// quarter that just ended (no more money can come into it after it closes).
// Re-running is safe: a paid period is never paid again.
Schedule::command('pool:distribute --previous')
    ->cron('17 1 1 1,4,7,10 *')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/pool-distribute.log'));

// Leadership bonus: qualify new leaders and pay any matching bonus whose
// leadership share was not paid at the time (each is paid once).
Schedule::command('leadership:process')
    ->hourlyAt(25)
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/leadership-process.log'));

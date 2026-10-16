<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPayment;
use App\Models\Loan;
use App\Models\SavingsProduct;
use App\Models\User;
use Illuminate\Console\Command;

class DispatchPaymentProcessing extends Command
{
    protected $signature   = 'payments:dispatch';
    protected $description = 'Dispatch payment processing jobs for all users with active savings or loans';

    public function handle(): void
    {
        $loanUserIds   = Loan::active()->pluck('user_id');
        $savingsUserIds = SavingsProduct::active()->pluck('user_id');

        $eligibleIds = $loanUserIds->merge($savingsUserIds)->unique();

        if ($eligibleIds->isEmpty()) {
            $this->info('No eligible users found.');
            return;
        }

        $count = 0;

        User::whereIn('id', $eligibleIds)
            ->chunk(100, function ($users) use (&$count) {
                foreach ($users as $user) {
                    ProcessPayment::dispatch($user);
                    $count++;
                }
            });

        $this->info("Dispatched {$count} payment processing jobs to the [payments] queue.");
    }
}

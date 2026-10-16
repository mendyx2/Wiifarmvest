<?php

namespace App\Jobs;

use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Models\LoanSchedule;
use App\Models\SavingsProduct;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public User $user)
    {
        $this->onQueue('payments');
    }

    public function handle(): void
    {
        // Nothing to process if user has no active savings or loans
        if (!$this->userHasSavingsOrLoan()) {
            return;
        }

        DB::transaction(function () {
            $user = User::lockForUpdate()->find($this->user->id);
            if (!$user) return;

            $this->flagDefaultedLoans($user);
            $this->processLoanRepayments($user);
            $this->processSavingsContributions($user);
            $this->processSavingsMaturity($user);
        });
    }

    // ── 1. Auto Loan Repayment ─────────────────────────────────────────────────

    private function processLoanRepayments(User $user): void
    {
        $overdueSchedules = LoanSchedule::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->where('due_date', '<=', now()->toDateString())
            ->orderBy('due_date')
            ->lockForUpdate()
            ->get();

        foreach ($overdueSchedules as $schedule) {
            $amountDue = $schedule->balance_due > 0 ? $schedule->balance_due : $schedule->total;
            $loan      = Loan::lockForUpdate()->find($schedule->loan_id);

            if (!$loan || !$loan->isActive()) {
                continue;
            }

            // Insufficient balance — mark overdue and flag loan as at_risk
            if ($user->balance < $amountDue) {
                $schedule->update(['status' => 'overdue']);
                if ($loan->status === 'active') {
                    $loan->increment('installments_overdue');
                    $loan->update(['status' => 'at_risk']);
                }
                continue;
            }

            $balanceBefore   = $user->balance;
            $user->balance  -= $amountDue;
            $user->save();

            $trx               = getTrx();
            $newOutstanding    = max(0, $loan->outstanding - $amountDue);
            $newTotalRepaid    = $loan->total_repaid + $amountDue;
            $newInstallments   = $loan->installments_paid + 1;

            // Repayment audit record
            LoanRepayment::create([
                'loan_id'            => $loan->id,
                'user_id'            => $user->id,
                'amount'             => $amountDue,
                'reference'          => $trx,
                'source'             => 'automatic',
                'outstanding_before' => $loan->outstanding,
                'outstanding_after'  => $newOutstanding,
                'balance_before'     => $balanceBefore,
                'balance_after'      => $user->balance,
                'status'             => 'completed',
            ]);

            // Mark schedule as paid
            $schedule->update([
                'status'      => 'paid',
                'amount_paid' => $amountDue,
                'balance_due' => 0,
                'paid_at'     => now(),
            ]);

            // Next pending installment due date
            $nextSchedule = LoanSchedule::where('loan_id', $loan->id)
                ->where('status', 'pending')
                ->orderBy('due_date')
                ->first();

            $loanPatch = [
                'outstanding'       => $newOutstanding,
                'total_repaid'      => $newTotalRepaid,
                'installments_paid' => $newInstallments,
                'next_due_date'     => $nextSchedule?->due_date,
            ];

            if ($newOutstanding <= 0 || $newTotalRepaid >= $loan->total_repayable) {
                $loanPatch['status']     = 'cleared';
                $loanPatch['cleared_at'] = now();
            } elseif ($loan->status === 'at_risk') {
                $loanPatch['status']             = 'active';
                $loanPatch['installments_overdue'] = max(0, $loan->installments_overdue - 1);
            }

            $loan->update($loanPatch);

            balance_TransactionReturn(
                $user->id,
                'Auto loan repayment — ' . $loan->reference,
                $amountDue,
                'loan_repayment',
                $trx,
                $user->balance
            );

            notify($user, 'LOAN_AUTO_REPAYMENT', [
                'loan_ref'    => $loan->reference,
                'amount'      => showAmount($amountDue, currencyFormat: false),
                'outstanding' => showAmount($newOutstanding, currencyFormat: false),
                'trx'         => $trx,
            ]);
        }
    }

    // ── 2. Savings Contribution Deduction ──────────────────────────────────────

    private function processSavingsContributions(User $user): void
    {
        // Only target savings have periodic contributions
        SavingsProduct::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('type', 'target')
            ->whereNotNull('next_due_date')
            ->where('next_due_date', '<=', now()->toDateString())
            ->lockForUpdate()
            ->get()
            ->each(function (SavingsProduct $savings) use ($user) {
                $amount = $savings->contribution_per_cycle;

                if ($user->balance < $amount) {
                    return; // skip — insufficient funds
                }

                $user->balance -= $amount;
                $user->save();

                $cyclesDone = $savings->cycles_completed + 1;
                $allDone    = $cyclesDone >= $savings->duration_cycles;

                $nextDue = $allDone ? null : ($savings->frequency === 'weekly'
                    ? now()->addWeek()->toDateString()
                    : now()->addMonth()->toDateString());

                $savings->update([
                    'balance'          => $savings->balance + $amount,
                    'principal'        => $savings->principal + $amount,
                    'cycles_completed' => $cyclesDone,
                    'next_due_date'    => $nextDue,
                ]);

                $trx = getTrx();

                balance_TransactionReturn(
                    $user->id,
                    'Savings contribution — ' . $savings->name,
                    $amount,
                    'savings_contribution',
                    $trx,
                    $user->balance
                );

                notify($user, 'SAVINGS_CONTRIBUTION', [
                    'savings_name'    => $savings->name,
                    'amount'          => showAmount($amount, currencyFormat: false),
                    'savings_balance' => showAmount($savings->balance + $amount, currencyFormat: false),
                    'trx'             => $trx,
                ]);
            });
    }

    // ── 3. Savings Maturity Payout ─────────────────────────────────────────────

    private function processSavingsMaturity(User $user): void
    {
        SavingsProduct::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('maturity_date', '<=', now()->toDateString())
            ->lockForUpdate()
            ->get()
            ->each(function (SavingsProduct $savings) use ($user) {
                $payout = $savings->balance + $savings->interest_earned;

                $savings->update([
                    'status'     => 'matured',
                    'matured_at' => now(),
                ]);

                $user->balance += $payout;
                $user->save();

                $trx = getTrx();

                balance_TransactionReturn(
                    $user->id,
                    'Savings maturity payout — ' . $savings->name,
                    $payout,
                    'savings_maturity',
                    $trx,
                    $user->balance
                );

                notify($user, 'SAVINGS_MATURED', [
                    'savings_name' => $savings->name,
                    'amount'       => showAmount($payout, currencyFormat: false),
                    'trx'          => $trx,
                ]);
            });
    }

    // ── 4. Loan Default Flagging ───────────────────────────────────────────────

    private function flagDefaultedLoans(User $user): void
    {
        $cutoff = now()->subDays(30)->toDateString();

        Loan::where('user_id', $user->id)
            ->whereIn('status', ['active', 'at_risk'])
            ->where('next_due_date', '<', $cutoff)
            ->lockForUpdate()
            ->get()
            ->each(function (Loan $loan) {
                $loan->update([
                    'status'       => 'defaulted',
                    'defaulted_at' => now(),
                ]);

                // Mark all remaining unpaid schedules as defaulted
                LoanSchedule::where('loan_id', $loan->id)
                    ->whereIn('status', ['pending', 'overdue'])
                    ->update(['status' => 'defaulted']);

                notify($loan->user, 'LOAN_DEFAULTED', [
                    'loan_ref'    => $loan->reference,
                    'outstanding' => showAmount($loan->outstanding, currencyFormat: false),
                ]);

                Log::warning("Loan {$loan->reference} marked defaulted for user {$loan->user_id}.");
            });
    }

    // ── Guard ─────────────────────────────────────────────────────────────────

    private function userHasSavingsOrLoan(): bool
    {
        return SavingsProduct::where('user_id', $this->user->id)->active()->exists()
            || Loan::where('user_id', $this->user->id)->active()->exists();
    }

    // ── Failure hook ──────────────────────────────────────────────────────────

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessPayment job failed for user {$this->user->id}: " . $exception->getMessage());
    }
}

<?php

namespace App\Http\Controllers\User;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\LoanRepayment;
use App\Models\LoanSchedule;
use App\Models\Transaction;
use App\Models\User;
use App\Constants\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class LoanController extends Controller
{
    public function index()
    {
        $pageTitle = 'My Loans';
        $user      = auth()->user();

        $activeLoan = Loan::where('user_id', $user->id)
                        ->active()
                        ->with(['loanProduct', 'schedule'])
                        ->latest()
                        ->first();

        $schedule = $activeLoan
            ? $activeLoan->schedule()->orderBy('installment_number')->get()
            : collect();

        $loanProduct  = $activeLoan?->loanProduct;
        $minThreshold = $loanProduct?->min_savings_threshold
            ?? LoanProduct::active()->value('min_savings_threshold')
            ?? 100;

        $loanHistory = Loan::where('user_id', $user->id)
                        ->whereIn('status', ['cleared', 'rejected', 'defaulted'])
                        ->with('loanProduct')
                        ->latest()
                        ->limit(10)
                        ->get();

        return view(activeTemplate() . 'user.loans.index', compact(
            'pageTitle', 'user', 'activeLoan', 'schedule', 'minThreshold', 'loanHistory'
        ));
    }

    public function apply() 
    {
        $user         = auth()->user();
        $pageTitle    = 'Apply for a Loan';
        $loanProducts = LoanProduct::active()->get();

        // Pick first active product for eligibility rules (or use the most generous)
        $product      = $loanProducts->first();
        $minThreshold = $product?->min_savings_threshold ?? 100;
        $maxMultiple  = $product?->savings_multiple ?? 2;
        $minDays      = $product?->min_membership_days ?? 0;

        $membershipOk = $user->created_at->diffInDays(now()) >= $minDays;
        $noDefault    = !Loan::where('user_id', $user->id)->defaulted()->exists();
        $noActiveLoan = !Loan::where('user_id', $user->id)->active()->exists();

        return view(activeTemplate() . 'user.loans.apply', compact(
            'pageTitle', 'user', 'loanProducts', 'minThreshold', 'maxMultiple',
            'membershipOk', 'noDefault', 'noActiveLoan'
        ));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'loan_product_id' => 'required|exists:loan_products,id',
            'amount'          => 'required|numeric|min:1',
            'tenure'          => ['required', 'string', 'regex:/^\d+(w|m)$/i'],
            'purpose'         => 'nullable|string|max:255',
        ]);

        $product = LoanProduct::active()->findOrFail($request->loan_product_id);
        $amount  = (float) $request->amount;

        // Parse e.g. "2w" → value=2, unit='week' | "6m" → value=6, unit='month'
        preg_match('/^(\d+)(w|m)$/i', trim($request->tenure), $tm);
        $tenureValue = (int) $tm[1];
        $tenureUnit  = strtolower($tm[2]) === 'w' ? 'week' : 'month';
        $tenureRaw   = strtolower(trim($request->tenure)); // "2w" or "6m"

        // KYC check — must be verified before applying
        if ($user->kv !== Status::KYC_VERIFIED) {
            $notify[] = ['error', 'You must complete KYC verification before applying for a loan.'];
            return back()->withNotify($notify)->withInput();
        }

        // Eligibility checks
        $minDays = $product->min_membership_days;
        if ($user->created_at->diffInDays(now()) < $minDays) {
            $notify[] = ['error', "You need at least {$minDays} days of membership to qualify."];
            return back()->withNotify($notify)->withInput();
        }

        if ($user->savings_wallet < $product->min_savings_threshold) {
            $notify[] = ['error', 'Your savings balance does not meet the minimum threshold.'];
            return back()->withNotify($notify)->withInput();
        }

        if (Loan::where('user_id', $user->id)->active()->exists()) {
            $notify[] = ['error', 'You already have an active loan. Please clear it before applying for a new one.'];
            return back()->withNotify($notify)->withInput();
        }

        if (Loan::where('user_id', $user->id)->defaulted()->exists()) {
            $notify[] = ['error', 'You have a defaulted loan on record. Contact support to resolve it.'];
            return back()->withNotify($notify)->withInput();
        }

        $maxLoan = $product->maxLoanFor($user->savings_wallet);
        if ($amount > $maxLoan) {
            $notify[] = ['error', 'Requested amount exceeds your maximum eligible loan of ' . showAmount($maxLoan) . '.'];
            return back()->withNotify($notify)->withInput();
        }

        if ($amount < $product->min_loan_amount) {
            $notify[] = ['error', 'Minimum loan amount is ' . showAmount($product->min_loan_amount) . '.'];
            return back()->withNotify($notify)->withInput();
        }

        // Normalise product options for comparison (handle legacy integer values)
        $normalised = array_map(function ($t) {
            $t = strtolower(trim((string)$t));
            return preg_match('/^\d+(w|m)$/', $t) ? $t : $t . 'm';
        }, (array) $product->tenure_options);

        if (!in_array($tenureRaw, $normalised)) {
            $notify[] = ['error', 'Selected tenure is not available for this loan product.'];
            return back()->withNotify($notify)->withInput();
        }

        try { DB::transaction(function () use ($user, $product, $amount, $tenureValue, $tenureUnit, $request) {
            // Re-acquire a locked copy of the user to prevent concurrent loan applications
            $user = User::lockForUpdate()->findOrFail($user->id);

            // Re-run eligibility guards under the lock — prevents race conditions
            if ($user->savings_wallet < $product->min_savings_threshold) {
                throw new \RuntimeException('Your savings balance does not meet the minimum threshold.');
            }
            if (Loan::where('user_id', $user->id)->active()->exists()) {
                throw new \RuntimeException('You already have an active loan.');
            }
            if (Loan::where('user_id', $user->id)->defaulted()->exists()) {
                throw new \RuntimeException('You have a defaulted loan on record.');
            }

            $rate = $product->interest_rate / 100;

            // Interest: simple interest scaled to the actual period in years
            $years         = $tenureUnit === 'week'
                ? ($tenureValue * 7) / 365
                : $tenureValue / 12;
            $totalInterest     = round($amount * $rate * $years, 2);
            $totalRepayable    = $amount + $totalInterest;
            $installmentAmount = round($totalRepayable / $tenureValue, 2);

            $firstDue = $tenureUnit === 'week'
                ? now()->addWeek()->toDateString()
                : now()->addMonth()->toDateString();

            $loan = new Loan();
            $loan->user_id             = $user->id;
            $loan->loan_product_id     = $product->id;
            $loan->reference           = $this->generateReference();
            $loan->original_amount     = $amount;
            $loan->interest_rate       = $product->interest_rate;
            $loan->total_interest      = $totalInterest;
            $loan->total_repayable     = $totalRepayable;
            $loan->outstanding         = $totalRepayable;
            $loan->total_repaid        = 0;
            $loan->tenure_months       = $tenureValue;   // stores the count (weeks OR months)
            $loan->tenure_unit         = $tenureUnit;    // 'week' | 'month'
            $loan->monthly_installment = $installmentAmount;
            $loan->next_due_date       = $firstDue;
            $loan->installments_paid   = 0;
            $loan->savings_balance_at_application = $user->savings_wallet;
            $loan->purpose             = $request->purpose;
            $loan->status              = $product->auto_approve ? 'active' : 'pending';

            if ($product->auto_approve) {
                $loan->approved_at  = now();
                $loan->disbursed_at = now();

                $user->balance += $amount;
                $user->save();

                $txn               = new Transaction();
                $txn->user_id      = $user->id;
                $txn->amount       = $amount;
                $txn->post_balance = $user->balance;
                $txn->charge       = 0;
                $txn->trx_type     = '+';
                $txn->details      = 'Loan disbursed: ' . $loan->reference;
                $txn->trx          = $loan->reference;
                $txn->remark       = 'loan_disbursement';
                $txn->save();
            }

            $loan->save();

            // Generate repayment schedule — one installment per week or month
            $principalPer = round($amount / $tenureValue, 2);
            $interestPer  = round($totalInterest / $tenureValue, 2);

            for ($i = 1; $i <= $tenureValue; $i++) {
                $dueDate = $tenureUnit === 'week'
                    ? now()->addWeeks($i)->toDateString()
                    : now()->addMonths($i)->toDateString();

                $schedule                     = new LoanSchedule();
                $schedule->loan_id            = $loan->id;
                $schedule->user_id            = $user->id;
                $schedule->installment_number = $i;
                $schedule->due_date           = $dueDate;
                $schedule->principal          = $principalPer;
                $schedule->interest           = $interestPer;
                $schedule->total              = $principalPer + $interestPer;
                $schedule->balance_due        = $principalPer + $interestPer;
                $schedule->status             = 'pending';
                $schedule->save();
            }
        }); } catch (\RuntimeException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify)->withInput();
        }

        $statusMsg = $product->auto_approve
            ? 'Loan approved and disbursed to your Money Box.'
            : 'Loan application submitted. Awaiting admin approval.';

        $notify[] = ['success', $statusMsg];
        return redirect()->route('user.loans.index')->withNotify($notify);
    }

    public function show($id)
    {
        $user      = auth()->user();
        $pageTitle = 'Loan Detail';
        $loan      = Loan::where('user_id', $user->id)
                        ->with(['loanProduct', 'schedule', 'repayments'])
                        ->find($id);

        if (!$loan) {
            $notify[] = ['error', 'Loan not found.'];
            return redirect()->route('user.loans.index')->withNotify($notify);
        }

        $schedule   = $loan->schedule()->orderBy('installment_number')->get();
        $repayments = $loan->repayments()->latest()->get();

        return view(activeTemplate() . 'user.loans.show', compact(
            'pageTitle', 'user', 'loan', 'schedule', 'repayments'
        ));
    }

    public function repay(Request $request, $id)
    {
        $userId = auth()->id();

        // Pre-flight ownership check (fast, before acquiring locks)
        $loanExists = Loan::where('user_id', $userId)
                        ->whereIn('status', ['active', 'at_risk'])
                        ->where('id', $id)
                        ->exists();

        if (!$loanExists) {
            abort(404);
        }

        $request->validate([
            'amount' => 'required|numeric|min:1|max:' . auth()->user()->balance,
        ]);

        $requestedAmount = (float) $request->amount;
        $applied         = 0;

        try { DB::transaction(function () use ($userId, $id, $requestedAmount, &$applied) {
            // Re-fetch both records under lock — prevents concurrent repayments from racing
            $user = User::lockForUpdate()->findOrFail($userId);
            $loan = Loan::where('user_id', $userId)
                        ->whereIn('status', ['active', 'at_risk'])
                        ->lockForUpdate()
                        ->findOrFail($id);

            if ($user->balance < $requestedAmount) {
                throw new \RuntimeException('Insufficient Money Box balance.');
            }

            // Cap repayment at outstanding — never allow overpayment
            $amount = min($requestedAmount, (float) $loan->outstanding);

            $balanceBefore     = $user->balance;
            $outstandingBefore = $loan->outstanding;

            $user->balance -= $amount;
            $user->save();

            $loan->total_repaid += $amount;
            $loan->outstanding  -= $amount;

            if ($loan->outstanding <= 0) {
                $loan->outstanding = 0;
                $loan->status      = 'cleared';
                $loan->cleared_at  = now();
            }

            $this->applyRepaymentToSchedule($loan, $amount);
            $loan->save();

            $repayment                     = new LoanRepayment();
            $repayment->loan_id            = $loan->id;
            $repayment->user_id            = $user->id;
            $repayment->amount             = $amount;
            $repayment->reference          = getTrx();
            $repayment->source             = 'manual';
            $repayment->outstanding_before = $outstandingBefore;
            $repayment->outstanding_after  = $loan->outstanding;
            $repayment->balance_before     = $balanceBefore;
            $repayment->balance_after      = $user->balance;
            $repayment->status             = 'completed';
            $repayment->save();

            $txn               = new Transaction();
            $txn->user_id      = $user->id;
            $txn->amount       = $amount;
            $txn->post_balance = $user->balance;
            $txn->charge       = 0;
            $txn->trx_type     = '-';
            $txn->details      = 'Loan repayment: ' . $loan->reference;
            $txn->trx          = $repayment->reference;
            $txn->remark       = 'loan_repayment';
            $txn->save();

            $applied = $amount;
        }); } catch (\RuntimeException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Repayment of ' . showAmount($applied) . ' applied to your loan successfully.'];
        return back()->withNotify($notify);
    }

    public function history()
    {
        $pageTitle   = 'Loan History';
        $user        = auth()->user();
        $loanHistory = Loan::where('user_id', $user->id)
                            ->whereIn('status', ['cleared', 'rejected', 'defaulted', 'pending'])
                            ->with('loanProduct')
                            ->latest()
                            ->get();

        return view(activeTemplate() . 'user.loans.history', compact('pageTitle', 'user', 'loanHistory'));
    }

    private function applyRepaymentToSchedule(Loan $loan, float $amount): void
    {
        $remaining = $amount;
        // lockForUpdate() here is essential — this method is always called inside
        // DB::transaction(). Without the lock, two concurrent repayments can both
        // read the same schedule rows and double-apply payments to them.
        $schedules = $loan->schedule()
                         ->whereIn('status', ['pending', 'partial', 'overdue'])
                         ->lockForUpdate()
                         ->orderBy('installment_number')
                         ->get();

        foreach ($schedules as $schedule) {
            if ($remaining <= 0) break;

            $due = $schedule->balance_due;

            if ($remaining >= $due) {
                $schedule->amount_paid += $due;
                $schedule->balance_due  = 0;
                $schedule->status       = 'paid';
                $schedule->paid_at      = now();
                $remaining             -= $due;
                $loan->installments_paid++;
            } else {
                $schedule->amount_paid += $remaining;
                $schedule->balance_due -= $remaining;
                $schedule->status       = 'partial';
                $remaining              = 0;
            }

            $schedule->save();
        }

        // Advance next_due_date to the next unpaid installment
        $nextUnpaid = $loan->schedule()
                          ->whereNotIn('status', ['paid'])
                          ->orderBy('due_date')
                          ->first();

        $loan->next_due_date = $nextUnpaid?->due_date ?? null;
    }

    private function generateReference(): string
    {
        $ref = 'LN-' . date('Y') . '-' . str_pad(
            Loan::whereYear('created_at', date('Y'))->count() + 1,
            5, '0', STR_PAD_LEFT
        );
        return Loan::where('reference', $ref)->exists()
            ? 'LN-' . date('Y') . '-' . getTrx()
            : $ref;
    }
}

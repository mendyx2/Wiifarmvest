<?php

namespace App\Http\Controllers\Admin;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Transaction;
use App\Constants\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AdminLoanController extends Controller
{
    /* ── Loan Products (CRUD) ─────────────────────────── */

    public function products()
    {
        $pageTitle = 'Loan Products';
        $products  = LoanProduct::latest()->paginate(getPaginate());
        return view('admin.loans.products', compact('pageTitle', 'products'));
    }

    public function storeProduct(Request $request, $id = 0)
    {
        $request->validate([
            'name'                 => 'required|string|max:100',
            'description'          => 'nullable|string|max:255',
            'min_membership_days'  => 'required|integer|min:0',
            'min_savings_threshold'=> 'required|numeric|min:0',
            'savings_multiple'     => 'required|numeric|min:1|max:20',
            'max_loan_amount'      => 'nullable|numeric|min:0',
            'min_loan_amount'      => 'required|numeric|min:0',
            'interest_rate'        => 'required|numeric|min:0|max:100',
            'tenure_options'       => ['required', 'string', function ($attr, $val, $fail) {
                $parts = array_filter(array_map('trim', explode(',', $val)));
                if (empty($parts)) { $fail('At least one tenure option is required.'); return; }
                foreach ($parts as $part) {
                    if (!preg_match('/^\d+(w|m)$/i', $part) && !preg_match('/^\d+$/', $part)) {
                        $fail("Invalid tenure \"$part\". Use e.g. 2w (weeks) or 6m (months).");
                    }
                }
            }],
            'auto_approve'         => 'nullable|boolean',
            'is_active'            => 'nullable|boolean',
        ]);

        // Normalise each token: bare integers become months (e.g. "6" → "6m")
        $tenureOptions = array_values(array_filter(array_map(function ($t) {
            $t = strtolower(trim($t));
            if (preg_match('/^\d+(w|m)$/', $t)) return $t;
            if (preg_match('/^\d+$/', $t))       return $t . 'm';
            return null;
        }, explode(',', $request->tenure_options))));

        $product = $id ? LoanProduct::findOrFail($id) : new LoanProduct();
        $product->name                  = $request->name;
        $product->description           = $request->description;
        $product->min_membership_days   = $request->min_membership_days;
        $product->min_savings_threshold = $request->min_savings_threshold;
        $product->savings_multiple      = $request->savings_multiple;
        $product->max_loan_amount       = $request->max_loan_amount ?: null;
        $product->min_loan_amount       = $request->min_loan_amount;
        $product->interest_rate         = $request->interest_rate;
        $product->tenure_options        = $tenureOptions;
        $product->auto_approve          = $request->boolean('auto_approve');
        $product->is_active             = $request->boolean('is_active', true);
        $product->created_by            = auth('admin')->id();
        $product->save();

        $notify[] = ['success', 'Loan product ' . ($id ? 'updated' : 'created') . ' successfully.'];
        return back()->withNotify($notify);
    }

    public function toggleProduct($id)
    {
        $product = LoanProduct::findOrFail($id);
        $product->is_active = !$product->is_active;
        $product->save();

        $notify[] = ['success', 'Loan product ' . ($product->is_active ? 'enabled' : 'disabled') . '.'];
        return back()->withNotify($notify);
    }

    /* ── All Loans ────────────────────────────────────── */

    public function index(Request $request)
    {
        $pageTitle = 'All Loans';
        $loans     = Loan::with(['user', 'loanProduct'])
                        ->when($request->status, fn($q) => $q->where('status', $request->status))
                        ->when($request->search, fn($q) => $q->whereHas('user', fn($u) =>
                            $u->where('username', 'like', '%'.$request->search.'%')
                              ->orWhere('email', 'like', '%'.$request->search.'%')
                        ))
                        ->latest()
                        ->paginate(getPaginate());

        $summary = Loan::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status IN ("active","at_risk") THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN status = "defaulted" THEN 1 ELSE 0 END) as defaulted_count,
            SUM(original_amount) as total_disbursed,
            SUM(outstanding) as total_outstanding
        ')->first();

        return view('admin.loans.index', compact('pageTitle', 'loans', 'summary'));
    }

    public function pending(Request $request)
    {
        $pageTitle = 'Pending Loan Applications';
        $loans     = Loan::with(['user', 'loanProduct'])
                        ->where('status', 'pending')
                        ->latest()
                        ->paginate(getPaginate());

        return view('admin.loans.pending', compact('pageTitle', 'loans'));
    }

    public function show($id)
    {
        $pageTitle = 'Loan Detail';
        $loan      = Loan::with(['user', 'loanProduct', 'schedule', 'repayments'])->findOrFail($id);
        return view('admin.loans.show', compact('pageTitle', 'loan'));
    }

    public function approve(Request $request, $id)
    {
        $error     = null;
        $reference = null;

        DB::transaction(function () use ($id, &$error, &$reference) {
            $loan = Loan::lockForUpdate()->find($id);

            if (!$loan || $loan->status != 'pending') {
                $error = 'Loan not found or is no longer pending.';
                return;
            }

            $user = \App\Models\User::lockForUpdate()->find($loan->user_id);

            if (!$user) {
                $error = 'User not found for this loan.';
                return;
            }

            if ($user->kv != Status::KYC_VERIFIED) {
                $error = 'User KYC is not verified. Complete KYC verification before approving this loan.';
                return;
            }

            $user->balance     += $loan->original_amount;
            $user->save();

            $loan->status       = 'active';
            $loan->approved_at  = now();
            $loan->disbursed_at = now();
            $loan->approved_by  = auth('admin')->id();
            $loan->next_due_date = ($loan->tenure_unit == 'week')
                ? now()->addWeek()->toDateString()
                : now()->addMonth()->toDateString();
            $loan->save();

            $txn               = new Transaction();
            $txn->user_id      = $user->id;
            $txn->amount       = $loan->original_amount;
            $txn->post_balance = $user->balance;
            $txn->charge       = 0;
            $txn->trx_type     = '+';
            $txn->details      = 'Loan disbursed: ' . $loan->reference;
            $txn->trx          = $loan->reference;
            $txn->remark       = 'loan_disbursement';
            $txn->save();

            $reference = $loan->reference;
        });

        if ($error) {
            $notify[] = ['error', $error];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Loan ' . $reference . ' approved and disbursed.'];
        return back()->withNotify($notify);
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $updated = DB::transaction(function () use ($request, $id) {
            $loan = Loan::lockForUpdate()->find($id);

            if (!$loan || $loan->status != 'pending') {
                return false;
            }

            $loan->status           = 'rejected';
            $loan->rejection_reason = $request->rejection_reason;
            $loan->save();

            return true;
        });

        if (!$updated) {
            $notify[] = ['error', 'Loan not found or is no longer pending.'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Loan application rejected.'];
        return back()->withNotify($notify);
    }
}

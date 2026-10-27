<?php

namespace App\Http\Controllers\Admin;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\Transaction;
use App\Constants\Status;
use Illuminate\Http\Request;
use App\Support\AdminPresenter;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

/**
 * Savings & Loans → loan side (Pages/Admin/Loans/*): the loan products, one
 * list of loans (Pending Applications is that list with the status set),
 * and one loan with Approve & Disburse / Reject.
 */
class AdminLoanController extends Controller
{
    private const STATUSES = ['pending', 'active', 'at_risk', 'defaulted', 'cleared', 'rejected'];

    /* ── Loan Products (CRUD) ─────────────────────────── */

    public function products()
    {
        $pageTitle = 'Loan Products';
        $products  = LoanProduct::latest()->paginate(getPaginate())->withQueryString()
            ->through(fn (LoanProduct $p) => [
                'id'          => $p->id,
                'name'        => (string) $p->name,
                'description' => (string) $p->description,
                'rate'        => (float) $p->interest_rate . '% p.a.',
                'min'         => showAmount($p->min_loan_amount),
                'max'         => $p->max_loan_amount ? showAmount($p->max_loan_amount) : null,
                'multiple'    => (float) $p->savings_multiple . '×',
                'minSavings'  => showAmount($p->min_savings_threshold),
                'tenures'     => array_map(fn ($t) => $this->tenureChip((string) $t), (array) $p->tenure_options),
                'autoApprove' => (bool) $p->auto_approve,
                'active'      => (bool) $p->is_active,
                'form'        => [
                    'name'                  => (string) $p->name,
                    'description'           => (string) $p->description,
                    'min_membership_days'   => (string) (int) $p->min_membership_days,
                    'min_savings_threshold' => (string) (float) $p->min_savings_threshold,
                    'savings_multiple'      => (string) (float) $p->savings_multiple,
                    'max_loan_amount'       => $p->max_loan_amount ? (string) (float) $p->max_loan_amount : '',
                    'min_loan_amount'       => (string) (float) $p->min_loan_amount,
                    'interest_rate'         => (string) (float) $p->interest_rate,
                    'tenure_options'        => implode(',', (array) $p->tenure_options),
                    'auto_approve'          => (bool) $p->auto_approve,
                    'is_active'             => (bool) $p->is_active,
                ],
            ]);

        return Inertia::render('Admin/Loans/Products', [
            'products' => $products,
            'currency' => gs('cur_sym'),
            'urls'     => [
                'store'  => route('admin.loans.products.store'),
                'update' => route('admin.loans.products.store', ':id'),
                'toggle' => route('admin.loans.products.toggle', ':id'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** "6m" → {label: "6mo", weeks: false}; "2w" → {label: "2wk", weeks: true} */
    private function tenureChip(string $tenure): array
    {
        preg_match('/^(\d+)(w|m)?$/', strtolower(trim($tenure)), $m);
        $weeks = ($m[2] ?? 'm') === 'w';
        return ['label' => ($m[1] ?? $tenure) . ($weeks ? 'wk' : 'mo'), 'weeks' => $weeks];
    }

    public function storeProduct(Request $request, $id = 0)
    {
        $request->validate([
            'name'                 => 'required|string|max:100',
            'description'          => 'nullable|string|max:255',
            'min_membership_days'  => 'required|integer|min:0',
            'min_savings_threshold'=> 'required|numeric|min:0',
            'savings_multiple'     => 'required|numeric|min:1|max:20',
            'max_loan_amount'      => 'nullable|numeric|gte:min_loan_amount',
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
        // Who created it: an edit used to overwrite this with the editor
        if (!$product->exists) {
            $product->created_by = auth('admin')->id();
        }
        $product->save();

        $notify[] = ['success', 'Loan product ' . ($id ? 'updated' : 'created') . ' successfully.'];
        return back()->withNotify($notify);
    }

    /** Enable / disable, by the state the admin saw (a repeat says "already"). */
    public function toggleProduct(Request $request, $id)
    {
        $request->validate(['state' => 'required|in:enable,disable']);

        $product = LoanProduct::findOrFail($id);
        $active  = $request->state === 'enable';

        if ((bool) $product->is_active === $active) {
            $notify[] = ['info', 'Loan product is already ' . ($active ? 'enabled' : 'disabled') . '.'];
            return back()->withNotify($notify);
        }

        $product->is_active = $active;
        $product->save();

        $notify[] = ['success', 'Loan product ' . ($active ? 'enabled' : 'disabled') . '.'];
        return back()->withNotify($notify);
    }

    /* ── All Loans ────────────────────────────────────── */

    public function index(Request $request)
    {
        return $this->listPage($request, 'All Loans');
    }

    /** Pending Loan Applications: the loan list with the status set to pending. */
    public function pending(Request $request)
    {
        return $this->listPage($request, 'Pending Loan Applications', 'pending');
    }

    private function listPage(Request $request, string $pageTitle, string $preset = '')
    {
        // The pending page is always pending; All Loans takes ?status=
        $status = $preset ?: (in_array($request->status, self::STATUSES, true) ? $request->status : '');
        $search = trim((string) $request->search);

        $loans = Loan::with(['user:id,firstname,lastname,username,email', 'loanProduct:id,name'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('reference', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u
                    ->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))))
            ->latest()
            ->paginate(getPaginate())
            ->withQueryString()
            ->through(fn (Loan $loan) => [
                'id'          => $loan->id,
                'user'        => AdminPresenter::user($loan->user),
                'email'       => (string) $loan->user?->email,
                'reference'   => (string) $loan->reference,
                'product'     => (string) ($loan->loanProduct->name ?? '—'),
                'amount'      => showAmount($loan->original_amount),
                'outstanding' => showAmount($loan->outstanding),
                'owing'       => $loan->outstanding > 0,
                'repayable'   => showAmount($loan->total_repayable),
                'savings'     => showAmount($loan->savings_balance_at_application),
                'purpose'     => (string) $loan->purpose,
                'rate'        => (float) $loan->interest_rate . '%',
                'tenure'      => $this->tenureLabel($loan),
                'applied'     => $loan->created_at->format('M d, Y'),
                'nextDue'     => $loan->next_due_date?->format('M d, Y'),
                'status'      => $this->statusBadge($loan->status),
                'pending'     => $loan->status === 'pending',
                'url'         => route('admin.loans.show', $loan->id),
            ]);

        $summary = Loan::selectRaw("
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_count,
            COALESCE(SUM(original_amount), 0) as total_disbursed,
            COALESCE(SUM(outstanding), 0) as total_outstanding
        ")->first();

        return Inertia::render('Admin/Loans/Index', [
            'title'   => $pageTitle,
            'loans'   => $loans,
            'summary' => [
                'total'       => (int) $summary->total,
                'pending'     => (int) $summary->pending_count,
                'disbursed'   => showAmount($summary->total_disbursed),
                'outstanding' => showAmount($summary->total_outstanding),
            ],
            'filters' => ['search' => $search, 'status' => $status],
            'fixed'   => $preset !== '',
            'urls'    => [
                'index'    => route('admin.loans.index'),
                'pending'  => route('admin.loans.pending'),
                'products' => route('admin.loans.products'),
                'approve'  => route('admin.loans.approve', ':id'),
                'reject'   => route('admin.loans.reject', ':id'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function show($id)
    {
        $pageTitle = 'Loan Detail';
        $loan      = Loan::with(['user', 'loanProduct', 'schedule', 'repayments'])->findOrFail($id);
        $unit      = $loan->tenure_unit === 'week' ? 'Week' : 'Month';

        return Inertia::render('Admin/Loans/Show', [
            'loan' => [
                'id'          => $loan->id,
                'reference'   => (string) $loan->reference,
                'status'      => $this->statusBadge($loan->status),
                'pending'     => $loan->status === 'pending',
                'user'        => AdminPresenter::user($loan->user),
                'product'     => (string) ($loan->loanProduct->name ?? '—'),
                'amount'      => showAmount($loan->original_amount),
                'rate'        => (float) $loan->interest_rate . '% p.a.',
                'interest'    => showAmount($loan->total_interest),
                'repayable'   => showAmount($loan->total_repayable),
                'outstanding' => showAmount($loan->outstanding),
                'owing'       => $loan->outstanding > 0,
                'repaid'      => showAmount($loan->total_repaid),
                'progress'    => $loan->total_repayable > 0 ? (int) round(min(100, $loan->total_repaid / $loan->total_repayable * 100)) : 0,
                'tenure'      => $this->tenureLabel($loan, long: true),
                'installmentLabel' => $unit . 'ly Installment',
                'installment' => showAmount($loan->monthly_installment),
                'nextDue'     => $loan->next_due_date?->format('M d, Y'),
                'applied'     => $loan->created_at->format('M d, Y H:i'),
                'approved'    => $loan->approved_at?->format('M d, Y H:i'),
                'cleared'     => $loan->cleared_at?->format('M d, Y H:i'),
                'savings'     => showAmount($loan->savings_balance_at_application),
                'purpose'     => (string) $loan->purpose,
                'rejection'   => (string) $loan->rejection_reason,
            ],
            'schedule' => $loan->schedule->map(fn ($i) => [
                'id'        => $i->id,
                'number'    => (int) $i->installment_number,
                'due'       => $i->due_date?->format('M d, Y'),
                'principal' => showAmount($i->principal),
                'interest'  => showAmount($i->interest),
                'total'     => showAmount($i->total),
                'paid'      => showAmount($i->amount_paid),
                'anyPaid'   => $i->amount_paid > 0,
                'status'    => AdminPresenter::badge(ucfirst((string) $i->status), ['paid' => 'badge--success', 'partial' => 'badge--info', 'overdue' => 'badge--danger', 'defaulted' => 'badge--danger'][$i->status] ?? 'badge--dark'),
            ]),
            'repayments' => $loan->repayments->map(fn ($r) => [
                'id'        => $r->id,
                'date'      => $r->created_at->format('M d, Y'),
                'reference' => (string) $r->reference,
                'amount'    => showAmount($r->amount),
                'source'    => AdminPresenter::badge(ucfirst((string) $r->source), $r->source === 'manual' ? 'badge--info' : 'badge--success'),
                'after'     => showAmount($r->outstanding_after),
            ]),
            'urls' => [
                'index'   => route('admin.loans.index'),
                'approve' => route('admin.loans.approve', $loan->id),
                'reject'  => route('admin.loans.reject', $loan->id),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** "6m" in lists, "6 months" on the loan; weekly loans used to show as months. */
    private function tenureLabel(Loan $loan, bool $long = false): string
    {
        $n     = (int) $loan->tenure_months;
        $weeks = $loan->tenure_unit === 'week';
        if (!$long) {
            return $n . ($weeks ? 'w' : 'm');
        }
        return $n . ' ' . ($weeks ? 'week' : 'month') . ($n === 1 ? '' : 's');
    }

    private function statusBadge(?string $status): array
    {
        $class = ['pending' => 'badge--warning', 'active' => 'badge--success', 'at_risk' => 'badge--warning', 'defaulted' => 'badge--danger', 'cleared' => 'badge--primary', 'rejected' => 'badge--danger'][$status] ?? 'badge--dark';
        return AdminPresenter::badge(ucfirst(str_replace('_', ' ', (string) $status)), $class);
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

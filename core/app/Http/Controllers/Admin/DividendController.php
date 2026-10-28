<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessDividendPayment;
use App\Jobs\ReverseDividendPayment;
use App\Models\DividendBatch;
use App\Models\Plan;
use App\Models\Rinvestment;
use App\Models\Shtransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Manage FFO → dividends (Pages/Admin/Dividend/*): pay an amount per unit to
 * every active investment (all, a plan, or bought in a date range), follow
 * the batches, and see each investor's dividends.
 *
 * A batch is paid by ProcessDividendPayment on the "dividends" queue, and
 * reversed by ReverseDividendPayment.
 */
class DividendController extends Controller
{
    private const OPEN    = ['pending', 'processing'];
    private const CLOSED  = ['completed', 'cancelled', 'failed', 'reversed'];
    private const TONES   = ['pending' => 'warning', 'processing' => 'info', 'completed' => 'success', 'cancelled' => 'dark', 'failed' => 'danger', 'reversed' => 'dark'];

    public function index()
    {
        return $this->batches('open');
    }

    public function history()
    {
        return $this->batches('history');
    }

    /**
     * The batches waiting or being paid, and the finished ones. Reversed
     * batches used to disappear (history listed completed, cancelled and
     * failed only), and a batch stuck in "processing" was listed nowhere.
     */
    private function batches(string $tab)
    {
        $pageTitle = $tab === 'open' ? 'Dividend Payments' : 'Dividend Payment History';
        $batches   = DividendBatch::with('creator', 'plan')->whereIn('status', $tab === 'open' ? self::OPEN : self::CLOSED)->orderByDesc('id')->paginate(getPaginate());

        return Inertia::render('Admin/Dividend/Batches', [
            'title'   => $pageTitle,
            'tab'     => $tab,
            'stats'   => [
                'shareholders' => User::whereHas('rinvestment', fn ($q) => $q->where('status', 1))->count(),
                'units'        => number_format((float) Rinvestment::where('status', 1)->sum('units')),
                'distributed'  => showAmount(Shtransaction::where('status', 'completed')->sum('amount')),
            ],
            // A batch still pending after 10 minutes means no worker runs the "dividends" queue
            'stalled' => DividendBatch::where('status', 'pending')->where('created_at', '<', now()->subMinutes(10))->count(),
            'batches' => $batches->through(fn (DividendBatch $b) => $this->batchRow($b)),
            'urls'    => [
                'open'    => route('admin.dividend.index'),
                'history' => route('admin.dividend.history'),
                'create'  => route('admin.dividend.create'),
                'investors' => route('admin.dividend.investors'),
                'cancel'  => route('admin.dividend.cancel', ':id'),
                'reverse' => route('admin.dividend.reverse', ':id'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    private function batchRow(DividendBatch $b): array
    {
        return [
            'id'          => $b->id,
            'by'          => (string) ($b->creator?->name ?: '—'),
            'scope'       => $b->scope_display,
            'plan'        => (string) ($b->plan?->name ?: 'All plans'),
            'perUnit'     => showAmount($b->amount_per_unit),
            'users'       => (int) $b->total_users,
            'units'       => number_format((float) $b->total_units),
            'amount'      => showAmount($b->total_amount),
            'processed'   => (int) $b->processed_count,
            'failed'      => (int) $b->failed_count,
            'status'      => [ucfirst((string) $b->status), self::TONES[$b->status] ?? 'dark'],
            'canCancel'   => $b->canCancel(),
            'canReverse'  => $b->canReverse(),
            'error'       => $b->status === 'failed' ? (string) (($b->metadata['error'] ?? null) ?: ($b->metadata['failure_reason'] ?? '')) : '',
            'date'        => showDateTime($b->created_at),
            'url'         => route('admin.dividend.detail', $b->id),
        ];
    }

    public function create()
    {
        $pageTitle = 'Create Dividend Payment';

        return Inertia::render('Admin/Dividend/Create', [
            'plans' => Plan::where('status', 1)->orderBy('name')->get()->map(fn (Plan $p) => [
                'id'    => $p->id,
                'name'  => (string) $p->name,
                'units' => number_format((float) Rinvestment::where('status', 1)->where('plan_id', $p->id)->sum('units')),
            ]),
            'currency' => gs('cur_sym'),
            'urls'     => ['store' => route('admin.dividend.store'), 'preview' => route('admin.dividend.preview'), 'back' => route('admin.dividend.index')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    private function rules(): array
    {
        return [
            'amount_per_unit' => 'required|numeric|min:0.01|max:1000000',
            'scope'           => 'required|in:all-time,date-range',
            'start_date'      => 'required_if:scope,date-range|nullable|date',
            'end_date'        => 'required_if:scope,date-range|nullable|date|after_or_equal:start_date',
            'plan_id'         => 'nullable|integer|exists:plans,id',
            'notes'           => 'nullable|string|max:1000',
        ];
    }

    /** What a batch would pay, before it is created: members, units and total. */
    public function preview(Request $request)
    {
        $data  = $request->validate($this->rules());
        $query = DividendBatch::investmentsQuery($data['scope'], $data['start_date'] ?? null, $data['end_date'] ?? null, $data['plan_id'] ?? null);
        $units = (float) (clone $query)->sum('units');

        return response()->json([
            'investments' => (clone $query)->count(),
            'users'       => (clone $query)->distinct()->count('user_id'),
            'units'       => number_format($units),
            'amount'      => showAmount($units * (float) $data['amount_per_unit']),
        ]);
    }

    public function store(Request $request)
    {
        $data    = $request->validate($this->rules());
        $adminId = auth()->guard('admin')->id();
        $range   = $data['scope'] === 'date-range';

        // Idempotency guard: a second click within 2 minutes shows the first batch
        $recent = DividendBatch::where('created_by_id', $adminId)
            ->where('amount_per_unit', $data['amount_per_unit'])
            ->where('scope', $data['scope'])
            ->where('plan_id', $data['plan_id'] ?? null)
            ->where('created_at', '>=', now()->subMinutes(2))
            ->first();
        if ($recent) {
            $notify[] = ['warning', 'A similar dividend batch was submitted a moment ago. This is that batch; nothing new was created.'];
            return to_route('admin.dividend.detail', $recent->id)->withNotify($notify);
        }

        $investments = DividendBatch::investmentsQuery($data['scope'], $data['start_date'] ?? null, $data['end_date'] ?? null, $data['plan_id'] ?? null)->get(['id', 'user_id', 'units']);
        if ($investments->isEmpty()) {
            $notify[] = ['error', 'No investments match these choices.'];
            return back()->withNotify($notify);
        }

        $totalUnits  = $investments->sum('units');
        $totalAmount = $totalUnits * $data['amount_per_unit'];
        $totalUsers  = $investments->pluck('user_id')->unique()->count();

        $batch = DividendBatch::create([
            'created_by_id'   => $adminId,
            'amount_per_unit' => $data['amount_per_unit'],
            'scope'           => $data['scope'],
            'start_date'      => $range ? $data['start_date'] : null,
            'end_date'        => $range ? $data['end_date'] : null,
            'plan_id'         => $data['plan_id'] ?? null,
            'status'          => 'pending',
            'total_users'     => $totalUsers,
            'total_units'     => $totalUnits,
            'total_amount'    => $totalAmount,
            'notes'           => $data['notes'] ?? null,
        ]);

        ProcessDividendPayment::dispatch($batch);

        $notify[] = ['success', "Dividend batch #{$batch->id} created: {$totalUsers} members with " . number_format($totalUnits) . ' units will receive ' . showAmount($totalAmount) . '.'];
        return to_route('admin.dividend.detail', $batch->id)->withNotify($notify);
    }

    public function detail(Request $request, $batchId)
    {
        $batch     = DividendBatch::with('creator', 'plan')->findOrFail($batchId);
        $pageTitle = "Dividend Batch #{$batch->id}";
        $search    = trim((string) $request->search);

        $transactions = $batch->shtransactions()->with('user', 'rinvestment.plan')
            ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $u->where('username', 'like', "%{$search}%")))
            ->orderBy('id')
            ->paginate(getPaginate())->withQueryString();

        return Inertia::render('Admin/Dividend/Detail', [
            'batch'        => $this->batchRow($batch) + ['notes' => (string) $batch->notes, 'reversedAt' => $batch->reversed_at ? showDateTime($batch->reversed_at) : null],
            'search'       => $search,
            'transactions' => $transactions->through(fn (Shtransaction $t) => [
                'id'        => $t->id,
                'reference' => (string) $t->reference,
                'member'    => $t->user ? ['name' => (string) $t->user->fullname, 'username' => (string) $t->user->username, 'url' => route('admin.dividend.user.dividends', $t->user_id)] : null,
                'plan'      => (string) ($t->rinvestment?->plan?->name ?: '—'),
                'units'     => number_format((float) $t->units_held),
                'perUnit'   => showAmount($t->amount_per_unit),
                'amount'    => showAmount($t->amount),
                'status'    => [ucfirst((string) $t->status), self::TONES[$t->status] ?? 'dark'],
                'date'      => showDateTime($t->created_at),
            ]),
            'urls' => [
                'self'    => route('admin.dividend.detail', $batch->id),
                'cancel'  => route('admin.dividend.cancel', $batch->id),
                'reverse' => route('admin.dividend.reverse', $batch->id),
                'back'    => route(in_array($batch->status, self::OPEN, true) ? 'admin.dividend.index' : 'admin.dividend.history'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** The old AJAX search of a batch's payments: the page searches with ?search= now. */
    public function searchTransactions(Request $request, $batchId)
    {
        return to_route('admin.dividend.detail', ['batchId' => $batchId, 'search' => $request->query('search')]);
    }

    public function cancel($batchId)
    {
        $batch = DividendBatch::findOrFail($batchId);
        if (!$batch->cancel()) {
            $notify[] = ['error', 'This batch cannot be cancelled: it is already finished.'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', "Dividend batch #{$batch->id} cancelled."];
        return to_route('admin.dividend.history')->withNotify($notify);
    }

    public function reverse($batchId)
    {
        $batch = DividendBatch::findOrFail($batchId);
        if (!$batch->canReverse()) {
            $notify[] = ['error', 'Only a completed batch can be reversed.'];
            return back()->withNotify($notify);
        }

        ReverseDividendPayment::dispatch($batch, auth()->guard('admin')->id());

        $notify[] = ['success', "Reversal of batch #{$batch->id} queued: the members' balances are reduced shortly."];
        return to_route('admin.dividend.history')->withNotify($notify);
    }

    public function investors(Request $request)
    {
        $pageTitle = 'FFO Investors - Shares & Dividends';
        $search    = trim((string) $request->search);

        $investors = User::whereHas('rinvestment', fn ($q) => $q->where('status', 1))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('username', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('firstname', 'like', "%{$search}%")->orWhere('lastname', 'like', "%{$search}%")))
            ->withSum(['rinvestment as units' => fn ($q) => $q->where('status', 1)], 'units')
            ->withCount(['rinvestment as investments' => fn ($q) => $q->where('status', 1)])
            ->withSum(['shtransactions as paid' => fn ($q) => $q->where('status', 'completed')], 'amount')
            ->orderBy('username')
            ->paginate(getPaginate())->withQueryString();

        return Inertia::render('Admin/Dividend/Investors', [
            'search'    => $search,
            'investors' => $investors->through(fn (User $u) => [
                'id'          => $u->id,
                'name'        => (string) $u->fullname,
                'username'    => (string) $u->username,
                'email'       => (string) $u->email,
                'units'       => number_format((float) $u->units),
                'investments' => (int) $u->investments,
                'credits'     => showAmount($u->shares ?? 0),
                'paid'        => showAmount($u->paid ?? 0),
                'url'         => route('admin.dividend.user.dividends', $u->id),
                'member'      => route('admin.users.detail', $u->id),
            ]),
            'urls' => ['self' => route('admin.dividend.investors'), 'batches' => route('admin.dividend.index')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function userDividends($userId)
    {
        $user      = User::findOrFail($userId);
        $pageTitle = "Dividends for {$user->fullname}";

        return Inertia::render('Admin/Dividend/Member', [
            'title'  => $pageTitle,
            'member' => ['name' => (string) $user->fullname, 'username' => (string) $user->username, 'email' => (string) $user->email, 'url' => route('admin.users.detail', $user->id)],
            'total'  => showAmount($user->shtransactions()->where('status', 'completed')->sum('amount')),
            'dividends' => $user->shtransactions()->with('rinvestment.plan')->orderByDesc('id')->paginate(getPaginate())->through(fn (Shtransaction $t) => [
                'id'         => $t->id,
                'reference'  => (string) $t->reference,
                'investment' => (int) $t->rinvestment_id,
                'plan'       => (string) ($t->rinvestment?->plan?->name ?: '—'),
                'units'      => number_format((float) $t->units_held),
                'perUnit'    => showAmount($t->amount_per_unit),
                'amount'     => showAmount($t->amount),
                'status'     => [ucfirst((string) $t->status), self::TONES[$t->status] ?? 'dark'],
                'batch'      => $t->dividend_batch_id ? route('admin.dividend.detail', $t->dividend_batch_id) : null,
                'date'       => showDateTime($t->created_at),
            ]),
            'urls' => ['back' => route('admin.dividend.investors')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }
}

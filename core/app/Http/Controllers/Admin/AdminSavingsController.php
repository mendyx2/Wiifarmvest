<?php

namespace App\Http\Controllers\Admin;

use App\Models\SavingsSetting;
use App\Models\SavingsProduct;
use App\Models\FarmCycle;
use App\Models\Transaction;
use App\Jobs\ProcessFarmCycleMaturity;
use App\Jobs\ProcessSavingsTransfer;
use App\Support\AdminPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

/**
 * Savings & Loans → savings side (Pages/Admin/Savings/*): the product
 * settings, every member's savings with one saving's detail, and the farm
 * cycles with close and pay out.
 */
class AdminSavingsController extends Controller
{
    private const TYPES    = ['target', 'fixed', 'farm'];
    private const STATUSES = ['active', 'matured', 'closed'];

    /* ── Settings ─────────────────────────────────────── */

    public function settings()
    {
        $pageTitle = 'Savings Settings';
        // CASE instead of MySQL's FIELD(), same order: target, fixed, farm
        $settings = SavingsSetting::orderByRaw("CASE type WHEN 'target' THEN 1 WHEN 'fixed' THEN 2 WHEN 'farm' THEN 3 ELSE 4 END")
            ->orderBy('duration_months')
            ->get()
            ->map(fn (SavingsSetting $s) => [
                'id'          => $s->id,
                'label'       => (string) $s->label,
                'description' => (string) $s->description,
                'type'        => (string) $s->type,
                'duration'    => $s->duration_months ? (int) $s->duration_months : null,
                'rate'        => (float) $s->interest_rate,
                'min'         => (float) $s->min_amount,
                'max'         => $s->max_amount !== null ? (float) $s->max_amount : null,
                'minLabel'    => showAmount($s->min_amount),
                'maxLabel'    => $s->max_amount ? showAmount($s->max_amount) : null,
                'active'      => (bool) $s->is_active,
            ]);

        return Inertia::render('Admin/Savings/Settings', [
            'settings' => $settings,
            'currency' => gs('cur_sym'),
            'urls'     => [
                'store'  => route('admin.savings.settings.store'),
                'update' => route('admin.savings.settings.update', ':id'),
                'index'  => route('admin.savings.index'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function storeSetting(Request $request)
    {
        $request->validate([
            'type'            => 'required|in:target,fixed,farm',
            'duration_months' => 'required_if:type,fixed|nullable|integer|min:1',
            'label'           => 'required|string|max:100',
            'interest_rate'   => 'required|numeric|min:0|max:100',
            'min_amount'      => 'required|numeric|min:0',
            'max_amount'      => 'nullable|numeric|gte:min_amount',
            'description'     => 'nullable|string|max:255',
            'is_active'       => 'nullable|boolean',
        ]);

        $duration = $request->type == 'fixed' ? $request->duration_months : null;

        $exists = SavingsSetting::where('type', $request->type)
                    ->where('duration_months', $duration)
                    ->exists();

        if ($exists) {
            $notify[] = ['error', 'A setting for this type and duration already exists.'];
            return back()->withNotify($notify);
        }

        SavingsSetting::create([
            'type'            => $request->type,
            'duration_months' => $duration,
            'label'           => $request->label,
            'interest_rate'   => $request->interest_rate,
            'min_amount'      => $request->min_amount,
            'max_amount'      => $request->max_amount ?: null,
            'description'     => $request->description,
            'is_active'       => $request->boolean('is_active', true),
        ]);

        $notify[] = ['success', 'New savings setting created successfully.'];
        return back()->withNotify($notify);
    }

    public function updateSetting(Request $request, $id)
    {
        $setting = SavingsSetting::findOrFail($id);

        $request->validate([
            'interest_rate' => 'required|numeric|min:0|max:100',
            'min_amount'    => 'required|numeric|min:0',
            'max_amount'    => 'nullable|numeric|gte:min_amount',
            'description'   => 'nullable|string|max:255',
            'is_active'     => 'nullable|boolean',
        ]);

        $setting->interest_rate = $request->interest_rate;
        $setting->min_amount    = $request->min_amount;
        $setting->max_amount    = $request->max_amount ?: null;
        $setting->description   = $request->description;
        $setting->is_active     = $request->boolean('is_active');
        $setting->save();

        $notify[] = ['success', $setting->label . ' settings updated successfully.'];
        return back()->withNotify($notify);
    }

    /* ── All user savings ─────────────────────────────── */

    public function index(Request $request)
    {
        $pageTitle = 'All Savings Products';
        $type      = in_array($request->type, self::TYPES, true) ? $request->type : '';
        $status    = in_array($request->status, self::STATUSES, true) ? $request->status : '';
        $search    = trim((string) $request->search);

        $savings = SavingsProduct::with('user:id,firstname,lastname,username,email')
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('reference', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u
                    ->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))))
            ->latest()
            ->paginate(getPaginate())
            ->withQueryString()
            ->through(fn (SavingsProduct $s) => [
                'id'        => $s->id,
                'user'      => AdminPresenter::user($s->user),
                'email'     => (string) $s->user?->email,
                'reference' => (string) $s->reference,
                'type'      => $this->typeBadge($s->type),
                'principal' => showAmount($s->principal),
                'rate'      => (float) $s->interest_rate . '%',
                'interest'  => showAmount($s->interest_earned),
                'maturity'  => $s->maturity_date?->format('M d, Y'),
                'status'    => $this->statusBadge($s->status),
                'url'       => route('admin.savings.show', $s->id),
            ]);

        // Single quotes: "active" is a column name in standard SQL (and in MySQL's ANSI mode)
        $summary = SavingsProduct::selectRaw("
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) as active_count,
            COALESCE(SUM(principal), 0) as total_principal,
            COALESCE(SUM(interest_earned), 0) as total_interest
        ")->first();

        return Inertia::render('Admin/Savings/Index', [
            'savings' => $savings,
            'summary' => [
                'total'     => (int) $summary->total,
                'active'    => (int) $summary->active_count,
                'principal' => showAmount($summary->total_principal),
                'interest'  => showAmount($summary->total_interest),
            ],
            'filters' => ['search' => $search, 'type' => $type, 'status' => $status],
            'urls'    => ['settings' => route('admin.savings.settings'), 'cycles' => route('admin.savings.cycles')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function show($id)
    {
        $pageTitle = 'Savings Detail';
        $saving    = SavingsProduct::with('user')->findOrFail($id);

        $txns = Transaction::where('user_id', $saving->user_id)
            ->where(fn ($q) => $q->where('trx', $saving->reference)
                ->orWhere(fn ($q) => $q->whereIn('remark', ['savings_lock', 'savings_withdrawal'])
                    ->where('details', 'like', '%' . $saving->name . '%')))
            ->latest()
            ->get()
            ->map(fn (Transaction $t) => [
                'id'      => $t->id,
                'date'    => $t->created_at->format('M d, Y'),
                'trx'     => (string) $t->trx,
                'amount'  => $t->trx_type . showAmount($t->amount),
                'credit'  => $t->trx_type === '+',
                'details' => (string) $t->details,
            ]);

        $target = (float) $saving->target_amount;

        return Inertia::render('Admin/Savings/Show', [
            'saving' => [
                'id'          => $saving->id,
                'reference'   => (string) $saving->reference,
                'user'        => AdminPresenter::user($saving->user),
                'name'        => (string) $saving->name,
                'type'        => $this->typeBadge($saving->type),
                'status'      => $this->statusBadge($saving->status),
                'principal'   => showAmount($saving->principal),
                'balance'     => showAmount($saving->balance),
                'target'      => $target ? showAmount($target) : null,
                'goal'        => showAmount($saving->target_amount ?? $saving->principal),
                'progress'    => $target > 0 ? (int) round(min(100, $saving->balance / $target * 100)) : 100,
                'rate'        => (float) $saving->interest_rate . '% p.a.',
                'interest'    => showAmount($saving->interest_earned),
                'start'       => $saving->start_date?->format('M d, Y'),
                'maturity'    => $saving->maturity_date?->format('M d, Y'),
                'closed'      => $saving->closed_at?->format('M d, Y H:i'),
                'payout'      => showAmount((float) $saving->balance + (float) $saving->interest_earned),
                // Only an active saving whose date has passed: a "matured" one was already paid out
                'canTransfer' => $this->transferable($saving),
                'maturesLater' => $saving->status === 'active' && $saving->maturity_date && !$saving->maturity_date->isPast(),
            ],
            'transactions' => $txns,
            'urls'         => ['transfer' => route('admin.savings.transfer', $saving->id), 'index' => route('admin.savings.index')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /**
     * Pay an active saving whose maturity date has passed into the member's
     * Money Box (queued). "Matured" savings are refused: the maturity runs
     * (ProcessPayment, ProcessFarmCycleMaturity) set that status when they
     * pay, so transferring one again paid it twice.
     */
    public function transferToBalance(Request $request, $id)
    {
        $saving = SavingsProduct::with('user')->findOrFail($id);

        if ($saving->status !== 'active') {
            $notify[] = ['error', 'This savings product has already been paid out or closed.'];
            return back()->withNotify($notify);
        }

        if (!$saving->maturity_date || !$saving->maturity_date->isPast()) {
            $due = $saving->maturity_date ? $saving->maturity_date->format('M d, Y') : 'unknown';
            $notify[] = ['error', "Maturity date ({$due}) has not been reached yet."];
            return back()->withNotify($notify);
        }

        if (!$saving->user) {
            $notify[] = ['error', 'User not found for this savings product.'];
            return back()->withNotify($notify);
        }

        ProcessSavingsTransfer::dispatch($saving, auth('admin')->id());

        $total    = showAmount((float) $saving->balance + (float) $saving->interest_earned);
        $notify[] = ['success', "Transfer queued. {$saving->user->username}'s Money Box will be credited with {$total} shortly."];
        return back()->withNotify($notify);
    }

    private function transferable(SavingsProduct $saving): bool
    {
        return $saving->status === 'active' && $saving->maturity_date && $saving->maturity_date->isPast();
    }

    private function typeBadge(?string $type): array
    {
        $class = ['target' => 'badge--info', 'fixed' => 'badge--warning', 'farm' => 'badge--success'][$type] ?? 'badge--dark';
        return AdminPresenter::badge(ucfirst((string) $type), $class);
    }

    private function statusBadge(?string $status): array
    {
        $class = ['active' => 'badge--success', 'matured' => 'badge--warning', 'closed' => 'badge--dark'][$status] ?? 'badge--dark';
        return AdminPresenter::badge(ucfirst((string) $status), $class);
    }

    /* ── Farm Cycles ──────────────────────────────────── */

    public function cycles()
    {
        $pageTitle = 'Farm Cycles';
        $active    = fn ($q) => $q->where('status', 'active');

        $cycles = FarmCycle::withCount(['savings as active_count' => $active])
            ->withSum(['savings as active_total' => $active], 'principal')
            ->latest()
            ->paginate(getPaginate())
            ->withQueryString()
            ->through(function (FarmCycle $c) {
                $reached = $c->isMaturityDateReached();

                return [
                    'id'           => $c->id,
                    'name'         => (string) $c->name,
                    'contribution' => showAmount($c->min_contribution) . ($c->max_contribution ? ' – ' . showAmount($c->max_contribution) : ''),
                    'yield'        => (float) $c->min_yield . '% – ' . (float) $c->max_yield . '%',
                    'actualYield'  => $c->actual_yield !== null ? (float) $c->actual_yield : null,
                    'opens'        => $c->subscription_opens_at?->format('M d, Y'),
                    'maturity'     => $c->maturity_date?->format('M d, Y'),
                    'daysLeft'     => $c->status === 'closed' && !$reached && $c->maturity_date ? (int) now()->startOfDay()->diffInDays($c->maturity_date) : null,
                    'subscribers'  => (int) $c->active_count,
                    'total'        => showAmount($c->active_total ?? 0),
                    'status'       => AdminPresenter::badge(ucfirst((string) $c->status), ['open' => 'badge--success', 'closed' => 'badge--warning', 'matured' => 'badge--info'][$c->status] ?? 'badge--dark'),
                    'state'        => (string) $c->status,
                    'paidOut'      => (bool) $c->payout_processed,
                    'reached'      => $reached,
                    // The edit form's values
                    'form'         => [
                        'name'                  => (string) $c->name,
                        'min_contribution'      => (string) (float) $c->min_contribution,
                        'max_contribution'      => (string) (float) $c->max_contribution,
                        'min_yield'             => (string) (float) $c->min_yield,
                        'max_yield'             => (string) (float) $c->max_yield,
                        'actual_yield'          => $c->actual_yield !== null ? (string) (float) $c->actual_yield : '',
                        'subscription_opens_at' => (string) $c->subscription_opens_at?->format('Y-m-d'),
                        'maturity_date'         => (string) $c->maturity_date?->format('Y-m-d'),
                        'status'                => $c->status === 'closed' ? 'closed' : 'open',
                    ],
                ];
            });

        return Inertia::render('Admin/Savings/Cycles', [
            'cycles'   => $cycles,
            'currency' => gs('cur_sym'),
            'urls'     => [
                'store'  => route('admin.savings.cycles.store'),
                'update' => route('admin.savings.cycles.store', ':id'),
                'close'  => route('admin.savings.cycles.close', ':id'),
                'mature' => route('admin.savings.cycles.mature', ':id'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function storeCycle(Request $request, $id = 0)
    {
        $request->validate([
            'name'                   => 'required|string|max:100',
            'min_contribution'       => 'required|numeric|min:0',
            'max_contribution'       => 'required|numeric|min:0|gte:min_contribution',
            'min_yield'              => 'required|numeric|min:0|max:100',
            'max_yield'              => 'required|numeric|min:0|max:100|gte:min_yield',
            'subscription_opens_at'  => 'required|date',
            'maturity_date'          => 'required|date|after:subscription_opens_at',
            'actual_yield'           => 'nullable|numeric|min:0|max:100',
            'status'                 => 'required|in:open,closed',
        ]);

        $cycle = $id ? FarmCycle::findOrFail($id) : new FarmCycle();
        // A cycle that has been (or is being) paid out keeps the figures it was paid with
        if ($id && ($cycle->status == 'matured' || $cycle->payout_processed)) {
            $notify[] = ['error', 'Matured cycles cannot be edited.'];
            return back()->withNotify($notify);
        }

        $cycle->fill($request->only([
            'name', 'min_contribution', 'max_contribution',
            'min_yield', 'max_yield', 'actual_yield',
            'subscription_opens_at', 'maturity_date', 'status',
        ]));
        $cycle->save();

        $notify[] = ['success', 'Farm cycle ' . ($id ? 'updated' : 'created') . ' successfully.'];
        return back()->withNotify($notify);
    }

    public function closeCycle($id)
    {
        FarmCycle::findOrFail($id);

        // Only the request that finds it still open closes it
        $closed = FarmCycle::whereKey($id)->where('status', 'open')->update(['status' => 'closed', 'updated_at' => now()]);
        if (!$closed) {
            $notify[] = ['error', 'Only open cycles can be closed.'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Farm cycle closed. No new contributions will be accepted.'];
        return back()->withNotify($notify);
    }

    /**
     * Pay out a closed cycle whose maturity date has come: principal plus
     * interest at the actual yield into each subscriber's Money Box.
     *
     * The job does the paying and marks the cycle paid out, under a lock,
     * so a second click (or a second job) finds it done and does nothing.
     * The old code marked the cycle "paid out" here, before queueing the
     * job, and the job then saw that flag and skipped the payout: no member
     * was ever paid. Migration 2026_10_27_000001 re-opens cycles left in that
     * state so they can be paid out.
     */
    public function matureCycle(Request $request, $id)
    {
        $request->validate([
            'actual_yield' => 'required|numeric|gt:0|max:100',
        ]);

        $error = null;
        $cycle = null;

        DB::transaction(function () use ($request, $id, &$cycle, &$error) {
            $cycle = FarmCycle::lockForUpdate()->findOrFail($id);

            if ($cycle->status != 'closed') {
                $error = $cycle->status == 'matured' ? 'Payout has already been processed for this cycle.' : 'Only closed cycles can be matured. Close the cycle first.';
                return;
            }

            if ($cycle->payout_processed) {
                $error = 'Payout has already been processed for this cycle.';
                return;
            }

            if (!$cycle->isMaturityDateReached()) {
                $error = 'Maturity date (' . $cycle->maturity_date->format('M d, Y') . ') has not been reached yet.';
                return;
            }

            $cycle->actual_yield = $request->actual_yield;
            $cycle->save();
        });

        if ($error) {
            $notify[] = ['error', $error];
            return back()->withNotify($notify);
        }

        $count = $cycle->activeSavingsCount();
        $total = $cycle->activeSavingsTotal();

        ProcessFarmCycleMaturity::dispatch($cycle);

        $notify[] = ['success', "Maturity payout queued for {$count} savings accounts (total " . showAmount($total) . " principal). Users will be credited with principal + interest shortly."];
        return back()->withNotify($notify);
    }
}

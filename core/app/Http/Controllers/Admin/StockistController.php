<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\ProductStatePrice;
use App\Models\Sktransaction;
use App\Models\State;
use App\Models\StockTransfer;
use App\Models\Stockist;
use App\Models\StockistRedemption;
use App\Models\Stockist_store;
use App\Models\Stransaction;
use App\Models\User;
use App\Support\AdminPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Manage Stockist: the stockist list, activation, one stockist's page
 * (with wallet top-up and activate / deactivate) and the redemption report.
 * Pages are in resources/js/Pages/Admin/Stockist/.
 */
class StockistController extends Controller
{
    /** Old list pages (All / Active / Inactive Stockists): the dashboard lists and filters the same stockists. */
    public function allStockists()
    {
        return to_route('admin.stockist.dashboard');
    }

    public function activeStockist()
    {
        return to_route('admin.stockist.dashboard', ['status' => 'active']);
    }

    public function inactiveStockist()
    {
        return to_route('admin.stockist.dashboard', ['status' => 'inactive']);
    }

    /** Old stockist page: the details page shows the same and more. */
    public function detail($id)
    {
        return to_route('admin.stockist.details', Stockist::findOrFail($id));
    }

    /** Old wallet top-up page: the top-up is a dialog on the stockist's page now. */
    public function walletTopUpPage(Stockist $stockist)
    {
        return to_route('admin.stockist.details', ['stockist' => $stockist, 'topup' => 1]);
    }

    /**
     * Activate or deactivate a stockist (`state=activate|deactivate`; a
     * reason is needed to deactivate). Both flags change together: the old
     * toggle set `status` while the pages and the stockist search read
     * `is_active`, so a deactivated stockist still showed as active.
     */
    public function changeUserStockistStatus(Request $request, $id)
    {
        $request->validate([
            'state'         => 'required|in:activate,deactivate',
            'status_reason' => 'required_if:state,deactivate|nullable|string|max:255',
        ], ['status_reason.required_if' => 'Give a reason for deactivating this stockist.']);

        $stockist = Stockist::findOrFail($id);
        $activate = $request->state === 'activate';
        $isActive = (int) $stockist->status === Status::ACTIVE && $stockist->is_active;

        if ($activate === $isActive) {
            $notify[] = ['info', $activate ? 'The stockist is already active.' : 'The stockist is already inactive.'];
            return back()->withNotify($notify);
        }

        $stockist->status    = $activate ? Status::ACTIVE : Status::INACTIVE;
        $stockist->is_active = $activate;
        if (Schema::hasColumn('stockists', 'status_reason')) {
            $stockist->status_reason = $activate ? null : $request->status_reason;
        }
        $stockist->save();

        $notify[] = ['success', $activate ? 'Stockist is now active' : 'Stockist is now inactive'];
        return back()->withNotify($notify);
    }

    /** Manage Stockist → Dashboard (Pages/Admin/Stockist/Dashboard.tsx): totals and every stockist. */
    public function dashboard(Request $request)
    {
        $pageTitle = 'Stockist Dashboard';
        $search    = trim((string) $request->query('search', ''));
        $status    = in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : '';
        $type      = in_array($request->query('type'), ['mega', 'mini'], true) ? $request->query('type') : '';
        $stateId   = (int) $request->query('state');

        $stockists = Stockist::with(['user:id,username,firstname,lastname', 'state:id,name'])
            ->withSum('stockiststore as units', 'quantity')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('business_name', 'like', "%{$search}%")
                ->orWhere('business_email', 'like', "%{$search}%")
                ->orWhere('business_phone', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('username', 'like', "%{$search}%"))))
            ->when($status === 'active', fn ($q) => $q->where('is_active', true)->where('status', Status::ACTIVE))
            ->when($status === 'inactive', fn ($q) => $q->where(fn ($q) => $q->where('is_active', false)->orWhere('status', '!=', Status::ACTIVE)))
            ->when($type === 'mega', fn ($q) => $q->where('store_type', Stockist::STORE_TYPE_MEGA))
            ->when($type === 'mini', fn ($q) => $q->where('store_type', '!=', Stockist::STORE_TYPE_MEGA))
            ->when($stateId, fn ($q) => $q->where('state_id', $stateId))
            ->latest('id')
            ->paginate(getPaginate())
            ->withQueryString()
            ->through(fn (Stockist $s) => $this->row($s) + [
                'email'    => (string) $s->business_email,
                'phone'    => (string) $s->business_phone,
                'units'    => (int) $s->units,
                'verified' => $s->is_verified ? ($s->verified_at ? showDateTime($s->verified_at, 'd M Y') : true) : false,
            ]);

        return Inertia::render('Admin/Stockist/Dashboard', [
            'stats' => [
                'total'    => Stockist::count(),
                'active'   => Stockist::where('is_active', true)->where('status', Status::ACTIVE)->count(),
                'mega'     => Stockist::where('store_type', Stockist::STORE_TYPE_MEGA)->count(),
                'units'    => number_format((int) Stockist_store::whereNotNull('stockist_id')->sum('quantity')),
                'wallets'  => showAmount(Stockist::sum('wallet')),
            ],
            'stockists' => $stockists,
            'states'    => State::orderBy('name')->get(['id', 'name'])->map(fn ($s) => ['id' => $s->id, 'name' => (string) $s->name]),
            'filters'   => ['search' => $search, 'status' => $status, 'type' => $type, 'state' => $stateId ? (string) $stateId : ''],
            'urls'      => ['activate' => route('admin.stockist.activation-page')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** The fields every stockist list shows. */
    private function row(Stockist $s): array
    {
        return [
            'id'     => $s->id,
            'name'   => $s->business_name ?: 'Stockist #' . $s->id,
            'user'   => AdminPresenter::user($s->user),
            'state'  => $s->state?->name,
            'mega'   => $s->isMega(),
            'active' => (int) $s->status === Status::ACTIVE && (bool) $s->is_active,
            'wallet' => showAmount($s->wallet),
            'url'    => route('admin.stockist.details', $s->id),
        ];
    }

    /** Activate New Stockist (Pages/Admin/Stockist/Activate.tsx). */
    public function activationPage()
    {
        return Inertia::render('Admin/Stockist/Activate', [
            'states' => State::orderBy('name')->get(['id', 'name', 'code'])->map(fn ($s) => ['id' => $s->id, 'name' => (string) $s->name, 'code' => (string) $s->code]),
            'urls'   => [
                'search'    => route('admin.stockist.search-user'),
                'activate'  => route('admin.stockist.activate'),
                'dashboard' => route('admin.stockist.dashboard'),
            ],
        ])->withViewData(['pageTitle' => 'Activate New Stockist']);
    }

    /** Look up the member to promote (JSON for the activation page). */
    public function searchUser(Request $request)
    {
        $request->validate(['username' => 'required|string|max:60']);

        $user = User::where('username', trim($request->username))->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No member with that username.']);
        }
        if (Stockist::where('user_id', $user->id)->exists()) {
            // The whole stockist record used to be sent back here.
            return response()->json(['success' => false, 'message' => 'This member is already a stockist.']);
        }
        if ((int) $user->status !== Status::USER_ACTIVE) {
            return response()->json(['success' => false, 'message' => 'This member is banned.']);
        }

        return response()->json([
            'success' => true,
            'user'    => [
                'id'       => $user->id,
                'name'     => (string) $user->fullname,
                'username' => (string) $user->username,
                'email'    => (string) $user->email,
                'mobile'   => (string) $user->mobileNumber,
                'balance'  => (float) $user->balance,
                'balanceLabel' => showAmount($user->balance),
                'joined'   => showDateTime($user->created_at, 'd M Y'),
            ],
        ]);
    }

    /**
     * Make a member a stockist. The activation cost comes out of their Money
     * Box and opens the stockist wallet (as before). The member row is locked
     * and the checks run inside the transaction, so a double click can no
     * longer charge twice or create two stockist records.
     */
    public function activateStockist(Request $request)
    {
        $request->validate([
            'user_id'         => 'required|integer|exists:users,id',
            'state_id'        => 'required|integer|exists:states,id',
            'store_type'      => 'required|in:1,2',
            'activation_cost' => 'required|numeric|min:0|max:100000000',
            'business_name'   => 'required|string|max:255',
            'business_email'  => 'required|email|max:255',
            'business_phone'  => 'required|string|max:20',
        ]);

        try {
            $stockist = DB::transaction(function () use ($request) {
                $user = User::lockForUpdate()->findOrFail($request->user_id);

                if (Stockist::where('user_id', $user->id)->exists()) {
                    throw ValidationException::withMessages(['user_id' => 'This member is already a stockist.']);
                }
                if ((int) $user->status !== Status::USER_ACTIVE) {
                    throw ValidationException::withMessages(['user_id' => 'This member is banned.']);
                }

                $amount = (float) $request->activation_cost;
                if ($user->balance < $amount) {
                    throw ValidationException::withMessages(['activation_cost' => 'The member has only ' . showAmount($user->balance) . ' in their Money Box.']);
                }

                $user->balance -= $amount;
                $user->save();

                $stockist                 = new Stockist();
                $stockist->user_id        = $user->id;
                $stockist->store_type     = (int) $request->store_type;
                $stockist->wallet         = $amount;
                $stockist->state_id       = $request->state_id;
                $stockist->business_name  = trim($request->business_name);
                $stockist->business_email = trim($request->business_email);
                $stockist->business_phone = trim($request->business_phone);
                $stockist->is_verified    = true;
                $stockist->status         = Status::ACTIVE;
                $stockist->is_active      = true;
                $stockist->verified_at    = now();
                $stockist->save();

                stockistActivation($user->id, $amount, $user->balance, 'Stockist activation', 'Stockist_activation_fee', getTrx());

                return $stockist;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            // The exception used to be shown in the toast.
            report($e);
            $notify[] = ['error', 'The stockist could not be activated. Nothing was charged; please try again.'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Stockist activated successfully!'];
        return to_route('admin.stockist.details', $stockist)->withNotify($notify);
    }

    /**
     * Add money to a stockist wallet. The stockist row is locked: the old
     * read-then-write could overwrite a sale or rebate credited at the same
     * moment. The admin who did it is recorded (it used to read the member
     * guard, so it was always empty).
     */
    public function topUpWallet(Request $request, Stockist $stockist)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0|max:100000000',
            'notes'  => 'nullable|string|max:500',
        ]);

        try {
            $after = DB::transaction(function () use ($request, $stockist) {
                $locked = Stockist::lockForUpdate()->findOrFail($stockist->id);
                $before = (float) $locked->wallet;
                $locked->wallet = $before + (float) $request->amount;
                $locked->save();

                Sktransaction::create([
                    'stockist_id'    => $locked->id,
                    'type'           => 'credit',
                    'amount'         => $request->amount,
                    'balance_before' => $before,
                    'balance_after'  => $locked->wallet,
                    'description'    => 'Wallet Top-up by Admin',
                    'notes'          => $request->notes,
                    'reference'      => Sktransaction::generateReference(),
                    'metadata'       => [
                        'topped_up_by' => auth('admin')->id(),
                        'topped_up_at' => now()->toDateTimeString(),
                        'method'       => 'manual',
                    ],
                ]);

                return $locked->wallet;
            });
        } catch (\Throwable $e) {
            report($e);
            $notify[] = ['error', 'The wallet could not be topped up. Nothing was changed.'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Added ' . showAmount($request->amount) . " to {$stockist->business_name}'s wallet. New balance: " . showAmount($after) . '.'];
        return back()->withNotify($notify);
    }

    /** One stockist (Pages/Admin/Stockist/Details.tsx): overview, inventory, transfers and wallet activity. */
    public function showDetails(Request $request, Stockist $stockist)
    {
        $stockist->load(['user', 'state']);
        $user = $stockist->user;

        // Inventory, valued at this stockist's state prices (what stockists pay)
        $inventory = Stockist_store::where('user_id', $stockist->user_id)
            ->with('product:id,name,sku,thumbnail,pv')
            ->orderByDesc('quantity')
            ->get();
        $prices = ProductStatePrice::where('state_id', $stockist->state_id)
            ->whereIn('product_id', $inventory->pluck('product_id'))
            ->pluck('price', 'product_id');
        $level = fn ($row) => $row->quantity <= 0 ? 'out' : ($row->quantity < ($row->min_stock_level ?: 10) ? 'low' : 'ok');

        $direction = in_array($request->direction, ['sent', 'received'], true) ? $request->direction : null;
        $transfers = StockTransfer::query()
            ->when($direction === 'sent', fn ($q) => $q->where('seller_stockist_id', $stockist->id))
            ->when($direction === 'received', fn ($q) => $q->where('buyer_stockist_id', $stockist->id))
            ->when(!$direction, fn ($q) => $q->involving($stockist))
            ->with(['buyer.user:id,username', 'seller.user:id,username'])
            ->withCount('items')
            ->latest('id')
            ->paginate(10, ['*'], 'transfers_page')
            ->withQueryString()
            ->through(function (StockTransfer $t) use ($stockist) {
                $out   = (int) $t->seller_stockist_id === (int) $stockist->id;
                $other = $out ? $t->buyer : $t->seller;
                return [
                    'id'       => $t->id,
                    'code'     => (string) $t->code,
                    'date'     => showDateTime($t->created_at, 'd M Y, h:i A'),
                    'out'      => $out,
                    'other'    => ['name' => $other?->business_name ?? '—', 'username' => $other?->user?->username],
                    'units'    => (int) $t->total_quantity,
                    'products' => (int) $t->items_count,
                    'amount'   => showAmount($t->total_amount),
                    'rebate'   => $out && $t->status === StockTransfer::STATUS_COMPLETED ? showAmount($t->rebate_amount) : null,
                    'status'   => AdminPresenter::badge(ucfirst($t->status), $t->adminBadge()),
                    'statusLabel' => $t->statusLabel(),
                    'url'      => route('admin.stockist.transfers.show', $t->id),
                ];
            });

        $sent = StockTransfer::where('seller_stockist_id', $stockist->id)->where('status', StockTransfer::STATUS_COMPLETED)
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(total_amount), 0) AS total, COALESCE(SUM(rebate_amount), 0) AS rebate, COALESCE(SUM(total_quantity), 0) AS units')->first();
        $received = StockTransfer::where('buyer_stockist_id', $stockist->id)->where('status', StockTransfer::STATUS_COMPLETED)
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(total_amount), 0) AS total, COALESCE(SUM(total_quantity), 0) AS units')->first();
        $redemptions = StockistRedemption::where('stockist_id', $stockist->id)
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(amount), 0) AS total, COALESCE(SUM(quantity), 0) AS units')->first();

        // Wallet activity: admin top-ups (sktransactions) and the stockist ledger (stransactions)
        $activity = Sktransaction::where('stockist_id', $stockist->id)->latest('id')->take(15)->get()
            ->map(fn ($row) => ['at' => $row->created_at, 'label' => (string) $row->description, 'note' => $row->notes, 'plus' => $row->type === 'credit', 'amount' => (float) $row->amount, 'balance' => (float) $row->balance_after, 'ref' => (string) $row->reference, 'source' => 'Top-up'])
            ->concat(Stransaction::where('user_id', $stockist->user_id)->latest('id')->take(15)->get()
                ->map(fn ($row) => ['at' => $row->created_at, 'label' => (string) $row->details, 'note' => null, 'plus' => $row->trx_type === '+', 'amount' => (float) $row->amount, 'balance' => (float) $row->post_balance, 'ref' => (string) $row->trx, 'source' => ucwords(str_replace('_', ' ', (string) $row->remark))]))
            ->sortByDesc('at')
            ->take(15)
            ->values()
            ->map(fn ($r) => ['date' => showDateTime($r['at'], 'd M Y, h:i A'), 'amount' => showAmount($r['amount']), 'balance' => showAmount($r['balance'])] + $r)
            ->map(fn ($r) => array_diff_key($r, ['at' => 1]));

        $active = (int) $stockist->status === Status::ACTIVE && $stockist->is_active;
        $title  = $stockist->business_name ?: 'Stockist #' . $stockist->id;

        return Inertia::render('Admin/Stockist/Details', [
            'title'    => $title,
            'stockist' => $this->row($stockist) + [
                'active'       => $active,
                'reason'       => $stockist->status_reason ?? null,
                'storeType'    => $stockist->store_type_name,
                'phone'        => (string) $stockist->business_phone,
                'email'        => (string) $stockist->business_email,
                'registration' => (string) $stockist->business_registration_number,
                'description'  => (string) $stockist->business_description,
                'verified'     => $stockist->is_verified ? ($stockist->verified_at ? showDateTime($stockist->verified_at, 'd M Y') : true) : false,
                'since'        => showDateTime($stockist->created_at, 'M Y'),
                'walletRaw'    => (float) $stockist->wallet,
            ],
            'owner' => $user ? [
                'fullname' => (string) $user->fullname,
                'username' => (string) $user->username,
                'url'      => route('admin.users.detail', $user->id),
                'email'    => (string) $user->email,
                'mobile'   => (string) $user->mobileNumber,
                'joined'   => showDateTime($user->created_at, 'd M Y'),
                'balance'  => showAmount($user->balance),
                'rebate'   => showAmount($user->stockist_rebate),
            ] : null,
            'numbers' => [
                'units'      => number_format((int) $inventory->sum('quantity')),
                'products'   => $inventory->count(),
                'low'        => $inventory->filter(fn ($row) => $level($row) === 'low')->count(),
                'out'        => $inventory->filter(fn ($row) => $level($row) === 'out')->count(),
                'value'      => showAmount($inventory->sum(fn ($row) => $row->quantity * (float) ($prices[$row->product_id] ?? 0))),
                'redeemed'   => ['total' => showAmount($redemptions->total), 'n' => (int) $redemptions->n, 'units' => (int) $redemptions->units],
                'sent'       => ['total' => showAmount($sent->total), 'n' => (int) $sent->n, 'units' => (int) $sent->units, 'rebate' => showAmount($sent->rebate)],
                'received'   => ['total' => showAmount($received->total), 'n' => (int) $received->n, 'units' => (int) $received->units],
                'pending'    => StockTransfer::involving($stockist)->where('status', StockTransfer::STATUS_PENDING)->count(),
                'transfers'  => StockTransfer::involving($stockist)->count(),
                'sentAll'    => StockTransfer::where('seller_stockist_id', $stockist->id)->count(),
                'receivedAll' => StockTransfer::where('buyer_stockist_id', $stockist->id)->count(),
            ],
            'inventory' => $inventory->map(function ($row) use ($prices, $level) {
                $price = (float) ($prices[$row->product_id] ?? 0);
                return [
                    'id'        => $row->id,
                    'name'      => $row->product?->name ?? 'Product #' . $row->product_id,
                    'sku'       => $row->product?->sku,
                    'image'     => getImage(getFilePath('products') . '/' . $row->product?->thumbnail, getFileSize('products')),
                    'quantity'  => (int) $row->quantity,
                    'level'     => $level($row),
                    'min'       => (int) $row->min_stock_level,
                    'price'     => $price ? showAmount($price) : null,
                    'value'     => $price ? showAmount($row->quantity * $price) : null,
                    'restocked' => $row->last_restocked_at ? showDateTime($row->last_restocked_at, 'd M Y') : null,
                ];
            })->values(),
            'transfers' => $transfers,
            'direction' => $direction ?? '',
            'activity'  => $activity,
            // Opens on Transfers when a transfer page or filter was asked for, or the Top-up dialog from the old top-up URL.
            'tab'       => $request->has('transfers_page') || $direction ? 'transfers' : 'overview',
            'topup'     => $request->boolean('topup'),
            'urls'      => [
                'topup'     => route('admin.stockist.topup-wallet', $stockist),
                'status'    => route('admin.stockist.changeUserStockistStatus', $stockist->id),
                'transfers' => route('admin.stockist.transfers.index', ['search' => $user?->username]),
                'report'    => route('admin.stockist.report.show', $stockist->id),
                'self'      => route('admin.stockist.details', $stockist),
                'dashboard' => route('admin.stockist.dashboard'),
            ],
        ])->withViewData(['pageTitle' => $title]);
    }

    // ── Redemption / accounting report ──────────────────────────────

    /** Redemption Report (Pages/Admin/Stockist/Report.tsx): what every stockist handed over, by channel. */
    public function redemptionReport(Request $request)
    {
        $pageTitle = 'Stockist Redemption Report';
        $search    = trim((string) $request->query('search', ''));

        $perStockist = StockistRedemption::selectRaw('stockist_id, SUM(quantity) as qty, SUM(amount) as amt')->groupBy('stockist_id')->get()->keyBy('stockist_id');
        $overall     = StockistRedemption::selectRaw('type, SUM(quantity) as qty, SUM(amount) as amt, COUNT(*) as cnt')->groupBy('type')->get()->keyBy('type');

        $stockists = Stockist::with(['user:id,username,firstname,lastname', 'state:id,name'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('business_name', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u->where('username', 'like', "%{$search}%"))))
            ->orderByDesc('id')
            ->paginate(getPaginate())
            ->withQueryString()
            ->through(fn (Stockist $s) => $this->row($s) + [
                'units'     => (int) ($perStockist[$s->id]->qty ?? 0),
                'amount'    => showAmount($perStockist[$s->id]->amt ?? 0),
                'reportUrl' => route('admin.stockist.report.show', $s->id),
            ]);

        return Inertia::render('Admin/Stockist/Report', [
            'totals'    => $this->channelTotals($overall),
            'stockists' => $stockists,
            'search'    => $search,
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** One stockist's redemptions (Pages/Admin/Stockist/ReportShow.tsx), by channel and date. */
    public function redemptionReportShow(Stockist $stockist, Request $request)
    {
        $stockist->load(['user', 'state']);
        $title = 'Redemption Report - ' . ($stockist->business_name ?: $stockist->user?->username ?: $stockist->id);
        $type  = array_key_exists((string) $request->type, StockistRedemption::$types) ? $request->type : '';
        // Only real dates: a bad value is ignored instead of breaking the query.
        $date = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : '';
        $from = $date($request->from);
        $to   = $date($request->to);

        $grouped = StockistRedemption::where('stockist_id', $stockist->id)
            ->selectRaw('type, SUM(quantity) as qty, SUM(amount) as amt, COUNT(*) as cnt')
            ->groupBy('type')->get()->keyBy('type');
        $totals = $this->channelTotals($grouped);

        $redemptions = StockistRedemption::where('stockist_id', $stockist->id)
            ->when($type, fn ($q) => $q->ofType($type))
            ->when($from, fn ($q) => $q->whereDate('redeemed_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('redeemed_at', '<=', $to))
            ->with('customer:id,firstname,lastname,username')
            ->latest('redeemed_at')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (StockistRedemption $r) => [
                'id'        => $r->id,
                'type'      => $r->type,
                'typeLabel' => $r->type_label,
                'reference' => $r->reference_code ?: ($r->trx ?: '—'),
                'customer'  => $r->customer?->fullname ?: ($r->buyer_name ?: null),
                'items'     => collect((array) $r->items)->map(fn ($i) => ['name' => (string) ($i['product_name'] ?? 'Product'), 'quantity' => (int) ($i['quantity'] ?? 0)])->values(),
                'quantity'  => (int) $r->quantity,
                'amount'    => showAmount($r->amount),
                'date'      => $r->redeemed_at ? showDateTime($r->redeemed_at, 'd M Y') : '—',
                'time'      => $r->redeemed_at ? showDateTime($r->redeemed_at, 'H:i') : '',
            ]);

        return Inertia::render('Admin/Stockist/ReportShow', [
            'title'    => $title,
            'stockist' => $this->row($stockist),
            'grand'    => ['amount' => showAmount(array_sum(array_column($totals, 'raw'))), 'units' => array_sum(array_column($totals, 'qty'))],
            'totals'   => $totals,
            'redemptions' => $redemptions,
            'filters'  => ['type' => $type, 'from' => $from, 'to' => $to],
            'urls'     => ['self' => route('admin.stockist.report.show', $stockist->id), 'index' => route('admin.stockist.report')],
        ])->withViewData(['pageTitle' => $title]);
    }

    /** Units, amount and count per redemption channel, every channel listed. */
    private function channelTotals($grouped): array
    {
        $totals = [];
        foreach (StockistRedemption::$types as $type => $label) {
            $totals[] = [
                'type'   => $type,
                'label'  => $label,
                'qty'    => (int) ($grouped[$type]->qty ?? 0),
                'raw'    => (float) ($grouped[$type]->amt ?? 0),
                'amount' => showAmount($grouped[$type]->amt ?? 0),
                'count'  => (int) ($grouped[$type]->cnt ?? 0),
            ];
        }

        return $totals;
    }
}

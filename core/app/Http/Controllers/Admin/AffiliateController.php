<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateClick;
use App\Models\AffiliateOrder;
use App\Models\AffiliateSetting;
use App\Models\State;
use App\Models\Transaction;
use App\Models\User;
use App\Support\AdminPresenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Affiliate Program (the public /shop, sold through members' links):
 * dashboard, orders, affiliates, the funnel & money report and settings.
 * Pages are in resources/js/Pages/Admin/Affiliate/.
 */
class AffiliateController extends Controller
{
    private const STATUSES = [
        AffiliateOrder::STATUS_PENDING, AffiliateOrder::STATUS_PAID, AffiliateOrder::STATUS_AWAITING_PICKUP,
        AffiliateOrder::STATUS_FULFILLED, AffiliateOrder::STATUS_CANCELLED, AffiliateOrder::STATUS_EXPIRED,
    ];

    /** Orders that brought money in. */
    private const PAID = [AffiliateOrder::STATUS_PAID, AffiliateOrder::STATUS_FULFILLED];

    public function dashboard()
    {
        $pageTitle = 'Affiliate Dashboard';

        return Inertia::render('Admin/Affiliate/Dashboard', [
            'stats' => [
                'clicks'  => number_format(AffiliateClick::count()),
                'orders'  => number_format(AffiliateOrder::count()),
                'revenue' => showAmount(AffiliateOrder::whereIn('status', self::PAID)->sum('total_amount')),
                'bonus'   => showAmount(Transaction::where('remark', 'affiliate_bonus')->sum('amount')),
                'pickup'  => AffiliateOrder::where('status', AffiliateOrder::STATUS_AWAITING_PICKUP)->count(),
            ],
            'top' => User::whereHas('affiliateOrders')->withCount('affiliateOrders')->orderByDesc('affiliate_orders_count')->limit(5)
                ->get(['id', 'firstname', 'lastname', 'username'])
                ->map(fn (User $u) => ['user' => AdminPresenter::user($u), 'orders' => (int) $u->affiliate_orders_count]),
            'recent' => AffiliateOrder::with('affiliate:id,firstname,lastname,username')->latest('id')->limit(10)->get()->map(fn ($o) => $this->orderRow($o)),
            'urls'   => [
                'orders' => route('admin.affiliate.orders'),
                'pickup' => route('admin.affiliate.orders', ['status' => AffiliateOrder::STATUS_AWAITING_PICKUP]),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    private function orderRow(AffiliateOrder $o): array
    {
        return [
            'id'        => $o->id,
            'code'      => (string) $o->order_code,
            'buyer'     => ['name' => (string) $o->buyer_name, 'email' => (string) $o->buyer_email],
            'affiliate' => AdminPresenter::user($o->affiliate),
            'state'     => $o->state?->name,
            'payment'   => $o->payment_method === AffiliateOrder::PAYMENT_PAYSTACK ? 'Paystack' : 'Cash on Pickup',
            'total'     => showAmount($o->total_amount),
            'status'    => $this->badge($o),
            'date'      => showDateTime($o->created_at, 'd M Y'),
            'url'       => route('admin.affiliate.order.show', $o->id),
        ];
    }

    private function badge(AffiliateOrder $o): array
    {
        $class = [
            AffiliateOrder::STATUS_PENDING         => 'badge--warning',
            AffiliateOrder::STATUS_PAID            => 'badge--info',
            AffiliateOrder::STATUS_AWAITING_PICKUP => 'badge--primary',
            AffiliateOrder::STATUS_FULFILLED       => 'badge--success',
            AffiliateOrder::STATUS_CANCELLED       => 'badge--danger',
            AffiliateOrder::STATUS_EXPIRED         => 'badge--dark',
        ][$o->status] ?? 'badge--secondary';

        return AdminPresenter::badge(ucwords(str_replace('_', ' ', (string) $o->status)), $class);
    }

    /** Affiliate Orders (Pages/Admin/Affiliate/Orders.tsx). */
    public function orders(Request $request)
    {
        $pageTitle = 'Affiliate Orders';
        $status    = in_array($request->status, self::STATUSES, true) ? $request->status : '';
        $payment   = in_array($request->payment_method, [AffiliateOrder::PAYMENT_PAYSTACK, AffiliateOrder::PAYMENT_CASH_ON_PICKUP], true) ? $request->payment_method : '';
        $stateId   = (int) $request->state_id;
        $search    = trim((string) $request->search);

        $orders = AffiliateOrder::with('affiliate:id,firstname,lastname,username', 'state:id,name')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($payment, fn ($q) => $q->where('payment_method', $payment))
            ->when($stateId, fn ($q) => $q->where('state_id', $stateId))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('order_code', 'like', "%{$search}%")
                ->orWhere('buyer_name', 'like', "%{$search}%")
                ->orWhere('buyer_email', 'like', "%{$search}%")
                ->orWhereHas('affiliate', fn ($u) => $u->where('username', 'like', "%{$search}%"))))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($o) => $this->orderRow($o));

        return Inertia::render('Admin/Affiliate/Orders', [
            'orders'   => $orders,
            'statuses' => collect(self::STATUSES)->map(fn ($s) => ['value' => $s, 'label' => ucwords(str_replace('_', ' ', $s))])->values(),
            'states'   => State::orderBy('name')->get(['id', 'name'])->map(fn ($s) => ['id' => $s->id, 'name' => (string) $s->name]),
            'filters'  => ['search' => $search, 'status' => $status, 'payment_method' => $payment, 'state_id' => $stateId ? (string) $stateId : ''],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** One affiliate order (Pages/Admin/Affiliate/OrderDetail.tsx). */
    public function orderShow(AffiliateOrder $order)
    {
        $order->load('items', 'affiliate', 'state', 'stockist.user');
        $title = 'Order ' . $order->order_code;

        return Inertia::render('Admin/Affiliate/OrderDetail', [
            'title' => $title,
            'order' => $this->orderRow($order) + [
                'phone'    => $order->buyer_phone,
                'paystack' => $order->paystack_reference,
                'credited' => (bool) $order->bonus_credited,
                'stockist' => $order->stockist ? ['name' => (string) ($order->stockist->business_name ?: $order->stockist->user?->fullname), 'url' => route('admin.stockist.details', $order->stockist->id)] : null,
                'redeemed' => $order->redeemed_at ? showDateTime($order->redeemed_at, 'd M Y, h:i A') : null,
                'placed'   => showDateTime($order->created_at, 'd M Y, h:i A'),
                'ip'       => $order->ip_address,
            ],
            'items' => $order->items->map(fn ($i) => [
                'id'       => $i->id,
                'name'     => (string) $i->product_name,
                'quantity' => (int) $i->quantity,
                'unit'     => showAmount($i->unit_price),
                'line'     => showAmount($i->line_total),
                'bonus'    => showAmount($i->bonus_amount),
            ])->values(),
            'urls' => ['index' => route('admin.affiliate.orders')],
        ])->withViewData(['pageTitle' => $title]);
    }

    /**
     * Active Affiliates (Pages/Admin/Affiliate/Affiliates.tsx): members with
     * clicks or orders. The old query used HAVING without GROUP BY, which
     * strict SQL servers refuse.
     */
    public function affiliates(Request $request)
    {
        $pageTitle = 'Affiliates';
        $search    = trim((string) $request->search);

        $affiliates = User::withCount('affiliateOrders', 'affiliateClicks')
            ->where(fn ($q) => $q->whereHas('affiliateOrders')->orWhereHas('affiliateClicks'))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('username', 'like', "%{$search}%")->orWhere('firstname', 'like', "%{$search}%")->orWhere('lastname', 'like', "%{$search}%")))
            ->orderByDesc('affiliate_orders_count')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $u) => [
                'id'     => $u->id,
                'user'   => AdminPresenter::user($u),
                'clicks' => (int) $u->affiliate_clicks_count,
                'orders' => (int) $u->affiliate_orders_count,
                'bonus'  => showAmount($u->affiliate_bonus),
                'ordersUrl' => route('admin.affiliate.orders', ['search' => $u->username]),
            ]);

        return Inertia::render('Admin/Affiliate/Affiliates', [
            'affiliates' => $affiliates,
            'search'     => $search,
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** Old Funnel Report URL: the funnel is part of the audit page now. */
    public function funnel(Request $request)
    {
        return to_route('admin.affiliate.audit', $request->only('from', 'to'));
    }

    /**
     * Affiliate Audit (Pages/Admin/Affiliate/Audit.tsx): the funnel (clicks
     * to fulfilled orders) and the money for one period. They were two pages
     * with the same date form. A bad date used to give a server error.
     */
    public function audit(Request $request)
    {
        $pageTitle = 'Affiliate Funnel & Financial Audit';
        $parse = function ($v) {
            // A real calendar date only: "2026-13-45" used to roll over into 2027.
            if (!is_string($v) || !preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $v, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
                return null;
            }
            try {
                return Carbon::createFromFormat('!Y-m-d', $v);
            } catch (\Throwable) {
                return null;
            }
        };
        $from = ($parse($request->from) ?? now()->subDays(30))->copy()->startOfDay();
        $to   = ($parse($request->to) ?? now())->copy()->endOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $orders = AffiliateOrder::whereBetween('created_at', [$from, $to]);
        $clicks    = AffiliateClick::whereBetween('created_at', [$from, $to])->count();
        $placed    = (clone $orders)->count();
        $paid      = (clone $orders)->whereIn('status', [AffiliateOrder::STATUS_PAID, AffiliateOrder::STATUS_FULFILLED, AffiliateOrder::STATUS_AWAITING_PICKUP])->count();
        $fulfilled = (clone $orders)->where('status', AffiliateOrder::STATUS_FULFILLED)->count();

        $paystack = (float) (clone $orders)->where('payment_method', AffiliateOrder::PAYMENT_PAYSTACK)->whereIn('status', self::PAID)->sum('total_amount');
        $cash     = (float) (clone $orders)->where('payment_method', AffiliateOrder::PAYMENT_CASH_ON_PICKUP)->where('status', AffiliateOrder::STATUS_FULFILLED)->sum('total_amount');
        $owed     = (float) (clone $orders)->where('payment_method', AffiliateOrder::PAYMENT_CASH_ON_PICKUP)->where('status', AffiliateOrder::STATUS_AWAITING_PICKUP)->sum('total_amount');
        $bonus    = (float) Transaction::where('remark', 'affiliate_bonus')->whereBetween('created_at', [$from, $to])->sum('amount');

        return Inertia::render('Admin/Affiliate/Audit', [
            'period' => ['from' => $from->format('Y-m-d'), 'to' => $to->format('Y-m-d'), 'label' => $from->format('M d, Y') . ' – ' . $to->format('M d, Y')],
            'funnel' => [
                ['label' => 'Clicks', 'value' => $clicks, 'icon' => 'las la-mouse-pointer', 'bg' => 'primary'],
                ['label' => 'Orders Placed', 'value' => $placed, 'icon' => 'las la-shopping-cart', 'bg' => 'info'],
                ['label' => 'Paid / Awaiting Pickup', 'value' => $paid, 'icon' => 'las la-money-bill', 'bg' => 'warning'],
                ['label' => 'Fulfilled', 'value' => $fulfilled, 'icon' => 'las la-check-circle', 'bg' => 'success'],
            ],
            'conversion' => $clicks > 0 ? number_format($fulfilled / $clicks * 100, 2) . '%' : '0.00%',
            'money' => [
                'paystack' => showAmount($paystack),
                'cash'     => showAmount($cash),
                'owed'     => showAmount($owed),
                'bonus'    => showAmount($bonus),
                'revenue'  => showAmount($paystack + $cash),
                'net'      => showAmount($paystack + $cash - $bonus),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** Affiliate Settings (Pages/Admin/Affiliate/Settings.tsx). */
    public function settings()
    {
        $s = AffiliateSetting::current();

        return Inertia::render('Admin/Affiliate/Settings', [
            'settings' => [
                'cash_on_pickup_enabled'    => (bool) $s->cash_on_pickup_enabled,
                'paystack_enabled'          => (bool) $s->paystack_enabled,
                'cookie_days'               => (string) $s->cookie_days,
                'pending_order_expiry_days' => (string) $s->pending_order_expiry_days,
                'stockist_pickup_fee_type'  => $s->stockist_pickup_fee_type ?: 'fixed',
                'stockist_pickup_fee_value' => (string) ($s->stockist_pickup_fee_value + 0),
            ],
            'urls' => ['update' => route('admin.affiliate.settings.update')],
        ])->withViewData(['pageTitle' => 'Affiliate Settings']);
    }

    public function settingsUpdate(Request $request)
    {
        $request->validate([
            'cash_on_pickup_enabled'    => 'nullable|boolean',
            'paystack_enabled'          => 'nullable|boolean',
            'cookie_days'               => 'required|integer|min:1|max:365',
            'stockist_pickup_fee_type'  => 'nullable|in:fixed,percentage',
            // A percentage above 100 paid the stockist more than the order.
            'stockist_pickup_fee_value' => 'nullable|numeric|min:0' . ($request->stockist_pickup_fee_type === 'percentage' ? '|max:100' : ''),
            'pending_order_expiry_days' => 'required|integer|min:1|max:90',
        ], ['stockist_pickup_fee_value.max' => 'A percentage fee cannot be above 100.']);

        // With both off, nobody could pay for a /shop order.
        if (!$request->boolean('cash_on_pickup_enabled') && !$request->boolean('paystack_enabled')) {
            throw ValidationException::withMessages(['paystack_enabled' => 'Keep at least one payment method on, or the shop cannot take orders.']);
        }

        AffiliateSetting::current()->update([
            'cash_on_pickup_enabled'    => $request->boolean('cash_on_pickup_enabled'),
            'paystack_enabled'          => $request->boolean('paystack_enabled'),
            'cookie_days'               => $request->cookie_days,
            'stockist_pickup_fee_type'  => $request->stockist_pickup_fee_type ?: 'fixed',
            'stockist_pickup_fee_value' => $request->stockist_pickup_fee_value ?: 0,
            'pending_order_expiry_days' => $request->pending_order_expiry_days,
        ]);

        $notify[] = ['success', 'Affiliate settings updated successfully'];
        return back()->withNotify($notify);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateClick;
use App\Models\AffiliateOrder;
use App\Models\AffiliateSetting;
use App\Models\User;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function dashboard()
    {
        $pageTitle = 'Affiliate Dashboard';

        $totalOrders     = AffiliateOrder::count();
        $totalRevenue    = AffiliateOrder::whereIn('status', [AffiliateOrder::STATUS_PAID, AffiliateOrder::STATUS_FULFILLED])->sum('total_amount');
        $totalBonusPaid  = (float) \App\Models\Transaction::where('remark', 'affiliate_bonus')->sum('amount');
        $totalClicks     = AffiliateClick::count();
        $pendingPickup   = AffiliateOrder::where('status', AffiliateOrder::STATUS_AWAITING_PICKUP)->count();

        $topAffiliates = User::whereHas('affiliateOrders')
            ->withCount('affiliateOrders')
            ->orderByDesc('affiliate_orders_count')
            ->limit(5)
            ->get();

        $recentOrders = AffiliateOrder::with('affiliate', 'state')->latest()->limit(10)->get();

        return view('admin.affiliate.dashboard', compact(
            'pageTitle', 'totalOrders', 'totalRevenue', 'totalBonusPaid',
            'totalClicks', 'pendingPickup', 'topAffiliates', 'recentOrders'
        ));
    }

    public function orders(Request $request)
    {
        $pageTitle = 'Affiliate Orders';

        $orders = AffiliateOrder::with('affiliate', 'state')->latest();

        if ($request->filled('status')) {
            $orders->where('status', $request->status);
        }
        if ($request->filled('payment_method')) {
            $orders->where('payment_method', $request->payment_method);
        }
        if ($request->filled('state_id')) {
            $orders->where('state_id', $request->state_id);
        }

        $orders = $orders->paginate(20)->withQueryString();
        $states = \App\Models\State::orderBy('name')->get();

        return view('admin.affiliate.orders', compact('pageTitle', 'orders', 'states'));
    }

    public function orderShow(AffiliateOrder $order)
    {
        $pageTitle = 'Order ' . $order->order_code;
        $order->load('items', 'affiliate', 'state', 'stockist.user');

        return view('admin.affiliate.order_show', compact('pageTitle', 'order'));
    }

    public function affiliates()
    {
        $pageTitle = 'Affiliates';

        $affiliates = User::withCount('affiliateOrders', 'affiliateClicks')
            ->having('affiliate_orders_count', '>', 0)
            ->orHaving('affiliate_clicks_count', '>', 0)
            ->orderByDesc('affiliate_orders_count')
            ->paginate(20);

        return view('admin.affiliate.affiliates', compact('pageTitle', 'affiliates'));
    }

    public function funnel(Request $request)
    {
        $pageTitle = 'Affiliate Funnel';

        $from = $request->filled('from') ? $request->date('from')->startOfDay() : now()->subDays(30)->startOfDay();
        $to   = $request->filled('to') ? $request->date('to')->endOfDay() : now()->endOfDay();

        $clicks    = AffiliateClick::whereBetween('created_at', [$from, $to])->count();
        $ordersQ   = AffiliateOrder::whereBetween('created_at', [$from, $to]);
        $placed    = (clone $ordersQ)->count();
        $paid      = (clone $ordersQ)->whereIn('status', [AffiliateOrder::STATUS_PAID, AffiliateOrder::STATUS_FULFILLED, AffiliateOrder::STATUS_AWAITING_PICKUP])->count();
        $fulfilled = (clone $ordersQ)->where('status', AffiliateOrder::STATUS_FULFILLED)->count();

        $funnel = [
            'clicks'    => $clicks,
            'placed'    => $placed,
            'paid'      => $paid,
            'fulfilled' => $fulfilled,
        ];

        return view('admin.affiliate.funnel', compact('pageTitle', 'funnel', 'from', 'to'));
    }

    public function audit(Request $request)
    {
        $pageTitle = 'Affiliate Financial Audit';

        $from = $request->filled('from') ? $request->date('from')->startOfDay() : now()->subDays(30)->startOfDay();
        $to   = $request->filled('to') ? $request->date('to')->endOfDay() : now()->endOfDay();

        $orders = AffiliateOrder::whereBetween('created_at', [$from, $to]);

        $revenuePaystack = (clone $orders)->where('payment_method', AffiliateOrder::PAYMENT_PAYSTACK)
            ->whereIn('status', [AffiliateOrder::STATUS_PAID, AffiliateOrder::STATUS_FULFILLED])->sum('total_amount');

        $revenueCash = (clone $orders)->where('payment_method', AffiliateOrder::PAYMENT_CASH_ON_PICKUP)
            ->where('status', AffiliateOrder::STATUS_FULFILLED)->sum('total_amount');

        $outstandingCash = (clone $orders)->where('payment_method', AffiliateOrder::PAYMENT_CASH_ON_PICKUP)
            ->where('status', AffiliateOrder::STATUS_AWAITING_PICKUP)->sum('total_amount');

        $bonusPaid = \App\Models\Transaction::where('remark', 'affiliate_bonus')
            ->whereBetween('created_at', [$from, $to])->sum('amount');

        return view('admin.affiliate.audit', compact(
            'pageTitle', 'from', 'to', 'revenuePaystack', 'revenueCash', 'outstandingCash', 'bonusPaid'
        ));
    }

    public function settings()
    {
        $pageTitle = 'Affiliate Settings';
        $settings  = AffiliateSetting::current();

        return view('admin.affiliate.settings', compact('pageTitle', 'settings'));
    }

    public function settingsUpdate(Request $request)
    {
        $request->validate([
            'cash_on_pickup_enabled'     => 'nullable|boolean',
            'paystack_enabled'           => 'nullable|boolean',
            'cookie_days'                => 'required|integer|min:1|max:365',
            'stockist_pickup_fee_type'   => 'nullable|in:fixed,percentage',
            'stockist_pickup_fee_value'  => 'nullable|numeric|min:0',
            'pending_order_expiry_days'  => 'required|integer|min:1|max:90',
        ]);

        $settings = AffiliateSetting::current();
        $settings->update([
            'cash_on_pickup_enabled'    => (bool) $request->cash_on_pickup_enabled,
            'paystack_enabled'          => (bool) $request->paystack_enabled,
            'cookie_days'               => $request->cookie_days,
            'stockist_pickup_fee_type'  => $request->stockist_pickup_fee_type ?: 'fixed',
            'stockist_pickup_fee_value' => $request->stockist_pickup_fee_value ?: 0,
            'pending_order_expiry_days' => $request->pending_order_expiry_days,
        ]);

        $notify[] = ['success', 'Affiliate settings updated successfully'];
        return back()->withNotify($notify);
    }
}

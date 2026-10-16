<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdShare;
use App\Models\AffiliateOrder;
use Illuminate\Http\Request;

class AffiliateController extends Controller
{
    public function dashboard()
    {
        $pageTitle = 'Affiliate Dashboard';
        $user      = auth()->user();
        $code      = $user->getOrCreateAffiliateCode();
        $shopLink  = route('shop.index', ['ref' => $code]);

        $clicksCount = $user->affiliateClicks()->count();
        $orders      = $user->affiliateOrders();
        $ordersCount = (clone $orders)->count();
        $paidCount   = (clone $orders)->whereIn('status', [AffiliateOrder::STATUS_PAID, AffiliateOrder::STATUS_FULFILLED])->count();
        $fulfilledCount = (clone $orders)->where('status', AffiliateOrder::STATUS_FULFILLED)->count();

        $recentOrders = (clone $orders)->latest()->limit(8)->get();
        $adsCount     = Ad::active()->count();

        return view('Template::user.affiliate.dashboard', compact(
            'pageTitle', 'user', 'code', 'shopLink', 'clicksCount',
            'ordersCount', 'paidCount', 'fulfilledCount', 'recentOrders', 'adsCount'
        ));
    }

    public function orders()
    {
        $pageTitle = 'My Affiliate Orders';
        $orders    = auth()->user()->affiliateOrders()->with('items')->latest()->paginate(15);

        return view('Template::user.affiliate.orders', compact('pageTitle', 'orders'));
    }

    /** Ad Center: ready-made posts, each with this member's own tracked link. */
    public function ads()
    {
        $pageTitle = 'Ad Center';
        $user      = auth()->user();
        $user->getOrCreateAffiliateCode();

        $ads = Ad::active()->with('product:id,name')->ordered()->paginate(12);

        $visits = $user->affiliateClicks()
            ->whereNotNull('ad_id')
            ->selectRaw('ad_id, COUNT(*) AS total')
            ->groupBy('ad_id')
            ->pluck('total', 'ad_id');

        // Paid orders from people who arrived through this member's ad links
        $orders = $user->affiliateOrders()
            ->join('affiliate_clicks', 'affiliate_clicks.id', '=', 'affiliate_orders.affiliate_click_id')
            ->whereNotNull('affiliate_clicks.ad_id')
            ->whereIn('affiliate_orders.status', [AffiliateOrder::STATUS_PAID, AffiliateOrder::STATUS_FULFILLED])
            ->selectRaw('affiliate_clicks.ad_id, COUNT(*) AS total')
            ->groupBy('affiliate_clicks.ad_id')
            ->pluck('total', 'ad_id');

        $shared = $user->adShares()->distinct()->count('ad_id');

        return view('Template::user.affiliate.ads', compact('pageTitle', 'user', 'ads', 'visits', 'orders', 'shared'));
    }

    /**
     * Logs a share button tap. Called in the background; the share itself happens in the browser.
     * Answers in JSON itself: this app only renders exceptions as JSON under api/*.
     */
    public function adShared(Request $request, int $ad)
    {
        $channel = $request->input('channel');

        if (!is_string($channel) || !array_key_exists($channel, AdShare::CHANNELS)) {
            return response()->json(['ok' => false, 'message' => 'Unknown channel.'], 422);
        }

        $ad = Ad::active()->find($ad);

        if (!$ad) {
            return response()->json(['ok' => false, 'message' => 'This ad is no longer available.'], 404);
        }

        AdShare::create([
            'ad_id'   => $ad->id,
            'user_id' => auth()->id(),
            'channel' => $channel,
        ]);

        return response()->json(['ok' => true]);
    }
}

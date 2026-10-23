<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdShare;
use App\Models\AffiliateOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AffiliateController extends Controller
{
    public function dashboard()
    {
        $user  = auth()->user();
        $code  = $user->getOrCreateAffiliateCode();
        $orders = $user->affiliateOrders();

        return Inertia::render('User/Affiliate/Dashboard', [
            'shopLink' => route('shop.index', ['ref' => $code]),
            'stats'    => [
                'clicks'    => number_format($user->affiliateClicks()->count()),
                'orders'    => number_format((clone $orders)->count()),
                'fulfilled' => number_format((clone $orders)->where('status', AffiliateOrder::STATUS_FULFILLED)->count()),
                'bonus'     => showAmount($user->affiliate_bonus_balance),
            ],
            'recentOrders' => (clone $orders)->latest()->limit(8)->get()->map(fn ($order) => $this->presentOrder($order, 'M d, Y'))->values(),
            'adsCount' => Ad::active()->count(),
            'urls'     => [
                'ads'           => route('user.affiliate.ads'),
                'orders'        => route('user.affiliate.orders'),
                'bonusTransfer' => route('user.bonus.transfer.index'),
            ],
        ])->withViewData(['pageTitle' => 'Affiliate Dashboard']);
    }

    public function orders()
    {
        $orders = auth()->user()->affiliateOrders()->withSum('items as items_quantity', 'quantity')->latest()->paginate(15)
            ->through(fn ($order) => $this->presentOrder($order, 'M d, Y g:i A') + [
                'buyerEmail' => $order->buyer_email,
                'items'      => (int) $order->items_quantity,
            ]);

        return Inertia::render('User/Affiliate/Orders', [
            'orders' => $orders,
            'urls'   => ['dashboard' => route('user.affiliate.dashboard')],
        ])->withViewData(['pageTitle' => 'My Affiliate Orders']);
    }

    /** One order row, as both affiliate pages show it. */
    private function presentOrder(AffiliateOrder $order, string $dateFormat): array
    {
        return [
            'id'      => $order->id,
            'code'    => $order->order_code,
            'buyer'   => $order->buyer_name,
            'total'   => showAmount($order->total_amount),
            'payment' => $order->payment_method === 'paystack' ? 'Online' : 'Cash on Pickup',
            // Same colours as AffiliateOrder::status_badge, without sending HTML.
            'status'  => [
                'label' => ucwords(str_replace('_', ' ', $order->status)),
                'class' => match ($order->status) {
                    AffiliateOrder::STATUS_PENDING         => 'badge--warning',
                    AffiliateOrder::STATUS_PAID            => 'badge--info',
                    AffiliateOrder::STATUS_AWAITING_PICKUP => 'badge--primary',
                    AffiliateOrder::STATUS_FULFILLED       => 'badge--success',
                    AffiliateOrder::STATUS_CANCELLED       => 'badge--danger',
                    AffiliateOrder::STATUS_EXPIRED         => 'badge--dark',
                    default                                => 'badge--secondary',
                },
            ],
            'date'    => $order->created_at?->format($dateFormat) ?? '',
        ];
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

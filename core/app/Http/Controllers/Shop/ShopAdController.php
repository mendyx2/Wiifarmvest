<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AffiliateClick;
use App\Support\Crawler;
use Illuminate\Http\Request;

class ShopAdController extends Controller
{
    public function show(Request $request, int $ad)
    {
        $ad = Ad::with('product')->find($ad);

        // A deleted ad still takes old posts to the shop; the ref was tracked already
        if (!$ad) {
            return redirect()->route('shop.index');
        }

        if ($clickId = $request->attributes->get('affiliate_click_id')) {
            AffiliateClick::whereKey($clickId)->update(['ad_id' => $ad->id]);
        }

        if (Crawler::is($request)) {
            // og:url must keep ?ref=: networks send clicks on the card to og:url.
            // Rebuilt from the ref alone, so fbclid & co. or made-up parameters never end up in it.
            $ref      = $request->query('ref');
            $shareUrl = route('shop.ad', is_string($ref) && $ref !== '' ? ['ad' => $ad->id, 'ref' => $ref] : ['ad' => $ad->id]);

            return view('Template::shop.ad_preview', ['ad' => $ad, 'shareUrl' => $shareUrl]);
        }

        return redirect()->to($ad->is_active ? $ad->destinationUrl() : route('shop.index'));
    }
}

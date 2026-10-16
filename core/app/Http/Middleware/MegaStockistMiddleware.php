<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ordering stock from the company is for Mega stockists only. Mini stockists
 * restock by buying from a Mega stockist in their state (Stockist → Buy from
 * Mega), so every route that orders from the company sits behind this, the
 * pages and the AJAX cart calls alike. Hiding the links is not enough.
 */
class MegaStockistMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $stockist = $request->user()?->stockist;

        if ($stockist && $stockist->isMega()) {
            return $next($request);
        }

        $message = $stockist
            ? 'Mini stockists restock from a Mega stockist in their state, not from the company.'
            : 'Only stockists can order stock.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        $notify[] = ['error', $message];

        return $stockist
            ? to_route('user.stockist.transfers.create')->withNotify($notify)
            : to_route('user.home')->withNotify($notify);
    }
}

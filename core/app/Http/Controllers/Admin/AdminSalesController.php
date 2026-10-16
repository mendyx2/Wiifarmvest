<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminSalesController extends Controller
{
    /**
     * List view — shows last 10 of today by default.
     * When a search term is submitted, searches all orders across all dates.
     */
    public function index(Request $request)
    {
        $pageTitle = 'Manage Sales';
        $search    = trim($request->get('search'));

        $query = Order::with(['user', 'state', 'invoice.redemptions'])
                      ->orderByDesc('created_at');

        if ($search) {
            // Search across trx (invoice_code), username, or invoice code
            $query->where(function ($q) use ($search) {
                $q->where('invoice_code', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) =>
                        $u->where('username',  'like', "%{$search}%")
                          ->orWhere('firstname', 'like', "%{$search}%")
                          ->orWhere('lastname',  'like', "%{$search}%")
                  )
                  ->orWhereHas('invoice', fn ($i) =>
                        $i->where('invoice_code', 'like', "%{$search}%")
                  );
            });

            $orders    = $query->paginate(20)->withQueryString();
            $isSearch  = true;
        } else {
            // Default: last 10 sales placed today
            //$orders   = $query->whereDate('created_at', today())->limit(10)->get();
            $orders   = $query->paginate(10)->withQueryString();
            $isSearch = false;
        }

        // Summary stats for today (always based on today regardless of search)
        $todayOrders = Order::whereDate('created_at', today());

     
        $stats = [
            'total'    => (clone $todayOrders)->count(),
            'redeemed' => (clone $todayOrders)->whereHas('invoice', fn ($q) =>
                                $q->whereNotNull('redeemed_at')
                          )->count(),
            'amount'   => (clone $todayOrders)->with('invoice')
                                ->get()
                                ->sum(fn ($o) => (float) optional($o->invoice)->total_amount),
        ];

        return view('admin.sales.index', compact('pageTitle', 'orders', 'search', 'isSearch', 'stats'));
    }

    /**
     * Detail view — all products in the order, redemption info, stockist.
     */
    public function show($id)
    {
        $pageTitle = 'Sale Detail';

        $order = Order::with([
            'user',
            'state',
            'items.product',                      // order line items + product info
            'invoice.redemptions.stockist.user',  // who redeemed & which stockist
        ])->findOrFail($id);

        // Build a per-product redemption summary for the detail view
        $invoice         = $order->invoice;
        $redeemedQtyMap  = [];   // product_id => total qty redeemed

        if ($invoice) {
            foreach ($invoice->redemptions as $redemption) {
                foreach ((array) $redemption->redeemed_items as $item) {
                    $pid = $item['product_id'] ?? null;
                    if ($pid) {
                        $redeemedQtyMap[$pid] = ($redeemedQtyMap[$pid] ?? 0) + ($item['quantity'] ?? 0);
                    }
                }
            }
        }

        return view('admin.sales.show', compact('pageTitle', 'order', 'invoice', 'redeemedQtyMap'));
    }
}

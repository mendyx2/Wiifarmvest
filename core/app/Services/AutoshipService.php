<?php

namespace App\Services;

use App\Constants\Status;
use Illuminate\Support\Facades\DB;

class AutoshipService
{
   
    public const MIN_PV = 10;

    public function isFulfilled(int $userId): bool
    {
        return $this->monthlyPv($userId) >= self::MIN_PV;
    }

  
    public function monthlyPv(int $userId): float
    {
        $from = now()->startOfMonth();
        $to   = now()->endOfMonth();

        $thisMonthsOrders = fn ($q) => $q->where('orders.user_id', $userId)
            ->where('orders.status', '!=', Status::ORDER_CANCELED)
            ->whereBetween('orders.created_at', [$from, $to]);

        $cartPv = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where($thisMonthsOrders)
            ->sum(DB::raw('order_items.quantity * products.pv'));

        $singlePv = DB::table('orders')
            ->join('products', 'products.id', '=', 'orders.product_id')
            ->where($thisMonthsOrders)
            ->whereNotExists(fn ($q) => $q->from('order_items')->whereColumn('order_items.order_id', 'orders.id'))
            ->sum(DB::raw('orders.quantity * products.pv'));

        return (float) $cartPv + (float) $singlePv;
    }
}

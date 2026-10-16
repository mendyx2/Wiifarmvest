<?php

namespace App\Console\Commands;

use App\Services\AffiliateOrderService;
use Illuminate\Console\Command;

class ExpireAffiliateOrders extends Command
{
    protected $signature = 'affiliate:expire-orders';

    protected $description = 'Cancel stale unpaid/unpicked-up affiliate shop orders and release their reserved stock';

    public function handle(AffiliateOrderService $orders)
    {
        $count = $orders->expireStaleOrders();
        $this->info("Expired {$count} affiliate order(s).");

        return self::SUCCESS;
    }
}

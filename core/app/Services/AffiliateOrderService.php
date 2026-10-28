<?php

namespace App\Services;

use App\Mail\AffiliateInvoiceMail;
use App\Models\AffiliateOrder;
use App\Models\AffiliateSetting;
use App\Models\Product;
use App\Models\Stockist;
use App\Models\StockistRedemption;
use App\Models\Stockist_store;
use App\Services\StockistRebateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AffiliateOrderService
{
    const AFFILIATE_BONUS_TYPE = 18; 
    public function createOrder(array $buyer, array $resolvedCart, string $paymentMethod, ?array $attribution, string $ip, string $userAgent): AffiliateOrder
    {
        if (empty($resolvedCart['lines'])) {
            throw new \RuntimeException('Your cart is empty.');
        }

        return DB::transaction(function () use ($buyer, $resolvedCart, $paymentMethod, $attribution, $ip, $userAgent) {
            $order = AffiliateOrder::create([
                'affiliate_user_id'  => $attribution['affiliate_user_id'] ?? null,
                'affiliate_click_id' => $attribution['click_id'] ?? null,
                'buyer_name'         => $buyer['name'],
                'buyer_email'        => $buyer['email'],
                'buyer_phone'        => $buyer['phone'] ?? null,
                'state_id'           => $buyer['state_id'],
                'payment_method'     => $paymentMethod,
                'status'             => $paymentMethod === AffiliateOrder::PAYMENT_CASH_ON_PICKUP
                                            ? AffiliateOrder::STATUS_AWAITING_PICKUP
                                            : AffiliateOrder::STATUS_PENDING,
                'subtotal'           => $resolvedCart['subtotal'],
                'total_amount'       => $resolvedCart['subtotal'],
                'ip_address'         => $ip,
                'user_agent'         => $userAgent,
            ]);

            foreach ($resolvedCart['lines'] as $line) {
                /** @var Product $product */
                $product = $line['product'];

                $locked = Product::whereKey($product->id)->lockForUpdate()->first();
                if (!$locked || $locked->quantity < $line['quantity']) {
                    throw new \RuntimeException("\"{$product->name}\" no longer has enough stock available.");
                }

                $locked->decrement('quantity', $line['quantity']);

                $bonus = $order->affiliate_user_id
                    ? $product->calculateAffiliateBonus($line['unit_price'], $line['quantity'])
                    : 0.0;

                $order->items()->create([
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'quantity'     => $line['quantity'],
                    'unit_price'   => $line['unit_price'],
                    'line_total'   => $line['line_total'],
                    'bonus_amount' => $bonus,
                ]);
            }

            if ($order->status === AffiliateOrder::STATUS_AWAITING_PICKUP) {
                $this->sendInvoiceEmail($order);
            }

            return $order->fresh('items');
        });
    }

    
    public function markPaystackPaid(AffiliateOrder $order, string $reference): void
    {
        if ($order->status !== AffiliateOrder::STATUS_PENDING) {
            return;
        }

        DB::transaction(function () use ($order, $reference) {
            $order->update([
                'status'             => AffiliateOrder::STATUS_PAID,
                'paystack_reference' => $reference,
            ]);

            $this->creditAffiliateBonus($order->fresh('items'));
        });

        $this->sendInvoiceEmail($order->fresh());
    }


    public function confirmPickup(AffiliateOrder $order, Stockist $stockist): void
    { 
        if (!$order->isReadyForPickup()) {
            throw new \RuntimeException('This order is not awaiting pickup.');
        }

        DB::transaction(function () use ($order, $stockist) {
            // Claim the pickup: only one request can move the order to fulfilled,
            // so a double click can't take stock or pay the stockist twice
            $claimed = AffiliateOrder::whereKey($order->id)
                ->whereIn('status', [AffiliateOrder::STATUS_AWAITING_PICKUP, AffiliateOrder::STATUS_PAID])
                ->update([
                    'status'                  => AffiliateOrder::STATUS_FULFILLED,
                    'redeemed_by_stockist_id' => $stockist->id,
                    'redeemed_at'             => now(),
                ]);

            if (!$claimed) {
                throw new \RuntimeException('This order has already been picked up.');
            }

            $order->loadMissing('items.product');

            foreach ($order->items as $item) {
                // Locked: a POS sale at the same moment can't use the same units.
                $stockistStore = Stockist_store::where('user_id', $stockist->user_id)
                    ->where('product_id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$stockistStore) {
                    throw new \RuntimeException("This stockist does not have the product \"{$item->product_name}\" in stock.");
                }

                if ($stockistStore->quantity < $item->quantity) {
                    throw new \RuntimeException("Insufficient stock for \"{$item->product_name}\" to complete this affiliate pickup.");
                }

                $stockistStore->decrementStock($item->quantity);
            }

            StockistRedemption::create([
                'stockist_id'        => $stockist->id,
                'type'               => StockistRedemption::TYPE_AFFILIATE_INVOICE,
                'reference_code'     => $order->order_code,
                'affiliate_order_id' => $order->id,
                'buyer_name'         => $order->buyer_name,
                'items'              => $order->items->map(fn ($item) => [
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product_name,
                    'quantity'     => $item->quantity,
                    'unit_price'   => $item->unit_price,
                    'subtotal'     => $item->line_total,
                ])->all(),
                'quantity'    => $order->items->sum('quantity'),
                'amount'      => $order->total_amount,
                'redeemed_at' => now(),
            ]);

            if ($order->payment_method === AffiliateOrder::PAYMENT_CASH_ON_PICKUP) {
               
                $this->creditAffiliateBonus($order->fresh('items'));
            }
            // Stockist rebate per product: tier % on its state price × PV × rDollar
            // (same rule as invoices and welcome packs), into stockist_rebate
            $trx     = getTrx(10);
            $rebates = app(StockistRebateService::class);
            foreach ($order->items as $item) {
                if ($item->product) {
                    $rebates->credit($stockist, $item->product, (int) $item->quantity, $trx, 'Affiliate order ' . $order->order_code);
                }
            }
        });
    }

    public function creditAffiliateBonus(AffiliateOrder $order): void
    { 
       
        $affiliate = $order->affiliate;
        if ($order->bonus_credited || !$order->affiliate) {
            return;
        }

        
        $order->loadMissing('items.product');
        $total = round((float) $order->items->sum(function ($item) {
            if ((float) $item->bonus_amount > 0) {
                return (float) $item->bonus_amount;
            }
            return (float) ($item->product ? $item->product->calculateAffiliateBonus($item->unit_price, $item->quantity) : 0.0);
        }), 2);

        if ($total > 0) { 
            $affiliate->addAffiliateBonus($total);
            newTransaction(
                $affiliate,
                'Affiliate bonus for order ' . $order->order_code,
                'affiliate_bonus',
                $total,
                '+',
                getTrx(10),
                self::AFFILIATE_BONUS_TYPE,
                0,
                'affiliate_bonus'
            );
        }
        $order->update(['bonus_credited' => true]);
    }

    private function sendInvoiceEmail(AffiliateOrder $order): void
    {
        try {
            Mail::to($order->buyer_email)->send(new AffiliateInvoiceMail($order));
        } catch (\Throwable $e) {
            Log::error('Affiliate invoice email failed for order ' . $order->order_code . ': ' . $e->getMessage());
        }
    }

    
    public function expireStaleOrders(): int
    {
        $days = AffiliateSetting::current()->pending_order_expiry_days ?: 7;
        $cutoff = now()->subDays($days);

        $stale = AffiliateOrder::whereIn('status', [AffiliateOrder::STATUS_PENDING, AffiliateOrder::STATUS_AWAITING_PICKUP])
            ->where('created_at', '<', $cutoff)
            ->with('items')
            ->get();

        foreach ($stale as $order) {
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    Product::whereKey($item->product_id)->increment('quantity', $item->quantity);
                }
                $order->update(['status' => AffiliateOrder::STATUS_EXPIRED]);
            });
        }

        return $stale->count();
    }
}

<?php

namespace App\Services;

use App\Constants\Status;
use App\Exceptions\StockTransferException;
use App\Models\Admin;
use App\Models\AdminNotification;
use App\Models\NotificationLog;
use App\Models\Product;
use App\Models\ProductStatePrice;
use App\Models\StockTransfer;
use App\Models\Stockist;
use App\Models\Stockist_store;
use App\Models\Stransaction;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public const MAX_LINES    = 50;
    public const MAX_QUANTITY = 100000;

    // Ledger remarks in stransactions (stockist wallets). Credits are internal
    // moves between stockists, listed under "ignore" in config/financial_report.php.
    public const REMARK_PAYMENT = 'stock_transfer_payment';
    public const REMARK_SALE    = 'stock_transfer_sale';
    public const REMARK_REFUND  = 'stock_transfer_refund';

    public function __construct(private StockistRebateService $rebates)
    {
    }

    /** Mega stockists a Mini can buy from: active, in the Mini's state. */
    public function sellersFor(Stockist $buyer): Collection
    {
        return Stockist::query()
            ->where('store_type', Stockist::STORE_TYPE_MEGA)
            ->where('state_id', $buyer->state_id)
            ->where('status', Status::ACTIVE)
            ->where('is_active', true)
            ->whereKeyNot($buyer->id)
            ->with('user:id,firstname,lastname,username,mobile')
            ->addSelect(['products_in_stock' => Stockist_store::selectRaw('COUNT(DISTINCT product_id)')
                ->whereColumn('stockist_stores.user_id', 'stockists.user_id')
                ->where('quantity', '>', 0)])
            ->orderByDesc('products_in_stock')
            ->get();
    }

    /**
     * What a Mega can sell: products they hold that have a price in their state.
     * Each row: product, available, unit_price, state_price.
     */
    public function catalogue(Stockist $seller): Collection
    {
        $stock = $this->stockRows($seller, null)->filter(fn ($row) => $row->quantity > 0 && $row->product);

        $prices = ProductStatePrice::where('state_id', $seller->state_id)
            ->whereIn('product_id', $stock->keys())
            ->get()
            ->keyBy('product_id');

        return $stock
            ->filter(fn ($row, $productId) => $prices->has($productId))
            ->map(fn ($row, $productId) => (object) [
                'product'     => $row->product,
                'available'   => (int) $row->quantity,
                'unit_price'  => (float) $prices[$productId]->price,
                'state_price' => $prices[$productId],
            ])
            ->sortBy(fn ($row) => $row->product->name)
            ->values();
    }

    /**
     * The Mini asks a Mega for stock and pays for it now; the money is held on
     * the transfer until the Mega confirms or declines.
     *
     * @param array<int,int> $quantities product_id => quantity
     */
    public function request(Stockist $buyer, Stockist $seller, array $quantities, ?string $note = null): StockTransfer
    {
        $this->assertCanBuy($buyer);
        $this->assertCanSell($seller, $buyer);

        $quantities = collect($quantities)
            ->mapWithKeys(fn ($qty, $productId) => [(int) $productId => (int) $qty])
            ->filter(fn ($qty) => $qty > 0);

        if ($quantities->isEmpty()) {
            throw new StockTransferException('Enter a quantity for at least one product.');
        }
        if ($quantities->count() > self::MAX_LINES) {
            throw new StockTransferException('A request can hold up to ' . self::MAX_LINES . ' products.');
        }
        if ($quantities->max() > self::MAX_QUANTITY) {
            throw new StockTransferException('A single product can be requested up to ' . number_format(self::MAX_QUANTITY) . ' units at a time.');
        }

        // Price every line on the server, from the seller's state price
        $catalogue = $this->catalogue($seller)->keyBy(fn ($row) => $row->product->id);
        $lines     = [];
        $total     = '0';

        foreach ($quantities as $productId => $qty) {
            $row = $catalogue->get($productId);
            if (!$row) {
                throw new StockTransferException('One of the products is no longer available from this Mega stockist. Refresh the page.');
            }
            if ($qty > $row->available) {
                throw new StockTransferException("{$row->product->name}: {$seller->business_name} has {$row->available} in stock.");
            }

            $lineTotal = bcmul($this->money($row->unit_price), (string) $qty, 2);
            $total     = bcadd($total, $lineTotal, 2);
            $lines[]   = [
                'product_id'   => $productId,
                'product_name' => $row->product->name,
                'quantity'     => $qty,
                'unit_price'   => $this->money($row->unit_price),
                'line_total'   => $lineTotal,
            ];
        }

        if (bccomp($total, '0', 2) <= 0) {
            throw new StockTransferException('These products have no price set for your state. Contact admin.');
        }

        $transfer = DB::transaction(function () use ($buyer, $seller, $lines, $total, $quantities, $note) {
            $buyer = Stockist::whereKey($buyer->id)->lockForUpdate()->first();

            if (bccomp((string) $buyer->wallet, $total, 8) < 0) {
                throw new StockTransferException(sprintf(
                    'Your stockist wallet has %s; this request costs %s. Top up and try again.',
                    showAmount($buyer->wallet), showAmount($total)
                ));
            }

            $buyer->wallet = bcsub((string) $buyer->wallet, $total, 8);
            $buyer->save();

            $transfer = StockTransfer::create([
                'code'                => StockTransfer::newCode(),
                'buyer_stockist_id'   => $buyer->id,
                'seller_stockist_id'  => $seller->id,
                'state_id'            => $seller->state_id,
                'status'              => StockTransfer::STATUS_PENDING,
                'total_amount'        => $total,
                'total_quantity'      => $quantities->sum(),
                'trx'                 => getTrx(12),
                'buyer_balance_after' => $buyer->wallet,
                'buyer_note'          => $note ? mb_substr(trim($note), 0, 500) : null,
            ]);
            $transfer->items()->createMany($lines);

            $this->ledger($buyer, $total, '-', self::REMARK_PAYMENT, "Stock request {$transfer->code} to {$seller->business_name}", $transfer->trx);

            return $transfer;
        });

        $transfer->load('items', 'buyer.user', 'seller.user');
        $this->notifyRequested($transfer);

        return $transfer;
    }

    /**
     * The Mega hands the stock over: inventories move, the held payment goes to
     * the Mega's stockist wallet and the Mega earns the stockist rebate.
     */
    public function confirm(StockTransfer $transfer, Stockist $seller): StockTransfer
    {
        $transfer = DB::transaction(function () use ($transfer, $seller) {
            $transfer = $this->lockPending($transfer, sellerId: $seller->id);

            $parties = Stockist::whereIn('id', [$transfer->buyer_stockist_id, $transfer->seller_stockist_id])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $buyer  = $parties[$transfer->buyer_stockist_id];
            $seller = $parties[$transfer->seller_stockist_id];

            if (!$seller->isMega()) {
                throw new StockTransferException('Only a Mega stockist can confirm a stock request.');
            }

            // Read-only checks first: the claim below must not be followed by a refusal
            $items      = $transfer->items()->orderBy('product_id')->get();
            $productIds = $items->pluck('product_id')->all();
            $sellerRows = $this->stockRows($seller, $productIds, lock: true);
            $buyerRows  = $this->stockRows($buyer, $productIds, lock: true);
            $products   = Product::whereIn('id', $productIds)->get()->keyBy('id');
            $prices     = ProductStatePrice::where('state_id', $seller->state_id)->whereIn('product_id', $productIds)->get()->keyBy('product_id');

            foreach ($items as $item) {
                $have = (int) ($sellerRows->get($item->product_id)->quantity ?? 0);
                if ($have < $item->quantity) {
                    throw new StockTransferException("You have {$have} of {$item->product_name} and this request needs {$item->quantity}. Restock, or decline the request.");
                }
            }

            $settleTrx = getTrx(12);
            $claimed   = StockTransfer::whereKey($transfer->id)
                ->where('status', StockTransfer::STATUS_PENDING)
                ->update([
                    'status'       => StockTransfer::STATUS_COMPLETED,
                    'completed_at' => now(),
                    'closed_by'    => 'seller',
                    'settle_trx'   => $settleTrx,
                ]);
            if (!$claimed) {
                throw new StockTransferException('This request was already handled.');
            }

            // From here on: writes that succeed or throw
            $rebateTotal = '0';
            foreach ($items as $item) {
                $from         = $sellerRows->get($item->product_id);
                $sellerBefore = (int) $from->quantity;
                $from->quantity = $sellerBefore - $item->quantity;
                $from->save();

                $to = $buyerRows->get($item->product_id) ?? new Stockist_store([
                    'user_id'    => $buyer->user_id,
                    'product_id' => $item->product_id,
                    'quantity'   => 0,
                    'is_active'  => true,
                    'status'     => 1,
                ]);
                $buyerBefore          = (int) $to->quantity;
                $to->stockist_id      = $buyer->id;
                $to->quantity         = $buyerBefore + $item->quantity;
                $to->is_active        = true;
                $to->last_restocked_at = now();
                $to->save();

                $rebate = 0.0;
                $percent = null;
                if ($product = $products->get($item->product_id)) {
                    $statePrice = $prices->get($item->product_id);
                    $percent    = $statePrice?->bonusPercentFor($seller);
                    $rebate     = $this->rebates->credit(
                        $seller, $product, $item->quantity, $settleTrx,
                        "Stock transfer {$transfer->code} to {$buyer->business_name}", $statePrice
                    );
                }
                $rebateTotal = bcadd($rebateTotal, $this->money($rebate), 2);

                $item->update([
                    'rebate_percent'      => $percent,
                    'rebate_amount'       => $rebate,
                    'seller_stock_before' => $sellerBefore,
                    'seller_stock_after'  => $from->quantity,
                    'buyer_stock_before'  => $buyerBefore,
                    'buyer_stock_after'   => $to->quantity,
                ]);
            }

            $seller->wallet = bcadd((string) $seller->wallet, (string) $transfer->total_amount, 8);
            $seller->save();

            $this->ledger($seller, (string) $transfer->total_amount, '+', self::REMARK_SALE,
                "Stock sold to {$buyer->business_name} — {$transfer->code}", $settleTrx);

            StockTransfer::whereKey($transfer->id)->update([
                'rebate_amount'        => $rebateTotal,
                'seller_balance_after' => $seller->wallet,
            ]);

            return $transfer;
        });

        $transfer = $transfer->fresh(['items', 'buyer.user', 'seller.user']);
        $this->notifyCompleted($transfer);

        return $transfer;
    }

    /** The Mega turns the request down; the Mini gets the money back. */
    public function decline(StockTransfer $transfer, Stockist $seller, string $reason): StockTransfer
    {
        $reason = mb_substr(trim($reason), 0, 500);
        if ($reason === '') {
            throw new StockTransferException('Tell the Mini stockist why you are declining.');
        }

        $transfer = $this->closeWithRefund($transfer, StockTransfer::STATUS_DECLINED, 'seller', $reason, sellerId: $seller->id);

        $this->tell($transfer->buyer?->user, 'STOCK_TRANSFER_DECLINED', $transfer,
            "Stock request {$transfer->code} declined",
            "{$transfer->seller->business_name} declined your stock request {$transfer->code}: {$reason}. "
            . showAmount($transfer->total_amount) . ' was refunded to your stockist wallet.',
            ['reason' => $reason, 'post_balance' => showAmount($transfer->buyer->wallet, currencyFormat: false)]);

        return $transfer;
    }

    /** The Mini withdraws a request the Mega hasn't acted on; the money comes back. */
    public function cancel(StockTransfer $transfer, Stockist $buyer): StockTransfer
    {
        $reason   = 'Cancelled by ' . $buyer->business_name;
        $transfer = $this->closeWithRefund($transfer, StockTransfer::STATUS_CANCELLED, 'buyer', $reason, buyerId: $buyer->id);

        $this->tell($transfer->seller?->user, 'STOCK_TRANSFER_CANCELLED', $transfer,
            "Stock request {$transfer->code} cancelled",
            "{$transfer->buyer->business_name} cancelled stock request {$transfer->code}. There is nothing to confirm.",
            ['reason' => $reason]);

        return $transfer;
    }

    /** Support: close a request stuck in pending and refund the Mini. */
    public function adminCancel(StockTransfer $transfer, Admin $admin, string $reason): StockTransfer
    {
        $reason   = 'Cancelled by admin: ' . mb_substr(trim($reason), 0, 450);
        $transfer = $this->closeWithRefund($transfer, StockTransfer::STATUS_CANCELLED, 'admin', $reason, adminId: $admin->id);

        $this->tell($transfer->buyer?->user, 'STOCK_TRANSFER_DECLINED', $transfer,
            "Stock request {$transfer->code} cancelled",
            "Your stock request {$transfer->code} was cancelled by admin. " . showAmount($transfer->total_amount) . ' was refunded to your stockist wallet.',
            ['reason' => $reason, 'post_balance' => showAmount($transfer->buyer->wallet, currencyFormat: false)]);
        $this->tell($transfer->seller?->user, 'STOCK_TRANSFER_CANCELLED', $transfer,
            "Stock request {$transfer->code} cancelled",
            "Stock request {$transfer->code} from {$transfer->buyer->business_name} was cancelled by admin.",
            ['reason' => $reason]);

        return $transfer;
    }

    /** Rebate the Mega would earn on this request, for showing before confirming. */
    public function rebatePreview(StockTransfer $transfer): float
    {
        $seller = $transfer->seller;
        $total  = 0.0;

        foreach ($transfer->items as $item) {
            if ($item->product) {
                $total += $this->rebates->amountFor($seller, $item->product, $item->quantity);
            }
        }

        return round($total, 2);
    }

    // ── Internals ─────────────────────────────────────────────────────────

    private function closeWithRefund(
        StockTransfer $transfer,
        string $status,
        string $closedBy,
        string $reason,
        ?int $sellerId = null,
        ?int $buyerId = null,
        ?int $adminId = null,
    ): StockTransfer {
        $transfer = DB::transaction(function () use ($transfer, $status, $closedBy, $reason, $sellerId, $buyerId, $adminId) {
            $transfer = $this->lockPending($transfer, sellerId: $sellerId, buyerId: $buyerId);
            $buyer    = Stockist::whereKey($transfer->buyer_stockist_id)->lockForUpdate()->first();

            $settleTrx = getTrx(12);
            $closedAt  = $status === StockTransfer::STATUS_DECLINED ? 'declined_at' : 'cancelled_at';
            $claimed   = StockTransfer::whereKey($transfer->id)
                ->where('status', StockTransfer::STATUS_PENDING)
                ->update([
                    'status'        => $status,
                    $closedAt       => now(),
                    'closed_by'     => $closedBy,
                    'decision_note' => $reason,
                    'admin_id'      => $adminId,
                    'settle_trx'    => $settleTrx,
                ]);
            if (!$claimed) {
                throw new StockTransferException('This request was already handled.');
            }

            $buyer->wallet = bcadd((string) $buyer->wallet, (string) $transfer->total_amount, 8);
            $buyer->save();

            $this->ledger($buyer, (string) $transfer->total_amount, '+', self::REMARK_REFUND,
                "Refund for stock request {$transfer->code} ({$status})", $settleTrx);

            return $transfer;
        });

        return $transfer->fresh(['items', 'buyer.user', 'seller.user']);
    }

    /** Locks the transfer and checks it is pending and belongs to whoever is acting. */
    private function lockPending(StockTransfer $transfer, ?int $sellerId = null, ?int $buyerId = null): StockTransfer
    {
        $locked = StockTransfer::whereKey($transfer->id)->lockForUpdate()->first();

        if (!$locked
            || ($sellerId !== null && (int) $locked->seller_stockist_id !== $sellerId)
            || ($buyerId !== null && (int) $locked->buyer_stockist_id !== $buyerId)) {
            throw new StockTransferException('This stock request is not yours to change.');
        }
        if (!$locked->isPending()) {
            throw new StockTransferException('This request is already ' . strtolower($locked->statusLabel()) . '.');
        }

        return $locked;
    }

    /**
     * A stockist's inventory rows, one per product (the oldest row, as the POS
     * and redemptions use), keyed by product_id.
     */
    private function stockRows(Stockist $stockist, ?array $productIds, bool $lock = false): Collection
    {
        return Stockist_store::where('user_id', $stockist->user_id)
            ->when($productIds !== null, fn ($q) => $q->whereIn('product_id', $productIds))
            ->when(!$lock, fn ($q) => $q->with('product'))
            ->orderBy('product_id')->orderBy('id')
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->get()
            ->unique('product_id')
            ->keyBy('product_id');
    }

    private function assertCanBuy(Stockist $buyer): void
    {
        if ($buyer->isMega()) {
            throw new StockTransferException('Mega stockists restock from the company, not from other stockists.');
        }
        if ((int) $buyer->status !== Status::ACTIVE || !$buyer->is_active) {
            throw new StockTransferException('Your stockist account is not active. Contact admin.');
        }
    }

    private function assertCanSell(Stockist $seller, Stockist $buyer): void
    {
        if (!$seller->isMega() || (int) $seller->status !== Status::ACTIVE || !$seller->is_active) {
            throw new StockTransferException('This Mega stockist is not taking requests right now.');
        }
        if ((int) $seller->state_id !== (int) $buyer->state_id) {
            throw new StockTransferException('You can only buy from a Mega stockist in your state.');
        }
    }

    private function ledger(Stockist $stockist, string $amount, string $sign, string $remark, string $details, string $trx): void
    {
        $row               = new Stransaction();
        $row->user_id      = $stockist->user_id;
        $row->amount       = $amount;
        $row->trx_type     = $sign;
        $row->remark       = $remark;
        $row->details      = mb_substr($details, 0, 255);
        $row->trx          = $trx;
        $row->post_balance = $stockist->wallet;
        $row->save();
    }

    private function money($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    // ── Notifications (sent after the money has committed) ─────────────────

    private function notifyRequested(StockTransfer $transfer): void
    {
        $this->tell($transfer->seller?->user, 'STOCK_TRANSFER_REQUESTED', $transfer,
            "New stock request {$transfer->code}",
            "{$transfer->buyer->business_name} wants {$transfer->itemsSummary()} for " . showAmount($transfer->total_amount)
            . '. The payment is held for you: confirm or decline it in Stockist → Stock Requests.');
    }

    private function notifyCompleted(StockTransfer $transfer): void
    {
        $this->tell($transfer->buyer?->user, 'STOCK_TRANSFER_COMPLETED_BUYER', $transfer,
            "Stock received: {$transfer->code}",
            "{$transfer->seller->business_name} confirmed {$transfer->code}. {$transfer->itemsSummary()} added to your inventory; "
            . showAmount($transfer->total_amount) . ' paid.',
            ['post_balance' => showAmount($transfer->buyer->wallet, currencyFormat: false)]);

        $this->tell($transfer->seller?->user, 'STOCK_TRANSFER_COMPLETED_SELLER', $transfer,
            "Stock transfer {$transfer->code} completed",
            "You transferred {$transfer->itemsSummary()} to {$transfer->buyer->business_name}. "
            . showAmount($transfer->total_amount) . ' paid to your stockist wallet, rebate ' . showAmount($transfer->rebate_amount) . '.',
            ['post_balance' => showAmount($transfer->seller->wallet, currencyFormat: false)]);

        $admin            = new AdminNotification();
        $admin->user_id   = $transfer->buyer->user_id;
        $admin->title     = sprintf('Stock transfer %s: %s → %s, %s', $transfer->code,
            $transfer->seller->business_name, $transfer->buyer->business_name, showAmount($transfer->total_amount));
        $admin->click_url = urlPath('admin.stockist.transfers.show', $transfer->id);
        $admin->save();
    }

    /**
     * Email / SMS through the notification templates, plus one entry in the
     * member's notification centre (so it shows even when email is off).
     */
    private function tell(?User $user, string $template, StockTransfer $transfer, string $subject, string $message, array $extra = []): void
    {
        if (!$user) {
            return;
        }

        $codes = array_merge([
            'code'         => $transfer->code,
            'buyer_name'   => $transfer->buyer?->user?->fullname,
            'buyer_store'  => $transfer->buyer?->business_name,
            'seller_name'  => $transfer->seller?->user?->fullname,
            'seller_store' => $transfer->seller?->business_name,
            'items'        => $transfer->itemsSummary(),
            'total'        => showAmount($transfer->total_amount, currencyFormat: false),
            'rebate'       => showAmount($transfer->rebate_amount, currencyFormat: false),
            'post_balance' => '',
            'reason'       => (string) $transfer->decision_note,
            'link'         => route('user.stockist.transfers.show', $transfer->id),
        ], $extra);

        try {
            notify($user, $template, $codes, createLog: false);
        } catch (\Throwable $e) {
            \Log::error("Stock transfer {$transfer->code}: {$template} not sent: " . $e->getMessage());
        }

        $log                    = new NotificationLog();
        $log->user_id           = $user->id;
        $log->sender            = 'system';
        $log->sent_from         = mb_substr((string) gs('site_name'), 0, 40);
        $log->sent_to           = mb_substr((string) $user->username, 0, 40);
        $log->notification_type = 'stockist';
        $log->subject           = $subject;
        $log->message           = $message;
        $log->save();
    }
}

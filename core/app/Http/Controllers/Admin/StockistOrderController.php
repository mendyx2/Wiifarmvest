<?php
// app/Http/Controllers/Admin/StockistOrderController.php

namespace App\Http\Controllers\Admin;
 
use App\Http\Controllers\Controller;
use App\Models\StockistOrder;
use App\Models\Product;
use App\Models\Stockist;
use App\Models\User;
use App\Models\StockistOrderItem;
use App\Models\Stockist_store;
use App\Models\Transaction;
use App\Models\StockistInventory;
use App\Models\Stockist_store_history;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Support\AdminPresenter;
use Inertia\Inertia;

class StockistOrderController extends Controller
{
   
    /** Manage Stockist → Stockist Orders (Pages/Admin/Stockist/Orders.tsx): stock bought from the company. */
    public function index(Request $request)
    {
        $pageTitle = 'Stockist Orders';
        $status    = in_array($request->status, self::STATUSES, true) ? $request->status : '';
        $search    = trim((string) $request->search);
        $date      = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : '';
        $from      = $date($request->date_from);
        $to        = $date($request->date_to);

        // The old filters ran on any value, so "?status=" or "?search=" with nothing in it emptied the list.
        $orders = StockistOrder::with(['stockist.user:id,username,firstname,lastname'])
            ->withCount('items')->withSum('items as units', 'quantity')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('order_number', 'like', "%{$search}%")
                ->orWhereHas('stockist', fn ($s) => $s->where('business_name', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('username', 'like', "%{$search}%")))))
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (StockistOrder $o) => [
                'id'       => $o->id,
                'number'   => (string) $o->order_number,
                'stockist' => ['name' => $o->stockist?->business_name ?? '—', 'owner' => $o->stockist?->user?->fullname, 'url' => $o->stockist ? route('admin.stockist.details', $o->stockist_id) : null],
                'date'     => AdminPresenter::when($o->created_at),
                'products' => (int) $o->items_count,
                'units'    => (int) $o->units,
                'total'    => showAmount($o->grand_total),
                'status'   => $this->badge($o),
                'next'     => $this->nextAction($o),
                'url'      => route('admin.stockist-orders.show', $o->id),
            ]);

        $counts = StockistOrder::selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return Inertia::render('Admin/Stockist/Orders', [
            'orders'  => $orders,
            'counts'  => collect(self::STATUSES)->mapWithKeys(fn ($st) => [$st => (int) ($counts[$st] ?? 0)])->all() + ['total' => (int) $counts->sum()],
            'filters' => ['status' => $status, 'search' => $search, 'date_from' => $from, 'date_to' => $to],
            'urls'    => ['action' => route('admin.stockist-orders.approve', ':id')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** One stock order (Pages/Admin/Stockist/OrderDetail.tsx): items, totals, progress and the next step. */
    public function show(StockistOrder $order)
    {
        $order->load(['stockist.user', 'items.product']);
        $title = 'Stockist Order ' . $order->order_number;
        $at    = fn ($d) => $d ? showDateTime($d, 'd M Y, h:i A') : null;

        return Inertia::render('Admin/Stockist/OrderDetail', [
            'title' => $title,
            'order' => [
                'id'       => $order->id,
                'number'   => (string) $order->order_number,
                'status'   => (string) $order->status,
                'badge'    => $this->badge($order),
                'notes'    => $order->notes,
                'subtotal' => showAmount($order->total_amount),
                'shipping' => showAmount($order->shipping_cost),
                'tax'      => showAmount($order->tax_amount),
                'total'    => showAmount($order->grand_total),
                'units'    => (int) $order->items->sum('quantity'),
                'steps'    => [
                    ['key' => 'pending', 'label' => 'Order placed', 'at' => $at($order->created_at)],
                    ['key' => 'approved', 'label' => 'Approved', 'at' => $at($order->approved_at)],
                    ['key' => 'processing', 'label' => 'Processing', 'at' => null],
                    ['key' => 'shipped', 'label' => 'Shipped', 'at' => $at($order->shipped_at)],
                    ['key' => 'delivered', 'label' => 'Delivered', 'at' => $at($order->delivered_at)],
                ],
            ],
            'stockist' => $order->stockist ? [
                'name'   => (string) $order->stockist->business_name,
                'owner'  => AdminPresenter::user($order->stockist->user),
                'type'   => $order->stockist->store_type_name,
                'wallet' => showAmount($order->stockist->wallet),
                'url'    => route('admin.stockist.details', $order->stockist_id),
            ] : null,
            'items' => $order->items->map(fn (StockistOrderItem $item) => [
                'id'       => $item->id,
                'name'     => $item->product?->name ?? 'Product #' . $item->product_id,
                'sku'      => $item->product?->sku,
                'image'    => $item->product?->thumbnail ? getImage(getFilePath('products') . '/' . $item->product->thumbnail, getFileSize('products')) : null,
                'quantity' => (int) $item->quantity,
                'unit'     => showAmount($item->unit_price),
                'total'    => showAmount($item->total_price),
            ])->values(),
            'actions' => [
                'approve' => $order->canBeApproved() ? route('admin.stockist-orders.approve', $order->id) : null,
                'process' => $order->status === 'approved' ? route('admin.stockist-orders.processOrder', $order->id) : null,
                'ship'    => $order->canBeShipped() ? route('admin.stockist-orders.ship', $order->id) : null,
                'deliver' => $order->canBeDelivered() ? route('admin.stockist-orders.deliver', $order->id) : null,
                'cancel'  => $order->canBeCancelled() ? route('admin.stockist-orders.cancel', $order->id) : null,
            ],
            'urls' => ['index' => route('admin.stockist-orders.index')],
        ])->withViewData(['pageTitle' => $title]);
    }

    private const STATUSES = ['pending', 'approved', 'processing', 'shipped', 'delivered', 'cancelled'];

    private function badge(StockistOrder $order): array
    {
        return AdminPresenter::badge(ucfirst((string) $order->status), 'badge--' . $order->getStatusBadgeClass());
    }

    /** The one button the list shows for an order, if any. */
    private function nextAction(StockistOrder $order): ?array
    {
        return match (true) {
            $order->canBeApproved()             => ['label' => 'Approve', 'url' => route('admin.stockist-orders.approve', $order->id)],
            $order->status === 'shipped'        => ['label' => 'Mark Delivered', 'url' => route('admin.stockist-orders.deliver', $order->id)],
            $order->canBeShipped()              => ['label' => 'Ship', 'url' => route('admin.stockist-orders.ship', $order->id)],
            default                             => null,
        };
    }

    /**
     * Move an order on, with the order row locked and its status checked
     * again inside the transaction. The old actions checked the status
     * before the transaction without a lock, so two clicks on Deliver both
     * passed: the stock was added to the stockist twice and the sponsor
     * bonus paid twice.
     */
    private function transition(StockistOrder $order, callable $allowed, callable $apply, string $refused, string $done)
    {
        try {
            $ok = DB::transaction(function () use ($order, $allowed, $apply) {
                $locked = StockistOrder::with(['items.product', 'stockist.user'])->lockForUpdate()->find($order->id);
                if (!$locked || !$allowed($locked)) {
                    return false;
                }
                $apply($locked);
                return true;
            });
        } catch (\Throwable $e) {
            // The exception text used to be shown in the toast.
            report($e);
            $notify[] = ['error', 'The order could not be updated. Nothing was changed; please try again.'];
            return back()->withNotify($notify);
        }

        $notify[] = $ok ? ['success', $done] : ['error', $refused];
        return back()->withNotify($notify);
    }

    public function approveOrder(StockistOrder $order)
    {
        return $this->transition($order, fn ($o) => $o->canBeApproved(), fn ($o) => $o->markAsApproved(),
            'Order cannot be approved in its current status.', 'Order approved successfully!');
    }

    public function processOrder(StockistOrder $order)
    {
        return $this->transition($order, fn ($o) => $o->status === 'approved', fn ($o) => $o->update(['status' => 'processing']),
            'Order can only be processed from approved status.', 'Order marked as processing!');
    }

    public function shipOrder(StockistOrder $order)
    {
        return $this->transition($order, fn ($o) => $o->canBeShipped(), fn ($o) => $o->markAsShipped(),
            'Order cannot be shipped in its current status.', 'Order marked as shipped!');
    }

    public function deliverOrder(StockistOrder $order)
    {
        return $this->transition($order, fn ($o) => $o->canBeDelivered(), function ($o) {
            $o->markAsDelivered();
            foreach ($o->items as $item) {
                $this->updateStockistInventory($o->id, $o->stockist->user, $item);
            }
        }, 'Order cannot be delivered in its current status.', 'Order delivered and inventory updated successfully!');
    }

    public function cancelOrder(Request $request, StockistOrder $order)
    {
        $result = DB::transaction(function () use ($order) {
            // Locked and re-checked, so two clicks can't refund twice.
            $locked = StockistOrder::with(['items', 'stockist'])->lockForUpdate()->find($order->id);
            if (!$locked || !$locked->canBeCancelled()) {
                return false;
            }

            $locked->stockist->addToWallet($locked->grand_total);

            // Placing the order took this stock from the company; it has not
            // reached the stockist yet (that happens on delivery), so give it back.
            foreach ($locked->items as $item) {
                Product::whereKey($item->product_id)->increment('quantity', $item->quantity);
            }

            $locked->update(['status' => 'cancelled']);

            return true;
        });

        $notify[] = $result
            ? ['success', 'Order cancelled, amount refunded and stock returned.']
            : ['error', 'Order cannot be cancelled in its current status.'];
        return back()->withNotify($notify);
    }


    private function updateStockistInventory($orderId, User $user, StockistOrderItem $item)
    {  

        //$order->stockist->user_id
        $quantity = $item->quantity;
        
        $product_sku = (int)$item->product->sku;
        $productId = $item->product->id;
        $userId = $user->id;

        $stockistStore = Stockist_store::where('user_id', $userId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        $stockist = Stockist::where('user_id', $userId)->first();
        $stockist_id = $stockist->id;
        $stockist_store_type = (int)$stockist->store_type; 
             
        //StockistInventory;
        if ($stockistStore) {
            // Update existing stock 
            $stockistStore->incrementStock($quantity);
            //$stockistStore->incrementStockInventory($quantity);
        } else {
            //get the user stockist ID
           
            //$stockist_id = getStockistId($userId);
            
            // Create new stock entry
            $stockistStore = new Stockist_store();
           
            $stockistStore->user_id = $userId;
            $stockistStore->stockist_id = $stockist_id;
            $stockistStore->product_id = $productId;
            $stockistStore->quantity = $quantity;
            $stockistStore->is_active = true;
            $stockistStore->status = 1;
            $stockistStore->save();
        }

        // record the update 

        //Store in history for proper tracking
        $stockist_store_history =  new Stockist_store_history();
        $stockist_store_history->user_id = $userId;
        $stockist_store_history->stockist_store_id = $stockistStore->id;
        $stockist_store_history->quantity = $quantity;
        $stockist_store_history->admin_id = auth('admin')->id();
        $stockist_store_history->save();
        
        if($stockist_store_type == 1){
            if ($product_sku == 1) {
                // 40 naira per product
                $amount = $quantity * 40;
                if ($user->ref_by) {
    
                    $user_ref = User::lockForUpdate()->find($user->ref_by);
                    if (!$user_ref) {
                        return;
                    }
                    $user_ref->balance +=  $amount;
                    $user_ref->save();
                    //dd($user_ref);
    
                    $transaction               = new Transaction();
                    $transaction->amount       = $amount;
                    $transaction->user_id      = $user_ref->id;
                    $transaction->charge       = 0;
                    $transaction->trx_type     = '+';
                    $transaction->details      = 'Stockist purchase bonus received from '.$user->username.' purchasing '.$quantity.' QTY of '.$item->product->name;
                    $transaction->remark       = 'Stockist_purchase_commission';
                    $transaction->trx          = getTrx();
                    $transaction->post_balance = $user_ref->balance;
                    $transaction->save();
    
    
                }
    
            }
        } //end $stockist->store_type == 1
        //dd($item->product->name);

    }

    


}
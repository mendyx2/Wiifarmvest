<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Constants\Status;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminPresenter;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Shop → Orders (Pages/Admin/Shop/Orders.tsx): every member order with
     * its total, redemption and status. It replaces two pages that listed
     * the same orders: "Manage Order" (ship / cancel) and "Manage Sales"
     * (redemption), which only ever showed its first 10 rows.
     */
    public function index(Request $request, ?int $userId = null)
    {
        $pageTitle = 'Orders';
        $search    = trim((string) $request->query('search', ''));
        $status    = (string) $request->query('status', '');
        $redeemed  = (string) $request->query('redeemed', '');

        $orders = Order::query()
            ->with(['user:id,username,firstname,lastname', 'state:id,name', 'invoice:id,order_id,invoice_code,total_amount,items,redeemed_at', 'invoice.redemptions:id,invoice_id,redeemed_items'])
            ->withCount('items')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('invoice_code', 'like', "%{$search}%")
                ->orWhere('trx', 'like', "%{$search}%")
                ->orWhereHas('invoice', fn ($i) => $i->where('invoice_code', 'like', "%{$search}%"))
                ->orWhereHas('user', fn ($u) => $u->where('username', 'like', "%{$search}%")->orWhere('firstname', 'like', "%{$search}%")->orWhere('lastname', 'like', "%{$search}%"))))
            ->when($status === 'pending', fn ($q) => $q->where('status', Status::ORDER_PENDING))
            ->when($status === 'shipped', fn ($q) => $q->where('status', Status::ORDER_SHIPPED))
            ->when($status === 'cancelled', fn ($q) => $q->where('status', Status::ORDER_CANCELED))
            ->when($redeemed === 'full', fn ($q) => $q->whereHas('invoice', fn ($i) => $i->whereNotNull('redeemed_at')))
            ->when($redeemed === 'partial', fn ($q) => $q->whereHas('invoice', fn ($i) => $i->whereNull('redeemed_at')->has('redemptions')))
            ->when($redeemed === 'none', fn ($q) => $q->whereDoesntHave('invoice.redemptions'))
            ->orderByDesc('id')
            ->paginate(getPaginate())
            ->withQueryString()
            ->through(fn (Order $order) => [
                'id'       => $order->id,
                'user'     => AdminPresenter::user($order->user),
                'invoice'  => (string) ($order->invoice?->invoice_code ?? $order->invoice_code),
                'state'    => $order->state?->name,
                'items'    => (int) $order->items_count,
                'total'    => showAmount($this->total($order)),
                'date'     => AdminPresenter::when($order->created_at),
                'status'   => $this->statusBadge($order),
                'redeemed' => $this->redemption($order),
                'url'      => route('admin.order.show', $order->id),
            ]);

        // Today's figures, whatever the filter.
        $today = Order::whereDate('created_at', today());

        return Inertia::render('Admin/Shop/Orders', [
            'title'   => $userId ? 'Orders of ' . (User::find($userId)?->username ?? '#' . $userId) : $pageTitle,
            'orders'  => $orders,
            'stats'   => [
                'orders'   => (clone $today)->count(),
                'pending'  => (clone $today)->where('status', Status::ORDER_PENDING)->count(),
                'redeemed' => (clone $today)->whereHas('invoice', fn ($i) => $i->whereNotNull('redeemed_at'))->count(),
                'revenue'  => showAmount((clone $today)->where('status', '!=', Status::ORDER_CANCELED)->with('invoice:id,order_id,total_amount')->get()->sum(fn ($o) => $this->total($o))),
            ],
            'filters' => ['search' => $search, 'status' => $status, 'redeemed' => $redeemed],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** One order (Pages/Admin/Shop/OrderDetail.tsx): its products, what was collected where, and ship / cancel. */
    public function show(int $id)
    {
        $order = Order::with(['user', 'state', 'items.product', 'invoice.redemptions.stockist.user'])->findOrFail($id);
        $invoice = $order->invoice;

        // product_id => quantity collected so far
        $collected = [];
        foreach ($invoice?->redemptions ?? [] as $redemption) {
            foreach ((array) $redemption->redeemed_items as $item) {
                if ($pid = $item['product_id'] ?? null) {
                    $collected[$pid] = ($collected[$pid] ?? 0) + (int) ($item['quantity'] ?? 0);
                }
            }
        }
        $names = $order->items->mapWithKeys(fn ($i) => [$i->product_id => $i->product?->name]);
        $image = fn ($product) => $product?->thumbnail ? getImage(getFilePath('products') . '/' . $product->thumbnail, getFileSize('products')) : null;
        $pending = (int) $order->status === Status::ORDER_PENDING;
        $title = 'Order ' . ($invoice?->invoice_code ?? $order->invoice_code);

        return Inertia::render('Admin/Shop/OrderDetail', [
            'title'  => $title,
            'order'  => [
                'id'       => $order->id,
                'invoice'  => (string) ($invoice?->invoice_code ?? $order->invoice_code),
                'trx'      => (string) $order->trx,
                'user'     => AdminPresenter::user($order->user),
                'state'    => $order->state?->name,
                'total'    => showAmount($this->total($order)),
                'date'     => AdminPresenter::when($order->created_at),
                'status'   => $this->statusBadge($order),
                'redeemed' => $this->redemption($order),
            ],
            'items' => $order->items->map(function ($item) use ($collected, $image) {
                $got = $collected[$item->product_id] ?? 0;
                return [
                    'id'        => $item->id,
                    'name'      => $item->product?->name ?? 'Deleted product',
                    'sku'       => $item->product?->sku,
                    'image'     => $image($item->product),
                    'quantity'  => (int) $item->quantity,
                    'collected' => min($got, (int) $item->quantity),
                    'price'     => showAmount($item->price),
                    'subtotal'  => showAmount($item->price * $item->quantity),
                ];
            })->values(),
            'redemptions' => ($invoice?->redemptions ?? collect())->map(fn ($r) => [
                'id'       => $r->id,
                'stockist' => $r->stockist?->business_name ?? 'Unknown stockist',
                'username' => $r->stockist?->user?->username,
                'amount'   => showAmount($r->total_amount),
                'date'     => AdminPresenter::when($r->redeemed_at ?? $r->created_at),
                'notes'    => $r->notes,
                'items'    => collect((array) $r->redeemed_items)->map(fn ($i) => [
                    'name'     => $names[$i['product_id'] ?? 0] ?? ($i['product_name'] ?? 'Product #' . ($i['product_id'] ?? '?')),
                    'quantity' => (int) ($i['quantity'] ?? 0),
                ])->values(),
            ])->values(),
            // Ship or cancel only while pending; cancelling also needs nothing collected yet.
            'actions' => [
                'ship'   => $pending,
                'cancel' => $pending && ($invoice?->redemptions->isEmpty() ?? true),
                'url'    => route('admin.order.status', $order->id),
            ],
            'urls' => ['index' => route('admin.order.index')],
        ])->withViewData(['pageTitle' => $title]);
    }

    /** Cart orders keep their total on the invoice; orders.total_amount stays empty for them. */
    private function total(Order $order): float
    {
        return (float) ($order->invoice?->total_amount ?? $order->total_amount);
    }

    private function statusBadge(Order $order): array
    {
        return match ((int) $order->status) {
            Status::ORDER_PENDING  => AdminPresenter::badge('Pending', 'badge--warning'),
            Status::ORDER_CANCELED => AdminPresenter::badge('Cancelled', 'badge--danger'),
            // Full collection at a stockist also stores 1 (ORDER_COLLECTED).
            default                => $order->invoice?->redeemed_at ? AdminPresenter::badge('Collected', 'badge--success') : AdminPresenter::badge('Shipped', 'badge--success'),
        };
    }

    /** How much of the order was collected at stockists. */
    private function redemption(Order $order): array
    {
        $invoice = $order->invoice;
        if ($invoice?->redeemed_at) {
            return AdminPresenter::badge('Full', 'badge--success');
        }
        if ($invoice && $invoice->redemptions->isNotEmpty()) {
            return AdminPresenter::badge('Partial', 'badge--warning');
        }

        return AdminPresenter::badge('None', 'badge--dark');
    }

    public function status(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:1,2',
        ]);
        $notPending = Order::whereKey($id)->where('status', '!=', Status::ORDER_PENDING)->exists();
        if ($notPending) {
            $notify[] = ['info', 'This order is no longer pending; nothing was changed.'];
            return back()->withNotify($notify);
        }

        $result = DB::transaction(function () use ($request, $id) {
            // Locked so two clicks can't both refund the same order.
            $order = Order::where('status', Status::ORDER_PENDING)
                ->with(['items.product', 'user', 'invoice.redemptions'])
                ->lockForUpdate()
                ->findOrFail($id);

            // Locked too: two orders of one member cancelled at once overwrote each other's refund.
            $user = User::lockForUpdate()->findOrFail($order->user_id);
            // Cart orders keep their total on the invoice; orders.total_amount
            // stays empty for them, so the old code refunded ₦0.
            $totalAmount = (float) ($order->invoice?->total_amount ?? $order->total_amount);
            $itemCount   = $order->items->count();

            if ($request->status == Status::ORDER_SHIPPED) {
                $order->status = Status::ORDER_SHIPPED;
                $order->save();

                return ['ORDER_SHIPPED', $order, $user, $itemCount, $totalAmount];
            }

            // Goods already handed over by a stockist can't be refunded.
            if ($order->invoice && $order->invoice->redemptions->isNotEmpty()) {
                return 'This order has already been (partly) collected from a stockist and cannot be cancelled.';
            }

            $order->status = Status::ORDER_CANCELED;
            $order->save();

            $user->product_wallet += $totalAmount;
            $user->save();

            $transaction               = new Transaction();
            $transaction->user_id      = $order->user_id;
            $transaction->amount       = $totalAmount;
            $transaction->post_balance = $user->product_wallet;
            $transaction->charge       = 0;
            $transaction->trx_type     = '+';
            $transaction->details      = "Order {$order->invoice_code} cancelled — {$itemCount} item(s) refunded";
            $transaction->remark       = 'order_cancel_refund';
            $transaction->trx          = $order->trx;
            $transaction->save();

            // No stock is put back: checkout never takes company stock (stock
            // leaves a stockist's store only when the invoice is redeemed).

            return ['ORDER_CANCELED', $order, $user, $itemCount, $totalAmount];
        });

        if (is_string($result)) {
            $notify[] = ['error', $result];
            return back()->withNotify($notify);
        }

        [$template, $order, $user, $itemCount, $totalAmount] = $result;
        notify($user, $template, [
            'invoice_code' => $order->invoice_code,
            'item_count'   => $itemCount,
            'total_price'  => showAmount($totalAmount, currencyFormat: false),
            'trx'          => $order->trx,
        ]);

        $notify[] = ['success', 'Order status updated successfully'];
        return back()->withNotify($notify);
    }
}

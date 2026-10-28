<?php

namespace App\Http\Controllers\User;

use App\Models\Product;
use App\Models\ProductStatePrice;
use App\Models\Stockist;
use App\Models\StockistOrder;
use App\Models\StockistOrderItem;
use App\Support\ShopPresenter;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * A stockist's own stock, and (Mega stockists only) ordering stock from the
 * company. The order cart lives in the session under "stockist_cart" as
 * product_id => quantity; prices are always read fresh from the state price.
 */
class StockistInventoryController extends Controller
{
    /** Below this many units an item counts as "low stock" (same rule as Stockist::low_stock_items). */
    public const LOW_STOCK = 20;

    public const ORDER_STATUSES = ['pending', 'approved', 'processing', 'shipped', 'delivered', 'cancelled'];

    public function dashboard()
    {
        $pageTitle = "Stockist Inventory";
        $stockist  = auth()->user()->stockist;
        $inventory = $stockist->inventory()->with('product')->get()->filter(fn ($item) => $item->product);

        // The old page read stock_status / min / max fields the store rows don't
        // have, so every item showed "In Stock", even at 0.
        $status = fn ($qty) => $qty <= 0 ? 'out_of_stock' : ($qty < self::LOW_STOCK ? 'low_stock' : 'in_stock');
        $mega   = $stockist->isMega();

        return Inertia::render('User/Stockist/Inventory/Dashboard', [
            'canOrderFromCompany' => $mega,
            'stats' => [
                'products'   => $inventory->count(),
                'low'        => $inventory->filter(fn ($i) => $i->quantity > 0 && $i->quantity < self::LOW_STOCK)->count(),
                'out'        => $inventory->filter(fn ($i) => $i->quantity <= 0)->count(),
                'wallet'     => showAmount($stockist->wallet),
            ],
            'items' => $inventory->map(fn ($item) => [
                'id'       => $item->id,
                'name'     => (string) $item->product->name,
                'sku'      => $item->product->sku,
                'image'    => $item->product->thumbnail ? ShopPresenter::image($item->product->thumbnail) : null,
                'quantity' => (int) $item->quantity,
                'status'   => $status((int) $item->quantity),
            ])->values(),
            'lowStock'  => self::LOW_STOCK,
            'cartCount' => count($this->cart()),
            'urls' => [
                'restock'  => $mega ? route('user.stockist.inventory.catalog') : route('user.stockist.transfers.create'),
                'orders'   => $mega ? route('user.stockist.inventory.orders') : route('user.stockist.transfers.index'),
                'cart'     => $mega ? route('user.stockist.inventory.checkout') : null,
                'reports'  => route('user.stockist.redemptions'),
            ],
        ])->withViewData(compact('pageTitle'));
    }

    public function productsCatalog()
    {
        $pageTitle = "Stockist catalog";
        $stockist  = auth()->user()->stockist;

        // Only products that can actually be ordered. The old page loaded 12
        // and then hid inactive / sold-out ones, leaving short or empty pages.
        $prices = ProductStatePrice::with('product')
            ->where('state_id', $stockist->state_id)
            // One row per product: if the admin entered two prices for the same
            // state, use the first, like the member shop does.
            ->whereIn('id', ProductStatePrice::selectRaw('MIN(id)')->where('state_id', $stockist->state_id)->groupBy('product_id'))
            ->whereHas('product', fn ($q) => $q->where('status', 1)->where('quantity', '>', 0))
            ->paginate(12)
            ->withQueryString();

        $cart = $this->cart();

        return Inertia::render('User/Stockist/Inventory/Catalog', [
            'products' => $prices->through(function ($price) use ($cart) {
                $product = $price->product;
                [$min, $max] = $this->limits($product);

                return [
                    'id'          => $product->id,
                    'name'        => (string) $product->name,
                    'sku'         => $product->sku,
                    'image'       => $product->thumbnail ? ShopPresenter::image($product->thumbnail) : null,
                    'description' => \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 80),
                    'price'       => ShopPresenter::price($price->price),
                    'stock'       => (int) $product->quantity,
                    'min'         => $min,
                    'max'         => $max,
                    'inCart'      => (int) ($cart[$product->id] ?? 0),
                ];
            }),
            'wallet'    => showAmount($stockist->wallet),
            'cartCount' => count($cart),
            'urls'      => [
                'add'       => route('user.stockist.inventory.cart.add'),
                'checkout'  => route('user.stockist.inventory.checkout'),
                'dashboard' => route('user.stockist.inventory.dashboard'),
            ],
        ])->withViewData(compact('pageTitle'));
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::find($request->product_id);
        $price   = $product ? $this->statePrice($product) : null;
        if (!$product || !$product->is_available || !$price) {
            return back()->withErrors(['cart' => 'This product is not available for ordering.']);
        }

        // Check the quantity already in the cart plus this one; before, adding
        // the same product twice could pass the maximum and the stock.
        $cart  = $this->cart();
        $total = (int) ($cart[$product->id] ?? 0) + (int) $request->quantity;
        if ($error = $this->quantityError($product, $total)) {
            return back()->withErrors(['cart' => $error]);
        }

        $cart[$product->id] = $total;
        session()->put('stockist_cart', $cart);

        // The page shows its own "Added to Cart" dialog, so no toast here.
        return back();
    }

    public function updateCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity'   => 'required|integer|min:0',
        ]);

        $cart = $this->cart();
        if ((int) $request->quantity === 0) {
            unset($cart[$request->product_id]);
        } else {
            $product = Product::find($request->product_id);
            if (!$product || !isset($cart[$product->id])) {
                return back();
            }
            if ($error = $this->quantityError($product, (int) $request->quantity)) {
                return back()->withErrors(['cart' => $error]);
            }
            $cart[$product->id] = (int) $request->quantity;
        }
        session()->put('stockist_cart', $cart);

        return back();
    }

    public function checkout()
    {
        $pageTitle = "Stockist Cart";
        $stockist  = auth()->user()->stockist;
        $lines     = $this->cartLines();
        $total     = array_sum(array_column($lines, 'total'));
        $wallet    = (float) $stockist->wallet;

        return Inertia::render('User/Stockist/Inventory/Checkout', [
            'lines' => array_map(fn ($line) => [
                'productId' => $line['product']->id,
                'name'      => (string) $line['product']->name,
                'sku'       => $line['product']->sku,
                'image'     => $line['product']->thumbnail ? ShopPresenter::image($line['product']->thumbnail) : null,
                'price'     => showAmount($line['price']),
                'quantity'  => $line['quantity'],
                'total'     => showAmount($line['total']),
                'min'       => $this->limits($line['product'])[0],
                'max'       => $this->limits($line['product'])[1],
                'problem'   => $line['problem'],
            ], $lines),
            'total'     => showAmount($total),
            'wallet'    => showAmount($wallet),
            'shortfall' => $wallet < $total ? showAmount($total - $wallet) : null,
            'canPlace'  => $lines && $wallet >= $total && !array_filter(array_column($lines, 'problem')),
            'urls'      => [
                'update'  => route('user.stockist.inventory.cart.update'),
                'place'   => route('user.stockist.inventory.order.place'),
                'catalog' => route('user.stockist.inventory.catalog'),
            ],
        ])->withViewData(compact('pageTitle'));
    }

    public function placeOrder(Request $request)
    {
        $request->validate(['notes' => 'nullable|string|max:1000']);
        $stockist = auth()->user()->stockist;

        // One order at a time per stockist: a double click (or two tabs) used
        // to place the same cart twice.
        $lock = Cache::lock('stockist-place-order:' . $stockist->id, 30);
        if (!$lock->get()) {
            $notify[] = ['error', 'Your order is already being placed. Please wait.'];
            return back()->withNotify($notify);
        }

        try {
            $order = DB::transaction(function () use ($request, $stockist) {
                $cart = $this->cart();
                if (!$cart) {
                    throw new \DomainException('Your cart is empty.');
                }

                $totalAmount = 0;
                $items       = [];
                // Products are locked, so two stockists can't both take the last units.
                $products = Product::whereIn('id', array_keys($cart))->lockForUpdate()->get()->keyBy('id');

                foreach ($cart as $productId => $quantity) {
                    $product = $products->get($productId);
                    $price   = $product ? $this->statePrice($product) : null;
                    if (!$product || !$product->is_available || !$price) {
                        throw new \DomainException('A product in your cart is no longer available.');
                    }
                    if ($error = $this->quantityError($product, (int) $quantity)) {
                        throw new \DomainException($error);
                    }

                    // Today's price for your state, not the price when it was added.
                    $lineTotal    = $price->price * $quantity;
                    $totalAmount += $lineTotal;
                    $items[]      = ['product_id' => $product->id, 'quantity' => (int) $quantity, 'unit_price' => $price->price, 'total_price' => $lineTotal];
                }

                // Atomic: the balance check and the debit are one SQL statement.
                if (!$stockist->deductFromWallet($totalAmount)) {
                    throw new \DomainException('Insufficient wallet balance.');
                }

                $order = StockistOrder::create([
                    'stockist_id'   => $stockist->id,
                    'total_amount'  => $totalAmount,
                    'shipping_cost' => 0,
                    'tax_amount'    => 0,
                    'grand_total'   => $totalAmount,
                    'status'        => 'pending',
                    'notes'         => $request->notes,
                ]);

                foreach ($items as $item) {
                    StockistOrderItem::create($item + ['stockist_order_id' => $order->id]);
                    // Reserve the company's stock; it reaches the stockist on delivery.
                    $products->get($item['product_id'])->decrement('quantity', $item['quantity']);
                }

                return $order;
            });
        } catch (\DomainException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        } catch (\Throwable $e) {
            report($e);
            $notify[] = ['error', 'The order could not be placed. Please try again.'];
            return back()->withNotify($notify);
        } finally {
            $lock->release();
        }

        session()->forget('stockist_cart');

        $notify[] = ['success', "Order {$order->order_number} placed successfully!"];
        return to_route('user.stockist.inventory.order.details', $order->id)->withNotify($notify);
    }

    public function orderHistory()
    {
        $pageTitle = "Stockist Orders";
        $stockist  = auth()->user()->stockist;
        $mega      = $stockist->isMega();
        if (!$mega) {
            $pageTitle = "Earlier Company Orders";
        }

        $orders = $stockist->orders()->with('items')->latest()->paginate(10);
        // Counters cover every order, not just this page.
        $counts = $stockist->orders()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return Inertia::render('User/Stockist/Inventory/Orders', [
            'canOrderFromCompany' => $mega,
            'stats' => [
                'total'     => (int) $counts->sum(),
                'pending'   => (int) ($counts['pending'] ?? 0),
                'approved'  => (int) ($counts['approved'] ?? 0),
                'delivered' => (int) ($counts['delivered'] ?? 0),
            ],
            'orders' => $orders->through(fn ($order) => [
                'id'     => $order->id,
                'number' => $order->order_number,
                'date'   => showDateTime($order->created_at, 'M j, Y'),
                'time'   => showDateTime($order->created_at, 'g:i A'),
                'items'  => $order->items->count(),
                'units'  => (int) $order->items->sum('quantity'),
                'total'  => showAmount($order->grand_total),
                'status' => (string) $order->status,
                'url'    => route('user.stockist.inventory.order.details', $order->id),
            ]),
            'urls' => [
                'catalog'   => route('user.stockist.inventory.catalog'),
                'transfers' => route('user.stockist.transfers.index'),
                'buy'       => route('user.stockist.transfers.create'),
            ],
        ])->withViewData(compact('pageTitle'));
    }

    public function orderDetails(StockistOrder $order)
    {
        $pageTitle = "Stockist Order details";
        abort_unless((int) $order->stockist_id === (int) auth()->user()->stockist->id, 403);

        $order->load('items.product');
        $when = fn ($date) => $date ? showDateTime($date, 'M j, Y \a\t g:i A') : null;

        return Inertia::render('User/Stockist/Inventory/OrderDetails', [
            'order' => [
                'number'    => $order->order_number,
                'status'    => (string) $order->status,
                'placed'    => $when($order->created_at),
                'approved'  => $when($order->approved_at),
                'shipped'   => $when($order->shipped_at),
                'delivered' => $when($order->delivered_at),
                'subtotal'  => showAmount($order->total_amount),
                'shipping'  => showAmount($order->shipping_cost),
                'total'     => showAmount($order->grand_total),
                'units'     => (int) $order->items->sum('quantity'),
                'notes'     => $order->notes,
                'items'     => $order->items->map(fn ($item) => [
                    'id'       => $item->id,
                    'name'     => (string) @$item->product->name,
                    'sku'      => @$item->product->sku,
                    'image'    => @$item->product->thumbnail ? ShopPresenter::image($item->product->thumbnail) : null,
                    'price'    => showAmount($item->unit_price),
                    'quantity' => (int) $item->quantity,
                    'total'    => showAmount($item->total_price),
                ])->values(),
            ],
            'ordersUrl' => route('user.stockist.inventory.orders'),
        ])->withViewData(compact('pageTitle'));
    }

    /** The session cart as product_id => quantity (older sessions stored whole rows). */
    private function cart(): array
    {
        $cart = [];
        foreach ((array) session()->get('stockist_cart', []) as $productId => $row) {
            $quantity = is_array($row) ? (int) ($row['quantity'] ?? 0) : (int) $row;
            if ($quantity > 0) {
                $cart[(int) $productId] = $quantity;
            }
        }
        return $cart;
    }

    /** Cart lines with today's price and any reason the line can't be ordered. */
    private function cartLines(): array
    {
        $cart     = $this->cart();
        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');
        $lines    = [];

        foreach ($cart as $productId => $quantity) {
            $product = $products->get($productId);
            if (!$product) {
                continue;
            }
            $price   = $this->statePrice($product);
            $lines[] = [
                'product'  => $product,
                'quantity' => $quantity,
                'price'    => (float) ($price?->price ?? 0),
                'total'    => (float) ($price?->price ?? 0) * $quantity,
                'problem'  => !$product->is_available || !$price ? 'No longer available' : $this->quantityError($product, $quantity),
            ];
        }

        return $lines;
    }

    private function statePrice(Product $product): ?ProductStatePrice
    {
        return ProductStatePrice::where('state_id', auth()->user()->stockist->state_id)->where('product_id', $product->id)->orderBy('id')->first();
    }

    /** [min, max] order quantity: max is capped by the stock (0 = no product maximum). */
    private function limits(Product $product): array
    {
        $min = max(1, (int) $product->min_order_quantity);
        $max = (int) $product->max_order_quantity > 0 ? min((int) $product->quantity, (int) $product->max_order_quantity) : (int) $product->quantity;
        return [$min, max(0, $max)];
    }

    private function quantityError(Product $product, int $quantity): ?string
    {
        [$min, $max] = $this->limits($product);
        if ($quantity < $min) {
            return "Minimum order quantity for {$product->name} is {$min}.";
        }
        if ($quantity > $max) {
            return "You can order at most {$max} of {$product->name}.";
        }
        return null;
    }
}

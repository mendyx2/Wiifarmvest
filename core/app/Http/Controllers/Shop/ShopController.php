<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\AffiliateOrder;
use App\Models\Product;
use App\Services\ShopCartService;
use App\Support\PublicShopPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * The public shop (Pages/Shop/*.tsx, inside Layouts/PublicLayout.tsx).
 * Guests buy here through affiliate links; see TrackAffiliateClick.
 */
class ShopController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::active()->hasCategory()->with('category');

        if ($request->filled('category')) {
            $products->where('category_id', $request->integer('category'));
        }

        if ($request->filled('q')) {
            $products->where('name', 'like', '%' . $request->string('q') . '%');
        }

        $products = $products->latest()->paginate(getPaginate(16))->withQueryString()
            ->through(fn (Product $product) => PublicShopPresenter::card($product));

        return Inertia::render('Shop/Index', [
            'products' => $products,
            'search'   => (string) $request->query('q', ''),
            'urls'     => ['shop' => route('shop.index')],
        ])->withViewData(['pageTitle' => 'Shop']);
    }

    public function show(Product $product)
    {
        abort_unless($product->status, 404);

        $product->loadMissing('category');
        $related = Product::active()->hasCategory()->with('category')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->latest()->limit(8)->get();

        $description = strLimit(trim(strip_tags((string) $product->description)), 320);
        $image       = PublicShopPresenter::image($product);

        return Inertia::render('Shop/Product', [
            'product' => PublicShopPresenter::card($product) + [
                'description' => $description,
                'stock'       => max(0, (int) $product->quantity),
                'maxQuantity' => min(ShopCartService::MAX_QUANTITY, max(1, (int) $product->quantity)),
            ],
            'related' => $related->map(fn (Product $p) => PublicShopPresenter::card($p))->values(),
            'urls'    => ['shop' => route('shop.index')],
        ])->withViewData([
            'pageTitle' => $product->name,
            // Link previews (WhatsApp, Facebook) read these from the first HTML response.
            'seoContents' => [
                'description'        => $description ?: null,
                'social_title'       => $product->name,
                'social_description' => $description ?: null,
            ],
            'seoImage' => $image,
        ]);
    }

    public function cart(ShopCartService $cart)
    {
        $resolved = $cart->resolve();

        return Inertia::render('Shop/Cart', [
            'lines'    => collect($resolved['lines'])->map(fn (array $line) => PublicShopPresenter::cartLine($line))->values(),
            'subtotal' => showAmount($resolved['subtotal']),
            'currency' => (string) gs('cur_text'),
            'urls'     => [
                'shop'     => route('shop.index'),
                'checkout' => route('shop.checkout'),
                'update'   => route('shop.cart.update'),
                'remove'   => route('shop.cart.remove'),
            ],
        ])->withViewData(['pageTitle' => 'Your Cart']);
    }

    public function orderSuccess(string $orderCode)
    {
        $order = AffiliateOrder::with('state')->where('order_code', $orderCode)->firstOrFail();

        $awaitingPayment = $order->status === AffiliateOrder::STATUS_PENDING
            && $order->payment_method === AffiliateOrder::PAYMENT_PAYSTACK;

        return Inertia::render('Shop/OrderSuccess', [
            'order' => [
                'code'     => $order->order_code,
                'total'    => showAmount($order->total_amount) . ' ' . gs('cur_text'),
                'payment'  => $order->payment_method === AffiliateOrder::PAYMENT_PAYSTACK ? 'Paid Online' : 'Cash on Pickup',
                'cash'     => $order->payment_method === AffiliateOrder::PAYMENT_CASH_ON_PICKUP,
                'state'    => $order->state->name ?? '-',
                'email'    => $order->buyer_email,
                'status'   => PublicShopPresenter::orderStatus($order),
                // A Paystack order whose payment was not finished (pop-up closed).
                'awaitingPayment' => $awaitingPayment,
            ],
            'urls' => [
                'invoice' => route('shop.order.invoice', $order->order_code),
                'pay'     => $awaitingPayment ? route('shop.order.pay', $order->order_code) : null,
                'shop'    => route('shop.index'),
            ],
        ])->withViewData(['pageTitle' => $awaitingPayment ? 'Complete Payment' : 'Order Confirmed']);
    }

    public function orderInvoiceDownload(string $orderCode)
    {
        $order = AffiliateOrder::with('items.product:id,sku', 'state')->where('order_code', $orderCode)->firstOrFail();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('Template::shop.pdf.invoice', ['order' => $order]);

        return $pdf->download('invoice-' . $order->order_code . '.pdf');
    }
}

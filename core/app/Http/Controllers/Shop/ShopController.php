<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\State;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Shop';

        $products = Product::active()->hasCategory()->with('category');

        if ($request->filled('category')) {
            $products->where('category_id', $request->integer('category'));
        }

        if ($request->filled('q')) {
            $products->where('name', 'like', '%' . $request->string('q') . '%');
        }

        $products   = $products->latest()->paginate(getPaginate(16))->withQueryString();
        $categories = Category::active()->hasActiveProduct()->get();

        return view('Template::shop.index', compact('pageTitle', 'products', 'categories'));
    }

    public function show(Product $product)
    {
        abort_unless($product->status, 404);

        $pageTitle = $product->name;
        $related   = Product::active()->hasCategory()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->latest()->limit(8)->get();

        return view('Template::shop.product', compact('pageTitle', 'product', 'related'));
    }

    public function cart()
    {
        $pageTitle = 'Your Cart';
        $states    = State::orderBy('name')->get();

        return view('Template::shop.cart', compact('pageTitle', 'states'));
    }

    public function orderSuccess(string $orderCode)
    {
        $order = \App\Models\AffiliateOrder::with('items.product:id,sku', 'state')->where('order_code', $orderCode)->firstOrFail();

        $pageTitle = 'Order Confirmed';

        return view('Template::shop.success', compact('pageTitle', 'order'));
    }

    public function orderInvoiceDownload(string $orderCode)
    {
        $order = \App\Models\AffiliateOrder::with('items.product:id,sku', 'state')->where('order_code', $orderCode)->firstOrFail();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('Template::shop.pdf.invoice', ['order' => $order]);

        return $pdf->download('invoice-' . $order->order_code . '.pdf');
    }
}

<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ShopCartService;
use Illuminate\Http\Request;

/**
 * Cart buttons on the React shop pages. Each answers with a redirect back,
 * so Inertia reloads the page's props (cart lines, the header's cart count)
 * and shows the toast; the old pages fetched JSON and reloaded by hand.
 */
class ShopCartController extends Controller
{
    public function __construct(private ShopCartService $cart)
    {
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity'   => 'nullable|integer|min:1|max:' . ShopCartService::MAX_QUANTITY,
        ]);

        $product = Product::active()->find($request->integer('product_id'));

        if (!$product || $product->quantity < 1) {
            return back()->withNotify([['error', 'This product is currently unavailable.']]);
        }

        $this->cart->add($product->id, $request->integer('quantity', 1));

        return back()->withNotify([['success', $product->name . ' added to cart.']]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity'   => 'required|integer|min:0|max:' . ShopCartService::MAX_QUANTITY,
        ]);

        $this->cart->update($request->integer('product_id'), $request->integer('quantity'));

        return back();
    }

    public function remove(Request $request)
    {
        $request->validate(['product_id' => 'required|integer']);

        $this->cart->remove($request->integer('product_id'));

        return back()->withNotify([['success', 'Removed from your cart.']]);
    }
}

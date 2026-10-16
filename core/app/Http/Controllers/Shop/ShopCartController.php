<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ShopCartService;
use Illuminate\Http\Request;

class ShopCartController extends Controller
{
    public function __construct(private ShopCartService $cart)
    {
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'quantity'   => 'nullable|integer|min:1|max:50',
        ]);

        $product = Product::active()->find($request->integer('product_id'));

        if (!$product || $product->quantity < 1) {
            return response()->json(['success' => false, 'message' => 'This product is currently unavailable.'], 422);
        }

        $this->cart->add($product->id, $request->integer('quantity', 1));

        return response()->json([
            'success' => true,
            'message' => $product->name . ' added to cart.',
            'count'   => $this->cart->count(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity'   => 'required|integer|min:0|max:50',
        ]);

        $this->cart->update($request->integer('product_id'), $request->integer('quantity'));

        $resolved = $this->cart->resolve();

        return response()->json([
            'success'  => true,
            'subtotal' => getAmount($resolved['subtotal']),
            'count'    => $this->cart->count(),
        ]);
    }

    public function remove(Request $request)
    {
        $request->validate(['product_id' => 'required|integer']);

        $this->cart->remove($request->integer('product_id'));

        return response()->json(['success' => true, 'count' => $this->cart->count()]);
    }
}

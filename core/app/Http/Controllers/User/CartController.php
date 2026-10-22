<?php
// app/Http/Controllers/CartController.php
namespace App\Http\Controllers\User;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductStatePrice;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Support\ShopPresenter;

class CartController extends Controller
{
    public function index()
    {
        $pageTitle = 'Shopping Cart';
        $user      = auth()->user();
        $cart      = $user->cart->load('items.product', 'items.productstate.state');
        $total     = (float) $cart->items->sum(fn ($item) => $item->price * $item->quantity); // same sum as Cart::updateTotal()
        $wallet    = (float) ($user->product_wallet ?? 0);

        return Inertia::render('User/Shop/Cart', [
            'items' => $cart->items->map(fn ($item) => [
                'id'        => $item->id,
                'name'      => (string) @$item->product->name,
                'sku'       => @$item->product->sku,
                'image'     => @$item->product->thumbnail ? ShopPresenter::image($item->product->thumbnail) : null,
                'state'     => (string) @$item->productstate->state->name,
                'quantity'  => (int) $item->quantity,
                'price'     => showAmount($item->price, 2),
                'lineTotal' => showAmount($item->price * $item->quantity, 2),
                'updateUrl' => route('user.cart.update', $item->id),
                'removeUrl' => route('user.cart.remove', $item->id),
            ])->values(),
            'total'       => showAmount($total, 2),
            'wallet'      => showAmount($wallet),
            'canCheckout' => $wallet >= $total,
            'shortfall'   => $wallet < $total ? showAmount($total - $wallet) : null,
            'productsUrl' => route('user.products'),
            'checkoutUrl' => route('user.checkout'),
        ])->withViewData(compact('pageTitle'));
    }

    public function addToCart(Request $request, $product_id)
    {
        $request->validate([
            'quantity'         => 'required|integer|min:1|max:99',
            'productId'        => 'required|integer',
            'product_state_id' => 'required|integer|exists:product_state_prices,id',
        ], [
            'product_state_id.required' => 'Please select your state first.',
        ]);

        $cart = auth()->user()->cart;

        // A cart holds products for one state only (prices differ per state).
        $firstItem = $cart->id ? $cart->items()->with('productstate')->first() : null;
        if ($firstItem) {
            $statePrice = getProductStatePrice($request->product_state_id);
            if ($firstItem->productstate->state_id != $statePrice->state_id) {
                return back()->withErrors(['cart' => 'A cart can only hold products for a single state']);
            }
        }

        DB::beginTransaction();
        try {
            if (!$cart->id) {
                $cart = $this->createCart();
            }

            $product       = Product::active()->where('id', $request->productId)->first();
            $product_state = ProductStatePrice::where('product_id', $request->productId)->where('id', $request->product_state_id)->first();

            // An inactive product, or a state price that belongs to another product.
            if (!$product || !$product_state) {
                DB::rollBack();
                return back()->withErrors(['cart' => 'This product is not available.']);
            }

            $cartItem = $cart->items()->where('product_id', $product->id)->where('product_state_id', $product_state->id)->first();

            if ($cartItem) {
                $cartItem->quantity += $request->quantity;
                $cartItem->save();
            } elseif (!$cart->items()->where('product_id', $product->id)->exists()) {
                // The same product for another state can't sit in the cart twice.
                CartItem::create([
                    'cart_id'          => $cart->id,
                    'product_id'       => $product->id,
                    'quantity'         => $request->quantity,
                    'product_state_id' => $request->product_state_id,
                    'price'            => $product_state->price,
                ]);
            }

            $cart->load('items');
            $cart->updateTotal();
            DB::commit();

            return back();
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return back()->withErrors(['cart' => 'Add to cart failed. Please try again.']);
        }
    }

    public function updateCart(Request $request, CartItem $cartItem)
    {
        $this->authorizeItem($cartItem);

        $request->validate([
            'quantity' => 'required|integer|min:1|max:99'
        ]);

        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        $cartItem->cart->updateTotal();

        return back();
    }

    public function removeFromCart(CartItem $cartItem)
    {
        $this->authorizeItem($cartItem);

        $cart = $cartItem->cart;
        $cartItem->delete();
        $cart->load('items');
        $cart->updateTotal();

        $notify[] = ['success', 'Item removed from your cart'];
        return back()->withNotify($notify);
    }

    /**
     * Cart items are looked up by id from the URL: only the owner of the cart
     * may change or remove them (any user could, before).
     */
    private function authorizeItem(CartItem $cartItem): void
    {
        abort_unless((int) optional($cartItem->cart)->user_id === (int) auth()->id(), 404);
    }

    private function createCart()
    {
        $cart = new Cart();
        $cart->user_id = auth()->id();
        $cart->total_amount =  0;
        $cart->save();

        return $cart;
    }
}
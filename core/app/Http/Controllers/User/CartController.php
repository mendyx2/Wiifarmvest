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

class CartController extends Controller
{
    public function index()
    {   $pageTitle = 'Shopping Cart';
        //dd($cart = auth()->user()->cart);
        $cart = auth()->user()->cart ?? $this->createCart();

        //dd($cart);
        if (!isset($cart->id)) {
            $this->createCart(); 
        }
        //dd($cart);
        return view('Template::user.cart.index', compact('cart', 'pageTitle'));
    }

    public function addToCart(Request $request, $product_id)
    { 
        $request->validate([ 
            'quantity' => 'required|integer|min:1',
            'productId'=> 'required|integer',
            'product_state_id'=> 'required|integer|exists:product_state_prices,id'
        ]);
        //product_state_id
        $cart = auth()->user()->cart;
        if ($cart) {// cart exist...
            $cartItemChecking = $cart->items()->first();


            if ($cartItemChecking) {// cartItem exist...

               $product_state_price  = getProductStatePrice($request->product_state_id);
              
                if ($cartItemChecking->productstate->state_id != $product_state_price->state_id) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'A cart cannot only hold a single state',
                        'cart_count' => $cart->items->count()
                    ]);
                }

            }//  end cartItem exist...
        }// end cart exist...
        //dd('i am here');
        
        DB::beginTransaction();
        try {

            
            if (!$cart->id) {
                $cart = new Cart();
                $cart->user_id = auth()->id();
                $cart->total_amount =  0;
                $cart->save();
            }else{
                $cart = auth()->user()->cart;
            }

            $product = Product::where('id', $request->productId)->first();
            $product_state = ProductStatePrice::Where('product_id', $request->productId)->where('id',$request->product_state_id)->first();
            
            $cartItem = $cart->items()->where('product_id', $product->id)->where('product_state_id', $product_state->id)->first();

            if ($cartItem) {
                $cartItem->quantity += $request->quantity;
                $cartItem->save();

            } else {
                $cartItem_check = $cart->items()->where('product_id', $product->id)->first();
                //  cartItem_check this is to ensure no same product for different location is in this cart.
                if (!$cartItem_check) {

                    CartItem::create([
                        'cart_id' => $cart->id,
                        'product_id' => $product->id,
                        'quantity' => $request->quantity,
                        'product_state_id' => $request->product_state_id,
                        'price' => $product_state->price
                    ]);
                }
            }

            $cart->updateTotal();
        DB::commit();
        
            return response()->json([
                'success' => true,
                'message' => 'Product added to cart successfully',
                'cart_count' => $cart->items->count()
            ]);
      
        } catch (\Exception $e) {
        DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Add to cart failed: ' . $e->getMessage()]);
        }

    }

    public function updateCart(Request $request, CartItem $cartItem)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        $cartItem->cart->updateTotal();


        return response()->json(['success' => true]);
    }

    public function removeFromCart(CartItem $cartItem)
    {
       
        DB::beginTransaction();
        try {

            $cart = $cartItem->cart;
            $cartItem->delete();
            $cartItem->cart->updateTotal();
           
            //dd($cart->items->count());
            // if ($cart->items->count() == 0) {
            //    //dd($cart->items->count());
            //    //$carter = Cart::find($cart->id);
            //    $cart->destroy($cart->id);
            // }
           
            
            DB::commit();
         return response()->json(['success' => true]);
     
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Add to cart failed: ' . $e->getMessage()]);
        }
       
    }

    private function createCart()
    {
        $cart = new Cart();
        $cart->user_id = auth()->id();
        $cart->total_amount =  0;
        $cart->save();
        //dd($cart);

        return $cart;
    }
}
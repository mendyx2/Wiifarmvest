<?php
// app/Http/Controllers/StockistInventoryController.php
namespace App\Http\Controllers\User;

use App\Models\Product;
use App\Models\StockistProduct;
use App\Models\Stockist;
use App\Models\StockistInventory;
use App\Models\ProductStatePrice;
use App\Models\StockistOrder;
use App\Models\StockistOrderItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class StockistInventoryController extends Controller
{
    public function dashboard()
    { 
        $pageTitle = "Stockist Inventory";
        //$stockist = auth()->user()->stockist;
        $stockist = Stockist::where('user_id', auth()->id())->first();


        $inventory = $stockist->inventory()->with('product')->get();
        //dd($inventory);
        $lowStockItems = $stockist->low_stock_items;
        $outOfStockItems = $stockist->out_of_stock_items;
         //dd($lowStockItems->count());
        $cart = session()->get('stockist_cart', []);
        //'total_inventory_value' => $stockist->total_inventory_value,
        $stats = [
            'total_products' => $inventory->count(),
            'low_stock_count' => $lowStockItems->count(),
            'out_of_stock_count' => $outOfStockItems->count(),
            
            'wallet_balance' => $stockist->wallet
        ];

        // Only a Mega stockist orders from the company; a Mini restocks from a Mega
        $canOrderFromCompany = $stockist->isMega();

        return view('Template::user.stockist.inventory.dashboard', compact(
            'stockist', 'inventory', 'lowStockItems', 'outOfStockItems', 'stats' , 'pageTitle', 'cart', 'canOrderFromCompany'
        ));
    }

    public function productsCatalog()
    { 
        $pageTitle = "Stockist catalog";
        $stockist = auth()->user()->stockist;
        $stateID = $stockist->state_id;
        $products = ProductStatePrice::with('product')
                ->where('state_id', $stateID)
                ->paginate(12);
        //$products = Product::with('productstate')->where('is_active', true) ->where('quantity', '>', 0)->paginate(12);
            //dd($products);
 
        
        $cart = session()->get('stockist_cart', []);
 
        return view('Template::user.stockist.inventory.catalog', compact('products', 'stockist', 'cart' , 'pageTitle'));
    }

    public function addToCart(Request $request)
    {

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);
        
        $stockist = auth()->user()->stockist;
        $stateID = $stockist->state_id;
        
        $product = Product::findOrFail($request->product_id);
        $statePrice = ProductStatePrice::where('state_id', $stateID)
            ->where('product_id', $product->id)
            ->first();

        // Check if product is available
        if (!$product->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'Product is not available for ordering.'
            ]);
        }
        //dd($product);
        // Check quantity limits
        if ($request->quantity > $product->max_order_quantity) {
            return response()->json([
                'success' => false,
                'message' => "Maximum order quantity is {$product->max_order_quantity}."
            ]);
        }

        if ($request->quantity < $product->min_order_quantity) {
            return response()->json([
                'success' => false,
                'message' => "Minimum order quantity is {$product->min_order_quantity}."
            ]);
        }

        $cart = session()->get('stockist_cart', []);

        // Check if product already in cart
        if (isset($cart[$request->product_id])) {
            $cart[$request->product_id]['quantity'] += $request->quantity;
        } else {
            $cart[$request->product_id] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $statePrice->price,
                'quantity' => $request->quantity, 
                'image' => $product->thumbnail,
                'max_quantity' => $product->max_order_quantity
            ];
        }

        session()->put('stockist_cart', $cart);

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart successfully!',
            'cart_count' => count($cart)
        ]);
    }

    public function updateCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:0'
        ]);
        //dd($request);
        $cart = session()->get('stockist_cart', []);

        if ($request->quantity == 0) {
            unset($cart[$request->product_id]);
        } else {
            $product = Product::find($request->product_id);
            if ($product && $request->quantity <= $product->max_order_quantity) {
                $cart[$request->product_id]['quantity'] = $request->quantity;
            }
        }

        session()->put('stockist_cart', $cart);

        return response()->json([
            'success' => true,
            'cart_count' => count($cart)
        ]);
    }

    public function getCart()
    {
        $cart = session()->get('stockist_cart', []);
        $total = 0;

        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
       
        return response()->json([
            'cart' => $cart,
            'total' => $total,
            'count' => count($cart)

        ]);
    }

    public function checkout()
    {
        $pageTitle = "Stockist Cart";
        $cart = session()->get('stockist_cart', []);
        
        if (empty($cart)) {
            return redirect()->route('user.stockist.inventory.catalog')
                ->with('error', 'Your cart is empty.');
        }

        $stockist = auth()->user()->stockist;
        $total = 0;

        foreach ($cart as $item) {
            $total += $item['price'] * $item['quantity'];
        }
         $tax  = 1;
        // The session cart predates SKUs on these pages: look them up in one query
        $skus = Product::skuMap(array_keys($cart));

        return view('Template::user.stockist.inventory.checkout', compact('stockist', 'cart', 'total' , 'pageTitle', 'tax', 'skus'));
    }

    public function placeOrder(Request $request)
    {
        $cart = session()->get('stockist_cart', []);
        
        if (empty($cart)) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty.'
            ]);
        }

        $stockist = auth()->user()->stockist;

        DB::beginTransaction();
        try {
            // Calculate totals
            $totalAmount = 0;
            $items = [];

            foreach ($cart as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product || !$product->is_available) {
                    throw new \Exception("Product {$item['name']} is no longer available.");
                }

                if ($item['quantity'] > $product->quantity) {
                    throw new \Exception("Insufficient stock for {$item['name']}. Available: {$product->quantity}");
                }

                $itemTotal = $item['price'] * $item['quantity'];
                $totalAmount += $itemTotal;

                $items[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'total_price' => $itemTotal
                ];
            }

            $shippingCost = 0; // Could be calculated based on location
            $taxAmount =  0;$totalAmount * 0.05; // 5% tax
            $grandTotal = $totalAmount; // + $shippingCost + $taxAmount;

            // Check wallet balance
            if ($stockist->wallet < $grandTotal) {
                throw new \Exception('Insufficient wallet balance. Please top up your wallet.');
            }

            // Create order
            $order = StockistOrder::create([
                'stockist_id' => $stockist->id,
                'total_amount' => $totalAmount,
                'shipping_cost' => $shippingCost,
                'tax_amount' => $taxAmount,
                'grand_total' => $grandTotal,
                'status' => 'pending',
                'notes' => $request->notes
            ]);

            // Create order items
            foreach ($items as $item) {
                StockistOrderItem::create(array_merge($item, [
                    'stockist_order_id' => $order->id
                ]));

                // Reserve stock (reduce available quantity)
                $product = Product::find($item['product_id']);
                $product->decrement('quantity', $item['quantity']);
            }

            // Deduct from wallet — atomic; a parallel order may have spent it since the check above
            if (!$stockist->deductFromWallet($grandTotal)) {
                throw new \Exception('Insufficient wallet balance. Please top up your wallet.');
            }

            // Clear cart
            session()->forget('stockist_cart');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully!',
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function orderHistory()
    {
        $pageTitle = "Stockist Orders";
        $stockist = auth()->user()->stockist;
        $orders = $stockist->orders()->with('items.product')->latest()->paginate(10);
        $canOrderFromCompany = $stockist->isMega();
        if (!$canOrderFromCompany) {
            $pageTitle = "Earlier Company Orders";
        }

        return view('Template::user.stockist.inventory.orders', compact('stockist', 'orders' , 'pageTitle', 'canOrderFromCompany'));
    }

    public function orderDetails(StockistOrder $order)
    {
        $pageTitle = "Stockist Order details";
        if ($order->stockist_id != auth()->user()->stockist->id) {
            abort(403);
        }

        $order->load('items.product');

        return view('Template::user.stockist.inventory.order-details', compact('order' , 'pageTitle'));
    }

    public function updateInventoryLevels(Request $request)
    {
        $request->validate([
            'inventory_id' => 'required|exists:stockist_inventory,id',
            'min_stock_level' => 'required|integer|min:0',
            'max_stock_level' => 'required|integer|min:1|gt:min_stock_level'
        ]);

        $inventory = StockistInventory::findOrFail($request->inventory_id);

        // Check if stockist owns this inventory
        if ($inventory->stockist_id !== auth()->user()->stockist->id) {
            abort(403);
        }

        $inventory->update([
            'min_stock_level' => $request->min_stock_level,
            'max_stock_level' => $request->max_stock_level
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inventory levels updated successfully!'
        ]);
    }
}
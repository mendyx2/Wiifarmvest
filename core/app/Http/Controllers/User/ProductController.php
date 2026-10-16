<?php

namespace App\Http\Controllers\User;
use App\Models\Product;
use App\Models\State;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        
        $pageTitle = 'Products';
        $states = State::all();
        $selectedState = $request->get('state');
        //dd($selectedState);
        $products = Product::with(['statePrices' => function($query) use ($selectedState) {
            if ($selectedState) {
                $query->where('state_id', $selectedState);
            }
        }])->get();
        
        
        return view('Template::user.products.index', compact('products', 'states', 'selectedState','pageTitle'));
    }
    
    
    
    public function search(Request $request)
    {   
        $pageTitle = 'Products';
        $query = $request->input('query');
        $stateId = $request->input('state');
        
        $products = Product::when($query, function($q) use ($query) {
                $q->where(function($queryBuilder) use ($query) {
                    $queryBuilder->where('name', 'LIKE', "%{$query}%")
                                ->orWhere('description', 'LIKE', "%{$query}%");
                               
                });
                // ->orWhere('category', 'LIKE', "%{$query}%");
            })
            ->with(['statePrices' => function($q) use ($stateId) {
                if ($stateId) {
                    $q->where('state_id', $stateId);
                }
            }])
            ->get();
            
         $selectedStateName = null;
        $selectedState = $stateId; // Add this line to fix the issue
        
        if ($stateId) {
            $state = State::find($stateId);
            $selectedStateName = $state ? $state->name : null;
        }
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'products_html' => view('Template::user.products.products_grid', [
                    'products' => $products,
                    'selectedState' => $selectedState // Pass the variable
                ])->render(),
                'products_count' => $products->count(),
                'selected_state_name' => $selectedStateName
            ]);
        }
            
        $states = State::all();
        return view('Template::user.products.index', compact('products', 'states', 'selectedState','pageTitle'));
        
    }

    public function getProductPrice($productId, $stateId)
    {
        $product = Product::findOrFail($productId);
        $price = $product->getPriceForState($stateId);
        
        return response()->json([
            'price' => $price,
            'formatted_price' => '₦' . number_format($price, 2)
        ]);
    }
}
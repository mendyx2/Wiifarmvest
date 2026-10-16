<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\ProductStatePrice;
use App\Models\State;
use Illuminate\Http\Request;
use App\Rules\FileTypeValidate;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{

    public function index()
    {
        $pageTitle = 'Products';
        $products  = Product::searchable(['name', 'category:name'])->with('category')->latest('id')->paginate(getPaginate());
        return view('admin.product.index', compact('pageTitle', 'products'));
    }

    public function create()
    {
        $pageTitle  = 'Create Product';
        $categories = Category::active()->get();
        $states     = State::orderBy('name')->get();
        return view('admin.product.create', compact('pageTitle', 'categories', 'states'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'category'              => 'required|integer|exists:categories,id',
            'price'                 => 'required|numeric|gt:0',
            'selling_price'         => 'nullable|numeric|gt:0',
            'quantity'              => 'required|integer|gt:0',
            'sku'                   => 'required|integer|gt:0',
            'pv'                    => 'required|numeric|min:0',
            
            'min_order_quantity'    => 'required|numeric|gt:0',
            'max_order_quantity'    => 'required|numeric|gt:0',
            'description'           => 'required',
            'specification.*.name'  => 'required|sometimes',
            'specification.*.value' => 'required|sometimes',
            'gallery.*'             => ['nullable', 'image', new FileTypeValidate(['jpeg', 'jpg', 'png'])],
            'thumbnail'             => ['required', 'image', new FileTypeValidate(['jpeg', 'jpg', 'png'])],
            'affiliate_bonus_type'  => 'nullable|in:fixed,percentage',
            'affiliate_bonus_value' => 'nullable|numeric|min:0',
            'state_prices'                  => 'nullable|array',
            'state_prices.*.state_id'       => 'required|integer|distinct|exists:states,id',
            'state_prices.*.price'                => 'required|numeric|gt:0',
            'state_prices.*.mega_stockist_bonus'  => 'nullable|numeric|min:0|max:100',
            'state_prices.*.micro_stockist_bonus' => 'nullable|numeric|min:0|max:100',
        ], [
            'specification.*.name.required'  => 'All specification name fields are required',
            'specification.*.value.required' => 'All specification value fields are required',
            'state_prices.*.state_id.distinct'  => 'Each state can only have one price',
            'state_prices.*.*_stockist_bonus.max' => 'A stockist bonus is a percentage (0–100)',
        ]);

        $product                     = new Product();
        $product->category_id        = $request->category;
        $product->name               = $request->name;
        $product->price              = $request->price;
        $product->selling_price      = $request->selling_price ?: null;
        $product->quantity           = $request->quantity;
        $product->sku                = $request->sku;
        $product->pv                 = $request->pv;
        $product->prb                = 0;
        //$request->prb;'prb'=> 'required|numeric|min:0',
        $product->min_order_quantity = $request->min_order_quantity;
        $product->max_order_quantity = $request->max_order_quantity;
        $product->affiliate_bonus_type  = $request->affiliate_bonus_type ?: null;
        $product->affiliate_bonus_value = $request->affiliate_bonus_type ? $request->affiliate_bonus_value : null;
        $product->description        = $request->description;
        $product->meta_title         = $request->meta_title;
        $product->meta_description   = $request->meta_description;
        $product->meta_keyword       = $request->meta_keywords;
        $product->bv                 = 1;

        if ($request->hasFile('thumbnail')) {
            try {
                $thumb              = getThumbSize('products');
                $product->thumbnail = fileUploader($request->thumbnail, getFilePath('products'), getFileSize('products'), null, $thumb);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload thumbnail image'];
                return back()->withNotify($notify);
            }
        }

        if ($request->specification) {
            $product->specifications = array_values($request->specification);
        }
        $product->save();

        foreach ($request->state_prices ?? [] as $row) {
            ProductStatePrice::create([
                'product_id'           => $product->id,
                'state_id'             => $row['state_id'],
                'price'                => $row['price'],
                'mega_stockist_bonus'  => $row['mega_stockist_bonus'] ?? 0,
                'micro_stockist_bonus' => $row['micro_stockist_bonus'] ?? 0,
            ]);
        }

        $image = $this->insertImages($request, $product);
        if (!$image) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Couldn\'t upload product gallery images',
            ]);
        }

        $notify[] = ['success', 'Product created successfully'];
        return back()->withNotify($notify);
    }

    public function edit($id)
    {
        $product    = Product::with('category', 'images', 'statePrices.state')->findOrFail($id);
        $pageTitle  = 'Edit Product: ' . $product->name;
        $categories = Category::active()->get();
        $states     = State::orderBy('name')->get();

        $images = [];
        foreach (($product->images ?? []) as $image) {
            $images[] = [
                'id'  => $image->id,
                'src' => getImage(getFilePath('products') . '/' . $image->name),
            ];
        }

        return view('admin.product.edit', compact('pageTitle', 'categories', 'product', 'images', 'states'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'category'              => 'required|integer|exists:categories,id',
            'price'                 => 'required|numeric|gt:0',
            'selling_price'         => 'nullable|numeric|gt:0',
            'sku'                   => 'required|integer|gt:0',
            'pv'                    => 'required|numeric|min:0',
            
            'min_order_quantity'    => 'required|numeric|gt:0',
            'max_order_quantity'    => 'required|numeric|gt:0',
            'quantity'              => 'required|integer|gt:0',
            'description'           => 'required',
            'specification.*.name'  => 'required|sometimes',
            'specification.*.value' => 'required|sometimes',
            'galleryImages.*'       => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'thumbnail'             => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'affiliate_bonus_type'  => 'nullable|in:fixed,percentage',
            'affiliate_bonus_value' => 'nullable|numeric|min:0',
        ], [
            'specification.*.name.required'  => 'All specification name fields are required',
            'specification.*.value.required' => 'All specification value fields are required',
        ]);

        $product                     = Product::findOrFail($id);
        $product->name               = $request->name;
        $product->category_id        = $request->category;
        $product->price              = $request->price;
        $product->selling_price      = $request->selling_price ?: null;
        $product->quantity           = $request->quantity;
        $product->description        = $request->description;
        $product->meta_title         = $request->meta_title;
        $product->meta_description   = $request->meta_description;
        $product->bv                 = 1;
        $product->sku                = $request->sku;
        $product->pv                 = $request->pv;
        $product->prb                = 0;
        //$request->prb; 'prb'  => 'required|numeric|min:0',
        $product->min_order_quantity = $request->min_order_quantity;
        $product->max_order_quantity = $request->max_order_quantity;
        $product->meta_keyword       = $request->meta_keywords;
        $product->affiliate_bonus_type  = $request->affiliate_bonus_type ?: null;
        $product->affiliate_bonus_value = $request->affiliate_bonus_type ? $request->affiliate_bonus_value : null;

        if ($request->specification) {
            $product->specifications = array_values($request->specification);
        } else {
            $product->specifications = null;
        }

        if ($request->hasFile('thumbnail')) {
            try {
                $thumb              = getThumbSize('products');
                $product->thumbnail = fileUploader($request->thumbnail, getFilePath('products'), getFileSize('products'), null, $thumb);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload thumbnail image'];
                return back()->withNotify($notify);
            }
        }

        $image = $this->insertImages($request, $product, $id);
        if (!$image) {
            $notify[] = ['error', 'Couldn\'t upload product gallery images'];
            return back()->withNotify($notify);
        }

        $product->save();

        $notify[] = ['success', 'Product updated successfully'];
        return back()->withNotify($notify);
    }

    public function storeStatePrice(Request $request, int $id)
    {
        $request->validate([
            'state_id'             => 'required|integer|exists:states,id',
            'price'                => 'required|numeric|gt:0',
            'mega_stockist_bonus'  => 'nullable|numeric|min:0|max:100',
            'micro_stockist_bonus' => 'nullable|numeric|min:0|max:100',
        ], [
            '*_stockist_bonus.max' => 'A stockist bonus is a percentage (0–100)',
        ]);

        $product = Product::findOrFail($id);

        // Adds a new state, or updates an existing one (the Edit button re-submits this form)
        ProductStatePrice::updateOrCreate(
            ['product_id' => $product->id, 'state_id' => $request->state_id],
            [
                'price'                => $request->price,
                'mega_stockist_bonus'  => $request->mega_stockist_bonus ?? 0,
                'micro_stockist_bonus' => $request->micro_stockist_bonus ?? 0,
            ]
        );

        $notify[] = ['success', 'State price saved successfully'];
        return back()->withNotify($notify);
    }

    public function destroyStatePrice(int $id, int $priceId)
    {
        $price = ProductStatePrice::where('product_id', $id)->where('id', $priceId)->firstOrFail();
        $price->delete();

        $notify[] = ['success', 'State price removed'];
        return back()->withNotify($notify);
    }

    protected function insertImages($request, $product, $id = null)
    {
        $path = getFilePath('products');

        if ($id) {
            $this->removeImages($request, $product, $path);
        }

        if ($request->hasFile('gallery')) {
            $size      = getFileSize('products');
            $thumbSize = getThumbSize('products');
            $images    = [];

            foreach ($request->file('gallery') as $file) {
                try {
                    $name              = fileUploader($file, $path, $size, null, $thumbSize);
                    $image             = new ProductImage();
                    $image->product_id = $product->id;
                    $image->name       = $name;
                    $images[]          = $image;
                } catch (\Exception $exp) {
                    return false;
                }
            }
            $product->images()->saveMany($images);
        }
        return true;
    }

    protected function removeImages($request, $product, $path)
    {
        $previousImages = $product->images->pluck('id')->toArray();
        $imageToRemove  = array_values(array_diff($previousImages, $request->old ?? []));
        foreach ($imageToRemove as $item) {
            $productImage = ProductImage::find($item);
            fileManager()->removeFile($path . '/' . $productImage->name);
            fileManager()->removeFile($path . '/thumb_' . $productImage->name);
            $productImage->delete();
        }
    }

    public function status($id)
    {
        return Product::changeStatus($id);
    }

    public function feature($id)
    {
        return Product::changeStatus($id, 'is_featured');
    }
}

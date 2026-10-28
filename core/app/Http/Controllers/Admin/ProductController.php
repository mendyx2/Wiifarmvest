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
use App\Constants\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ProductController extends Controller
{

    /** Shop → Manage Product (Pages/Admin/Shop/Products.tsx). */
    public function index()
    {
        $pageTitle = 'Products';
        $products  = Product::searchable(['name', 'category:name'])->with('category')->latest('id')->paginate(getPaginate())->withQueryString()
            ->through(fn (Product $product) => [
                'id'       => $product->id,
                'name'     => (string) $product->name,
                'sku'      => (string) $product->sku,
                'category' => $product->category?->name,
                'price'    => showAmount($product->price),
                'pv'       => number_format((float) $product->pv, 2),
                'stock'    => (int) $product->quantity,
                'featured' => (int) $product->is_featured === Status::ENABLE,
                'enabled'  => (int) $product->status === Status::ENABLE,
                'image'    => getImage(getFilePath('products') . '/' . $product->thumbnail, getFileSize('products')),
                'editUrl'  => route('admin.product.edit', $product->id),
            ]);

        return Inertia::render('Admin/Shop/Products', [
            'products' => $products,
            'search'   => (string) request()->query('search', ''),
            'urls'     => [
                'create'  => route('admin.product.create'),
                'status'  => route('admin.product.status', ':id'),
                'feature' => route('admin.product.feature', ':id'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function create()
    {
        return $this->formPage(null);
    }

    public function edit($id)
    {
        return $this->formPage(Product::with('images', 'statePrices.state')->findOrFail($id));
    }

    /** Create and Edit Product: one page (Pages/Admin/Shop/ProductForm.tsx); they were two near-copies. */
    private function formPage(?Product $product)
    {
        $pageTitle = $product ? 'Edit Product: ' . $product->name : 'Create Product';
        $path      = getFilePath('products');

        return Inertia::render('Admin/Shop/ProductForm', [
            'title'      => $pageTitle,
            'product'    => $product ? [
                'id'                    => $product->id,
                'name'                  => (string) $product->name,
                'category'              => (string) $product->category_id,
                'sku'                   => (string) $product->sku,
                'quantity'              => (string) $product->quantity,
                'min_order_quantity'    => (string) $product->min_order_quantity,
                'max_order_quantity'    => (string) $product->max_order_quantity,
                'price'                 => (string) getAmount($product->price),
                'selling_price'         => $product->selling_price ? (string) getAmount($product->selling_price) : '',
                'pv'                    => (string) ($product->pv + 0),
                'affiliate_bonus_type'  => (string) $product->affiliate_bonus_type,
                'affiliate_bonus_value' => $product->affiliate_bonus_value !== null ? (string) ($product->affiliate_bonus_value + 0) : '',
                'description'           => (string) $product->description,
                'specification'         => array_values(array_map(fn ($s) => ['name' => (string) ($s['name'] ?? ''), 'value' => (string) ($s['value'] ?? '')], (array) $product->specifications)),
                'meta_title'            => (string) $product->meta_title,
                'meta_keywords'         => array_values(array_map('strval', (array) $product->meta_keyword)),
                'meta_description'      => (string) $product->meta_description,
                'thumbnail'             => getImage($path . '/' . $product->thumbnail, getFileSize('products')),
                'gallery'               => $product->images->map(fn (ProductImage $image) => ['id' => $image->id, 'src' => getImage($path . '/' . $image->name)])->values(),
            ] : null,
            'statePrices' => $product ? $product->statePrices->sortBy(fn ($sp) => $sp->state?->name)->map(fn (ProductStatePrice $sp) => [
                'id'      => $sp->id,
                'stateId' => $sp->state_id,
                'state'   => (string) $sp->state?->name,
                'code'    => (string) $sp->state?->code,
                'price'   => (float) $sp->price,
                'mega'    => (float) $sp->mega_stockist_bonus,
                'micro'   => (float) $sp->micro_stockist_bonus,
            ])->values() : [],
            'categories' => Category::active()->orderBy('name')->get(['id', 'name'])->map(fn ($c) => ['id' => $c->id, 'name' => (string) $c->name]),
            'states'     => State::orderBy('name')->get(['id', 'name', 'code'])->map(fn ($s) => ['id' => $s->id, 'name' => (string) $s->name, 'code' => (string) $s->code]),
            // ₦ per PV, for "1% bonus = ₦x per unit".
            'pvRate'     => rDollar(),
            'imageSize'  => getFileSize('products'),
            'urls'       => [
                'save'        => $product ? route('admin.product.update', $product->id) : route('admin.product.store'),
                'index'       => route('admin.product.index'),
                'statePrice'  => $product ? route('admin.product.state-price.store', $product->id) : null,
                'statePriceDestroy' => $product ? route('admin.product.state-price.destroy', [$product->id, ':id']) : null,
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** The rules both forms share. */
    private function rules(bool $creating): array
    {
        return [
            'name'                  => 'required|string|max:255',
            'category'              => 'required|integer|exists:categories,id',
            'price'                 => 'required|numeric|gt:0',
            'selling_price'         => 'nullable|numeric|gt:0',
            'quantity'              => 'required|integer|gte:0',
            'sku'                   => 'required|integer|gt:0',
            'pv'                    => 'required|numeric|min:0',
            'min_order_quantity'    => 'required|integer|gt:0',
            // A maximum below the minimum made the product impossible to order.
            'max_order_quantity'    => 'required|integer|gte:min_order_quantity',
            'description'           => 'required|string',
            'specification'         => 'nullable|array',
            'specification.*.name'  => 'required|string|max:255',
            'specification.*.value' => 'required|string|max:255',
            'meta_title'            => 'nullable|string|max:255',
            'meta_keywords'         => 'nullable|array',
            'meta_keywords.*'       => 'string|max:100',
            'meta_description'      => 'nullable|string|max:1000',
            // The edit form checked "galleryImages.*", a name nothing sends, so its gallery uploads were never checked.
            'gallery'               => 'nullable|array|max:10',
            'gallery.*'             => ['image', 'max:3072', new FileTypeValidate(['jpeg', 'jpg', 'png'])],
            'old'                   => 'nullable|array',
            'old.*'                 => 'integer',
            'thumbnail'             => [$creating ? 'required' : 'nullable', 'image', new FileTypeValidate(['jpeg', 'jpg', 'png'])],
            'affiliate_bonus_type'  => 'nullable|in:fixed,percentage',
            'affiliate_bonus_value' => 'nullable|required_with:affiliate_bonus_type|numeric|min:0' . (request('affiliate_bonus_type') === 'percentage' ? '|max:100' : ''),
        ];
    }

    private const MESSAGES = [
        'specification.*.name.required'       => 'All specification name fields are required',
        'specification.*.value.required'      => 'All specification value fields are required',
        'max_order_quantity.gte'              => 'The maximum order quantity cannot be below the minimum.',
        'affiliate_bonus_value.required_with' => 'Enter the bonus value, or set the bonus type to None.',
        'affiliate_bonus_value.max'           => 'A percentage bonus cannot be above 100.',
        'state_prices.*.state_id.distinct'    => 'Each state can only have one price',
        'state_prices.*.*_stockist_bonus.max' => 'A stockist bonus is a percentage (0–100)',
    ];

    /** Fields both forms save. */
    private function fill(Product $product, Request $request): void
    {
        $product->category_id           = $request->category;
        $product->name                  = trim($request->name);
        $product->price                 = $request->price;
        $product->selling_price         = $request->selling_price ?: null;
        $product->quantity              = $request->quantity;
        $product->sku                   = $request->sku;
        $product->pv                    = $request->pv;
        $product->prb                   = 0;
        $product->min_order_quantity    = $request->min_order_quantity;
        $product->max_order_quantity    = $request->max_order_quantity;
        $product->affiliate_bonus_type  = $request->affiliate_bonus_type ?: null;
        $product->affiliate_bonus_value = $request->affiliate_bonus_type ? $request->affiliate_bonus_value : null;
        $product->description           = $request->description;
        $product->meta_title            = $request->meta_title;
        $product->meta_description      = $request->meta_description;
        $product->meta_keyword          = $request->meta_keywords ? array_values(array_filter(array_map('trim', $request->meta_keywords))) : null;
        $product->specifications        = $request->specification ? array_values($request->specification) : null;
        $product->bv                    = 1;
    }

    public function store(Request $request)
    {
        $request->validate($this->rules(true) + [
            'state_prices'                        => 'nullable|array',
            'state_prices.*.state_id'             => 'required|integer|distinct|exists:states,id',
            'state_prices.*.price'                => 'required|numeric|gt:0',
            'state_prices.*.mega_stockist_bonus'  => 'nullable|numeric|min:0|max:100',
            'state_prices.*.micro_stockist_bonus' => 'nullable|numeric|min:0|max:100',
        ], self::MESSAGES);

        $product = new Product();
        $this->fill($product, $request);

        try {
            $product->thumbnail = fileUploader($request->thumbnail, getFilePath('products'), getFileSize('products'), null, getThumbSize('products'));
        } catch (\Exception $e) {
            report($e);
            throw ValidationException::withMessages(['thumbnail' => 'Couldn\'t upload the thumbnail image.']);
        }

        // The product and its state prices together: a failed price no longer leaves a product without them.
        DB::transaction(function () use ($product, $request) {
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
        });

        // The old code answered a failed gallery upload with raw JSON.
        if (!$this->insertImages($request, $product)) {
            $notify[] = ['warning', 'Product created, but some gallery images could not be uploaded.'];
            return to_route('admin.product.edit', $product->id)->withNotify($notify);
        }

        $notify[] = ['success', 'Product created successfully'];
        return to_route('admin.product.edit', $product->id)->withNotify($notify);
    }

    public function update(Request $request, $id)
    {
        $request->validate($this->rules(false), self::MESSAGES);

        $product = Product::findOrFail($id);
        $this->fill($product, $request);

        if ($request->hasFile('thumbnail')) {
            try {
                // Passing the old name removes the old file; it used to stay on disk.
                $product->thumbnail = fileUploader($request->thumbnail, getFilePath('products'), getFileSize('products'), $product->getOriginal('thumbnail'), getThumbSize('products'));
            } catch (\Exception $e) {
                report($e);
                throw ValidationException::withMessages(['thumbnail' => 'Couldn\'t upload the thumbnail image.']);
            }
        }

        $product->save();

        if (!$this->insertImages($request, $product, $id)) {
            $notify[] = ['warning', 'Product saved, but some gallery images could not be uploaded.'];
            return back()->withNotify($notify);
        }

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
            $productImage = ProductImage::where('product_id', $product->id)->find($item);
            if (!$productImage) {
                continue;
            }
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

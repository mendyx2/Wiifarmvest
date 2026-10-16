@extends($activeTemplate . 'layouts.shop')
@section('content')

<div class="shop-wrap">
    <div class="shop-container">

        @if($products->count())
            <div class="shop-grid">
                @foreach($products as $product)
                    <div class="shop-card">
                        <a href="{{ route('shop.product', $product->id) }}" class="shop-card-img">
                            <img src="{{ route('shop.image', \App\Http\Controllers\Shop\ShopImageController::tokenFor($product)) }}" alt="{{ $product->name }}" loading="lazy">
                            @if($product->is_featured)
                                <span class="shop-card-badge">Featured</span>
                            @endif
                        </a>
                        <div class="shop-card-body">
                            <div class="shop-card-meta">
                                <span class="shop-card-cat">{{ $product->category->name ?? '' }}</span>
                                <x-product.sku :value="$product->sku" />
                            </div>
                            <h3 class="shop-card-title"><a href="{{ route('shop.product', $product->id) }}">{{ $product->name }}</a></h3>
                            <div class="shop-card-foot">
                                <x-product.price :amount="$product->shop_price" class="shop-card-price" />
                                <button type="button" class="shop-add-btn" data-add-to-cart data-product-id="{{ $product->id }}" {{ $product->quantity < 1 ? 'disabled' : '' }}>
                                    <i class="las la-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">{{ $products->links() }}</div>
        @else
            <div class="shop-empty">
                <i class="las la-box-open"></i>
                <p>No products found.</p>
            </div>
        @endif

    </div>
</div>

@include('Template::shop.partials.cart_script')
@endsection

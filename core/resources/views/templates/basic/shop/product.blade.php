@extends($activeTemplate . 'layouts.shop')
@section('content')

<div class="shop-wrap">
    <div class="shop-container">

        <a href="{{ route('shop.index') }}" class="shop-back-link"><i class="las la-arrow-left"></i> Back to Shop</a>

        <div class="shop-detail">
            <div class="shop-detail-img">
                <img src="{{ route('shop.image', \App\Http\Controllers\Shop\ShopImageController::tokenFor($product)) }}" alt="{{ $product->name }}">
            </div>
            <div class="shop-detail-body">
                <div class="pp-meta">
                    <span class="shop-detail-cat">{{ $product->category->name ?? '' }}</span>
                    <x-product.sku :value="$product->sku" />
                </div>
                <h1 class="shop-detail-title">{{ $product->name }}</h1>
                <div class="shop-detail-price"><x-product.price :amount="$product->shop_price" /></div>
                <p class="shop-detail-desc">{{ strLimit(strip_tags($product->description), 320) }}</p>

                @if($product->quantity > 0)
                    <div class="shop-qty-row">
                        <div class="shop-qty">
                            <button type="button" data-qty-minus>&minus;</button>
                            <input type="number" id="detailQty" value="1" min="1" max="{{ min(50, $product->quantity) }}">
                            <button type="button" data-qty-plus>+</button>
                        </div>
                        <span class="text-muted small">{{ $product->quantity }} in stock</span>
                    </div>
                    <button type="button" class="shop-buy-btn" data-add-to-cart data-product-id="{{ $product->id }}" data-quantity-input="detailQty">
                        <i class="las la-shopping-cart"></i> Add to Cart
                    </button>
                @else
                    <div class="text-danger fw-bold">Out of stock</div>
                @endif
            </div>
        </div>

        @if($related->count())
            <h2 class="shop-related-heading">You may also like</h2>
            <div class="shop-grid">
                @foreach($related as $rp)
                    <div class="shop-card">
                        <a href="{{ route('shop.product', $rp->id) }}" class="shop-card-img">
                            <img src="{{ route('shop.image', \App\Http\Controllers\Shop\ShopImageController::tokenFor($rp)) }}" alt="{{ $rp->name }}" loading="lazy">
                        </a>
                        <div class="shop-card-body">
                            <div class="shop-card-meta">
                                <span class="shop-card-cat">{{ $rp->category->name ?? '' }}</span>
                                <x-product.sku :value="$rp->sku" />
                            </div>
                            <h3 class="shop-card-title"><a href="{{ route('shop.product', $rp->id) }}">{{ $rp->name }}</a></h3>
                            <div class="shop-card-foot">
                                <x-product.price :amount="$rp->shop_price" class="shop-card-price" />
                                <button type="button" class="shop-add-btn" data-add-to-cart data-product-id="{{ $rp->id }}" {{ $rp->quantity < 1 ? 'disabled' : '' }}>
                                    <i class="las la-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@include('Template::shop.partials.cart_script')
<script>
(function () {
    var minus  = document.querySelector('[data-qty-minus]');
    var plus   = document.querySelector('[data-qty-plus]');
    var input  = document.getElementById('detailQty');
    var buyBtn = document.querySelector('[data-quantity-input]');

    function syncQty() { if (buyBtn) buyBtn.setAttribute('data-quantity', input.value); }

    if (minus) minus.addEventListener('click', function () { input.value = Math.max(1, parseInt(input.value || 1) - 1); syncQty(); });
    if (plus)  plus.addEventListener('click', function () { input.value = Math.min(parseInt(input.max || 50), parseInt(input.value || 1) + 1); syncQty(); });
    if (input) input.addEventListener('change', syncQty);

    syncQty();
})();
</script>
@endsection

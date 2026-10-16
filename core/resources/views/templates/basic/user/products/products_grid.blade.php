@foreach($products as $product)
@php
    $statePrice   = $product->statePrices->first();
    $displayPrice = $statePrice ? $statePrice->price : $product->price;
    $isStatePrice = (bool) $statePrice;
@endphp
<div class="shop-card" data-product-id="{{ $product->id }}">

    {{-- Image --}}
    <div class="shop-card-img">
        @if($product->thumbnail)
            <img src="{{ getImage(getFilePath('products') . '/' . $product->thumbnail, getFileSize('products')) }}" alt="{{ $product->name }}">
        @else
            <div class="shop-card-img-placeholder">
                <i class="las la-box"></i>
            </div>
        @endif
        <span class="shop-card-cat">{{ $product->category->name }}</span>
    </div>

    {{-- Body --}}
    <div class="shop-card-body">
        <h6 class="shop-card-name">{{ $product->name }}</h6>
        <div class="pp-meta"><x-product.sku :value="$product->sku" /></div>
        <p class="shop-card-desc">{{ Str::limit($product->description, 80) }}</p>

        <div class="shop-card-footer">
            <div class="shop-card-price-wrap">
                @if($selectedState)
                    <x-product.price :amount="$displayPrice" class="shop-card-price" />
                    @if($isStatePrice)
                        <span class="shop-card-price-badge state">State Price</span>
                    @else
                        <span class="shop-card-price-badge default">Default</span>
                    @endif
                @else
                    <x-product.price :amount="$product->price" class="shop-card-price" />
                    <span class="shop-card-price-hint"><i class="las la-map-marker-alt"></i> Select state</span>
                @endif
            </div>

            @if($selectedState)
                <button class="shop-card-btn add-to-cart" data-product-id="{{ $product->id }}">
                    <i class="las la-shopping-cart"></i>
                </button>
            @else
                <button class="shop-card-btn disabled" disabled title="Select a state first">
                    <i class="las la-map-marker-alt"></i>
                </button>
            @endif
        </div>
    </div>
</div>
@endforeach

@extends($activeTemplate . 'layouts.shop')
@section('content')

@php $resolved = app(\App\Services\ShopCartService::class)->resolve(); @endphp

<div class="shop-wrap">
    <div class="shop-container">

        <a href="{{ route('shop.index') }}" class="shop-back-link"><i class="las la-arrow-left"></i> Continue Shopping</a>
        <h2 class="shop-related-heading mt-0">Your Cart</h2>

        @if(empty($resolved['lines']))
            <div class="shop-empty">
                <i class="las la-shopping-cart"></i>
                <p>Your cart is empty.</p>
                <a href="{{ route('shop.index') }}" class="shop-buy-btn"><i class="las la-store"></i> Browse Products</a>
            </div>
        @else
            <div class="shop-cart-grid">
                <div id="shopCartItems">
                    @foreach($resolved['lines'] as $line)
                        <div class="shop-cart-item" data-cart-row data-product-id="{{ $line['product']->id }}">
                            <img src="{{ route('shop.image', \App\Http\Controllers\Shop\ShopImageController::tokenFor($line['product'])) }}" alt="{{ $line['product']->name }}">
                            <div class="flex-grow-1">
                                <p class="shop-cart-item-name">{{ $line['product']->name }}</p>
                                <div class="pp-meta my-1">
                                    <x-product.sku :value="$line['product']->sku" />
                                    <span class="shop-cart-item-price"><x-product.price :amount="$line['unit_price']" :code="false" /> each</span>
                                </div>
                                <div class="shop-qty mt-2">
                                    <button type="button" data-cart-minus>&minus;</button>
                                    <input type="number" value="{{ $line['quantity'] }}" min="1" max="50" data-cart-qty-input>
                                    <button type="button" data-cart-plus>+</button>
                                </div>
                            </div>
                            <div class="shop-cart-item-total" data-line-total>{{ showAmount($line['line_total']) }}</div>
                            <button type="button" class="shop-cart-remove" data-cart-remove title="Remove"><i class="las la-trash"></i></button>
                        </div>
                    @endforeach
                </div>

                <div class="shop-summary">
                    <p class="shop-summary-heading">Order Summary</p>
                    <div class="shop-summary-row"><span>Subtotal</span><span id="shopCartSubtotal">{{ showAmount($resolved['subtotal']) }}</span></div>
                    <div class="shop-summary-row"><span>Delivery / Pickup</span><span>At checkout</span></div>
                    <div class="shop-summary-total"><span>Total</span><span id="shopCartTotal">{{ showAmount($resolved['subtotal']) }} <small class="shop-cur">{{ gs('cur_text') }}</small></span></div>
                    <a href="{{ route('shop.checkout') }}" class="shop-checkout-btn">Proceed to Checkout <i class="las la-arrow-right"></i></a>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
(function () {
    'use strict';
    var CSRF_TOKEN = "{{ csrf_token() }}";

    function post(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, 'Accept': 'application/json' },
            body: JSON.stringify(data)
        }).then(function (r) { return r.json(); });
    }

    document.addEventListener('click', function (e) {
        var row = e.target.closest('[data-cart-row]');
        if (!row) return;
        var productId = row.getAttribute('data-product-id');
        var input = row.querySelector('[data-cart-qty-input]');

        if (e.target.closest('[data-cart-minus]')) {
            input.value = Math.max(1, parseInt(input.value || 1) - 1);
            syncRow(productId, input.value);
        } else if (e.target.closest('[data-cart-plus]')) {
            input.value = Math.min(50, parseInt(input.value || 1) + 1);
            syncRow(productId, input.value);
        } else if (e.target.closest('[data-cart-remove]')) {
            post("{{ route('shop.cart.remove') }}", { product_id: productId }).then(function () {
                window.location.reload();
            });
        }
    });

    document.addEventListener('change', function (e) {
        var row = e.target.closest('[data-cart-row]');
        if (!row || !e.target.matches('[data-cart-qty-input]')) return;
        syncRow(row.getAttribute('data-product-id'), e.target.value);
    });

    function syncRow(productId, quantity) {
        post("{{ route('shop.cart.update') }}", { product_id: productId, quantity: quantity }).then(function (res) {
            if (res.success) window.location.reload();
        });
    }
})();
</script>
@endsection

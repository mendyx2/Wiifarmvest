@extends($activeTemplate . 'layouts.master')
@section('title', 'Shopping Cart')
@section('content')

<div class="cart-page">

    {{-- ── Header ── --}}
    <div class="cart-header-card">
        <div class="cart-header-left">
            <div class="cart-header-icon"><i class="las la-shopping-cart"></i></div>
            <div>
                <h6 class="cart-header-title">Shopping Cart</h6>
                <p class="cart-header-sub">Check the state selected before proceeding to checkout</p>
            </div>
        </div>
        <a href="{{ route('user.products') }}" class="cart-continue-btn">
            <i class="las la-arrow-left"></i> Continue Shopping
        </a>
    </div>

    @if($cart->items->isEmpty())
    {{-- ── Empty State ── --}}
    <div class="cart-empty">
        <div class="cart-empty-icon"><i class="las la-shopping-cart"></i></div>
        <h6>Your cart is empty</h6>
        <p>Looks like you haven't added any items to your cart yet.</p>
        <a href="{{ route('user.products') }}" class="cart-empty-btn">
            <i class="las la-store"></i> Start Shopping
        </a>
    </div>

    @else
    <div class="cart-layout">

        {{-- ── Items ── --}}
        <div class="cart-items-card">
            <div class="cart-items-head">
                <i class="las la-box"></i> Cart Items ({{ $cart->items->count() }})
            </div>

            @foreach($cart->items as $item)
            <div class="cart-item" data-item-id="{{ $item->id }}">
                <div class="cart-item-img">
                    @if($item->product->thumbnail)
                        <img src="{{ getImage(getFilePath('products') . '/' . $item->product->thumbnail, getFilePath('products')) }}" alt="{{ $item->product->name }}">
                    @else
                        <div class="cart-item-img-placeholder"><i class="las la-image"></i></div>
                    @endif
                </div>
                <div class="cart-item-info">
                    <p class="cart-item-name">{{ $item->product->name }}</p>
                    <p class="cart-item-state"><x-product.sku :value="$item->product->sku" /> <i class="las la-map-marker-alt"></i> {{ $item->productstate->state->name }}</p>
                    <p class="cart-item-price">{{ showAmount($item->price, 2) }} <span>each</span></p>
                </div>
                <div class="cart-item-qty">
                    <button class="cart-qty-btn quantity-decrease" type="button" {{ $item->quantity <= 1 ? 'disabled' : '' }}>
                        <i class="las la-minus"></i>
                    </button>
                    <input class="cart-qty-input quantity-input" type="number" value="{{ $item->quantity }}" min="1" max="99">
                    <button class="cart-qty-btn quantity-increase" type="button">
                        <i class="las la-plus"></i>
                    </button>
                </div>
                <div class="cart-item-total">
                    {{ showAmount($item->price * $item->quantity, 2) }}
                </div>
                <button class="cart-item-remove remove-item" title="Remove item">
                    <i class="las la-trash-alt"></i>
                </button>
            </div>
            @endforeach
        </div>

        {{-- ── Summary ── --}}
        <div class="cart-summary-card">
            <div class="cart-summary-head">
                <i class="las la-receipt"></i> Order Summary
            </div>
            <div class="cart-summary-body">
                <div class="cart-summary-row">
                    <span>Subtotal</span>
                    <span>{{ showAmount($cart->total_amount, 2) }}</span>
                </div>
                <div class="cart-summary-row">
                    <span>Shipping</span>
                    <span class="cart-free">FREE</span>
                </div>
                <div class="cart-summary-divider"></div>
                <div class="cart-summary-row cart-summary-total">
                    <span>Total</span>
                    <span>{{ showAmount($cart->total_amount, 2) }}</span>
                </div>

                {{-- Re-purchase wallet balance --}}
                @php $productWallet = auth()->user()->product_wallet ?? 0; @endphp
                <div class="cart-wallet-box">
                    <div class="cart-wallet-row">
                        <span><i class="las la-shopping-bag"></i> Re-purchase wallet</span>
                        <span class="cart-wallet-amt">{{ showAmount($productWallet) }}</span>
                    </div>
                    @if($productWallet < $cart->total_amount)
                        <div class="cart-wallet-alert warn">
                            <i class="las la-exclamation-triangle"></i>
                            You need {{ showAmount($cart->total_amount - $productWallet) }} more in your Re-purchase wallet
                        </div>
                    @else
                        <div class="cart-wallet-alert ok">
                            <i class="las la-check-circle"></i>
                            Sufficient Re-purchase wallet balance
                        </div>
                    @endif
                </div>

                {{-- Checkout --}}
                @if($productWallet >= $cart->total_amount)
                    <a href="{{ route('user.checkout') }}" class="cart-checkout-btn">
                        <i class="las la-lock"></i> Proceed to Checkout
                    </a>
                @else
                    <button class="cart-checkout-btn disabled" disabled>
                        <i class="las la-lock"></i> Insufficient Re-purchase wallet
                    </button>
                    <p class="cart-secure-note" style="color:#DC2626;margin-top:.5rem;">
                        <i class="las la-info-circle"></i> Products are purchased using your Re-purchase wallet only.
                    </p>
                @endif

                <p class="cart-secure-note">
                    <i class="las la-shield-alt"></i> Secure checkout with SSL encryption
                </p>
            </div>
        </div>
    </div>
    @endif

</div>

@push('script')
<script>
$(document).ready(function() {
    $('.quantity-decrease').on('click', function() {
        const input = $(this).siblings('.quantity-input');
        let qty = parseInt(input.val());
        if (qty > 1) { input.val(qty - 1); updateCartItem($(this).closest('.cart-item')); }
    });

    $('.quantity-increase').on('click', function() {
        const input = $(this).siblings('.quantity-input');
        let qty = parseInt(input.val());
        if (qty < 99) { input.val(qty + 1); updateCartItem($(this).closest('.cart-item')); }
    });

    $('.quantity-input').on('change', function() {
        let qty = parseInt($(this).val());
        if (qty < 1) $(this).val(1);
        if (qty > 99) $(this).val(99);
        updateCartItem($(this).closest('.cart-item'));
    });

    $('.remove-item').on('click', function() {
        const cartItem = $(this).closest('.cart-item');
        const itemId   = cartItem.data('item-id');
        if (confirm('Remove this item from your cart?')) {
            removeCartItem(itemId, cartItem);
        }
    });

    function updateCartItem(cartItemElement) {
        const itemId  = cartItemElement.data('item-id');
        const qty     = cartItemElement.find('.quantity-input').val();
        $.ajax({
            url: "{{ route('user.cart.update', '') }}/" + itemId,
            method: 'PUT',
            data: { quantity: qty, _token: "{{ csrf_token() }}" },
            beforeSend: function() { cartItemElement.css('opacity', '.6'); },
            success: function() { location.reload(); },
            error: function() { alert('Error updating cart. Please try again.'); location.reload(); }
        });
    }

    function removeCartItem(itemId, cartItemElement) {
        $.ajax({
            url: "{{ route('user.cart.remove', '') }}/" + itemId,
            method: 'DELETE',
            data: { _token: "{{ csrf_token() }}" },
            beforeSend: function() { cartItemElement.css('opacity', '.4'); },
            success: function() { cartItemElement.slideUp(300, function() { $(this).remove(); location.reload(); }); },
            error: function() { alert('Error removing item.'); cartItemElement.css('opacity', '1'); }
        });
    }
});
</script>
@endpush

@endsection

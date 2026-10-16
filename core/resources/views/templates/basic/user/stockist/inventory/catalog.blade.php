@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="nc-wrap" id="ncWrap">
    <br>
{{-- ── Page Header ─────────────────────────────────── --}}
<div class="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="sl-page-title mb-1">Product Catalog</h4>
        <p class="sl-page-subtitle mb-0">Browse and order products for your inventory.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('user.stockist.inventory.checkout') }}" class="sl-btn sl-btn-outline position-relative">
            <i class="las la-shopping-cart me-1"></i> Cart
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartCount">
                {{ count($cart) }}
            </span>
        </a>
        <span class="sl-badge-count" style="font-size:.82rem;padding:.5rem .9rem;">
            Wallet: {{ showAmount($stockist->wallet) }}
        </span>
        <a href="{{ route('user.stockist.inventory.dashboard') }}" class="sl-btn sl-btn-outline">
            <i class="las la-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

{{-- ── Product grid ──────────────────────────────────── --}}
<div class="row g-3">
    @forelse($products as $productPrice)
    @if($productPrice->product->status != 0 && $productPrice->product->quantity > 0)
    @php
        $minQty = $productPrice->product->min_order_quantity;
        // Capped by stock; a product without a max order quantity is capped by stock alone
        $maxQty = $productPrice->product->max_order_quantity > 0
            ? min($productPrice->product->quantity, $productPrice->product->max_order_quantity)
            : $productPrice->product->quantity;
    @endphp
    <div class="col-6 col-md-6 col-xl-4">
        <div class="sl-catalog-card">
            <div class="sl-catalog-img-wrap">
                @if($productPrice->product->thumbnail)
                    <img src="{{ getImage(getFilePath('products') . '/' . $productPrice->product->thumbnail, getFileSize('products')) }}"
                         alt="{{ $productPrice->product->name }}" class="sl-catalog-img">
                @else
                    <div class="sl-catalog-img-placeholder"><i class="las la-box"></i></div>
                @endif

                @if($productPrice->product->quantity <= 0)
                    <span class="sl-status sl-status-out-of-stock sl-catalog-ribbon"><i class="las la-times me-1"></i>Out of Stock</span>
                @endif
            </div>

            <div class="sl-catalog-body">
                <h6 class="sl-catalog-name" title="{{ $productPrice->product->name }}">{{ $productPrice->product->name }}</h6>
                <div class="pp-meta"><x-product.sku :value="$productPrice->product->sku" /></div>
                <p class="sl-catalog-desc">{{ Str::limit($productPrice->product->description, 80) }}</p>

                <div class="sl-catalog-divider"></div>

                <div class="sl-catalog-price-row">
                    <x-product.price :amount="$productPrice->price" class="sl-catalog-price" />
                    <span class="sl-catalog-stock-note"><i class="las la-check-circle me-1" style="color:var(--sl-green);"></i>{{ $productPrice->product->quantity }} in stock</span>
                </div>

                <div class="sl-catalog-actions">
                    @if($productPrice->product->is_available)
                    <form class="add-to-cart-form" data-product-id="{{ $productPrice->product->id }}">
                        @csrf
                        <div class="sl-qty-stepper mb-2">
                            <button type="button" class="sl-qty-btn qty-dec" tabindex="-1">&minus;</button>
                            <input type="number"
                                   class="sl-qty-input quantity-input"
                                   name="quantity"
                                   value="{{ $minQty }}"
                                   min="{{ $minQty }}"
                                   max="{{ $maxQty }}"
                                   required>
                            <button type="button" class="sl-qty-btn qty-inc" tabindex="-1">&plus;</button>
                        </div>
                        <p class="sl-field-hint text-center mb-2">Min {{ $minQty }} &middot; Max {{ $maxQty }}</p>
                        <button type="submit" class="sl-btn sl-btn-primary add-to-cart-btn w-100">
                            <i class="las la-cart-plus me-1"></i> Add to Cart
                        </button>
                    </form>
                    @else
                    <button class="sl-btn sl-btn-outline w-100" disabled>
                        <i class="las la-times me-1"></i> Out of Stock
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    @empty
    <div class="col-12">
        <div class="sl-card">
            <div class="sl-empty-state">
                <div class="sl-empty-icon"><i class="las la-box-open"></i></div>
                <p class="sl-empty-title">No Products Available</p>
                <p class="sl-empty-sub">There are currently no products available for ordering.</p>
            </div>
        </div>
    </div>
    @endforelse
</div>

@if($products->hasPages())
<div class="sl-card mt-4">
    <div class="sl-card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <small class="text-muted">
            Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} products
        </small>
        {{ $products->links() }}
    </div>
</div>
@endif
</div>
@endsection

@push('modal')
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3" style="color:var(--sl-green);">
                    <i class="las la-check-circle" style="font-size:3.5rem;"></i>
                </div>
                <h4 class="mb-3">Added to Cart!</h4>
                <p class="text-muted mb-4" id="successMessage">Product has been added to your cart successfully.</p>
                <div class="d-grid gap-2">
                    <button type="button" class="sl-btn sl-btn-primary" data-bs-dismiss="modal">Continue Shopping</button>
                    <a href="{{ route('user.stockist.inventory.checkout') }}" class="sl-btn sl-btn-outline">Proceed to Checkout</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endpush

@push('script')
<script>
$(document).ready(function() {
    $('.add-to-cart-form').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const productId = form.data('product-id');
        const quantity = form.find('.quantity-input').val();
        const button = form.find('.add-to-cart-btn');
        const originalText = button.html();

        button.prop('disabled', true).html('<i class="las la-spinner la-spin me-1"></i>Adding...');

        $.ajax({
            url: "{{ route('user.stockist.inventory.cart.add') }}",
            method: 'POST',
            data: {
                product_id: productId,
                quantity: quantity,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    $('#cartCount').text(response.cart_count);
                    $('#successMessage').text(response.message);
                    $('#successModal').modal('show');
                    form.find('.quantity-input').val(form.find('.quantity-input').attr('min'));
                } else {
                    alert('Error: ' + response.message);
                }

                button.prop('disabled', false).html(originalText);
            },
            error: function(xhr) {
                alert('An error occurred. Please try again.');
                button.prop('disabled', false).html(originalText);
            }
        });
    });

    $('.quantity-input').on('change', function() {
        const input = $(this);
        const min = parseInt(input.attr('min'));
        const max = parseInt(input.attr('max'));
        let value = parseInt(input.val());

        if (value < min) {
            input.val(min);
        } else if (value > max) {
            input.val(max);
        }
    });

    $('.qty-dec, .qty-inc').on('click', function() {
        const input = $(this).closest('.sl-qty-stepper').find('.quantity-input');
        const min = parseInt(input.attr('min'));
        const max = parseInt(input.attr('max'));
        let value = parseInt(input.val()) || min;

        value = $(this).hasClass('qty-inc') ? value + 1 : value - 1;
        if (value < min) value = min;
        if (value > max) value = max;

        input.val(value);
    });

    function updateCartCount() {
        $.get("{{ route('user.stockist.inventory.cart.get') }}", function(response) {
            $('#cartCount').text(response.count);
        });
    }

    setInterval(updateCartCount, 30000);
});
</script>
@endpush

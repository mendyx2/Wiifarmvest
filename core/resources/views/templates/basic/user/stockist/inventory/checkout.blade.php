@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="nc-wrap" id="ncWrap">
    <br>
{{-- ── Page Header ─────────────────────────────────── --}}
<div class="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="sl-page-title mb-1">Checkout</h4>
        <p class="sl-page-subtitle mb-0">Review your order and complete purchase.</p>
    </div>
    <a href="{{ route('user.stockist.inventory.catalog') }}" class="sl-btn sl-btn-outline">
        <i class="las la-arrow-left me-1"></i> Back to Catalog
    </a>
</div>

@if(empty($cart))
    <div class="sl-card">
        <div class="sl-empty-state">
            <div class="sl-empty-icon"><i class="las la-shopping-cart"></i></div>
            <p class="sl-empty-title">Your Cart is Empty</p>
            <p class="sl-empty-sub">Add some products to your cart before checking out.</p>
            <a href="{{ route('user.stockist.inventory.catalog') }}" class="sl-btn sl-btn-primary mt-3">
                <i class="las la-shopping-cart me-1"></i> Browse Products
            </a>
        </div>
    </div>
@else
    @php $subtotal = 0; @endphp
    <div class="row g-4">
        {{-- ── LEFT: Order summary + notes ─────────────── --}}
        <div class="col-12 col-lg-8">
            <div class="sl-card mb-4">
                <div class="sl-card-header"><i class="las la-shopping-bag me-1"></i> Order Summary</div>
                <div class="sl-card-body p-0">
                    <div class="table-responsive">
                        <table class="sl-table" aria-label="Cart items">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cart as $item)
                                @php
                                    $itemTotal = $item['price'] * $item['quantity'];
                                    $subtotal += $itemTotal;
                                @endphp
                                <tr>
                                    <td data-label="Product">
                                        <div class="sl-table-name">
                                            @if($item['image'])
                                                <img src="{{ getImage(getFilePath('products') . '/' . $item['image'], getFilePath('products')) }}"
                                                     alt="{{ $item['name'] }}" class="sl-table-avatar" style="object-fit:cover;">
                                            @else
                                                <div class="sl-table-avatar" style="background:#F3F4F6;color:#9CA3AF;">
                                                    <i class="las la-box"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="fw-600 mb-0">{{ $item['name'] }}</p>
                                                <x-product.sku :value="$skus[$item['product_id']] ?? null" />
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Price">{{ showAmount($item['price']) }}</td>
                                    <td data-label="Quantity">
                                        <div class="sl-qty-stepper" style="max-width:120px;">
                                            <button type="button" class="sl-qty-btn qty-dec" tabindex="-1">&minus;</button>
                                            <input type="number"
                                                   class="sl-qty-input quantity-update"
                                                   value="{{ $item['quantity'] }}"
                                                   min="1"
                                                   max="{{ $item['max_quantity'] }}"
                                                   data-product-id="{{ $item['product_id'] }}">
                                            <button type="button" class="sl-qty-btn qty-inc" tabindex="-1">&plus;</button>
                                        </div>
                                    </td>
                                    <td data-label="Total" class="fw-700" style="color:var(--sl-green);">{{ showAmount($itemTotal) }}</td>
                                    <td data-label="">
                                        <button class="sl-btn sl-btn-outline remove-item" style="color:#DC2626;border-color:#FECACA;" data-product-id="{{ $item['product_id'] }}" title="Remove">
                                            <i class="las la-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="sl-card">
                <div class="sl-card-header"><i class="las la-sticky-note me-1"></i> Order Notes (optional)</div>
                <div class="sl-card-body">
                    <textarea class="sl-input" id="orderNotes" rows="3"
                              placeholder="Add any special instructions or notes for your order…"></textarea>
                </div>
            </div>
        </div>

        {{-- ── RIGHT: Order total ──────────────────────── --}}
        <div class="col-12 col-lg-4">
            <div class="sl-preview-card sticky-lg-top" style="top:100px;">
                <div class="sl-preview-header"><i class="las la-receipt me-2"></i>Order Total</div>
                <div class="sl-preview-body">
                    <div class="sl-preview-row">
                        <span>Subtotal</span>
                        <strong>{{ showAmount($subtotal) }}</strong>
                    </div>
                    <div class="sl-preview-row">
                        <span>Shipping</span>
                        <strong style="color:var(--sl-green);">FREE</strong>
                    </div>
                    <div class="sl-preview-divider"></div>
                    <div class="sl-preview-row sl-preview-total">
                        <span>Total</span>
                        <strong id="grandTotal">{{ showAmount($subtotal) }}</strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                        <span class="fw-600" style="font-size:.85rem;"><i class="las la-wallet me-1" style="color:var(--sl-green);"></i>Wallet Balance</span>
                        <strong style="color:var(--sl-green);">{{ showAmount($stockist->wallet) }}</strong>
                    </div>

                    @if($stockist->wallet < $subtotal)
                        <div class="alert sl-alert-warning" style="font-size:.8rem;">
                            <i class="las la-exclamation-triangle me-1"></i>
                            Insufficient balance. You need {{ showAmount($subtotal - $stockist->wallet) }} more.
                        </div>
                    @else
                        <div class="alert sl-alert-success" style="font-size:.8rem;">
                            <i class="las la-check-circle me-1"></i>
                            Sufficient balance available
                        </div>
                    @endif

                    <div class="d-grid mt-3">
                        @if($stockist->wallet >= $subtotal)
                            <button type="button" class="sl-btn sl-btn-primary" style="padding:.75rem 1rem;font-size:.9rem;" id="placeOrderBtn">
                                <i class="las la-check-circle me-1"></i> Place Order
                            </button>
                        @else
                            <button class="sl-btn sl-btn-outline" style="padding:.75rem 1rem;font-size:.9rem;opacity:.6;pointer-events:none;" disabled>
                                <i class="las la-lock me-1"></i> Insufficient Funds
                            </button>
                            <a href="#" class="sl-btn sl-btn-outline mt-2">
                                <i class="las la-plus-circle me-1"></i> Top Up Wallet
                            </a>
                        @endif
                    </div>

                    <p class="text-center text-muted mt-3 mb-0" style="font-size:.72rem;">
                        <i class="las la-shield-alt me-1"></i> Secure payment &middot; Funds deducted from wallet
                    </p>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 bg-transparent shadow-none">
            <div class="modal-body text-center">
                <div class="spinner-border mb-3" style="width:3rem;height:3rem;color:#fff;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="text-white">Processing Order…</h5>
                <p class="text-white-50 mb-0">Please wait while we process your order</p>
            </div>
        </div>
    </div>
</div>
</
@endsection

@push('modal')
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3" style="color:var(--sl-green);">
                    <i class="las la-check-circle" style="font-size:3.5rem;"></i>
                </div>
                <h4 class="mb-3">Order Placed Successfully!</h4>
                <p class="text-muted mb-4" id="successMessage"></p>
                <div class="d-grid gap-2">
                    <a href="{{ route('user.stockist.inventory.orders') }}" class="sl-btn sl-btn-primary">
                        <i class="las la-list me-1"></i> View Orders
                    </a>
                    <a href="{{ route('user.stockist.inventory.dashboard') }}" class="sl-btn sl-btn-outline">
                        <i class="las la-tachometer-alt me-1"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endpush

@push('script')
<script>
$(document).ready(function() {
    let grandTotal = {{ $subtotal * 1.05 }};

    $('.qty-dec, .qty-inc').on('click', function() {
        const input = $(this).closest('.sl-qty-stepper').find('.quantity-update');
        const min = parseInt(input.attr('min'));
        const max = parseInt(input.attr('max'));
        let value = parseInt(input.val()) || min;

        value = $(this).hasClass('qty-inc') ? value + 1 : value - 1;
        if (value < min) value = min;
        if (value > max) value = max;

        input.val(value).trigger('change');
    });

    $('.quantity-update').on('change', function() {
        const productId = $(this).data('product-id');
        const quantity = $(this).val();

        $.ajax({
            url: "{{ route('user.stockist.inventory.cart.update') }}",
            method: 'POST',
            data: {
                product_id: productId,
                quantity: quantity,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            },
            error: function(xhr) {
                alert('Error updating quantity. Please try again.');
                location.reload();
            }
        });
    });

    $('.remove-item').on('click', function() {
        const productId = $(this).data('product-id');
        if (confirm('Are you sure you want to remove this item from your cart?')) {
            $.ajax({
                url: "{{ route('user.stockist.inventory.cart.update') }}",
                method: 'POST',
                data: {
                    product_id: productId,
                    quantity: 0,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                },
                error: function(xhr) {
                    alert('Error removing item. Please try again.');
                    location.reload();
                }
            });
        }
    });

    $('#placeOrderBtn').on('click', function() {
        const notes = $('#orderNotes').val();
        const button = $(this);
        const originalText = button.html();

        $('#loadingModal').modal('show');

        $.ajax({
            url: "{{ route('user.stockist.inventory.order.place') }}",
            method: 'POST',
            data: {
                notes: notes,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                $('#loadingModal').modal('hide');

                if (response.success) {
                    $('#successMessage').html(`
                        Your order <strong>${response.order_number}</strong> has been placed successfully!<br>
                        Order ID: <strong>#${response.order_id}</strong>
                    `);
                    $('#successModal').modal('show');
                } else {
                    alert('Error: ' + response.message);
                    button.html(originalText);
                }
            },
            error: function(xhr) {
                $('#loadingModal').modal('hide');
                alert('An error occurred while placing your order. Please try again.');
                button.html(originalText);
            }
        });
    });
});
</script>
@endpush

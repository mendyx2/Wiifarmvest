@extends($activeTemplate . 'layouts.master')
@section('title', 'Checkout')
@section('content')

<div class="chk-page">

    <div class="messageProcessing"></div>

    @if(!$cart || $cart->items->isEmpty())
    <div class="chk-empty">
        <div class="chk-empty-icon"><i class="las la-shopping-cart"></i></div>
        <h6>Your cart is empty!</h6>
        <p>Add some products to your cart before proceeding to checkout.</p>
        <a href="{{ route('user.products') }}" class="chk-back-btn"><i class="las la-store"></i> Continue Shopping</a>
    </div>
    @else

    <div class="chk-layout">

        {{-- ── Order Summary ── --}}
        <div class="chk-items-card">
            <div class="chk-card-head">
                <i class="las la-shopping-cart"></i> Order Summary
            </div>
            <div class="chk-items-body">
                @foreach($cart->items as $item)
                <div class="chk-item">
                    <div class="chk-item-img">
                        @if($item->product->thumbnail)
                            <img src="{{ getImage(getFilePath('products') . '/' . $item->product->thumbnail, getFilePath('products')) }}" alt="{{ $item->product->name }}">
                        @else
                            <div class="chk-item-placeholder"><i class="las la-image"></i></div>
                        @endif
                    </div>
                    <div class="chk-item-info">
                        <p class="chk-item-name">{{ $item->product->name }}</p>
                        <p class="chk-item-state"><x-product.sku :value="$item->product->sku" /> <i class="las la-map-marker-alt"></i> {{ $item->productstate->state->name }}</p>
                    </div>
                    <div class="chk-item-right">
                        <span class="chk-item-qty">×{{ $item->quantity }}</span>
                        <span class="chk-item-total">{{ showAmount($item->price * $item->quantity) }}</span>
                        <small class="chk-item-each">{{ showAmount($item->price, 2) }} each</small>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Info card --}}
            <div class="chk-info-box">
                <div class="chk-info-title"><i class="las la-info-circle"></i> Important Information</div>
                <ul class="chk-info-list">
                    <li><i class="las la-receipt"></i> You will receive an invoice code after payment</li>
                    <li><i class="las la-store"></i> Present the code at any authorised stockist to redeem</li>
                    <li><i class="las la-clock"></i> Please do not share code with members</li>
                    <!--
                    <li><i class="las la-undo"></i> Refunds are processed within 7 business days</li> 
                    -->
                </ul>
            </div>
        </div>

        {{-- ── Payment Panel ── --}}
        <div class="chk-pay-card">
            <div class="chk-card-head chk-card-head--pay">
                <i class="las la-credit-card"></i> Payment Summary
            </div>
            <div class="chk-pay-body">
                <div class="chk-pay-row">
                    <span>Subtotal</span>
                    <span>{{ showAmount($cart->total_amount, 2) }}</span>
                </div>
                <div class="chk-pay-row">
                    <span>Shipping</span>
                    <span class="chk-free">FREE</span>
                </div>
                <div class="chk-pay-divider"></div>
                <div class="chk-pay-row chk-pay-total">
                    <span>Total</span>
                    <span>{{ showAmount($cart->total_amount, 2) }}</span>
                </div>

                @php $productWallet = auth()->user()->product_wallet ?? 0; @endphp
                <div class="chk-wallet-box">
                    <div class="chk-wallet-row">
                        <span><i class="las la-shopping-bag"></i> Re-purchase wallet</span>
                        <span class="chk-wallet-amt">{{ showAmount($productWallet, 2) }}</span>
                    </div>
                    @if($productWallet < $cart->total_amount)
                        <div class="chk-wallet-alert warn">
                            <i class="las la-exclamation-triangle"></i>
                            You need {{ showAmount($cart->total_amount - $productWallet) }} more in your Re-purchase wallet to complete this purchase.
                        </div>
                    @else
                        <div class="chk-wallet-alert ok">
                            <i class="las la-check-circle"></i>
                            Your Re-purchase wallet has sufficient funds.
                        </div>
                    @endif
                </div>

                @if($productWallet >= $cart->total_amount)
                    <button type="button" id="pay-now" class="chk-pay-btn">
                        <i class="las la-lock"></i> Pay Now
                    </button>
                @else
                    <button type="button" class="chk-pay-btn disabled" disabled>
                        <i class="las la-lock"></i> Insufficient Re-purchase wallet
                    </button>
                    <p class="chk-secure mt-2" style="color:#DC2626;">
                        <i class="las la-info-circle"></i> Products can only be purchased using your Re-purchase wallet balance.
                    </p>
                @endif

                <a href="{{ route('user.cart.index') }}" class="chk-back-link">
                    <i class="las la-arrow-left"></i> Back to Cart
                </a>

                <p class="chk-secure">
                    <i class="las la-shield-alt"></i> Your payment is secure and encrypted
                </p>
            </div>
        </div>

    </div>
    @endif

</div>

@push('modal')
<div class="modal fade" id="processingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pd-modal-content">
            <div class="pd-modal-header">
                <div class="pd-modal-icon"><i class="las la-spinner"></i></div>
                <h6 class="pd-modal-title">Processing Payment</h6>
            </div>
            <div class="pd-modal-body" style="text-align:center;">
                <div class="chk-spinner"></div>
                <p>Please wait while we process your payment...</p>
                <p style="font-size:.78rem;color:#9CA3AF;">Do not refresh or close this page.</p>
            </div>
        </div>
    </div>
</div>
@endpush

@push('script')
<script>
$(document).ready(function() {
    $('#pay-now').on('click', function() {
        const $btn = $(this);
        const orig = $btn.html();
        $btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> Processing...');
        $('#processingModal').modal('show');

        $.ajax({
            url: "{{ route('user.process.payment') }}",
            method: 'POST',
            data: { _token: "{{ csrf_token() }}" },
            success: function(response) {
                $('#processingModal').modal('hide');
                if (response.success) {
                    showAlert('success', 'Payment successful! Redirecting...');
                    setTimeout(function() { window.location.href = response.redirect_url; }, 1000);
                } else {
                    showAlert('error', response.message);
                    $btn.prop('disabled', false).html(orig);
                }
            },
            error: function(xhr) {
                $('#processingModal').modal('hide');
                const msg = xhr.responseJSON?.message || 'An error occurred while processing your payment.';
                showAlert('error', msg);
                $btn.prop('disabled', false).html(orig);
            }
        });
    });

    function showAlert(type, message) {
        const cls = type === 'success' ? 'ok' : 'warn';
        const icon = type === 'success' ? 'la-check-circle' : 'la-exclamation-triangle';
        const html = `<div class="chk-alert chk-alert--${cls}"><i class="las ${icon}"></i> ${message} <button onclick="this.parentElement.remove()" class="chk-alert-close">&times;</button></div>`;
        $('.messageProcessing').html(html);
    }
});
</script>
@endpush

@endsection

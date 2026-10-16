@extends($activeTemplate . 'layouts.shop')
@section('content')

<div class="shop-wrap">
    <div class="shop-container" style="max-width:480px;">
        <div class="shop-success-card">
            <div class="shop-success-icon"><i class="las la-lock"></i></div>
            <h4 class="fw-bold mb-1">Complete Your Payment</h4>
            <p class="text-muted">Order <strong>{{ $order->order_code }}</strong></p>
            <div class="shop-detail-price">{{ getAmount($order->total_amount) }}</div>
            <button type="button" class="shop-buy-btn w-100 mt-3" id="payNowBtn">
                <i class="las la-credit-card"></i> Pay Now
            </button>
        </div>
    </div>
</div>

<script src="//js.paystack.co/v1/inline.js"></script>
<script>
document.getElementById('payNowBtn').addEventListener('click', function () {
    var handler = PaystackPop.setup({
        key:      @json($paystack['key']),
        email:    @json($paystack['email']),
        amount:   {{ (int) $paystack['amount'] }},
        currency: @json($paystack['currency']),
        ref:      @json($paystack['ref']),
        metadata: @json($paystack['metadata']),
        callback: function (response) {
            window.location.href = @json($paystack['callback_url']) + '?reference=' + encodeURIComponent(response.reference);
        },
        onClose: function () {}
    });
    handler.openIframe();
});
</script>
@endsection

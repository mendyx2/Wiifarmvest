@extends($activeTemplate . 'layouts.shop')
@section('content')

<div class="shop-wrap">
    <div class="shop-container" style="max-width:560px;">
        <div class="shop-success-card">
            <div class="shop-success-icon"><i class="las la-check"></i></div>
            <h4 class="fw-bold mb-1">Order Confirmed</h4>
            <p class="text-muted mb-0">
                @if($order->payment_method === 'cash_on_pickup')
                    Pay when you pick up your order at any partner location.
                @else
                    Your payment was received. Present your code to collect your order.
                @endif
            </p>

            <div class="shop-code-box">
                <div class="text-muted small text-uppercase fw-bold mb-1">Redemption Code</div>
                <div class="shop-code-val" id="redemptionCode">{{ $order->order_code }}</div>
                <button type="button" class="shop-copy-btn" id="copyCodeBtn"><i class="las la-copy"></i> Copy Code</button>
            </div>

            <table class="shop-detail-table">
                <tbody>
                    <tr><td>Order Total</td><td>{{ showAmount($order->total_amount) }} {{ gs('cur_text') }}</td></tr>
                    <tr><td>Payment Method</td><td>{{ $order->payment_method === 'paystack' ? 'Paid Online' : 'Cash on Pickup' }}</td></tr>
                    <tr><td>Pickup State</td><td>{{ $order->state->name ?? '-' }}</td></tr>
                    <tr><td>Status</td><td>{!! $order->status_badge !!}</td></tr>
                </tbody>
            </table>

            <a href="{{ route('shop.order.invoice', $order->order_code) }}" class="shop-buy-btn mt-3">
                <i class="las la-file-pdf"></i> Download Invoice
            </a>
            <p class="text-muted small mt-3 mb-0">A copy of your invoice and this code has also been emailed to {{ $order->buyer_email }}.</p>
        </div>
    </div>
</div>

<script>
document.getElementById('copyCodeBtn').addEventListener('click', function () {
    var btn = this;
    var code = document.getElementById('redemptionCode').textContent.trim();
    navigator.clipboard.writeText(code).then(function () {
        btn.classList.add('copied');
        btn.innerHTML = '<i class="las la-check"></i> Copied!';
        setTimeout(function () {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="las la-copy"></i> Copy Code';
        }, 2000);
    });
});
</script>
@endsection

@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="nc-wrap" id="ncWrap">
    <br>
    <div class="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="sl-page-title mb-1">Affiliate Dashboard</h4>
            <p class="sl-page-subtitle mb-0">Share your link, earn a bonus on every order.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('user.affiliate.ads') }}" class="sl-btn sl-btn-primary">
                <i class="las la-bullhorn me-1"></i> Ad Center
                @if ($adsCount)
                    <span class="adc-pill">{{ $adsCount }}</span>
                @endif
            </a>
            <a href="{{ route('user.affiliate.orders') }}" class="sl-btn sl-btn-outline">
                <i class="las la-box me-1"></i> My Sales
            </a>
        </div>
    </div>

    <div class="aff-link-box mb-4">
        <div>
            <div class="fw-bold mb-1">Your Affiliate Link</div>
            <code id="affLinkCode">{{ $shopLink }}</code>
        </div>
        <button type="button" class="aff-copy-btn" id="affCopyBtn">
            <i class="las la-copy"></i> Copy Link
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="aff-stat-card">
                <div class="aff-stat-val">{{ number_format($clicksCount) }}</div>
                <div class="aff-stat-lbl">Total Clicks</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="aff-stat-card">
                <div class="aff-stat-val">{{ number_format($ordersCount) }}</div>
                <div class="aff-stat-lbl">Orders Placed</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="aff-stat-card">
                <div class="aff-stat-val">{{ number_format($fulfilledCount) }}</div>
                <div class="aff-stat-lbl">Fulfilled</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="aff-stat-card">
                <div class="aff-stat-val">{{ showAmount($user->affiliate_bonus_balance) }}</div>
                <div class="aff-stat-lbl">Bonus Balance</div>
            </div>
        </div>
    </div>

    <div class="aff-panel">
        <div class="aff-panel-header">Recent Orders</div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>Code</th><th>Buyer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        <tr>
                            <td><code>{{ $order->order_code }}</code></td>
                            <td>{{ $order->buyer_name }}</td>
                            <td>{{ showAmount($order->total_amount) }}</td>
                            <td>{{ $order->payment_method === 'paystack' ? 'Online' : 'Cash on Pickup' }}</td>
                            <td>{!! $order->status_badge !!}</td>
                            <td>{{ $order->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No orders yet. Share your link to get started.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-3">
        Your affiliate bonus is credited to <strong>{{ showAmount($user->affiliate_bonus_balance) }}</strong> and can be moved to your Money Box from
        <a href="{{ route('user.bonus.transfer.index') }}">Bonus Transfer</a>.
    </p>
</div>

<script>
document.getElementById('affCopyBtn').addEventListener('click', function () {
    var btn = this;
    navigator.clipboard.writeText(document.getElementById('affLinkCode').textContent.trim()).then(function () {
        btn.innerHTML = '<i class="las la-check"></i> Copied!';
        setTimeout(function () { btn.innerHTML = '<i class="las la-copy"></i> Copy Link'; }, 2000);
    });
});
</script>
@endsection

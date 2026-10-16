@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="nc-wrap" id="ncWrap">
    <br>
    <div class="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="sl-page-title mb-1">My Affiliate Sales</h4>
            <p class="sl-page-subtitle mb-0">Every order placed through your affiliate link.</p>
        </div>
        <a href="{{ route('user.affiliate.dashboard') }}" class="sl-btn sl-btn-outline">
            <i class="las la-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <div class="aff-panel">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>Code</th><th>Buyer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td><code>{{ $order->order_code }}</code></td>
                            <td>{{ $order->buyer_name }}<br><span class="text-muted small">{{ $order->buyer_email }}</span></td>
                            <td>{{ $order->items->sum('quantity') }}</td>
                            <td>{{ showAmount($order->total_amount) }}</td>
                            <td>{{ $order->payment_method === 'paystack' ? 'Online' : 'Cash on Pickup' }}</td>
                            <td>{!! $order->status_badge !!}</td>
                            <td>{{ $order->created_at->format('M d, Y g:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No orders yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $orders->links() }}</div>
</div>
@endsection

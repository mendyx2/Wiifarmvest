@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="nc-wrap" id="ncWrap">
    <br>
{{-- ── Page Header ─────────────────────────────────── --}}
<div class="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="sl-page-title mb-1">{{ $canOrderFromCompany ? 'Order History' : 'Earlier Company Orders' }}</h4>
        <p class="sl-page-subtitle mb-0">
            @if ($canOrderFromCompany)
                Track and manage your product orders.
            @else
                Orders you placed with the company before. Mini stockists now buy their stock from a Mega stockist in their state.
            @endif
        </p>
    </div>
    @if ($canOrderFromCompany)
        <a href="{{ route('user.stockist.inventory.catalog') }}" class="sl-btn sl-btn-primary">
            <i class="las la-plus me-1"></i> New Order
        </a>
    @else
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('user.stockist.transfers.index') }}" class="sl-btn sl-btn-outline">
                <i class="las la-shopping-basket me-1"></i> My Orders
            </a>
            <a href="{{ route('user.stockist.transfers.create') }}" class="sl-btn sl-btn-primary">
                <i class="las la-truck-loading me-1"></i> Buy from Mega
            </a>
        </div>
    @endif
</div>

{{-- ── Stats cards ───────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-primary">
            <div class="sl-stat-icon"><i class="las la-shopping-bag"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Total Orders</p>
                <h3 class="sl-stat-value">{{ $orders->total() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-gold">
            <div class="sl-stat-icon"><i class="las la-clock"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Pending</p>
                <h3 class="sl-stat-value">{{ $orders->where('status', 'pending')->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-info">
            <div class="sl-stat-icon"><i class="las la-check-circle"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Approved</p>
                <h3 class="sl-stat-value">{{ $orders->where('status', 'approved')->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-success">
            <div class="sl-stat-icon"><i class="las la-truck"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Delivered</p>
                <h3 class="sl-stat-value">{{ $orders->where('status', 'delivered')->count() }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- ── Orders table ─────────────────────────────────── --}}
<div class="sl-card">
    <div class="sl-card-header"><i class="las la-history me-1"></i> Recent Orders</div>
    <div class="sl-card-body p-0">
        @if($orders->isEmpty())
            <div class="sl-empty-state">
                <div class="sl-empty-icon"><i class="las la-shopping-bag"></i></div>
                <p class="sl-empty-title">No Orders Yet</p>
                @if ($canOrderFromCompany)
                    <p class="sl-empty-sub">You haven't placed any orders yet.</p>
                    <a href="{{ route('user.stockist.inventory.catalog') }}" class="sl-btn sl-btn-primary mt-3">
                        <i class="las la-shopping-cart me-1"></i> Place Your First Order
                    </a>
                @else
                    <p class="sl-empty-sub">No company orders. Your stock comes from a Mega stockist in your state.</p>
                    <a href="{{ route('user.stockist.transfers.create') }}" class="sl-btn sl-btn-primary mt-3">
                        <i class="las la-truck-loading me-1"></i> Buy from Mega
                    </a>
                @endif
            </div>
        @else
            <div class="table-responsive">
                <table class="sl-table" aria-label="Order history">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orders as $order)
                        <tr>
                            <td data-label="Order #"><span class="fw-700">{{ $order->order_number }}</span></td>
                            <td data-label="Date">
                                <small class="text-muted">
                                    {{ $order->created_at->format('M j, Y') }}<br>
                                    {{ $order->created_at->format('g:i A') }}
                                </small>
                            </td>
                            <td data-label="Items">
                                <small>
                                    {{ $order->items->count() }} item(s)<br>
                                    <span class="text-muted">{{ $order->items->sum('quantity') }} units</span>
                                </small>
                            </td>
                            <td data-label="Total Amount" class="fw-700" style="color:var(--sl-green);">{{ showAmount($order->grand_total) }}</td>
                            <td data-label="Status">
                                @switch($order->status)
                                    @case('pending')
                                        <span class="sl-status sl-status-pending"><i class="las la-clock me-1"></i>Pending</span>
                                        @break
                                    @case('approved')
                                        <span class="sl-status sl-status-approved"><i class="las la-check me-1"></i>Approved</span>
                                        @break
                                    @case('processing')
                                        <span class="sl-status sl-status-processing"><i class="las la-cog me-1"></i>Processing</span>
                                        @break
                                    @case('shipped')
                                        <span class="sl-status sl-status-shipped"><i class="las la-shipping-fast me-1"></i>Shipped</span>
                                        @break
                                    @case('delivered')
                                        <span class="sl-status sl-status-delivered"><i class="las la-check-circle me-1"></i>Delivered</span>
                                        @break
                                    @case('cancelled')
                                        <span class="sl-status sl-status-cancelled"><i class="las la-times me-1"></i>Cancelled</span>
                                        @break
                                    @default
                                        <span class="sl-status sl-status-closed">{{ $order->status }}</span>
                                @endswitch
                            </td>
                            <td data-label="Actions">
                                <a href="{{ route('user.stockist.inventory.order.details', $order->id) }}" class="sl-action-btn">
                                    View <i class="las la-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3" style="border-top:1px solid var(--sl-border);">
                <small class="text-muted">
                    Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} orders
                </small>
                {{ $orders->links() }}
            </div>
            @endif
        @endif
    </div>
</div>
</div>
@endsection

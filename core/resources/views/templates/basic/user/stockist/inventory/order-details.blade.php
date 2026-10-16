@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="nc-wrap" id="ncWrap">
    <br>
{{-- ── Page Header ─────────────────────────────────── --}}
<div class="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="sl-page-title mb-1">Order Details</h4>
        <p class="sl-page-subtitle mb-0">Order #{{ $order->order_number }}</p>
    </div>
    <a href="{{ route('user.stockist.inventory.orders') }}" class="sl-btn sl-btn-outline">
        <i class="las la-arrow-left me-1"></i> Back to Orders
    </a>
</div>

<div class="row g-4">
    {{-- ── LEFT: Status, items, notes ──────────────────── --}}
    <div class="col-12 col-lg-8">

        <div class="sl-card mb-4">
            <div class="sl-card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="sl-status-icon sl-status-icon-{{ $order->status }}">
                            @switch($order->status)
                                @case('pending') <i class="las la-clock"></i> @break
                                @case('approved') <i class="las la-check"></i> @break
                                @case('processing') <i class="las la-cog"></i> @break
                                @case('shipped') <i class="las la-shipping-fast"></i> @break
                                @case('delivered') <i class="las la-check-circle"></i> @break
                                @case('cancelled') <i class="las la-times"></i> @break
                            @endswitch
                        </div>
                        <div>
                            <h5 class="fw-700 text-capitalize mb-1">{{ $order->status }}</h5>
                            <p class="text-muted mb-0" style="font-size:.82rem;">
                                Order placed on {{ $order->created_at->format('F j, Y \a\t g:i A') }}
                            </p>
                            @if($order->approved_at)
                                <p class="mb-0" style="font-size:.78rem;color:var(--sl-green);">Approved on {{ $order->approved_at->format('F j, Y') }}</p>
                            @endif
                            @if($order->delivered_at)
                                <p class="mb-0" style="font-size:.78rem;color:var(--sl-green);">Delivered on {{ $order->delivered_at->format('F j, Y') }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <h3 class="fw-800 mb-0" style="color:var(--sl-green);">{{ showAmount($order->grand_total) }}</h3>
                        <small class="text-muted">Total Amount</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="sl-card mb-4">
            <div class="sl-card-header"><i class="las la-boxes me-1"></i> Order Items</div>
            <div class="sl-card-body p-0">
                <div class="table-responsive">
                    <table class="sl-table" aria-label="Order items">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr>
                                <td data-label="Product">
                                    <div class="sl-table-name">
                                        @if($item->product->thumbnail)
                                            <img src="{{ getImage(getFilePath('products') . '/' . $item->product->thumbnail, getFilePath('products')) }}"
                                                 alt="{{ $item->product->name }}" class="sl-table-avatar" style="object-fit:cover;">
                                        @else
                                            <div class="sl-table-avatar" style="background:#F3F4F6;color:#9CA3AF;">
                                                <i class="las la-box"></i>
                                            </div>
                                        @endif
                                        <div>
                                            <p class="fw-600 mb-0">{{ $item->product->name }}</p>
                                            <x-product.sku :value="$item->product->sku" />
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Price">{{ showAmount($item->unit_price) }}</td>
                                <td data-label="Quantity">{{ $item->quantity }}</td>
                                <td data-label="Total" class="fw-600" style="color:var(--sl-green);">{{ showAmount($item->total_price) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-700">Subtotal:</td>
                                <td class="fw-700">{{ showAmount($order->total_amount) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-700">Tax (5%):</td>
                                <td class="fw-700">{{ showAmount($order->tax_amount) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-700">Shipping:</td>
                                <td class="fw-700" style="color:var(--sl-green);">{{ showAmount($order->shipping_cost) }}</td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end fw-800">Grand Total:</td>
                                <td class="fw-800" style="color:var(--sl-green);">{{ showAmount($order->grand_total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        @if($order->notes)
        <div class="sl-card">
            <div class="sl-card-header"><i class="las la-sticky-note me-1"></i> Order Notes</div>
            <div class="sl-card-body">
                <p class="text-muted mb-0">{{ $order->notes }}</p>
            </div>
        </div>
        @endif
    </div>

    {{-- ── RIGHT: Summary + timeline ───────────────────── --}}
    <div class="col-12 col-lg-4">
        <div class="sl-card mb-4">
            <div class="sl-card-header"><i class="las la-file-invoice me-1"></i> Order Summary</div>
            <div class="sl-card-body">
                <div class="sl-info-row">
                    <span class="fw-600">Order Number</span>
                    <code>{{ $order->order_number }}</code>
                </div>
                <div class="sl-info-row">
                    <span class="fw-600">Order Date</span>
                    <span>{{ $order->created_at->format('M j, Y') }}</span>
                </div>
                <div class="sl-info-row">
                    <span class="fw-600">Items</span>
                    <span>{{ $order->items->count() }}</span>
                </div>
                <div class="sl-info-row">
                    <span class="fw-600">Total Units</span>
                    <span>{{ $order->items->sum('quantity') }}</span>
                </div>
                <div class="sl-info-row">
                    <span class="fw-600">Payment Method</span>
                    <span style="color:var(--sl-green);">Wallet</span>
                </div>
            </div>
        </div>

        <div class="sl-card">
            <div class="sl-card-header"><i class="las la-history me-1"></i> Order Timeline</div>
            <div class="sl-card-body">
                <div class="sl-timeline-item">
                    <div class="sl-timeline-icon" style="background:var(--sl-green);"><i class="las la-shopping-cart"></i></div>
                    <div>
                        <p class="fw-600 mb-0">Order Placed</p>
                        <small class="text-muted">{{ $order->created_at->format('M j, Y \a\t g:i A') }}</small>
                    </div>
                </div>

                @if($order->approved_at)
                <div class="sl-timeline-item">
                    <div class="sl-timeline-icon" style="background:var(--sl-blue);"><i class="las la-check"></i></div>
                    <div>
                        <p class="fw-600 mb-0">Order Approved</p>
                        <small class="text-muted">{{ $order->approved_at->format('M j, Y \a\t g:i A') }}</small>
                    </div>
                </div>
                @endif

                @if($order->shipped_at)
                <div class="sl-timeline-item">
                    <div class="sl-timeline-icon" style="background:#6366F1;"><i class="las la-shipping-fast"></i></div>
                    <div>
                        <p class="fw-600 mb-0">Order Shipped</p>
                        <small class="text-muted">{{ $order->shipped_at->format('M j, Y \a\t g:i A') }}</small>
                    </div>
                </div>
                @endif

                @if($order->delivered_at)
                <div class="sl-timeline-item">
                    <div class="sl-timeline-icon" style="background:var(--sl-green);"><i class="las la-check-circle"></i></div>
                    <div>
                        <p class="fw-600 mb-0">Order Delivered</p>
                        <small class="text-muted">{{ $order->delivered_at->format('M j, Y \a\t g:i A') }}</small>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
@endsection

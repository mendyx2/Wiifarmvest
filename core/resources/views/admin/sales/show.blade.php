@extends('admin.layouts.app')
@section('panel')

{{-- ─────────────────────────────────────────────
     BACK BUTTON + PAGE HEADER
────────────────────────────────────────────── --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <a href="{{ route('admin.sales.index') }}" class="btn btn-sm btn-outline--primary mb-2">
            <i class="las la-arrow-left me-1"></i>Back to Sales
        </a>
        <h4 class="mb-1 fw-bold">
            <i class="las la-file-invoice text--primary me-2"></i>Sale Detail
        </h4>
        <p class="text-muted mb-0 small">
            Order placed on {{ $order->created_at->format('D, d M Y \a\t h:i A') }}
        </p>
    </div>

    {{-- Overall redemption badge --}}
    <div class="text-end">
        @php
            $isFullyRedeem = $invoice ? $invoice->is_fully_redeemed : false;
            $hasPartial    = $invoice && $invoice->redemptions->count() > 0 && !$isFullyRedeem;
        @endphp
        @if($isFullyRedeem)
            <span class="status-hero-badge badge--success">
                <i class="las la-check-double me-1"></i>Fully Redeemed
            </span>
        @elseif($hasPartial)
            <span class="status-hero-badge badge--warning">
                <i class="las la-adjust me-1"></i>Partially Redeemed
            </span>
        @else
            <span class="status-hero-badge badge--danger">
                <i class="las la-times-circle me-1"></i>Not Redeemed
            </span>
        @endif
    </div>
</div>

{{-- ─────────────────────────────────────────────
     ORDER META CARDS ROW
────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">

    {{-- Customer --}}
    <div class="col-md-3 col-sm-6">
        <div class="meta-card">
            <div class="meta-icon bg--primary">
                <i class="las la-user"></i>
            </div>
            <div class="meta-body">
                <p class="meta-label">Customer</p>
                <p class="meta-value">{{ $order->user->username ?? '—' }}</p>
                @if($order->user)
                    <p class="meta-sub">{{ trim($order->user->firstname . ' ' . $order->user->lastname) }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- State --}}
    <div class="col-md-3 col-sm-6">
        <div class="meta-card">
            <div class="meta-icon bg--info">
                <i class="las la-map-marker-alt"></i>
            </div>
            <div class="meta-body">
                <p class="meta-label">State</p>
                <p class="meta-value">{{ optional($order->state)->name ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Invoice Code --}}
    <div class="col-md-3 col-sm-6">
        <div class="meta-card">
            <div class="meta-icon bg--warning">
                <i class="las la-file-invoice"></i>
            </div>
            <div class="meta-body">
                <p class="meta-label">Invoice Code</p>
                <p class="meta-value inv-code-sm">
                    {{ $invoice ? $invoice->invoice_code : $order->invoice_code }}
                </p>
            </div>
        </div>
    </div>

    {{-- Total Amount --}}
    <div class="col-md-3 col-sm-6">
        <div class="meta-card">
            <div class="meta-icon bg--success">
                <i class="las la-money-bill-wave"></i>
            </div>
            <div class="meta-body">
                <p class="meta-label">Total Amount</p>
                <p class="meta-value amount-hero">
                    {{ showAmount($invoice ? $invoice->total_amount : $order->total_amount) }}
                </p>
            </div>
        </div>
    </div>
</div>

{{-- ─────────────────────────────────────────────
     PRODUCTS IN THIS ORDER
────────────────────────────────────────────── --}}
<div class="card b-radius--10 mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="las la-box-open text--primary me-2"></i>
            Products in This Order
            <span class="badge badge--primary ms-2">{{ $order->items->count() }}</span>
        </h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table--light style--two mb-0">
                <thead>
                    <tr>
                        <th style="width:60px">Image</th>
                        <th>Product</th>
                        <th class="text-center">Qty Ordered</th>
                        <th class="text-center">Qty Redeemed</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->items as $item)
                        @php
                            $product      = $item->product;
                            $productId    = $item->product_id;
                            $orderedQty   = $item->quantity;
                            $redeemedQty  = $redeemedQtyMap[$productId] ?? 0;
                            $remainingQty = max(0, $orderedQty - $redeemedQty);
                            $isItemFull   = $remainingQty === 0;
                        @endphp
                        <tr>
                            {{-- Product Image --}}
                            <td>
                                @if($product && $product->thumbnail)
                                    <img
                                        src="{{ getImage(getFilePath('products') . '/' . $product->thumbnail, getFileSize('products')) }}"
                                        alt="{{ $product->name ?? 'Product' }}"
                                        class="product-thumb"
                                        onerror="this.src='{{ asset('assets/images/placeholder.png') }}'"
                                    >
                                @else
                                    <div class="product-thumb-placeholder">
                                        <i class="las la-image"></i>
                                    </div>
                                @endif
                            </td>

                            {{-- Product Name --}}
                            <td>
                                <span class="fw-semibold">{{ $product->name ?? 'Deleted Product' }}</span>
                                @if($product && $product->sku ?? false)
                                    <br><small class="text-muted">SKU: {{ $product->sku }}</small>
                                @endif
                            </td>

                            {{-- Qty Ordered --}}
                            <td class="text-center">
                                <span class="qty-badge">{{ $orderedQty }}</span>
                            </td>

                            {{-- Qty Redeemed --}}
                            <td class="text-center">
                                @if($redeemedQty > 0)
                                    <span class="qty-badge redeemed">{{ $redeemedQty }}</span>
                                @else
                                    <span class="qty-badge none">0</span>
                                @endif
                            </td>

                            {{-- Unit Price --}}
                            <td class="text-end">
                                {{ showAmount($item->price) }}
                            </td>

                            {{-- Subtotal --}}
                            <td class="text-end fw-bold">
                                {{ showAmount($item->price * $orderedQty) }}
                            </td>

                            {{-- Status --}}
                            <td class="text-center">
                                @if($isItemFull)
                                    <span class="badge badge--success item-status-badge">
                                        <i class="las la-check-circle me-1"></i>Redeemed
                                    </span>
                                @elseif($redeemedQty > 0)
                                    <span class="badge badge--warning item-status-badge">
                                        <i class="las la-adjust me-1"></i>Partial
                                    </span>
                                @else
                                    <span class="badge badge--danger item-status-badge">
                                        <i class="las la-clock me-1"></i>Pending
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No products found for this order.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                {{-- Order Total Footer Row --}}
                @if($order->items->count() > 0)
                <tfoot>
                    <tr class="order-total-row">
                        <td colspan="5" class="text-end fw-bold py-3">
                            <i class="las la-calculator me-1"></i>Order Total
                        </td>
                        <td class="text-end fw-bold py-3 total-amount-cell">
                            {{ showAmount($invoice ? $invoice->total_amount : $order->total_amount) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- ─────────────────────────────────────────────
     REDEMPTION HISTORY
────────────────────────────────────────────── --}}
@if($invoice && $invoice->redemptions->count() > 0)
<div class="card b-radius--10 mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">
            <i class="las la-store text--success me-2"></i>
            Redemption History
            <span class="badge badge--success ms-2">{{ $invoice->redemptions->count() }}</span>
        </h5>
    </div>
    <div class="card-body p-0">
        @foreach($invoice->redemptions as $rIndex => $redemption)
            @php
                $stockist = $redemption->stockist;
                $items    = (array) $redemption->redeemed_items;
            @endphp
            <div class="redemption-block {{ !$loop->last ? 'border-bottom' : '' }}">

                {{-- Redemption header --}}
                <div class="redemption-header">
                    <div class="d-flex flex-wrap align-items-center gap-3">

                        {{-- Stockist info --}}
                        <div class="stockist-info">
                            <div class="stockist-avatar">
                                <i class="las la-store-alt"></i>
                            </div>
                            <div>
                                <p class="stockist-name mb-0">
                                    {{ $stockist->business_name ?? 'Unknown Stockist' }}
                                </p>
                                @if($stockist && $stockist->user)
                                    <p class="stockist-sub mb-0">
                                        @username: {{ $stockist->user->username }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- Amount & date --}}
                        <div class="ms-auto text-end">
                            <p class="mb-0 fw-bold text--success">
                                {{ showAmount($redemption->total_amount) }}
                            </p>
                            <p class="text-muted small mb-0">
                                <i class="las la-clock me-1"></i>
                                {{ $redemption->redeemed_at
                                    ? $redemption->redeemed_at->format('d M Y, h:i A')
                                    : ($redemption->created_at ? $redemption->created_at->format('d M Y, h:i A') : '—')
                                }}
                            </p>
                        </div>
                    </div>

                    {{-- Notes if any --}}
                    @if($redemption->notes)
                        <div class="redemption-notes mt-2">
                            <i class="las la-sticky-note me-1"></i>{{ $redemption->notes }}
                        </div>
                    @endif
                </div>

                {{-- Redeemed items breakdown --}}
                @if(!empty($items))
                <div class="redemption-items">
                    <div class="row g-2">
                        @foreach($items as $rItem)
                            @php
                                $rProductId = $rItem['product_id'] ?? null;
                                $rQty       = $rItem['quantity']   ?? 0;
                                $rProduct   = $rProductId ? $order->items->firstWhere('product_id', $rProductId)?->product : null;
                            @endphp
                            <div class="col-12 col-sm-6 col-md-4">
                                <div class="ritem-card">
                                    @if($rProduct && $rProduct->thumbnail)
                                        <img
                                            src="{{ getImage(getFilePath('products') . '/' . $rProduct->thumbnail, getFileSize('products')) }}"
                                            alt="{{ $rProduct->name }}"
                                            class="ritem-img"
                                            onerror="this.src='{{ asset('assets/images/placeholder.png') }}'"
                                        >
                                    @else
                                        <div class="ritem-img-placeholder">
                                            <i class="las la-image"></i>
                                        </div>
                                    @endif
                                    <div class="ritem-details">
                                        <p class="ritem-name mb-0">
                                            {{ $rProduct->name ?? 'Product #' . $rProductId }}
                                        </p>
                                        <p class="ritem-qty mb-0">
                                            <i class="las la-layer-group me-1"></i>Qty: <strong>{{ $rQty }}</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@else
    {{-- No redemptions yet --}}
    <div class="card b-radius--10 mb-4">
        <div class="card-body text-center py-5">
            <div class="empty-state">
                <i class="las la-store"></i>
                <p class="mt-2 text-muted">No redemptions recorded for this order yet.</p>
            </div>
        </div>
    </div>
@endif

@endsection

@push('style')
<style>
/* ── Sale detail page styles ── */

/* Hero status badge */
.status-hero-badge {
    display: inline-flex;
    align-items: center;
    padding: 8px 18px;
    border-radius: 30px;
    font-weight: 600;
    font-size: .88rem;
    letter-spacing: .02em;
}
.status-hero-badge.badge--success  { background: #d1f5e0; color: #0f5132; border: 1px solid #a3e6c0; }
.status-hero-badge.badge--warning  { background: #fff3cd; color: #664d03; border: 1px solid #ffe69c; }
.status-hero-badge.badge--danger   { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }

/* Meta info cards */
.meta-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    background: #fff;
    border: 1px solid #e8eaf0;
    border-radius: 12px;
    padding: 16px 18px;
    height: 100%;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
    transition: box-shadow .2s ease, transform .2s ease;
}
.meta-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,.08);
    transform: translateY(-2px);
}
.meta-icon {
    width: 46px;
    height: 46px;
    min-width: 46px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.4rem;
}
.meta-label {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #888;
    margin-bottom: 2px;
}
.meta-value {
    font-size: .95rem;
    font-weight: 700;
    color: #222;
    margin-bottom: 0;
    word-break: break-all;
}
.meta-sub {
    font-size: .78rem;
    color: #999;
    margin-bottom: 0;
}
.inv-code-sm {
    font-size: .78rem;
    font-family: monospace;
    letter-spacing: .04em;
}
.amount-hero { color: #1b6b3a; font-size: 1.05rem; }

/* Product thumbnail */
.product-thumb {
    width: 46px;
    height: 46px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #e8eaf0;
}
.product-thumb-placeholder {
    width: 46px;
    height: 46px;
    background: #f3f4f9;
    border-radius: 8px;
    border: 1px solid #e8eaf0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ccc;
    font-size: 1.2rem;
}

/* Qty badges */
.qty-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 28px;
    padding: 0 8px;
    border-radius: 6px;
    font-size: .78rem;
    font-weight: 700;
    background: #e9ecef;
    color: #495057;
}
.qty-badge.redeemed { background: #d1f5e0; color: #0f5132; }
.qty-badge.none     { background: #f8d7da; color: #842029; }

/* Item status badge */
.item-status-badge {
    font-size: .74rem;
    padding: 3px 10px;
    border-radius: 20px;
}

/* Order total footer row */
.order-total-row {
    background: #f8f9fa;
    border-top: 2px solid #e8eaf0;
}
.total-amount-cell {
    font-size: 1rem;
    color: #1b6b3a;
}

/* Redemption block */
.redemption-block {
    padding: 20px 24px;
}
.redemption-header {
    margin-bottom: 16px;
}
.stockist-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.stockist-avatar {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1.2rem;
}
.stockist-name {
    font-weight: 700;
    font-size: .95rem;
    color: #222;
}
.stockist-sub {
    font-size: .78rem;
    color: #999;
}
.redemption-notes {
    font-size: .82rem;
    color: #666;
    background: #fffbe6;
    border-left: 3px solid #fadb14;
    padding: 6px 12px;
    border-radius: 0 6px 6px 0;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.redemption-items {
    padding-top: 4px;
}

/* Redeemed item card */
.ritem-card {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8f9fc;
    border: 1px solid #e8eaf0;
    border-radius: 10px;
    padding: 10px 12px;
    transition: box-shadow .15s ease;
}
.ritem-card:hover {
    box-shadow: 0 3px 10px rgba(0,0,0,.07);
}
.ritem-img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 7px;
    border: 1px solid #dde;
    flex-shrink: 0;
}
.ritem-img-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 7px;
    background: #e9ecef;
    border: 1px solid #dde;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #bbb;
    font-size: 1rem;
    flex-shrink: 0;
}
.ritem-name {
    font-size: .83rem;
    font-weight: 600;
    color: #333;
    line-height: 1.3;
}
.ritem-qty {
    font-size: .78rem;
    color: #666;
}

/* Empty state */
.empty-state { color: #bbb; }
.empty-state i { font-size: 3rem; display: block; }

/* Responsive tweaks */
@media (max-width: 575px) {
    .meta-card { padding: 12px 14px; }
    .redemption-block { padding: 14px 16px; }
    th, td { font-size: .78rem; }
}
</style>
@endpush

@push('script')
<script>
$(function () {
    /* Smooth-highlight a row on hover for the products table */
    $('table tbody tr').on('mouseenter', function () {
        $(this).addClass('bg-light');
    }).on('mouseleave', function () {
        $(this).removeClass('bg-light');
    });
});
</script>
@endpush

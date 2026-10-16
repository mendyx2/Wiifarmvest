@extends($activeTemplate . 'layouts.master')
@section('title', 'Order Details')
@section('content')

<div class="osd-page">

    {{-- ── Header ── --}}
    <div class="osd-header-card">
        <div class="osd-header-left">
            <div class="osd-header-icon"><i class="las la-receipt"></i></div>
            <div>
                <h6 class="osd-header-title">Order Details</h6>
                <p class="osd-header-sub">Invoice: <strong>{{ $order->invoice_code }}</strong> &bull; <i class="las la-map-marker-alt"></i> {{ $order->state->name }}</p>
            </div>
        </div>
        <a href="{{ route('user.orders.index') }}" class="osd-back-btn">
            <i class="las la-arrow-left"></i> Back to Orders
        </a>
    </div>

    <div class="osd-layout">

        {{-- ── Left column ── --}}
        <div class="osd-left">

            {{-- Status card --}}
            <div class="osd-status-card">
                <div class="osd-status-icon-wrap">
                    @switch($order->status)
                        @case(Status::ORDER_PENDING)
                            <div class="osd-status-icon pend"><i class="las la-clock"></i></div>
                            @break
                        @case(Status::ORDER_PAID)
                            <div class="osd-status-icon ok"><i class="las la-check-circle"></i></div>
                            @break
                        @case(Status::ORDER_REDEEMED)
                            <div class="osd-status-icon redeemed"><i class="las la-box-open"></i></div>
                            @break
                        @default
                            <div class="osd-status-icon cancel"><i class="las la-times-circle"></i></div>
                    @endswitch
                </div>
                <div class="osd-status-info">
                    <h6 class="osd-status-label">{{ ucfirst($order->statusShowBadge) }}</h6>
                    <p class="osd-status-date">Order placed on {{ showDateTime($order->created_at) }}</p>
                    @if($order->invoice && $order->invoice->redeemed_at)
                        <p class="osd-status-redeemed"><i class="las la-check-circle"></i> Redeemed on {{ showDateTime($order->invoice->redeemed_at) }}</p>
                    @endif
                </div>
                <div class="osd-status-amount">
                    <span class="osd-amount">{{ showAmount($order->invoice?->total_amount, 2) }}</span>
                    <span class="osd-amount-label">Total Amount</span>
                </div>
            </div>

            {{-- Order items --}}
            <div class="osd-card">
                <div class="osd-card-head">
                    <i class="las la-boxes"></i> Order Items
                </div>
                @foreach($order->items as $item)
                <div class="osd-item">
                    <div class="osd-item-img">
                        @if($item->product->thumbnail)
                            <img src="{{ getImage(getFilePath('products') . '/' . $item->product->thumbnail, getFilePath('products')) }}" alt="{{ $item->product->name }}">
                        @else
                            <div class="osd-item-placeholder"><i class="las la-image"></i></div>
                        @endif
                    </div>
                    <div class="osd-item-info">
                        <p class="osd-item-name">{{ $item->product->name }} <x-product.sku :value="$item->product->sku" class="ms-1" /></p>
                        @if($item->product->description)
                            <p class="osd-item-desc">{{ Str::limit($item->product->description, 80) }}</p>
                        @endif
                        <p class="osd-item-price">{{ showAmount($item->price, 2) }}</p>
                    </div>
                    <div class="osd-item-right">
                        <span class="osd-item-qty">×{{ $item->quantity }}</span>
                        <span class="osd-item-total">{{ showAmount($item->price * $item->quantity, 2) }}</span>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Invoice info --}}
            @if($order->invoice)
            <div class="osd-card">
                <div class="osd-card-head">
                    <i class="las la-receipt"></i> Invoice Information
                </div>
                <div class="osd-invoice-body">
                    <div class="osd-invoice-row">
                        <label class="osd-invoice-label">Invoice Code</label>
                        <div class="osd-code-wrap">
                            <input type="text" class="osd-code-input" value="{{ $order->invoice->invoice_code }}" readonly id="invoiceCode">
                            <button class="osd-copy-btn" type="button" onclick="copyInvoiceCode()">
                                <i class="las la-copy"></i> Copy
                            </button>
                        </div>
                        <small class="osd-code-hint">Present this code to redeem your products</small>
                    </div>
                    <div class="osd-invoice-row">
                        <label class="osd-invoice-label">Status</label>
                        @if($order->invoice->redeemed_at)
                            <span class="osd-inv-badge ok"><i class="las la-check-circle"></i> Redeemed on {{ showDateTime($order->invoice->redeemed_at) }}</span>
                        @else
                            <span class="osd-inv-badge pend"><i class="las la-clock"></i> Awaiting Redemption</span>
                        @endif
                    </div>

                    <div class="osd-redeem-steps">
                        <p class="osd-redeem-title"><i class="las la-info-circle"></i> How to Redeem Your Products</p>
                        <ol class="osd-redeem-list">
                            <li>Visit any authorised stockist location</li>
                            <li>Present your invoice code: <code>{{ $order->invoice?->invoice_code }}</code></li>
                            <li>Show your ID for verification</li>
                            <li>Collect your products</li>
                        </ol>
                    </div>
                </div>
            </div>
            @endif

        </div>

        {{-- ── Right column ── --}}
        <div class="osd-right">

            {{-- Order summary --}}
            <div class="osd-card">
                <div class="osd-card-head osd-card-head--green">
                    <i class="las la-file-invoice"></i> Order Summary
                </div>
                <div class="osd-summary-body">
                    <div class="osd-sum-row">
                        <span>Subtotal</span>
                        <span>{{ showAmount($order->invoice?->total_amount, 2) }}</span>
                    </div>
                    <div class="osd-sum-row">
                        <span>Shipping</span>
                        <span class="osd-free">FREE</span>
                    </div>
                    <div class="osd-sum-div"></div>
                    <div class="osd-sum-row osd-sum-total">
                        <span>Total</span>
                        <span>{{ showAmount($order->total_amount, 2) }}</span>
                    </div>
                    <div class="osd-payment-method">
                        <div class="osd-pm-icon"><i class="las la-wallet"></i></div>
                        <div>
                            <p class="osd-pm-name">Wallet Payment</p>
                            <p class="osd-pm-sub">Paid from your wallet balance</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Timeline --}}
            <div class="osd-card">
                <div class="osd-card-head">
                    <i class="las la-history"></i> Order Timeline
                </div>
                <div class="osd-timeline">
                    @php
                        $timelineItems = [
                            ['icon' => 'la-shopping-cart', 'text' => 'Order Placed', 'date' => $order->created_at],
                            ['icon' => 'la-credit-card',   'text' => 'Payment Confirmed', 'date' => $order->created_at->addMinutes(5)],
                        ];
                        if ($order->invoice && $order->invoice->redeemed_at) {
                            $timelineItems[] = ['icon' => 'la-box-open', 'text' => 'Products Redeemed', 'date' => $order->invoice->redeemed_at];
                        }
                    @endphp
                    @foreach($timelineItems as $i => $tl)
                    <div class="osd-tl-item {{ !$loop->last ? 'has-line' : '' }}">
                        <div class="osd-tl-dot"><i class="las {{ $tl['icon'] }}"></i></div>
                        <div class="osd-tl-info">
                            <p class="osd-tl-label">{{ $tl['text'] }}</p>
                            <p class="osd-tl-date">{{ showDateTime($tl['date']) }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="osd-card">
                <div class="osd-card-head">
                    <i class="las la-bolt"></i> Quick Actions
                </div>
                <div class="osd-actions">
                    @if($order->invoice && !$order->invoice->redeemed_at)
                        <button class="osd-action-btn outline" onclick="copyInvoiceCode()">
                            <i class="las la-copy"></i> Copy Invoice Code
                        </button>
                    @endif
                    <a href="{{ route('user.orders.index') }}" class="osd-action-btn outline">
                        <i class="las la-list"></i> View All Orders
                    </a>
                    <a href="{{ route('user.products') }}" class="osd-action-btn green">
                        <i class="las la-store"></i> Continue Shopping
                    </a>
                </div>
            </div>

        </div>
    </div>

</div>

@push('modal')
<div class="modal fade" id="copySuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pd-modal-content">
            <div class="pd-modal-header pd-modal-header--success">
                <div class="pd-modal-icon"><i class="las la-check-circle"></i></div>
                <h6 class="pd-modal-title">Invoice Code Copied!</h6>
                <button class="pd-modal-close" data-bs-dismiss="modal"><i class="las la-times"></i></button>
            </div>
            <div class="pd-modal-body">
                <p>The invoice code has been copied to your clipboard. Present it to the stockist to redeem your products.</p>
            </div>
            <div class="pd-modal-footer">
                <button class="pd-modal-btn-confirm" data-bs-dismiss="modal">Got It</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('script')
<script>
function copyInvoiceCode() {
    const el = document.getElementById('invoiceCode');
    navigator.clipboard.writeText(el.value).then(function() {
        $('#copySuccessModal').modal('show');
    }).catch(function() { alert('Failed to copy invoice code.'); });
}
</script>
@endpush

@endsection

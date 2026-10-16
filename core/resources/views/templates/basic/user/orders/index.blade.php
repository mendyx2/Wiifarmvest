@extends($activeTemplate . 'layouts.master')
@section('title', 'My Orders')
@section('content')

<div class="ord-page">

    {{-- ── Welcome Packages ── --}}
    @if(($welcomePackages ?? collect())->count())
    <div class="ord-wp-section">
        <div class="ord-wp-section-head">
            <i class="las la-gift"></i>
            <span>Welcome Packages</span>
            <span class="ord-wp-count">{{ $welcomePackages->count() }}</span>
        </div>
        <div class="ord-wp-grid">
            @foreach($welcomePackages as $wp)
            <div class="ord-wp-card {{ $wp->isRedeemed() ? 'ord-wp-card--done' : '' }}">
                <div class="ord-wp-top">
                    <div class="ord-wp-icon"><i class="las la-gift"></i></div>
                    <div class="ord-wp-info">
                        <p class="ord-wp-title">{{ ucfirst($wp->source) }} Welcome Package</p>
                        <p class="ord-wp-amount">{{ showAmount($wp->amount) }}</p>
                    </div>
                    <span class="ord-wp-badge {{ $wp->isRedeemed() ? 'ord-wp-badge--done' : 'ord-wp-badge--pend' }}">
                        @if($wp->isRedeemed())
                            <i class="las la-check-circle"></i> Redeemed
                        @else
                            <i class="las la-hourglass-half"></i> Pending
                        @endif
                    </span>
                </div>
                <div class="ord-wp-code-row">
                    <div class="ord-wp-code-wrap">
                        <span class="ord-wp-code-label">Code</span>
                        <span class="ord-wp-code" id="wpCode{{ $loop->index }}">{{ $wp->code }}</span>
                    </div>
                    @if(!$wp->isRedeemed())
                    <button class="ord-wp-copy-btn copy-wp-code"
                            data-code="{{ $wp->code }}"
                            title="Copy code">
                        <i class="las la-copy"></i> Copy
                    </button>
                    @else
                    <span class="ord-wp-redeemed-date">
                        <i class="las la-calendar-check"></i>
                        {{ $wp->redeemed_at?->format('M d, Y') }}
                    </span>
                    @endif
                </div>
                @if(!$wp->isRedeemed())
                <p class="ord-wp-hint"><i class="las la-map-marker-alt"></i> Show this code to any authorized stockist to claim your gift package</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Header ── --}}
    <div class="ord-header-card">
        <div class="ord-header-left">
            <div class="ord-header-icon"><i class="las la-receipt"></i></div>
            <div>
                <h6 class="ord-header-title">My Orders</h6>
                <p class="ord-header-sub">Track and manage your purchase history</p>
            </div>
        </div>
        <a href="{{ route('user.products') }}" class="ord-shop-btn">
            <i class="las la-store"></i> Continue Shopping
        </a>
    </div>

    @if($orders->isEmpty())
    {{-- ── Empty ── --}}
    <div class="ord-empty">
        <div class="ord-empty-icon"><i class="las la-receipt"></i></div>
        <h6>No Orders Yet</h6>
        <p>You haven't placed any orders. Start shopping to see your order history here.</p>
        <a href="{{ route('user.products') }}" class="ord-empty-btn">
            <i class="las la-store"></i> Start Shopping
        </a>
    </div>

    @else

    {{-- ── Stats ── --}}
    <div class="ord-stats">
        <div class="ord-stat">
            <span class="ord-stat-val">{{ $orders->count() }}</span>
            <span class="ord-stat-label">Total Orders</span>
        </div>
        <div class="ord-stat">
            <span class="ord-stat-val pend">{{ $orders->where('status', '0')->count() }}</span>
            <span class="ord-stat-label">Pending</span>
        </div>
        <div class="ord-stat">
            <span class="ord-stat-val ok">{{ $orders->where('status', 1)->count() }}</span>
            <span class="ord-stat-label">Redeemed</span>
        </div>
        <div class="ord-stat">
            <span class="ord-stat-val warn">{{ $orders->where('status', 3)->count() }}</span>
            <span class="ord-stat-label">Cancelled</span>
        </div>
    </div>

    {{-- ── Filters ── --}}
    <div class="ord-filter-bar">
        <div class="ord-filter-group">
            <label class="ord-filter-label">Status</label>
            <select class="ord-select" id="statusFilter">
                <option value="all">All Orders</option>
                <option value="0">Pending</option>
                <option value="1">Redeemed</option>
                <option value="3">Cancelled</option>
            </select>
        </div>
        <div class="ord-filter-group">
            <label class="ord-filter-label">Sort By</label>
            <select class="ord-select" id="sortBy">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="amount_high">Amount: High to Low</option>
                <option value="amount_low">Amount: Low to High</option>
            </select>
        </div>
        <div class="ord-filter-group ord-search-group">
            <label class="ord-filter-label">Search</label>
            <div class="ord-search-wrap">
                <i class="las la-search"></i>
                <input class="ord-search-input" type="text" placeholder="Search by invoice code..." id="searchOrders">
            </div>
        </div>
    </div>

    {{-- ── Orders List ── --}}
    <div class="ord-list">
        @foreach($orders as $order)
        <div class="ord-card"
             data-status="{{ $order->status }}"
             data-invoice="{{ $order->invoice_code }}"
             data-amount="{{ $order->invoice?->total_amount }}"
             data-date="{{ $order->created_at->timestamp }}">

            {{-- Card header --}}
            <div class="ord-card-head">
                <div class="ord-card-icon-wrap">
                    @switch($order->status)
                        @case('0')
                            <div class="ord-status-icon pend"><i class="las la-clock"></i></div>
                            @break
                        @case('1')
                            <div class="ord-status-icon ok"><i class="las la-box-open"></i></div>
                            @break
                        @default
                            <div class="ord-status-icon cancel"><i class="las la-times-circle"></i></div>
                    @endswitch
                </div>
                <div class="ord-card-meta">
                    <p class="ord-invoice">Order #{{ $order->invoice_code }}</p>
                    <p class="ord-date"><i class="las la-calendar"></i> {{ showDateTime($order->created_at) }}</p>
                    <p class="ord-state"><i class="las la-map-marker-alt"></i> {{ $order->state->name }}</p>
                </div>
                <div class="ord-card-right">
                    <p class="ord-amount">{{ showAmount($order->invoice?->total_amount, 2) }}</p>
                    <span class="ord-badge ord-badge--{{ $order->status == '0' ? 'pend' : ($order->status == '1' ? 'ok' : 'cancel') }}">
                        {{ ucfirst($order->statusShowBadge) }}
                    </span>
                </div>
            </div>

            {{-- Items preview --}}
            <div class="ord-card-items">
                <p class="ord-items-label"><i class="las la-boxes"></i> Items ({{ $order->items->count() }})</p>
                <div class="ord-items-row">
                    @foreach($order->items->take(3) as $item)
                    <div class="ord-item-chip">
                        @if($item->product->thumbnail)
                            <img src="{{ getImage(getFilePath('products') . '/' . $item->product->thumbnail, getFilePath('products')) }}" alt="{{ $item->product->name }}">
                        @else
                            <div class="ord-item-chip-placeholder"><i class="las la-image"></i></div>
                        @endif
                        <div>
                            <p class="ord-item-name">{{ Str::limit($item->product->name, 20) }}</p>
                            <x-product.sku :value="$item->product->sku" />
                            <p class="ord-item-qty">Qty: {{ $item->quantity }} &bull; {{ showAmount($item->price, 2) }}</p>
                        </div>
                    </div>
                    @endforeach
                    @if($order->items->count() > 3)
                    <div class="ord-item-more">+{{ $order->items->count() - 3 }} more</div>
                    @endif
                </div>
            </div>

            {{-- Footer --}}
            <div class="ord-card-foot">
                <div class="ord-card-foot-left">
                    @if($order->status == '0' && $order->invoice && !$order->invoice->redeemed_at)
                        <span class="ord-foot-note pend"><i class="las la-exclamation-circle"></i> Ready for redemption</span>
                    @elseif($order->status == '1')
                        <span class="ord-foot-note ok"><i class="las la-check-circle"></i> Successfully redeemed</span>
                    @endif
                    @if($order->invoice)
                        <span class="ord-foot-invoice">Invoice: <code>{{ $order->invoice->invoice_code }}</code></span>
                    @endif
                </div>
                <div class="ord-card-foot-right">
                    @if($order->status == '0' && $order->invoice && !$order->invoice->redeemed_at)
                        <button class="ord-btn-copy copy-invoice" data-invoice="{{ $order->invoice->invoice_code }}">
                            <i class="las la-copy"></i> Copy Code
                        </button>
                    @endif
                    <a href="{{ route('user.orders.show', $order->id) }}" class="ord-btn-view">
                        <i class="las la-eye"></i> View Order
                    </a>
                </div>
            </div>

        </div>
        @endforeach
    </div>

    {{-- ── Pagination ── --}}
    @if($orders->hasPages())
    <div class="ord-pagination">
        <small class="ord-page-info">Showing {{ $orders->firstItem() }}–{{ $orders->lastItem() }} of {{ $orders->total() }} orders</small>
        {{ $orders->links() }}
    </div>
    @endif

    @endif

</div>

@push('modal')
<div class="modal fade" id="copySuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pd-modal-content">
            <div class="pd-modal-header pd-modal-header--success">
                <div class="pd-modal-icon"><i class="las la-check-circle"></i></div>
                <h6 class="pd-modal-title">Invoice Code Copied!</h6>
                <button class="pd-modal-close" data-bs-dismiss="modal" type="button"><i class="las la-times"></i></button>
            </div>
            <div class="pd-modal-body">
                <p id="copiedInvoiceText">The invoice code has been copied to your clipboard.</p>
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
$(document).ready(function() {
    $('.copy-invoice').on('click', function() {
        const code = $(this).data('invoice');
        navigator.clipboard.writeText(code).then(function() {
            $('#copiedInvoiceText').html('Invoice code <strong>' + code + '</strong> copied. Present it to the stockist to redeem your products.');
            $('#copySuccessModal').modal('show');
        }).catch(function() { alert('Failed to copy invoice code.'); });
    });

    $('.copy-wp-code').on('click', function() {
        const code = $(this).data('code');
        const $btn = $(this);
        navigator.clipboard.writeText(code).then(function() {
            $('#copiedInvoiceText').html('Welcome package code <strong>' + code + '</strong> copied. Present it to any authorized stockist to redeem your gift.');
            $('#copySuccessModal').modal('show');
            $btn.html('<i class="las la-check"></i> Copied!');
            setTimeout(function() { $btn.html('<i class="las la-copy"></i> Copy'); }, 2500);
        }).catch(function() { alert('Failed to copy code.'); });
    });

    $('#statusFilter, #sortBy').on('change', filterAndSort);
    $('#searchOrders').on('input', filterAndSort);

    function filterAndSort() {
        const status = $('#statusFilter').val();
        const sort   = $('#sortBy').val();
        const term   = $('#searchOrders').val().toLowerCase();

        $('.ord-card').each(function() {
            const $c = $(this);
            const statusMatch = status === 'all' || $c.data('status') == status;
            const searchMatch = $c.data('invoice').toLowerCase().includes(term) || term === '';
            $c.toggle(statusMatch && searchMatch);
        });

        const $container = $('.ord-list');
        const $cards = $('.ord-card').get();
        $cards.sort(function(a, b) {
            const $a = $(a), $b = $(b);
            if (sort === 'newest') return $b.data('date') - $a.data('date');
            if (sort === 'oldest') return $a.data('date') - $b.data('date');
            if (sort === 'amount_high') return parseFloat($b.data('amount')) - parseFloat($a.data('amount'));
            if (sort === 'amount_low') return parseFloat($a.data('amount')) - parseFloat($b.data('amount'));
            return 0;
        });
        $.each($cards, function(i, el) { $container.append(el); });
    }
});
</script>
@endpush

@endsection

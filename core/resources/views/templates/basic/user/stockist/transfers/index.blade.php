@extends($activeTemplate . 'layouts.master')

@section('content')
@php
    $stat = fn ($s) => $counts->get($s);
    $pending   = $stat('pending');
    $completed = $stat('completed');
    $closed    = (int) ($stat('declined')->n ?? 0) + (int) ($stat('cancelled')->n ?? 0);
@endphp
<div class="nc-wrap stx" id="ncWrap">
    <br>
    <div class="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="sl-page-title mb-1">{{ $pageTitle }}</h4>
            <p class="sl-page-subtitle mb-0">
                @if ($isMega)
                    Mini stockists in your state buying from your stock. Their payment is already held: confirm to hand the stock over.
                @else
                    Your stock comes from a Mega stockist in your state. You pay when you order; the money is held until they confirm.
                @endif
            </p>
        </div>
        @unless ($isMega)
            <a href="{{ route('user.stockist.transfers.create') }}" class="sl-btn sl-btn-primary">
                <i class="las la-truck-loading me-1"></i> Buy from Mega
            </a>
        @endunless
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="sl-stat-card sl-stat-gold">
                <div class="sl-stat-icon"><i class="las la-hourglass-half"></i></div>
                <div>
                    <p class="sl-stat-label">{{ $isMega ? 'Waiting for you' : 'Waiting for Mega' }}</p>
                    <h3 class="sl-stat-value">{{ number_format($pending->n ?? 0) }}</h3>
                    <p class="sl-stat-note">{{ showAmount($pending->total ?? 0) }} held</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="sl-stat-card sl-stat-primary">
                <div class="sl-stat-icon"><i class="las la-check-double"></i></div>
                <div>
                    <p class="sl-stat-label">Completed</p>
                    <h3 class="sl-stat-value">{{ number_format($completed->n ?? 0) }}</h3>
                    <p class="sl-stat-note">{{ showAmount($completed->total ?? 0) }} {{ $isMega ? 'earned' : 'bought' }}</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="sl-stat-card sl-stat-info">
                <div class="sl-stat-icon"><i class="las la-undo-alt"></i></div>
                <div>
                    <p class="sl-stat-label">Declined / cancelled</p>
                    <h3 class="sl-stat-value">{{ number_format($closed) }}</h3>
                    <p class="sl-stat-note">Payments refunded</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="sl-stat-card sl-stat-success">
                <div class="sl-stat-icon"><i class="las la-wallet"></i></div>
                <div>
                    <p class="sl-stat-label">Stockist wallet</p>
                    <h3 class="sl-stat-value">{{ showAmount($stockist->wallet) }}</h3>
                    <p class="sl-stat-note">{{ $stockist->store_type_name }}</p>
                </div>
            </div>
        </div>
    </div>

    <nav class="stx-tabs mb-3" aria-label="Filter by status">
        <a href="{{ route('user.stockist.transfers.index') }}" @class(['stx-tab', 'is-active' => !$status])>All</a>
        @foreach (\App\Models\StockTransfer::STATUSES as $key => $label)
            <a href="{{ route('user.stockist.transfers.index', ['status' => $key]) }}" @class(['stx-tab', 'is-active' => $status === $key])>
                {{ $key === 'pending' ? 'Pending' : $label }}
                @if ($n = $counts->get($key)->n ?? 0)<span>{{ $n }}</span>@endif
            </a>
        @endforeach
    </nav>

    <div class="sl-card">
        @if ($transfers->isEmpty())
            <div class="sl-empty-state">
                <div class="sl-empty-icon"><i class="las la-exchange-alt"></i></div>
                <p class="sl-empty-title">{{ $status ? 'Nothing here' : ($isMega ? 'No stock requests yet' : 'No orders yet') }}</p>
                <p class="sl-empty-sub">
                    @if ($isMega)
                        When a Mini stockist in your state asks to buy from your stock, it shows up here.
                    @else
                        Order stock from a Mega stockist in your state. You pay from your stockist wallet; it's held until they confirm.
                    @endif
                </p>
                @unless ($isMega)
                    <a href="{{ route('user.stockist.transfers.create') }}" class="sl-btn sl-btn-primary mt-3"><i class="las la-truck-loading me-1"></i> Buy from Mega</a>
                @endunless
            </div>
        @else
            <div class="table-responsive">
                <table class="sl-table" aria-label="Stock requests">
                    <thead>
                        <tr>
                            <th>{{ $isMega ? 'Request' : 'Order' }}</th>
                            <th>{{ $isMega ? 'Mini stockist' : 'Mega stockist' }}</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transfers as $transfer)
                            @php $other = $isMega ? $transfer->buyer : $transfer->seller; @endphp
                            <tr @class(['stx-row-pending' => $transfer->isPending() && $isMega])>
                                <td data-label="{{ $isMega ? 'Request' : 'Order' }}">
                                    <strong class="stx-code">{{ $transfer->code }}</strong>
                                    <small class="d-block text-muted">{{ showDateTime($transfer->created_at, 'd M Y, h:i A') }}</small>
                                </td>
                                <td data-label="{{ $isMega ? 'Mini stockist' : 'Mega stockist' }}">
                                    {{ $other?->business_name ?? '—' }}
                                    <small class="d-block text-muted">{{ $other?->user?->fullname }}</small>
                                </td>
                                <td data-label="Items">
                                    {{ $transfer->items->count() }} {{ \Illuminate\Support\Str::plural('product', $transfer->items->count()) }}
                                    <small class="d-block text-muted">{{ number_format($transfer->total_quantity) }} {{ \Illuminate\Support\Str::plural('unit', $transfer->total_quantity) }}</small>
                                </td>
                                <td data-label="Amount"><strong>{{ showAmount($transfer->total_amount) }}</strong></td>
                                <td data-label="Status"><span class="sl-status {{ $transfer->statusClass() }}">{{ $transfer->statusLabel() }}</span></td>
                                <td data-label="">
                                    <a href="{{ route('user.stockist.transfers.show', $transfer->id) }}" @class(['sl-btn', 'sl-btn-primary' => $isMega && $transfer->isPending(), 'sl-btn-outline' => !($isMega && $transfer->isPending()), 'stx-btn-sm'])>
                                        {{ $isMega && $transfer->isPending() ? 'Review' : 'View' }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if ($transfers->hasPages())
        <div class="mt-3">{{ $transfers->links() }}</div>
    @endif

    @if (!$isMega && $companyOrders > 0)
        <p class="stx-earlier">
            <i class="las la-history"></i>
            You also have {{ $companyOrders }} earlier {{ \Illuminate\Support\Str::plural('order', $companyOrders) }} from the company.
            <a href="{{ route('user.stockist.inventory.orders') }}">View {{ $companyOrders === 1 ? 'it' : 'them' }}</a>
        </p>
    @endif
</div>
@endsection

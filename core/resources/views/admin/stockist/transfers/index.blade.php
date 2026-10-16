@extends('admin.layouts.app')
@section('panel')
@php
    $pending   = $stats->get('pending');
    $completed = $stats->get('completed');
    $closed    = (int) ($stats->get('declined')->n ?? 0) + (int) ($stats->get('cancelled')->n ?? 0);
@endphp
<div class="db-wrap atx">
    <div class="db-greeting">
        <div class="db-greeting__left">
            <p class="db-greeting__tag">Stockists</p>
            <h2 class="db-greeting__name">Stock Transfers</h2>
            <p class="db-greeting__sub">Mini stockists buying stock from a Mega stockist in their state. The Mini pays when they ask; the money is held until the Mega confirms, or refunded if it doesn't happen.</p>
        </div>
    </div>

    <div class="row gy-4 fr-boxes">
        <div class="col-xl-3 col-sm-6">
            <x-widget value="{{ number_format($pending->n ?? 0) }}" title="Waiting for Mega" subheading="{{ showAmount($pending->total ?? 0) }} held" style="6" icon="las la-hourglass-half" bg="warning" outline=false :viewMoreIcon="false" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-widget value="{{ showAmount($completed->total ?? 0) }}" title="Stock sold Mega → Mini" subheading="{{ number_format($completed->n ?? 0) }} completed" style="6" icon="las la-exchange-alt" bg="success" outline=false :viewMoreIcon="false" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-widget value="{{ number_format($completed->units ?? 0) }}" title="Units moved" subheading="{{ $closed }} declined or cancelled" style="6" icon="las la-boxes" bg="info" outline=false :viewMoreIcon="false" />
        </div>
        <div class="col-xl-3 col-sm-6">
            <x-widget value="{{ showAmount($completed->rebate ?? 0) }}" title="Rebate paid to Megas" subheading="Company cost, in stockist_rebate" style="6" icon="las la-gift" bg="1" outline=false :viewMoreIcon="false" />
        </div>
    </div>

    <div class="atx-bar mt-4">
        <nav class="atx-tabs" aria-label="Filter by status">
            <a href="{{ route('admin.stockist.transfers.index', request()->except('status', 'page')) }}" @class(['atx-tab', 'is-active' => !$status])>All</a>
            @foreach (\App\Models\StockTransfer::STATUSES as $key => $label)
                <a href="{{ route('admin.stockist.transfers.index', array_merge(request()->except('page'), ['status' => $key])) }}" @class(['atx-tab', 'is-active' => $status === $key])>
                    {{ $key === 'pending' ? 'Pending' : $label }} <span>{{ $stats->get($key)->n ?? 0 }}</span>
                </a>
            @endforeach
        </nav>

        <form class="atx-search" method="GET">
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Code, store or username">
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" aria-label="From">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" aria-label="To">
            <button type="submit" class="btn btn--primary"><i class="las la-search"></i></button>
        </form>
    </div>

    <div class="card atx-card">
        <div class="card-body p-0">
            <div class="table-responsive--md table-responsive">
                <table class="table table--light style--two mb-0">
                    <thead>
                        <tr>
                            <th>Transfer</th>
                            <th>Mega <i class="las la-long-arrow-alt-right"></i> Mini</th>
                            <th>State</th>
                            <th>Stock</th>
                            <th>Amount</th>
                            <th>Rebate</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transfers as $transfer)
                            <tr>
                                <td>
                                    <span class="atx-code">{{ $transfer->code }}</span>
                                    <small class="d-block text-muted">{{ showDateTime($transfer->created_at, 'd M Y, h:i A') }}</small>
                                </td>
                                <td class="text-start">
                                    <div class="atx-flow">
                                        <span><strong>{{ $transfer->seller?->business_name ?? '—' }}</strong><small>{{ '@' . $transfer->seller?->user?->username }}</small></span>
                                        <i class="las la-long-arrow-alt-right"></i>
                                        <span><strong>{{ $transfer->buyer?->business_name ?? '—' }}</strong><small>{{ '@' . $transfer->buyer?->user?->username }}</small></span>
                                    </div>
                                </td>
                                <td>{{ $transfer->state?->name ?? '—' }}</td>
                                <td>{{ number_format($transfer->total_quantity) }} {{ \Illuminate\Support\Str::plural('unit', $transfer->total_quantity) }}<small class="d-block text-muted">{{ $transfer->items_count }} {{ \Illuminate\Support\Str::plural('product', $transfer->items_count) }}</small></td>
                                <td><strong>{{ showAmount($transfer->total_amount) }}</strong></td>
                                <td>{{ $transfer->status === 'completed' ? showAmount($transfer->rebate_amount) : '—' }}</td>
                                <td><span class="badge {{ $transfer->adminBadge() }}" title="{{ $transfer->statusLabel() }}">{{ ucfirst($transfer->status) }}</span></td>
                                <td>
                                    <a href="{{ route('admin.stockist.transfers.show', $transfer->id) }}" class="btn btn-sm btn-outline--primary">
                                        <i class="las la-eye"></i> Details
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="las la-exchange-alt d-block fs-1 mb-2"></i>
                                    {{ $status || $search ? 'No transfers match these filters.' : 'No stock transfers yet. They appear here when a Mini stockist buys from a Mega stockist.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($transfers->hasPages())
            <div class="card-footer py-3">{{ paginateLinks($transfers) }}</div>
        @endif
    </div>
</div>
@endsection

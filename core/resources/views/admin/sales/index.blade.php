@extends('admin.layouts.app')
@section('panel')

{{-- ─────────────────────────────────────────────
     PAGE HEADER
────────────────────────────────────────────── --}}
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="mb-1 fw-bold">
            <i class="las la-chart-line text--primary me-2"></i>Manage Sales
        </h4>
        <p class="text-muted mb-0 small">
            <i class="las la-calendar-alt me-1"></i>
            Today &mdash; {{ now()->format('l, d F Y') }}
        </p>
    </div>
</div>

{{-- ─────────────────────────────────────────────
     SUMMARY STAT CARDS (always today's figures)
────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="dashboard-w1 b-radius--10 bg--primary">
            <div class="icon"><i class="las la-shopping-bag"></i></div>
            <div class="details">
                <p class="text-white">Orders Today</p>
                <h3 class="text-white">{{ $stats['total'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="dashboard-w1 b-radius--10 bg--success">
            <div class="icon"><i class="las la-check-double"></i></div>
            <div class="details">
                <p class="text-white">Fully Redeemed</p>
                <h3 class="text-white">{{ $stats['redeemed'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-4">
        <div class="dashboard-w1 b-radius--10 bg--warning">
            <div class="icon"><i class="las la-money-bill-wave"></i></div>
            <div class="details">
                <p class="text-white">Today&rsquo;s Revenue</p>
                <h3 class="text-white">{{ showAmount($stats['amount']) }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- ─────────────────────────────────────────────
     MAIN TABLE CARD
────────────────────────────────────────────── --}}
<div class="card b-radius--10">

    {{-- Card Header --}}
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h5 class="card-title mb-0">
                @if($isSearch)
                    Search Results
                    <span class="badge badge--primary ms-2">{{ $orders->total() }} found</span>
                @else
                    Last 10 Sales Today
                    <span class="badge badge--primary ms-2">{{ $orders->count() }}</span>
                @endif
            </h5>
            @if($isSearch)
                <small class="text-muted">Showing results for: <strong>&ldquo;{{ $search }}&rdquo;</strong></small>
            @endif
        </div>
    </div>

    {{-- Search Bar --}}
    <div class="card-body border-bottom pb-3">
        <form method="GET" action="{{ route('admin.sales.index') }}" id="salesSearchForm">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-8 col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg--primary text-white border-0">
                            <i class="las la-search"></i>
                        </span>
                        <input
                            type="text"
                            name="search"
                            id="searchInput"
                            class="form-control border-start-0"
                            placeholder="Search by TRX / Invoice Code / Username…"
                            value="{{ $search }}"
                            autocomplete="off"
                        >
                        @if($search)
                            <a href="{{ route('admin.sales.index') }}" class="btn btn-outline--danger" title="Clear search">
                                <i class="las la-times"></i>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="col-12 col-sm-4 col-md-3">
                    <button type="submit" class="btn btn--primary w-100">
                        <i class="las la-search me-1"></i>Search
                    </button>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-muted">
                    <i class="las la-info-circle me-1"></i>
                    Search by <strong>TRX code</strong>, <strong>invoice code</strong>, or <strong>username</strong>
                </small>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table--light style--two mb-0" id="salesTable">
                <thead>
                    <tr>
                        <th class="text-center" style="width:50px">S/N</th>
                        <th>Customer</th>
                        <th>Invoice Code</th>
                        <th>State</th>
                        <th>Amount</th>
                        <th class="text-center">Redeemed</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $index => $order)
                        @php
                            $invoice       = $order->invoice;
                            $isFullyRedeem = $invoice ? $invoice->is_fully_redeemed : false;
                            $totalAmount   = $invoice ? $invoice->total_amount : $order->total_amount;
                            $invoiceCode   = $invoice ? $invoice->invoice_code : $order->invoice_code;
                            $stateName     = optional($order->state)->name ?? '—';
                            $serial        = $isSearch
                                ? (($orders->currentPage() - 1) * $orders->perPage()) + $loop->iteration
                                : $loop->iteration;
                        @endphp
                        <tr class="sales-row" data-order-id="{{ $order->id }}">

                            {{-- S/N --}}
                            <td class="text-center">
                                <span class="sn-badge">{{ $serial }}</span>
                            </td>

                            {{-- Customer --}}
                            <td>
                                <div class="user-info-cell">
                                    <span class="fw-semibold text--primary username-text">
                                        {{ $order->user->username ?? '—' }}
                                    </span>
                                    @if($order->user && ($order->user->firstname || $order->user->lastname))
                                        <br>
                                        <span class="fullname-sub text-muted">
                                            {{ trim($order->user->firstname . ' ' . $order->user->lastname) }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Invoice Code --}}
                            <td>
                                <code class="invoice-code">{{ $invoiceCode }}</code>
                            </td>

                            {{-- State --}}
                            <td>
                                <span class="state-pill">
                                    <i class="las la-map-marker me-1"></i>{{ $stateName }}
                                </span>
                            </td>

                            {{-- Total Amount --}}
                            <td>
                                <span class="fw-bold amount-text">{{ showAmount($totalAmount) }}</span>
                            </td>

                            {{-- Redeemed Status --}}
                            <td class="text-center">
                                @if($isFullyRedeem)
                                    <span class="badge badge--success redeemed-badge">
                                        <i class="las la-check-circle me-1"></i>Full
                                    </span>
                                @elseif($invoice && $invoice->redemptions->count() > 0)
                                    <span class="badge badge--warning redeemed-badge">
                                        <i class="las la-adjust me-1"></i>Partial
                                    </span>
                                @else
                                    <span class="badge badge--danger redeemed-badge">
                                        <i class="las la-times-circle me-1"></i>None
                                    </span>
                                @endif
                            </td>

                            {{-- Action --}}
                            <td class="text-center">
                                <a href="{{ route('admin.sales.show', $order->id) }}"
                                   class="btn btn-sm btn--primary view-btn"
                                   title="View sale detail">
                                    <i class="las la-eye me-1"></i>View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="las la-shopping-bag"></i>
                                    <p class="mt-2 text-muted">
                                        @if($isSearch)
                                            No sales found for <strong>&ldquo;{{ $search }}&rdquo;</strong>
                                        @else
                                            No sales recorded today yet.
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination (only in search mode) --}}
        @if($isSearch && $orders->hasPages())
            <div class="card-footer d-flex justify-content-center">
                {{ $orders->links() }}
            </div>
        @endif

        {{-- Footer note when showing today's default --}}
       {{-- 
        @if(!$isSearch && $orders->count() > 0)
            <div class="card-footer text-center text-muted small">
                <i class="las la-info-circle me-1"></i>
                Showing last {{ $orders->count() }} sale(s) today.
                Use the search box to find orders across all dates.
            </div>
        @endif
        --}}
    </div>
</div>

@endsection

@push('style')
<style>
/* ── Sales index page styles ── */

.sn-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--bs-gray-100);
    color: var(--bs-gray-700);
    font-size: .75rem;
    font-weight: 600;
    border: 1px solid var(--bs-gray-300);
}

.user-info-cell .username-text {
    font-size: .9rem;
    font-weight: 600;
}

.user-info-cell .fullname-sub {
    font-size: .78rem;
    letter-spacing: .01em;
}

.invoice-code {
    font-size: .78rem;
    background: #f3f4ff;
    color: #5b6abf;
    padding: 2px 8px;
    border-radius: 4px;
    border: 1px solid #d8dcf5;
    letter-spacing: .03em;
    word-break: break-all;
}

.state-pill {
    display: inline-flex;
    align-items: center;
    font-size: .82rem;
    color: #444;
}

.amount-text {
    font-size: .95rem;
    color: #1b6b3a;
}

.redeemed-badge {
    font-size: .78rem;
    padding: 4px 10px;
    border-radius: 20px;
}

.view-btn {
    font-size: .78rem;
    padding: 4px 14px;
    border-radius: 20px;
    white-space: nowrap;
}

.empty-state {
    color: #bbb;
}
.empty-state i {
    font-size: 3rem;
    display: block;
}

/* Row hover highlight */
.sales-row {
    transition: background .15s ease;
}
.sales-row:hover {
    background: #f7f8ff !important;
}

/* Search input styling */
.input-group .form-control:focus {
    box-shadow: none;
    border-color: #ced4da;
}

/* Responsive: stack columns on very small screens */
@media (max-width: 575px) {
    th, td {
        font-size: .78rem;
    }
    .view-btn {
        padding: 3px 10px;
    }
}
</style>
@endpush

@push('script')
<script>
$(function () {
    /* Allow pressing Enter in the search box to submit */
    $('#searchInput').on('keydown', function (e) {
        if (e.key === 'Enter') {
            $('#salesSearchForm').submit();
        }
    });

    /* Clicking anywhere on a row (except the View button) navigates to detail */
    $('#salesTable').on('click', '.sales-row', function (e) {
        /* Don't hijack actual link/button clicks */
        if ($(e.target).closest('a, button').length) return;
        var orderId = $(this).data('order-id');
        window.location.href = '{{ url("admin/sales") }}/' + orderId;
    }).css('cursor', 'pointer');
});
</script>
@endpush

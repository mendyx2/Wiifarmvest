@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="nc-wrap" id="ncWrap">
    <br>
{{-- ── Page Header ─────────────────────────────────── --}}
<div class="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="sl-page-title mb-1">Redemption History</h4>
        <p class="sl-page-subtitle mb-0">Track all your product redemption activities.</p>
    </div>
    <a href="{{ route('user.stockist.dashboard') }}" class="sl-btn sl-btn-outline">
        <i class="las la-arrow-left me-1"></i> Back to Dashboard
    </a>
</div>

{{-- ── Stats cards ───────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-info">
            <div class="sl-stat-icon"><i class="las la-receipt"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Total Redemptions</p>
                <h3 class="sl-stat-value">{{ $redemptions->total() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-success">
            <div class="sl-stat-icon"><i class="las la-coins"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Total Value</p>
                <h3 class="sl-stat-value">{{ showAmount($redemptions->sum('total_amount')) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-gold">
            <div class="sl-stat-icon"><i class="las la-calendar-day"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Today's Redemptions</p>
                <h3 class="sl-stat-value">{{ $redemptions->where('created_at', '>=', now()->startOfDay())->count() }}</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-primary">
            <div class="sl-stat-icon"><i class="las la-file-invoice"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Unique Invoices</p>
                <h3 class="sl-stat-value">{{ $redemptions->unique('invoice_id')->count() }}</h3>
            </div>
        </div>
    </div>
</div>

{{-- ── Filters ──────────────────────────────────────── --}}
<div class="sl-card mb-4">
    <div class="sl-card-body">
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3">
                <label class="sl-label" for="dateFilter">Date Range</label>
                <select class="sl-select" id="dateFilter">
                    <option value="all">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="custom">Custom Range</option>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="sl-label" for="sortFilter">Sort By</label>
                <select class="sl-select" id="sortFilter">
                    <option value="newest">Newest First</option>
                    <option value="oldest">Oldest First</option>
                    <option value="amount_high">Amount: High to Low</option>
                    <option value="amount_low">Amount: Low to High</option>
                </select>
            </div>
            <div class="col-12 col-md-4">
                <label class="sl-label" for="searchInput">Search</label>
                <input type="text" class="sl-input" placeholder="Search by invoice code, customer name…" id="searchInput">
            </div>
            <div class="col-12 col-md-2">
                <button class="sl-btn sl-btn-outline w-100" id="exportBtn">
                    <i class="las la-download me-1"></i> Export
                </button>
            </div>
        </div>

        <div class="row g-3 mt-1 d-none" id="customDateRange">
            <div class="col-12 col-md-3">
                <label class="sl-label" for="fromDate">From Date</label>
                <input type="date" class="sl-input" id="fromDate">
            </div>
            <div class="col-12 col-md-3">
                <label class="sl-label" for="toDate">To Date</label>
                <input type="date" class="sl-input" id="toDate">
            </div>
            <div class="col-12 col-md-2">
                <button class="sl-btn sl-btn-primary w-100" id="applyDateRange">
                    <i class="las la-check me-1"></i> Apply
                </button>
            </div>
        </div>
    </div>
</div>

@if($redemptions->isEmpty())
    <div class="sl-card">
        <div class="sl-empty-state">
            <div class="sl-empty-icon"><i class="las la-receipt"></i></div>
            <p class="sl-empty-title">No Redemptions Yet</p>
            <p class="sl-empty-sub">You haven't processed any redemptions yet. They will appear here once you start redeeming customer invoices.</p>
            <a href="{{ route('user.stockist.dashboard') }}" class="sl-btn sl-btn-primary mt-3">
                <i class="las la-qrcode me-1"></i> Start Redeeming
            </a>
        </div>
    </div>
@else
    @foreach($redemptions as $redemption)
    <div class="sl-card sl-redemption-card mb-4"
         data-date="{{ $redemption->created_at->timestamp }}"
         data-amount="{{ $redemption->total_amount }}"
         data-invoice="{{ $redemption->invoice->invoice_code }}"
         data-customer="{{ $redemption->user->name }}">

        {{-- Header row --}}
        <div class="sl-card-header" style="background:#F9FAFB;">
            <div class="row align-items-center g-2 w-100">
                <div class="col-12 col-md-5 d-flex align-items-center gap-3">
                    <div class="sl-table-avatar" style="background:var(--sl-green-lt);color:var(--sl-green);width:44px;height:44px;">
                        <i class="las la-check"></i>
                    </div>
                    <div>
                        <p class="fw-700 mb-0" style="color:var(--bk-text);">Redemption #{{ $redemption->id }}</p>
                        <small class="text-muted"><i class="las la-calendar me-1"></i>{{ $redemption->created_at->format('F j, Y \a\t g:i A') }}</small>
                    </div>
                </div>
                <div class="col-6 col-md-3 text-md-center">
                    <h5 class="fw-700 mb-0" style="color:var(--sl-green);">{{ showAmount($redemption->total_amount) }}</h5>
                    <small class="text-muted">Total Amount</small>
                </div>
                <div class="col-6 col-md-4 text-md-end">
                    <span class="sl-type-badge" style="background:var(--sl-green-lt);color:var(--sl-green);"><i class="las la-receipt me-1"></i>{{ $redemption->invoice->invoice_code }}</span><br>
                    <small class="text-muted"><i class="las la-user me-1"></i>{{ $redemption->user->name }}</small>
                </div>
            </div>
        </div>

        <div class="sl-card-body">
            <div class="row g-4">
                <div class="col-12 col-md-8">
                    <h6 class="fw-600 mb-3"><i class="las la-boxes me-1"></i> Redeemed Items ({{ count($redemption->redeemed_items) }})</h6>
                    <div class="table-responsive">
                        <table class="sl-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($redemption->redeemed_items as $item)
                                @php
                                    $itemArray = is_string($item) ? json_decode($item, true) : $item;
                                @endphp
                                <tr>
                                    <td data-label="Product">
                                        <div class="sl-table-name">
                                            <div class="sl-table-avatar" style="background:#F3F4F6;color:#9CA3AF;">
                                                <i class="las la-box"></i>
                                            </div>
                                            <div>
                                                <p class="fw-600 mb-0">{{ $itemArray['product_name'] ?? 'Product' }}</p>
                                                <x-product.sku :value="$skus[$itemArray['product_id'] ?? 0] ?? null" />
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Quantity">{{ $itemArray['quantity'] ?? 0 }}</td>
                                    <td data-label="Price">{{ showAmount($itemArray['price'] ?? 0) }}</td>
                                    <td data-label="Subtotal" class="fw-600" style="color:var(--sl-green);">
                                        {{ showAmount(($itemArray['quantity'] ?? 0) * ($itemArray['price'] ?? 0)) }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end fw-700">Total:</td>
                                    <td class="fw-700" style="color:var(--sl-green);">{{ showAmount($redemption->total_amount) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @if($redemption->notes)
                    <div class="mt-3">
                        <h6 class="fw-600 mb-2"><i class="las la-sticky-note me-1"></i> Notes</h6>
                        <div class="alert sl-alert-warning" style="background:#F9FAFB;border-color:var(--sl-border);color:var(--bk-text);">
                            {{ $redemption->notes }}
                        </div>
                    </div>
                    @endif
                </div>

                <div class="col-12 col-md-4">
                    <div class="sl-card" style="background:#F9FAFB;box-shadow:none;height:100%;">
                        <div class="sl-card-body">
                            <h6 class="fw-600 mb-3">Transaction Details</h6>

                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-white rounded-2">
                                <small class="fw-600"><i class="las la-user-circle me-1" style="color:var(--sl-green);"></i>Customer</small>
                                <small>{{ $redemption->user->fullname }}</small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-white rounded-2">
                                <small class="fw-600"><i class="las la-calendar me-1" style="color:var(--sl-blue);"></i>Date &amp; Time</small>
                                <small>{{ $redemption->created_at->format('M j, Y H:i') }}</small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-white rounded-2">
                                <small class="fw-600"><i class="las la-coins me-1" style="color:var(--sl-gold);"></i>Amount</small>
                                <strong style="color:var(--sl-green);">{{ showAmount($redemption->total_amount) }}</strong>
                            </div>

                            <div class="mt-3 pt-3" style="border-top:1px solid var(--sl-border);">
                                <div class="d-grid gap-2">
                                    <button class="sl-btn sl-btn-outline copy-invoice" data-invoice="{{ $redemption->invoice->invoice_code }}">
                                        <i class="las la-copy me-1"></i> Copy Invoice Code
                                    </button>
                                    {{-- <button class="sl-btn sl-btn-outline view-details" data-redemption-id="{{ $redemption->id }}">
                                        <i class="las la-eye me-1"></i> View Details
                                    </button> --}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <div class="sl-card">
        <div class="sl-card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
            <small class="text-muted">
                Showing {{ $redemptions->firstItem() }} to {{ $redemptions->lastItem() }} of {{ $redemptions->total() }} redemptions
            </small>
            {{ $redemptions->links() }}
        </div>
    </div>
@endif
</div>
@endsection

@push('modal')
<div class="modal fade" id="copySuccessModal" tabindex="-1" aria-labelledby="copySuccessModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3" style="color:var(--sl-green);">
                    <i class="las la-check-circle" style="font-size:3.5rem;"></i>
                </div>
                <h4 class="mb-3">Copied to Clipboard!</h4>
                <p class="text-muted mb-4" id="copiedInvoiceText">Invoice code has been copied successfully.</p>
                <button type="button" class="sl-btn sl-btn-primary" data-bs-dismiss="modal">Continue</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="redemptionDetailsModal" tabindex="-1" aria-labelledby="redemptionDetailsModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Redemption Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="redemptionDetailsContent"></div>
        </div>
    </div>
</div>
@endpush

@push('script')
<script>
$(document).ready(function() {
    $('.copy-invoice').on('click', function() {
        const invoiceCode = $(this).data('invoice');

        navigator.clipboard.writeText(invoiceCode).then(function() {
            $('#copiedInvoiceText').html(
                `Invoice code <strong>${invoiceCode}</strong> has been copied to clipboard.`
            );
            $('#copySuccessModal').modal('show');
        }).catch(function(err) {
            alert('Failed to copy invoice code: ' + err);
        });
    });

    $('#dateFilter').on('change', function() {
        const value = $(this).val();

        if (value === 'custom') {
            $('#customDateRange').removeClass('d-none');
        } else {
            $('#customDateRange').addClass('d-none');
            filterRedemptions();
        }
    });

    $('#applyDateRange').on('click', function() {
        filterRedemptions();
    });

    $('#sortFilter, #searchInput').on('change input', function() {
        filterRedemptions();
    });

    $('#exportBtn').on('click', function() {
        exportToCSV();
    });

    $('.view-details').on('click', function() {
        const redemptionId = $(this).data('redemption-id');
        showRedemptionDetails(redemptionId);
    });

    function filterRedemptions() {
        const dateFilter = $('#dateFilter').val();
        const sortFilter = $('#sortFilter').val();
        const searchTerm = $('#searchInput').val().toLowerCase();

        $('.sl-redemption-card').each(function() {
            const $card = $(this);
            const date = parseInt($card.data('date'));
            const invoice = String($card.data('invoice')).toLowerCase();
            const customer = String($card.data('customer')).toLowerCase();

            let dateMatch = true;
            if (dateFilter !== 'all') {
                const now = new Date();
                const cardDate = new Date(date * 1000);

                switch(dateFilter) {
                    case 'today':
                        dateMatch = cardDate.toDateString() === now.toDateString();
                        break;
                    case 'week':
                        const startOfWeek = new Date(now.setDate(now.getDate() - now.getDay()));
                        dateMatch = cardDate >= startOfWeek;
                        break;
                    case 'month':
                        const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1);
                        dateMatch = cardDate >= startOfMonth;
                        break;
                    case 'custom':
                        const fromDate = $('#fromDate').val();
                        const toDate = $('#toDate').val();
                        if (fromDate && toDate) {
                            dateMatch = cardDate >= new Date(fromDate) && cardDate <= new Date(toDate);
                        }
                        break;
                }
            }

            let searchMatch = invoice.includes(searchTerm) ||
                            customer.includes(searchTerm) ||
                            searchTerm === '';

            if (dateMatch && searchMatch) {
                $card.show();
            } else {
                $card.hide();
            }
        });

        sortRedemptions(sortFilter);
    }

    function sortRedemptions(sortBy) {
        const $container = $('.sl-redemption-card').parent();
        const $redemptions = $('.sl-redemption-card').get();

        $redemptions.sort(function(a, b) {
            const $a = $(a);
            const $b = $(b);

            switch(sortBy) {
                case 'newest':
                    return parseInt($b.data('date')) - parseInt($a.data('date'));
                case 'oldest':
                    return parseInt($a.data('date')) - parseInt($b.data('date'));
                case 'amount_high':
                    return parseFloat($b.data('amount')) - parseFloat($a.data('amount'));
                case 'amount_low':
                    return parseFloat($a.data('amount')) - parseFloat($b.data('amount'));
                default:
                    return 0;
            }
        });

        $.each($redemptions, function(i, redemption) {
            $container.append(redemption);
        });
    }

    function exportToCSV() {
        let csv = 'Redemption ID,Invoice Code,Customer,Amount,Date,Items\n';

        $('.sl-redemption-card:visible').each(function() {
            const $card = $(this);
            const redemptionId = $card.find('.view-details').data('redemption-id');
            const invoiceCode = $card.data('invoice');
            const customer = $card.data('customer');
            const amount = $card.data('amount');
            const date = new Date(parseInt($card.data('date')) * 1000).toLocaleDateString();

            csv += `"${redemptionId}","${invoiceCode}","${customer}","${amount}","${date}","${$card.find('tbody tr').length} items"\n`;
        });

        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', `redemptions-${new Date().toISOString().split('T')[0]}.csv`);
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    function showRedemptionDetails(redemptionId) {
        $('#redemptionDetailsContent').html(`
            <div class="text-center py-4">
                <i class="las la-receipt" style="font-size:2.6rem;color:var(--sl-green);"></i>
                <h5 class="mt-3">Redemption #${redemptionId}</h5>
                <p class="text-muted">Detailed view for redemption ${redemptionId}</p>
            </div>
        `);
        $('#redemptionDetailsModal').modal('show');
    }

    const today = new Date().toISOString().split('T')[0];
    $('#fromDate').val(today);
    $('#toDate').val(today);
});
</script>
@endpush

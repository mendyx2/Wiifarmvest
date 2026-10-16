@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="nc-wrap" id="ncWrap">
    <br>
{{-- ── Page Header ─────────────────────────────────── --}}
<div class="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="sl-page-title mb-1">Stockist Dashboard</h4>
        <p class="sl-page-subtitle mb-0">Welcome back, {{ auth()->user()->fullname }}.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="sl-status sl-status-active">Active Stockist</span>
        <a href="{{ route('user.stockist.history') }}" class="sl-btn sl-btn-outline">
            <i class="las la-history me-1"></i> Redemption History
        </a>
    </div>
</div>
 
{{-- ── Stats cards ───────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-primary">
            <div class="sl-stat-icon"><i class="las la-file-invoice"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Stockist Rebate</p>
                <h3 class="sl-stat-value">{{ showAmount(auth()->user()->stockist_rebate) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-info">
            <div class="sl-stat-icon"><i class="las la-receipt"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Stockist wallet</p>
                <h3 class="sl-stat-value">{{ showAmount(auth()->user()->stockist->wallet) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-gold">
            <div class="sl-stat-icon"><i class="las la-calendar-day"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Today's Redemptions</p>
                <h3 class="sl-stat-value">{{ $stats['today_redemptions'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-success">
            <div class="sl-stat-icon"><i class="las la-coins"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Total Processed</p>
                <h3 class="sl-stat-value">{{ showAmount($stats['total_amount']) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- ── LEFT: Redeem + Recent ───────────────────────── --}}
    <div class="col-12 col-lg-8">

        <div class="sl-card mb-4">
            <div class="sl-card-header"><i class="las la-qrcode me-1"></i> Redeem an Invoice</div>
            <div class="sl-card-body">
                <form id="redeemInvoiceForm">
                    <label class="sl-label" for="invoiceCodeInput">Invoice Code</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <input type="text" id="invoiceCodeInput" class="invoiceC sl-input" style="flex:1;min-width:220px;"
                               name="invoice_code" placeholder="Enter invoice code" required>
                        <button type="submit" class="sl-btn sl-btn-primary">
                            <i class="las la-search me-1"></i> Verify Invoice
                        </button>
                    </div>
                </form>

                {{-- Invoice Details --}}
                <div id="invoiceDetails" class="mt-4 d-none">
                    <div class="sl-card" style="border-color:var(--sl-green);">
                        <div class="sl-card-header" style="background:var(--sl-green-lt);color:var(--sl-green);">
                            <i class="las la-check-circle me-1"></i> Invoice Verified
                        </div>
                        <div class="sl-card-body">
                            <div id="invoiceItems"></div>
                            <form id="processRedemptionForm" class="mt-2">
                                <div id="redemptionItems"></div>
                                <div class="mt-3">
                                    <label class="sl-label" for="redemptionNotes">Notes (optional)</label>
                                    <textarea class="sl-input" id="redemptionNotes" name="notes" rows="2"
                                              placeholder="Add any notes about this redemption…"></textarea>
                                </div>
                                <div class="sl-form-actions mt-3" style="justify-content:flex-end;">
                                    <button type="submit" class="sl-btn sl-btn-primary">
                                        <i class="las la-check me-1"></i> Process Redemption
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sl-card">
            <div class="sl-card-header d-flex justify-content-between align-items-center">
                <span><i class="las la-history me-1"></i> Recent Redemptions</span>
            </div>
            <div class="sl-card-body p-0">
                @if($recentRedemptions->count() > 0)
                    <div class="table-responsive">
                        <table class="sl-table" aria-label="Recent redemptions">
                            <thead>
                                <tr>
                                    <th>Invoice</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentRedemptions as $redemption)
                                <tr>
                                    <td data-label="Invoice"><span class="fw-600">{{ $redemption->invoice->invoice_code }}</span></td>
                                    <td data-label="Customer">{{ $redemption->user->username }}</td>
                                    <td data-label="Amount" class="fw-600">{{ showAmount($redemption->total_amount) }}</td>
                                    <td data-label="Date">{{ $redemption->created_at->format('M j, H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center py-3">
                        <a href="{{ route('user.stockist.history') }}" class="sl-action-btn">
                            View Full History <i class="las la-arrow-right ms-1"></i>
                        </a>
                    </div>
                @else
                    <div class="sl-empty-state">
                        <div class="sl-empty-icon"><i class="las la-receipt"></i></div>
                        <p class="sl-empty-title">No redemptions yet</p>
                        <p class="sl-empty-sub">Verified redemptions you process will show up here.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── RIGHT: Quick actions + guide ────────────────── --}}
    <div class="col-12 col-lg-4">

        <div class="sl-card mb-4">
            <div class="sl-card-header"><i class="las la-bolt me-1"></i> Quick Actions</div>
            <div class="sl-card-body d-grid gap-2">
                <a href="{{ route('user.stockist.history') }}" class="sl-btn sl-btn-outline">
                    <i class="las la-history me-1"></i> Redemption History
                </a>
                <button type="button" class="sl-btn sl-btn-outline" onclick="clearForm()">
                    <i class="las la-broom me-1"></i> Clear Form
                </button>
            </div>
        </div>

        <div class="sl-card">
            <div class="sl-card-header"><i class="las la-info-circle me-1"></i> Redemption Guide</div>
            <div class="sl-card-body">
                <ol class="ps-3 mb-3" style="font-size:.85rem;color:var(--bk-text);">
                    <li class="mb-2">Enter the customer's invoice code</li>
                    <li class="mb-2">Verify the invoice details</li>
                    <li class="mb-2">Adjust quantities based on available stock</li>
                    <li class="mb-2">Process partial or full redemption</li>
                    <li class="mb-0">Provide remaining items to customer</li>
                </ol>
                <div class="alert sl-alert-warning mb-0" style="font-size:.8rem;">
                    <i class="las la-exclamation-triangle me-1"></i>
                    Customers can redeem remaining items at other stockists
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('modal')
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3" style="color:var(--sl-green);">
                    <i class="las la-check-circle" style="font-size:3.5rem;"></i>
                </div>
                <h4 class="mb-3">Redemption Successful!</h4>
                <p class="text-muted mb-4" id="successMessage"></p>
                <div class="d-grid gap-2">
                    <button type="button" class="sl-btn sl-btn-primary" data-bs-dismiss="modal">Continue</button>
                    <button type="button" class="sl-btn sl-btn-outline" onclick="clearForm()">Process Another</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="invoiceAlreadyRedeem" tabindex="-1" aria-labelledby="invoiceAlreadyRedeem" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3" style="color:#D97706;">
                    <i class="las la-exclamation-circle" style="font-size:3.5rem;"></i>
                </div>
                <h4 class="mb-3">Note</h4>
                <p class="text-muted mb-4" id="copiedInvoiceText"></p>
                <button type="button" class="sl-btn sl-btn-primary" data-bs-dismiss="modal">Continue</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('script')
<script>
function clearForm() {
    $('#redeemInvoiceForm')[0].reset();
    $('#invoiceDetails').addClass('d-none');
    $('.invoiceC').focus();
}

$(document).ready(function() {

    $('#redeemInvoiceForm').on('submit', function(e) {
        e.preventDefault();
        var invoiceCode = $('.invoiceC').val();

        $.ajax({
            url: "{{ route('user.stockist.verify.invoice') }}",
            method: 'POST',
            data: {
                invoice_code: invoiceCode,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    displayInvoiceDetails(response);
                } else {
                    $('#copiedInvoiceText').html(response.message);
                    $('#invoiceAlreadyRedeem').modal('show');
                }
            },
            error: function(xhr) {
                alert('Error verifying invoice. Please try again.');
            }
        });
    });

    $('#processRedemptionForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('invoice_code', $('.invoiceC').val());
        formData.append('_token', "{{ csrf_token() }}");

        $.ajax({
            url: "{{ route('user.stockist.process.redemption') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showSuccessModal(response);
                } else {
                    $('#copiedInvoiceText').html(response.message);
                    $('#invoiceAlreadyRedeem').modal('show');
                }
            },
            error: function(xhr) {
                alert('Error processing redemption. Please try again.');
            }
        });
    });

    function displayInvoiceDetails(response) {
        const { invoice, remaining_items, original_items, skus = {} } = response;
        const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

        let itemsHtml = `
            <h6 class="fw-600 mb-3">Order Items</h6>
            <div class="table-responsive">
                <table class="sl-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Original Qty</th>
                            <th>Redeemed</th>
                            <th>Remaining</th>
                            <th>Redeem Qty</th>
                        </tr>
                    </thead>
                    <tbody>`;

        original_items.forEach(item => {
            const remaining = remaining_items[item.product_id] || 0;
            const redeemed = item.quantity - remaining;

            itemsHtml += `
                <tr>
                    <td data-label="Product">${esc(item.product_name)}${skus[item.product_id] ? ` <span class="pp-sku"><span class="pp-sku__label">SKU</span>${esc(skus[item.product_id])}</span>` : ''}</td>
                    <td data-label="Original Qty">${item.quantity}</td>
                    <td data-label="Redeemed">${redeemed}</td>
                    <td data-label="Remaining">${remaining}</td>
                    <td data-label="Redeem Qty">
                        <input type="number"
                               class="sl-input redemption-qty"
                               name="redeemed_items[${item.product_id}]"
                               value="${remaining}"
                               min="0"
                               max="${remaining}"
                               style="width:90px;padding:.4rem .6rem;">
                    </td>
                </tr>`;
        });

        itemsHtml += `</tbody></table></div>`;
        $('#redemptionItems').html(itemsHtml);
        $('#invoiceDetails').removeClass('d-none');
    }

    function showSuccessModal(response) {
        const message = response.remaining_items && Object.keys(response.remaining_items).length > 0
            ? 'Partial redemption processed successfully. Customer can redeem remaining items at other stockists.'
            : 'Full redemption processed successfully. All items have been redeemed.';

        $('#successMessage').text(message);
        $('#successModal').modal('show');
    }
});
</script>
@endpush

@extends($activeTemplate . 'layouts.master')
@section('title', 'Transfer Bonus - Monthly Purchase Required')
@section('content')
@include($activeTemplate.'layouts.breadcrumb')
<div class="container-fluid py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Header -->
                <div class="text-center mb-5">
                    <h1 class="display-6 fw-bold text-dark mb-3">Transfer Unilevel Bonus</h1>
                    <p class="text-muted">Transfer funds from your unilevel bonus to main balance</p>
                </div> 
                
                <div class="row">
                    <!-- Wallet Balances -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-header bg-gradient-primary text-white rounded-top">
                                <h5 class="mb-0 text-center">Wallet Balances</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-4">
                                    <h6 class="text-muted mb-2">Unilevel Bonus</h6>
                                    <h4 class="fw-bold text-primary">₦{{ number_format($user->unilevel_bonus, 2) }}</h4>
                                </div>
                                <div>
                                    <h6 class="text-muted mb-2">Main Balance</h6>
                                    <h4 class="fw-bold text-success">₦{{ number_format($user->balance, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Purchase Status -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-3 h-100">
                            <div class="card-header bg-gradient-info text-white rounded-top">
                                <h5 class="mb-0 text-center">Monthly Purchase Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    @if($purchaseStatus['has_purchase'])
                                        <div class="text-success mb-2">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                        <h6 class="text-success mb-1">Purchase Made for {{ now()->format('F Y') }}</h6>
                                    @else
                                        <div class="text-warning mb-2">
                                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                                        </div>
                                        <h6 class="text-warning mb-1">No Purchase for {{ now()->format('F Y') }}</h6>
                                    @endif
                                </div>

                                @if($purchaseStatus['skipped_count'] > 0)
                                    <div class="alert alert-warning small mb-0">
                                        <i class="fas fa-history me-2"></i>
                                        You have {{ $purchaseStatus['skipped_count'] }} skipped month(s)
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transfer Form -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="fas fa-exchange-alt me-2 text-success"></i>
                            Transfer Funds
                        </h5>
                    </div>
                    <div class="card-body">
                        @if($purchaseStatus['can_transfer'])
                            <form id="transferForm">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Amount to Transfer</label>
                                        <input type="number" class="form-control" name="amount" 
                                               placeholder="Enter amount" min="1" max="{{ $user->unilevel_bonus }}" 
                                               step="0.01" required>
                                        <small class="text-muted">Maximum: ₦{{ number_format($user->unilevel_bonus, 2) }}</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">From Wallet</label>
                                        <input type="text" class="form-control bg-light" value="Unilevel Bonus" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">To Wallet</label>
                                        <input type="text" class="form-control bg-light" value="Main Balance" readonly>
                                    </div>
                                </div>
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-success btn-lg rounded-pill px-5">
                                        <i class="fas fa-paper-plane me-2"></i>Transfer Now
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="text-center py-4">
                                <div class="text-warning mb-3">
                                    <i class="fas fa-lock fa-3x"></i>
                                </div>
                                <h5 class="text-warning mb-3">Transfer Not Available</h5>
                                <p class="text-muted mb-4" id="transferMessage">
                                    {{ $purchaseStatus['has_purchase'] ? 
                                        'You have skipped purchases in previous months. Please make purchases for all skipped months.' : 
                                        'You need to make a product purchase for this month before transferring bonus.' 
                                    }}
                                </p>
                                <a href="{{ route('user.products') }}" class="btn btn-success rounded-pill px-4">
                                    <i class="fas fa-shopping-cart me-2"></i>Make Purchase
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Purchase History -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2 text-info"></i>
                            Monthly Purchase Requirements
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <div class="p-3 bg-light rounded-3">
                                    <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                                    <h6>Current Month</h6>
                                    <p class="small text-muted mb-0">{{ now()->format('F Y') }}</p>
                                    <span class="badge bg-{{ $purchaseStatus['has_purchase'] ? 'success' : 'warning' }} mt-2">
                                        {{ $purchaseStatus['has_purchase'] ? 'Completed' : 'Pending' }}
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 bg-light rounded-3">
                                    <i class="fas fa-clock fa-2x text-info mb-2"></i>
                                    <h6>Skipped Months</h6>
                                    <p class="small text-muted mb-0">Previous months</p>
                                    <span class="badge bg-{{ $purchaseStatus['skipped_count'] > 0 ? 'warning' : 'success' }} mt-2">
                                        {{ $purchaseStatus['skipped_count'] }} skipped
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 bg-light rounded-3">
                                    <i class="fas fa-exchange-alt fa-2x text-success mb-2"></i>
                                    <h6>Transfer Status</h6>
                                    <p class="small text-muted mb-0">Eligibility</p>
                                    <span class="badge bg-{{ $purchaseStatus['can_transfer'] ? 'success' : 'danger' }} mt-2">
                                        {{ $purchaseStatus['can_transfer'] ? 'Eligible' : 'Not Eligible' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        @if($purchaseStatus['skipped_count'] > 0)
                        <div class="mt-4">
                            <h6 class="fw-semibold mb-3">Skipped Months</h6>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                You need to make purchases for the following months:
                                <strong>{{ implode(', ', array_map(function($month) {
                                    return \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y');
                                }, $purchaseStatus['skipped_months'])) }}</strong>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                 <!-- Transfer History -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Transfer History</h5>
                                </div> 
                                <div class="card-body">
                                    @if($transfers->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Source</th>
                                                        <th>Amount</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($transfers as $transfer)
                                                        <tr>
                                                            <td>{{ $transfer->created_at->format('M d, Y H:i') }}</td>
                                                            <td>{{ $transfer->from_wallet }}</td>
                                                            <td class="text-success">+ {{ showAmount($transfer->amount) }}</td>
                                                            <td>
                                                                <span class="{{ $transfer->status_badge_class }}">
                                                                    {{ $transfer->status_text }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex justify-content-center">
                                            {{ $transfers->links() }}
                                        </div>
                                    @else
                                        <p class="text-center text-muted">No transfer history found.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

            </div>
        </div>
    </div>
</div>
@endsection
@push('modal')
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModal" aria-hidden="true">
<!-- Success Modal -->
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="text-success mb-3">
                    <i class="fas fa-check-circle fa-4x"></i>
                </div>
                <h4 class="text-dark mb-3">Transfer Successful!</h4>
                <p class="text-muted mb-4" id="successMessage"></p>
                <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-dismiss="modal">Continue</button>
            </div>
        </div>
    </div>
</div>

@endpush
@push('script') 
<script>
$(document).ready(function() {
    $('#transferForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
         
        $.ajax({
            url: "{{ route('user.bonus.transfer.submit') }}",
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#successMessage').html(`
                        Your transfer of <strong>₦${response.amount}</strong> was successful!<br>
                        New Unilevel Bonus: <strong>₦${response.new_unilevel_bonus}</strong><br>
                        New Balance: <strong>₦${response.new_balance}</strong>
                    `);
                    $('#successModal').modal('show');
                    
                    // Reload page after modal close to update balances
                    $('#successModal').on('hidden.bs.modal', function() {
                        location.reload();
                    });
                } else {
                    alert('Error: ' + response.message);
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                alert('An error occurred. Please try again.');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Auto-update purchase status every 30 seconds
    setInterval(function() {
        $.get("{{ route('user.bonus.purchase-status') }}", function(response) {
            if (response.success && !response.purchase_status.can_transfer) {
                location.reload();
            }
        });
    }, 30000);
});
</script>
@endpush


@push('style')
<style>
.badge {
    padding: 0.5em 0.75em;
    font-size: 0.875em;
}
.badge-warning {
    background-color: #ffc107;
    color: #212529;
}
.badge-success {
    background-color: #28a745;
    color: white;
}
.badge-danger {
    background-color: #dc3545;
    color: white;
}
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style>
@endpush

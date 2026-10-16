@extends('admin.layouts.app')

@section('title', 'Top-up Stockist Wallet')

@section('panel')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Top-up Stockist Wallet</h1>
        <p>
            <a href="{{ route('admin.stockist.details', $stockist) }}"  class="btn btn-primary" title="View Details">
                <i class="fas fa-eye"></i> View
            </a>

            <a href="{{ route('admin.stockist.dashboard') }}" class="btn btn-success">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </p>
        
        
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow">
                <div class="card-header bg--success text-white py-3">
                    <h6 class="m-0 font-weight-bold text-white">Wallet Top-up for <strong class="text-black">{{ $stockist->business_name }}</strong></h6>
                </div>
                <div class="card-body">
                    <!-- Stockist Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="info-card bg-light p-3 rounded">
                                <h6 class="font-weight-bold text-success">Current Wallet Balance</h6>
                                <h3 class="text-success">₦{{ number_format($stockist->wallet, 2) }}</h3>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card bg-light p-3 rounded">
                                <h6 class="font-weight-bold text-success">Store Type</h6>
                                <p class="mb-0">
                                    @if($stockist->store_type == 1)
                                        <span class="badge badge-success">Mega Store</span>
                                    @else
                                        <span class="badge badge--info">Mini Store</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Top-up Form -->
                    <form method="POST" action="{{ route('admin.stockist.topup-wallet', $stockist) }}">
                        @csrf
                        
                        <div class="form-group">
                            <label for="amount" class="font-weight-bold text-dark">Top-up Amount (₦) *</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-success text-white">₦</span>
                                </div>
                                <input type="number" class="form-control form-control-lg" id="amount" 
                                       name="amount" step="0.01" min="0" required 
                                       placeholder="Enter amount to top-up">
                            </div>
                            <small class="form-text text-muted">
                                Enter the amount you want to add to this stockist's wallet
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="notes" class="font-weight-bold text-dark">Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Add any notes about this transaction..."></textarea>
                        </div>

                        <!-- Preview Section -->
                        <div class="card mt-4 mb-4 d-none" id="previewCard">
                            <div class="card-header bg-info text-white">
                                <h6 class="m-0 font-weight-bold">Transaction Preview</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Current Balance:</strong> ₦{{ number_format($stockist->wallet, 2) }}</p>
                                        <p><strong>Top-up Amount:</strong> <span id="previewAmount" class="text-success font-weight-bold"></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>New Balance:</strong> <span id="previewNewBalance" class="text-primary font-weight-bold"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-wallet"></i> Confirm Top-up
                            </button>
                            <a href="{{ route('admin.stockist.dashboard') }}" class="btn btn-danger btn-lg">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
$(document).ready(function() {
    const currentBalance = {{ $stockist->wallet }};
    
    $('#amount').on('input', function() {
        const amount = parseFloat($(this).val()) || 0;
        
        if (amount > 0) {
            $('#previewAmount').text('₦' + amount.toFixed(2));
            $('#previewNewBalance').text('₦' + (currentBalance + amount).toFixed(2));
            $('#previewCard').removeClass('d-none');
        } else {
            $('#previewCard').addClass('d-none');
        }
    });

    $('form').on('submit', function() {
        const amount = parseFloat($('#amount').val());
        return confirm(`Are you sure you want to top-up ₦${amount.toFixed(2)} to ${$stockist->business_name}'s wallet?`);
    });
});
</script>

@push('style')
<style>
.info-card {
    border-left: 4px solid #4e73df;
}

.form-control-lg {
    font-size: 1.1rem;
    font-weight: 600;
}

.input-group-text {
    font-weight: 600;
}
</style>
@endpush
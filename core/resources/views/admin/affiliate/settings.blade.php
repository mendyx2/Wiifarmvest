@extends('admin.layouts.app')
@section('panel')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><h5 class="card-title">Affiliate Settings</h5></div>
            <div class="card-body">
                <form action="{{ route('admin.affiliate.settings.update') }}" method="POST">
                    @csrf

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="cash_on_pickup_enabled" id="cashOnPickup" value="1" {{ $settings->cash_on_pickup_enabled ? 'checked' : '' }}>
                        <label class="form-check-label" for="cashOnPickup">Enable Cash on Pickup</label>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" name="paystack_enabled" id="paystackEnabled" value="1" {{ $settings->paystack_enabled ? 'checked' : '' }}>
                        <label class="form-check-label" for="paystackEnabled">Enable Paystack Online Payment</label>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Attribution Cookie Window (days)</label>
                            <input type="number" name="cookie_days" min="1" max="365" class="form-control form--control" value="{{ $settings->cookie_days }}" required>
                            <small class="text-muted">How long a click keeps crediting the affiliate for a later purchase.</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pending Order Expiry (days)</label>
                            <input type="number" name="pending_order_expiry_days" min="1" max="90" class="form-control form--control" value="{{ $settings->pending_order_expiry_days }}" required>
                            <small class="text-muted">Unpaid / unpicked-up orders auto-cancel after this many days.</small>
                        </div>
                    </div>

                    <hr>
                    <h6 class="fw-bold mb-3">Stockist Pickup Handling Fee</h6>
                    <p class="text-muted small">Paid to the Stockist who confirms pickup for a Cash on Pickup order.</p>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fee Type</label>
                            <select name="stockist_pickup_fee_type" class="form-control form--control">
                                <option value="fixed" {{ $settings->stockist_pickup_fee_type === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                <option value="percentage" {{ $settings->stockist_pickup_fee_type === 'percentage' ? 'selected' : '' }}>Percentage of Order</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fee Value</label>
                            <input type="number" step="any" min="0" name="stockist_pickup_fee_value" class="form-control form--control" value="{{ $settings->stockist_pickup_fee_value }}">
                        </div>
                    </div>

                    <button type="submit" class="btn btn--primary mt-2">Save Settings</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

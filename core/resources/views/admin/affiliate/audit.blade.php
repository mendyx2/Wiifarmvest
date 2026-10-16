@extends('admin.layouts.app')
@section('panel')
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.affiliate.audit') }}" method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">From</label>
                        <input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="form-control form--control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">To</label>
                        <input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="form-control form--control">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn--primary w-100">Apply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row gy-4">
    <div class="col-lg-3 col-sm-6">
        <x-widget style="6" value="{{ getAmount($revenuePaystack) }}" title="Revenue — Paystack" icon="las la-credit-card" bg="primary" outline=false />
    </div>
    <div class="col-lg-3 col-sm-6">
        <x-widget style="6" value="{{ getAmount($revenueCash) }}" title="Revenue — Cash Collected" icon="las la-money-bill-wave" bg="success" outline=false />
    </div>
    <div class="col-lg-3 col-sm-6">
        <x-widget style="6" value="{{ getAmount($outstandingCash) }}" title="Outstanding Cash Liability" icon="las la-hourglass-half" bg="danger" outline=false />
    </div>
    <div class="col-lg-3 col-sm-6">
        <x-widget style="6" value="{{ getAmount($bonusPaid) }}" title="Affiliate Bonus Paid" icon="las la-gift" bg="warning" outline=false />
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h5 class="card-title">Net Summary</h5></div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><td>Total Revenue Collected</td><td class="text-end fw-bold">{{ getAmount($revenuePaystack + $revenueCash) }}</td></tr>
                    <tr><td>Less: Affiliate Bonus Paid</td><td class="text-end text-danger">&minus; {{ getAmount($bonusPaid) }}</td></tr>
                    <tr class="border-top"><td class="fw-bold">Net Revenue (excl. stockist fees)</td><td class="text-end fw-bold">{{ getAmount(($revenuePaystack + $revenueCash) - $bonusPaid) }}</td></tr>
                    <tr><td>Outstanding Cash-on-Pickup (not yet collected)</td><td class="text-end">{{ getAmount($outstandingCash) }}</td></tr>
                </table>
                <p class="text-muted small mt-3 mb-0">
                    Period: {{ $from->format('M d, Y') }} &ndash; {{ $to->format('M d, Y') }}.
                    Stockist handling fees are logged individually as stockist transactions and are not netted here.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

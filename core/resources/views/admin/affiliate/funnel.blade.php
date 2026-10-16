@extends('admin.layouts.app')
@section('panel')
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.affiliate.funnel') }}" method="GET" class="row g-2 align-items-end">
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

@php
    $stages = [
        ['label' => 'Clicks',        'value' => $funnel['clicks'],    'icon' => 'las la-mouse-pointer', 'bg' => 'primary'],
        ['label' => 'Orders Placed', 'value' => $funnel['placed'],    'icon' => 'las la-shopping-cart',  'bg' => 'info'],
        ['label' => 'Paid / Awaiting Pickup', 'value' => $funnel['paid'], 'icon' => 'las la-money-bill', 'bg' => 'warning'],
        ['label' => 'Fulfilled',     'value' => $funnel['fulfilled'], 'icon' => 'las la-check-circle',   'bg' => 'success'],
    ];
@endphp

<div class="row gy-4">
    @foreach($stages as $i => $stage)
        <div class="col-lg-3 col-sm-6">
            <x-widget style="6" value="{{ number_format($stage['value']) }}" title="{{ $stage['label'] }}" icon="{{ $stage['icon'] }}" bg="{{ $stage['bg'] }}" outline=false />
            @if($i > 0 && $stages[$i - 1]['value'] > 0)
                <p class="small text-muted text-center mt-1">{{ number_format(($stage['value'] / $stages[$i - 1]['value']) * 100, 1) }}% of previous stage</p>
            @endif
        </div>
    @endforeach
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-0">
                    Overall conversion (clicks &rarr; fulfilled orders):
                    <strong>{{ $funnel['clicks'] > 0 ? number_format(($funnel['fulfilled'] / $funnel['clicks']) * 100, 2) : '0.00' }}%</strong>
                    for the period {{ $from->format('M d, Y') }} &ndash; {{ $to->format('M d, Y') }}.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

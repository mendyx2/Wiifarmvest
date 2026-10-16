@extends('admin.layouts.app')

@section('panel')
<div class="str-wrap">

    <div class="str-hero">
        <div class="str-hero-icon"><i class="las la-clipboard-list"></i></div>
        <div class="str-hero-body">
            <h2 class="text-white">Stockist Redemption Report</h2>
            <p class="text-white">Stock and money moved by every stockist, broken down by cash, invoice code, welcome pack code and affiliate invoice code.</p>
        </div>
    </div>

    <div class="str-stats">
        @foreach($totals as $type => $t)
        <div class="str-stat str-stat--{{ $type }}">
            <div class="str-stat-icon"><i class="las la-{{ $type === 'cash' ? 'money-bill-wave' : ($type === 'invoice_code' ? 'file-invoice' : ($type === 'welcome_pack' ? 'gift' : 'share-alt')) }}"></i></div>
            <div>
                <div class="str-stat-val">{{ showAmount($t['amt']) }}</div>
                <div class="str-stat-lbl">{{ $t['label'] }} &middot; {{ $t['qty'] }} unit(s)</div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="str-card">
        <div class="str-card-head">
            <h6><i class="las la-store me-1"></i> Stockists</h6>
            <form action="" method="GET" class="str-filters">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search stockist…" class="str-input">
                <button type="submit" class="str-btn str-btn-primary"><i class="las la-search"></i> Search</button>
                @if(request('search'))
                    <a href="{{ route('admin.stockist.report') }}" class="str-btn str-btn-muted"><i class="las la-times"></i> Clear</a>
                @endif
            </form>
        </div>

        @if($stockists->count())
        <div class="table-responsive">
            <table class="str-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Stockist</th>
                        <th>State</th>
                        <th>Total Units Redeemed</th>
                        <th>Total Amount</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stockists as $i => $stockist)
                    <tr>
                        <td style="color:#94a3b8;font-size:.76rem;">{{ $stockists->firstItem() + $i }}</td>
                        <td>
                            <div class="str-user">
                                <div class="str-av">{{ strtoupper(substr($stockist->business_name ?? $stockist->user->firstname ?? '?', 0, 1)) }}</div>
                                <div>
                                    <div class="str-uname">{{ $stockist->business_name ?? ($stockist->user->fullname ?? '—') }}</div>
                                    <div class="str-uid">@ {{ $stockist->user->username ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $stockist->state->name ?? '—' }}</td>
                        <td>{{ $stockist->redemption_qty }}</td>
                        <td><span class="str-amt">{{ showAmount($stockist->redemption_amt) }}</span></td>
                        <td>
                            <a href="{{ route('admin.stockist.report.show', $stockist->id) }}" class="str-btn str-btn-primary">
                                <i class="las la-chart-bar"></i> View Report
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:12px 18px;border-top:1px solid #f1f5f9;">
            {{ paginateLinks($stockists) }}
        </div>
        @else
        <div class="str-empty">
            <i class="las la-store-slash"></i>
            <p>{{ request('search') ? 'No stockists match your search.' : 'No stockists found.' }}</p>
        </div>
        @endif
    </div>

</div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.stockist.report') }}" class="btn btn-sm btn-outline--info">
        <i class="las la-clipboard-list"></i> Redemption Report
    </a>
@endpush

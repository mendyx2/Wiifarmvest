@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="nc-wrap" id="ncWrap">
    <br>
    <div class="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="sl-page-title mb-1">Redemption Report</h4>
            <p class="sl-page-subtitle mb-0">Stock and money you've moved, broken down by cash, invoice code, welcome pack code and affiliate invoice code.</p>
        </div>
        <a href="{{ route('user.stockist.dashboard') }}" class="sl-btn sl-btn-outline">
            <i class="las la-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="{{ route('user.stockist.redemptions', ['type' => 'cash']) }}" class="text-decoration-none">
                <div class="sl-stat-card sl-stat-success">
                    <div class="sl-stat-icon"><i class="las la-money-bill-wave"></i></div>
                    <div class="sl-stat-body">
                        <p class="sl-stat-label">Cash</p>
                        <h3 class="sl-stat-value">{{ showAmount($totals['cash']['amt']) }}</h3>
                        <p class="sl-stat-note">{{ $totals['cash']['qty'] }} unit(s) &middot; {{ $totals['cash']['cnt'] }} sale(s)</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('user.stockist.redemptions', ['type' => 'invoice_code']) }}" class="text-decoration-none">
                <div class="sl-stat-card sl-stat-info">
                    <div class="sl-stat-icon"><i class="las la-file-invoice"></i></div>
                    <div class="sl-stat-body">
                        <p class="sl-stat-label">Invoice Code</p>
                        <h3 class="sl-stat-value">{{ showAmount($totals['invoice_code']['amt']) }}</h3>
                        <p class="sl-stat-note">{{ $totals['invoice_code']['qty'] }} unit(s) &middot; {{ $totals['invoice_code']['cnt'] }} redemption(s)</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('user.stockist.redemptions', ['type' => 'welcome_pack']) }}" class="text-decoration-none">
                <div class="sl-stat-card sl-stat-gold">
                    <div class="sl-stat-icon"><i class="las la-gift"></i></div>
                    <div class="sl-stat-body">
                        <p class="sl-stat-label">Welcome Pack Code</p>
                        <h3 class="sl-stat-value">{{ showAmount($totals['welcome_pack']['amt']) }}</h3>
                        <p class="sl-stat-note">{{ $totals['welcome_pack']['qty'] }} unit(s) &middot; {{ $totals['welcome_pack']['cnt'] }} redemption(s)</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="{{ route('user.stockist.redemptions', ['type' => 'affiliate_invoice']) }}" class="text-decoration-none">
                <div class="sl-stat-card sl-stat-primary">
                    <div class="sl-stat-icon"><i class="las la-share-alt"></i></div>
                    <div class="sl-stat-body">
                        <p class="sl-stat-label">Affiliate Invoice Code</p>
                        <h3 class="sl-stat-value">{{ showAmount($totals['affiliate_invoice']['amt']) }}</h3>
                        <p class="sl-stat-note">{{ $totals['affiliate_invoice']['qty'] }} unit(s) &middot; {{ $totals['affiliate_invoice']['cnt'] }} redemption(s)</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="sl-alert-success d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <strong>Grand Total: {{ showAmount($grandAmt) }}</strong>
        <span>{{ $grandQty }} unit(s) redeemed across all channels</span>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <a href="{{ route('user.stockist.redemptions') }}" class="sl-btn {{ !$type ? 'sl-btn-primary' : 'sl-btn-outline' }}">All</a>
        @foreach(\App\Models\StockistRedemption::$types as $t_type => $t_label)
            <a href="{{ route('user.stockist.redemptions', ['type' => $t_type]) }}" class="sl-btn {{ $type === $t_type ? 'sl-btn-primary' : 'sl-btn-outline' }}">{{ $t_label }}</a>
        @endforeach
    </div>

    <div class="sl-card">
        <div class="sl-card-header">
            Redemption History @if($type) &mdash; {{ \App\Models\StockistRedemption::$types[$type] ?? $type }} @endif
        </div>

        @if($redemptions->isEmpty())
            <div class="sl-empty-state">
                <div class="sl-empty-icon"><i class="las la-receipt"></i></div>
                <p class="sl-empty-title">No Redemptions Yet</p>
                <p class="sl-empty-sub">Redemptions across cash, invoice code, welcome pack and affiliate orders will appear here.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="sl-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Qty</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($redemptions as $r)
                        <tr>
                            <td><span class="sl-type-badge sl-type-{{ $r->type }}">{{ $r->type_label }}</span></td>
                            <td>{{ $r->reference_code ?: ($r->trx ?: '—') }}</td>
                            <td>
                                @if($r->customer)
                                    {{ $r->customer->fullname }}
                                @elseif($r->buyer_name)
                                    {{ $r->buyer_name }}
                                @else
                                    <span class="text-muted">Walk-in</span>
                                @endif
                            </td>
                            <td>{{ $r->quantity }}</td>
                            <td class="fw-600" style="color:var(--sl-green);">{{ showAmount($r->amount) }}</td>
                            <td>{{ optional($r->redeemed_at)->format('M j, Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="sl-card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <small class="text-muted">
                    Showing {{ $redemptions->firstItem() }} to {{ $redemptions->lastItem() }} of {{ $redemptions->total() }} redemptions
                </small>
                {{ $redemptions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

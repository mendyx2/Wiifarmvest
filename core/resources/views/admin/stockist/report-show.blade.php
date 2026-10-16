@extends('admin.layouts.app')

@section('panel')
<div class="str-wrap">

    <div class="str-hero">
        <div class="str-hero-icon"><i class="las la-store"></i></div>
        <div class="str-hero-body">
            <h2 class="text-white">{{ $stockist->business_name ?? ($stockist->user->fullname ?? 'Stockist') }}</h2>
            <p class="text-white">@ {{ $stockist->user->username ?? '' }} &middot; {{ $stockist->state->name ?? 'No state set' }}</p>
        </div>
    </div>

    <div class="str-grand">
        <div class="str-grand-lbl">Grand Total</div>
        <div class="str-grand-amt">{{ showAmount($grandAmt) }}</div>
        <div class="str-grand-qty">{{ $grandQty }} unit(s) redeemed across all channels</div>
    </div>

    <div class="str-stats str-stats--clickable">
        <a href="{{ route('admin.stockist.report.show', $stockist->id) }}" class="str-stat str-stat--all {{ !$type ? 'is-active' : '' }}">
            <div class="str-stat-icon"><i class="las la-layer-group"></i></div>
            <div>
                <div class="str-stat-val">{{ showAmount($grandAmt) }}</div>
                <div class="str-stat-lbl">All Channels &middot; {{ $grandQty }} unit(s)</div>
            </div>
        </a>
        @foreach($totals as $t_type => $t)
        <a href="{{ route('admin.stockist.report.show', ['stockist' => $stockist->id, 'type' => $t_type]) }}"
           class="str-stat str-stat--{{ $t_type }} {{ $type === $t_type ? 'is-active' : '' }}">
            <div class="str-stat-icon"><i class="las la-{{ $t_type === 'cash' ? 'money-bill-wave' : ($t_type === 'invoice_code' ? 'file-invoice' : ($t_type === 'welcome_pack' ? 'gift' : 'share-alt')) }}"></i></div>
            <div>
                <div class="str-stat-val">{{ showAmount($t['amt']) }}</div>
                <div class="str-stat-lbl">{{ $t['label'] }} &middot; {{ $t['qty'] }} unit(s) &middot; {{ $t['cnt'] }} redemption(s)</div>
            </div>
        </a>
        @endforeach
    </div>

    <div class="str-card">
        <div class="str-card-head">
            <h6><i class="las la-list me-1"></i> Redemption History @if($type) &mdash; {{ \App\Models\StockistRedemption::$types[$type] ?? $type }} @endif</h6>
            <form action="{{ route('admin.stockist.report.show', $stockist->id) }}" method="GET" class="str-filters">
                @if($type)<input type="hidden" name="type" value="{{ $type }}">@endif
                <input type="date" name="from" value="{{ request('from') }}" class="str-input">
                <input type="date" name="to" value="{{ request('to') }}" class="str-input">
                <button type="submit" class="str-btn str-btn-primary"><i class="las la-filter"></i> Filter</button>
                @if(request('from') || request('to'))
                    <a href="{{ route('admin.stockist.report.show', array_filter(['stockist' => $stockist->id, 'type' => $type])) }}" class="str-btn str-btn-muted"><i class="las la-times"></i> Clear Dates</a>
                @endif
            </form>
        </div>

        @if($redemptions->count())
        <div class="table-responsive">
            <table class="str-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Qty</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($redemptions as $i => $r)
                    <tr>
                        <td style="color:#94a3b8;font-size:.76rem;">{{ $redemptions->firstItem() + $i }}</td>
                        <td><span class="str-chip str-chip--{{ $r->type }}">{{ $r->type_label }}</span></td>
                        <td><span class="str-code">{{ $r->reference_code ?: ($r->trx ?: '—') }}</span></td>
                        <td>
                            @if($r->customer)
                                {{ $r->customer->fullname }}
                            @elseif($r->buyer_name)
                                {{ $r->buyer_name }}
                            @else
                                <span class="text-muted">Walk-in</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($r->items))
                                <span class="str-items-toggle" data-toggle-items="items-{{ $r->id }}">{{ count($r->items) }} product(s) <i class="las la-angle-down"></i></span>
                                <div class="str-items-detail" id="items-{{ $r->id }}" style="display:none;">
                                    @foreach($r->items as $item)
                                        <div>{{ $item['quantity'] }} &times; {{ $item['product_name'] }}</div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $r->quantity }}</td>
                        <td><span class="str-amt">{{ showAmount($r->amount) }}</span></td>
                        <td class="str-date">
                            {{ optional($r->redeemed_at)->format('d M Y') }}<br>
                            <small>{{ optional($r->redeemed_at)->format('H:i') }}</small>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div style="padding:12px 18px;border-top:1px solid #f1f5f9;">
            {{ paginateLinks($redemptions) }}
        </div>
        @else
        <div class="str-empty">
            <i class="las la-inbox"></i>
            <p>No redemptions found{{ $type ? ' for this channel' : '' }}.</p>
        </div>
        @endif
    </div>

</div>

<script>
document.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-toggle-items]');
    if (!toggle) return;
    var el = document.getElementById(toggle.getAttribute('data-toggle-items'));
    if (el) el.style.display = el.style.display === 'none' ? 'block' : 'none';
});
</script>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.stockist.report') }}" class="btn btn-sm btn-outline--info">
        <i class="las la-arrow-left"></i> All Stockists
    </a>
@endpush

@extends($activeTemplate . 'layouts.master')

@section('content')

<div class="nc-wrap" id="ncWrap">
    <br/>
<div class="txn-page">

    {{-- ── Filter Card ── --}}
    @if(!request()->routeIs('user.recharge.log'))
    <div class="txn-filter-card">
        <form method="GET" class="txn-filter-form">
            <div class="txn-filter-field">
                <label>Transaction Ref</label>
                <div class="txn-filter-input-wrap">
                    <i class="las la-search"></i>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search reference...">
                </div>
            </div>
            <div class="txn-filter-field">
                <label>Type</label>
                <select name="trx_type">
                    <option value="">All Types</option>
                    <option value="+" @selected(request('trx_type') == '+')>Credit (+)</option>
                    <option value="-" @selected(request('trx_type') == '-')>Debit (−)</option>
                </select>
            </div>
            @isset($remarks)
            <div class="txn-filter-field">
                <label>Remark</label>
                <select name="remark">
                    <option value="">All Remarks</option>
                    @foreach($remarks as $remark)
                        <option value="{{ $remark->remark }}" @selected(request('remark') == $remark->remark)>
                            {{ keyToTitle($remark->remark) }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endisset
            <div class="txn-filter-field txn-filter-btn-wrap">
                <label>&nbsp;</label>
                <button type="submit" class="txn-filter-btn">
                    <i class="las la-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
    @endif

    {{-- ── Transaction List ── --}}
    <div class="txn-list-card">
        <div class="txn-list-head">
            <h6 class="txn-list-title"><i class="las la-exchange-alt"></i> Transactions</h6>
            @if(isset($transactions) && $transactions->total() > 0)
                <span class="txn-count-pill">{{ $transactions->total() }} records</span>
            @endif
        </div>

        @php $items = $transactions ?? $logs ?? collect(); @endphp

        @forelse($items as $trx)
        <div class="txn-row">
            {{-- Type indicator --}}
            <div class="txn-type-icon {{ $trx->trx_type == '+' ? 'credit' : 'debit' }}">
                <i class="las {{ $trx->trx_type == '+' ? 'la-arrow-down' : 'la-arrow-up' }}"></i>
            </div>

            {{-- Details --}}
            <div class="txn-row-info">
                <p class="txn-row-desc">{{ __($trx->details) }}</p>
                <p class="txn-row-ref">
                    <i class="las la-hashtag"></i> {{ $trx->trx }}
                    <span class="txn-row-dot">·</span>
                    {{ diffForHumans($trx->created_at) }}
                    <span class="txn-row-dot">·</span>
                    <span class="txn-inline-date">{{ showDateTime($trx->created_at, 'd M Y') }}</span>
                </p>
            </div>

            {{-- Amount + balance --}}
            <div class="txn-row-right">
                <p class="txn-row-amount {{ $trx->trx_type == '+' ? 'credit' : 'debit' }}">
                    {{ $trx->trx_type == '+' ? '+' : '−' }}{{ showAmount($trx->amount) }}
                </p>
                @if($trx->charge > 0)
                    <p class="txn-row-charge">Fee: {{ showAmount($trx->charge) }}</p>
                @endif
                <p class="txn-row-bal">Bal: {{ showAmount($trx->post_balance) }}</p>
            </div>

            {{-- Date --}}
            <div class="txn-row-date">
                <span>{{ showDateTime($trx->created_at, 'd M') }}</span>
                <span class="txn-row-year">{{ showDateTime($trx->created_at, 'Y') }}</span>
            </div>
        </div>
        @empty
        <div class="txn-empty">
            <div class="txn-empty-icon"><i class="las la-file-invoice-dollar"></i></div>
            <h6>No transactions yet</h6>
            <p>Your transaction history will appear here.</p>
        </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if(isset($transactions) && $transactions->hasPages())
        <div>{{ paginateLinks($transactions) }}</div>
    @elseif(isset($logs) && $logs->hasPages())
        <div>{{ paginateLinks($logs) }}</div>
    @endif

</div>
</div>
@endsection

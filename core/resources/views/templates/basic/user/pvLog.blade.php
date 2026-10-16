@extends($activeTemplate . 'layouts.master')
@section('content')

<div class="pv-page">

    {{-- ══ HERO ══ --}}
    <div class="pv-hero">
        <div class="pv-hero-glow"></div>
        <div class="pv-hero-inner">
            <div class="pv-hero-left">
                <div class="pv-hero-eyebrow">
                    <span class="pv-live-dot"></span>
                    Binary Network
                </div>
                <h2 class="pv-hero-title">Point Value Log</h2>
                <!-- <p class="pv-hero-sub">Track every PV credit and debit across your binary legs</p> -->
            </div>
            <div class="pv-hero-badge">
                <i class="las la-chart-line pv-hero-badge-icon"></i>
            </div>
        </div>
    </div>

    {{-- ══ STAT CARDS ══ --}}
    <div class="pv-stats-grid">
        <div class="pv-stat-card pv-stat--left">
            <div class="pv-stat-icon"><i class="las la-arrow-circle-left"></i></div>
            <div class="pv-stat-body">
                <p class="pv-stat-label">Total Left PV</p>
                <p class="pv-stat-val">{{ getAmount($totalLeft) }}</p>
            </div>
            <a href="{{ route('user.pv.log') }}?type=leftPV" class="pv-stat-link">View <i class="las la-arrow-right"></i></a>
        </div>
        <div class="pv-stat-card pv-stat--right">
            <div class="pv-stat-icon"><i class="las la-arrow-circle-right"></i></div>
            <div class="pv-stat-body">
                <p class="pv-stat-label">Total Right PV</p>
                <p class="pv-stat-val">{{ getAmount($totalRight) }}</p>
            </div>
            <a href="{{ route('user.pv.log') }}?type=rightPV" class="pv-stat-link">View <i class="las la-arrow-right"></i></a>
        </div>
        {{--
        <div class="pv-stat-card pv-stat--cut">
            <div class="pv-stat-icon"><i class="las la-cut"></i></div>
            <div class="pv-stat-body">
                <p class="pv-stat-label">Total Cut PV</p>
                <p class="pv-stat-val">{{ getAmount($totalCut) }}</p>
            </div>
            <a href="{{ route('user.pv.log') }}?type=cutPV" class="pv-stat-link">View <i class="las la-arrow-right"></i></a>
        </div>
        --}}
        <div class="pv-stat-card pv-stat--total">
            <div class="pv-stat-icon"><i class="las la-star"></i></div>
            <div class="pv-stat-body">
                <p class="pv-stat-label">Total Paid PV</p>
                <p class="pv-stat-val">{{ getAmount($totalAll) }}</p>
            </div>
            <a href="{{ route('user.pv.log') }}" class="pv-stat-link">View All <i class="las la-arrow-right"></i></a>
        </div>
    </div>

    {{-- ══ FILTER TABS ══ --}}
    <div class="pv-tabs-wrap">
        @php $activeType = request('type', ''); @endphp
        <a href="{{ route('user.pv.log') }}"
           class="pv-tab {{ $activeType === '' ? 'pv-tab--active' : '' }}">
            <i class="las la-list"></i> All
        </a>
        <a href="{{ route('user.pv.log') }}?type=leftPV"
           class="pv-tab pv-tab--left {{ $activeType === 'leftPV' ? 'pv-tab--active' : '' }}">
            <i class="las la-arrow-left"></i> Left PV
        </a>
        <a href="{{ route('user.pv.log') }}?type=rightPV"
           class="pv-tab pv-tab--right {{ $activeType === 'rightPV' ? 'pv-tab--active' : '' }}">
            <i class="las la-arrow-right"></i> Right PV
        </a>
        {{--
        <a href="{{ route('user.pv.log') }}?type=cutPV"
           class="pv-tab pv-tab--cut {{ $activeType === 'cutPV' ? 'pv-tab--active' : '' }}">
            <i class="las la-cut"></i> Cut PV
        </a>
        --}}
    </div>

    {{-- ══ LOG FEED ══ --}}
    <div class="pv-feed-card">
        <div class="pv-feed-head">
            <h6 class="pv-feed-title">
                @if($activeType === 'leftPV')   <i class="las la-arrow-circle-left"></i> Left PV Entries
                @elseif($activeType === 'rightPV') <i class="las la-arrow-circle-right"></i> Right PV Entries
                @elseif($activeType === 'cutPV')   <i class="las la-cut"></i> Cut PV Entries
                @else <i class="las la-history"></i> All PV Entries
                @endif
            </h6>
            @if($logs->total() > 0)
                <span class="pv-count-pill">{{ number_format($logs->total()) }} records</span>
            @endif
        </div>

        @forelse($logs as $key => $log)
        @php
            $isLeft  = $log->position == 1;
            $isCredit = $log->trx_type == '+';
            $typeClass = !$isCredit ? 'cut' : ($isLeft ? 'left' : 'right');
        @endphp
        <div class="pv-row">
            {{-- Indicator dot ──────────── --}}
            <div class="pv-row-dot pv-dot--{{ $typeClass }}">
                @if(!$isCredit)
                    <i class="las la-cut"></i>
                @elseif($isLeft)
                    <i class="las la-arrow-left"></i>
                @else
                    <i class="las la-arrow-right"></i>
                @endif
            </div>

            {{-- Details ────────────────── --}}
            <div class="pv-row-body">
                <p class="pv-row-desc">{{ $log->details ?: 'PV Transaction' }}</p>
                <div class="pv-row-meta">
                    <span class="pv-leg-badge pv-leg--{{ $typeClass }}">
                        @if(!$isCredit) Cut
                        @elseif($isLeft) Left Leg
                        @else Right Leg
                        @endif
                    </span>
                    <span class="pv-meta-dot">·</span>
                    <span class="pv-meta-num">#{{ $logs->firstItem() + $key }}</span>
                    <span class="pv-meta-dot">·</span>
                    <span>{{ $log->created_at ? date('d M Y, g:i A', strtotime($log->created_at)) : '—' }}</span>
                </div>
            </div>

            {{-- Amount ──────────────────── --}}
            <div class="pv-row-amount pv-amt--{{ $isCredit ? 'credit' : 'debit' }}">
                <span class="pv-amt-sign">{{ $isCredit ? '+' : '−' }}</span>
                <span class="pv-amt-val">{{ getAmount($log->amount) }}</span>
                <span class="pv-amt-unit">PV</span>
            </div>
        </div>
        @empty
        <div class="pv-empty">
            <div class="pv-empty-icon">
                <i class="las la-chart-bar"></i>
            </div>
            <p class="pv-empty-title">No PV Entries</p>
            <p class="pv-empty-sub">{{ __($emptyMessage) }}</p>
            <a href="{{ route('user.pv.log') }}" class="pv-empty-btn">View All PV</a>
        </div>
        @endforelse
    </div>

    {{-- ══ PAGINATION ══ --}}
    @if($logs->hasPages())
    <div class="pv-pagination">
        {{ paginateLinks($logs) }}
    </div>
    @endif

</div>

@endsection

@push('style')
<style>
/* ═══════════════════════════════════════════════════════════════
   PV LOG PAGE  ·  theme: --bk-primary #0D5C2E
   ═══════════════════════════════════════════════════════════════ */

.pv-page {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 20px;
}

/* ── Hero ─────────────────────────────────────────────────────── */
.pv-hero {
    position: relative;
    background: linear-gradient(135deg, #0D5C2E 0%, #16A34A 60%, #22c55e 100%);
    border-radius: 18px;
    padding: 32px 32px 28px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(13,92,46,.25);
}
.pv-hero-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 80% 50%, rgba(255,255,255,.10) 0%, transparent 60%);
    pointer-events: none;
}
.pv-hero-inner {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}
.pv-hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: rgba(255,255,255,.70);
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    margin-bottom: 10px;
}
.pv-live-dot {
    width: 7px; height: 7px;
    border-radius: 50%;
    background: #86efac;
    box-shadow: 0 0 0 3px rgba(134,239,172,.30);
    animation: pvPulse 2s ease infinite;
}
@keyframes pvPulse {
    0%,100% { box-shadow: 0 0 0 3px rgba(134,239,172,.30); }
    50%      { box-shadow: 0 0 0 6px rgba(134,239,172,.10); }
}
.pv-hero-title {
    font-size: clamp(20px, 3vw, 26px);
    font-weight: 800;
    color: #fff;
    margin: 0 0 6px;
    line-height: 1.2;
}
.pv-hero-sub {
    font-size: 13px;
    color: rgba(255,255,255,.65);
    margin: 0;
}
.pv-hero-badge {
    width: 72px; height: 72px;
    flex-shrink: 0;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.20);
    border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(8px);
}
.pv-hero-badge-icon {
    font-size: 34px;
    color: #fff;
}

/* ── Stat cards ───────────────────────────────────────────────── */
.pv-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}
@media (max-width: 900px) { .pv-stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .pv-stats-grid { grid-template-columns: 1fr 1fr; } }

.pv-stat-card {
    background: var(--bk-surface);
    border-radius: 14px;
    border: 1px solid var(--bk-border);
    padding: 18px 16px 14px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
    transition: transform .2s ease, box-shadow .2s ease;
    position: relative;
    overflow: hidden;
}
.pv-stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 28px rgba(0,0,0,.10);
}
/* coloured left stripe */
.pv-stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; bottom: 0;
    width: 4px;
    border-radius: 14px 0 0 14px;
}
.pv-stat--left::before  { background: #16A34A; }
.pv-stat--right::before { background: #2563EB; }
.pv-stat--cut::before   { background: #DC2626; }
.pv-stat--total::before { background: #CA8A04; }

.pv-stat-icon {
    font-size: 22px;
    line-height: 1;
}
.pv-stat--left .pv-stat-icon  { color: #16A34A; }
.pv-stat--right .pv-stat-icon { color: #2563EB; }
.pv-stat--cut .pv-stat-icon   { color: #DC2626; }
.pv-stat--total .pv-stat-icon { color: #CA8A04; }

.pv-stat-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .7px;
    color: var(--bk-muted);
    margin: 0;
}
.pv-stat-val {
    font-size: 20px;
    font-weight: 800;
    color: var(--bk-text);
    margin: 0;
    line-height: 1.1;
}
.pv-stat-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 11px;
    font-weight: 600;
    color: var(--bk-muted);
    text-decoration: none;
    transition: color .18s;
    margin-top: auto;
}
.pv-stat-link:hover { color: var(--bk-accent); }

/* ── Filter tabs ──────────────────────────────────────────────── */
.pv-tabs-wrap {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.pv-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: 50px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    color: var(--bk-muted);
    background: var(--bk-surface);
    border: 1.5px solid var(--bk-border);
    transition: all .2s ease;
    white-space: nowrap;
}
.pv-tab:hover { border-color: #16A34A; color: #16A34A; }

.pv-tab--active                  { background: var(--bk-primary); color: #fff; border-color: var(--bk-primary); }
.pv-tab--left.pv-tab--active     { background: #16A34A; border-color: #16A34A; }
.pv-tab--right.pv-tab--active    { background: #2563EB; border-color: #2563EB; }
.pv-tab--cut.pv-tab--active      { background: #DC2626; border-color: #DC2626; }

/* ── Feed card ────────────────────────────────────────────────── */
.pv-feed-card {
    background: var(--bk-surface);
    border-radius: 16px;
    border: 1px solid var(--bk-border);
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
    overflow: hidden;
}
.pv-feed-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 22px;
    border-bottom: 1px solid var(--bk-border);
}
.pv-feed-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--bk-text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 7px;
}
.pv-feed-title i { color: var(--bk-accent); font-size: 16px; }
.pv-count-pill {
    background: #F0FDF4;
    color: #16A34A;
    border: 1px solid #BBF7D0;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
}

/* ── Feed rows ────────────────────────────────────────────────── */
.pv-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 22px;
    border-bottom: 1px solid #F3F4F6;
    transition: background .15s;
}
.pv-row:last-child { border-bottom: none; }
.pv-row:hover      { background: #FAFAFA; }

/* dot */
.pv-row-dot {
    width: 40px; height: 40px;
    border-radius: 12px;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
}
.pv-dot--left  { background: #F0FDF4; color: #16A34A; }
.pv-dot--right { background: #EFF6FF; color: #2563EB; }
.pv-dot--cut   { background: #FEF2F2; color: #DC2626; }

/* body */
.pv-row-body { flex: 1; min-width: 0; }
.pv-row-desc {
    font-size: 13px;
    font-weight: 600;
    color: var(--bk-text);
    margin: 0 0 5px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.pv-row-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
    font-size: 11px;
    color: var(--bk-muted);
}
.pv-meta-dot { color: #D1D5DB; }
.pv-meta-num { font-weight: 600; }

/* leg badge */
.pv-leg-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
}
.pv-leg--left  { background: #DCFCE7; color: #166534; }
.pv-leg--right { background: #DBEAFE; color: #1E40AF; }
.pv-leg--cut   { background: #FEE2E2; color: #991B1B; }

/* amount */
.pv-row-amount {
    display: flex;
    align-items: baseline;
    gap: 2px;
    flex-shrink: 0;
    font-weight: 800;
    white-space: nowrap;
}
.pv-amt--credit { color: #16A34A; }
.pv-amt--debit  { color: #DC2626; }
.pv-amt-sign  { font-size: 14px; }
.pv-amt-val   { font-size: 17px; }
.pv-amt-unit  { font-size: 10px; font-weight: 600; color: var(--bk-muted); margin-left: 2px; }

/* ── Empty state ──────────────────────────────────────────────── */
.pv-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 60px 24px;
    gap: 10px;
}
.pv-empty-icon {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: #F0FDF4;
    display: flex; align-items: center; justify-content: center;
    font-size: 32px;
    color: #86EFAC;
    margin-bottom: 4px;
}
.pv-empty-title {
    font-size: 16px;
    font-weight: 700;
    color: var(--bk-text);
    margin: 0;
}
.pv-empty-sub {
    font-size: 13px;
    color: var(--bk-muted);
    margin: 0;
}
.pv-empty-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 10px;
    padding: 9px 20px;
    background: var(--bk-primary);
    color: #fff;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: opacity .2s;
}
.pv-empty-btn:hover { opacity: .85; color: #fff; }

/* ── Pagination ───────────────────────────────────────────────── */
.pv-pagination { display: flex; justify-content: center; }
</style>
@endpush

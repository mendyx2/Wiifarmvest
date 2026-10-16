@extends('admin.layouts.app')

@push('style')
<style>
/* ══════════════════════════════════════════════════════
   PV LOG — Admin View
══════════════════════════════════════════════════════ */

/* ── Hero ──────────────────────────────────────────── */
.pvl-hero {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 26px;
    display: flex;
    align-items: center;
    gap: 20px;
    position: relative;
    overflow: hidden;
}
.pvl-hero::before {
    content: '';
    position: absolute;
    right: -40px; bottom: -40px;
    width: 200px; height: 200px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    pointer-events: none;
}
.pvl-hero::after {
    content: '';
    position: absolute;
    right: 60px; top: -20px;
    width: 100px; height: 100px;
    border-radius: 50%;
    background: rgba(255,255,255,.03);
    pointer-events: none;
}
.pvl-hero-icon {
    width: 54px; height: 54px;
    border-radius: 14px;
    background: rgba(255,255,255,.1);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; color: #94a3b8;
    flex-shrink: 0;
}
.pvl-hero-body h2 {
    font-size: 1.3rem; font-weight: 800;
    color: #f1f5f9; margin: 0 0 5px;
    letter-spacing: -.02em;
}
.pvl-hero-body p { font-size: .83rem; color: #64748b; margin: 0; }

/* ── Stat cards ────────────────────────────────────── */
.pvl-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 24px;
}
@media (max-width: 900px) { .pvl-stats { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .pvl-stats { grid-template-columns: 1fr; } }

.pvl-stat {
    background: #fff;
    border: 1px solid #e8edf2;
    border-radius: 13px;
    padding: 18px 16px;
    display: flex;
    align-items: flex-start;
    gap: 13px;
    position: relative;
    overflow: hidden;
    transition: box-shadow .18s;
}
.pvl-stat:hover { box-shadow: 0 4px 18px rgba(0,0,0,.07); }
.pvl-stat-accent {
    position: absolute;
    top: 0; left: 0;
    width: 3px; height: 100%;
    border-radius: 4px 0 0 4px;
}
.pvl-stat-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; flex-shrink: 0;
}
.pvl-stat-body { flex: 1; min-width: 0; }
.pvl-stat-val {
    font-size: 1.3rem; font-weight: 800;
    color: #0f172a; line-height: 1;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.pvl-stat-lbl { font-size: .71rem; color: #64748b; margin-top: 4px; font-weight: 500; }

/* ── Filter toolbar ────────────────────────────────── */
.pvl-toolbar {
    background: #fff;
    border: 1px solid #e8edf2;
    border-radius: 13px;
    padding: 16px 20px;
    margin-bottom: 16px;
    display: flex;
    align-items: flex-end;
    gap: 10px;
    flex-wrap: wrap;
}
.pvl-field { display: flex; flex-direction: column; gap: 5px; }
.pvl-label {
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .08em;
    color: #94a3b8;
}
.pvl-input, .pvl-select {
    height: 38px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: .83rem;
    color: #1e293b;
    padding: 0 12px;
    background: #fff;
    transition: border-color .18s, box-shadow .18s;
    outline: none;
}
.pvl-input:focus, .pvl-select:focus {
    border-color: #475569;
    box-shadow: 0 0 0 3px rgba(71,85,105,.1);
}
.pvl-input { min-width: 220px; }
.pvl-select { min-width: 130px; }

.pvl-search-wrap { position: relative; }
.pvl-search-icon {
    position: absolute;
    left: 11px; top: 50%;
    transform: translateY(-50%);
    color: #94a3b8; font-size: .85rem;
    pointer-events: none;
}
.pvl-search-wrap .pvl-input { padding-left: 32px; }

.pvl-btn {
    height: 38px; padding: 0 18px;
    border-radius: 8px;
    font-size: .82rem; font-weight: 700;
    border: none; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    transition: all .18s; text-decoration: none; white-space: nowrap;
}
.pvl-btn-primary { background: #1e293b; color: #fff; }
.pvl-btn-primary:hover { background: #0f172a; color: #fff; transform: translateY(-1px); }
.pvl-btn-ghost {
    background: transparent;
    border: 1.5px solid #e2e8f0;
    color: #64748b;
}
.pvl-btn-ghost:hover { border-color: #94a3b8; color: #1e293b; }

.pvl-toolbar-actions { display: flex; gap: 7px; align-items: flex-end; }

/* ── Active filters display ────────────────────────── */
.pvl-active-filters {
    display: flex; gap: 6px; flex-wrap: wrap;
    margin-bottom: 14px;
}
.pvl-filter-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: .72rem; font-weight: 600;
    background: #f1f5f9; color: #475569;
    border: 1px solid #e2e8f0;
}
.pvl-filter-chip a { color: #94a3b8; text-decoration: none; font-size: .75rem; line-height: 1; }
.pvl-filter-chip a:hover { color: #dc2626; }

/* ── Table card ────────────────────────────────────── */
.pvl-card {
    background: #fff;
    border: 1px solid #e8edf2;
    border-radius: 14px;
    overflow: hidden;
}
.pvl-card-head {
    padding: 14px 20px;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px;
}
.pvl-card-head-title {
    font-size: .76rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em;
    color: #64748b; margin: 0;
    display: flex; align-items: center; gap: 6px;
}
.pvl-count-badge {
    font-size: .65rem; font-weight: 800;
    padding: 2px 8px; border-radius: 20px;
    background: #f1f5f9; color: #475569;
}

/* Table itself */
.pvl-table { width: 100%; border-collapse: collapse; }
.pvl-table th {
    padding: 10px 16px;
    text-align: left;
    font-size: .67rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .09em;
    color: #94a3b8; background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
    white-space: nowrap;
}
.pvl-table td {
    padding: 13px 16px;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
}
.pvl-table tbody tr:last-child td { border-bottom: none; }
.pvl-table tbody tr { transition: background .12s; }
.pvl-table tbody tr:hover td { background: #f8fafc; }

/* Row stripe for visual rhythm */
.pvl-table tbody tr:nth-child(even) td { background: #fafbfc; }
.pvl-table tbody tr:nth-child(even):hover td { background: #f3f4f6; }

/* ── Cell components ───────────────────────────────── */
.pvl-user { display: flex; align-items: center; gap: 10px; }
.pvl-av {
    width: 36px; height: 36px;
    border-radius: 9px;
    background: linear-gradient(135deg, #334155, #1e293b);
    color: #94a3b8;
    font-size: .76rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; letter-spacing: -.02em;
}
.pvl-uname { font-weight: 700; font-size: .85rem; color: #0f172a; }
.pvl-uid   { font-size: .7rem; color: #94a3b8; }

/* Position badge */
.pvl-pos {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .72rem; font-weight: 700;
    padding: 4px 11px; border-radius: 20px;
    white-space: nowrap;
}
.pvl-pos-left  { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.pvl-pos-right { background: #faf5ff; color: #7c3aed; border: 1px solid #ddd6fe; }
.pvl-pos i     { font-size: .7rem; }

/* Amount + type */
.pvl-type-row { display: flex; align-items: center; gap: 7px; }
.pvl-type-dot {
    width: 7px; height: 7px;
    border-radius: 50%; flex-shrink: 0;
}
.pvl-type-dot--credit { background: #059669; }
.pvl-type-dot--debit  { background: #dc2626; }

.pvl-amount {
    font-size: .9rem; font-weight: 800;
    display: inline-flex; align-items: center; gap: 4px;
}
.pvl-amount--credit { color: #059669; }
.pvl-amount--debit  { color: #dc2626; }
.pvl-amount-sign    { font-size: .8rem; opacity: .7; }

/* Details text */
.pvl-detail {
    font-size: .77rem; color: #64748b;
    max-width: 260px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

/* Date */
.pvl-date { font-size: .77rem; color: #475569; white-space: nowrap; }
.pvl-date small { color: #94a3b8; font-size: .7rem; display: block; margin-top: 1px; }

/* Row number */
.pvl-num { font-size: .73rem; color: #cbd5e1; font-weight: 600; }

/* ── Empty state ───────────────────────────────────── */
.pvl-empty {
    padding: 64px 24px;
    text-align: center;
}
.pvl-empty-icon {
    width: 64px; height: 64px;
    border-radius: 16px;
    background: #f1f5f9;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; color: #94a3b8;
    margin: 0 auto 16px;
}
.pvl-empty h5 { font-size: .95rem; font-weight: 700; color: #475569; margin: 0 0 6px; }
.pvl-empty p  { font-size: .82rem; color: #94a3b8; margin: 0; }

/* ── Pagination wrapper ─────────────────────────────── */
.pvl-pagination { padding: 14px 18px; border-top: 1px solid #f1f5f9; }

/* ── Responsive ─────────────────────────────────────── */
@media (max-width: 767px) {
    .pvl-hero { flex-direction: column; align-items: flex-start; gap: 12px; }
    .pvl-toolbar { flex-direction: column; }
    .pvl-input, .pvl-select { min-width: 100%; width: 100%; }
    .pvl-toolbar-actions { width: 100%; }
    .pvl-btn { width: 100%; justify-content: center; }
    .pvl-detail { max-width: 160px; }
}
</style>
@endpush

@section('panel')

{{-- ── Hero ─────────────────────────────────────────── --}}
<div class="pvl-hero">
    <div class="pvl-hero-icon"><i class="las la-project-diagram"></i></div>
    <div class="pvl-hero-body">
        <h2>PV Logs</h2>
        <p>Full audit trail of every point-volume movement across all binary legs — credits, cuts, and position records.</p>
    </div>
</div>

{{-- ── Stats ──────────────────────────────────────────── --}}
<div class="pvl-stats">
    <div class="pvl-stat">
        <div class="pvl-stat-accent" style="background:#1d4ed8;"></div>
        <div class="pvl-stat-icon" style="background:#eff6ff;color:#1d4ed8;">
            <i class="las la-arrow-alt-circle-left"></i>
        </div>
        <div class="pvl-stat-body">
            <div class="pvl-stat-val">{{ number_format($totalLeft, 2) }}</div>
            <div class="pvl-stat-lbl">Total Left PV Credited</div>
        </div>
    </div>
    <div class="pvl-stat">
        <div class="pvl-stat-accent" style="background:#7c3aed;"></div>
        <div class="pvl-stat-icon" style="background:#faf5ff;color:#7c3aed;">
            <i class="las la-arrow-alt-circle-right"></i>
        </div>
        <div class="pvl-stat-body">
            <div class="pvl-stat-val">{{ number_format($totalRight, 2) }}</div>
            <div class="pvl-stat-lbl">Total Right PV Credited</div>
        </div>
    </div>
    <div class="pvl-stat">
        <div class="pvl-stat-accent" style="background:#dc2626;"></div>
        <div class="pvl-stat-icon" style="background:#fef2f2;color:#dc2626;">
            <i class="las la-minus-circle"></i>
        </div>
        <div class="pvl-stat-body">
            <div class="pvl-stat-val">{{ number_format($totalCut, 2) }}</div>
            <div class="pvl-stat-lbl">Total PV Deducted</div>
        </div>
    </div>
    <div class="pvl-stat">
        <div class="pvl-stat-accent" style="background:#64748b;"></div>
        <div class="pvl-stat-icon" style="background:#f8fafc;color:#64748b;">
            <i class="las la-list-alt"></i>
        </div>
        <div class="pvl-stat-body">
            <div class="pvl-stat-val">{{ number_format($totalRecords) }}</div>
            <div class="pvl-stat-lbl">Total Log Entries</div>
        </div>
    </div>
</div>

{{-- ── Filter toolbar ──────────────────────────────────── --}}
<form action="" method="GET">
    <div class="pvl-toolbar">
        {{-- Username search --}}
        <div class="pvl-field" style="flex:1;">
            <span class="pvl-label">Search</span>
            <div class="pvl-search-wrap">
                <i class="las la-search pvl-search-icon"></i>
                <input type="text" name="search" value="{{ $search }}"
                       class="pvl-input" placeholder="Username, first or last name…"
                       style="width:100%;">
            </div>
        </div>

        {{-- Position filter --}}
        <div class="pvl-field">
            <span class="pvl-label">Position</span>
            <select name="position" class="pvl-select">
                <option value="">All Positions</option>
                <option value="1" {{ $position == '1' ? 'selected' : '' }}>Left</option>
                <option value="2" {{ $position == '2' ? 'selected' : '' }}>Right</option>
            </select>
        </div>

        {{-- Type filter --}}
        <div class="pvl-field">
            <span class="pvl-label">Type</span>
            <select name="type" class="pvl-select">
                <option value="">All Types</option>
                <option value="+" {{ $type === '+' ? 'selected' : '' }}>Credit (+)</option>
                <option value="-" {{ $type === '-' ? 'selected' : '' }}>Debit (−)</option>
            </select>
        </div>

        <div class="pvl-toolbar-actions">
            <button type="submit" class="pvl-btn pvl-btn-primary">
                <i class="las la-search"></i> Filter
            </button>
            @if($search || $position || $type)
            <a href="{{ route('admin.pv-logs.index') }}" class="pvl-btn pvl-btn-ghost">
                <i class="las la-times"></i> Clear
            </a>
            @endif
        </div>
    </div>
</form>

{{-- Active filter chips --}}
@if($search || $position || $type)
<div class="pvl-active-filters">
    @if($search)
    <span class="pvl-filter-chip">
        <i class="las la-user" style="font-size:.75rem;"></i>
        "{{ $search }}"
        <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" title="Remove">×</a>
    </span>
    @endif
    @if($position)
    <span class="pvl-filter-chip">
        <i class="las la-{{ $position == 1 ? 'arrow-alt-circle-left' : 'arrow-alt-circle-right' }}" style="font-size:.75rem;"></i>
        {{ $position == 1 ? 'Left Leg' : 'Right Leg' }}
        <a href="{{ request()->fullUrlWithQuery(['position' => null]) }}" title="Remove">×</a>
    </span>
    @endif
    @if($type)
    <span class="pvl-filter-chip">
        {{ $type === '+' ? '＋ Credit' : '− Debit' }}
        <a href="{{ request()->fullUrlWithQuery(['type' => null]) }}" title="Remove">×</a>
    </span>
    @endif
</div>
@endif

{{-- ── Table ────────────────────────────────────────────── --}}
<div class="pvl-card">
    <div class="pvl-card-head">
        <h6 class="pvl-card-head-title">
            <i class="las la-table"></i>
            Log Entries
            <span class="pvl-count-badge">{{ number_format($logs->total()) }}</span>
        </h6>
        <span style="font-size:.74rem;color:#94a3b8;">
            Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}
        </span>
    </div>

    @if($logs->count())
    <div class="table-responsive">
        <table class="pvl-table">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>User</th>
                    <th>Position</th>
                    <th>Amount</th>
                    <th>Details</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $i => $log)
                @php
                    $isCredit = $log->trx_type === '+';
                    $isLeft   = $log->position == 1;
                    $u        = $log->user;
                @endphp
                <tr>
                    <td><span class="pvl-num">{{ $logs->firstItem() + $i }}</span></td>

                    {{-- User --}}
                    <td>
                        @if($u)
                        <div class="pvl-user">
                            <div class="pvl-av">
                                {{ strtoupper(substr($u->firstname ?? '?', 0, 1)) }}{{ strtoupper(substr($u->lastname ?? '', 0, 1)) }}
                            </div>
                            <div>
                                <div class="pvl-uname">{{ $u->fullname }}</div>
                                <div class="pvl-uid">@ {{ $u->username }}</div>
                            </div>
                        </div>
                        @else
                        <span style="font-size:.78rem;color:#94a3b8;">— deleted —</span>
                        @endif
                    </td>

                    {{-- Position --}}
                    <td>
                        @if($isLeft)
                        <span class="pvl-pos pvl-pos-left">
                            <i class="las la-arrow-alt-circle-left"></i> Left
                        </span>
                        @else
                        <span class="pvl-pos pvl-pos-right">
                            <i class="las la-arrow-alt-circle-right"></i> Right
                        </span>
                        @endif
                    </td>

                    {{-- Amount --}}
                    <td>
                        <div class="pvl-type-row">
                            <div class="pvl-type-dot {{ $isCredit ? 'pvl-type-dot--credit' : 'pvl-type-dot--debit' }}"></div>
                            <span class="pvl-amount {{ $isCredit ? 'pvl-amount--credit' : 'pvl-amount--debit' }}">
                                <span class="pvl-amount-sign">{{ $log->trx_type }}</span>{{ number_format($log->amount, 2) }} PV
                            </span>
                        </div>
                    </td>

                    {{-- Details --}}
                    <td>
                        <span class="pvl-detail" title="{{ $log->details }}">
                            {{ $log->details ?: '—' }}
                        </span>
                    </td>

                    {{-- Date --}}
                    <td>
                        <div class="pvl-date">
                            {{ $log->created_at->format('d M Y') }}
                            <small>{{ $log->created_at->format('h:i A') }}</small>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pvl-pagination">
        {{ paginateLinks($logs) }}
    </div>
    @else
    <div class="pvl-empty">
        <div class="pvl-empty-icon"><i class="las la-search"></i></div>
        <h5>{{ ($search || $position || $type) ? 'No records match your filter' : 'No PV logs yet' }}</h5>
        <p>{{ ($search || $position || $type) ? 'Try adjusting your search terms or clearing the filters.' : 'PV movements will appear here once users start earning.' }}</p>
    </div>
    @endif
</div>

@endsection

@push('breadcrumb-plugins')
<a href="{{ route('admin.pv-logs.index') }}" class="btn btn-sm btn-outline--primary">
    <i class="las la-project-diagram"></i> PV Logs
</a>
@endpush

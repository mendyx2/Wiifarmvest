@extends('admin.layouts.app')

@push('style')
<style>
/* ══════════════════════════════════════════════════
   AUTOSHIP ADMIN
══════════════════════════════════════════════════ */
.as-wrap { padding: 0; }

/* Hero */
.as-hero {
    background: linear-gradient(135deg, #0c3f4e 0%, #0891b2 100%);
    border-radius: 16px;
    padding: 26px 30px;
    margin-bottom: 26px;
    display: flex;
    align-items: center;
    gap: 18px;
    position: relative;
    overflow: hidden;
    color: #fff;
}
.as-hero::after {
    content: '';
    position: absolute;
    right: -30px; top: -30px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
    pointer-events: none;
}
.as-hero-icon {
    width: 52px; height: 52px;
    background: rgba(255,255,255,.15);
    border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0;
}
.as-hero-body h2 { font-size: 1.25rem; font-weight: 800; margin: 0 0 4px; letter-spacing: -.02em; }
.as-hero-body p  { font-size: .82rem; opacity: .72; margin: 0; }

/* Stats row */
.as-stats {
    display: flex;
    gap: 14px;
    margin-bottom: 22px;
    flex-wrap: wrap;
}
.as-stat {
    flex: 1; min-width: 140px;
    background: #fff;
    border: 1px solid #e8edf2;
    border-radius: 12px;
    padding: 15px 16px;
    display: flex; align-items: center; gap: 11px;
}
.as-stat-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; flex-shrink: 0;
}
.as-stat-val { font-size: 1.25rem; font-weight: 800; color: #0f172a; line-height: 1; }
.as-stat-lbl { font-size: .7rem; color: #64748b; margin-top: 3px; font-weight: 500; }

/* Tab bar */
.as-tabs {
    display: flex;
    gap: 4px;
    background: #f1f5f9;
    border-radius: 12px;
    padding: 5px;
    margin-bottom: 20px;
    width: fit-content;
}
.as-tab {
    padding: 8px 20px;
    border-radius: 8px;
    font-size: .82rem;
    font-weight: 700;
    color: #64748b;
    text-decoration: none;
    display: flex; align-items: center; gap: 7px;
    transition: all .18s;
    white-space: nowrap;
}
.as-tab:hover { color: #0891b2; background: #fff; }
.as-tab.active {
    background: #fff;
    color: #0891b2;
    box-shadow: 0 1px 6px rgba(8,145,178,.15);
}
.as-tab .as-tab-count {
    font-size: .65rem;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 20px;
    background: #e0f2fe;
    color: #0369a1;
}
.as-tab.active .as-tab-count { background: #0891b2; color: #fff; }

/* Card shell */
.as-card {
    background: #fff;
    border: 1px solid #e8edf2;
    border-radius: 14px;
    overflow: hidden;
}
.as-card-head {
    padding: 15px 20px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: wrap;
}
.as-card-head h6 {
    font-size: .76rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em;
    color: #64748b; margin: 0;
}
.as-filters { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.as-input {
    height: 34px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    font-size: .8rem;
    padding: 0 11px;
    background: #fff;
    transition: border-color .18s, box-shadow .18s;
}
.as-input:focus { outline: none; border-color: #0891b2; box-shadow: 0 0 0 3px rgba(8,145,178,.1); }
.as-btn {
    height: 34px; padding: 0 14px;
    border-radius: 8px;
    font-size: .78rem; font-weight: 700;
    border: none; cursor: pointer; transition: background .18s;
    display: inline-flex; align-items: center; gap: 5px;
    text-decoration: none;
}
.as-btn-primary { background: #0891b2; color: #fff; }
.as-btn-primary:hover { background: #0e7490; color: #fff; }
.as-btn-muted { background: #e2e8f0; color: #475569; }
.as-btn-muted:hover { background: #cbd5e1; color: #1e293b; }

/* Table */
.as-table { width: 100%; border-collapse: collapse; }
.as-table th {
    padding: 10px 16px;
    text-align: left;
    font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .08em;
    color: #94a3b8; background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}
.as-table td { padding: 13px 16px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.as-table tbody tr:last-child td { border-bottom: none; }
.as-table tbody tr:hover td { background: #fafbfc; }

/* Cells */
.as-user { display: flex; align-items: center; gap: 9px; }
.as-av {
    width: 34px; height: 34px;
    border-radius: 9px;
    background: linear-gradient(135deg, #0891b2, #0e7490);
    color: #fff; font-size: .76rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.as-av--red   { background: linear-gradient(135deg,#dc2626,#991b1b); }
.as-av--green { background: linear-gradient(135deg,#059669,#047857); }
.as-uname { font-weight: 700; font-size: .85rem; color: #0f172a; line-height: 1.2; }
.as-uid   { font-size: .7rem; color: #94a3b8; }

.as-amt {
    font-size: .9rem; font-weight: 800;
    padding: 3px 10px; border-radius: 6px;
    display: inline-block;
}
.as-amt--teal  { color: #0891b2; background: #ecfeff; border: 1px solid #a5f3fc; }
.as-amt--red   { color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; }
.as-amt--green { color: #059669; background: #f0fdf4; border: 1px solid #bbf7d0; }

.as-chip {
    font-size: .7rem; font-weight: 700;
    padding: 2px 9px; border-radius: 20px;
    display: inline-block;
}
.as-chip--blue { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

.as-trx { font-size: .7rem; color: #94a3b8; font-family: monospace; }
.as-date { font-size: .76rem; color: #475569; }
.as-date small { color: #94a3b8; }

.as-detail { font-size: .76rem; color: #64748b; max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* Empty */
.as-empty { padding: 56px 24px; text-align: center; color: #94a3b8; }
.as-empty i { font-size: 2.6rem; display: block; margin-bottom: 12px; opacity: .32; }
.as-empty p { font-size: .86rem; margin: 0; }

@media (max-width: 767px) {
    .as-hero { flex-direction: column; text-align: center; }
    .as-tabs { flex-wrap: wrap; width: 100%; }
    .as-tab  { flex: 1; justify-content: center; }
    .as-filters { flex-direction: column; width: 100%; }
    .as-input, .as-btn { width: 100%; justify-content: center; }
}
</style>
@endpush

@section('panel')
<div class="as-wrap">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="as-hero">
        <div class="as-hero-icon"><i class="las la-sync-alt"></i></div>
        <div class="as-hero-body">
            <h2 class="text-white">Autoship</h2>
            <p class="text-white">20% of each Matching Bonus is held here until the user buys a product. Unclaimed amounts are swept to admin on the last day of every month.</p>
        </div>
    </div>

    {{-- ── Stats ────────────────────────────────────────────── --}}
    <div class="as-stats">
        <div class="as-stat">
            <div class="as-stat-icon" style="background:#ecfeff;color:#0891b2;"><i class="las la-wallet"></i></div>
            <div>
                <div class="as-stat-val">{{ showAmount($totalCurrent) }}</div>
                <div class="as-stat-lbl">Currently Holding ({{ $holdingCount }} users)</div>
            </div>
        </div>
        <div class="as-stat">
            <div class="as-stat-icon" style="background:#f0fdf4;color:#059669;"><i class="las la-arrow-circle-down"></i></div>
            <div>
                <div class="as-stat-val">{{ showAmount($totalGained) }}</div>
                <div class="as-stat-lbl">Total Credited (All Time)</div>
            </div>
        </div>
        <div class="as-stat">
            <div class="as-stat-icon" style="background:#fef2f2;color:#dc2626;"><i class="las la-times-circle"></i></div>
            <div>
                <div class="as-stat-val">{{ showAmount($totalThisMonth) }}</div>
                <div class="as-stat-lbl">Swept This Month</div>
            </div>
        </div>
        <div class="as-stat">
            <div class="as-stat-icon" style="background:#fef9c3;color:#854d0e;"><i class="las la-ban"></i></div>
            <div>
                <div class="as-stat-val">{{ showAmount($totalLost) }}</div>
                <div class="as-stat-lbl">Total Swept (All Time)</div>
            </div>
        </div>
    </div>

    {{-- ── Tab bar ──────────────────────────────────────────── --}}
    <div class="as-tabs">
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'current',  'search' => null, 'month' => null]) }}"
           class="as-tab {{ $tab === 'current' ? 'active' : '' }}">
            <i class="las la-wallet"></i> Holding
            @if($holdingCount > 0)
                <span class="as-tab-count">{{ $holdingCount }}</span>
            @endif
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'gained',   'search' => null, 'month' => null]) }}"
           class="as-tab {{ $tab === 'gained'  ? 'active' : '' }}">
            <i class="las la-arrow-circle-down"></i> Gained
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'lost',     'search' => null, 'month' => null]) }}"
           class="as-tab {{ $tab === 'lost'    ? 'active' : '' }}">
            <i class="las la-times-circle"></i> Lost
        </a>
    </div>

    {{-- ══════════════════════════════════════════════════════
         TAB: HOLDING (current autoship balances)
    ══════════════════════════════════════════════════════ --}}
    @if($tab === 'current')
    <div class="as-card">
        <div class="as-card-head">
            <h6><i class="las la-wallet me-1"></i> Users Currently Holding Autoship</h6>
            <form action="" method="GET" class="as-filters">
                <input type="hidden" name="tab" value="current">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search user…" class="as-input" style="width:190px;">
                <button type="submit" class="as-btn as-btn-primary"><i class="las la-search"></i> Search</button>
                @if($search)
                    <a href="{{ route('admin.autoship.index', ['tab' => 'current']) }}" class="as-btn as-btn-muted"><i class="las la-times"></i> Clear</a>
                @endif
            </form>
        </div>

        @if($current->count())
        <div class="table-responsive">
            <table class="as-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Autoship Balance</th>
                        <th>Matching Bonus</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($current as $i => $u)
                    <tr>
                        <td style="color:#94a3b8;font-size:.76rem;">{{ $current->firstItem() + $i }}</td>
                        <td>
                            <div class="as-user">
                                <div class="as-av">{{ strtoupper(substr($u->firstname,0,1)) }}{{ strtoupper(substr($u->lastname,0,1)) }}</div>
                                <div>
                                    <div class="as-uname">{{ $u->fullname }}</div>
                                    <div class="as-uid">@ {{ $u->username }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="as-date">{{ $u->email }}</td>
                        <td><span class="as-amt as-amt--teal">{{ showAmount($u->autoship) }}</span></td>
                        <td><span class="as-amt as-amt--green">{{ showAmount($u->matching_bonus ?? 0) }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($current->hasPages())
        <div style="padding:12px 18px;border-top:1px solid #f1f5f9;">
            {{ paginateLinks($current) }}
        </div>
        @endif
        @else
        <div class="as-empty">
            <i class="las la-wallet"></i>
            <p>{{ $search ? 'No users match your search.' : 'No user is currently holding an autoship balance.' }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════
         TAB: GAINED (autoship credits from matching splits)
    ══════════════════════════════════════════════════════ --}}
    @if($tab === 'gained')
    <div class="as-card">
        <div class="as-card-head">
            <h6><i class="las la-arrow-circle-down me-1"></i> Autoship Credits — 20% Matching Splits</h6>
            <form action="" method="GET" class="as-filters">
                <input type="hidden" name="tab" value="gained">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search user…" class="as-input" style="width:190px;">
                <button type="submit" class="as-btn as-btn-primary"><i class="las la-search"></i> Search</button>
                @if($search)
                    <a href="{{ route('admin.autoship.index', ['tab' => 'gained']) }}" class="as-btn as-btn-muted"><i class="las la-times"></i> Clear</a>
                @endif
            </form>
        </div>

        @if($gained->count())
        <div class="table-responsive">
            <table class="as-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Amount Credited</th>
                        <th>Details</th>
                        <th>Date</th>
                        <th>TRX</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gained as $i => $txn)
                    @php $u = $txn->user; @endphp
                    <tr>
                        <td style="color:#94a3b8;font-size:.76rem;">{{ $gained->firstItem() + $i }}</td>
                        <td>
                            <div class="as-user">
                                <div class="as-av as-av--green">{{ strtoupper(substr($u->firstname ?? '?',0,1)) }}{{ strtoupper(substr($u->lastname ?? '',0,1)) }}</div>
                                <div>
                                    <div class="as-uname">{{ $u->fullname ?? '—' }}</div>
                                    <div class="as-uid">@ {{ $u->username ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="as-amt as-amt--green">{{ showAmount($txn->amount) }}</span></td>
                        <td><span class="as-detail" title="{{ $txn->details }}">{{ $txn->details }}</span></td>
                        <td class="as-date">
                            {{ $txn->created_at->format('d M Y') }}<br>
                            <small>{{ $txn->created_at->format('H:i') }}</small>
                        </td>
                        <td><span class="as-trx">{{ $txn->trx }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($gained->hasPages())
        <div style="padding:12px 18px;border-top:1px solid #f1f5f9;">
            {{ paginateLinks($gained) }}
        </div>
        @endif
        @else
        <div class="as-empty">
            <i class="las la-arrow-circle-down"></i>
            <p>{{ $search ? 'No credits match your search.' : 'No autoship credits recorded yet.' }}</p>
        </div>
        @endif
    </div>
    @endif

    {{-- ══════════════════════════════════════════════════════
         TAB: LOST (month-end sweeps)
    ══════════════════════════════════════════════════════ --}}
    @if($tab === 'lost')
    <div class="as-card">
        <div class="as-card-head">
            <h6><i class="las la-times-circle me-1"></i> Month-End Sweeps — Unclaimed Balances</h6>
            <form action="" method="GET" class="as-filters">
                <input type="hidden" name="tab" value="lost">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search user…" class="as-input" style="width:170px;">
                <select name="month" class="as-input" style="width:120px;">
                    <option value="">All Months</option>
                    @foreach($months as $m)
                        <option value="{{ $m }}" {{ $month === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
                <button type="submit" class="as-btn as-btn-primary"><i class="las la-search"></i> Filter</button>
                @if($search || $month)
                    <a href="{{ route('admin.autoship.index', ['tab' => 'lost']) }}" class="as-btn as-btn-muted"><i class="las la-times"></i> Clear</a>
                @endif
            </form>
        </div>

        @if($lost->count())
        <div class="table-responsive">
            <table class="as-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Amount Swept</th>
                        <th>Month</th>
                        <th>Swept On</th>
                        <th>TRX</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lost as $i => $rec)
                    @php $u = $rec->user; @endphp
                    <tr>
                        <td style="color:#94a3b8;font-size:.76rem;">{{ $lost->firstItem() + $i }}</td>
                        <td>
                            <div class="as-user">
                                <div class="as-av as-av--red">{{ strtoupper(substr($u->firstname ?? '?',0,1)) }}{{ strtoupper(substr($u->lastname ?? '',0,1)) }}</div>
                                <div>
                                    <div class="as-uname">{{ $u->fullname ?? '—' }}</div>
                                    <div class="as-uid">@ {{ $u->username ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="as-amt as-amt--red">{{ showAmount($rec->amount) }}</span></td>
                        <td><span class="as-chip as-chip--blue">{{ $rec->month }}</span></td>
                        <td class="as-date">
                            {{ $rec->swept_at->format('d M Y') }}<br>
                            <small>{{ $rec->swept_at->format('H:i') }}</small>
                        </td>
                        <td><span class="as-trx">{{ $rec->trx }}</span></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($lost->hasPages())
        <div style="padding:12px 18px;border-top:1px solid #f1f5f9;">
            {{ paginateLinks($lost) }}
        </div>
        @endif
        @else
        <div class="as-empty">
            <i class="las la-times-circle"></i>
            <p>{{ ($search || $month) ? 'No sweep records match your filter.' : 'No autoship sweeps have occurred yet.' }}</p>
        </div>
        @endif
    </div>
    @endif

</div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.autoship.index') }}" class="btn btn-sm btn-outline--info">
        <i class="las la-sync-alt"></i> Autoship
    </a>
@endpush

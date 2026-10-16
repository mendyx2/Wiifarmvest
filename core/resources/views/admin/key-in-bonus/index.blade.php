@extends('admin.layouts.app')

@push('style')
<style>
/* ══════════════════════════════════════════════════
   KEY-IN BONUS ADMIN
══════════════════════════════════════════════════ */
.kib-wrap { padding: 0; }

.kib-hero {
    background: linear-gradient(135deg, #4c1d95 0%, #7c3aed 100%);
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
.kib-hero::after {
    content: '';
    position: absolute;
    right: -30px; top: -30px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
    pointer-events: none;
}
.kib-hero-icon {
    width: 52px; height: 52px;
    background: rgba(255,255,255,.15);
    border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0;
}
.kib-hero-body h2 { font-size: 1.25rem; font-weight: 800; margin: 0 0 4px; letter-spacing: -.02em; }
.kib-hero-body p  { font-size: .82rem; opacity: .72; margin: 0; }

.kib-stats { display: flex; gap: 14px; margin-bottom: 22px; flex-wrap: wrap; }
.kib-stat {
    flex: 1; min-width: 140px;
    background: #fff;
    border: 1px solid #e8edf2;
    border-radius: 12px;
    padding: 15px 16px;
    display: flex; align-items: center; gap: 11px;
}
.kib-stat-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; flex-shrink: 0;
}
.kib-stat-val { font-size: 1.25rem; font-weight: 800; color: #0f172a; line-height: 1; }
.kib-stat-lbl { font-size: .7rem; color: #64748b; margin-top: 3px; font-weight: 500; }

.kib-tabs {
    display: flex;
    gap: 4px;
    background: #f1f5f9;
    border-radius: 12px;
    padding: 5px;
    margin-bottom: 20px;
    width: fit-content;
}
.kib-tab {
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
.kib-tab:hover { color: #7c3aed; background: #fff; }
.kib-tab.active { background: #fff; color: #7c3aed; box-shadow: 0 1px 6px rgba(124,58,237,.15); }
.kib-tab .kib-tab-count {
    font-size: .65rem; font-weight: 800;
    padding: 2px 7px; border-radius: 20px;
    background: #ede9fe; color: #6d28d9;
}
.kib-tab.active .kib-tab-count { background: #7c3aed; color: #fff; }

.kib-card { background: #fff; border: 1px solid #e8edf2; border-radius: 14px; overflow: hidden; }
.kib-card-head {
    padding: 15px 20px;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px; flex-wrap: wrap;
}
.kib-card-head h6 {
    font-size: .76rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em;
    color: #64748b; margin: 0;
}
.kib-filters { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.kib-input {
    height: 34px; border-radius: 8px; border: 1px solid #e2e8f0;
    font-size: .8rem; padding: 0 11px; background: #fff;
    transition: border-color .18s, box-shadow .18s;
}
.kib-input:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124,58,237,.1); }
.kib-btn {
    height: 34px; padding: 0 14px; border-radius: 8px;
    font-size: .78rem; font-weight: 700;
    border: none; cursor: pointer; transition: background .18s;
    display: inline-flex; align-items: center; gap: 5px;
    text-decoration: none;
}
.kib-btn-primary { background: #7c3aed; color: #fff; }
.kib-btn-primary:hover { background: #6d28d9; color: #fff; }
.kib-btn-muted { background: #e2e8f0; color: #475569; }
.kib-btn-muted:hover { background: #cbd5e1; color: #1e293b; }

.kib-table { width: 100%; border-collapse: collapse; }
.kib-table th {
    padding: 10px 16px; text-align: left;
    font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .08em;
    color: #94a3b8; background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}
.kib-table td { padding: 13px 16px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.kib-table tbody tr:last-child td { border-bottom: none; }
.kib-table tbody tr:hover td { background: #fafbfc; }

.kib-user { display: flex; align-items: center; gap: 9px; }
.kib-av {
    width: 34px; height: 34px; border-radius: 9px;
    background: linear-gradient(135deg, #7c3aed, #6d28d9);
    color: #fff; font-size: .76rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.kib-uname { font-weight: 700; font-size: .85rem; color: #0f172a; line-height: 1.2; }
.kib-uid   { font-size: .7rem; color: #94a3b8; }

.kib-amt {
    font-size: .9rem; font-weight: 800;
    padding: 3px 10px; border-radius: 6px;
    display: inline-block;
    color: #7c3aed; background: #f5f3ff; border: 1px solid #ddd6fe;
}
.kib-trx { font-size: .7rem; color: #94a3b8; font-family: monospace; }
.kib-date { font-size: .76rem; color: #475569; }
.kib-date small { color: #94a3b8; }
.kib-detail { font-size: .76rem; color: #64748b; max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.kib-empty { padding: 56px 24px; text-align: center; color: #94a3b8; }
.kib-empty i { font-size: 2.6rem; display: block; margin-bottom: 12px; opacity: .32; }
.kib-empty p { font-size: .86rem; margin: 0; }

@media (max-width: 767px) {
    .kib-hero { flex-direction: column; text-align: center; }
    .kib-tabs { flex-wrap: wrap; width: 100%; }
    .kib-tab  { flex: 1; justify-content: center; }
    .kib-filters { flex-direction: column; width: 100%; }
    .kib-input, .kib-btn { width: 100%; justify-content: center; }
}
</style>
@endpush

@section('panel')
<div class="kib-wrap">

    <div class="kib-hero">
        <div class="kib-hero-icon"><i class="las la-keyboard"></i></div>
        <div class="kib-hero-body">
            <h2 class="text-white">Key-In Bonus</h2>
            <p class="text-white">2% of a new distributor's registration fee, paid to the sponsor who keyed in the registration. Credited immediately and never expires.</p>
        </div>
    </div>

    <div class="kib-stats">
        <div class="kib-stat">
            <div class="kib-stat-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="las la-wallet"></i></div>
            <div>
                <div class="kib-stat-val">{{ showAmount($totalCurrent) }}</div>
                <div class="kib-stat-lbl">Currently Held ({{ $holderCount }} users)</div>
            </div>
        </div>
        <div class="kib-stat">
            <div class="kib-stat-icon" style="background:#f0fdf4;color:#059669;"><i class="las la-arrow-circle-down"></i></div>
            <div>
                <div class="kib-stat-val">{{ showAmount($totalGained) }}</div>
                <div class="kib-stat-lbl">Total Earned (All Time)</div>
            </div>
        </div>
    </div>

    <div class="kib-tabs">
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'current', 'search' => null]) }}"
           class="kib-tab {{ $tab === 'current' ? 'active' : '' }}">
            <i class="las la-wallet"></i> Holders
            @if($holderCount > 0)
                <span class="kib-tab-count">{{ $holderCount }}</span>
            @endif
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'gained', 'search' => null]) }}"
           class="kib-tab {{ $tab === 'gained' ? 'active' : '' }}">
            <i class="las la-arrow-circle-down"></i> Gained
        </a>
    </div>

    @if($tab === 'current')
    <div class="kib-card">
        <div class="kib-card-head">
            <h6><i class="las la-wallet me-1"></i> Users Holding Key-In Bonus</h6>
            <form action="" method="GET" class="kib-filters">
                <input type="hidden" name="tab" value="current">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search user…" class="kib-input" style="width:190px;">
                <button type="submit" class="kib-btn kib-btn-primary"><i class="las la-search"></i> Search</button>
                @if($search)
                    <a href="{{ route('admin.key-in-bonus.index', ['tab' => 'current']) }}" class="kib-btn kib-btn-muted"><i class="las la-times"></i> Clear</a>
                @endif
            </form>
        </div>

        @if($current->count())
        <div class="table-responsive">
            <table class="kib-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Email</th>
                        <th>Key-In Bonus</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($current as $i => $u)
                    <tr>
                        <td style="color:#94a3b8;font-size:.76rem;">{{ $current->firstItem() + $i }}</td>
                        <td>
                            <div class="kib-user">
                                <div class="kib-av">{{ strtoupper(substr($u->firstname,0,1)) }}{{ strtoupper(substr($u->lastname,0,1)) }}</div>
                                <div>
                                    <div class="kib-uname">{{ $u->fullname }}</div>
                                    <div class="kib-uid">@ {{ $u->username }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="kib-date">{{ $u->email }}</td>
                        <td><span class="kib-amt">{{ showAmount($u->key_in_bonus) }}</span></td>
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
        <div class="kib-empty">
            <i class="las la-wallet"></i>
            <p>{{ $search ? 'No users match your search.' : 'No user currently holds a key-in bonus balance.' }}</p>
        </div>
        @endif
    </div>
    @endif

    @if($tab === 'gained')
    <div class="kib-card">
        <div class="kib-card-head">
            <h6><i class="las la-arrow-circle-down me-1"></i> Key-In Bonus Credits</h6>
            <form action="" method="GET" class="kib-filters">
                <input type="hidden" name="tab" value="gained">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search user…" class="kib-input" style="width:190px;">
                <button type="submit" class="kib-btn kib-btn-primary"><i class="las la-search"></i> Search</button>
                @if($search)
                    <a href="{{ route('admin.key-in-bonus.index', ['tab' => 'gained']) }}" class="kib-btn kib-btn-muted"><i class="las la-times"></i> Clear</a>
                @endif
            </form>
        </div>

        @if($gained->count())
        <div class="table-responsive">
            <table class="kib-table">
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
                            <div class="kib-user">
                                <div class="kib-av">{{ strtoupper(substr($u->firstname ?? '?',0,1)) }}{{ strtoupper(substr($u->lastname ?? '',0,1)) }}</div>
                                <div>
                                    <div class="kib-uname">{{ $u->fullname ?? '—' }}</div>
                                    <div class="kib-uid">@ {{ $u->username ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="kib-amt">{{ showAmount($txn->amount) }}</span></td>
                        <td><span class="kib-detail" title="{{ $txn->details }}">{{ $txn->details }}</span></td>
                        <td class="kib-date">
                            {{ $txn->created_at->format('d M Y') }}<br>
                            <small>{{ $txn->created_at->format('H:i') }}</small>
                        </td>
                        <td><span class="kib-trx">{{ $txn->trx }}</span></td>
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
        <div class="kib-empty">
            <i class="las la-arrow-circle-down"></i>
            <p>{{ $search ? 'No credits match your search.' : 'No key-in bonus credits recorded yet.' }}</p>
        </div>
        @endif
    </div>
    @endif

</div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.key-in-bonus.index') }}" class="btn btn-sm btn-outline--info">
        <i class="las la-keyboard"></i> Key-In Bonus
    </a>
@endpush

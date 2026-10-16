@extends('admin.layouts.app')

@push('style')
<style>
/* ══════════════════════════════════════════════════
   WELCOME PACKAGE ADMIN
══════════════════════════════════════════════════ */
.wp-wrap { padding: 0; }

.wp-hero {
    background: linear-gradient(135deg, #064e3b 0%, #16a34a 100%);
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
.wp-hero::after {
    content: '';
    position: absolute;
    right: -30px; top: -30px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    pointer-events: none;
}
.wp-hero-icon {
    width: 52px; height: 52px;
    background: rgba(255,255,255,.15);
    border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0;
}
.wp-hero-body h2 { font-size: 1.25rem; font-weight: 800; margin: 0 0 4px; letter-spacing: -.02em; }
.wp-hero-body p  { font-size: .82rem; opacity: .78; margin: 0; }

.wp-stats { display: flex; gap: 14px; margin-bottom: 22px; flex-wrap: wrap; }
.wp-stat {
    flex: 1; min-width: 160px;
    background: #fff;
    border: 1px solid #e8edf2;
    border-radius: 12px;
    padding: 15px 16px;
    display: flex; align-items: center; gap: 11px;
}
.wp-stat-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; flex-shrink: 0;
}
.wp-stat-val { font-size: 1.25rem; font-weight: 800; color: #0f172a; line-height: 1; }
.wp-stat-lbl { font-size: .7rem; color: #64748b; margin-top: 3px; font-weight: 500; }

.wp-tabs {
    display: flex; gap: 4px;
    background: #f1f5f9; border-radius: 12px; padding: 5px;
    margin-bottom: 20px; width: fit-content;
}
.wp-tab {
    padding: 8px 20px; border-radius: 8px;
    font-size: .82rem; font-weight: 700; color: #64748b;
    text-decoration: none; display: flex; align-items: center; gap: 7px;
    transition: all .18s; white-space: nowrap;
}
.wp-tab:hover { color: #16a34a; background: #fff; }
.wp-tab.active { background: #fff; color: #16a34a; box-shadow: 0 1px 6px rgba(22,163,74,.15); }
.wp-tab .wp-tab-count {
    font-size: .65rem; font-weight: 800; padding: 2px 7px; border-radius: 20px;
    background: #dcfce7; color: #15803d;
}
.wp-tab.active .wp-tab-count { background: #16a34a; color: #fff; }

.wp-card { background: #fff; border: 1px solid #e8edf2; border-radius: 14px; overflow: hidden; }
.wp-card-head {
    padding: 15px 20px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px; flex-wrap: wrap;
}
.wp-card-head h6 {
    font-size: .76rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em;
    color: #64748b; margin: 0;
}
.wp-filters { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.wp-input {
    height: 34px; border-radius: 8px; border: 1px solid #e2e8f0;
    font-size: .8rem; padding: 0 11px; background: #fff;
    transition: border-color .18s, box-shadow .18s;
}
.wp-input:focus { outline: none; border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.1); }
.wp-btn {
    height: 34px; padding: 0 14px; border-radius: 8px;
    font-size: .78rem; font-weight: 700; border: none; cursor: pointer; transition: background .18s;
    display: inline-flex; align-items: center; gap: 5px; text-decoration: none;
}
.wp-btn-primary { background: #16a34a; color: #fff; }
.wp-btn-primary:hover { background: #0D5C2E; color: #fff; }
.wp-btn-muted { background: #e2e8f0; color: #475569; }
.wp-btn-muted:hover { background: #cbd5e1; color: #1e293b; }

.wp-filter-chip {
    display: inline-flex; align-items: center; gap: 6px;
    background: #dcfce7; color: #15803d;
    font-size: .78rem; font-weight: 700;
    padding: 5px 12px; border-radius: 20px;
}
.wp-filter-chip a { color: #15803d; }

.wp-table { width: 100%; border-collapse: collapse; }
.wp-table th {
    padding: 10px 16px; text-align: left;
    font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em;
    color: #94a3b8; background: #f8fafc; border-bottom: 1px solid #f1f5f9;
}
.wp-table td { padding: 13px 16px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.wp-table tbody tr:last-child td { border-bottom: none; }
.wp-table tbody tr:hover td { background: #fafbfc; }

.wp-user { display: flex; align-items: center; gap: 9px; }
.wp-av {
    width: 34px; height: 34px; border-radius: 9px;
    background: linear-gradient(135deg, #16a34a, #0D5C2E);
    color: #fff; font-size: .76rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.wp-uname { font-weight: 700; font-size: .85rem; color: #0f172a; line-height: 1.2; }
.wp-uid   { font-size: .7rem; color: #94a3b8; }

.wp-amt {
    font-size: .9rem; font-weight: 800; padding: 3px 10px; border-radius: 6px; display: inline-block;
    color: #16a34a; background: #f0fdf4; border: 1px solid #bbf7d0;
}
.wp-chip { font-size: .7rem; font-weight: 700; padding: 2px 9px; border-radius: 20px; display: inline-block; text-transform: capitalize; }
.wp-chip--registration { background: #eff6ff; color: #1d4ed8; }
.wp-chip--upgrade      { background: #fef3c7; color: #92400e; }
.wp-chip--pending      { background: #f3f4f6; color: #6b7280; }
.wp-chip--redeemed     { background: #dcfce7; color: #15803d; }

.wp-code { font-size: .75rem; font-family: monospace; letter-spacing: .05em; color: #475569; background: #f1f5f9; padding: 2px 8px; border-radius: 6px; }
.wp-date { font-size: .76rem; color: #475569; }
.wp-date small { color: #94a3b8; }

.wp-empty { padding: 56px 24px; text-align: center; color: #94a3b8; }
.wp-empty i { font-size: 2.6rem; display: block; margin-bottom: 12px; opacity: .32; }
.wp-empty p { font-size: .86rem; margin: 0; }

@media (max-width: 767px) {
    .wp-hero { flex-direction: column; text-align: center; }
    .wp-tabs { flex-wrap: wrap; width: 100%; }
    .wp-tab  { flex: 1; justify-content: center; }
    .wp-filters { flex-direction: column; width: 100%; }
    .wp-input, .wp-btn { width: 100%; justify-content: center; }
}
</style>
@endpush

@section('panel')
<div class="wp-wrap">

    <div class="wp-hero">
        <div class="wp-hero-icon"><i class="las la-gift"></i></div>
        <div class="wp-hero-body">
            <h2 class="text-white">Welcome Packages</h2>
            <p class="text-white">Registration &amp; upgrade cash-back held as redeemable codes until a stockist redeems them in person.</p>
        </div>
    </div>

    <div class="wp-stats">
        <div class="wp-stat">
            <div class="wp-stat-icon" style="background:#fef9c3;color:#854d0e;"><i class="las la-hourglass-half"></i></div>
            <div>
                <div class="wp-stat-val">{{ showAmount($totalPending) }}</div>
                <div class="wp-stat-lbl">Pending ({{ $pendingCount }} packages)</div>
            </div>
        </div>
        <div class="wp-stat">
            <div class="wp-stat-icon" style="background:#f0fdf4;color:#16a34a;"><i class="las la-check-circle"></i></div>
            <div>
                <div class="wp-stat-val">{{ showAmount($totalRedeemed) }}</div>
                <div class="wp-stat-lbl">Redeemed ({{ $redeemedCount }} packages)</div>
            </div>
        </div>
    </div>

    @if($userFilter)
    <div class="mb-3">
        <span class="wp-filter-chip">
            <i class="las la-filter"></i> Showing packages for @ {{ $userFilter }}
            <a href="{{ route('admin.welcome-pack.index', ['tab' => $tab]) }}"><i class="las la-times"></i></a>
        </span>
    </div>
    @endif

    <div class="wp-tabs">
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'pending', 'search' => null]) }}"
           class="wp-tab {{ $tab === 'pending' ? 'active' : '' }}">
            <i class="las la-hourglass-half"></i> Pending
            @if($pendingCount > 0)<span class="wp-tab-count">{{ $pendingCount }}</span>@endif
        </a>
        <a href="{{ request()->fullUrlWithQuery(['tab' => 'redeemed', 'search' => null]) }}"
           class="wp-tab {{ $tab === 'redeemed' ? 'active' : '' }}">
            <i class="las la-check-circle"></i> Redeemed
            @if($redeemedCount > 0)<span class="wp-tab-count">{{ $redeemedCount }}</span>@endif
        </a>
    </div>

    <div class="wp-card">
        <div class="wp-card-head">
            <h6><i class="las la-list me-1"></i> {{ $tab === 'pending' ? 'Awaiting Redemption' : 'Redemption History' }}</h6>
            <form action="" method="GET" class="wp-filters">
                <input type="hidden" name="tab" value="{{ $tab }}">
                @if($userFilter)<input type="hidden" name="user" value="{{ $userFilter }}">@endif
                <input type="text" name="search" value="{{ $search }}" placeholder="Search user…" class="wp-input" style="width:190px;">
                <button type="submit" class="wp-btn wp-btn-primary"><i class="las la-search"></i> Search</button>
                @if($search)
                    <a href="{{ route('admin.welcome-pack.index', ['tab' => $tab, 'user' => $userFilter]) }}" class="wp-btn wp-btn-muted"><i class="las la-times"></i> Clear</a>
                @endif
            </form>
        </div>

        @if($packages->count())
        <div class="table-responsive">
            <table class="wp-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Code</th>
                        <th>Source</th>
                        <th>Amount</th>
                        @if($tab === 'redeemed')<th>Redeemed By</th>@endif
                        <th>{{ $tab === 'redeemed' ? 'Redeemed On' : 'Created' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($packages as $i => $package)
                    @php $u = $package->user; @endphp
                    <tr>
                        <td style="color:#94a3b8;font-size:.76rem;">{{ $packages->firstItem() + $i }}</td>
                        <td>
                            <div class="wp-user">
                                <div class="wp-av">{{ strtoupper(substr($u->firstname ?? '?', 0, 1)) }}{{ strtoupper(substr($u->lastname ?? '', 0, 1)) }}</div>
                                <div>
                                    <div class="wp-uname">{{ $u->fullname ?? '—' }}</div>
                                    <div class="wp-uid">@ {{ $u->username ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="wp-code">{{ $package->code }}</span></td>
                        <td><span class="wp-chip wp-chip--{{ $package->source }}">{{ $package->source }}</span></td>
                        <td><span class="wp-amt">{{ showAmount($package->amount) }}</span></td>
                        @if($tab === 'redeemed')
                        <td>
                            @if($package->redeemedBy)
                                <div class="wp-uname" style="font-size:.8rem;">{{ $package->redeemedBy->business_name ?? ($package->redeemedBy->user->fullname ?? '—') }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        @endif
                        <td class="wp-date">
                            @php $ts = $tab === 'redeemed' ? $package->redeemed_at : $package->created_at; @endphp
                            {{ $ts?->format('d M Y') }}<br>
                            <small>{{ $ts?->format('H:i') }}</small>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($packages->hasPages())
        <div style="padding:12px 18px;border-top:1px solid #f1f5f9;">
            {{ paginateLinks($packages) }}
        </div>
        @endif
        @else
        <div class="wp-empty">
            <i class="las la-gift"></i>
            <p>{{ $search ? 'No packages match your search.' : ($tab === 'pending' ? 'No welcome packages are currently pending.' : 'No welcome packages have been redeemed yet.') }}</p>
        </div>
        @endif
    </div>

</div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.welcome-pack.index') }}" class="btn btn-sm btn-outline--info">
        <i class="las la-gift"></i> Welcome Packages
    </a>
@endpush

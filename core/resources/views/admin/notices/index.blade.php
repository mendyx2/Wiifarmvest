@extends('admin.layouts.app')

@push('style')
<style>
/* ══════════════════════════════════════════════════
   DIRECT NOTICES ADMIN
══════════════════════════════════════════════════ */
.nt-wrap { padding: 0; }

.nt-hero {
    background: linear-gradient(135deg, #312e81 0%, #4f46e5 100%);
    border-radius: 16px;
    padding: 26px 30px;
    margin-bottom: 26px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 18px;
    flex-wrap: wrap;
    position: relative;
    overflow: hidden;
    color: #fff;
}
.nt-hero::after {
    content: '';
    position: absolute;
    right: -30px; top: -30px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
    pointer-events: none;
}
.nt-hero-left { display: flex; align-items: center; gap: 18px; }
.nt-hero-icon {
    width: 52px; height: 52px;
    background: rgba(255,255,255,.15);
    border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0;
}
.nt-hero-body h2 { font-size: 1.25rem; font-weight: 800; margin: 0 0 4px; letter-spacing: -.02em; }
.nt-hero-body p  { font-size: .82rem; opacity: .72; margin: 0; }
.nt-hero-btn {
    background: rgba(255,255,255,.15);
    color: #fff;
    border: 1px solid rgba(255,255,255,.3);
    padding: 10px 18px;
    border-radius: 10px;
    font-size: .84rem; font-weight: 700;
    display: inline-flex; align-items: center; gap: 7px;
    text-decoration: none;
    transition: background .18s;
}
.nt-hero-btn:hover { background: rgba(255,255,255,.25); color: #fff; }

.nt-stats { display: flex; gap: 14px; margin-bottom: 22px; flex-wrap: wrap; }
.nt-stat {
    flex: 1; min-width: 140px;
    background: #fff;
    border: 1px solid #e8edf2;
    border-radius: 12px;
    padding: 15px 16px;
    display: flex; align-items: center; gap: 11px;
}
.nt-stat-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.05rem; flex-shrink: 0;
}
.nt-stat-val { font-size: 1.25rem; font-weight: 800; color: #0f172a; line-height: 1; }
.nt-stat-lbl { font-size: .7rem; color: #64748b; margin-top: 3px; font-weight: 500; }

.nt-card { background: #fff; border: 1px solid #e8edf2; border-radius: 14px; overflow: hidden; }
.nt-card-head {
    padding: 15px 20px;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px; flex-wrap: wrap;
}
.nt-card-head h6 {
    font-size: .76rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em;
    color: #64748b; margin: 0;
}
.nt-filters { display: flex; gap: 7px; flex-wrap: wrap; align-items: center; }
.nt-input {
    height: 34px; border-radius: 8px; border: 1px solid #e2e8f0;
    font-size: .8rem; padding: 0 11px; background: #fff;
    transition: border-color .18s, box-shadow .18s;
}
.nt-input:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
.nt-btn {
    height: 34px; padding: 0 14px; border-radius: 8px;
    font-size: .78rem; font-weight: 700;
    border: none; cursor: pointer; transition: background .18s;
    display: inline-flex; align-items: center; gap: 5px;
    text-decoration: none;
}
.nt-btn-primary { background: #4f46e5; color: #fff; }
.nt-btn-primary:hover { background: #4338ca; color: #fff; }
.nt-btn-muted { background: #e2e8f0; color: #475569; }
.nt-btn-muted:hover { background: #cbd5e1; color: #1e293b; }

.nt-table { width: 100%; border-collapse: collapse; }
.nt-table th {
    padding: 10px 16px; text-align: left;
    font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .08em;
    color: #94a3b8; background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}
.nt-table td { padding: 13px 16px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.nt-table tbody tr:last-child td { border-bottom: none; }
.nt-table tbody tr:hover td { background: #fafbfc; }

.nt-user { display: flex; align-items: center; gap: 9px; }
.nt-av {
    width: 34px; height: 34px; border-radius: 9px;
    background: linear-gradient(135deg, #4f46e5, #4338ca);
    color: #fff; font-size: .76rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.nt-uname { font-weight: 700; font-size: .85rem; color: #0f172a; line-height: 1.2; }
.nt-uid   { font-size: .7rem; color: #94a3b8; }

.nt-chip {
    font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
    padding: 2px 9px; border-radius: 20px;
    display: inline-block;
}
.nt-chip--email { background: #eef2ff; color: #4f46e5; }
.nt-chip--sms   { background: #f0fdf4; color: #16a34a; }
.nt-chip--push  { background: #fff7ed; color: #ea580c; }

.nt-subject { font-weight: 700; font-size: .84rem; color: #0f172a; margin-bottom: 2px; }
.nt-message { font-size: .76rem; color: #64748b; max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.nt-date { font-size: .76rem; color: #475569; }
.nt-date small { color: #94a3b8; }
.nt-by { font-size: .76rem; color: #6366f1; font-weight: 600; }

.nt-empty { padding: 56px 24px; text-align: center; color: #94a3b8; }
.nt-empty i { font-size: 2.6rem; display: block; margin-bottom: 12px; opacity: .32; }
.nt-empty p { font-size: .86rem; margin: 0 0 14px; }

@media (max-width: 767px) {
    .nt-hero { flex-direction: column; text-align: center; }
    .nt-hero-left { flex-direction: column; }
    .nt-filters { flex-direction: column; width: 100%; }
    .nt-input, .nt-btn { width: 100%; justify-content: center; }
}
</style>
@endpush

@section('panel')
<div class="nt-wrap">

    <div class="nt-hero">
        <div class="nt-hero-left">
            <div class="nt-hero-icon"><i class="las la-comment-dots"></i></div>
            <div class="nt-hero-body">
                <h2 class="text-white">Direct Notices</h2>
                <p class="text-white">Send a personal notice to a single user — separate from bulk broadcasts, delivered via their preferred channel and flagged in their notification feed.</p>
            </div>
        </div>
        <a href="{{ route('admin.notices.create') }}" class="nt-hero-btn"><i class="las la-plus"></i> New Notice</a>
    </div>

    <div class="nt-stats">
        <div class="nt-stat">
            <div class="nt-stat-icon" style="background:#eef2ff;color:#4f46e5;"><i class="las la-paper-plane"></i></div>
            <div>
                <div class="nt-stat-val">{{ $totalSent }}</div>
                <div class="nt-stat-lbl">Total Notices Sent</div>
            </div>
        </div>
        <div class="nt-stat">
            <div class="nt-stat-icon" style="background:#f0fdf4;color:#059669;"><i class="las la-calendar-day"></i></div>
            <div>
                <div class="nt-stat-val">{{ $sentToday }}</div>
                <div class="nt-stat-lbl">Sent Today</div>
            </div>
        </div>
        <div class="nt-stat">
            <div class="nt-stat-icon" style="background:#fff7ed;color:#ea580c;"><i class="las la-users"></i></div>
            <div>
                <div class="nt-stat-val">{{ $usersReached }}</div>
                <div class="nt-stat-lbl">Users Reached</div>
            </div>
        </div>
    </div>

    <div class="nt-card">
        <div class="nt-card-head">
            <h6><i class="las la-list me-1"></i> Notice History</h6>
            <form action="" method="GET" class="nt-filters">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search user…" class="nt-input" style="width:190px;">
                <button type="submit" class="nt-btn nt-btn-primary"><i class="las la-search"></i> Search</button>
                @if($search)
                    <a href="{{ route('admin.notices.index') }}" class="nt-btn nt-btn-muted"><i class="las la-times"></i> Clear</a>
                @endif
            </form>
        </div>

        @if($notices->count())
        <div class="table-responsive">
            <table class="nt-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Recipient</th>
                        <th>Notice</th>
                        <th>Via</th>
                        <th>Sent By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($notices as $i => $n)
                    @php $u = $n->user; @endphp
                    <tr>
                        <td style="color:#94a3b8;font-size:.76rem;">{{ $notices->firstItem() + $i }}</td>
                        <td>
                            <div class="nt-user">
                                <div class="nt-av">{{ strtoupper(substr($u->firstname ?? '?',0,1)) }}{{ strtoupper(substr($u->lastname ?? '',0,1)) }}</div>
                                <div>
                                    <div class="nt-uname">{{ $u->fullname ?? '—' }}</div>
                                    <div class="nt-uid">@ {{ $u->username ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="nt-subject">{{ $n->subject ?: '—' }}</div>
                            <div class="nt-message" title="{{ $n->message }}">{{ strip_tags($n->message) }}</div>
                        </td>
                        <td><span class="nt-chip nt-chip--{{ $n->notification_type }}">{{ $n->notification_type }}</span></td>
                        <td class="nt-by">{{ $n->notifyingAdmin->name ?? $n->notifyingAdmin->username ?? '—' }}</td>
                        <td class="nt-date">
                            {{ $n->created_at->format('d M Y') }}<br>
                            <small>{{ $n->created_at->format('H:i') }}</small>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($notices->hasPages())
        <div style="padding:12px 18px;border-top:1px solid #f1f5f9;">
            {{ paginateLinks($notices) }}
        </div>
        @endif
        @else
        <div class="nt-empty">
            <i class="las la-comment-slash"></i>
            <p>{{ $search ? 'No notices match your search.' : 'No direct notices have been sent yet.' }}</p>
            <a href="{{ route('admin.notices.create') }}" class="nt-btn nt-btn-primary" style="display:inline-flex;"><i class="las la-plus"></i> Send the first one</a>
        </div>
        @endif
    </div>

</div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.notices.index') }}" class="btn btn-sm btn-outline--info">
        <i class="las la-comment-dots"></i> Direct Notices
    </a>
@endpush

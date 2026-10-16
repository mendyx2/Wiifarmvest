@extends('admin.layouts.app')

@push('style')
<style>
/* ── ACB Member Management ─────────────────────────────────── */
.act-page { padding: 0; }

/* Hero strip */
.act-hero {
    background: linear-gradient(135deg, #1e3a5f 0%, #1d4ed8 100%);
    border-radius: 16px;
    padding: 28px 32px;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    gap: 20px;
    overflow: hidden;
    position: relative;
    color: #fff;
}
.act-hero::after {
    content: '\f091';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    position: absolute;
    right: 32px;
    font-size: 6rem;
    opacity: .06;
    pointer-events: none;
    line-height: 1;
}
.act-hero-icon {
    width: 56px; height: 56px;
    background: rgba(255,255,255,.15);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}
.act-hero-body h2 { font-size: 1.35rem; font-weight: 800; margin: 0 0 4px; letter-spacing: -.02em; }
.act-hero-body p  { font-size: .85rem; opacity: .8; margin: 0; }

/* Add Member card */
.act-add-card {
    background: #fff;
    border: 1px solid #e8edf2;
    border-radius: 14px;
    padding: 22px 24px 20px;
    margin-bottom: 24px;
}
.act-add-card h6 {
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: #64748b;
    margin: 0 0 16px;
}
.act-add-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: flex-end;
}
.act-add-row .form-group { margin: 0; flex: 1; min-width: 160px; }
.act-add-row .form-group label {
    font-size: .75rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 6px;
    display: block;
}
.act-add-row .form-control {
    height: 42px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    font-size: .88rem;
    padding: 0 12px;
}
.act-add-row .form-control:focus {
    border-color: #1d4ed8;
    box-shadow: 0 0 0 3px rgba(29,78,216,.1);
}
.act-add-row textarea.form-control { height: 42px; padding: 10px 12px; resize: none; }
.btn-act-add {
    height: 42px;
    padding: 0 22px;
    border-radius: 8px;
    background: #1d4ed8;
    color: #fff;
    font-size: .85rem;
    font-weight: 700;
    border: none;
    cursor: pointer;
    white-space: nowrap;
    transition: background .2s, transform .15s;
    display: flex; align-items: center; gap: 8px;
    flex-shrink: 0;
}
.btn-act-add:hover { background: #1e40af; transform: translateY(-1px); color: #fff; }

/* Stats row */
.act-stats {
    display: flex;
    gap: 16px;
    margin-bottom: 24px;
    flex-wrap: wrap;
}
.act-stat {
    flex: 1;
    min-width: 140px;
    background: #fff;
    border: 1px solid #e8edf2;
    border-radius: 12px;
    padding: 16px 18px;
    display: flex; align-items: center; gap: 12px;
}
.act-stat-icon {
    width: 42px; height: 42px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.act-stat-body .val { font-size: 1.4rem; font-weight: 800; color: #0f172a; line-height: 1; }
.act-stat-body .lbl { font-size: .72rem; color: #64748b; margin-top: 3px; font-weight: 500; }

/* Table */
.act-table-card {
    background: #fff;
    border: 1px solid #e8edf2;
    border-radius: 14px;
    overflow: hidden;
}
.act-table-head {
    padding: 18px 22px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}
.act-table-head h6 {
    font-size: .78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: #64748b;
    margin: 0;
}
.act-table-search { position: relative; }
.act-table-search input {
    height: 36px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    font-size: .82rem;
    padding: 0 12px 0 34px;
    width: 220px;
    transition: border-color .2s, box-shadow .2s;
}
.act-table-search input:focus {
    outline: none;
    border-color: #1d4ed8;
    box-shadow: 0 0 0 3px rgba(29,78,216,.1);
}
.act-table-search i {
    position: absolute;
    left: 10px; top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: .82rem;
    pointer-events: none;
}
.act-table { width: 100%; border-collapse: collapse; }
.act-table th {
    padding: 11px 18px;
    text-align: left;
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #94a3b8;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}
.act-table td { padding: 14px 18px; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.act-table tbody tr:last-child td { border-bottom: none; }
.act-table tbody tr:hover td { background: #fafbfc; }

/* User cell */
.act-user-cell { display: flex; align-items: center; gap: 10px; }
.act-avatar {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: linear-gradient(135deg, #1d4ed8, #3b82f6);
    color: #fff;
    font-size: .82rem;
    font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.act-user-name { font-weight: 700; font-size: .88rem; color: #0f172a; line-height: 1.2; }
.act-user-id   { font-size: .72rem; color: #94a3b8; }

/* ACB balance badge */
.act-bal {
    font-size: .9rem;
    font-weight: 700;
    color: #15803d;
    background: #f0fdf4;
    padding: 4px 10px;
    border-radius: 6px;
    border: 1px solid #bbf7d0;
    display: inline-block;
}

/* Remove button */
.btn-act-remove {
    height: 32px;
    padding: 0 14px;
    border-radius: 7px;
    background: #fff0f0;
    color: #dc2626;
    border: 1px solid #fecaca;
    font-size: .75rem;
    font-weight: 700;
    cursor: pointer;
    transition: all .2s;
    display: inline-flex; align-items: center; gap: 5px;
}
.btn-act-remove:hover { background: #dc2626; color: #fff; border-color: #dc2626; }

/* Empty state */
.act-empty {
    padding: 64px 24px;
    text-align: center;
    color: #94a3b8;
}
.act-empty i { font-size: 3rem; margin-bottom: 12px; display: block; opacity: .4; }
.act-empty p { font-size: .9rem; }

/* Note truncate */
.act-note { font-size: .78rem; color: #64748b; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.act-note:empty::before { content: '—'; color: #cbd5e1; }

@media (max-width: 767px) {
    .act-hero { flex-direction: column; text-align: center; }
    .act-hero::after { display: none; }
    .act-add-row { flex-direction: column; }
    .act-add-row .form-group { min-width: 100%; }
}
</style>
@endpush

@section('panel')
<div class="act-page">

    {{-- Hero --}}
    <div class="act-hero mb-4">
        <div class="act-hero-icon"><i class="las la-star"></i></div>
        <div class="act-hero-body">
            <h2 class="text-white">ACB Member Management</h2>
            <p class="text-white">Users on this list receive Achievers Celebrated Bonus when their downlines qualify for awards — Gen 1: 5% · Gen 2: 2% · Gen 3: 1%</p>
        </div>
    </div>

    {{-- Stats --}}
    @php $totalMembers = \App\Models\AcbUser::count(); @endphp
    <div class="act-stats">
        <div class="act-stat">
            <div class="act-stat-icon" style="background:#eff6ff;color:#1d4ed8;"><i class="las la-users"></i></div>
            <div class="act-stat-body">
                <div class="val">{{ $totalMembers }}</div>
                <div class="lbl">Total ACB Members</div>
            </div>
        </div>
        <div class="act-stat">
            <div class="act-stat-icon" style="background:#f0fdf4;color:#15803d;"><i class="las la-star"></i></div>
            <div class="act-stat-body">
                <div class="val">5% · 2% · 1%</div>
                <div class="lbl">Bonus Structure</div>
            </div>
        </div>
        <div class="act-stat">
            <div class="act-stat-icon" style="background:#fef9c3;color:#854d0e;"><i class="las la-trophy"></i></div>
            <div class="act-stat-body">
                <div class="val">3 Gen</div>
                <div class="lbl">Upline Depth</div>
            </div>
        </div>
    </div>

    {{-- Add Member --}}
    <div class="act-add-card">
        <h6><i class="las la-user-plus me-1"></i> Add a Member to ACB</h6>
        <form action="{{ route('admin.acb.store') }}" method="POST">
            @csrf
            <div class="act-add-row">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" placeholder="e.g. johndoe" value="{{ old('username') }}" required>
                </div>
                <div class="form-group" style="flex:2;">
                    <label>Note <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                    <textarea name="notes" class="form-control" placeholder="Why this member qualifies...">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="btn-act-add">
                    <i class="las la-plus-circle"></i> Add to ACB
                </button>
            </div>
            @error('username')
                <p style="color:#dc2626;font-size:.78rem;margin:8px 0 0;">{{ $message }}</p>
            @enderror
        </form>
    </div>

    {{-- Member Table --}}
    <div class="act-table-card">
        <div class="act-table-head">
            <h6><i class="las la-list me-1"></i> Current ACB Members</h6>
            <form action="" method="GET" class="act-table-search">
                <i class="las la-search"></i>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search members…">
            </form>
        </div>

        @if($acbMembers->count())
        <div class="table-responsive">
            <table class="act-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Member</th>
                        <th>Email</th>
                        <th>ACB Balance</th>
                        <th>Note</th>
                        <th>Added</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($acbMembers as $i => $act)
                    @php $u = $act->user; @endphp
                    
                    <tr>
                        <td style="color:#94a3b8;font-size:.8rem;">{{ $acbMembers->firstItem() + $i }}</td>
                        <td>
                            <div class="act-user-cell">
                                <div class="act-avatar">{{ strtoupper(substr($u->firstname,0,1)) }}{{ strtoupper(substr($u->lastname,0,1)) }}</div>
                                <div>
                                    <div class="act-user-name">{{ $u->fullname }}</div>
                                    <div class="act-user-id">@ {{ $u->username }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="font-size:.82rem;color:#475569;">{{ $u->email }}</td>
                        <td><span class="act-bal">{{ showAmount($u->acb ?? 0) }}</span></td>
                        <td><span class="act-note">{{ $act->notes }}</span></td>
                        <td style="font-size:.78rem;color:#64748b;">
                            {{ $act->created_at->format('d M Y') }}<br>
                            <span style="color:#94a3b8;">{{ $act->created_at->diffForHumans() }}</span>
                        </td>
                        <td>
                            <form action="{{ route('admin.acb.destroy', $act->id) }}" method="POST" onsubmit="return confirm('Remove @ {{ $u->username }} from ACB?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-act-remove">
                                    <i class="las la-times"></i> Remove
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($acbMembers->hasPages())
        <div style="padding:16px 20px;border-top:1px solid #f1f5f9;">
            {{ paginateLinks($acbMembers) }}
        </div>
        @endif
        @else
        <div class="act-empty">
            <i class="las la-user-slash"></i>
            <p>{{ $search ? 'No members match your search.' : 'No ACB members yet. Add the first one above.' }}</p>
        </div>
        @endif
    </div>

</div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{ route('admin.acb.index') }}" class="btn btn-sm btn-outline--primary"><i class="las la-star"></i> ACB Members</a>
@endpush

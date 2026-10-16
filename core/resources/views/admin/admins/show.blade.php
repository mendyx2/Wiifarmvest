@extends('admin.layouts.app')

@section('panel')
<div class="container-fluid">

    {{-- Back bar --}}
    <div class="d-flex align-items-center mb-3 flex-wrap gap-2">
        <a href="{{ route('admin.admins.index') }}" class="btn btn-sm btn--primary">
            <i class="fas fa-arrow-left mr-1"></i> Back to Admins
        </a>
        <br>
    </div>
    <br>    
    <div class="row">

        {{-- ── LEFT COLUMN: Profile card + quick actions ── --}}
        <div class="col-12 col-lg-4 mb-4">

            {{-- Profile card --}}
            <div class="card au-profile-card text-center mb-3">
                <div class="au-avatar-wrap">
                    <div class="au-avatar">
                        {{ strtoupper(substr($admin->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $admin->name)[1] ?? $admin->username, 0, 1)) }}
                    </div>
                </div>
                <div class="card-body pt-2">
                    <h4 class="au-name mb-1">{{ $admin->name }}</h4>
                    <p class="au-username text-muted mb-2">@ {{ $admin->username }}</p>

                    <div class="mb-3">
                        @foreach($admin->roles as $role)
                            <span class="au-role-badge">{{ $role->name }}</span>
                        @endforeach
                    </div>

                    <span class="badge {{ $admin->is_active ? 'badge--success' : 'badge--danger' }} px-3 py-1">
                        <i class="fas fa-circle mr-1" style="font-size:.55rem;vertical-align:middle"></i>
                        {{ $admin->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="card">
                <div class="card-header py-2">
                    <h6 class="card-title mb-0"><i class="fas fa-bolt text-warning mr-1"></i> Actions</h6>
                </div>
                <div class="card-body p-2">
                    <a href="{{ route('admin.admins.edit', $admin) }}"
                       class="btn btn--warning btn--shadow w-100 mb-2">
                        <i class="fas fa-edit mr-1"></i> Edit Admin
                    </a>

                    <form action="{{ route('admin.admins.toggle-status', $admin) }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="btn w-100 mb-2 {{ $admin->is_active ? 'btn--danger' : 'btn--success' }}"
                            onclick="return confirm('{{ $admin->is_active ? 'Deactivate' : 'Activate' }} this admin?')">
                            <i class="fas fa-{{ $admin->is_active ? 'ban' : 'check-circle' }} mr-1"></i>
                            {{ $admin->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>

                    @if(!in_array($admin->name, ['super-admin']) && $admin->id > 1)
                    <form action="{{ route('admin.admins.destroy', $admin) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn--danger w-100"
                            onclick="return confirm('Permanently delete this admin?')">
                            <i class="fas fa-trash mr-1"></i> Delete Admin
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── RIGHT COLUMN: Details + Permissions ── --}}
        <div class="col-12 col-lg-8">

            

            {{-- Permissions accordion --}}
            @php
                $allPerms  = $admin->getAllPermissions()->groupBy('group');
                $permTotal = $admin->getAllPermissions()->count();
            @endphp

            <div class="card mb-4">
                <div class="card-header py-2 d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-key text-info mr-1"></i> Permissions
                    </h6>
                    <span class="badge badge--info">{{ $permTotal }} total</span>
                </div>

                @if($permTotal > 0)
                <div class="card-body p-0">
                    <div class="accordion au-perm-accordion" id="permAccordion">
                        @foreach($allPerms as $group => $perms)
                        @php $gid = 'pg' . $loop->index; @endphp
                        <div class="au-perm-group">
                            <div class="au-perm-group-header" id="head-{{ $gid }}">
                                <button class="au-perm-toggle {{ $loop->first ? '' : 'collapsed' }}"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#{{ $gid }}"
                                    aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                                    aria-controls="{{ $gid }}">
                                    <span class="au-perm-group-name">
                                        <i class="fas fa-layer-group mr-2 text-muted"></i>{{ $group ?: 'General' }}
                                    </span>
                                    <span class="au-perm-group-count">{{ $perms->count() }}</span>
                                </button>
                            </div>
                            <div id="{{ $gid }}"
                                 class="collapse {{ $loop->first ? 'show' : '' }}"
                                 aria-labelledby="head-{{ $gid }}"
                                 data-bs-parent="#permAccordion">
                                <div class="au-perm-group-body">
                                    @foreach($perms as $perm)
                                        <span class="au-perm-badge">{{ $perm->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="card-body text-center text-muted py-4">
                    <i class="fas fa-lock fa-2x mb-2 d-block"></i>
                    No permissions assigned
                </div>
                @endif
            </div>
            {{-- Account details --}}
            <div class="card ">
                <div class="card-header py-2">
                    <h6 class="card-title mb-0"><i class="fas fa-id-card text-primary mr-1"></i> Account Details</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush au-detail-list">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="au-label"><i class="fas fa-user mr-2 text-muted"></i> Full Name</span>
                            <span class="au-value">{{ $admin->name }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="au-label"><i class="fas fa-at mr-2 text-muted"></i> Username</span>
                            <span class="au-value text-monospace">{{ $admin->username }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="au-label"><i class="fas fa-envelope mr-2 text-muted"></i> Email</span>
                            <span class="au-value">{{ $admin->email ?? '—' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="au-label"><i class="fas fa-shield-alt mr-2 text-muted"></i> Status</span>
                            <span class="badge {{ $admin->is_active ? 'badge--success' : 'badge--danger' }}">
                                {{ $admin->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="au-label"><i class="fas fa-calendar-plus mr-2 text-muted"></i> Created</span>
                            <span class="au-value">{{ $admin->created_at->format('M d, Y · H:i') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="au-label"><i class="fas fa-clock mr-2 text-muted"></i> Last Updated</span>
                            <span class="au-value">{{ $admin->updated_at->format('M d, Y · H:i') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
/* ── Profile card ── */
.au-profile-card {
    border-radius: 14px;
    overflow: visible;
    padding-top: 56px;
    position: relative;
}
.au-avatar-wrap {
    position: absolute;
    top: -44px;
    left: 50%;
    transform: translateX(-50%);
}
.au-avatar {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    font-size: 1.7rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 4px solid #fff;
    box-shadow: 0 4px 18px rgba(102,126,234,.35);
    letter-spacing: .04em;
}
.au-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1e293b;
}
.au-username {
    font-size: .875rem;
}
.au-role-badge {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    border-radius: 20px;
    padding: 3px 14px;
    font-size: .75rem;
    font-weight: 600;
    letter-spacing: .03em;
    margin: 2px;
}

/* ── Detail list ── */
.au-detail-list .list-group-item {
    padding: 12px 20px;
    flex-wrap: wrap;
    gap: 6px;
}
.au-label {
    font-size: .83rem;
    color: #64748b;
    font-weight: 500;
    min-width: 130px;
}
.au-value {
    font-size: .9rem;
    color: #1e293b;
    font-weight: 500;
    text-align: right;
    word-break: break-all;
}

/* ── Permissions accordion ── */
.au-perm-accordion {
    border-top: 1px solid #e2e8f0;
}
.au-perm-group {
    border-bottom: 1px solid #e2e8f0;
}
.au-perm-group:last-child {
    border-bottom: none;
}
.au-perm-group-header {
    background: #f8fafc;
}
.au-perm-toggle {
    width: 100%;
    background: transparent;
    border: none;
    padding: 11px 18px;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    text-align: left;
}
.au-perm-toggle:focus { outline: none; }
.au-perm-group-name {
    flex: 1;
    font-size: .82rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #475569;
}
.au-perm-group-count {
    background: #e2e8f0;
    color: #475569;
    border-radius: 20px;
    padding: 1px 9px;
    font-size: .72rem;
    font-weight: 600;
    flex-shrink: 0;
}
.au-perm-toggle::after {
    content: '\f078';
    font-family: 'Font Awesome 5 Free';
    font-weight: 900;
    font-size: .7rem;
    color: #94a3b8;
    transition: transform .2s;
    flex-shrink: 0;
}
.au-perm-toggle[aria-expanded="true"]::after {
    transform: rotate(180deg);
}
.au-perm-group-body {
    padding: 10px 18px 14px;
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    background: #fff;
}
.au-perm-badge {
    display: inline-block;
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    border-radius: 5px;
    padding: 3px 10px;
    font-size: .72rem;
    white-space: nowrap;
}

/* ── Mobile tweaks ── */
@media (max-width: 575px) {
    .au-detail-list .list-group-item {
        flex-direction: column;
        align-items: flex-start !important;
    }
    .au-value { text-align: left; }
}

/* Avatar wrapper needs room on large screens */
@media (min-width: 992px) {
    .au-profile-card { padding-top: 60px; }
}
</style>
@endpush

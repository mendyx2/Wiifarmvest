@extends('admin.layouts.app')
@section('panel')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3 class="card-title mb-0">Roles Management</h3>
                    <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add Role
                    </a>
                </div>
                <div class="card-body">
                    @include('admin.partials.alerts')

                    <div class="table-responsive">
                        <table class="table--light style--two table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Permissions</th>
                                    <th>Users</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $role)
                                <tr>
                                    <td data-label="ID">{{ $role->id }}</td>
                                    <td data-label="Name">
                                        <span class="badge badge--primary">{{ $role->name }}</span>
                                    </td>
                                    <td data-label="Permissions">
                                        @php $count = $role->permissions->count(); @endphp
                                        @if($count > 0)
                                            <button type="button"
                                                class="btn btn-sm btn--info"
                                                data-bs-toggle="modal"
                                                data-bs-target="#permModal{{ $role->id }}">
                                                <i class="fas fa-key"></i>
                                                {{ $count }} permission{{ $count !== 1 ? 's' : '' }}
                                            </button>
                                        @else
                                            <span class="text-muted font-italic">None</span>
                                        @endif
                                    </td>
                                    <td data-label="Users">{{ $role->users_count }}</td>
                                    <td data-label="Actions">
                                        <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn--warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if(!in_array($role->name, ['super-admin', 'admin']))
                                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn--danger" title="Delete"
                                                onclick="return confirm('Are you sure you want to delete this role?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $roles->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Permission modals (one per role, rendered outside table) --}}
@foreach($roles as $role)
    @if($role->permissions->count() > 0)
    <div class="modal fade" id="permModal{{ $role->id }}" tabindex="-1" role="dialog"
         aria-labelledby="permModalLabel{{ $role->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="permModalLabel{{ $role->id }}">
                        <i class="fas fa-key text-info mr-1"></i>
                        Permissions — <span class="badge badge--primary">{{ $role->name }}</span>
                        <small class="text-muted ml-1">({{ $role->permissions->count() }} total)</small>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @php $grouped = $role->permissions->groupBy('group'); @endphp
                    <div class="row">
                        @foreach($grouped as $group => $perms)
                        <div class="col-12 col-sm-6 col-md-4 mb-4">
                            <div class="perm-group-card h-100">
                                <div class="perm-group-header">
                                    <i class="fas fa-layer-group mr-1"></i>
                                    {{ $group ?: 'General' }}
                                    <span class="perm-count">{{ $perms->count() }}</span>
                                </div>
                                <div class="perm-group-body">
                                    @foreach($perms as $perm)
                                        <span class="perm-badge">{{ $perm->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn--warning btn-sm">
                        <i class="fas fa-edit"></i> Edit Role
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach

@push('style')
<style>
    /* group card */
    .perm-group-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
    }
    .perm-group-header {
        background: #f1f5f9;
        padding: 8px 12px;
        font-weight: 600;
        font-size: .8rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #475569;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .perm-count {
        margin-left: auto;
        background: #cbd5e1;
        color: #334155;
        border-radius: 20px;
        padding: 1px 8px;
        font-size: .72rem;
    }
    .perm-group-body {
        padding: 10px 12px;
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
    }
    .perm-badge {
        display: inline-block;
        background: #e0f2fe;
        color: #0369a1;
        border: 1px solid #bae6fd;
        border-radius: 4px;
        padding: 2px 8px;
        font-size: .73rem;
        white-space: nowrap;
    }
    /* modal scrollable body cap */
    .modal-dialog-scrollable .modal-body {
        max-height: 65vh;
        overflow-y: auto;
    }
</style>
@endpush
@endsection

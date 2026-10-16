@extends('admin.layouts.app')
@section('panel')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Role Details: {{ $role->name }}</h3>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary float-right">
                        <i class="fas fa-arrow-left"></i> Back to Roles
                    </a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Role Information</h4>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th width="30%">ID</th>
                                            <td>{{ $role->id }}</td>
                                        </tr>
                                        <tr>
                                            <th>Name</th>
                                            <td>
                                                <span class="badge badge-primary">{{ $role->name }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Guard Name</th>
                                            <td>{{ $role->guard_name }}</td>
                                        </tr>
                                        <tr>
                                            <th>Created At</th>
                                            <td>{{ $role->created_at->format('M d, Y H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <th>Updated At</th>
                                            <td>{{ $role->updated_at->format('M d, Y H:i') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Assigned Permissions</h4>
                                </div>
                                <div class="card-body">
                                    @if($role->permissions->count() > 0)
                                        <div class="row">
                                            @foreach($role->permissions->groupBy('group') as $group => $permissions)
                                                <div class="col-12 mb-3">
                                                    <h6 class="text-primary">{{ ucfirst($group) }}</h6>
                                                    @foreach($permissions as $permission)
                                                        <span class="badge badge-info mb-1 mr-1">{{ $permission->name }}</span>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted">No permissions assigned to this role.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Users with this Role ({{ $role->users->count() }})</h4>
                                </div>
                                <div class="card-body">
                                    @if($role->users->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Name</th>
                                                        <th>Username</th>
                                                        <th>Email</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($role->users as $user)
                                                        <tr>
                                                            <td>{{ $user->id }}</td>
                                                            <td>{{ $user->name }}</td>
                                                            <td>{{ $user->username }}</td>
                                                            <td>{{ $user->email ?? 'N/A' }}</td>
                                                            <td>
                                                                <span class="badge badge-{{ $user->is_active ? 'success' : 'danger' }}">
                                                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <p class="text-muted">No users assigned to this role.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit Role
                        </a>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
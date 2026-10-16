@extends('admin.layouts.app')
@section('panel')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Permissions Management</h3>
                    <div>
                        <a href="{{ route('admin.permissions.create') }}" class="btn btn-primary mr-2">
                            <i class="fas fa-plus"></i> Add Permission
                        </a>
                        <a href="{{ route('admin.permissions.bulk.create') }}" class="btn btn-success">
                            <i class="fas fa-bulk"></i> Bulk Create
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @include('admin.partials.alerts')
                     <div class="table-responsive--lg table-responsive">
                        <table class="table--light style--two table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Group</th>
                                    <th>Roles</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permissions as $permission)
                                <tr>
                                    <td>{{ $loop->index + 1 }}</td>
                                    <td>{{ $permission->name }}</td>
                                    <td>
                                        <span class="badge badge--secondary text-black">{{ $permission->group }}</span>
                                    </td>
                                    <td>{{ $permission->roles_count }}</td>
                                    <td>
                                        <a href="{{ route('admin.permissions.show', $permission) }}" class="btn btn-sm btn--info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-sm btn--warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.permissions.destroy', $permission) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn--danger" onclick="return confirm('Are you sure?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ paginateLinks($permissions) }}
                        {{-- $permissions->links() --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
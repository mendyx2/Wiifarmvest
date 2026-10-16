@extends('admin.layouts.app')
@section('panel')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Create New Role</h3>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary float-right">
                        <i class="fas fa-arrow-left"></i> Back to Roles
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.roles.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Role Name *</label>
                                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name') }}" required placeholder="Enter role name">
                                    @error('name')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Permissions</label>
                            <div class="row">
                                @foreach($permissions as $group => $groupPermissions)
                                    <div class="col-md-4 mb-4">
                                        <div class="card">
                                            <div class="card-header bg-light">
                                                <h6 class="card-title mb-0">
                                                    <div class="custom-control custom-checkbox">
                                                        <input type="checkbox" class="custom-control-input group-checkbox" 
                                                               id="group-{{ $group }}" data-group="{{ $group }}">
                                                        <label class="custom-control-label" for="group-{{ $group }}">
                                                            {{ ucfirst($group) }}
                                                        </label>
                                                    </div>
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                @foreach($groupPermissions as $permission)
                                                    <div class="custom-control custom-checkbox mb-2">
                                                        <input type="checkbox" name="permissions[]" 
                                                               value="{{ $permission->id }}" 
                                                               class="custom-control-input permission-checkbox {{ $group }}-permission" 
                                                               id="permission-{{ $permission->id }}">
                                                        <label class="custom-control-label" for="permission-{{ $permission->id }}">
                                                            {{ $permission->name }}
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Role
                            </button>
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script> 
$(document).ready(function() {
    // Group checkbox functionality
    $('.group-checkbox').change(function() {
        const group = $(this).data('group');
        const isChecked = $(this).is(':checked');
        
        $(`.${group}-permission`).prop('checked', isChecked);
    });

    // Individual permission checkbox functionality
    $('.permission-checkbox').change(function() {
        const classList = $(this).attr('class').split(' ');
        const permissionClass = classList.find(cls => cls.endsWith('-permission'));
        
        if (permissionClass) {
            const group = permissionClass.split('-')[0];
            const allChecked = $(`.${group}-permission:checked`).length === $(`.${group}-permission`).length;
            const noneChecked = $(`.${group}-permission:checked`).length === 0;
            
            $(`#group-${group}`).prop('checked', allChecked);
            $(`#group-${group}`).prop('indeterminate', !allChecked && !noneChecked);
        }
    });
});
</script>
@endpush
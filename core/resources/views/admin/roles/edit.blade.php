@extends('admin.layouts.app')
@section('panel')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Edit Role: {{ $role->name }}</h3>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary float-right">
                        <i class="fas fa-arrow-left"></i> Back to Roles
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Role Name *</label>
                                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                                           value="{{ old('name', $role->name) }}" required>
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
                                                               id="permission-{{ $permission->id }}"
                                                               {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}>
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
                                <i class="fas fa-save"></i> Update Role
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
    // Initialize group checkboxes based on current selections
    @foreach($permissions as $group => $groupPermissions)
        const {{ str_replace('-', '_', $group) }}Checked = $(`.{{ $group }}-permission:checked`).length;
        const {{ str_replace('-', '_', $group) }}Total = $(`.{{ $group }}-permission`).length;
        
        if ({{ str_replace('-', '_', $group) }}Checked === {{ str_replace('-', '_', $group) }}Total) {
            $(`#group-{{ $group }}`).prop('checked', true);
        } else if ({{ str_replace('-', '_', $group) }}Checked > 0) {
            $(`#group-{{ $group }}`).prop('indeterminate', true);
        }
    @endforeach

    // Group checkbox functionality
    $('.group-checkbox').change(function() {
        const group = $(this).data('group');
        const isChecked = $(this).is(':checked');
        
        $(`.${group}-permission`).prop('checked', isChecked);
        $(this).prop('indeterminate', false);
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
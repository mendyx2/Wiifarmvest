@extends('admin.layouts.app')
@section('panel')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Bulk Create Permissions</h3>
                    <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary float-right">
                        <i class="fas fa-arrow-left"></i> Back to Permissions
                    </a>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.permissions.bulk.store') }}" method="POST">
                        @csrf
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Add multiple permissions at once. Each permission requires a name and group.
                        </div>

                        <div id="permissions-container">
                            <div class="permission-row row mb-3">
                                <div class="col-md-5">
                                    <input type="text" name="permissions[0][name]" class="form-control" 
                                           placeholder="Permission name (e.g., manage users)" required>
                                </div>
                                <div class="col-md-5">
                                    <select name="permissions[0][group]" class="form-control" required>
                                        <option value="">Select Group</option>
                                        @foreach($groups as $group)
                                            <option value="{{ $group }}">{{ ucfirst($group) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger remove-row" disabled>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="button" id="add-row" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add Another Permission
                            </button>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Permissions
                            </button>
                            <a href="{{ route('admin.permissions.index') }}" class="btn btn-secondary">Cancel</a>
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
    let rowCount = 1;

    // Add new row
    $('#add-row').click(function() {
        const newRow = `
            <div class="permission-row row mb-3">
                <div class="col-md-5">
                    <input type="text" name="permissions[${rowCount}][name]" class="form-control" 
                           placeholder="Permission name (e.g., manage users)" required>
                </div>
                <div class="col-md-5">
                    <select name="permissions[${rowCount}][group]" class="form-control" required>
                        <option value="">Select Group</option>
                        @foreach($groups as $group)
                            <option value="{{ $group }}">{{ ucfirst($group) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-row">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        
        $('#permissions-container').append(newRow);
        rowCount++;
        
        // Enable remove buttons if there's more than one row
        if ($('.permission-row').length > 1) {
            $('.remove-row').prop('disabled', false);
        }
    });

    // Remove row
    $(document).on('click', '.remove-row', function() {
        if ($('.permission-row').length > 1) {
            $(this).closest('.permission-row').remove();
            rowCount--;
        }
        
        // Disable remove button if only one row left
        if ($('.permission-row').length === 1) {
            $('.remove-row').prop('disabled', true);
        }
    });
});
</script>
@endpush
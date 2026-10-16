@extends('admin.layouts.app')
@section('panel')

<div class="row">
    <div class="col-12">
        <div class="card b-radius--10">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Savings Product Settings</h5>
                <div class="d-flex gap-2">
                    <button class="btn btn--primary btn-sm" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="las la-plus me-1"></i>Add Setting
                    </button>
                    <a href="{{ route('admin.savings.index') }}" class="btn btn-outline--primary btn-sm">
                        <i class="las la-list me-1"></i>All Savings
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table--light style--two mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Type</th>
                                <th>Duration</th>
                                <th>Interest Rate (% p.a.)</th>
                                <th>Min Amount</th>
                                <th>Max Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($settings as $setting)
                            <tr>
                                <td>
                                    <strong>{{ $setting->label }}</strong>
                                    @if($setting->description)
                                        <br><small class="text-muted">{{ $setting->description }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge--{{ $setting->type === 'target' ? 'info' : ($setting->type === 'fixed' ? 'warning' : 'success') }}">
                                        {{ ucfirst($setting->type) }}
                                    </span>
                                </td>
                                <td>{{ $setting->duration_months ? $setting->duration_months . ' months' : '—' }}</td>
                                <td>
                                    <span class="fw-bold text-success">{{ number_format($setting->interest_rate, 2) }}%</span>
                                </td>
                                <td>{{ showAmount($setting->min_amount) }}</td>
                                <td>{{ $setting->max_amount ? showAmount($setting->max_amount) : 'Unlimited' }}</td>
                                <td>
                                    @if($setting->is_active)
                                        <span class="badge badge--success">Active</span>
                                    @else
                                        <span class="badge badge--danger">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-outline--primary btn-sm editBtn"
                                        data-id="{{ $setting->id }}"
                                        data-label="{{ $setting->label }}"
                                        data-interest_rate="{{ $setting->interest_rate }}"
                                        data-min_amount="{{ $setting->min_amount }}"
                                        data-max_amount="{{ $setting->max_amount ?? '' }}"
                                        data-description="{{ $setting->description }}"
                                        data-is_active="{{ $setting->is_active ? 1 : 0 }}">
                                        <i class="las la-pen"></i> Edit
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Modal --}}
<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Savings Setting</h5>
                <button type="button" class="close" data-bs-dismiss="modal"><i class="las la-times"></i></button>
            </div>
            <form method="POST" action="{{ route('admin.savings.settings.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Type <span class="text-danger">*</span></label>
                            <select class="form-select" name="type" id="add_type" required>
                                <option value="">— Select Type —</option>
                                <option value="target">Target</option>
                                <option value="fixed">Fixed</option>
                                <option value="farm">Farm</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="add_duration_wrap" style="display:none">
                            <label class="form-label fw-bold">Duration (months) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="duration_months" id="add_duration_months"
                                   min="1" placeholder="e.g. 3, 6, 12">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Label <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="label" maxlength="100" required
                                   placeholder="e.g. Fixed Box — 24 Months">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Interest Rate (% p.a.) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="interest_rate"
                                       step="0.01" min="0" max="100" required>
                                <span class="input-group-text">% p.a.</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Minimum Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                <input type="number" class="form-control" name="min_amount"
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Maximum Amount <small class="text-muted">(optional)</small></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                <input type="number" class="form-control" name="max_amount"
                                       step="0.01" min="0" placeholder="Leave blank for unlimited">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea class="form-control" name="description" rows="2" maxlength="255"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="add_is_active" value="1" checked>
                                <label class="form-check-label fw-bold" for="add_is_active">Active (users can create this product type)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline--dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--primary">
                        <i class="las la-plus me-1"></i>Add Setting
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit — <span id="modalLabel"></span></h5>
                <button type="button" class="close" data-bs-dismiss="modal"><i class="las la-times"></i></button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Interest Rate (% p.a.) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="interest_rate" id="f_interest_rate"
                                       step="0.01" min="0" max="100" required>
                                <span class="input-group-text">% p.a.</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Minimum Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                <input type="number" class="form-control" name="min_amount" id="f_min_amount"
                                       step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Maximum Amount <small class="text-muted">(optional)</small></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                <input type="number" class="form-control" name="max_amount" id="f_max_amount"
                                       step="0.01" min="0" placeholder="Leave blank for unlimited">
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Description</label>
                            <textarea class="form-control" name="description" id="f_description" rows="2" maxlength="255"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="f_is_active" value="1">
                                <label class="form-check-label fw-bold" for="f_is_active">Active (users can create this product type)</label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <i class="las la-info-circle me-1"></i>
                        Changes apply to <strong>new</strong> savings products. Existing products retain their original interest rate.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline--dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--primary">
                        <i class="las la-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
$(function () {
    $('#add_type').on('change', function () {
        var isFixed = $(this).val() === 'fixed';
        $('#add_duration_wrap').toggle(isFixed);
        $('#add_duration_months').prop('required', isFixed);
    });

    $('.editBtn').on('click', function () {
        var $btn = $(this);
        $('#modalLabel').text($btn.data('label'));
        $('#editForm').attr('action', '{{ route("admin.savings.settings.update", ":id") }}'.replace(':id', $btn.data('id')));
        $('#f_interest_rate').val($btn.data('interest_rate'));
        $('#f_min_amount').val($btn.data('min_amount'));
        $('#f_max_amount').val($btn.data('max_amount'));
        $('#f_description').val($btn.data('description'));
        $('#f_is_active').prop('checked', $btn.data('is_active') == 1);
        $('#editModal').modal('show');
    });
});
</script>
@endpush

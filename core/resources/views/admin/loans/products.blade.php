@extends('admin.layouts.app')
@section('panel')

<div class="card b-radius--10">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">Loan Products</h5>
        <button class="btn btn--primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="las la-plus me-1"></i>New Product
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table--light style--two mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Interest Rate</th>
                        <th>Min Amount</th>
                        <th>Max Amount</th>
                        <th>Savings Multiple</th>
                        <th>Min Savings</th>
                        <th>Tenures</th>
                        <th>Auto-Approve</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>
                            <strong>{{ $product->name }}</strong>
                            @if($product->description)
                                <br><small class="text-muted">{{ $product->description }}</small>
                            @endif
                        </td>
                        <td class="fw-bold text-success">{{ $product->interest_rate }}% p.a.</td>
                        <td>{{ showAmount($product->min_loan_amount) }}</td>
                        <td>{{ $product->max_loan_amount ? showAmount($product->max_loan_amount) : 'Unlimited' }}</td>
                        <td>{{ $product->savings_multiple }}×</td>
                        <td>{{ showAmount($product->min_savings_threshold) }}</td>
                        <td>
                            @foreach($product->tenure_options as $t)
                                @php
                                    $ts = strtolower(trim((string)$t));
                                    preg_match('/^(\d+)(w|m)?$/', $ts, $tp);
                                    $tUnit = ($tp[2] ?? 'm') === 'w' ? 'wk' : 'mo';
                                    $tColor = ($tp[2] ?? 'm') === 'w' ? 'warning' : 'info';
                                @endphp
                                <span class="badge badge--{{ $tColor }}">{{ $tp[1] ?? $ts }}{{ $tUnit }}</span>
                            @endforeach
                        </td>
                        <td>
                            @if($product->auto_approve)
                                <span class="badge badge--success">Yes</span>
                            @else
                                <span class="badge badge--warning">No</span>
                            @endif
                        </td>
                        <td>
                            @if($product->is_active)
                                <span class="badge badge--success">Active</span>
                            @else
                                <span class="badge badge--danger">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <div class="button--group">
                                <button class="btn btn-outline--primary btn-sm editProductBtn"
                                    data-id="{{ $product->id }}"
                                    data-name="{{ $product->name }}"
                                    data-description="{{ $product->description }}"
                                    data-min_membership_days="{{ $product->min_membership_days }}"
                                    data-min_savings_threshold="{{ $product->min_savings_threshold }}"
                                    data-savings_multiple="{{ $product->savings_multiple }}"
                                    data-max_loan_amount="{{ $product->max_loan_amount ?? '' }}"
                                    data-min_loan_amount="{{ $product->min_loan_amount }}"
                                    data-interest_rate="{{ $product->interest_rate }}"
                                    data-tenure_options="{{ implode(',', $product->tenure_options) }}"
                                    data-auto_approve="{{ $product->auto_approve ? 1 : 0 }}"
                                    data-is_active="{{ $product->is_active ? 1 : 0 }}">
                                    <i class="las la-pen"></i>
                                </button>
                                <form action="{{ route('admin.loans.products.toggle', $product->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm {{ $product->is_active ? 'btn-outline--danger' : 'btn-outline--success' }}"
                                        onclick="return confirm('Toggle this product status?')">
                                        <i class="las la-{{ $product->is_active ? 'eye-slash' : 'eye' }}"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No loan products yet. Create one to get started.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
        <div class="card-footer">{{ $products->links() }}</div>
        @endif
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Loan Product</h5>
                <button type="button" class="close" data-bs-dismiss="modal"><i class="las la-times"></i></button>
            </div>
            <form action="{{ route('admin.loans.products.store') }}" method="POST">
                @csrf
                @include('admin.loans._product_form')
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline--dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--primary"><i class="las la-save me-1"></i>Create Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Loan Product</h5>
                <button type="button" class="close" data-bs-dismiss="modal"><i class="las la-times"></i></button>
            </div>
            <form id="editProductForm" method="POST">
                @csrf
                @include('admin.loans._product_form')
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline--dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--primary"><i class="las la-save me-1"></i>Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
$(function () {
    $('.editProductBtn').on('click', function () {
        var $b = $(this);
        var form = $('#editProductForm');
        form.attr('action', '{{ route("admin.loans.products.store", ":id") }}'.replace(':id', $b.data('id')));
        form.find('[name="name"]').val($b.data('name'));
        form.find('[name="description"]').val($b.data('description'));
        form.find('[name="min_membership_days"]').val($b.data('min_membership_days'));
        form.find('[name="min_savings_threshold"]').val($b.data('min_savings_threshold'));
        form.find('[name="savings_multiple"]').val($b.data('savings_multiple'));
        form.find('[name="max_loan_amount"]').val($b.data('max_loan_amount'));
        form.find('[name="min_loan_amount"]').val($b.data('min_loan_amount'));
        form.find('[name="interest_rate"]').val($b.data('interest_rate'));
        form.find('[name="tenure_options"]').val($b.data('tenure_options'));
        form.find('[name="auto_approve"]').prop('checked', $b.data('auto_approve') == 1);
        form.find('[name="is_active"]').prop('checked', $b.data('is_active') == 1);
        $('#editModal').modal('show');
    });
});
</script>
@endpush

@extends('admin.layouts.app')
@section('panel')

<div class="card b-radius--10">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">Pending Loan Applications</h5>
        <a href="{{ route('admin.loans.index') }}" class="btn btn-outline--dark btn-sm">
            <i class="las la-list me-1"></i>All Loans
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table--light style--two mb-0">
                <thead>
                    <tr>
                        <th>Applied</th>
                        <th>User</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Tenure</th>
                        <th>Rate</th>
                        <th>Total Repayable</th>
                        <th>Savings at Application</th>
                        <th>Purpose</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                    <tr>
                        <td>{{ $loan->created_at->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('admin.users.detail', $loan->user_id) }}" class="fw-bold">
                                {{ $loan->user->username ?? '—' }}
                            </a><br>
                            <small class="text-muted">{{ $loan->user->email ?? '' }}</small>
                        </td>
                        <td>{{ $loan->loanProduct->name ?? '—' }}</td>
                        <td class="fw-bold">{{ showAmount($loan->original_amount) }}</td>
                        <td>{{ $loan->tenure_months }} months</td>
                        <td>{{ $loan->interest_rate }}% p.a.</td>
                        <td>{{ showAmount($loan->total_repayable) }}</td>
                        <td>{{ showAmount($loan->savings_balance_at_application) }}</td>
                        <td class="text-muted small">{{ $loan->purpose ?: '—' }}</td>
                        <td>
                            <div class="button--group">
                                <a href="{{ route('admin.loans.show', $loan->id) }}" class="btn btn-outline--primary btn-sm">
                                    <i class="las la-eye"></i> View
                                </a>
                                <form action="{{ route('admin.loans.approve', $loan->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline--success btn-sm"
                                        onclick="return confirm('Approve and disburse {{ showAmount($loan->original_amount) }} to {{ $loan->user->username ?? "this user" }}?')">
                                        <i class="las la-check"></i> Approve
                                    </button>
                                </form>
                                <button class="btn btn-outline--danger btn-sm rejectBtn" data-id="{{ $loan->id }}">
                                    <i class="las la-times"></i> Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-5">
                            <i class="las la-check-circle fs-1 d-block mb-2 text-success"></i>
                            No pending applications. All caught up!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($loans->hasPages())
        <div class="card-footer">{{ $loans->links() }}</div>
        @endif
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Loan Application</h5>
                <button type="button" class="close" data-bs-dismiss="modal"><i class="las la-times"></i></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <label class="form-label fw-bold">Reason for Rejection <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="rejection_reason" rows="4" required maxlength="500"
                        placeholder="This reason will be stored for audit purposes…"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline--dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--danger"><i class="las la-times-circle me-1"></i>Reject Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
$(function () {
    $('.rejectBtn').on('click', function () {
        $('#rejectForm').attr('action', '{{ route("admin.loans.reject", ":id") }}'.replace(':id', $(this).data('id')));
        $('#rejectModal').modal('show');
    });
});
</script>
@endpush

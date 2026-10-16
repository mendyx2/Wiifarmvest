@extends('admin.layouts.app')
@section('panel')

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="dashboard-w1 b-radius--10 bg--primary">
            <div class="icon"><i class="las la-file-invoice-dollar"></i></div>
            <div class="details">
                <p class="text-white">Total Loans</p>
                <h3 class="text-white">{{ $summary->total ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dashboard-w1 b-radius--10 bg--warning">
            <div class="icon"><i class="las la-clock"></i></div>
            <div class="details">
                <p class="text-white">Pending</p>
                <h3 class="text-white">{{ $summary->pending_count ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dashboard-w1 b-radius--10 bg--success">
            <div class="icon"><i class="las la-hand-holding-usd"></i></div>
            <div class="details">
                <p class="text-white">Total Disbursed</p>
                <h3 class="text-white">{{ showAmount($summary->total_disbursed ?? 0) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dashboard-w1 b-radius--10 bg--danger">
            <div class="icon"><i class="las la-exclamation-circle"></i></div>
            <div class="details">
                <p class="text-white">Outstanding</p>
                <h3 class="text-white">{{ showAmount($summary->total_outstanding ?? 0) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card b-radius--10">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0">All Loans</h5>
        <div class="d-flex gap-2 flex-wrap">
            @if(($summary->pending_count ?? 0) > 0)
            <a href="{{ route('admin.loans.pending') }}" class="btn btn-outline--warning btn-sm">
                <i class="las la-clock me-1"></i>Pending
                <span class="badge bg-warning text-dark ms-1">{{ $summary->pending_count }}</span>
            </a>
            @endif
            <a href="{{ route('admin.loans.products') }}" class="btn btn-outline--primary btn-sm">
                <i class="las la-cog me-1"></i>Loan Products
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card-body border-bottom pb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-5">
                <input type="text" name="search" class="form-control" placeholder="Search by username or email…" value="{{ request('search') }}">
            </div>
            <div class="col-sm-4">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    @foreach(['pending','active','at_risk','defaulted','cleared','rejected'] as $s)
                    <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-3">
                <button type="submit" class="btn btn--primary w-100"><i class="las la-filter me-1"></i>Filter</button>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table--light style--two mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Reference</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Outstanding</th>
                        <th>Rate</th>
                        <th>Tenure</th>
                        <th>Next Due</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                    @php
                        $statusColors = ['pending'=>'warning','active'=>'success','at_risk'=>'warning','defaulted'=>'danger','cleared'=>'primary','rejected'=>'danger'];
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.users.detail', $loan->user_id) }}" class="fw-bold">
                                {{ $loan->user->username ?? '—' }}
                            </a>
                        </td>
                        <td><code>{{ $loan->reference }}</code></td>
                        <td>{{ $loan->loanProduct->name ?? '—' }}</td>
                        <td class="fw-bold">{{ showAmount($loan->original_amount) }}</td>
                        <td class="{{ $loan->outstanding > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                            {{ showAmount($loan->outstanding) }}
                        </td>
                        <td>{{ $loan->interest_rate }}%</td>
                        <td>{{ $loan->tenure_months }}m</td>
                        <td>{{ $loan->next_due_date ? $loan->next_due_date->format('M d, Y') : '—' }}</td>
                        <td>
                            <span class="badge badge--{{ $statusColors[$loan->status] ?? 'secondary' }}">
                                {{ ucfirst(str_replace('_', ' ', $loan->status)) }}
                            </span>
                        </td>
                        <td>
                            <div class="button--group">
                                <a href="{{ route('admin.loans.show', $loan->id) }}" class="btn btn-outline--primary btn-sm">
                                    <i class="las la-eye"></i>
                                </a>
                                @if($loan->status === 'pending')
                                <form action="{{ route('admin.loans.approve', $loan->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-outline--success btn-sm"
                                        onclick="return confirm('Approve and disburse {{ showAmount($loan->original_amount) }} to this member?')">
                                        <i class="las la-check"></i>
                                    </button>
                                </form>
                                <button class="btn btn-outline--danger btn-sm rejectBtn"
                                    data-id="{{ $loan->id }}">
                                    <i class="las la-times"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">No loans found.</td>
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
                        placeholder="Explain why this application was rejected…"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline--dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--danger"><i class="las la-times-circle me-1"></i>Reject</button>
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
        var id = $(this).data('id');
        $('#rejectForm').attr('action', '{{ route("admin.loans.reject", ":id") }}'.replace(':id', id));
        $('#rejectModal').modal('show');
    });
});
</script>
@endpush

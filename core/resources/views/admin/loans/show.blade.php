@extends('admin.layouts.app')
@section('panel')

<div class="row mb-4">
    <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <a href="{{ route('admin.loans.index') }}" class="btn btn-outline--dark btn-sm">
            <i class="las la-arrow-left me-1"></i>Back to Loans
        </a>
        @if($loan->status === 'pending')
        <div class="d-flex gap-2">
            <form action="{{ route('admin.loans.approve', $loan->id) }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline--success btn-sm"
                    onclick="return confirm('Approve and disburse {{ showAmount($loan->original_amount) }}?')">
                    <i class="las la-check me-1"></i>Approve & Disburse
                </button>
            </form>
            <button class="btn btn-outline--danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal">
                <i class="las la-times me-1"></i>Reject
            </button>
        </div>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">

        {{-- Loan info --}}
        <div class="card b-radius--10 mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">{{ $loan->reference }}</h5>
                @php $statusColors = ['pending'=>'warning','active'=>'success','at_risk'=>'warning','defaulted'=>'danger','cleared'=>'primary','rejected'=>'danger']; @endphp
                <span class="badge badge--{{ $statusColors[$loan->status] ?? 'secondary' }} fs-6">
                    {{ ucfirst(str_replace('_', ' ', $loan->status)) }}
                </span>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted w-40">Member</td>
                        <td><a href="{{ route('admin.users.detail', $loan->user_id) }}" class="fw-bold">{{ $loan->user->username ?? '—' }}</a></td>
                    </tr>
                    <tr><td class="text-muted">Product</td><td>{{ $loan->loanProduct->name ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Loan Amount</td><td class="fw-bold">{{ showAmount($loan->original_amount) }}</td></tr>
                    <tr><td class="text-muted">Interest Rate</td><td class="text-success fw-bold">{{ $loan->interest_rate }}% p.a.</td></tr>
                    <tr><td class="text-muted">Total Interest</td><td>{{ showAmount($loan->total_interest) }}</td></tr>
                    <tr><td class="text-muted">Total Repayable</td><td class="fw-bold">{{ showAmount($loan->total_repayable) }}</td></tr>
                    <tr><td class="text-muted">Outstanding</td>
                        <td class="{{ $loan->outstanding > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                            {{ showAmount($loan->outstanding) }}
                        </td>
                    </tr>
                    <tr><td class="text-muted">Total Repaid</td><td class="text-success fw-bold">{{ showAmount($loan->total_repaid) }}</td></tr>
                    @php
                        $tuUnit  = $loan->tenure_unit ?? 'month';
                        $tuLabel = $loan->tenure_months . ' ' . ($tuUnit === 'week' ? 'week' : 'month') . ($loan->tenure_months > 1 ? 's' : '');
                        $instLabel = ucfirst($tuUnit) . 'ly Installment';
                    @endphp
                    <tr><td class="text-muted">Tenure</td><td>{{ $tuLabel }}</td></tr>
                    <tr><td class="text-muted">{{ $instLabel }}</td><td>{{ showAmount($loan->monthly_installment) }}</td></tr>
                    <tr><td class="text-muted">Next Due</td><td>{{ $loan->next_due_date ? $loan->next_due_date->format('M d, Y') : '—' }}</td></tr>
                    <tr><td class="text-muted">Applied On</td><td>{{ $loan->created_at->format('M d, Y H:i') }}</td></tr>
                    @if($loan->approved_at)
                    <tr><td class="text-muted">Approved</td><td>{{ $loan->approved_at->format('M d, Y H:i') }}</td></tr>
                    @endif
                    @if($loan->cleared_at)
                    <tr><td class="text-muted">Cleared</td><td>{{ $loan->cleared_at->format('M d, Y H:i') }}</td></tr>
                    @endif
                    <tr><td class="text-muted">Savings at Application</td><td>{{ showAmount($loan->savings_balance_at_application) }}</td></tr>
                    <tr><td class="text-muted">Purpose</td><td class="text-muted">{{ $loan->purpose ?: '—' }}</td></tr>
                    @if($loan->rejection_reason)
                    <tr><td class="text-muted">Rejection Reason</td>
                        <td class="text-danger small">{{ $loan->rejection_reason }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- Repayment progress --}}
        @php
            $pct = $loan->total_repayable > 0
                ? min(100, ($loan->total_repaid / $loan->total_repayable) * 100)
                : 0;
        @endphp
        <div class="card b-radius--10">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-bold">Repayment Progress</span>
                    <span class="fw-bold text-success">{{ round($pct) }}%</span>
                </div>
                <div class="progress" style="height:12px;border-radius:999px;">
                    <div class="progress-bar bg-success" style="width:{{ $pct }}%;border-radius:999px;"></div>
                </div>
                <div class="d-flex justify-content-between mt-2 text-muted small">
                    <span>{{ showAmount($loan->total_repaid) }} repaid</span>
                    <span>{{ showAmount($loan->outstanding) }} remaining</span>
                </div>
            </div>
        </div>

    </div>

    <div class="col-lg-7">

        {{-- Repayment schedule --}}
        <div class="card b-radius--10 mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Repayment Schedule</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table--light style--two mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Due Date</th>
                                <th>Principal</th>
                                <th>Interest</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($loan->schedule as $inst)
                            @php $sColors = ['paid'=>'success','partial'=>'info','overdue'=>'danger','defaulted'=>'danger','pending'=>'secondary']; @endphp
                            <tr>
                                <td>{{ $inst->installment_number }}</td>
                                <td>{{ $inst->due_date->format('M d, Y') }}</td>
                                <td>{{ showAmount($inst->principal) }}</td>
                                <td>{{ showAmount($inst->interest) }}</td>
                                <td class="fw-bold">{{ showAmount($inst->total) }}</td>
                                <td class="{{ $inst->amount_paid > 0 ? 'text-success' : '' }}">{{ showAmount($inst->amount_paid) }}</td>
                                <td><span class="badge badge--{{ $sColors[$inst->status] ?? 'secondary' }}">{{ ucfirst($inst->status) }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">No schedule generated.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Repayment history --}}
        <div class="card b-radius--10">
            <div class="card-header">
                <h5 class="card-title mb-0">Repayment History</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table--light style--two mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Reference</th>
                                <th>Amount</th>
                                <th>Source</th>
                                <th>Outstanding After</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($loan->repayments as $rep)
                            <tr>
                                <td>{{ $rep->created_at->format('M d, Y') }}</td>
                                <td><code class="small">{{ $rep->reference }}</code></td>
                                <td class="text-success fw-bold">{{ showAmount($rep->amount) }}</td>
                                <td><span class="badge badge--{{ $rep->source === 'manual' ? 'info' : 'success' }}">{{ ucfirst($rep->source) }}</span></td>
                                <td>{{ showAmount($rep->outstanding_after) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No repayments recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Reject Modal --}}
@if($loan->status === 'pending')
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Loan Application</h5>
                <button type="button" class="close" data-bs-dismiss="modal"><i class="las la-times"></i></button>
            </div>
            <form action="{{ route('admin.loans.reject', $loan->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <label class="form-label fw-bold">Reason for Rejection <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="rejection_reason" rows="4" required maxlength="500"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline--dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection

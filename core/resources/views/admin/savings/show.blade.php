@extends('admin.layouts.app')
@section('panel')

<div class="row mb-4">
    <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-2">
        <a href="{{ route('admin.savings.index') }}" class="btn btn-outline--dark btn-sm">
            <i class="las la-arrow-left me-1"></i>Back to Savings
        </a>
        @if($saving->status !== 'closed' && $saving->maturity_date && $saving->maturity_date->isPast())
        <button type="button" class="btn btn-success btn-sm" id="transferBtn"
                data-id="{{ $saving->id }}"
                data-user="{{ $saving->user->username ?? 'this user' }}"
                data-amount="{{ showAmount((float)$saving->balance + (float)$saving->interest_earned) }}">
            <i class="las la-exchange-alt me-1"></i>Transfer to Money Box
        </button>
        @elseif($saving->status !== 'closed' && $saving->maturity_date && !$saving->maturity_date->isPast())
        <span class="badge badge--warning fs-6">
            <i class="las la-clock me-1"></i>Matures {{ $saving->maturity_date->format('M d, Y') }}
        </span>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">

        {{-- Product info --}}
        <div class="card b-radius--10 mb-4">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Savings Product</h5>
                @php $badgeMap = ['active'=>'success','matured'=>'warning','closed'=>'dark']; @endphp
                <span class="badge badge--{{ $badgeMap[$saving->status] ?? 'secondary' }} fs-6">
                    {{ ucfirst($saving->status) }}
                </span>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted w-40">Reference</td><td><code>{{ $saving->reference }}</code></td></tr>
                    <tr><td class="text-muted">Owner</td>
                        <td><a href="{{ route('admin.users.detail', $saving->user_id) }}" class="fw-bold">{{ $saving->user->username ?? '—' }}</a></td>
                    </tr>
                    <tr><td class="text-muted">Name</td><td>{{ $saving->name }}</td></tr>
                    <tr><td class="text-muted">Type</td>
                        <td><span class="badge badge--{{ $saving->type === 'target' ? 'info' : ($saving->type === 'fixed' ? 'warning' : 'success') }}">{{ ucfirst($saving->type) }}</span></td>
                    </tr>
                    <tr><td class="text-muted">Principal</td><td class="fw-bold">{{ showAmount($saving->principal) }}</td></tr>
                    <tr><td class="text-muted">Current Balance</td><td class="fw-bold">{{ showAmount($saving->balance) }}</td></tr>
                    <tr><td class="text-muted">Target Amount</td><td>{{ $saving->target_amount ? showAmount($saving->target_amount) : '—' }}</td></tr>
                    <tr><td class="text-muted">Interest Rate</td><td class="text-success fw-bold">{{ $saving->interest_rate }}% p.a.</td></tr>
                    <tr><td class="text-muted">Interest Earned</td><td class="text-success">{{ showAmount($saving->interest_earned) }}</td></tr>
                    <tr><td class="text-muted">Start Date</td><td>{{ $saving->start_date?->format('M d, Y') ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Maturity Date</td><td>{{ $saving->maturity_date?->format('M d, Y') ?? '—' }}</td></tr>
                    @if($saving->closed_at)
                    <tr><td class="text-muted">Closed At</td><td>{{ $saving->closed_at->format('M d, Y H:i') }}</td></tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- Progress --}}
        @php
            $pct = $saving->target_amount > 0
                ? min(100, ($saving->balance / $saving->target_amount) * 100)
                : 100;
        @endphp
        <div class="card b-radius--10">
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-bold">Goal Progress</span>
                    <span class="fw-bold text-success">{{ round($pct) }}%</span>
                </div>
                <div class="progress" style="height:12px;border-radius:999px;">
                    <div class="progress-bar bg-success" style="width:{{ $pct }}%;border-radius:999px;"></div>
                </div>
                <div class="d-flex justify-content-between mt-2 text-muted small">
                    <span>{{ showAmount($saving->balance) }} saved</span>
                    <span>{{ showAmount($saving->target_amount ?? $saving->principal) }} goal</span>
                </div>
            </div>
        </div>

    </div>

    <div class="col-lg-7">
        {{-- Transactions linked to this savings --}}
        <div class="card b-radius--10">
            <div class="card-header">
                <h5 class="card-title mb-0">Transaction History</h5>
            </div>
            <div class="card-body p-0">
                @php
                    $txns = \App\Models\Transaction::where('user_id', $saving->user_id)
                                ->where(function($q) use ($saving) {
                                    $q->where('trx', $saving->reference)
                                      ->orWhere(function($q2) use ($saving) {
                                          $q2->whereIn('remark', ['savings_lock','savings_withdrawal'])
                                             ->where('details', 'like', '%'.$saving->name.'%');
                                      });
                                })
                                ->latest()->get();
                @endphp
                <div class="table-responsive">
                    <table class="table table--light style--two mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>TRX</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($txns as $txn)
                            <tr>
                                <td>{{ $txn->created_at->format('M d, Y') }}</td>
                                <td><code class="small">{{ $txn->trx }}</code></td>
                                <td class="{{ $txn->trx_type === '+' ? 'text-success' : 'text-danger' }} fw-bold">
                                    {{ $txn->trx_type }}{{ showAmount($txn->amount) }}
                                </td>
                                <td>
                                    <span class="badge badge--{{ $txn->trx_type === '+' ? 'success' : 'danger' }}">
                                        {{ $txn->trx_type === '+' ? 'Credit' : 'Debit' }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $txn->details }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No transactions found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Transfer confirmation modal --}}
<div class="modal fade" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="transferModalLabel">
                    <i class="las la-exchange-alt me-1"></i> Transfer Savings to Money Box
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">You are about to transfer the savings balance and interest to the user's Money Box.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">User</td>
                            <td class="fw-bold" id="confirmUser">—</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Total Transfer</td>
                            <td class="fw-bold text-success" id="confirmAmount">—</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Reference</td>
                            <td><code>{{ $saving->reference }}</code></td>
                        </tr>
                    </table>
                </div>
                <div class="alert alert-warning mt-3 mb-0 p-2 small">
                    <i class="las la-exclamation-triangle me-1"></i>
                    This action is irreversible. The savings product will be closed after transfer.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmTransferBtn">
                    <i class="las la-check me-1"></i> Confirm Transfer
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
$(function () {
    var transferUrl = '{{ route('admin.savings.transfer', $saving->id) }}';

    // Open confirmation modal
    $('#transferBtn').on('click', function () {
        var $btn = $(this);
        $('#confirmUser').text($btn.data('user'));
        $('#confirmAmount').text($btn.data('amount'));
        $('#transferModal').modal('show');
    });

    // Confirm and dispatch via AJAX
    $('#confirmTransferBtn').on('click', function () {
        var $btn = $(this);
        $btn.prop('disabled', true).html('<i class="las la-spinner la-spin me-1"></i>Processing…');

        $.ajax({
            url: transferUrl,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function (res) {
                $('#transferModal').modal('hide');
                iziToast.success({ title: 'Success', message: res.message, position: 'topRight' });

                // Disable the transfer button and update status badge
                $('#transferBtn').prop('disabled', true).text('Transfer Queued');
                setTimeout(function () { location.reload(); }, 2500);
            },
            error: function (xhr) {
                var msg = xhr.responseJSON ? xhr.responseJSON.message : 'An error occurred. Please try again.';
                iziToast.error({ title: 'Error', message: msg, position: 'topRight' });
                $btn.prop('disabled', false).html('<i class="las la-check me-1"></i> Confirm Transfer');
            }
        });
    });
});
</script>
@endpush

@extends('admin.layouts.app')
@section('panel')

<div class="row">
    <div class="col-12">
        <div class="card b-radius--10">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Farm Cycles</h5>
                <button class="btn btn--primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="las la-plus me-1"></i>New Cycle
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table--light style--two mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Contribution Range</th>
                                <th>Yield Range</th>
                                <th>Opens At</th>
                                <th>Maturity Date</th>
                                <th>Subscribers</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cycles as $cycle)
                            @php
                                $maturityReached = $cycle->isMaturityDateReached();
                                $activeCount     = $cycle->activeSavingsCount();
                                $activeTotal     = $cycle->activeSavingsTotal();
                            @endphp
                            <tr>
                                <td><strong>{{ $cycle->name }}</strong></td>
                                <td>
                                    {{ showAmount($cycle->min_contribution) }}
                                    @if($cycle->max_contribution)
                                        – {{ showAmount($cycle->max_contribution) }}
                                    @endif
                                </td>
                                <td>
                                    {{ $cycle->min_yield }}% – {{ $cycle->max_yield }}%
                                    @if($cycle->actual_yield)
                                        <br><small class="text-success fw-bold">Actual: {{ $cycle->actual_yield }}%</small>
                                    @endif
                                </td>
                                <td>{{ $cycle->subscription_opens_at?->format('M d, Y') ?? '—' }}</td>
                                <td>
                                    {{ $cycle->maturity_date?->format('M d, Y') ?? '—' }}
                                    @if($cycle->status === 'closed' && !$maturityReached)
                                        <br><small class="text-warning">
                                            <i class="las la-clock"></i>
                                            {{ now()->diffInDays($cycle->maturity_date, false) * -1 }} days left
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge--primary">{{ $activeCount }}</span>
                                    @if($activeCount > 0)
                                        <br><small class="text-muted">{{ showAmount($activeTotal) }}</small>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $colors = ['open' => 'success', 'closed' => 'warning', 'matured' => 'info'];
                                    @endphp
                                    <span class="badge badge--{{ $colors[$cycle->status] ?? 'secondary' }}">
                                        {{ ucfirst($cycle->status) }}
                                    </span>
                                    @if($cycle->payout_processed)
                                        <br><small class="text-success"><i class="las la-check-circle"></i> Paid out</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        {{-- Edit button (not for matured cycles) --}}
                                        @if($cycle->status !== 'matured')
                                        <button class="btn btn-outline--primary btn-sm editCycleBtn"
                                            data-id="{{ $cycle->id }}"
                                            data-name="{{ $cycle->name }}"
                                            data-min_contribution="{{ $cycle->min_contribution }}"
                                            data-max_contribution="{{ $cycle->max_contribution }}"
                                            data-min_yield="{{ $cycle->min_yield }}"
                                            data-max_yield="{{ $cycle->max_yield }}"
                                            data-actual_yield="{{ $cycle->actual_yield }}"
                                            data-subscription_opens_at="{{ $cycle->subscription_opens_at?->format('Y-m-d') }}"
                                            data-maturity_date="{{ $cycle->maturity_date?->format('Y-m-d') }}"
                                            data-status="{{ $cycle->status }}"
                                            title="Edit">
                                            <i class="las la-pen"></i>
                                        </button>
                                        @endif

                                        {{-- Close button (open cycles only) --}}
                                        @if($cycle->status === 'open')
                                        <button class="btn btn-outline--warning btn-sm closeCycleBtn"
                                            data-id="{{ $cycle->id }}"
                                            data-name="{{ $cycle->name }}"
                                            data-count="{{ $activeCount }}"
                                            title="Close this cycle">
                                            <i class="las la-lock"></i>
                                        </button>
                                        @endif

                                        {{-- Mature button (closed cycles only) --}}
                                        @if($cycle->status === 'closed' && !$cycle->payout_processed)
                                            @if($maturityReached)
                                            <button class="btn btn--success btn-sm matureCycleBtn"
                                                data-id="{{ $cycle->id }}"
                                                data-name="{{ $cycle->name }}"
                                                data-count="{{ $activeCount }}"
                                                data-total="{{ $activeTotal }}"
                                                data-actual_yield="{{ $cycle->actual_yield }}"
                                                title="Process maturity payout">
                                                <i class="las la-seedling me-1"></i>Pay Out
                                            </button>
                                            @else
                                            <button class="btn btn--secondary btn-sm" disabled
                                                title="Maturity date ({{ $cycle->maturity_date?->format('M d, Y') }}) not yet reached">
                                                <i class="las la-clock me-1"></i>Pay Out
                                            </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No farm cycles yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($cycles->hasPages())
                <div class="card-footer">{{ $cycles->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Farm Cycle</h5>
                <button type="button" class="close" data-bs-dismiss="modal"><i class="las la-times"></i></button>
            </div>
            <form action="{{ route('admin.savings.cycles.store') }}" method="POST">
                @csrf
                @include('admin.savings._cycle_form')
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline--dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--primary"><i class="las la-save me-1"></i>Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Farm Cycle</h5>
                <button type="button" class="close" data-bs-dismiss="modal"><i class="las la-times"></i></button>
            </div>
            <form id="editCycleForm" method="POST">
                @csrf
                @include('admin.savings._cycle_form')
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline--dark" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--primary"><i class="las la-save me-1"></i>Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Close Cycle Confirmation Modal --}}
<div class="modal fade" id="closeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title"><i class="las la-lock me-2"></i>Close Farm Cycle</h5>
                <button type="button" class="close" data-bs-dismiss="modal"><i class="las la-times"></i></button>
            </div>
            <div class="modal-body">
                <p>You are about to <strong>close</strong> the cycle: <strong id="closeCycleName"></strong></p>
                <div class="alert alert-warning">
                    <i class="las la-exclamation-triangle me-2"></i> 
                    <strong id="closeCount"></strong> active savings account(s) are linked to this cycle.
                    Once closed, <strong>no new contributions</strong> will be accepted from any user.
                    Existing savings remain intact until maturity.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline--dark" data-bs-dismiss="modal">Cancel</button>
                <form id="closeForm" method="POST">
                    @csrf
                    <button type="submit" class="btn btn--warning">
                        <i class="las la-lock me-1"></i>Yes, Close Cycle
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Mature / Payout Confirmation Modal --}}
<div class="modal fade" id="matureModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="las la-seedling me-2"></i>Process Maturity Payout</h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal"><i class="las la-times"></i></button>
            </div>
            <div class="modal-body">
                <p>You are about to process the maturity payout for: <strong id="matureCycleName"></strong></p>
                <div class="alert alert-info">
                    <i class="las la-info-circle me-2"></i>
                    <strong id="matureCount"></strong> savings account(s) totalling
                    <strong>{{ gs('cur_sym') }}<span id="matureTotal"></span></strong> in principal will be credited
                    with <strong>principal + interest</strong> to each user's Money Box. All payouts are
                    recorded in the transaction history.
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Actual Yield % <span class="text-danger">*</span>
                        <small class="text-muted fw-normal">— the confirmed return rate for this cycle</small>
                    </label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="matureYieldInput"
                               step="0.01" min="0" max="100" placeholder="e.g. 18.5" required>
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="text-muted">Estimated range: <span id="matureYieldRange"></span></small>
                </div>
                <p class="text-danger mb-0">
                    <strong>This action cannot be undone.</strong> The payout job will be queued immediately.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline--dark" data-bs-dismiss="modal">Cancel</button>
                <form id="matureForm" method="POST">
                    @csrf
                    <input type="hidden" name="actual_yield" id="matureYieldHidden">
                    <button type="submit" class="btn btn--success" id="matureSubmitBtn">
                        <i class="las la-seedling me-1"></i>Confirm & Pay Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
$(function () {

    /* ── Edit cycle ─────────────────────────── */
    $('.editCycleBtn').on('click', function () {
        var $b   = $(this);
        var form = $('#editCycleForm');
        form.attr('action', '{{ route("admin.savings.cycles.store", ":id") }}'.replace(':id', $b.data('id')));
        form.find('[name="name"]').val($b.data('name'));
        form.find('[name="min_contribution"]').val($b.data('min_contribution'));
        form.find('[name="max_contribution"]').val($b.data('max_contribution'));
        form.find('[name="min_yield"]').val($b.data('min_yield'));
        form.find('[name="max_yield"]').val($b.data('max_yield'));
        form.find('[name="actual_yield"]').val($b.data('actual_yield'));
        form.find('[name="subscription_opens_at"]').val($b.data('subscription_opens_at'));
        form.find('[name="maturity_date"]').val($b.data('maturity_date'));
        form.find('[name="status"]').val($b.data('status'));
        $('#editModal').modal('show');
    });

    /* ── Close cycle ────────────────────────── */
    $('.closeCycleBtn').on('click', function () {
        var $b = $(this);
        $('#closeCycleName').text($b.data('name'));
        $('#closeCount').text($b.data('count'));
        $('#closeForm').attr('action',
            '{{ route("admin.savings.cycles.close", ":id") }}'.replace(':id', $b.data('id'))
        );
        $('#closeModal').modal('show');
    });

    /* ── Mature cycle ───────────────────────── */
    $('.matureCycleBtn').on('click', function () {
        var $b = $(this);
        var total = parseFloat($b.data('total') || 0).toLocaleString('en', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
        $('#matureCycleName').text($b.data('name'));
        $('#matureCount').text($b.data('count'));
        $('#matureTotal').text(total);
        $('#matureYieldRange').text($b.data('min_yield') + '% – ' + $b.data('max_yield') + '%');
        $('#matureYieldInput').val($b.data('actual_yield') || '');
        $('#matureForm').attr('action',
            '{{ route("admin.savings.cycles.mature", ":id") }}'.replace(':id', $b.data('id'))
        );
        $('#matureModal').modal('show');
    });

    /* ── Sync actual_yield input → hidden field on submit ── */
    $('#matureForm').on('submit', function (e) {
        var yield_ = parseFloat($('#matureYieldInput').val());
        if (!yield_ || yield_ <= 0) {
            e.preventDefault();
            $('#matureYieldInput').addClass('is-invalid').focus();
            return;
        }
        $('#matureYieldHidden').val(yield_);
    });

    $('#matureYieldInput').on('input', function () {
        $(this).removeClass('is-invalid');
    });
});
</script>
@endpush

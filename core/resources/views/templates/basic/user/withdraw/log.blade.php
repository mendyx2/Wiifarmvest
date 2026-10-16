@extends($activeTemplate . 'layouts.master')
@section('content')

@php
    use App\Models\Withdrawal;
    use App\Constants\Status;
    $totalWithdrawn   = Withdrawal::where('user_id', auth()->id())->where('status', Status::PAYMENT_SUCCESS)->sum('amount');
    $pendingTotal     = Withdrawal::where('user_id', auth()->id())->where('status', Status::PAYMENT_PENDING)->sum('amount');
    $rejectedTotal    = Withdrawal::where('user_id', auth()->id())->where('status', Status::PAYMENT_REJECT)->sum('amount');
    $totalRequests    = Withdrawal::where('user_id', auth()->id())->where('status', '!=', Status::PAYMENT_INITIATE)->count();
@endphp

{{-- Header --}}
<div class="nc-wrap" id="ncWrap">
    <br/>
<div class="wl-page">

    {{-- Summary stats --}}
    <div class="wl-stats">
        <div class="wl-stat wl-stat-green">
            <div class="wl-stat-icon"><i class="las la-check-circle"></i></div>
            <div class="wl-stat-body">
                <p class="wl-stat-label">Total Withdrawn</p>
                <h4 class="wl-stat-val">{{ gs('cur_sym') }}{{ number_format($totalWithdrawn, 2) }}</h4>
                <p class="wl-stat-sub">Approved payouts</p>
            </div>
        </div>
        <div class="wl-stat wl-stat-amber">
            <div class="wl-stat-icon"><i class="las la-clock"></i></div>
            <div class="wl-stat-body">
                <p class="wl-stat-label">Pending</p>
                <h4 class="wl-stat-val">{{ gs('cur_sym') }}{{ number_format($pendingTotal, 2) }}</h4>
                <p class="wl-stat-sub">Awaiting approval</p>
            </div>
        </div>
        <div class="wl-stat wl-stat-rose">
            <div class="wl-stat-icon"><i class="las la-times-circle"></i></div>
            <div class="wl-stat-body">
                <p class="wl-stat-label">Rejected</p>
                <h4 class="wl-stat-val">{{ gs('cur_sym') }}{{ number_format($rejectedTotal, 2) }}</h4>
                <p class="wl-stat-sub">Declined requests</p>
            </div>
        </div>
        <div class="wl-stat wl-stat-blue">
            <div class="wl-stat-icon"><i class="las la-list-alt"></i></div>
            <div class="wl-stat-body">
                <p class="wl-stat-label">Total Requests</p>
                <h4 class="wl-stat-val">{{ $totalRequests }}</h4>
                <p class="wl-stat-sub">All time</p>
            </div>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="wl-toolbar">
        <form class="wl-search-form" method="GET">
            <i class="las la-search wl-search-icon"></i>
            <input class="wl-search-input" name="search" type="search"
                   value="{{ request()->search }}" placeholder="Search by transaction ID…">
        </form>
        <a href="{{ route('user.withdraw') }}" class="wl-new-btn">
            <i class="las la-plus"></i> Withdraw Now
        </a>
    </div>

    {{-- List --}}
    <div class="wl-card">

        {{-- Table header --}}
        <div class="wl-list-head">
            <span>Gateway / Trx</span>
            <span>Date</span>
            <span>Amount</span>
            <span>Conversion</span>
            <span>Status</span>
            <span>Detail</span>
        </div>

        @forelse($withdraws as $withdraw)
        @php
            $details = [];
            foreach ($withdraw->withdraw_information as $key => $info) {
                $details[] = $info;
                if ($info->type == 'file' && @$info->value) {
                    $details[$key]->value = route('user.download.attachment', encrypt(getFilePath('verify') . '/' . $info->value));
                }
            }
            $statusMap = [
                \App\Constants\Status::PAYMENT_SUCCESS => ['label' => 'Approved', 'cls' => 'wl-badge-green'],
                \App\Constants\Status::PAYMENT_PENDING => ['label' => 'Pending',  'cls' => 'wl-badge-amber'],
                \App\Constants\Status::PAYMENT_REJECT  => ['label' => 'Rejected', 'cls' => 'wl-badge-rose'],
            ];
            $st = $statusMap[$withdraw->status] ?? ['label' => 'Unknown', 'cls' => 'wl-badge-grey'];
        @endphp
        <div class="wl-row">
            <div class="wl-col-gw">
                <div class="wl-gw-name">{{ __(@$withdraw->method->name) }}</div>
                <div class="wl-gw-trx">{{ $withdraw->trx }}</div>
            </div>
            <div class="wl-col-date">
                <span class="wl-date-main">{{ $withdraw->created_at->format('M d, Y') }}</span>
                <span class="wl-date-sub">{{ $withdraw->created_at->format('h:i A') }}</span>
            </div>
            <div class="wl-col-amt">
                <span class="wl-amt-net">{{ showAmount($withdraw->amount - $withdraw->charge) }}</span>
                <span class="wl-amt-gross">{{ showAmount($withdraw->amount) }} − <span class="wl-charge">{{ showAmount($withdraw->charge) }}</span></span>
            </div>
            <div class="wl-col-conv">
                <span class="wl-conv-rate">1 {{ gs('cur_text') }} = {{ showAmount($withdraw->rate, currencyFormat:false) }} {{ __($withdraw->currency) }}</span>
                <span class="wl-conv-final">{{ showAmount($withdraw->final_amount, currencyFormat:false) }} {{ __($withdraw->currency) }}</span>
            </div>
            <div class="wl-col-status">
                <span class="wl-badge {{ $st['cls'] }}">{{ $st['label'] }}</span>
            </div>
            <div class="wl-col-action">
                <button class="wl-detail-btn detailBtn"
                    data-user_data="{{ json_encode($details) }}"
                    @if($withdraw->status == Status::PAYMENT_REJECT) data-admin_feedback="{{ $withdraw->admin_feedback }}" @endif
                    title="View Details">
                    <i class="las la-eye"></i>
                </button>
            </div>
        </div>
        @empty
        <div class="wl-empty">
            <div class="wl-empty-icon"><i class="las la-arrow-circle-up"></i></div>
            <h6>No Withdrawals Yet</h6>
            <p>Your withdrawal requests will appear here</p>
            <a href="{{ route('user.withdraw') }}" class="wl-empty-btn">Make a Withdrawal</a>
        </div>
        @endforelse
    </div>

    @if($withdraws->hasPages())
    <div class="wl-pagination">{{ paginateLinks($withdraws) }}</div>
    @endif

</div>
</div>

{{-- Withdrawal Suspended Modal --}}
@if(auth()->user()->withdrawal_blocked)
<div class="modal fade" id="withdrawSuspendedModal" tabindex="-1" aria-labelledby="withdrawSuspendedLabel" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content wl-suspend-modal">
            <div class="modal-header wl-suspend-head">
                <h5 class="modal-title wl-suspend-title" id="withdrawSuspendedLabel">
                    <i class="las la-ban"></i> Withdraw Suspended
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body wl-suspend-body">
                <div class="wl-suspend-icon-wrap">
                    <i class="las la-lock"></i>
                </div>
                <p class="wl-suspend-intro">Your withdrawal access has been temporarily suspended by the administrator.</p>
                @if(auth()->user()->withdrawal_block_reason)
                <div class="wl-suspend-reason">
                    <span class="wl-suspend-reason-label">Reason</span>
                    <p class="wl-suspend-reason-text">{{ auth()->user()->withdrawal_block_reason }}</p>
                </div>
                @endif
                <p class="wl-suspend-note">Please contact <strong>support</strong> to resolve this.</p>
            </div>
            <div class="modal-footer wl-suspend-foot">
                <button type="button" class="btn wl-suspend-close-btn" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Detail Modal --}}
<div class="wl-modal-overlay" id="wlModalOverlay">
    <div class="wl-modal">
        <div class="wl-modal-head">
            <span class="wl-modal-title"><i class="las la-receipt"></i> Withdrawal Details</span>
            <button class="wl-modal-close" id="wlModalClose"><i class="las la-times"></i></button>
        </div>
        <div class="wl-modal-body">
            <ul class="wl-detail-list" id="wlDetailList"></ul>
            <div id="wlAdminFeedback"></div>
        </div>
    </div>
</div>

@endsection

@push('style')
<style>
</style>
@endpush

@push('script')
<script>
(function($) {
    "use strict";

    @if(auth()->user()->withdrawal_blocked)
    var suspendedModal = new bootstrap.Modal($('#withdrawSuspendedModal')[0], { backdrop: 'static', keyboard: false });
    suspendedModal.show();
    @endif

    // Detail modal
    var $overlay    = $('#wlModalOverlay');
    var $detailList = $('#wlDetailList');
    var $feedbackDiv = $('#wlAdminFeedback');

    $(document).on('click', '.detailBtn', function() {
        var userData = $(this).data('user_data') || [];
        if (typeof userData === 'string') userData = JSON.parse(userData);
        var html = '';
        $.each(userData, function(i, el) {
            if (el.type !== 'file') {
                html += '<li class="wl-detail-item"><span class="wl-di-key">' + el.name + '</span><span class="wl-di-val">' + el.value + '</span></li>';
            } else {
                html += '<li class="wl-detail-item"><span class="wl-di-key">' + el.name + '</span><span class="wl-di-val"><a href="' + el.value + '"><i class="las la-paperclip"></i> Attachment</a></span></li>';
            }
        });
        $detailList.html(html);

        var feedback = $(this).data('admin_feedback');
        $feedbackDiv.html(feedback
            ? '<div class="wl-feedback"><strong>Admin Feedback</strong><p>' + feedback + '</p></div>'
            : '');

        $overlay.addClass('active');
    });

    $('#wlModalClose').on('click', function() {
        $overlay.removeClass('active');
    });
    $overlay.on('click', function(e) {
        if ($(e.target).is($overlay)) $overlay.removeClass('active');
    });

})(jQuery);
</script>
@endpush

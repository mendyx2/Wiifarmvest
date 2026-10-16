@extends($activeTemplate . 'layouts.master')
@section('content')

<div class="dph-page">

    {{-- ── Header ── --}}
    <div class="dph-header-card">
        <div class="dph-header-left">
            <div class="dph-header-icon"><i class="las la-history"></i></div>
            <div>
                <h6 class="dph-header-title">Deposit History</h6>
                <p class="dph-header-sub">All your funding transactions in one place</p>
            </div>
        </div>
        <a href="{{ route('user.deposit.index') }}" class="dph-deposit-btn">
            <i class="las la-plus"></i> Deposit Now
        </a>
    </div>

    {{-- ── Search bar ── --}}
    <div class="dph-search-card">
        <form class="dph-search-form">
            <div class="dph-search-wrap">
                <i class="las la-search"></i>
                <input class="dph-search-input" name="search" type="search"
                       value="{{ request()->search }}" placeholder="Search by transaction ID or gateway...">
            </div>
            <button class="dph-search-btn" type="submit">Search</button>
        </form>
    </div>

    {{-- ── Deposit rows ── --}}
    <div class="dph-list-card">
        <div class="dph-list-head">
            <span>Gateway / Transaction</span>
            <span class="dph-col-hide">Date</span>
            <span>Amount</span>
            <span class="dph-col-hide">Conversion</span>
            <span>Status</span>
            <span>Info</span>
        </div>

        @forelse($deposits as $deposit)
        @php
            $details = [];
            if ($deposit->method_code >= 1000 && $deposit->method_code <= 5000) {
                foreach (@$deposit->detail ?? [] as $key => $info) {
                    $details[] = $info;
                    if (@$info->type == 'file' && @$info->value) {
                        $details[$key]->value = route('user.download.attachment', encrypt(getFilePath('verify') . '/' . @$info->value));
                    }
                }
            }
        @endphp
        <div class="dph-row">

            {{-- Gateway / TRX --}}
            <div class="dph-cell dph-cell--gateway">
                <div class="dph-gw-icon">
                    <i class="las la-university"></i>
                </div>
                <div>
                    <p class="dph-gw-name">
                        @if($deposit->method_code < 5000)
                            {{ @$deposit->gateway->name }}
                        @else
                            Google Pay
                        @endif
                    </p>
                    <p class="dph-trx">{{ $deposit->trx }}</p>
                    {{-- Date shown on mobile only --}}
                    <p class="dph-date-mobile">
                        <i class="las la-calendar"></i>
                        {{ showDateTime($deposit->created_at) }} &bull; {{ diffForHumans($deposit->created_at) }}
                    </p>
                </div>
            </div>

            {{-- Date (desktop) --}}
            <div class="dph-cell dph-col-hide dph-cell--date">
                <p class="dph-date-main">{{ showDateTime($deposit->created_at) }}</p>
                <p class="dph-date-ago">{{ diffForHumans($deposit->created_at) }}</p>
            </div>

            {{-- Amount --}}
            <div class="dph-cell dph-cell--amount">
                <p class="dph-amount">{{ showAmount($deposit->amount + $deposit->charge) }}</p>
                <p class="dph-charge" data-bs-toggle="tooltip" title="@lang('Processing Charge')">
                    {{ showAmount($deposit->amount) }} + <span class="dph-charge-val">{{ showAmount($deposit->charge) }}</span>
                </p>
            </div>

            {{-- Conversion (desktop) --}}
            <div class="dph-cell dph-col-hide dph-cell--conv">
                <p class="dph-conv-rate">{{ showAmount(1) }} = {{ showAmount($deposit->rate, currencyFormat: false) }} {{ $deposit->method_currency }}</p>
                <p class="dph-conv-final"><strong>{{ showAmount($deposit->final_amount, currencyFormat: false) }} {{ $deposit->method_currency }}</strong></p>
            </div>

            {{-- Status --}}
            <div class="dph-cell dph-cell--status">
                @php echo $deposit->statusBadge @endphp
            </div>

            {{-- Details --}}
            <div class="dph-cell dph-cell--action">
                @if($deposit->method_code >= 1000 && $deposit->method_code <= 5000)
                    <button class="dph-detail-btn detailBtn"
                            data-info="{{ json_encode($details) }}"
                            @if($deposit->status == Status::PAYMENT_REJECT)
                                data-admin_feedback="{{ $deposit->admin_feedback }}"
                            @endif>
                        <i class="las la-eye"></i>
                    </button>
                @else
                    <span class="dph-auto-badge" data-bs-toggle="tooltip" title="@lang('Automatically processed')">
                        <i class="las la-check-circle"></i>
                    </span>
                @endif
            </div>

        </div>
        @empty
        <div class="dph-empty">
            <div class="dph-empty-icon"><i class="las la-inbox"></i></div>
            <p>{{ __($emptyMessage) }}</p>
            <a href="{{ route('user.deposit.index') }}" class="dph-deposit-btn">
                <i class="las la-plus"></i> Make Your First Deposit
            </a>
        </div>
        @endforelse
    </div>

    {{-- ── Pagination ── --}}
    @if($deposits->hasPages())
    <div class="dph-pagination">
        {{ paginateLinks($deposits) }}
    </div>
    @endif

</div>

@push('modal')
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pd-modal-content">
            <div class="pd-modal-header">
                <div class="pd-modal-icon"><i class="las la-receipt"></i></div>
                <h6 class="pd-modal-title">@lang('Transaction Details')</h6>
                <button class="pd-modal-close" data-bs-dismiss="modal" type="button">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="pd-modal-body">
                <ul class="dph-modal-list userData"></ul>
                <div class="feedback"></div>
            </div>
            <div class="pd-modal-footer">
                <button class="pd-modal-btn-cancel" data-bs-dismiss="modal" type="button">Close</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('script')
<script>
(function($) {
    "use strict";

    $('.detailBtn').on('click', function() {
        var modal    = $('#detailModal');
        var userData = $(this).data('info');
        var html     = '';

        if (userData) {
            userData.forEach(function(el) {
                if (el.type !== 'file') {
                    html += `<li class="dph-modal-item"><span class="dph-modal-key">${el.name}</span><span class="dph-modal-val">${el.value}</span></li>`;
                } else {
                    html += `<li class="dph-modal-item"><span class="dph-modal-key">${el.name}</span><a class="dph-modal-link" href="${el.value}"><i class="las la-file"></i> @lang('Attachment')</a></li>`;
                }
            });
        }

        modal.find('.userData').html(html);

        var adminFeedback = '';
        if ($(this).data('admin_feedback') !== undefined) {
            adminFeedback = `<div class="dph-feedback"><strong>@lang('Admin Feedback')</strong><p>${$(this).data('admin_feedback')}</p></div>`;
        }
        modal.find('.feedback').html(adminFeedback);
        modal.modal('show');
    });

    var tooltipEls = [].slice.call(document.querySelectorAll('[title], [data-title], [data-bs-title]'));
    tooltipEls.map(function(el) { return new bootstrap.Tooltip(el); });

})(jQuery);
</script>
@endpush

@endsection

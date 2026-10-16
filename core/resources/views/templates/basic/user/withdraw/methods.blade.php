@extends($activeTemplate . 'layouts.master')
@section('content')

<div class="wd-page">

    @php $user = auth()->user(); @endphp

    @if($user->withdrawal_blocked)
    {{-- Blocked notice --}}
    <div class="wd-blocked-wrap">
        <div class="wd-blocked-icon"><i class="las la-ban"></i></div>
        <h4 class="wd-blocked-title">Withdrawal Suspended</h4>
        <p class="wd-blocked-msg">Your ability to request a withdrawal has been temporarily suspended by the administrator.</p>
        @if($user->withdrawal_block_reason)
        <div class="wd-blocked-reason">
            <span class="wd-blocked-reason-label">Reason:</span>
            {{ $user->withdrawal_block_reason }}
        </div>
        @endif
        <p class="wd-blocked-support">Please contact <strong>support</strong> if you believe this is an error.</p>
    </div>
    @else

    {{-- Hero banner --}}
    <div class="wd-hero">
        <span class="wd-hero-blob wd-blob-1"></span>
        <span class="wd-hero-blob wd-blob-2"></span>
        <div class="wd-hero-body">
            <div class="wd-hero-icon"><i class="las la-arrow-circle-up"></i></div>
            <div>
                <h4 class="wd-hero-title">Withdraw Funds</h4>
                <p class="wd-hero-sub">Available balance: <strong>{{ gs('cur_sym') }}{{ number_format(auth()->user()->balance, 2) }}</strong></p>
            </div>
        </div>
        <a href="{{ route('user.withdraw.history') }}" class="wd-history-link">
            <i class="las la-history"></i> History
        </a>
    </div>

    <form action="{{ route('user.withdraw.money') }}" method="post" class="withdraw-form wd-form">
        @csrf
        <div class="wd-layout">

            {{-- LEFT: Gateway selector --}}
            <div class="wd-col-left">
                <div class="wd-section-label">
                    <i class="las la-wallet"></i> Select Withdrawal Method
                </div>

                {{-- Bank Details Card --}}
                @php $user = auth()->user(); @endphp
                <div class="wd-bank-card">
                    <div class="wd-bank-header">
                        <span class="wd-bank-label"><i class="las la-university"></i> Payout Account</span>
                        <a href="{{ route('user.profile.setting') }}" class="wd-bank-edit"><i class="las la-pen"></i> Edit</a>
                    </div>
                    <div class="wd-bank-body">
                        <div class="wd-bank-row">
                            <span class="wd-bank-key">Bank</span>
                            <span class="wd-bank-val">{{ $user->bname }}</span>
                        </div>
                        <div class="wd-bank-row">
                            <span class="wd-bank-key">Account Name</span>
                            <span class="wd-bank-val">{{ $user->aname }}</span>
                        </div>
                        <div class="wd-bank-row">
                            <span class="wd-bank-key">Account No.</span>
                            <span class="wd-bank-val wd-bank-acno">{{ $user->ano }}</span>
                        </div>
                    </div>
                </div>

                <div class="wd-gw-list payment-system-list is-scrollable gateway-option-list">
                    @foreach($withdrawMethod as $data)
                    <label for="{{ titleToKey($data->name) }}"
                           class="wd-gw-item @if($loop->index > 4) d-none @endif gateway-option">
                        <div class="wd-gw-check payment-item__check"></div>
                        <div class="wd-gw-thumb">
                            <img src="{{ getImage(getFilePath('withdrawMethod') . '/' . $data->image) }}" alt="{{ $data->name }}">
                        </div>
                        <span class="wd-gw-name">{{ __($data->name) }}</span>
                        <input class="payment-item__radio gateway-input" id="{{ titleToKey($data->name) }}" hidden
                            data-gateway='@json($data)' type="radio" name="method_code" value="{{ $data->id }}"
                            @if(old('method_code')) @checked(old('method_code') == $data->id) @else @checked($loop->first) @endif
                            data-min-amount="{{ showAmount($data->min_limit) }}"
                            data-max-amount="{{ showAmount($data->max_limit) }}">
                    </label>
                    @endforeach
                    @if($withdrawMethod->count() > 4)
                    <button type="button" class="wd-show-more more-gateway-option">
                        <i class="las la-chevron-down"></i> Show All Methods
                    </button>
                    @endif
                </div>
            </div>

            {{-- RIGHT: Amount + summary --}}
            <div class="wd-col-right">
                <div class="wd-section-label">
                    <i class="las la-coins"></i> Enter Amount
                </div>

                <div class="wd-amount-card">
                    {{-- Amount input --}}
                    <div class="wd-input-wrap">
                        <span class="wd-cur-sym">{{ gs('cur_sym') }}</span>
                        <input type="text" class="wd-amount-input amount" name="amount"
                               placeholder="0.00" value="{{ old('amount') }}" autocomplete="off">
                        <span class="wd-cur-text">{{ __(gs('cur_text')) }}</span>
                    </div>

                    {{-- Summary rows --}}
                    <div class="wd-summary">
                        <div class="wd-sum-row">
                            <span class="wd-sum-label"><i class="las la-arrows-alt-h"></i> Limit</span>
                            <span class="wd-sum-val gateway-limit">—</span>
                        </div>
                        <div class="wd-sum-row">
                            <span class="wd-sum-label">
                                <i class="las la-percentage"></i> Processing Fee
                                <span class="proccessing-fee-info" data-bs-toggle="tooltip" title="">
                                    <i class="las la-info-circle"></i>
                                </span>
                            </span>
                            <span class="wd-sum-val wd-sum-charge">{{ gs('cur_sym') }}<span class="processing-fee">0.00</span> {{ __(gs('cur_text')) }}</span>
                        </div>
                        <div class="wd-sum-row wd-sum-total">
                            <span class="wd-sum-label"><i class="las la-hand-holding-usd"></i> You Receive</span>
                            <span class="wd-sum-val wd-sum-net">{{ gs('cur_sym') }}<span class="final-amount">0.00</span> {{ __(gs('cur_text')) }}</span>
                        </div>
                        <div class="wd-sum-row gateway-conversion d-none">
                            <span class="wd-sum-label"><i class="las la-exchange-alt"></i> Conversion</span>
                            <span class="wd-sum-val"></span>
                        </div>
                        <div class="wd-sum-row conversion-currency d-none">
                            <span class="wd-sum-label">In <span class="gateway-currency"></span></span>
                            <span class="wd-sum-val"><span class="in-currency"></span></span>
                        </div>
                    </div>

                    <button type="submit" class="wd-submit-btn" disabled>
                        <i class="las la-paper-plane"></i> Confirm Withdrawal
                    </button>

                    <p class="wd-info-text">
                        <i class="las la-shield-alt"></i>
                        Safely withdraw your funds using our highly secure process.
                    </p>
                </div>
            </div>

        </div>
    </form>
</div>
    @endif {{-- end withdrawal_blocked check --}}

@endsection

@push('style')
<style>
/* ── Withdrawal blocked notice ───────────────────────────── */
.wd-blocked-wrap {
    max-width: 520px;
    margin: 60px auto;
    background: #fff8f8;
    border: 1.5px solid #f5c6cb;
    border-radius: 16px;
    padding: 40px 32px;
    text-align: center;
    box-shadow: 0 4px 24px rgba(220,53,69,0.08);
}
.wd-blocked-icon {
    font-size: 3.5rem;
    color: #dc3545;
    margin-bottom: 12px;
    line-height: 1;
}
.wd-blocked-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #a71d2a;
    margin-bottom: 10px;
}
.wd-blocked-msg {
    color: #555;
    font-size: 0.95rem;
    margin-bottom: 16px;
}
.wd-blocked-reason {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 10px 16px;
    font-size: 0.9rem;
    color: #664d03;
    margin-bottom: 16px;
    text-align: left;
}
.wd-blocked-reason-label {
    font-weight: 700;
    margin-right: 6px;
}
.wd-blocked-support {
    font-size: 0.85rem;
    color: #888;
}
.wd-bank-card {
    background: #fff;
    border-radius: 14px;
    border: 1.5px solid #e8eaf0;
    overflow: hidden;
    margin-bottom: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}
.wd-bank-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    background: linear-gradient(90deg, #f0f4ff, #f7f9ff);
    border-bottom: 1px solid #e8eaf0;
}
.wd-bank-label {
    font-size: 0.82rem;
    font-weight: 700;
    color: #4a5568;
    letter-spacing: 0.4px;
    text-transform: uppercase;
}
.wd-bank-label i { margin-right: 5px; color: #4bb94d; }
.wd-bank-edit {
    font-size: 0.78rem;
    font-weight: 600;
    color: #0f521a;
    text-decoration: none;
    display: flex;  xx
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    border: 1px solid #125c25;
    transition: background 0.2s, color 0.2s;
}
.wd-bank-edit:hover { background: #153d14; color: #fff; }
.wd-bank-body { padding: 12px 16px; display: flex; flex-direction: column; gap: 8px; }
.wd-bank-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
}
.wd-bank-key {
    font-size: 0.8rem;
    color: #000;
    font-weight: 500;
    flex-shrink: 0;
}
.wd-bank-val {
    font-size: 0.87rem;
    font-weight: 600;
    color: #2d3748;
    text-align: right;
}
.wd-bank-acno {
    font-family: monospace;
    letter-spacing: 1px;
    color: #37d383;
}
</style>
@endpush

@push('script')
<script>
    "use strict";
    (function($) {
        var amount = parseFloat($('.amount').val() || 0);
        var gateway, minAmount, maxAmount;

        $('.amount').on('input', function() {
            amount = parseFloat($(this).val()) || 0;
            calculation();
        });

        $('.gateway-input').on('change', function() {
            gatewayChange();
        });

        function gatewayChange() {
            let gatewayElement = $('.gateway-input:checked');
            gateway    = gatewayElement.data('gateway');
            minAmount  = gatewayElement.data('min-amount');
            maxAmount  = gatewayElement.data('max-amount');

            let processingFeeInfo = `${parseFloat(gateway.percent_charge).toFixed(2)}% with ${parseFloat(gateway.fixed_charge).toFixed(2)} {{ __(gs('cur_text')) }} charge for processing fees`;
            $(".proccessing-fee-info").attr("data-bs-original-title", processingFeeInfo);
            calculation();
        }

        gatewayChange();

        $(".more-gateway-option").on("click", function() {
            let paymentList = $(".gateway-option-list");
            paymentList.find(".gateway-option").removeClass("d-none");
            $(this).addClass('d-none');
            paymentList.animate({ scrollTop: (paymentList.height() - 60) }, 'slow');
        });

        function calculation() {
            if (!gateway) return;
            $(".gateway-limit").text(minAmount + " – " + maxAmount);
            let percentCharge     = amount ? parseFloat(gateway.percent_charge) : 0;
            let fixedCharge       = amount ? parseFloat(gateway.fixed_charge)   : 0;
            let totalPercentCharge = parseFloat(amount / 100 * percentCharge);
            let totalCharge       = parseFloat(totalPercentCharge + fixedCharge);
            let totalAmount       = parseFloat((amount || 0) - totalPercentCharge - fixedCharge);

            $(".final-amount").text(totalAmount.toFixed(2));
            $(".processing-fee").text(totalCharge.toFixed(2));
            $("input[name=currency]").val(gateway.currency);
            $(".gateway-currency").text(gateway.currency);

            if (amount < Number(gateway.min_limit) || amount > Number(gateway.max_limit)) {
                $(".withdraw-form button[type=submit]").attr('disabled', true);
            } else {
                $(".withdraw-form button[type=submit]").removeAttr('disabled');
            }

            if (gateway.currency != "{{ gs('cur_text') }}") {
                $('.withdraw-form').addClass('adjust-height');
                $(".gateway-conversion, .conversion-currency").removeClass('d-none');
                $(".gateway-conversion").find('.wd-sum-val').html(
                    `1 {{ __(gs('cur_text')) }} = <span class="rate">${parseFloat(gateway.rate).toFixed(2)}</span> <span class="method_currency">${gateway.currency}</span>`
                );
                $('.in-currency').text(parseFloat(totalAmount * gateway.rate).toFixed(2));
            } else {
                $(".gateway-conversion, .conversion-currency").addClass('d-none');
                $('.withdraw-form').removeClass('adjust-height');
            }
        }

        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(el) { return new bootstrap.Tooltip(el); });

        $('.gateway-input').change();
    })(jQuery);
</script>
@endpush

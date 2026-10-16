@extends($activeTemplate . 'layouts.master')
@section('content')

<div class="dep-page">

    {{-- ── Top bar ── --}}
    <div class="dep-topbar">
        <div class="dep-topbar-left">
            <div class="dep-topbar-icon"><i class="las la-wallet"></i></div>
            <div>
                <h6 class="dep-topbar-title">Fund Your Wallet</h6>
                <p class="dep-topbar-sub">Fast, secure deposits to your account</p>
            </div>
        </div>
        <a href="{{ route('user.deposit.history') }}" class="dep-hist-btn">
            <i class="las la-list-alt"></i> History
        </a>
    </div>

    {{-- ── Hero banner ── --}}
    <div class="dep-hero">
        <div class="dep-hero-bg"></div>
        <div class="dep-hero-content">
            <div class="dep-hero-badge"><i class="las la-shield-alt"></i> 100% Secure</div>
            <h3 class="dep-hero-heading">Credit Money Box</h3>
            <!-- <p class="dep-hero-sub">Choose a payment method and enter the amount to fund your account instantly.</p> -->
            <div class="dep-hero-pills">
                <span class="dep-hero-pill"><i class="las la-bolt"></i> Instant</span>
                <span class="dep-hero-pill"><i class="las la-lock"></i> Encrypted</span>
                <span class="dep-hero-pill"><i class="las la-headset"></i> 24/7 Support</span>
            </div>
        </div>
    </div>

    {{-- ── Form ── --}}
    <form action="{{ route('user.deposit.insert') }}" method="post" class="deposit-form">
        @csrf
        <input type="hidden" name="currency">

        <div class="dep-grid">

            {{-- ── Left: Gateway selection ── --}}
            <div class="dep-card dep-gw-card">
                <div class="dep-card-head">
                    <div class="dep-card-head-icon gw"><i class="las la-credit-card"></i></div>
                    <div>
                        <h6 class="dep-card-title">Payment Method</h6>
                        <p class="dep-card-sub">Select how you'd like to pay</p>
                    </div>
                </div>

                {{-- View Account Detail Button --}}
                <button type="button" class="dep-acct-btn" onclick="openBankModal()">
                    <span class="dep-acct-btn-inner">
                        <i class="las la-university"></i>
                        <span>Click to View Account Details</span>
                    </span>
                    <i class="las la-chevron-right dep-acct-btn-arrow"></i>
                </button>

                <div class="dep-gw-list payment-system-list is-scrollable gateway-option-list">
                    @foreach($gatewayCurrency as $data)
                    <label for="{{ titleToKey($data->name) }}"
                           class="dep-gw-item @if($loop->index > 4) d-none @endif gateway-option">
                        <div class="dep-gw-item-left">
                            <div class="dep-gw-radio-wrap">
                                <span class="dep-gw-radio payment-item__check"></span>
                            </div>
                            <span class="dep-gw-name">{{ __($data->name) }}</span>
                        </div>
                        <div class="dep-gw-logo">
                            <img src="{{ getImage(getFilePath('gateway') . '/' . $data->method->image) }}" alt="{{ $data->name }}">
                        </div>
                        <input class="payment-item__radio gateway-input" id="{{ titleToKey($data->name) }}"
                               hidden
                               data-gateway='@json($data)'
                               type="radio"
                               name="gateway"
                               value="{{ $data->method_code }}"
                               @if(old('gateway')) @checked(old('gateway') == $data->method_code) @else @checked($loop->first) @endif
                               data-min-amount="{{ showAmount($data->min_amount) }}"
                               data-max-amount="{{ showAmount($data->max_amount) }}">
                    </label>
                    @endforeach

                    @if($gatewayCurrency->count() > 4)
                    <button type="button" class="dep-more-btn more-gateway-option">
                        <i class="las la-th-list"></i>
                        <span class="payment-item__btn-text">Show All Payment Options</span>
                        <i class="las la-chevron-down"></i>
                    </button>
                    @endif
                </div>

                {{-- Crypto notice --}}
                <div class="dep-crypto-msg crypto-message d-none">
                    <i class="las la-info-circle"></i>
                    Conversion with <strong><span class="gateway-currency"></span></strong> — final value shown on next step.
                </div>
            </div>

{{-- Company Bank Details Modal --}}
<div id="bankDetailModal" class="bdm-overlay" onclick="if(event.target===this)closeBankModal()">
    <div class="bdm-box">
        <div class="bdm-header">
            <div class="bdm-header-icon"><i class="las la-university"></i></div>
            <div>
                <h5 class="bdm-title">Company Bank Details</h5>
                <p class="bdm-subtitle">Transfer funds to the account below</p>
            </div>
            <button type="button" class="bdm-close" onclick="closeBankModal()"><i class="las la-times"></i></button>
        </div>

        <div class="bdm-body">
            <div class="bdm-field">
                <span class="bdm-field-label"><i class="las la-landmark"></i> Bank Name</span>
                <div class="bdm-field-val-wrap">
                    <span class="bdm-field-val" id="bdm-bank-name">KEYSTONE BANK</span>
                    <button type="button" class="bdm-copy-btn" onclick="bdmCopy('bdm-bank-name', this)" title="Copy">
                        <i class="las la-copy"></i>
                    </button>
                </div>
            </div>

            <div class="bdm-field">
                <span class="bdm-field-label"><i class="las la-user-circle"></i> Account Name</span>
                <div class="bdm-field-val-wrap">
                    <span class="bdm-field-val" id="bdm-acct-name"> WiiFARM Cooperative Society</span>
                    <button type="button" class="bdm-copy-btn" onclick="bdmCopy('bdm-acct-name', this)" title="Copy">
                        <i class="las la-copy"></i>
                    </button>
                </div>
            </div>

            <div class="bdm-field bdm-field-acno">
                <span class="bdm-field-label"><i class="las la-hashtag"></i> Account Number</span>
                <div class="bdm-field-val-wrap">
                    <span class="bdm-field-val bdm-acno" id="bdm-acct-no">1013769731</span>
                    <button type="button" class="bdm-copy-btn" onclick="bdmCopy('bdm-acct-no', this)" title="Copy">
                        <i class="las la-copy"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="bdm-note">
            <i class="las la-info-circle"></i>
            After transfer, submit your deposit and upload proof of payment if required.
        </div>

        <button type="button" class="bdm-done-btn" onclick="closeBankModal()">
            <i class="las la-check-circle"></i> Got it
        </button>
    </div>
</div>

            {{-- ── Right: Amount & summary ── --}}
            <div class="dep-card dep-amount-card">
                <div class="dep-card-head">
                    <div class="dep-card-head-icon amt"><i class="las la-coins"></i></div>
                    <div>
                        <h6 class="dep-card-title">Enter Amount</h6>
                        <p class="dep-card-sub">Type the amount you wish to deposit</p>
                    </div>
                </div>

                {{-- Amount input ── --}}
                <div class="dep-amount-wrap">
                    <span class="dep-cur-sym">{{ gs('cur_sym') }}</span>
                    <input type="text" class="dep-amount-input amount" name="amount"
                           placeholder="0.00" value="{{ old('amount') }}" autocomplete="off">
                    <span class="dep-cur-text">{{ gs('cur_text') }}</span>
                </div>

                {{-- Quick amounts ── --}}
                <div class="dep-quick-amounts">
                    @foreach([500, 1000, 2000, 5000, 10000, 50000] as $qa)
                    <button type="button" class="dep-quick-btn" data-amount="{{ $qa }}">
                        {{ gs('cur_sym') }}{{ number_format($qa) }}
                    </button>
                    @endforeach
                </div>

                {{-- Summary ── --}}
                <div class="dep-summary">

                    <div class="dep-summary-row">
                        <span class="dep-summary-label"><i class="las la-arrows-alt-h"></i> Limit</span>
                        <span class="dep-summary-val gateway-limit">—</span>
                    </div>

                    <div class="dep-summary-row">
                        <span class="dep-summary-label">
                            <i class="las la-percentage"></i> Processing Charge
                            <span class="dep-fee-info proccessing-fee-info" data-bs-toggle="tooltip" title="">
                                <i class="las la-info-circle"></i>
                            </span>
                        </span>
                        <span class="dep-summary-val dep-charge processing-fee">0.00</span>
                    </div>

                    <div class="dep-summary-divider"></div>

                    <div class="dep-summary-row dep-summary-total total-amount">
                        <span class="dep-summary-label">Total</span>
                        <span class="dep-summary-val dep-total-val">
                            <span class="final-amount">0.00</span>
                            <small>{{ gs('cur_text') }}</small>
                        </span>
                    </div>

                    {{-- Conversion rows ── --}}
                    <div class="dep-summary-row gateway-conversion d-none total-amount">
                        <span class="dep-summary-label"><i class="las la-exchange-alt"></i> Rate</span>
                        <span class="dep-summary-val"></span>
                    </div>
                    <div class="dep-summary-row conversion-currency d-none total-amount">
                        <span class="dep-summary-label">In <span class="gateway-currency"></span></span>
                        <span class="dep-summary-val dep-conv-val"><span class="in-currency"></span></span>
                    </div>

                </div>

                <button type="submit" class="dep-submit-btn" disabled>
                    <i class="las la-lock"></i>
                    Confirm Deposit
                </button>

                <p class="dep-secure-note">
                    <i class="las la-shield-alt"></i>
                    Ensuring your funds grow safely through our secure deposit process with world-class payment options.
                </p>
            </div>

        </div>
    </form>

    {{-- ── Trust strip ── 
    <div class="dep-trust-strip">
        <div class="dep-trust-item">
            <div class="dep-trust-icon t1"><i class="las la-lock"></i></div>
            <div>
                <p class="dep-trust-label">SSL Encrypted</p>
                <p class="dep-trust-sub">256-bit security</p>
            </div>
        </div>
        <div class="dep-trust-item">
            <div class="dep-trust-icon t2"><i class="las la-bolt"></i></div>
            <div>
                <p class="dep-trust-label">Instant Credit</p>
                <p class="dep-trust-sub">Funds in seconds</p>
            </div>
        </div>
        <div class="dep-trust-item">
            <div class="dep-trust-icon t3"><i class="las la-headset"></i></div>
            <div>
                <p class="dep-trust-label">24/7 Support</p>
                <p class="dep-trust-sub">Always here for you</p>
            </div>
        </div>
        <div class="dep-trust-item">
            <div class="dep-trust-icon t4"><i class="las la-undo"></i></div>
            <div>
                <p class="dep-trust-label">Easy Refunds</p>
                <p class="dep-trust-sub">Hassle-free process</p>
            </div>
        </div>
    </div>
    --}}

</div>

@push('script')
<script>
"use strict";

function openBankModal() {
    document.getElementById('bankDetailModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeBankModal() {
    var m = document.getElementById('bankDetailModal');
    m.classList.remove('open');
    document.body.style.overflow = '';
}
function bdmCopy(id, btn) {
    var text = document.getElementById(id).innerText;
    navigator.clipboard.writeText(text).then(function() {
        btn.classList.add('copied');
        btn.innerHTML = '<i class="las la-check"></i>';
        setTimeout(function() {
            btn.classList.remove('copied');
            btn.innerHTML = '<i class="las la-copy"></i>';
        }, 1800);
    });
}

(function($) {
    var amount = parseFloat($('.amount').val() || 0);
    var gateway, minAmount, maxAmount;

    $('.amount').on('input', function() {
        amount = parseFloat($(this).val()) || 0;
        calculation();
    });

    $('.gateway-input').on('change', function() { gatewayChange(); });

    // Quick amount buttons
    $('.dep-quick-btn').on('click', function() {
        var val = $(this).data('amount');
        $('.amount').val(val);
        amount = val;
        $('.dep-quick-btn').removeClass('active');
        $(this).addClass('active');
        calculation();
    });

    function gatewayChange() {
        let el = $('.gateway-input:checked');
        gateway   = el.data('gateway');
        minAmount = el.data('min-amount');
        maxAmount = el.data('max-amount');
        let feeInfo = `${parseFloat(gateway.percent_charge).toFixed(2)}% + ${parseFloat(gateway.fixed_charge).toFixed(2)} {{ __(gs('cur_text')) }} gateway fee`;
        $(".proccessing-fee-info").attr("data-bs-original-title", feeInfo).attr("title", feeInfo);
        // Active highlight
        $('.dep-gw-item').removeClass('selected');
        el.closest('.dep-gw-item').addClass('selected');
        calculation();
    }

    gatewayChange();

    $(".more-gateway-option").on("click", function() {
        $(".gateway-option-list .gateway-option").removeClass("d-none");
        $(this).addClass('d-none');
    });

    function calculation() {
        if (!gateway) return;
        $(".gateway-limit").text(minAmount + " – " + maxAmount);
        let pct = amount ? parseFloat(amount / 100 * parseFloat(gateway.percent_charge)) : 0;
        let fixed = parseFloat(gateway.fixed_charge);
        let charge = parseFloat(pct + fixed);
        let total  = parseFloat((amount || 0) + charge);
        $(".final-amount").text(total.toFixed(2));
        $(".processing-fee").text(charge.toFixed(2));
        $("input[name=currency]").val(gateway.currency);
        $(".gateway-currency").text(gateway.currency);

        if (amount < Number(gateway.min_amount) || amount > Number(gateway.max_amount)) {
            $(".deposit-form button[type=submit]").attr('disabled', true);
        } else {
            $(".deposit-form button[type=submit]").removeAttr('disabled');
        }

        if (gateway.currency != "{{ gs('cur_text') }}" && gateway.method.crypto != 1) {
            $(".gateway-conversion, .conversion-currency").removeClass('d-none');
            $(".gateway-conversion .dep-summary-val").html(`1 {{ gs('cur_text') }} = <strong>${parseFloat(gateway.rate).toFixed(2)} ${gateway.currency}</strong>`);
            $('.in-currency').text(parseFloat(total * gateway.rate).toFixed(gateway.method.crypto == 1 ? 8 : 2));
        } else {
            $(".gateway-conversion, .conversion-currency").addClass('d-none');
        }

        if (gateway.method.crypto == 1) {
            $('.crypto-message').removeClass('d-none');
        } else {
            $('.crypto-message').addClass('d-none');
        }
    }

    var tips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tips.map(function(el) { return new bootstrap.Tooltip(el); });
    $('.gateway-input').change();

})(jQuery);
</script>
@endpush

@endsection

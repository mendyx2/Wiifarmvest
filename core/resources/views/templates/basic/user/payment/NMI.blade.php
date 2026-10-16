@extends($activeTemplate.'layouts.master')
@section('content')

<div class="gco-page">
    <div class="gco-layout">

        {{-- Summary --}}
        <div class="gco-summary-col">
            <div class="gco-summary-card">
                <div class="gco-summary-top">
                    <div class="gco-gw-badge nmi"><i class="las la-university"></i></div>
                    <div>
                        <p class="gco-gw-name">NMI</p>
                        <p class="gco-gw-sub">Secure card payment</p>
                    </div>
                </div>
                <div class="gco-amount-display">
                    <p class="gco-amount-label">You will pay</p>
                    <p class="gco-amount-val">{{ showAmount($deposit->final_amount, currencyFormat:false) }} <span>{{ __($deposit->method_currency) }}</span></p>
                </div>
                <div class="gco-receive-row">
                    <i class="las la-arrow-down"></i>
                    <div>
                        <p class="gco-receive-label">You will receive</p>
                        <p class="gco-receive-val">{{ showAmount($deposit->amount) }}</p>
                    </div>
                </div>
                <div class="gco-steps">
                    <div class="gco-step"><div class="gco-step-dot done"><i class="las la-check"></i></div><span>Amount</span></div>
                    <div class="gco-step-line"></div>
                    <div class="gco-step"><div class="gco-step-dot done"><i class="las la-check"></i></div><span>Gateway</span></div>
                    <div class="gco-step-line"></div>
                    <div class="gco-step"><div class="gco-step-dot current">3</div><span>Pay</span></div>
                </div>
            </div>
            <div class="gco-secure-card">
                <div class="gco-secure-item"><i class="las la-lock"></i> SSL Encrypted</div>
                <div class="gco-secure-item"><i class="las la-shield-alt"></i> PCI Compliant</div>
                <div class="gco-secure-item"><i class="las la-undo"></i> Safe & Secure</div>
            </div>
        </div>

        {{-- Form --}}
        <div class="gco-action-col">
            <div class="gco-action-card">
                <div class="gco-action-head">
                    <div class="gco-action-icon nmi"><i class="fas fa-credit-card"></i></div>
                    <div>
                        <h6 class="gco-action-title">Card Details</h6>
                        <p class="gco-action-sub">Enter your card information</p>
                    </div>
                </div>

                <form role="form" class="disableSubmission appPayment gco-form" id="payment-form"
                      method="{{ $data->method }}" action="{{ $data->url }}">
                    @csrf
                    <div class="gco-fields">
                        <div class="gco-field-group full">
                            <label class="gco-label">Card Number</label>
                            <div class="gco-input-wrap">
                                <i class="las la-credit-card"></i>
                                <input class="gco-input" type="tel" name="billing-cc-number" value="{{ old('billing-cc-number') }}" required autocomplete="off" autofocus placeholder="0000 0000 0000 0000">
                            </div>
                        </div>
                        <div class="gco-field-group half">
                            <label class="gco-label">Expiry Date</label>
                            <div class="gco-input-wrap">
                                <i class="las la-calendar"></i>
                                <input class="gco-input" type="tel" name="billing-cc-exp" value="{{ old('billing-cc-exp') }}" placeholder="MM/YY" autocomplete="off" required>
                            </div>
                        </div>
                        <div class="gco-field-group half">
                            <label class="gco-label">CVC Code</label>
                            <div class="gco-input-wrap">
                                <i class="las la-lock"></i>
                                <input class="gco-input" type="tel" name="billing-cc-cvv" value="{{ old('billing-cc-cvv') }}" autocomplete="off" required placeholder="123">
                            </div>
                        </div>
                    </div>
                    <button class="gco-pay-btn" type="submit">
                        <i class="las la-lock"></i>
                        Pay {{ showAmount($deposit->final_amount, currencyFormat:false) }} {{ __($deposit->method_currency) }}
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

@endsection
@if($deposit->from_api)
@push('script')
<script>
(function($) {
    "use strict";
    $('.appPayment').on('submit', function() {
        $(this).find('[type=submit]').html('<i class="las la-spinner la-spin"></i> Processing...');
    });
})(jQuery);
</script>
@endpush
@endif

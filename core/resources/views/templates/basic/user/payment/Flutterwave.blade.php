@extends($activeTemplate.'layouts.master')
@section('content')

<div class="gco-page gco-page--centered">
    <div class="gco-single-card">

        <div class="gco-single-head">
            <div class="gco-gw-badge flw"><i class="las la-bolt"></i></div>
            <div>
                <h6 class="gco-action-title">Flutterwave</h6>
                <p class="gco-action-sub">Secure online payment</p>
            </div>
        </div>

        <div class="gco-single-amounts">
            <div class="gco-single-amt-row">
                <div class="gco-single-amt-box pay">
                    <p class="gco-single-amt-label">You Pay</p>
                    <p class="gco-single-amt-val">{{ showAmount($deposit->final_amount, currencyFormat:false) }}</p>
                    <p class="gco-single-amt-cur">{{ __($deposit->method_currency) }}</p>
                </div>
                <div class="gco-single-arrow"><i class="las la-long-arrow-alt-right"></i></div>
                <div class="gco-single-amt-box get">
                    <p class="gco-single-amt-label">You Receive</p>
                    <p class="gco-single-amt-val">{{ showAmount($deposit->amount) }}</p>
                    <p class="gco-single-amt-cur">{{ gs('cur_text') }}</p>
                </div>
            </div>
        </div>

        <div class="gco-single-steps">
            <div class="gco-step"><div class="gco-step-dot done"><i class="las la-check"></i></div><span>Amount</span></div>
            <div class="gco-step-line"></div>
            <div class="gco-step"><div class="gco-step-dot done"><i class="las la-check"></i></div><span>Gateway</span></div>
            <div class="gco-step-line"></div>
            <div class="gco-step"><div class="gco-step-dot current">3</div><span>Pay</span></div>
        </div>

        <button type="button" class="gco-pay-btn" id="btn-confirm" onclick="payWithRave()">
            <i class="las la-lock"></i>
            Pay Now — {{ showAmount($deposit->final_amount, currencyFormat:false) }} {{ __($deposit->method_currency) }}
        </button>

        <div class="gco-single-secure">
            <span><i class="las la-lock"></i> SSL Encrypted</span>
            <span><i class="las la-shield-alt"></i> PCI Compliant</span>
            <span><i class="las la-headset"></i> 24/7 Support</span>
        </div>
    </div>
</div>

@endsection
@push('script')
<script src="https://api.ravepay.co/flwv3-pug/getpaidx/api/flwpbf-inline.js"></script>
<script>
"use strict";
function payWithRave() {
    var x = getpaidSetup({
        PBFPubKey: "{{ $data->API_publicKey }}",
        customer_email: "{{ $data->customer_email }}",
        amount: "{{ $data->amount }}",
        customer_phone: "{{ $data->customer_phone }}",
        currency: "{{ $data->currency }}",
        txref: "{{ $data->txref }}",
        onclose: function() {},
        callback: function(response) {
            var txref = response.tx.txRef;
            var status = response.tx.status;
            var chargeResponse = response.tx.chargeResponseCode;
            window.location = '{{ url("ipn/flutterwave") }}/' + txref + '/' + status;
        }
    });
}
</script>
@endpush

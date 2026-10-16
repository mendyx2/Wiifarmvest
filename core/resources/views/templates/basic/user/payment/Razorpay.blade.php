@extends($activeTemplate.'layouts.master')
@section('content')

<div class="gco-page gco-page--centered">
    <div class="gco-single-card">

        <div class="gco-single-head">
            <div class="gco-gw-badge rzp"><i class="las la-rupee-sign"></i></div>
            <div>
                <h6 class="gco-action-title">Razorpay</h6>
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

        <form action="{{ $data->url }}" method="{{ $data->method }}" class="gco-single-form">
            <input type="hidden" custom="{{ $data->custom }}" name="hidden">
            <script src="{{ $data->checkout_js }}"
                @foreach($data->val as $key => $value)
                    data-{{ $key }}="{{ $value }}"
                @endforeach>
            </script>
        </form>

        <div class="gco-single-secure">
            <span><i class="las la-lock"></i> SSL Encrypted</span>
            <span><i class="las la-shield-alt"></i> PCI Compliant</span>
            <span><i class="las la-headset"></i> 24/7 Support</span>
        </div>
    </div>
</div>

@endsection
@push('script')
<script>
(function($) {
    "use strict";
    $('input[type="submit"]').addClass("gco-pay-btn").css({ width: '100%', marginTop: '1rem' });
})(jQuery);
</script>
@endpush

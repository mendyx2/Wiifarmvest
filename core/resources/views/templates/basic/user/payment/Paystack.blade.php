@extends($activeTemplate.'layouts.master')
@section('content')

<div class="gco-page gco-page--centered">
    <div class="gco-single-card">

        <div class="gco-single-head">
            <div class="gco-gw-badge pstk"><i class="las la-credit-card"></i></div>
            <div>
                <h6 class="gco-action-title">Paystack</h6>
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

        <form action="{{ route('ipn.'.$deposit->gateway->alias) }}" method="POST" class="gco-single-form" id="depositPaystackForm">
            @csrf
            <input type="hidden" name="reference" id="depositReference">
            <input type="hidden" name="paystack-trxref" id="depositTrxref">
            <button type="button" class="gco-pay-btn" id="btn-confirm">
                <i class="las la-lock"></i>
                Pay Now — {{ showAmount($deposit->final_amount, currencyFormat:false) }} {{ __($deposit->method_currency) }}
            </button>
        </form>

        <script src="//js.paystack.co/v1/inline.js"></script>
        <script>
        document.getElementById('btn-confirm').addEventListener('click', function () {
            var handler = PaystackPop.setup({
                key:      '{{ $data->key }}',
                email:    '{{ $data->email }}',
                amount:   {{ round($data->amount) }},
                currency: '{{ $data->currency }}',
                ref:      '{{ $data->ref }}',
                metadata: {
                    payment_type: 'deposit'
                },
                callback: function (response) {
                    document.getElementById('depositReference').value  = response.reference;
                    document.getElementById('depositTrxref').value     = response.reference;
                    document.getElementById('depositPaystackForm').submit();
                },
                onClose: function () {}
            });
            handler.openIframe();
        });
        </script>

        <div class="gco-single-secure">
            <span><i class="las la-lock"></i> SSL Encrypted</span>
            <span><i class="las la-shield-alt"></i> PCI Compliant</span>
            <span><i class="las la-headset"></i> 24/7 Support</span>
        </div>
    </div>
</div>

@endsection

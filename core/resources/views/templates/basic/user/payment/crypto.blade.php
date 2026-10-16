@extends($activeTemplate.'layouts.master')
@section('content')

<div class="gco-page gco-page--centered">
    <div class="gco-single-card gco-crypto-card">

        <div class="gco-single-head">
            <div class="gco-gw-badge crypto"><i class="las la-coins"></i></div>
            <div>
                <h6 class="gco-action-title">Crypto Payment</h6>
                <p class="gco-action-sub">Send the exact amount to the address below</p>
            </div>
        </div>

        {{-- QR Code --}}
        <div class="gco-qr-wrap">
            <div class="gco-qr-frame">
                <img src="{{ $data->img }}" alt="QR Code">
            </div>
            <p class="gco-qr-hint"><i class="las la-qrcode"></i> Scan to send</p>
        </div>

        {{-- Amount to send --}}
        <div class="gco-crypto-amount-box">
            <p class="gco-crypto-amount-label">Send Exactly</p>
            <p class="gco-crypto-amount-val">{{ $data->amount }}</p>
            <p class="gco-crypto-amount-cur">{{ __($data->currency) }}</p>
        </div>

        {{-- Wallet address --}}
        <div class="gco-wallet-box">
            <p class="gco-wallet-label"><i class="las la-wallet"></i> Wallet Address</p>
            <div class="gco-wallet-addr-wrap">
                <p class="gco-wallet-addr" id="cryptoAddr">{{ $data->sendto }}</p>
                <button class="gco-copy-addr" type="button" onclick="copyAddr()" title="Copy address">
                    <i class="las la-copy"></i>
                </button>
            </div>
            <p class="gco-wallet-warn"><i class="las la-exclamation-triangle"></i> Send only <strong>{{ __($data->currency) }}</strong> to this address. Sending any other coin may result in permanent loss.</p>
        </div>

        <div class="gco-single-secure">
            <span><i class="las la-lock"></i> Encrypted</span>
            <span><i class="las la-shield-alt"></i> Verified</span>
            <span><i class="las la-clock"></i> Awaiting TX</span>
        </div>
    </div>
</div>

@endsection
@push('script')
<script>
function copyAddr() {
    var addr = document.getElementById('cryptoAddr').innerText;
    navigator.clipboard.writeText(addr).then(function() {
        var btn = document.querySelector('.gco-copy-addr');
        btn.innerHTML = '<i class="las la-check"></i>';
        btn.style.color = '#16A34A';
        setTimeout(function() {
            btn.innerHTML = '<i class="las la-copy"></i>';
            btn.style.color = '';
        }, 2000);
    });
}
</script>
@endpush

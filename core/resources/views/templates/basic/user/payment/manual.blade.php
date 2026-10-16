@extends($activeTemplate . 'layouts.master')
@section('content')

<div class="dep-page">

    {{-- ── Top bar ── --}}
    <div class="dep-topbar">
        <div class="dep-topbar-left">
            <div class="dep-topbar-icon man"><i class="las la-file-invoice-dollar"></i></div>
            <div>
                <h6 class="dep-topbar-title">Complete Your Deposit</h6>
                <p class="dep-topbar-sub">Manual payment confirmation</p>
            </div>
        </div>
        <a href="{{ route('user.deposit.history') }}" class="dep-hist-btn">
            <i class="las la-list-alt"></i> History
        </a>
    </div>

    {{-- ── Payment summary banner ── --}}
    <div class="man-banner">
        <div class="man-banner-left">
            <div class="man-banner-label">You are depositing</div>
            <div class="man-banner-amount">{{ showAmount($data['amount']) }}</div>
            <div class="man-banner-sub">Please pay exactly <strong>{{ showAmount($data['final_amount'], currencyFormat: false) }} {{ $data['method_currency'] }}</strong> for successful payment.</div>
        </div>
        <div class="man-banner-icon">
            <i class="las la-paper-plane"></i>
        </div>
    </div>

    {{-- ── Steps ── --}}
    <div class="man-steps">
        <div class="man-step active">
            <div class="man-step-num done"><i class="las la-check"></i></div>
            <span class="man-step-label">Amount</span>
        </div>
        <div class="man-step-line active"></div>
        <div class="man-step active">
            <div class="man-step-num done"><i class="las la-check"></i></div>
            <span class="man-step-label">Gateway</span>
        </div>
        <div class="man-step-line active"></div>
        <div class="man-step active">
            <div class="man-step-num current">3</div>
            <span class="man-step-label">Confirm</span>
        </div>
    </div>

    <div class="man-layout">

        {{-- ── Instructions ── --}}
        <div class="dep-card man-instr-card">
            <div class="dep-card-head">
                <div class="dep-card-head-icon gw"><i class="las la-info-circle"></i></div>
                <div>
                    <h6 class="dep-card-title">Payment Instructions</h6>
                    <p class="dep-card-sub">Follow the steps below carefully</p>
                </div>
            </div>
            <div class="man-instr-body">
                @php echo $data->gateway->description @endphp
            </div>
        </div>

        {{-- ── Form ── --}}
        <div class="dep-card man-form-card">
            <div class="dep-card-head">
                <div class="dep-card-head-icon amt"><i class="las la-upload"></i></div>
                <div>
                    <h6 class="dep-card-title">Submit Proof</h6>
                    <p class="dep-card-sub">Fill in the required details below</p>
                </div>
            </div>

            <form class="disableSubmission man-form" action="{{ route('user.deposit.manual.update') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="man-form-body">
                    <x-viser-form identifier="id" identifierValue="{{ $gateway->form_id }}" />
                </div> 

                <div class="man-form-footer">
                    <div class="man-secure-note">
                        <i class="las la-shield-alt"></i>
                        Your submission is encrypted and secure
                    </div>
                    <button class="dep-submit-btn man-submit-btn" type="submit">
                        <i class="las la-paper-plane"></i>
                        Pay Now — {{ showAmount($data['final_amount'], currencyFormat: false) }} {{ $data['method_currency'] }}
                    </button>
                </div>
            </form>
        </div>

    </div>

    {{-- ── Trust strip ── --}}
    <div class="dep-trust-strip">
        <div class="dep-trust-item">
            <div class="dep-trust-icon t1"><i class="las la-lock"></i></div>
            <div>
                <p class="dep-trust-label">SSL Encrypted</p>
                <p class="dep-trust-sub">256-bit security</p>
            </div>
        </div>
        <div class="dep-trust-item">
            <div class="dep-trust-icon t2"><i class="las la-user-shield"></i></div>
            <div>
                <p class="dep-trust-label">Admin Review</p>
                <p class="dep-trust-sub">Verified manually</p>
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
            <div class="dep-trust-icon t4"><i class="las la-check-circle"></i></div>
            <div>
                <p class="dep-trust-label">Fast Approval</p>
                <p class="dep-trust-sub">Usually within hours</p>
            </div>
        </div>
    </div>

</div>

@endsection

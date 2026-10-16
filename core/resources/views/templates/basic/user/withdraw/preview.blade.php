@extends($activeTemplate.'layouts.master')
@section('content')

<div class="wp-page">

    {{-- Info banner --}}
    <div class="wp-banner">
        <div class="wp-banner-icon"><i class="las la-info-circle"></i></div>
        <p class="wp-banner-text">
            You are requesting <strong>{{ showAmount($withdraw->amount) }}</strong> for withdrawal.
            Admin will send you <strong class="wp-banner-net">{{ showAmount($withdraw->final_amount, currencyFormat:false) }} {{ $withdraw->currency }}</strong> to your account.
        </p>
    </div>

    <div class="wp-layout">

        {{-- Summary card --}}
        <div class="wp-summary-card">
            <div class="wp-sum-head">
                <div class="wp-sum-icon"><i class="las la-arrow-circle-up"></i></div>
                <div>
                    <h6 class="wp-sum-title">Withdrawal Summary</h6>
                    <p class="wp-sum-via">via {{ $withdraw->method->name }}</p>
                </div>
            </div>
            <div class="wp-sum-rows">
                <div class="wp-sum-row">
                    <span>Requested Amount</span>
                    <strong>{{ showAmount($withdraw->amount) }}</strong>
                </div>
                <div class="wp-sum-row wp-charge-row">
                    <span>Processing Charge</span>
                    <strong>− {{ showAmount($withdraw->charge) }}</strong>
                </div>
                <div class="wp-sum-divider"></div>
                <div class="wp-sum-row wp-sum-net">
                    <span>You Receive</span>
                    <strong>{{ showAmount($withdraw->amount - $withdraw->charge) }}</strong>
                </div>
                <div class="wp-sum-row">
                    <span>In {{ $withdraw->currency }}</span>
                    <strong>{{ showAmount($withdraw->final_amount, currencyFormat:false) }} {{ $withdraw->currency }}</strong>
                </div>
            </div>
            <div class="wp-sum-steps">
                <div class="wp-step wp-step-done"><span class="wp-step-dot"><i class="las la-check"></i></span><span>Amount</span></div>
                <div class="wp-step-line"></div>
                <div class="wp-step wp-step-done"><span class="wp-step-dot"><i class="las la-check"></i></span><span>Method</span></div>
                <div class="wp-step-line"></div>
                <div class="wp-step wp-step-current"><span class="wp-step-dot">3</span><span>Confirm</span></div>
            </div>
        </div>

        {{-- Form card --}}
        <div class="wp-form-card">
            <div class="wp-form-head">
                <h6 class="wp-form-title">Provide Withdrawal Details</h6>
                <p class="wp-form-sub">Fill in your account information below</p>
            </div>

            @if($withdraw->method->description)
            <div class="wp-method-desc">
                @php echo $withdraw->method->description; @endphp
            </div>
            @endif

            <form action="{{ route('user.withdraw.submit') }}" class="disableSubmission" method="post" enctype="multipart/form-data">
                @csrf
                <x-viser-form identifier="id" identifierValue="{{ $withdraw->method->form_id }}" />

                @if(auth()->user()->ts)
                <div class="form--group mt-3">
                    <label class="form--label">Google Authenticator Code</label>
                    <input type="text" name="authenticator_code" class="form-control form--control" required>
                </div>
                @endif

                <button type="submit" class="wp-submit-btn">
                    <i class="las la-paper-plane"></i> Submit Withdrawal Request
                </button>
            </form>
        </div>

    </div>
</div>

@endsection

@extends($activeTemplate . 'layouts.frontend_new')
@section('content')
@php
    $user = getUserDetails(auth()->id());
@endphp

<div class="ud-page-wrap">

    {{-- ── Decorative background circles ── --}}
    <div class="ud-bg-circle ud-bg-circle--1"></div>
    <div class="ud-bg-circle ud-bg-circle--2"></div>
    <div class="ud-bg-circle ud-bg-circle--3"></div>

    <div class="container ud-container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-10 col-xl-8">
 
                {{-- ══ PAGE HEADER ══ --}}
                <div class="ud-page-header">
                    <div class="ud-logo-wrap">
                        <img src="{{ asset($activeTemplateTrue . 'images/logo/Wordmark.png') }}" alt="logo" class="ud-logo">
                    </div>
                    <div class="ud-step-pill">
                        <i class="las la-shield-alt"></i> Secure Membership Activation
                    </div>
                    <h1 class="ud-page-title">Complete Your Membership</h1>
                    <p class="ud-page-sub">Review your information below and enter your activation PIN to proceed.</p>
                </div>

                {{-- ══ PROGRESS TRACKER ══ --}}
                <div class="ud-progress-wrap">
                    <div class="ud-progress-step completed">
                        <div class="ud-ps-dot"><i class="las la-check"></i></div>
                        <span>Registered</span>
                    </div>
                    <div class="ud-progress-line completed"></div>
                    <div class="ud-progress-step active">
                        <div class="ud-ps-dot"><i class="las la-key"></i></div>
                        <span>Activation</span>
                    </div>
                    <div class="ud-progress-line"></div>
                    <div class="ud-progress-step">
                        <div class="ud-ps-dot"><i class="las la-check-circle"></i></div>
                        <span>Active</span>
                    </div>
                </div>

                <div class="ud-grid">

                    {{-- ══ LEFT: MEMBER INFORMATION ══ --}}
                    <div class="ud-info-card">
                        <div class="ud-card-header">
                            <div class="ud-card-icon">
                                <i class="las la-id-card"></i>
                            </div>
                            <div>
                                <h5 class="ud-card-title">Member Information</h5>
                                <p class="ud-card-sub">Please verify your registration details</p>
                            </div>
                        </div>

                        <div class="ud-avatar-row">
                            <div class="ud-avatar">
                                {{ strtoupper(substr($user->firstname ?? 'U', 0, 1)) }}{{ strtoupper(substr($user->lastname ?? '', 0, 1)) }}
                            </div>
                            <div>
                                <div class="ud-avatar-name">{{ $user->fullname }}</div>
                                <div class="ud-avatar-badge"><i class="las la-user-check"></i> @lang('Registered Member')</div>
                            </div>
                        </div>

                        <div class="ud-divider"></div>

                        <div class="ud-info-grid">
                            <div class="ud-info-item">
                                <div class="ud-info-icon"><i class="las la-envelope"></i></div>
                                <div>
                                    <div class="ud-info-label">@lang('Email Address')</div>
                                    <div class="ud-info-value">{{ $user->email }}</div>
                                </div>
                            </div>
                            <div class="ud-info-item">
                                <div class="ud-info-icon"><i class="las la-phone"></i></div>
                                <div>
                                    <div class="ud-info-label">@lang('Phone Number')</div>
                                    <div class="ud-info-value">{{ $user->mobile }}</div>
                                </div>
                            </div>
                            <div class="ud-info-item">
                                <div class="ud-info-icon"><i class="las la-map-marker-alt"></i></div>
                                <div>
                                    <div class="ud-info-label">@lang('Address')</div>
                                    <div class="ud-info-value">{{ $user->address }}</div>
                                </div>
                            </div>
                            <div class="ud-info-item">
                                <div class="ud-info-icon"><i class="las la-globe"></i></div>
                                <div>
                                    <div class="ud-info-label">@lang('Country')</div>
                                    <div class="ud-info-value">{{ $user->country ?? '—' }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Trust indicators --}}
                        
                    </div>

                    {{-- ══ RIGHT: ACTIVATION ══ --}}
                    <div class="ud-pin-card">

                        <div class="ud-card-header">
                            <div class="ud-card-icon">
                                <i class="las la-key"></i>
                            </div>
                            <div>
                                <h5 class="ud-card-title">Activate Membership</h5>
                                <p class="ud-card-sub">Use a PIN or pay online to activate</p>
                            </div>
                        </div>

                        {{-- ── PIN section ── --}}
                        <div class="ud-notice">
                            <i class="las la-info-circle ud-notice-icon"></i>
                            <p>Your PIN can be obtained from your <strong>Sponsor</strong> or use the chat icon at the bottom-right of this page.</p>
                        </div>

                        <form method="POST" action="{{ route('ipn.pinPay') }}" class="ud-pin-form">
                            @csrf

                            <div class="ud-pin-field-wrap">
                                <label class="ud-pin-label">@lang('Enter Activation PIN')</label>
                                <div class="ud-pin-input-wrap">
                                    <span class="ud-pin-icon"><i class="las la-hashtag"></i></span>
                                    <input type="text" class="ud-pin-input" name="pin"
                                        value="{{ old('pin') }}" required
                                        placeholder="● ● ● ● ● ●"
                                        autocomplete="off" spellcheck="false">
                                    <button type="button" class="ud-pin-toggle" id="pinToggleBtn" title="Show / hide PIN">
                                        <i class="las la-eye" id="pinEyeIcon"></i>
                                    </button>
                                </div>
                                @if($errors->has('pin'))
                                    <small class="ud-field-error"><i class="las la-times-circle"></i> {{ $errors->first('pin') }}</small>
                                @endif
                            </div>

                            <button type="submit" class="ud-submit-btn">
                                <i class="las la-paper-plane"></i>
                                @lang('Activate Membership')
                            </button>

                        </form>

                        {{-- ── OR divider ── --}}
                        <div class="ud-or-divider">
                            <span>OR</span>
                        </div>

                        {{-- ── Paystack section ── --}}
                        <div class="ud-notice">
                            <i class="las la-lock ud-notice-icon"></i>
                            <p>You will be redirected to our <strong>secure payment gateway</strong>. Your membership will be activated immediately upon successful payment.</p>
                        </div>

                        <div class="ud-amount-row">
                            <span class="ud-amount-label">Membership Registration Fee</span>
                            <span class="ud-amount-val">{{ showAmount($data['amount'] / 100) }}</span>
                        </div>

                        <div class="ud-pin-form">
                            <form action="{{ route('ipn.Paystack1') }}" method="POST" id="paystackForm">
                                @csrf
                                <input type="hidden" name="reference" id="paystackReference">
                                <input type="hidden" name="paystack-trxref" id="paystackTrxref">
                                <button type="button" id="btn-pay" class="ud-submit-btn ud-submit-btn--pay">
                                    <i class="las la-credit-card"></i>
                                    @lang('Pay Now') &mdash; {{ showAmount($data['amount'] / 100) }}
                                </button>
                            </form>
                        </div>

                        <p class="ud-terms-note">
                            By proceeding, you agree to our<br>
                            <a href="{{ route('agreement') }}" target="_blank" class="ud-link">Terms of Use and Member Investment Policy</a>.
                        </p>

                        <div class="ud-divider"></div>

                        <div class="ud-logout-row">
                            <i class="las la-sign-out-alt"></i>
                            <a href="{{ route('user.logout') }}" class="ud-logout-link">Sign out of this account</a>
                        </div>

                    </div>

                </div>{{-- /.ud-grid --}}

            </div>
        </div>
    </div>
</div>

@endsection

@push('style')
    <style>
        /* ═══════════════════════════════════════════════════════
           USER DATA / MEMBERSHIP ACTIVATION PAGE
           Theme: #6eb494
        ═══════════════════════════════════════════════════════ */
        :root {
            --ud-primary:     #6eb494;
            --ud-primary-dk:  #4a8a70;
            --ud-primary-dkr: #2d6352;
            --ud-primary-lt:  #9fcdb5;
            --ud-primary-bg:  #f0f9f5;
            --ud-primary-rim: rgba(110,180,148,.22);
            --ud-white:       #ffffff;
            --ud-gray-50:     #f8fdfb;
            --ud-gray-100:    #e8f5ef;
            --ud-gray-200:    #c8e0d5;
            --ud-gray-400:    #7aaa96;
            --ud-gray-600:    #3d6e5a;
            --ud-gray-800:    #1a3d2e;
            --ud-shadow-sm:   0 2px 8px rgba(46,99,82,.10);
            --ud-shadow:      0 4px 24px rgba(46,99,82,.13);
            --ud-shadow-lg:   0 8px 40px rgba(46,99,82,.18);
            --ud-radius:      14px;
            --ud-transition:  .24s ease;
        }

        /* ── Page wrap ── */
        .ud-page-wrap {
            min-height: 100vh;
            background: var(--ud-primary-bg);
            padding: 60px 0 80px;
            position: relative;
            overflow: hidden;
            font-family: 'Open Sans', sans-serif;
        }

        /* ── Decorative circles ── */
        .ud-bg-circle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
            opacity: .55;
        }
        .ud-bg-circle--1 {
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(110,180,148,.18) 0%, transparent 70%);
            top: -180px; right: -120px;
        }
        .ud-bg-circle--2 {
            width: 380px; height: 380px;
            background: radial-gradient(circle, rgba(74,138,112,.13) 0%, transparent 70%);
            bottom: -100px; left: -80px;
        }
        .ud-bg-circle--3 {
            width: 240px; height: 240px;
            background: radial-gradient(circle, rgba(110,180,148,.10) 0%, transparent 70%);
            top: 40%; left: 40%;
        }

        .ud-container { position: relative; z-index: 1; }

        /* ── Page header ── */
        .ud-page-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .ud-logo-wrap { margin-bottom: 20px; }
        .ud-logo { height: 48px; }

        .ud-step-pill {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(110,180,148,.15);
            border: 1px solid rgba(110,180,148,.35);
            color: var(--ud-primary-dk);
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .8px;
            padding: 5px 14px; border-radius: 20px;
            margin-bottom: 14px;
        }

        .ud-page-title {
            font-size: clamp(22px, 4vw, 30px);
            font-weight: 800;
            color: var(--ud-primary-dkr);
            margin-bottom: 8px;
            line-height: 1.2;
        }
        .ud-page-sub {
            font-size: 14px;
            color: var(--ud-gray-400);
            max-width: 420px;
            margin: 0 auto;
        }

        /* ── Progress tracker ── */
        .ud-progress-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-bottom: 36px;
        }
        .ud-progress-step {
            display: flex; flex-direction: column; align-items: center; gap: 6px;
        }
        .ud-ps-dot {
            width: 40px; height: 40px;
            border-radius: 50%;
            border: 2px solid var(--ud-gray-200);
            background: var(--ud-white);
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            color: var(--ud-gray-200);
            transition: all var(--ud-transition);
        }
        .ud-progress-step span {
            font-size: 11px; font-weight: 600;
            text-transform: uppercase; letter-spacing: .5px;
            color: var(--ud-gray-200);
            white-space: nowrap;
            transition: color var(--ud-transition);
        }
        .ud-progress-step.completed .ud-ps-dot {
            background: var(--ud-primary);
            border-color: var(--ud-primary);
            color: #fff;
        }
        .ud-progress-step.completed span { color: var(--ud-primary-dk); }
        .ud-progress-step.active .ud-ps-dot {
            background: var(--ud-primary-dkr);
            border-color: var(--ud-primary-dkr);
            color: #fff;
            box-shadow: 0 0 0 5px var(--ud-primary-rim);
        }
        .ud-progress-step.active span { color: var(--ud-primary-dkr); font-weight: 700; }

        .ud-progress-line {
            flex: 1; height: 2px;
            background: var(--ud-gray-200);
            max-width: 80px; min-width: 30px;
            margin: 0 4px;
            margin-bottom: 22px;
            transition: background var(--ud-transition);
        }
        .ud-progress-line.completed { background: var(--ud-primary); }

        /* ── Grid ── */
        .ud-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            align-items: start;
        }
        @media (max-width: 768px) { .ud-grid { grid-template-columns: 1fr; } }

        /* ── Shared card base ── */
        .ud-info-card,
        .ud-pin-card {
            background: var(--ud-white);
            border-radius: var(--ud-radius);
            border: 1px solid var(--ud-gray-100);
            box-shadow: var(--ud-shadow);
            overflow: hidden;
            position: relative;
        }
        /* top stripe */
        .ud-info-card::before,
        .ud-pin-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--ud-primary-dkr), var(--ud-primary), var(--ud-primary-lt));
            border-radius: var(--ud-radius) var(--ud-radius) 0 0;
        }

        /* ── Card header ── */
        .ud-card-header {
            display: flex; align-items: flex-start; gap: 12px;
            padding: 22px 24px 16px;
            border-bottom: 1px solid var(--ud-gray-100);
        }
        .ud-card-icon {
            width: 44px; height: 44px; flex-shrink: 0;
            background: linear-gradient(135deg, var(--ud-primary-dkr), var(--ud-primary-dk));
            border-radius: 11px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; color: #fff;
        }
        .ud-card-title {
            font-size: 15px; font-weight: 700;
            color: var(--ud-primary-dkr);
            margin: 0 0 3px;
        }
        .ud-card-sub {
            font-size: 12px; color: var(--ud-gray-400);
            margin: 0;
        }

        /* ── Avatar row ── */
        .ud-avatar-row {
            display: flex; align-items: center; gap: 14px;
            padding: 18px 24px 0;
        }
        .ud-avatar {
            width: 54px; height: 54px; flex-shrink: 0;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--ud-primary-dkr), var(--ud-primary));
            color: #fff;
            font-size: 18px; font-weight: 800;
            display: flex; align-items: center; justify-content: center;
            letter-spacing: 1px;
            box-shadow: 0 4px 14px rgba(110,180,148,.35);
        }
        .ud-avatar-name {
            font-size: 16px; font-weight: 700;
            color: var(--ud-primary-dkr);
            margin-bottom: 4px;
        }
        .ud-avatar-badge {
            display: inline-flex; align-items: center; gap: 4px;
            background: rgba(110,180,148,.12);
            color: var(--ud-primary-dk);
            font-size: 11px; font-weight: 700;
            padding: 3px 10px; border-radius: 20px;
        }

        /* ── Divider ── */
        .ud-divider {
            height: 1px;
            background: var(--ud-gray-100);
            margin: 16px 24px;
        }

        /* ── Info grid ── */
        .ud-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            padding: 0 12px 12px;
        }
        @media (max-width: 480px) { .ud-info-grid { grid-template-columns: 1fr; } }

        .ud-info-item {
            display: flex; align-items: flex-start; gap: 10px;
            padding: 12px;
            border-radius: 8px;
            transition: background var(--ud-transition);
        }
        .ud-info-item:hover { background: var(--ud-gray-50); }
        .ud-info-icon {
            width: 34px; height: 34px; flex-shrink: 0;
            background: var(--ud-primary-bg);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; color: var(--ud-primary-dk);
        }
        .ud-info-label {
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .6px;
            color: var(--ud-gray-400);
            margin-bottom: 3px;
        }
        .ud-info-value {
            font-size: 13px; font-weight: 600;
            color: var(--ud-primary-dkr);
            word-break: break-word;
        }

        /* ── Trust badges ── */
        .ud-trust-row {
            display: flex; gap: 8px; flex-wrap: wrap;
            padding: 0 24px 20px;
        }
        .ud-trust-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: var(--ud-gray-100);
            color: var(--ud-primary-dk);
            font-size: 11px; font-weight: 600;
            padding: 4px 10px; border-radius: 6px;
        }

        /* ── PIN card body ── */
        .ud-notice {
            display: flex; align-items: flex-start; gap: 10px;
            background: rgba(110,180,148,.10);
            border: 1px solid rgba(110,180,148,.25);
            border-radius: 10px;
            padding: 12px 14px;
            margin: 16px 24px;
        }
        .ud-notice-icon {
            font-size: 18px; color: var(--ud-primary-dk);
            margin-top: 1px; flex-shrink: 0;
        }
        .ud-notice p {
            font-size: 12px; color: var(--ud-gray-600);
            margin: 0; line-height: 1.55;
        }
        .ud-notice strong { color: var(--ud-primary-dkr); }

        /* ── PIN form ── */
        .ud-pin-form { padding: 0 24px 8px; }

        .ud-pin-field-wrap { margin-bottom: 20px; }
        .ud-pin-label {
            display: block;
            font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .7px;
            color: var(--ud-primary-dkr);
            margin-bottom: 8px;
        }
        .ud-pin-input-wrap {
            display: flex; align-items: center;
            border: 1.5px solid var(--ud-gray-200);
            border-radius: 10px;
            background: var(--ud-gray-50);
            transition: border-color var(--ud-transition), box-shadow var(--ud-transition);
            overflow: hidden;
        }
        .ud-pin-input-wrap:focus-within {
            border-color: var(--ud-primary);
            box-shadow: 0 0 0 3px var(--ud-primary-rim);
            background: var(--ud-white);
        }
        .ud-pin-icon {
            width: 44px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; color: var(--ud-primary);
            border-right: 1.5px solid var(--ud-gray-200);
        }
        .ud-pin-input {
            flex: 1; border: none; outline: none;
            padding: 13px 14px;
            font-size: 15px; font-weight: 600;
            letter-spacing: 3px;
            color: var(--ud-primary-dkr);
            background: transparent;
        }
        .ud-pin-input::placeholder { letter-spacing: 4px; color: var(--ud-primary-lt); font-weight: 400; font-size: 14px; }
        .ud-pin-toggle {
            width: 44px; flex-shrink: 0;
            border: none; background: transparent; cursor: pointer;
            font-size: 17px; color: var(--ud-primary);
            display: flex; align-items: center; justify-content: center;
            border-left: 1.5px solid var(--ud-gray-200);
            height: 100%; padding: 13px 0;
            transition: color var(--ud-transition), background var(--ud-transition);
        }
        .ud-pin-toggle:hover { color: var(--ud-primary-dk); background: var(--ud-gray-100); }

        .ud-field-error {
            display: flex; align-items: center; gap: 5px;
            font-size: 12px; color: #dc2626; margin-top: 6px;
        }

        /* ── Submit button ── */
        .ud-submit-btn {
            width: 100%;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            background: linear-gradient(135deg, var(--ud-primary-dkr) 100%, var(--ud-primary-dk) 100%, var(--ud-primary) 100%);
            color: #fff;
            border: none; border-radius: 10px;
            padding: 14px 24px;
            font-size: 15px; font-weight: 700;
            letter-spacing: .3px; cursor: pointer;
            transition: opacity var(--ud-transition), transform var(--ud-transition), box-shadow var(--ud-transition);
            margin-bottom: 14px;
        }
        .ud-submit-btn:hover {
            opacity: .88;
            transform: translateY(-1px);
            box-shadow: 0 8px 28px rgba(45,99,82,.30);
        }
        .ud-submit-btn:active { transform: translateY(0); opacity: .95; }

        /* ── Terms note ── */
        .ud-terms-note {
            font-size: 11px; color: var(--ud-gray-400);
            text-align: center; padding: 0 4px;
            line-height: 1.6;
            margin: 0;
        }
        .ud-link { color: var(--ud-primary-dk); font-weight: 600; }

        /* ── Logout row ── */
        .ud-logout-row {
            display: flex; align-items: center; justify-content: center; gap: 7px;
            padding: 0 24px 20px;
            font-size: 13px; color: var(--ud-gray-400);
        }
        .ud-logout-link {
            color: var(--ud-gray-600);
            font-weight: 600;
            text-decoration: none;
            transition: color var(--ud-transition);
        }
        .ud-logout-link:hover { color: #dc2626; }

        /* ── OR divider ── */
        .ud-or-divider {
            display: flex; align-items: center; gap: 12px;
            margin: 4px 24px 12px;
            color: var(--ud-gray-400);
            font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px;
        }
        .ud-or-divider::before,
        .ud-or-divider::after {
            content: ''; flex: 1;
            height: 1px; background: var(--ud-gray-200);
        }

        /* ── Amount row ── */
        .ud-amount-row {
            display: flex; align-items: center; justify-content: space-between;
            background: var(--ud-primary-bg);
            border: 1px solid var(--ud-gray-100);
            border-radius: 10px;
            padding: 12px 16px;
            margin: 0 24px 14px;
        }
        .ud-amount-label { font-size: 13px; color: var(--ud-gray-600); font-weight: 600; }
        .ud-amount-val   { font-size: 16px; color: var(--ud-primary-dkr); font-weight: 800; }

        /* ── Pay button variant ── */
        .ud-submit-btn--pay {
            background: linear-gradient(135deg, #1a3d2e 0%, #2d6352 50%, #4a8a70 100%);
        }
    </style>
@endpush

@push('script')
<script src="//js.paystack.co/v1/inline.js"></script>
<script>
(function($) {
    "use strict";

    /* PIN eye toggle */
    var $pinInput = $('[name="pin"]');
    $('#pinToggleBtn').on('click', function() {
        if ($pinInput.attr('type') === 'text') {
            $pinInput.attr('type', 'password');
            $('#pinEyeIcon').removeClass('la-eye-slash').addClass('la-eye');
        } else {
            $pinInput.attr('type', 'text');
            $('#pinEyeIcon').removeClass('la-eye').addClass('la-eye-slash');
        }
    });

    /* Paystack payment */
    $('#btn-pay').on('click', function () {
        var handler = PaystackPop.setup({
            key:      '{{ $data['key'] }}',
            email:    '{{ $data['email'] }}',
            amount:   {{ round($data['amount']) }},
            currency: '{{ $data['currency'] }}',
            ref:      '{{ $data['ref'] }}',
            metadata: {
                payment_type: 'registration'
            },
            callback: function (response) {
                $('#paystackReference').val(response.reference);
                $('#paystackTrxref').val(response.reference);
                $('#paystackForm').submit();
            },
            onClose: function () {
                window.location.reload();
            }
        });
        handler.openIframe();
    });

})(jQuery);
</script>
@endpush

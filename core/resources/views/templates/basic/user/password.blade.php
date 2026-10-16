@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="nc-wrap" id="ncWrap">
{{-- ── Page Header ─────────────────────────────────── --}}
<div class="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="sl-page-title mb-1">Change Password</h4>
        <p class="sl-page-subtitle mb-0">Keep your account secure with a strong, unique password.</p>
    </div>
</div>

<div class="row g-4">
    {{-- ── LEFT: Form ──────────────────────────────────── --}}
    <div class="col-12 col-lg-7">
        <div class="sl-card">
            <div class="sl-card-header"><i class="las la-shield-alt me-1"></i> Update Your Password</div>
            <div class="sl-card-body">
                <form method="post" id="changePasswordForm">
                    @csrf

                    <div class="mb-3">
                        <label class="sl-label" for="current_password">Current Password <span class="sl-required">*</span></label>
                        <div class="sl-pwd-wrap">
                            <input class="sl-input pwd-field" id="current_password" name="current_password"
                                   type="password" required autocomplete="current-password">
                            <button type="button" class="sl-pwd-toggle" data-target="current_password" tabindex="-1">
                                <i class="las la-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="sl-label" for="password">New Password <span class="sl-required">*</span></label>
                        <div class="sl-pwd-wrap">
                            <input class="sl-input pwd-field @if (gs('secure_password')) secure-password @endif"
                                   id="password" name="password" type="password" required
                                   autocomplete="new-password">
                            <button type="button" class="sl-pwd-toggle" data-target="password" tabindex="-1">
                                <i class="las la-eye"></i>
                            </button>
                        </div>
                        <div class="sl-pwd-meter" id="pwdMeter">
                            <div class="sl-pwd-meter-seg"></div>
                            <div class="sl-pwd-meter-seg"></div>
                            <div class="sl-pwd-meter-seg"></div>
                            <div class="sl-pwd-meter-seg"></div>
                        </div>
                        <p class="sl-pwd-meter-label mb-0" id="pwdMeterLabel">Start typing a new password</p>
                    </div>

                    <div class="mb-4">
                        <label class="sl-label" for="password_confirmation">Confirm New Password <span class="sl-required">*</span></label>
                        <div class="sl-pwd-wrap">
                            <input class="sl-input pwd-field" id="password_confirmation" name="password_confirmation"
                                   type="password" required autocomplete="new-password">
                            <button type="button" class="sl-pwd-toggle" data-target="password_confirmation" tabindex="-1">
                                <i class="las la-eye"></i>
                            </button>
                        </div>
                        <p class="sl-field-hint mb-0" id="pwdMatchHint"></p>
                    </div>

                    <div class="sl-form-actions">
                        <button class="sl-btn sl-btn-primary" type="submit">
                            <i class="las la-check-circle me-1"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ── RIGHT: Security tips ────────────────────────── --}}
    <div class="col-12 col-lg-5">
        <div class="sl-card">
            <div class="sl-card-header"><i class="las la-lightbulb me-1"></i> Password Security Tips</div>
            <div class="sl-card-body">
                <div class="sl-tip-item">
                    <div class="sl-tip-icon"><i class="las la-ruler"></i></div>
                    <div>
                        <p class="fw-600 mb-0" style="font-size:.86rem;">Go long</p>
                        <small class="text-muted">Use at least 8–12 characters — length matters more than complexity.</small>
                    </div>
                </div>
                <div class="sl-tip-item">
                    <div class="sl-tip-icon"><i class="las la-font"></i></div>
                    <div>
                        <p class="fw-600 mb-0" style="font-size:.86rem;">Mix it up</p>
                        <small class="text-muted">Combine upper and lower case letters, numbers, and a symbol.</small>
                    </div>
                </div>
                <div class="sl-tip-item">
                    <div class="sl-tip-icon"><i class="las la-recycle"></i></div>
                    <div>
                        <p class="fw-600 mb-0" style="font-size:.86rem;">Never reuse</p>
                        <small class="text-muted">Don't reuse a password from another site or an old account password.</small>
                    </div>
                </div>
                <div class="sl-tip-item">
                    <div class="sl-tip-icon"><i class="las la-user-secret"></i></div>
                    <div>
                        <p class="fw-600 mb-0" style="font-size:.86rem;">Avoid the obvious</p>
                        <small class="text-muted">Skip names, birthdays, or anything easily guessed from your profile.</small>
                    </div>
                </div>
                {{--
                <div class="sl-tip-item">
                    <div class="sl-tip-icon"><i class="las la-mobile-alt"></i></div>
                    <div>
                        <p class="fw-600 mb-0" style="font-size:.86rem;">Add a second layer</p>
                        <small class="text-muted">
                            Turn on <a href="{{ route('user.twofactor') }}" style="color:var(--sl-green);font-weight:700;">Two-Factor Authentication</a> for extra protection beyond your password.
                        </small>
                    </div>
                </div>
                --}}
            </div>
        </div>
    </div>
</div>
</div>

@endsection

@if (gs('secure_password'))
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif

@push('script')
<script>
$(function () {
    'use strict';

    // Show/hide toggle for every password field on the page
    $('.sl-pwd-toggle').on('click', function () {
        var $input = $('#' + $(this).data('target'));
        var $icon  = $(this).find('i');
        var isHidden = $input.attr('type') === 'password';

        $input.attr('type', isHidden ? 'text' : 'password');
        $icon.toggleClass('la-eye la-eye-slash');
    });

    // Live strength meter — purely visual, independent of server-side rules
    var $segments = $('#pwdMeter .sl-pwd-meter-seg');
    var colors    = ['#DC2626', '#F59E0B', '#3B82F6', '#16A34A'];
    var labels    = ['Weak', 'Fair', 'Good', 'Strong'];

    $('#password').on('input', function () {
        var val   = $(this).val();
        var score = 0;

        if (val.length >= 8) score++;
        if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        $segments.each(function (i) {
            $(this).css('background', i < score ? colors[Math.max(score - 1, 0)] : '#E5E9EF');
        });

        $('#pwdMeterLabel').text(val.length === 0 ? 'Start typing a new password' : labels[Math.max(score - 1, 0)]);
    });

    // Live confirm-match hint
    function checkMatch() {
        var pwd     = $('#password').val();
        var confirm = $('#password_confirmation').val();
        var $hint   = $('#pwdMatchHint');

        if (!confirm) {
            $hint.text('').css('color', '');
        } else if (pwd === confirm) {
            $hint.text('Passwords match').css('color', 'var(--sl-green)');
        } else {
            $hint.text('Passwords do not match').css('color', '#DC2626');
        }
    }
    $('#password, #password_confirmation').on('input', checkMatch);
});
</script>
@endpush

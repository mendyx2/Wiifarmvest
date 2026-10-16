@extends($activeTemplate . 'layouts.app')
@php
    $registerContent = getContent('register.content', true);
@endphp
@section('panel')

<div class="bank-register-wrap">

    {{-- ══════════════════════════════════ LEFT PANEL ══════════════════════════════════ --}}
    <div class="bank-left-panel">

        {{-- geometric SVG background --}}
        <svg class="bank-geo-bg" viewBox="0 0 600 800" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <circle cx="500" cy="-50"  r="260" fill="rgba(255,255,255,.04)"/>
            <circle cx="-80" cy="700" r="300" fill="rgba(255,255,255,.04)"/>
            <circle cx="300" cy="400" r="180" fill="rgba(255,255,255,.03)"/>
            <polygon points="0,600 200,450 400,600" fill="rgba(255,255,255,.03)"/>
        </svg>

        <div class="bank-left-inner">
            {{-- logo --}}
            <div class="bank-logo-wrap">
                <a href="{{ route('home') }}">
                    <img src="{{ siteLogo() }}" alt="logo" class="bank-logo">
                </a>
            </div>

            {{-- headline --}}
            <div class="bank-left-hero">
                <div class="bank-shield-icon">
                    <i class="las la-shield-alt"></i>
                </div>
                <h2 class="bank-hero-title">{{ __(@$registerContent->data_values->heading ?? 'Open Your Account') }}</h2>
                <p class="bank-hero-sub">{{ __(@$registerContent->data_values->sub_heading ?? 'Secure, fast and fully encrypted registration') }}</p>
            </div>

            {{-- trust badges --}}
            <!-- <div class="bank-trust-badges">
                <div class="trust-badge">
                    <i class="las la-lock"></i>
                    <span>256-bit SSL Encrypted</span>
                </div>
                <div class="trust-badge">
                    <i class="las la-user-shield"></i>
                    <span>Identity Protected</span>
                </div>
                <div class="trust-badge">
                    <i class="las la-check-circle"></i>
                    <span>Regulated & Compliant</span>
                </div>
            </div> -->

            {{-- step guide --}}
            <div class="bank-step-guide">
                <div class="step-guide-item active-guide" data-guide="1">
                    <div class="step-guide-dot"><i class="las la-user-friends"></i></div>
                    <div>
                        <div class="step-guide-label">Step 1</div>
                        <div class="step-guide-name">Referral & Sponsor</div>
                    </div>
                </div>
                <div class="step-guide-connector"></div>
                <div class="step-guide-item" data-guide="2">
                    <div class="step-guide-dot"><i class="las la-key"></i></div>
                    <div>
                        <div class="step-guide-label">Step 2</div>
                        <div class="step-guide-name">Account Credentials</div>
                    </div>
                </div>
                <div class="step-guide-connector"></div>
                <div class="step-guide-item" data-guide="3">
                    <div class="step-guide-dot"><i class="las la-address-card"></i></div>
                    <div>
                        <div class="step-guide-label">Step 3</div>
                        <div class="step-guide-name">Personal Details</div>
                    </div>
                </div>
                <div class="step-guide-connector"></div>
                <div class="step-guide-item" data-guide="4">
                    <div class="step-guide-dot"><i class="las la-clipboard-check"></i></div>
                    <div>
                        <div class="step-guide-label">Step 4</div>
                        <div class="step-guide-name">Review & Submit</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════ RIGHT PANEL ══════════════════════════════════ --}}
    <div class="bank-right-panel">

        @if (!gs('registration'))
            <div class="bank-disabled-notice">
                <i class="las la-exclamation-triangle"></i>
                <strong>@lang('Registration is currently disabled.')</strong>
                @lang('Please contact support for assistance.')
            </div>
        @endif

        {{-- top progress bar --}}
        <div class="bank-progress-bar-wrap">
            <div class="bank-progress-track">
                <div class="bank-progress-fill" id="progressFill" style="width:25%"></div>
            </div>
            <div class="bank-progress-steps">
                @foreach([1 => 'Referral', 2 => 'Account', 3 => 'Personal', 4 => 'Finish'] as $n => $lbl)
                <div class="bank-progress-step {{ $n === 1 ? 'active' : '' }}" data-step="{{ $n }}">
                    <div class="bps-circle">
                        <span class="bps-num">{{ $n }}</span>
                        <i class="las la-check bps-check"></i>
                    </div>
                    <span class="bps-label">{{ $lbl }}</span>
                </div>
                @if($n < 4)
                <div class="bps-line"></div>
                @endif
                @endforeach
            </div>
        </div>

        {{-- form card --}}
        <div class="bank-form-card @if (!gs('registration')) form-disabled @endif">

            <form class="verify-gcaptcha disableSubmission" method="POST" action="{{ route('user.register') }}" id="regForm">
                @csrf

                {{-- ── STEP 1 ── --}}
                <div class="bank-pane active" id="step-1">
                    <div class="bank-pane-header">
                        <div class="bank-pane-icon"><i class="las la-user-friends"></i></div>
                        <div>
                            <h5 class="bank-pane-title">@lang('Referral & Sponsor Info')</h5>
                            <p class="bank-pane-sub">@lang('Enter your sponsor\'s details or leave blank if you don\'t have one.')</p>
                        </div>
                    </div>

                    @if ($refUser == null)
                    <div class="bank-field-group">
                        <label class="bank-label">@lang('Referral Username')</label>
                        <div class="bank-input-wrap">
                            <span class="bank-input-icon"><i class="las la-at"></i></span>
                            <input class="bank-input referral" id="referenceBy" name="referBy" type="text"
                                value="{{ old('referBy') }}" placeholder="@lang('Enter referral username')">
                        </div>
                        <small class="bank-hint">@lang('Leave blank if you don\'t have a sponsor')</small>
                        <div id="ref" class="mt-1"></div>
                    </div>
                    @else
                    <div class="bank-field-group">
                        <label class="bank-label">@lang('Referral Username')</label>
                        <div class="bank-input-wrap">
                            <span class="bank-input-icon"><i class="las la-at"></i></span>
                            <input class="bank-input referral" value="{{ $refUser->username }}" id="ref_name"
                                name="referBy" type="text" required readonly>
                        </div>
                        <small class="bank-hint"><i class="las la-check-circle text--success"></i> @lang('Sponsor confirmed')</small>
                    </div>
                    @endif

                    <div class="bank-toggle-row">
                        <label class="bank-toggle-label" for="showDetailsCheckbox">
                            <div class="bank-toggle-switch">
                                <input type="checkbox" id="showDetailsCheckbox">
                                <span class="bank-toggle-slider"></span>
                            </div>
                            @lang('Add Placement Details')
                        </label>
                    </div>

                    <div id="additionalFields" style="display:none;" class="bank-placement-box">
                        <div class="bank-field-group">
                            <label class="bank-label">@lang('Parent Placement Username')</label>
                            <div class="bank-input-wrap">
                                <span class="bank-input-icon"><i class="las la-sitemap"></i></span>
                                <input type="text" class="bank-input referral" id="sponsorBy" name="parent"
                                    value="{{ old('parent') }}" placeholder="@lang('Parent username')">
                            </div>
                            <small class="text--danger" id="parentMsg"></small>
                        </div>
                        <div class="bank-field-group">
                            <label class="bank-label">@lang('Position')</label>
                            <div class="bank-select-wrap">
                                <select class="bank-select" id="position" name="position">
                                    <option value="" selected>@lang('Select position')</option>
                                    <option value="left">@lang('Left Child')</option>
                                    <option value="right">@lang('Right Child')</option>
                                </select>
                                <i class="las la-chevron-down bank-select-arrow"></i>
                            </div>
                            <span id="position-test"><span class="text--danger"></span></span>
                        </div>
                    </div>

                    @if(isset($projects) && $projects->count())
                    <div class="bank-field-group">
                        <label class="bank-label">@lang('Select Project')</label>
                        <div class="bank-select-wrap">
                            <select class="bank-select" name="project_id" id="reg-project">
                                <option value="">@lang('Choose a project')</option>
                                @foreach($projects as $project)
                                <option value="{{ $project->id }}" @selected(old('project_id') == $project->id)>
                                    {{ $project->title }} &mdash; {{ gs('cur_sym') }}{{ showAmount($project->amount) }}
                                </option>
                                @endforeach
                            </select>
                            <i class="las la-chevron-down bank-select-arrow"></i>
                        </div>
                    </div>
                    @endif

                    <div class="bank-nav-row">
                        <div></div>
                        <button type="button" class="bank-btn-next btn-next" data-current="1" data-next="2">
                            @lang('Continue') <i class="las la-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- ── STEP 2 ── --}}
                <div class="bank-pane" id="step-2">
                    <div class="bank-pane-header">
                        <div class="bank-pane-icon"><i class="las la-key"></i></div>
                        <div>
                            <h5 class="bank-pane-title">@lang('Account Credentials')</h5>
                            <p class="bank-pane-sub">@lang('Choose a unique username, enter your email and set a strong password.')</p>
                        </div>
                    </div>

                    <div class="bank-field-group">
                        <label class="bank-label">@lang('Username')</label>
                        <div class="bank-input-wrap">
                            <span class="bank-input-icon"><i class="las la-user"></i></span>
                            <input type="text" class="bank-input checkUser" name="username"
                                value="{{ old('username') }}" required placeholder="@lang('Choose a username')">
                        </div>
                        <small class="text--danger usernameExist"></small>
                    </div>

                    <div class="bank-field-group">
                        <label class="bank-label">@lang('Email Address')</label>
                        <div class="bank-input-wrap">
                            <span class="bank-input-icon"><i class="las la-envelope"></i></span>
                            <input class="bank-input checkUser" name="email" type="email"
                                required placeholder="@lang('your@email.com')">
                        </div>
                    </div>

                    <div class="bank-field-row">
                        <div class="bank-field-group">
                            <label class="bank-label">@lang('Password')</label>
                            <div class="bank-input-wrap">
                                <span class="bank-input-icon"><i class="las la-lock"></i></span>
                                <input class="bank-input @if(gs('secure_password')) secure-password @endif"
                                    name="password" type="password" required
                                    placeholder="@lang('Create password')" id="password">
                                <button type="button" class="bank-eye-btn" data-target="password">
                                    <i class="las la-eye" id="password-eye-icon"></i>
                                </button>
                            </div>
                            {{-- strength meter --}}
                            <div class="pw-strength-wrap" id="pwStrengthWrap" style="display:none">
                                <div class="pw-strength-bar">
                                    <div class="pw-strength-fill" id="pwFill"></div>
                                </div>
                                <span class="pw-strength-label" id="pwLabel"></span>
                            </div>
                        </div>

                        <div class="bank-field-group">
                            <label class="bank-label">@lang('Confirm Password')</label>
                            <div class="bank-input-wrap">
                                <span class="bank-input-icon"><i class="las la-lock"></i></span>
                                <input class="bank-input" name="password_confirmation" type="password"
                                    required placeholder="@lang('Repeat password')" id="password_confirmation">
                                <button type="button" class="bank-eye-btn" data-target="password_confirmation">
                                    <i class="las la-eye" id="password_confirmation-eye-icon"></i>
                                </button>
                            </div>
                            <small id="passwordMatchMessage"></small>
                        </div>
                    </div>

                    <div class="bank-nav-row">
                        <button type="button" class="bank-btn-back btn-prev" data-prev="1">
                            <i class="las la-arrow-left"></i> @lang('Back')
                        </button>
                        <button type="button" class="bank-btn-next btn-next" data-current="2" data-next="3">
                            @lang('Continue') <i class="las la-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- ── STEP 3 ── --}}
                <div class="bank-pane" id="step-3">
                    <div class="bank-pane-header">
                        <div class="bank-pane-icon"><i class="las la-address-card"></i></div>
                        <div>
                            <h5 class="bank-pane-title">@lang('Personal & Contact Details')</h5>
                            <p class="bank-pane-sub">@lang('Tell us a bit about yourself to complete your profile.')</p>
                        </div>
                    </div>

                    <div class="bank-field-row">
                        <div class="bank-field-group">
                            <label class="bank-label">@lang('First Name')</label>
                            <div class="bank-input-wrap">
                                <span class="bank-input-icon"><i class="las la-user-circle"></i></span>
                                <input class="bank-input" name="firstname" type="text"
                                    value="{{ old('firstname') }}" required placeholder="@lang('First name')">
                            </div>
                        </div>
                        <div class="bank-field-group">
                            <label class="bank-label">@lang('Last Name')</label>
                            <div class="bank-input-wrap">
                                <span class="bank-input-icon"><i class="las la-user-circle"></i></span>
                                <input class="bank-input" name="lastname" type="text"
                                    value="{{ old('lastname') }}" required placeholder="@lang('Last name')">
                            </div>
                        </div>
                    </div>

                    <div class="bank-field-row">
                        <div class="bank-field-group">
                            <label class="bank-label">@lang('Country')</label>
                            <div class="bank-select-wrap">
                                @php
                                    $ngData = null; $ngCode = null;
                                    foreach ((array) $countries as $_k => $_c) {
                                        if ($_c->country === 'Nigeria') { $ngData = $_c; $ngCode = $_k; break; }
                                    }
                                @endphp
                                <select name="country" class="bank-select" required>
                                    @if($ngData)
                                    <option data-mobile_code="{{ $ngData->dial_code }}" value="Nigeria" data-code="{{ $ngCode }}"
                                        @selected(old('country', 'Nigeria') === 'Nigeria')>Nigeria</option>
                                    <option value="" disabled>── Other countries ──</option>
                                    @endif
                                    @foreach($countries as $key => $country)
                                    @if($country->country !== 'Nigeria')
                                    <option data-mobile_code="{{ $country->dial_code }}" value="{{ $country->country }}" data-code="{{ $key }}"
                                        @selected(old('country') == $country->country)>{{ $country->country }}</option>
                                    @endif
                                    @endforeach
                                </select>
                                <i class="las la-chevron-down bank-select-arrow"></i>
                            </div>
                        </div>
                        <div class="bank-field-group">
                            <label class="bank-label">@lang('Mobile Number')</label>
                            <div class="bank-input-wrap bank-phone-wrap">
                                <span class="bank-phone-code mobile-code">+1</span>
                                <input type="hidden" name="mobile_code">
                                <input type="hidden" name="country_code">
                                <input type="number" name="mobile" value="{{ old('mobile') }}"
                                    class="bank-input bank-input-phone checkUser" required placeholder="@lang('Phone number')">
                            </div>
                            <small class="text--danger mobileExist"></small>
                        </div>
                    </div>

                    <div class="bank-field-group">
                        <label class="bank-label">@lang('Address')</label>
                        <div class="bank-input-wrap">
                            <span class="bank-input-icon"><i class="las la-map-marker-alt"></i></span>
                            <input type="text" class="bank-input" name="address"
                                value="{{ old('address') }}" required placeholder="@lang('Street address')">
                        </div>
                    </div>

                    <div class="bank-field-row">
                        <div class="bank-field-group">
                            <label class="bank-label">@lang('State / Province')</label>
                            <div class="bank-select-wrap">
                                <select name="state" class="bank-select" required>
                                    <option value="">— Select state —</option>
                                </select>
                                <i class="las la-chevron-down bank-select-arrow"></i>
                            </div>
                            <small id="regStateLoader" class="reg-geo-loader" style="display:none;">
                                <i class="las la-spinner la-spin"></i> @lang('Loading states…')
                            </small>
                        </div>
                        <div class="bank-field-group">
                            <label class="bank-label">@lang('City')</label>
                            <div class="bank-select-wrap">
                                <select name="city" class="bank-select" required>
                                    <option value="">— Select a state first —</option>
                                </select>
                                <i class="las la-chevron-down bank-select-arrow"></i>
                            </div>
                            <small id="regCityLoader" class="reg-geo-loader" style="display:none;">
                                <i class="las la-spinner la-spin"></i> @lang('Loading cities…')
                            </small>
                        </div>
                    </div>

                    <div class="bank-nav-row">
                        <button type="button" class="bank-btn-back btn-prev" data-prev="2">
                            <i class="las la-arrow-left"></i> @lang('Back')
                        </button>
                        <button type="button" class="bank-btn-next btn-next" data-current="3" data-next="4">
                            @lang('Continue') <i class="las la-arrow-right"></i>
                        </button>
                    </div>
                </div>

                {{-- ── STEP 4 ── --}}
                <div class="bank-pane" id="step-4">
                    <div class="bank-pane-header">
                        <div class="bank-pane-icon"><i class="las la-clipboard-check"></i></div>
                        <div>
                            <h5 class="bank-pane-title">@lang('Review & Submit')</h5>
                            <p class="bank-pane-sub">@lang('Please verify your information before submitting your application.')</p>
                        </div>
                    </div>

                    {{-- Review summary card --}}
                    <div class="bank-review-card">
                        <div class="bank-review-section">
                            <div class="bank-review-section-title">
                                <i class="las la-user-friends"></i> @lang('Referral')
                            </div>
                            <div class="bank-review-row">
                                <span class="bank-review-key">@lang('Sponsor')</span>
                                <span class="bank-review-val" id="rv-referral">—</span>
                            </div>
                        </div>
                        <div class="bank-review-section">
                            <div class="bank-review-section-title">
                                <i class="las la-key"></i> @lang('Account')
                            </div>
                            <div class="bank-review-row">
                                <span class="bank-review-key">@lang('Username')</span>
                                <span class="bank-review-val" id="rv-username">—</span>
                            </div>
                            <div class="bank-review-row">
                                <span class="bank-review-key">@lang('Email')</span>
                                <span class="bank-review-val" id="rv-email">—</span>
                            </div>
                        </div>
                        <div class="bank-review-section">
                            <div class="bank-review-section-title">
                                <i class="las la-address-card"></i> @lang('Personal')
                            </div>
                            <div class="bank-review-row">
                                <span class="bank-review-key">@lang('Full Name')</span>
                                <span class="bank-review-val" id="rv-name">—</span>
                            </div>
                            <div class="bank-review-row">
                                <span class="bank-review-key">@lang('Country')</span>
                                <span class="bank-review-val" id="rv-country">—</span>
                            </div>
                            <div class="bank-review-row">
                                <span class="bank-review-key">@lang('Mobile')</span>
                                <span class="bank-review-val" id="rv-mobile">—</span>
                            </div>
                            <div class="bank-review-row">
                                <span class="bank-review-key">@lang('Location')</span>
                                <span class="bank-review-val" id="rv-location">—</span>
                            </div>
                        </div>
                    </div>

                    <div class="bank-edit-links">
                        <button type="button" class="bank-edit-link btn-prev" data-prev="1">
                            <i class="las la-pencil-alt"></i> @lang('Edit Referral')
                        </button>
                        <button type="button" class="bank-edit-link btn-prev" data-prev="2">
                            <i class="las la-pencil-alt"></i> @lang('Edit Account')
                        </button>
                        <button type="button" class="bank-edit-link btn-prev" data-prev="3">
                            <i class="las la-pencil-alt"></i> @lang('Edit Personal')
                        </button>
                    </div>

                    @php $custom = true; @endphp
                    <div class="mt-3">
                        <x-captcha :custom="$custom" />
                    </div>

                    @if (gs('agree'))
                        <div class="bank-agree-row">
                            <label class="bank-agree-label" for="agree">
                                <input id="agree" name="agree" type="checkbox" @checked(old('agree')) required class="bank-checkbox">
                                <span>@lang('I agree with the')
                                    <a class="bank-link" href="{{ route('agreement') }}" target="_blank">
                                        @lang('Terms of Use and Member Investment Policy')
                                    </a>
                                </span>
                            </label>
                        </div>
                    @endif

                    <div class="bank-submit-row">
                        <button type="button" class="bank-btn-back btn-prev" data-prev="3">
                            <i class="las la-arrow-left"></i> @lang('Back')
                        </button>
                        <button class="bank-btn-submit" type="submit">
                            <i class="las la-paper-plane"></i> @lang('Submit Application')
                        </button>
                    </div>

                    <div class="bank-login-row">
                        @lang('Already have an account?')
                        <a href="{{ route('user.login') }}" class="bank-link">@lang('Sign In')</a>
                    </div>
                </div>

            </form>
        </div>

        <div class="bank-footer-note">
            <i class="las la-lock"></i>
            @lang('Click continue to advance')
        </div>
    </div>
</div>

{{-- Existing-user modal --}}
<div class="modal fade" id="existModalCenter" role="dialog" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content bank-modal">
            <div class="modal-header bank-modal-header">
                <h5 class="modal-title">@lang('Account Already Exists')</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="bank-modal-icon"><i class="las la-user-check"></i></div>
                <p class="mt-2">@lang('An account with this information already exists. Please sign in instead.')</p>
            </div>
            <div class="modal-footer">
                <button class="bank-btn-back" data-bs-dismiss="modal" type="button">@lang('Close')</button>
                <a class="bank-btn-next" href="{{ route('user.login') }}">@lang('Go to Login')</a>
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

@push('style')
<style>
/* ══════════════════════════════════════════════════════════
   BANKING REGISTER PAGE
══════════════════════════════════════════════════════════ */

/* CSS Variables — Theme: #6eb494 */
:root {
    --bank-primary:     #1e8155;   /* brand green */
    --bank-primary-dk:  #4a8a70;   /* darker green */
    --bank-primary-dkr: #2d6352;   /* deep green (replaces navy-dark) */
    --bank-primary-lt:  #9fcdb5;   /* light green */
    --bank-primary-bg:  #f0f9f5;   /* very light green tint */
    --bank-primary-rim: rgba(110,180,148,.20); /* ring glow */

    /* keep aliases so existing selectors still work */
    --bank-navy:      #2d6352;
    --bank-navy-mid:  #3d7a63;
    --bank-navy-dark: #1e4a39;

    /* accent (warm cream, contrasts the green) */
    --bank-gold:      #6eb494;
    --bank-gold-lt:   #9fcdb5;

    --bank-white:     #FFFFFF;
    --bank-gray-50:   #F4FAF7;
    --bank-gray-100:  #E8F5EF;
    --bank-gray-200:  #C8E0D5;
    --bank-gray-400:  #7aaa96;
    --bank-gray-600:  #3d6e5a;
    --bank-gray-800:  #1a3d2e;
    --bank-success:   #16A34A;
    --bank-danger:    #DC2626;
    --bank-radius:    10px;
    --bank-shadow:    0 4px 24px rgba(46,99,82,.12);
    --bank-transition: .25s ease;
}

/* ── Layout wrapper ── */
.bank-register-wrap {
    display: flex;
    min-height: 100vh;
    font-family: 'Open Sans', sans-serif;
}

/* ══════════ LEFT PANEL ══════════ */
.bank-left-panel {
    width: 380px;
    flex-shrink: 0;
    background: linear-gradient(160deg, var(--bank-primary-dk) 0%, var(--bank-primary-dkr) 60%, #1e4a39 100%);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: stretch;
}

@media (max-width: 991px) { .bank-left-panel { display: none; } }

.bank-geo-bg {
    position: absolute;
    inset: 0;
    width: 100%; height: 100%;
    pointer-events: none;
}

.bank-left-inner {
    position: relative;
    z-index: 1;
    padding: 40px 36px;
    display: flex;
    flex-direction: column;
    gap: 32px;
    width: 100%;
}

.bank-logo-wrap { margin-bottom: 4px; }
.bank-logo { max-height: 46px; filter: brightness(0) invert(1); opacity: .92; }

/* shield hero */
.bank-left-hero { color: var(--bank-white); }
.bank-shield-icon {
    width: 56px; height: 56px;
    background: rgba(110,180,148,.22);
    border: 1px solid rgba(159,205,181,.45);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 26px;
    color: #c8ead8;
    margin-bottom: 16px;
}
.bank-hero-title { font-size: 22px; font-weight: 700; line-height: 1.3; margin-bottom: 8px; }
.bank-hero-sub   { font-size: 13px; color: rgba(255,255,255,.60); line-height: 1.6; }

/* trust badges */
.bank-trust-badges { display: flex; flex-direction: column; gap: 10px; }
.trust-badge {
    display: flex; align-items: center; gap: 10px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.10);
    border-radius: 8px;
    padding: 10px 14px;
    color: rgba(255,255,255,.80);
    font-size: 12px;
    font-weight: 600;
}
.trust-badge i { color: var(--bank-primary-lt); font-size: 16px; flex-shrink: 0; }

/* step guide */
.bank-step-guide { display: flex; flex-direction: column; }
.step-guide-connector {
    width: 1px; height: 22px;
    background: rgba(255,255,255,.15);
    margin-left: 19px;
}
.step-guide-item {
    display: flex; align-items: center; gap: 12px;
    color: rgba(255,255,255,.45);
    transition: color var(--bank-transition);
    cursor: default;
}
.step-guide-item.active-guide { color: var(--bank-white); }
.step-guide-dot {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.18);
    display: flex; align-items: center; justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
    transition: background var(--bank-transition), border-color var(--bank-transition);
}
.step-guide-item.active-guide .step-guide-dot {
    background: var(--bank-primary);
    border-color: var(--bank-primary);
    color: #fff;
}
.step-guide-item.completed-guide .step-guide-dot {
    background: rgba(110,180,148,.25);
    border-color: var(--bank-primary);
    color: var(--bank-primary-lt);
}
.step-guide-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; opacity: .6; }
.step-guide-name  { font-size: 13px; font-weight: 600; }

/* ══════════ RIGHT PANEL ══════════ */
.bank-right-panel {
    flex: 1;
    background: var(--bank-primary-bg);
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 24px;
    overflow-y: auto;
}

/* disabled notice */
.bank-disabled-notice {
    background: #FEF3C7; border: 1px solid #F59E0B;
    border-radius: var(--bank-radius);
    padding: 12px 16px; margin-bottom: 20px;
    color: #92400E; font-size: 13px;
    display: flex; align-items: center; gap: 8px;
    width: 100%; max-width: 640px;
}

/* ── Progress bar ── */
.bank-progress-bar-wrap { width: 100%; max-width: 640px; margin-bottom: 28px; }

.bank-progress-track {
    height: 4px; background: var(--bank-gray-200);
    border-radius: 2px; margin-bottom: 20px;
    overflow: hidden;
}
.bank-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--bank-primary-dkr) 0%, var(--bank-primary) 100%);
    border-radius: 2px;
    transition: width .4s ease;
}

.bank-progress-steps {
    display: flex; align-items: center;
}
.bank-progress-step {
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    flex-shrink: 0;
}
.bps-circle {
    width: 36px; height: 36px;
    border-radius: 50%;
    border: 2px solid var(--bank-gray-200);
    background: var(--bank-white);
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700;
    color: var(--bank-primary-lt);
    position: relative;
    transition: all var(--bank-transition);
}
.bps-num  { transition: opacity var(--bank-transition); }
.bps-check { position: absolute; opacity: 0; font-size: 14px; transition: opacity var(--bank-transition); }

.bank-progress-step.active .bps-circle {
    border-color: var(--bank-primary-dk);
    background: var(--bank-primary-dk);
    color: var(--bank-white);
    box-shadow: 0 0 0 4px rgba(110,180,148,.20);
}
.bank-progress-step.completed .bps-circle {
    border-color: var(--bank-primary);
    background: var(--bank-primary);
    color: var(--bank-white);
}
.bank-progress-step.completed .bps-num   { opacity: 0; }
.bank-progress-step.completed .bps-check { opacity: 1; }

.bps-label {
    font-size: 10px; font-weight: 600;
    text-transform: uppercase; letter-spacing: .5px;
    color: var(--bank-primary-lt);
    white-space: nowrap;
}
.bank-progress-step.active    .bps-label { color: var(--bank-primary-dk); }
.bank-progress-step.completed .bps-label { color: var(--bank-primary); }

.bps-line {
    flex: 1; height: 2px;
    background: var(--bank-gray-200);
    margin: 0 6px;
    margin-bottom: 22px;
    transition: background var(--bank-transition);
}
.bps-line.completed { background: var(--bank-primary); }

/* ── Form card ── */
.bank-form-card {
    width: 100%; max-width: 640px;
    background: var(--bank-white);
    border-radius: 16px;
    box-shadow: var(--bank-shadow);
    border: 1px solid var(--bank-gray-200);
    padding: 36px 40px;
    overflow: hidden;
    position: relative;
}
/* subtle top accent stripe */
.bank-form-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--bank-primary-dkr), var(--bank-primary), var(--bank-primary-lt));
    border-radius: 16px 16px 0 0;
}
@media (max-width: 600px) { .bank-form-card { padding: 24px 18px; } }

/* pane transitions */
.bank-pane { display: none; animation: paneIn .3s ease; }
.bank-pane.active { display: block; }
@keyframes paneIn {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* pane header */
.bank-pane-header {
    display: flex; align-items: flex-start; gap: 14px;
    margin-bottom: 28px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--bank-gray-100);
}
.bank-pane-icon {
    width: 46px; height: 46px; flex-shrink: 0;
    background: linear-gradient(135deg, var(--bank-primary-dkr) 0%, var(--bank-primary-dk) 100%);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: var(--bank-white);
}
.bank-pane-title { font-size: 17px; font-weight: 700; color: var(--bank-primary-dkr); margin: 0 0 4px; }
.bank-pane-sub   { font-size: 12px; color: #7aaa96; margin: 0; line-height: 1.5; }

/* field groups */
.bank-field-row {
    display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
}
@media (max-width: 520px) { .bank-field-row { grid-template-columns: 1fr; } }

.bank-field-group { margin-bottom: 20px; }

.bank-label {
    display: block;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .8px;
    color: var(--bank-primary-dkr);
    margin-bottom: 7px;
}

/* inputs */
.bank-input-wrap {
    display: flex; align-items: center;
    border: 1.5px solid var(--bank-gray-200);
    border-radius: var(--bank-radius);
    background: var(--bank-white);
    transition: border-color var(--bank-transition), box-shadow var(--bank-transition);
    overflow: hidden;
}
.bank-input-wrap:focus-within {
    border-color: var(--bank-primary);
    box-shadow: 0 0 0 3px var(--bank-primary-rim);
}
.bank-input-icon {
    width: 42px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; color: var(--bank-primary);
    border-right: 1.5px solid var(--bank-gray-200);
}
.bank-input {
    flex: 1; border: none; outline: none;
    padding: 11px 14px;
    font-size: 14px; color: var(--bank-primary-dkr);
    background: transparent;
    min-width: 0;
}
.bank-input::placeholder { color: var(--bank-primary-lt); }

/* eye button */
.bank-eye-btn {
    width: 42px; flex-shrink: 0;
    border: none; background: transparent; cursor: pointer;
    font-size: 16px; color: var(--bank-primary);
    display: flex; align-items: center; justify-content: center;
    border-left: 1.5px solid var(--bank-gray-200);
    height: 100%;
    transition: color var(--bank-transition);
}
.bank-eye-btn:hover { color: var(--bank-primary-dk); }

/* select */
.bank-select-wrap {
    position: relative;
    border: 1.5px solid var(--bank-gray-200);
    border-radius: var(--bank-radius);
    background: var(--bank-white);
    overflow: hidden;
    transition: border-color var(--bank-transition), box-shadow var(--bank-transition);
}
.bank-select-wrap:focus-within {
    border-color: var(--bank-primary);
    box-shadow: 0 0 0 3px var(--bank-primary-rim);
}
.bank-select {
    width: 100%; border: none; outline: none;
    padding: 11px 38px 11px 14px;
    font-size: 14px; color: var(--bank-primary-dkr);
    background: transparent; appearance: none; cursor: pointer;
}
.bank-select-arrow {
    position: absolute; right: 12px; top: 50%;
    transform: translateY(-50%);
    font-size: 14px; color: var(--bank-primary);
    pointer-events: none;
}

/* phone wrap */
.bank-phone-wrap { gap: 0; }
.bank-phone-code {
    padding: 0 12px;
    font-size: 13px; font-weight: 700;
    color: var(--bank-primary-dk);
    border-right: 1.5px solid var(--bank-gray-200);
    white-space: nowrap;
    display: flex; align-items: center;
}
.bank-input-phone { padding-left: 12px; }

/* hint */
.bank-hint { font-size: 11px; color: var(--bank-gray-400); margin-top: 5px; display: block; }

/* toggle switch */
.bank-toggle-row { margin-bottom: 20px; }
.bank-toggle-label {
    display: flex; align-items: center; gap: 10px;
    font-size: 13px; font-weight: 600; color: var(--bank-primary-dk);
    cursor: pointer; user-select: none;
}
.bank-toggle-switch { position: relative; width: 40px; height: 22px; flex-shrink: 0; }
.bank-toggle-switch input { opacity: 0; width: 0; height: 0; }
.bank-toggle-slider {
    position: absolute; inset: 0;
    background: var(--bank-gray-200); border-radius: 11px;
    transition: background var(--bank-transition);
    cursor: pointer;
}
.bank-toggle-slider::before {
    content: '';
    position: absolute;
    width: 16px; height: 16px;
    left: 3px; top: 3px;
    background: var(--bank-white);
    border-radius: 50%;
    transition: transform var(--bank-transition);
}
.bank-toggle-switch input:checked + .bank-toggle-slider { background: var(--bank-primary); }
.bank-toggle-switch input:checked + .bank-toggle-slider::before { transform: translateX(18px); }

/* placement box */
.bank-placement-box {
    background: var(--bank-gray-50);
    border: 1px solid var(--bank-gray-200);
    border-radius: var(--bank-radius);
    padding: 16px;
    margin-bottom: 20px;
}

/* password strength */
.pw-strength-wrap { margin-top: 8px; display: flex; align-items: center; gap: 10px; }
.pw-strength-bar { flex: 1; height: 4px; background: var(--bank-gray-100); border-radius: 2px; overflow: hidden; }
.pw-strength-fill { height: 100%; border-radius: 2px; transition: width .3s, background .3s; }
.pw-strength-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; white-space: nowrap; }

/* review card */
.bank-review-card {
    border: 1px solid var(--bank-gray-200);
    border-radius: var(--bank-radius);
    overflow: hidden;
    margin-bottom: 16px;
}
.bank-review-section { padding: 14px 16px; border-bottom: 1px solid var(--bank-gray-100); }
.bank-review-section:last-child { border-bottom: none; }
.bank-review-section-title {
    font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px;
    color: var(--bank-primary-dk); margin-bottom: 10px;
    display: flex; align-items: center; gap: 6px;
}
.bank-review-row { display: flex; justify-content: space-between; padding: 5px 0; }
.bank-review-key { font-size: 12px; color: #7aaa96; }
.bank-review-val { font-size: 13px; font-weight: 600; color: var(--bank-primary-dkr); }

.bank-edit-links { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.bank-edit-link {
    background: none; border: 1px solid var(--bank-gray-200); border-radius: 6px;
    padding: 6px 12px; font-size: 11px; font-weight: 600; color: var(--bank-gray-600);
    cursor: pointer; display: flex; align-items: center; gap: 5px;
    transition: all var(--bank-transition);
}
.bank-edit-link:hover { border-color: var(--bank-primary); color: var(--bank-primary-dk); }

/* agree */
.bank-agree-row { margin: 16px 0; }
.bank-agree-label {
    display: flex; align-items: flex-start; gap: 10px;
    font-size: 13px; color: var(--bank-primary-dk); cursor: pointer;
}
.bank-checkbox { width: 16px; height: 16px; accent-color: var(--bank-primary); flex-shrink: 0; margin-top: 2px; }
.bank-link { color: var(--bank-primary-dk); font-weight: 600; text-decoration: underline; }

/* nav rows */
.bank-nav-row, .bank-submit-row {
    display: flex; justify-content: space-between; align-items: center;
    gap: 12px; margin-top: 8px;
}

/* buttons */
.bank-btn-next {
    display: inline-flex; align-items: center; gap: 8px;
    background: linear-gradient(135deg, var(--bank-primary-dkr) 0%, var(--bank-primary-dk) 100%);
    color: var(--bank-white);
    border: none; border-radius: 8px;
    padding: 11px 24px;
    font-size: 13px; font-weight: 700; letter-spacing: .3px;
    cursor: pointer;
    transition: opacity var(--bank-transition), transform var(--bank-transition), box-shadow var(--bank-transition);
    text-decoration: none;
}
.bank-btn-next:hover {
    opacity: .88;
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(45,99,82,.30);
    color: var(--bank-white);
}

.bank-btn-back {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--bank-white);
    color: var(--bank-primary-dk);
    border: 1.5px solid var(--bank-gray-200);
    border-radius: 8px;
    padding: 11px 20px;
    font-size: 13px; font-weight: 600;
    cursor: pointer;
    transition: all var(--bank-transition);
    text-decoration: none;
}
.bank-btn-back:hover { border-color: var(--bank-primary); color: var(--bank-primary-dk); background: var(--bank-gray-100); }

.bank-btn-submit {
    display: inline-flex; align-items: center; gap: 10px;
    background: linear-gradient(135deg, var(--bank-primary) 0%, var(--bank-primary-lt) 100%);
    color: #fff;
    border: none; border-radius: 8px;
    padding: 13px 30px;
    font-size: 14px; font-weight: 700; letter-spacing: .3px;
    cursor: pointer;
    transition: opacity var(--bank-transition), transform var(--bank-transition), box-shadow var(--bank-transition);
}
.bank-btn-submit:hover {
    opacity: .90;
    transform: translateY(-1px);
    box-shadow: 0 6px 24px rgba(110,180,148,.45);
}

/* login row */
.bank-login-row {
    text-align: center;
    font-size: 13px; color: var(--bank-primary-dk);
    margin-top: 20px;
}

/* footer note */
.bank-footer-note {
    margin-top: 20px;
    font-size: 11px; color: var(--bank-primary-dk);
    display: flex; align-items: center; gap: 6px;
    opacity: .7;
}

/* modal */
.bank-modal { border-radius: 14px; overflow: hidden; border: none; }
.bank-modal-header { background: var(--bank-primary-dkr); color: var(--bank-white); }
.bank-modal-icon { font-size: 42px; color: var(--bank-primary); }

/* form-disabled overlay */
.form-disabled { pointer-events: none; filter: grayscale(.4) opacity(.7); }

/* geo loader hint */
.reg-geo-loader { color: var(--bank-primary-dk); font-size: 11px; margin-top: 5px; display: block; }
.reg-geo-loader i { margin-right: 4px; }

/* select2 override inside bank */
.bank-form-card .select2-container { width: 100% !important; }
.bank-form-card .select2-container--default .select2-selection--single {
    border: none; border-radius: 0; height: auto;
    background: transparent; outline: none; box-shadow: none;
    padding: 11px 14px; font-size: 14px; line-height: 1.4;
}
.bank-form-card .select2-container--default .select2-selection--single .select2-selection__rendered {
    padding: 0; line-height: 1.4; color: var(--bank-primary-dkr);
}
.bank-form-card .select2-container--default .select2-selection--single .select2-selection__arrow { display: none; }
.bank-form-card .select2-container .select2-selection--single { height: auto; }
</style>
@endpush

@push('script')
<script>
(function($) {
    "use strict";

    /* ── Country / Mobile code init ── */
    @if($mobileCode)
        $(`option[data-code={{ $mobileCode }}]`).attr('selected','');
    @endif

    function syncMobileCode() {
        var $sel = $('select[name=country] :selected');
        $('input[name=mobile_code]').val($sel.data('mobile_code'));
        $('input[name=country_code]').val($sel.data('code'));
        $('.mobile-code').text('+' + $sel.data('mobile_code'));
    }
    syncMobileCode();
    $('select[name=country]').on('change', function() {
        syncMobileCode();
        fetchRegStates($(this).val());
        var v = $('[name=mobile]').val();
        if (v) checkUser(v, 'mobile');
    });

    /* ── Cascading Geo Selects ── */
    var GEO_API = 'https://countriesnow.space/api/v0.1/countries';

    function buildRegGeoOptions(placeholder, items, selected) {
        var html = '<option value="">' + placeholder + '</option>';
        $.each(items, function(_, item) {
            var sel = (selected && item === selected) ? ' selected' : '';
            html += '<option value="' + item + '"' + sel + '>' + item + '</option>';
        });
        return html;
    }

    function fetchRegStates(country, preState, preCity) {
        if (!country) return;
        var $st = $('[name=state]');
        var $ct = $('[name=city]');
        $st.prop('disabled', true).html('<option value="">Loading states…</option>');
        $ct.prop('disabled', true).html('<option value="">— Select a state first —</option>');
        $('#regStateLoader').show();
        $.ajax({
            url: GEO_API + '/states', method: 'POST', contentType: 'application/json',
            data: JSON.stringify({ country: country }),
            success: function(res) {
                $('#regStateLoader').hide();
                if (res && !res.error && res.data && res.data.states) {
                    var names = $.map(res.data.states, function(s) { return s.name; });
                    $st.html(buildRegGeoOptions('— Select state —', names, preState)).prop('disabled', false);
                    if (preState) { fetchRegCities(country, preState, preCity); }
                    else { $ct.prop('disabled', false); }
                } else {
                    $st.html('<option value="">— Could not load states —</option>').prop('disabled', false);
                    $ct.prop('disabled', false);
                }
            },
            error: function() {
                $('#regStateLoader').hide();
                $st.html('<option value="">— Failed to load states —</option>').prop('disabled', false);
                $ct.prop('disabled', false);
            }
        });
    }

    function fetchRegCities(country, state, preCity) {
        if (!country || !state) return;
        var $ct = $('[name=city]');
        $ct.prop('disabled', true).html('<option value="">Loading cities…</option>');
        $('#regCityLoader').show();
        $.ajax({
            url: GEO_API + '/state/cities', method: 'POST', contentType: 'application/json',
            data: JSON.stringify({ country: country, state: state }),
            success: function(res) {
                $('#regCityLoader').hide();
                if (res && !res.error && $.isArray(res.data) && res.data.length) {
                    $ct.html(buildRegGeoOptions('— Select city / LGA —', res.data, preCity)).prop('disabled', false);
                } else {
                    $ct.html('<option value="">— No city data available —</option>').prop('disabled', false);
                }
            },
            error: function() {
                $('#regCityLoader').hide();
                $ct.html('<option value="">— Failed to load cities —</option>').prop('disabled', false);
            }
        });
    }

    $(document).on('change', '[name=state]', function() {
        var country = $('[name=country]').val();
        var state   = $(this).val();
        if (state) { fetchRegCities(country, state); }
        else { $('[name=city]').prop('disabled', true).html('<option value="">— Select a state first —</option>'); }
    });

    // Initialise geo dropdowns on page load (Nigeria default + restore old() values on validation failure)
    var _regInitCountry = $('[name=country]').val();
    var _regOldState    = '{{ old("state") }}';
    var _regOldCity     = '{{ old("city") }}';
    if (_regInitCountry) {
        fetchRegStates(_regInitCountry, _regOldState || null, _regOldCity || null);
    }

    /* ── Referral AJAX ── */
    $('#referenceBy').on('keyup', function() {
        $.post("{{ route('check.referral') }}", {
            username: $(this).val(), _token: "{{ csrf_token() }}"
        }, function(data) { $("#ref").html(data.msg); });
    });

    /* ── Parent Placement ── */
    $('#sponsorBy').on('blur', function() {
        $.post("{{ route('check.parentMatrix') }}", {
            username: $(this).val(), _token: "{{ csrf_token() }}"
        }, function(data) {
            $('select[name=position]').attr('disabled', !data.success);
            $("#parentMsg").html(data.msg);
        });
    });

    $(document).on('change', '#position', function() {
        $.post("{{ route('get.user.position') }}", {
            parent_id: $('#parent_id').val(),
            position:  $(this).val(),
            _token:    "{{ csrf_token() }}"
        }, function(data) {
            if (!data.success && document.getElementById("ref_name")) document.getElementById("ref_name").focus();
            $("#position-test").html(data.msg);
        });
    });

    @if (old('position'))
        $('select[name=position]').val('{{ old('position') }}');
    @endif

    /* ── Duplicate user check ── */
    function checkUser(value, name) {
        var data = { _token: '{{ csrf_token() }}' };
        if (name === 'username') { data.username = value; }
        if (name === 'mobile')   { data.mobile = value; data.mobile_code = $('.mobile-code').text().substr(1); }
        $.post('{{ route('user.checkUser') }}', data, function(r) {
            if (r.data != false) { $(`.${r.type}Exist`).text(`${r.field} already exist`); }
            else                 { $(`.${r.type}Exist`).text(''); }
        });
    }

    $(document).on('focusout', '.checkUser', function() {
        var value = $(this).val(), name = $(this).attr('name');
        if (name === 'email') {
            $.post('{{ route('user.checkUser') }}', { email: value, _token: '{{ csrf_token() }}' }, function(r) {
                if (r.data != false) $('#existModalCenter').modal('show');
            });
        } else {
            checkUser(value, name);
        }
    });

    /* ── Password eye toggle ── */
    $(document).on('click', '.bank-eye-btn', function() {
        var target = $(this).data('target');
        // select by name so it works even after the global id-rename in app.blade.php
        var $inp = $('[name="' + target + '"]');
        var $ico = $('#' + target + '-eye-icon');
        if ($inp.attr('type') === 'password') {
            $inp.attr('type', 'text');
            $ico.removeClass('la-eye').addClass('la-eye-slash');
        } else {
            $inp.attr('type', 'password');
            $ico.removeClass('la-eye-slash').addClass('la-eye');
        }
    });

    /* ── Password strength ── */
    $('#password').on('input', function() {
        var v = $(this).val();
        if (!v) { $('#pwStrengthWrap').hide(); return; }
        $('#pwStrengthWrap').show();
        var score = 0;
        if (v.length >= 8)             score++;
        if (/[A-Z]/.test(v))           score++;
        if (/[0-9]/.test(v))           score++;
        if (/[^A-Za-z0-9]/.test(v))    score++;
        var configs = [
            { w:'15%',  bg:'#EF4444', lbl:'Weak',   col:'#EF4444' },
            { w:'40%',  bg:'#F97316', lbl:'Fair',    col:'#F97316' },
            { w:'70%',  bg:'#EAB308', lbl:'Good',    col:'#EAB308' },
            { w:'100%', bg:'#16A34A', lbl:'Strong',  col:'#16A34A' },
        ];
        var c = configs[Math.max(0, score - 1)];
        $('#pwFill').css({ width: c.w, background: c.bg });
        $('#pwLabel').text(c.lbl).css('color', c.col);
    });

    /* ── Password match ── */
    $(document).on('input focusout', '[name="password_confirmation"]', function() {
        var pw  = $('[name="password"]').val();
        var cpw = $(this).val();
        if (!pw || !cpw) { $('#passwordMatchMessage').text(''); return; }
        if (pw === cpw) {
            $('#passwordMatchMessage')
                .removeClass('text--danger').addClass('text--success')
                .html('<i class="las la-check-circle"></i> Passwords match');
        } else {
            $('#passwordMatchMessage')
                .removeClass('text--success').addClass('text--danger')
                .html('<i class="las la-times-circle"></i> Passwords do not match');
        }
    });

    /* ── Additional placement toggle ── */
    $('#additionalFields').hide();
    $('#showDetailsCheckbox').on('change', function() {
        $('#additionalFields').toggle($(this).is(':checked'));
    });

    /* ══════════════════════
       WIZARD NAVIGATION
    ══════════════════════ */
    var currentStep = 1;

    function showStep(step) {
        /* panes */
        $('.bank-pane').removeClass('active');
        $('#step-' + step).addClass('active');

        /* top progress dots */
        $('.bank-progress-step').each(function() {
            var s = parseInt($(this).data('step'));
            $(this).removeClass('active completed');
            if (s === step)    $(this).addClass('active');
            if (s < step)      $(this).addClass('completed');
        });

        /* connector lines */
        $('.bps-line').each(function(i) {
            $(this).toggleClass('completed', i < step - 1);
        });

        /* progress fill */
        var pct = [25, 50, 75, 100][step - 1];
        $('#progressFill').css('width', pct + '%');

        /* left-panel step guide */
        $('.step-guide-item').each(function() {
            var g = parseInt($(this).data('guide'));
            $(this).removeClass('active-guide completed-guide');
            if (g === step) $(this).addClass('active-guide');
            if (g < step)   $(this).addClass('completed-guide');
        });

        currentStep = step;
        if (step === 4) populateReview();

        /* scroll right panel to top */
        $('.bank-right-panel')[0].scrollTo({ top: 0, behavior: 'smooth' });
    }

    /* ── Validation per step ── */
    function validateStep(goingTo) {
        var ok = true;

        if (goingTo == 2) {
            /* nothing strictly required on step 1 */
        }

        if (goingTo == 3) {
            var un = $('input[name=username]').val();
            var em = $('input[name=email]').val();
            var pw = $('[name="password"]').val();
            var cp = $('[name="password_confirmation"]').val();
            if (!un) { notify('error', '{{ __("Please enter a username") }}'); ok = false; }
            else if ($('.usernameExist').text()) { notify('error', '{{ __("Username already taken") }}'); ok = false; }
            if (!em) { notify('error', '{{ __("Please enter your email") }}'); ok = false; }
            if (!pw)  { notify('error', '{{ __("Please enter a password") }}'); ok = false; }
            else if (pw != cp) { notify('error', '{{ __("Passwords do not match") }}'); ok = false; }
        }

        if (goingTo == 4) {
            var fields = [
                ['firstname', 'First name'],
                ['lastname',  'Last name'],
                ['address',   'Address'],
                ['state',     'State'],
                ['city',      'City'],
            ];
            for (var i = 0; i < fields.length; i++) {
                if (!$('[name=' + fields[i][0] + ']').val()) {
                    notify('error', fields[i][1] + ' {{ __("is required") }}');
                    ok = false; break;
                }
            }
            if (!$('[name=mobile]').val()) {
                notify('error', '{{ __("Mobile number is required") }}'); ok = false;
            }
        }

        return ok;
    }

    /* ── Review summary ── */
    function populateReview() {
        $('#rv-referral').text($('[name=referBy]').val() || '—');
        $('#rv-username').text($('[name=username]').val() || '—');
        $('#rv-email').text($('[name=email]').val() || '—');
        var fn = $('[name=firstname]').val(), ln = $('[name=lastname]').val();
        $('#rv-name').text(fn || ln ? (fn + ' ' + ln).trim() : '—');
        $('#rv-country').text($('select[name=country] :selected').text().trim() || '—');
        var code = $('.mobile-code').text().trim();
        var mob  = $('[name=mobile]').val();
        $('#rv-mobile').text(mob ? code + ' ' + mob : '—');
        var city  = $('[name=city]').val();
        var state = $('[name=state]').val();
        $('#rv-location').text((city && state) ? city + ', ' + state : (city || state || '—'));
    }

    /* ── Button handlers ── */
    $(document).on('click', '.btn-next', function() {
        var next = parseInt($(this).data('next'));
        if (validateStep(next)) showStep(next);
    });

    $(document).on('click', '.btn-prev', function() {
        showStep(parseInt($(this).data('prev')));
    });

    @if (!gs('registration'))
        notify('warning', 'Registration is currently disabled');
    @endif

    /* init */
    showStep(1);

})(jQuery);
</script>
@endpush

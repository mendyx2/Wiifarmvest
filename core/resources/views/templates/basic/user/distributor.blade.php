@extends($activeTemplate . 'layouts.master')
@section('content')

<div class="dist-page">

    {{-- ── Page header ── --}}
    <div class="dist-page-header">
        <div class="dist-page-header-inner">
            <div class="dist-page-header-icon">
                <i class="las la-user-plus"></i>
            </div>
            <div>
                <h4 class="dist-page-title">@lang('Add Distributor')</h4>
                <p class="dist-page-sub">@lang('Register a new distributor under your network. Registration cost will be deducted from your VISA wallet.')</p>
            </div>
        </div>
        {{-- VISA balance pill --}}
        <div class="dist-visa-pill">
            <i class="las la-wallet"></i>
            <span class="dist-visa-label">VISA Wallet</span>
            <strong class="dist-visa-amount" id="visaPillBalance">
                {{ gs('cur_sym') }}{{ showAmount(auth()->user()->visa ?? 0, currencyFormat: false) }}
            </strong>
        </div>
    </div>

    {{-- ── Two-column layout ── --}}
    <div class="dist-body">

        {{-- ── LEFT: Step guide ── --}}
        <div class="dist-left-panel">
            <div class="dist-step-guide">
                @php
                    $dSteps = [
                        1 => ['icon' => 'las la-user-friends', 'label' => 'Step 1', 'name'  => 'Referral & Project'],
                        2 => ['icon' => 'las la-key',          'label' => 'Step 2', 'name'  => 'Credentials'],
                        3 => ['icon' => 'las la-address-card', 'label' => 'Step 3', 'name'  => 'Personal Details'],
                        4 => ['icon' => 'las la-clipboard-check','label'=> 'Step 4', 'name' => 'Review & Submit'],
                    ];
                @endphp
                @foreach($dSteps as $n => $s)
                    <div class="dist-guide-item {{ $n === 1 ? 'active' : '' }}" data-guide="{{ $n }}">
                        <div class="dist-guide-dot"><i class="{{ $s['icon'] }}"></i></div>
                        <div class="dist-guide-text">
                            <span class="dist-guide-label">{{ $s['label'] }}</span>
                            <span class="dist-guide-name">{{ $s['name'] }}</span>
                        </div>
                    </div>
                    @if($n < 4)
                    <div class="dist-guide-connector"></div>
                    @endif
                @endforeach
            </div>

            {{-- Cost info box --}}
            <div class="dist-info-box" id="distCostBox" style="display:none;">
                <div class="dist-info-box-title"><i class="las la-info-circle"></i> @lang('Project Cost')</div>
                <div class="dist-info-box-row">
                    <span>@lang('Project')</span>
                    <strong id="ibProjectName">—</strong>
                </div>
                <div class="dist-info-box-row">
                    <span>@lang('Cost')</span>
                    <strong id="ibCost">—</strong>
                </div>
                <div class="dist-info-box-row">
                    <span>@lang('Your VISA')</span>
                    <strong id="ibBalance">—</strong>
                </div>
                <div class="dist-info-box-status" id="ibStatus"></div>
            </div>
        </div>

        {{-- ── RIGHT: Form ── --}}
        <div class="dist-right-panel">

            {{-- Progress bar --}}
            <div class="dist-progress-wrap">
                <div class="dist-progress-track">
                    <div class="dist-progress-fill" id="distProgressFill" style="width:25%"></div>
                </div>
                <div class="dist-progress-steps">
                    @foreach([1=>'Referral', 2=>'Credentials', 3=>'Personal', 4=>'Finish'] as $n => $lbl)
                    <div class="dist-progress-step {{ $n === 1 ? 'active' : '' }}" data-step="{{ $n }}">
                        <div class="dist-step-circle">
                            <span class="dist-step-num">{{ $n }}</span>
                            <i class="las la-check dist-step-check"></i>
                        </div>
                        <span class="dist-step-label">{{ $lbl }}</span>
                    </div>
                    @if($n < 4)
                    <div class="dist-step-line"></div>
                    @endif
                    @endforeach
                </div>
            </div>

            {{-- Form card --}}
            <div class="dist-form-card">
                <form method="POST" action="{{ route('user.distributor.store') }}" id="distForm" class="verify-gcaptcha disableSubmission">
                    @csrf

                    {{-- ── STEP 1: Referral & Project ── --}}
                    <div class="dist-pane active" id="dist-step-1">
                        <div class="dist-pane-header">
                            <div class="dist-pane-icon"><i class="las la-user-friends"></i></div>
                            <div>
                                <h5 class="dist-pane-title">@lang('Referral & Project')</h5>
                                <p class="dist-pane-sub">@lang('Enter the sponsor and select the project package.')</p>
                            </div>
                        </div>

                        <div class="dist-field-group">
                            <label class="dist-label">@lang('Sponsor Username')</label>
                            <div class="dist-input-wrap">
                                <span class="dist-input-icon"><i class="las la-at"></i></span>
                                <input class="dist-input" name="referBy" type="text"
                                    value="{{ old('referBy', auth()->user()->username) }}"
                                    placeholder="@lang('Sponsor username')">
                            </div>
                            <small class="dist-hint">@lang('Leave as your own username to be the direct sponsor')</small>
                        </div>

                        {{-- Placement toggle --}}
                        @php
                            // Auto-open placement box when pre-filled from binary list
                            $hasPrePlacement = !empty($preParent) || !empty($prePosition);
                        @endphp

                        <div class="dist-toggle-row">
                            <label class="dist-toggle-label" for="distPlacementToggle">
                                <div class="dist-toggle-switch">
                                    <input type="checkbox" id="distPlacementToggle" {{ $hasPrePlacement ? 'checked' : '' }}>
                                    <span class="dist-toggle-slider"></span>
                                </div>
                                @lang('Specify Placement Position')
                            </label>
                        </div>

                        <div id="distPlacementFields"
                             style="{{ $hasPrePlacement ? 'display:block;' : 'display:none;' }}"
                             class="dist-placement-box">

                            {{-- Pre-placement notice --}}
                            @if($hasPrePlacement)
                            <div class="dist-pre-notice">
                                <i class="las la-info-circle"></i>
                                Placement pre-filled from your binary tree. You may adjust if needed.
                            </div>
                            @endif

                            <div class="dist-field-group">
                                <label class="dist-label">@lang('Parent Username')</label>
                                <div class="dist-input-wrap">
                                    <span class="dist-input-icon"><i class="las la-sitemap"></i></span>
                                    <input type="text" class="dist-input" id="distParent" name="parent"
                                        value="{{ old('parent', $preParent ?? '') }}"
                                        placeholder="@lang('Parent username')">
                                </div>
                                <small class="text--danger" id="distParentMsg"></small>
                            </div>

                            <div class="dist-field-group">
                                <label class="dist-label">@lang('Position')</label>
                                <div class="dist-select-wrap">
                                    <select class="dist-select" name="position" id="distPosition">
                                        <option value="">@lang('Select position')</option>
                                        <option value="left"
                                            @selected(old('position', $prePosition ?? '') === 'left')>
                                            @lang('Left Child')
                                        </option>
                                        <option value="right"
                                            @selected(old('position', $prePosition ?? '') === 'right')>
                                            @lang('Right Child')
                                        </option>
                                    </select>
                                    <i class="las la-chevron-down dist-select-arrow"></i>
                                </div>
                            </div>
                        </div>

                        {{-- Project selection --}}
                        <div class="dist-field-group">
                            <label class="dist-label">@lang('Select Project') <span class="text--danger">*</span></label>
                            <div class="dist-select-wrap">
                                <select class="dist-select" name="project_id" id="distProject" required>
                                    <option value="">@lang('Choose a project package')</option>
                                    @foreach($projects as $project)
                                    <option value="{{ $project->id }}"
                                        data-amount="{{ $project->amount }}"
                                        @selected(old('project_id') == $project->id)>
                                        {{ $project->title }} &mdash; {{ showAmount($project->amount) }}
                                    </option>
                                    @endforeach
                                </select>
                                <i class="las la-chevron-down dist-select-arrow"></i>
                            </div>
                            <div id="distVisaStatus" class="dist-visa-status" style="display:none;"></div>
                        </div>

                        <div class="dist-nav-row">
                            <div></div>
                            <button type="button" class="dist-btn-next dist-btn-next-js" data-next="2">
                                @lang('Continue') <i class="las la-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    {{-- ── STEP 2: Credentials ── --}}
                    <div class="dist-pane" id="dist-step-2">
                        <div class="dist-pane-header">
                            <div class="dist-pane-icon"><i class="las la-key"></i></div>
                            <div>
                                <h5 class="dist-pane-title">@lang('Account Credentials')</h5>
                                <p class="dist-pane-sub">@lang('Set up login credentials for the new distributor.')</p>
                            </div>
                        </div>

                        <div class="dist-field-group">
                            <label class="dist-label">@lang('Username')</label>
                            <div class="dist-input-wrap">
                                <span class="dist-input-icon"><i class="las la-user"></i></span>
                                <input type="text" class="dist-input dist-checkuser" name="username"
                                    value="{{ old('username') }}" placeholder="@lang('Choose a username')">
                            </div>
                            <small class="text--danger dist-username-exist"></small>
                        </div>

                        <div class="dist-field-group">
                            <label class="dist-label">@lang('Email Address')</label>
                            <div class="dist-input-wrap">
                                <span class="dist-input-icon"><i class="las la-envelope"></i></span>
                                <input type="email" class="dist-input dist-checkuser" name="email"
                                    value="{{ old('email') }}" placeholder="@lang('distributor@email.com')">
                            </div>
                            <small class="dist-email-exist"></small>
                        </div>

                        <div class="dist-field-row">
                            <div class="dist-field-group">
                                <label class="dist-label">@lang('Password')</label>
                                <div class="dist-input-wrap">
                                    <span class="dist-input-icon"><i class="las la-lock"></i></span>
                                    <input type="password" class="dist-input" name="password"
                                        placeholder="@lang('Create password')" required>
                                    <button type="button" class="dist-eye-btn" data-target="password">
                                        <i class="las la-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="dist-field-group">
                                <label class="dist-label">@lang('Confirm Password')</label>
                                <div class="dist-input-wrap">
                                    <span class="dist-input-icon"><i class="las la-lock"></i></span>
                                    <input type="password" class="dist-input" name="password_confirmation"
                                        placeholder="@lang('Repeat password')" required>
                                    <button type="button" class="dist-eye-btn" data-target="password_confirmation">
                                        <i class="las la-eye"></i>
                                    </button>
                                </div>
                                <small id="distPwMatch"></small>
                            </div>
                        </div>

                        <div class="dist-nav-row">
                            <button type="button" class="dist-btn-back dist-btn-back-js" data-prev="1">
                                <i class="las la-arrow-left"></i> @lang('Back')
                            </button>
                            <button type="button" class="dist-btn-next dist-btn-next-js" data-next="3">
                                @lang('Continue') <i class="las la-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    {{-- ── STEP 3: Personal Details ── --}}
                    <div class="dist-pane" id="dist-step-3">
                        <div class="dist-pane-header">
                            <div class="dist-pane-icon"><i class="las la-address-card"></i></div>
                            <div>
                                <h5 class="dist-pane-title">@lang('Personal Details')</h5>
                                <p class="dist-pane-sub">@lang('Fill in the distributor\'s personal and contact information.')</p>
                            </div>
                        </div>

                        <div class="dist-field-row">
                            <div class="dist-field-group">
                                <label class="dist-label">@lang('First Name')</label>
                                <div class="dist-input-wrap">
                                    <span class="dist-input-icon"><i class="las la-user-circle"></i></span>
                                    <input type="text" class="dist-input" name="firstname"
                                        value="{{ old('firstname') }}" placeholder="@lang('First name')" required>
                                </div>
                            </div>
                            <div class="dist-field-group">
                                <label class="dist-label">@lang('Last Name')</label>
                                <div class="dist-input-wrap">
                                    <span class="dist-input-icon"><i class="las la-user-circle"></i></span>
                                    <input type="text" class="dist-input" name="lastname"
                                        value="{{ old('lastname') }}" placeholder="@lang('Last name')" required>
                                </div>
                            </div>
                        </div>

                        <div class="dist-field-row">
                            <div class="dist-field-group">
                                <label class="dist-label">@lang('Country') <span class="text--danger">*</span></label>
                                <div class="dist-select-wrap">
                                    <select name="country" class="dist-select" required>
                                        @php
                                            /* Find Nigeria so it can be pinned to the top */
                                            $ngData = null; $ngCode = null;
                                            foreach ((array) $countries as $_k => $_c) {
                                                if ($_c->country === 'Nigeria') {
                                                    $ngData = $_c; $ngCode = $_k; break;
                                                }
                                            }
                                        @endphp
                                        @if($ngData)
                                        <option data-mobile_code="{{ $ngData->dial_code }}"
                                            value="Nigeria" data-code="{{ $ngCode }}"
                                            @selected(old('country', 'Nigeria') === 'Nigeria')>
                                            Nigeria
                                        </option>
                                        <option value="" disabled>── Other countries ──</option>
                                        @endif
                                        @foreach($countries as $key => $country)
                                        @if($country->country !== 'Nigeria')
                                        <option data-mobile_code="{{ $country->dial_code }}"
                                            value="{{ $country->country }}" data-code="{{ $key }}"
                                            @selected(old('country') == $country->country)>
                                            {{ $country->country }}
                                        </option>
                                        @endif
                                        @endforeach
                                    </select>
                                    <i class="las la-chevron-down dist-select-arrow"></i>
                                </div>
                            </div>
                            <div class="dist-field-group">
                                <label class="dist-label">@lang('Mobile Number')</label>
                                <div class="dist-input-wrap dist-phone-wrap">
                                    <span class="dist-phone-code dist-mobile-code">+1</span>
                                    <input type="hidden" name="mobile_code">
                                    <input type="hidden" name="country_code">
                                    <input type="number" name="mobile" value="{{ old('mobile') }}"
                                        class="dist-input dist-input-phone dist-checkuser" required
                                        placeholder="@lang('Phone number')">
                                </div>
                                <small class="dist-mobile-exist"></small>
                            </div>
                        </div>

                        {{-- State: populated dynamically when country is chosen --}}
                        <div class="dist-field-row">
                            <div class="dist-field-group">
                                <label class="dist-label">@lang('State / Province') <span class="text--danger">*</span></label>
                                <div class="dist-select-wrap dist-geo-wrap">
                                    <select name="state" class="dist-select" required>
                                        <option value="">@lang('— Select a country first —')</option>
                                        @if(old('state'))
                                        <option value="{{ old('state') }}" selected>{{ old('state') }}</option>
                                        @endif
                                    </select>
                                    <i class="las la-map dist-select-arrow"></i>
                                    <span class="dist-geo-loader" id="stateLoader" style="display:none;">
                                        <i class="las la-spinner la-spin"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="dist-field-group">
                                <label class="dist-label">@lang('City / LGA') <span class="text--danger">*</span></label>
                                <div class="dist-select-wrap dist-geo-wrap">
                                    <select name="city" class="dist-select" required>
                                        <option value="">@lang('— Select a state first —')</option>
                                        @if(old('city'))
                                        <option value="{{ old('city') }}" selected>{{ old('city') }}</option>
                                        @endif
                                    </select>
                                    <i class="las la-city dist-select-arrow"></i>
                                    <span class="dist-geo-loader" id="cityLoader" style="display:none;">
                                        <i class="las la-spinner la-spin"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Address — after city/LGA --}}
                        <div class="dist-field-group">
                            <label class="dist-label">@lang('Address') <span class="text--danger">*</span></label>
                            <div class="dist-input-wrap">
                                <span class="dist-input-icon"><i class="las la-map-marker-alt"></i></span>
                                <input type="text" class="dist-input" name="address"
                                    value="{{ old('address') }}" placeholder="@lang('Street address')" required>
                            </div>
                        </div>

                        <div class="dist-nav-row">
                            <button type="button" class="dist-btn-back dist-btn-back-js" data-prev="2">
                                <i class="las la-arrow-left"></i> @lang('Back')
                            </button>
                            <button type="button" class="dist-btn-next dist-btn-next-js" data-next="4">
                                @lang('Continue') <i class="las la-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    {{-- ── STEP 4: Review & Submit ── --}}
                    <div class="dist-pane" id="dist-step-4">
                        <div class="dist-pane-header">
                            <div class="dist-pane-icon"><i class="las la-clipboard-check"></i></div>
                            <div>
                                <h5 class="dist-pane-title">@lang('Review & Submit')</h5>
                                <p class="dist-pane-sub">@lang('Confirm all details before registering the distributor.')</p>
                            </div>
                        </div>

                        <div class="dist-review-card">
                            {{-- Referral & Project --}}
                            <div class="dist-review-section">
                                <div class="dist-review-section-title">
                                    <i class="las la-user-friends"></i> @lang('Referral & Project')
                                </div>
                                <div class="dist-review-row">
                                    <span>@lang('Sponsor')</span>
                                    <strong id="rv-sponsor">—</strong>
                                </div>
                                <div class="dist-review-row">
                                    <span>@lang('Project')</span>
                                    <strong id="rv-project">—</strong>
                                </div>
                                <div class="dist-review-row">
                                    <span>@lang('Parent')</span>
                                    <strong id="rv-parent">NILL</strong>
                                </div>
                                <div class="dist-review-row">
                                    <span>@lang('Position')</span>
                                    <strong id="rv-position">NILL</strong>
                                </div>
                            </div>
                            {{-- Credentials --}}
                            <div class="dist-review-section">
                                <div class="dist-review-section-title">
                                    <i class="las la-key"></i> @lang('Credentials')
                                </div>
                                <div class="dist-review-row">
                                    <span>@lang('Username')</span>
                                    <strong id="rv-username">—</strong>
                                </div>
                                <div class="dist-review-row">
                                    <span>@lang('Email')</span>
                                    <strong id="rv-email">—</strong>
                                </div>
                                <div class="dist-review-row">
                                    <span>@lang('Password')</span>
                                    <strong id="rv-password" style="letter-spacing:2px;">—</strong>
                                </div>
                            </div>
                            {{-- Personal --}}
                            <div class="dist-review-section">
                                <div class="dist-review-section-title">
                                    <i class="las la-address-card"></i> @lang('Personal')
                                </div>
                                <div class="dist-review-row">
                                    <span>@lang('Full Name')</span>
                                    <strong id="rv-name">—</strong>
                                </div>
                                <div class="dist-review-row">
                                    <span>@lang('Country')</span>
                                    <strong id="rv-country">—</strong>
                                </div>
                                <div class="dist-review-row">
                                    <span>@lang('Mobile')</span>
                                    <strong id="rv-mobile">—</strong>
                                </div>
                                <div class="dist-review-row">
                                    <span>@lang('State')</span>
                                    <strong id="rv-state">—</strong>
                                </div>
                                <div class="dist-review-row">
                                    <span>@lang('City / LGA')</span>
                                    <strong id="rv-city">—</strong>
                                </div>
                                <div class="dist-review-row">
                                    <span>@lang('Address')</span>
                                    <strong id="rv-address">—</strong>
                                </div>
                            </div>
                        </div>

                        {{-- Cost deduction notice --}}
                        <div class="dist-cost-notice" id="distCostNotice">
                            <i class="las la-exclamation-circle"></i>
                            <span id="distCostNoticeText">
                                @lang('The project cost will be deducted from your VISA wallet upon submission.')
                            </span>
                        </div>

                        <div class="dist-edit-links">
                            <button type="button" class="dist-edit-link dist-btn-back-js" data-prev="1">
                                <i class="las la-pencil-alt"></i> @lang('Edit Referral')
                            </button>
                            <button type="button" class="dist-edit-link dist-btn-back-js" data-prev="2">
                                <i class="las la-pencil-alt"></i> @lang('Edit Credentials')
                            </button>
                            <button type="button" class="dist-edit-link dist-btn-back-js" data-prev="3">
                                <i class="las la-pencil-alt"></i> @lang('Edit Personal')
                            </button>
                        </div>

                        @php $custom = true; @endphp
                        <div class="mt-3">
                            <x-captcha :custom="$custom" />
                        </div>

                        <div class="dist-submit-row">
                            <button type="button" class="dist-btn-back dist-btn-back-js" data-prev="3">
                                <i class="las la-arrow-left"></i> @lang('Back')
                            </button>
                            <button type="submit" class="dist-btn-submit">
                                <i class="las la-user-plus"></i>
                                @lang('Register Distributor')
                            </button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
(function($) {
    "use strict";

    /* ── Mobile code sync ── */
    @if($mobileCode)
        $(`option[data-code={{ $mobileCode }}]`).attr('selected','');
    @endif

    function syncMobileCode() {
        var $sel = $('select[name=country] :selected');
        $('input[name=mobile_code]').val($sel.data('mobile_code'));
        $('input[name=country_code]').val($sel.data('code'));
        $('.dist-mobile-code').text('+' + $sel.data('mobile_code'));
    }
    syncMobileCode();

    /* ── Country → State → City cascading dropdowns ────────────────────── */
    var GEO_API = 'https://countriesnow.space/api/v0.1/countries';

    function buildGeoOptions(placeholder, items, preSelect) {
        var html = '<option value="">' + placeholder + '</option>';
        $.each(items, function(_, item) {
            var sel = (preSelect && item === preSelect) ? ' selected' : '';
            html += '<option value="' + item + '"' + sel + '>' + item + '</option>';
        });
        return html;
    }

    function fetchStates(country, preSelectState, preSelectCity) {
        if (!country) return;
        var $stateSel = $('[name=state]');
        var $citySel  = $('[name=city]');

        $stateSel.prop('disabled', true).html('<option value="">Loading states…</option>');
        $citySel.prop('disabled', true).html('<option value="">— Select a state first —</option>');
        $('#stateLoader').show();

        $.ajax({
            url: GEO_API + '/states',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ country: country }),
            success: function(res) {
                $('#stateLoader').hide();
                if (res && !res.error && res.data && res.data.states) {
                    var names = $.map(res.data.states, function(s) { return s.name; });
                    $stateSel.html(buildGeoOptions('— Select state —', names, preSelectState))
                             .prop('disabled', false);
                    // If restoring after validation failure, also fetch cities
                    if (preSelectState) {
                        fetchCities(country, preSelectState, preSelectCity);
                    } else {
                        $citySel.prop('disabled', false);
                    }
                } else {
                    $stateSel.html('<option value="">— Could not load states —</option>')
                             .prop('disabled', false);
                    $citySel.prop('disabled', false);
                }
            },
            error: function() {
                $('#stateLoader').hide();
                $stateSel.html('<option value="">— Failed to load states —</option>')
                         .prop('disabled', false);
                $citySel.prop('disabled', false);
            }
        });
    }

    function fetchCities(country, state, preSelectCity) {
        if (!country || !state) return;
        var $citySel = $('[name=city]');

        $citySel.prop('disabled', true).html('<option value="">Loading cities…</option>');
        $('#cityLoader').show();

        $.ajax({
            url: GEO_API + '/state/cities',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ country: country, state: state }),
            success: function(res) {
                $('#cityLoader').hide();
                if (res && !res.error && $.isArray(res.data) && res.data.length) {
                    $citySel.html(buildGeoOptions('— Select city / LGA —', res.data, preSelectCity))
                            .prop('disabled', false);
                } else {
                    $citySel.html('<option value="">— No city data available —</option>')
                            .prop('disabled', false);
                }
            },
            error: function() {
                $('#cityLoader').hide();
                $citySel.html('<option value="">— Failed to load cities —</option>')
                        .prop('disabled', false);
            }
        });
    }

    // Country change: sync mobile code + reload states
    $('select[name=country]').on('change', function() {
        syncMobileCode();
        fetchStates($(this).val());
    });

    // State change: reload cities
    $(document).on('change', '[name=state]', function() {
        var country = $('[name=country]').val();
        var state   = $(this).val();
        if (state) fetchCities(country, state);
        else $('[name=city]').prop('disabled', true)
                             .html('<option value="">— Select a state first —</option>');
    });

    // On page load: initialize state/city for the currently selected country
    // (covers both fresh load and returning from a validation failure)
    var _oldState   = '{{ old("state") }}';
    var _oldCity    = '{{ old("city") }}';
    var _initCountry = $('[name=country]').val();
    if (_initCountry) {
        fetchStates(_initCountry, _oldState || null, _oldCity || null);
    }

    /* Country uses a native styled <select> — no Select2 needed */

    /* ── Placement toggle ── */
    $('#distPlacementToggle').on('change', function() {
        $('#distPlacementFields').toggle($(this).is(':checked'));
    });

    /* ── Parent check (use name selector — app.blade.php overwrites ids) ── */
    $(document).on('blur', '[name=parent]', function() {
        if (!$(this).val()) return;
        $.post("{{ route('check.parentMatrix') }}", {
            username: $(this).val(), _token: "{{ csrf_token() }}"
        }, function(data) {
            $('[name=position]').attr('disabled', !data.success);
            $('#distParentMsg').html(data.msg);
        });
    });

    /* ── Project select → VISA check ── */
    var visaCheckTimeout;
    $(document).on('change', '[name=project_id]', function() {
        var projectId = $(this).val();
        var $status = $('#distVisaStatus');
        var $infoBox = $('#distCostBox');

        if (!projectId) {
            $status.hide();
            $infoBox.hide();
            return;
        }

        clearTimeout(visaCheckTimeout);
        visaCheckTimeout = setTimeout(function() {
            $.post("{{ route('user.distributor.check.visa') }}", {
                project_id: projectId,
                _token: "{{ csrf_token() }}"
            }, function(data) {
                $infoBox.show();
                $('#ibProjectName').text(data.project);
                $('#ibCost').text(data.cost_fmt);
                $('#ibBalance').text(data.balance_fmt);
                // Update pill balance display too
                $('#visaPillBalance').text(data.balance_fmt);

                if (data.success) {
                    $status.removeClass('err').addClass('ok').show()
                        .html('<i class="las la-check-circle"></i> Sufficient balance &mdash; ' + data.balance_fmt + ' available');
                    $('#ibStatus').removeClass('err').addClass('ok')
                        .text('✓ Sufficient balance');
                    $infoBox.css('border-color', '#BBF7D0');
                } else {
                    $status.removeClass('ok').addClass('err').show()
                        .html('<i class="las la-times-circle"></i> Insufficient balance. You have ' + data.balance_fmt + ' but need ' + data.cost_fmt);
                    $('#ibStatus').removeClass('ok').addClass('err')
                        .text('✗ Insufficient balance');
                    $infoBox.css('border-color', '#FECACA');
                }
            });
        }, 300);
    });

    /* ── Duplicate user check ── */
    $(document).on('focusout', '.dist-checkuser', function() {
        var value = $(this).val();
        var name  = $(this).attr('name');
        if (!value) return;

        if (name === 'email') {
            $.post("{{ route('user.checkUser') }}", { email: value, _token: "{{ csrf_token() }}" }, function(r) {
                if (r.data) {
                    // Warning only — not blocking; email need not be unique
                    $('.dist-email-exist').addClass('dist-field-warn').removeClass('text--danger text--success')
                        .html('<i class="las la-exclamation-triangle"></i> Email already registered — you may still proceed');
                } else {
                    $('.dist-email-exist').removeClass('dist-field-warn text--danger text--success').text('');
                }
            });
        } else if (name === 'username') {
            $.post("{{ route('user.checkUser') }}", { username: value, _token: "{{ csrf_token() }}" }, function(r) {
                if (r.data) {
                    // Blocking — username must be unique
                    $('.dist-username-exist').addClass('text--danger').removeClass('dist-field-warn text--success')
                        .text('Username already taken');
                } else {
                    $('.dist-username-exist').removeClass('text--danger dist-field-warn text--success').text('');
                }
            });
        } else if (name === 'mobile') {
            var mobileCode = $('.dist-mobile-code').text().substr(1);
            $.post("{{ route('user.checkUser') }}", { mobile: value, mobile_code: mobileCode, _token: "{{ csrf_token() }}" }, function(r) {
                if (r.data) {
                    // Warning only — mobile need not be unique
                    $('.dist-mobile-exist').addClass('dist-field-warn').removeClass('text--danger text--success')
                        .html('<i class="las la-exclamation-triangle"></i> Mobile already registered — you may still proceed');
                } else {
                    $('.dist-mobile-exist').removeClass('dist-field-warn text--danger text--success').text('');
                }
            });
        }
    });

    /* ── Eye toggle (use name selector — app.blade.php overwrites ids) ── */
    $(document).on('click', '.dist-eye-btn', function() {
        var target = $(this).data('target');
        var $inp   = $('[name="' + target + '"]');
        var $ico   = $(this).find('i');
        if ($inp.attr('type') === 'password') {
            $inp.attr('type', 'text');
            $ico.removeClass('la-eye').addClass('la-eye-slash');
        } else {
            $inp.attr('type', 'password');
            $ico.removeClass('la-eye-slash').addClass('la-eye');
        }
    });

    /* ── Password match ── */
    $(document).on('input focusout', '[name=password_confirmation]', function() {
        var pw  = $('[name=password]').val();
        var cpw = $(this).val();
        if (!pw || !cpw) { $('#distPwMatch').text(''); return; }
        if (pw === cpw) {
            $('#distPwMatch').removeClass('text--danger').addClass('text--success')
                .html('<i class="las la-check-circle"></i> Passwords match');
        } else {
            $('#distPwMatch').removeClass('text--success').addClass('text--danger')
                .html('<i class="las la-times-circle"></i> Passwords do not match');
        }
    });

    /* ══════════════════════
       WIZARD NAVIGATION
    ══════════════════════ */
    var currentStep = 1;

    function showStep(step) {
        $('.dist-pane').removeClass('active');
        $('#dist-step-' + step).addClass('active');

        $('.dist-progress-step').each(function() {
            var s = parseInt($(this).data('step'));
            $(this).removeClass('active completed');
            if (s === step) $(this).addClass('active');
            if (s < step)   $(this).addClass('completed');
        });

        $('.dist-step-line').each(function(i) {
            $(this).toggleClass('completed', i < step - 1);
        });

        var pct = [25, 50, 75, 100][step - 1];
        $('#distProgressFill').css('width', pct + '%');

        $('.dist-guide-item').each(function() {
            var g = parseInt($(this).data('guide'));
            $(this).removeClass('active completed');
            if (g === step) $(this).addClass('active');
            if (g < step)   $(this).addClass('completed');
        });

        currentStep = step;
        if (step === 4) populateReview();

        $('html, body').animate({ scrollTop: $('.dist-right-panel').offset().top - 80 }, 200);
    }

    /* ── Validation ── */
    function validateStep(goingTo) {
        var ok = true;

        if (goingTo === 2) {
            var project = $('select[name=project_id]').val();
            if (!project) {
                notify('error', '{{ __("Please select a project package") }}');
                ok = false;
            } else {
                var $status = $('#distVisaStatus');
                if ($status.hasClass('err')) {
                    notify('error', '{{ __("Insufficient VISA wallet balance for the selected project") }}');
                    ok = false;
                }
            }
        }

        if (goingTo === 3) {
            var un = $('[name=username]').val();
            var em = $('[name=email]').val();
            var pw = $('[name=password]').val();
            var cp = $('[name=password_confirmation]').val();
            if (!un) { notify('error', '{{ __("Please enter a username") }}'); ok = false; }
            else if ($('.dist-username-exist').text()) { notify('error', '{{ __("Username already taken") }}'); ok = false; }
            if (!em) { notify('error', '{{ __("Please enter an email") }}'); ok = false; }
            if (!pw) { notify('error', '{{ __("Please enter a password") }}'); ok = false; }
            else if (pw !== cp) { notify('error', '{{ __("Passwords do not match") }}'); ok = false; }
        }

        if (goingTo === 4) {
            var fields = [['firstname','First name'],['lastname','Last name'],['address','Address'],['state','State'],['city','City']];
            for (var i = 0; i < fields.length; i++) {
                if (!$('[name=' + fields[i][0] + ']').val()) {
                    notify('error', fields[i][1] + ' {{ __("is required") }}');
                    ok = false; break;
                }
            }
            if (!$('[name=mobile]').val()) { notify('error', '{{ __("Mobile number is required") }}'); ok = false; }
        }

        return ok;
    }

    /* ── Review populate ── */
    function populateReview() {
        // Referral & Project
        $('#rv-sponsor').text($('[name=referBy]').val() || '—');
        var $proj = $('select[name=project_id] option:selected');
        $('#rv-project').text($proj.val() ? $proj.text().trim() : '—');

        // Placement (shown only when toggle is on; otherwise NILL)
        var parentVal   = ($('#distPlacementToggle').is(':checked') ? $('[name=parent]').val() : '') || '';
        var positionVal = ($('#distPlacementToggle').is(':checked') ? $('[name=position]').val() : '') || '';
        $('#rv-parent').text(parentVal || 'NILL');
        $('#rv-position').text(positionVal ? positionVal.charAt(0).toUpperCase() + positionVal.slice(1) : 'NILL');

        // Credentials
        $('#rv-username').text($('[name=username]').val() || '—');
        $('#rv-email').text($('[name=email]').val() || '—');
        var pw = $('[name=password]').val();
        $('#rv-password').text(pw ? '●'.repeat(Math.min(pw.length, 10)) : '—');

        // Personal
        var fn = $('[name=firstname]').val(), ln = $('[name=lastname]').val();
        $('#rv-name').text((fn || ln) ? (fn + ' ' + ln).trim() : '—');
        $('#rv-country').text($('select[name=country] option:selected').text().trim() || '—');
        var code = $('.dist-mobile-code').text().trim();
        var mob  = $('[name=mobile]').val();
        $('#rv-mobile').text(mob ? code + ' ' + mob : '—');
        $('#rv-state').text($('[name=state]').val() || '—');
        $('#rv-city').text($('[name=city]').val() || '—');
        $('#rv-address').text($('[name=address]').val() || '—');

        if ($proj.val()) {
            $('#distCostNoticeText').text(
                'Registering on "' + $proj.text().trim() + '" will be deducted from your VISA wallet.'
            );
        }
    }

    /* ── Handlers ── */
    $(document).on('click', '.dist-btn-next-js', function() {
        var next = parseInt($(this).data('next'));
        if (validateStep(next)) showStep(next);
    });
    $(document).on('click', '.dist-btn-back-js', function() {
        showStep(parseInt($(this).data('prev')));
    });

    showStep(1);

})(jQuery);
</script>
@endpush

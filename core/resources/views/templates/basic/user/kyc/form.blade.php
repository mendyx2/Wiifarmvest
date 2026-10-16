@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="kyc-page">
<div class="container">

    {{-- Hero --}}
    <div class="kyc-hero">
        <div class="kyc-hero-icon"><i class="fas fa-shield-alt"></i></div>
        <h2 class="text-white">@lang('KYC Verification')</h2>
        <p>@lang('Verify your identity to unlock full platform features including loan applications.')</p>
        <div class="kyc-steps">
            <div class="kyc-step">
                <div class="kyc-step-num">1</div>
                <div class="kyc-step-label">@lang('Basic Info')</div>
            </div>
            <div class="kyc-step">
                <div class="kyc-step-num">2</div>
                <div class="kyc-step-label">@lang('ID Verification')</div>
            </div>
            <div class="kyc-step">
                <div class="kyc-step-num">3</div>
                <div class="kyc-step-label">@lang('Guarantor')</div>
            </div>
        </div>
    </div>

    {{-- Rejection notice --}}
    @if($user->kyc_rejection_reason)
    <div class="kyc-rejection">
        <div class="kyc-rejection-icon"><i class="fas fa-times-circle"></i></div>
        <div>
            <p class="kyc-rejection-title">@lang('Previous Submission Rejected')</p>
            <p class="kyc-rejection-text">{{ $user->kyc_rejection_reason }}</p>
        </div>
    </div>
    @endif

    <form action="{{ route('user.kyc.submit') }}" method="post" enctype="multipart/form-data" id="kycForm">
        @csrf

        {{-- ── Section 1: Basic Information ── --}}
        @if($form)
        <div class="kyc-section">
            <div class="kyc-section-header">
                <div class="kyc-section-num">1</div>
                <div>
                    <p class="kyc-section-title">@lang('Basic Information')</p>
                    <p class="kyc-section-sub">@lang('Personal details as they appear on your documents')</p>
                </div>
            </div>
            <div class="kyc-section-body">
                <x-viser-form identifier="act" identifierValue="kyc" />
            </div>
        </div>
        @endif

        {{-- ── Section 2: Identity Verification ── --}}
        @php $secNum = $form ? 2 : 1; @endphp
        <div class="kyc-section">
            <div class="kyc-section-header">
                <div class="kyc-section-num">{{ $secNum }}</div>
                <div>
                    <p class="kyc-section-title">@lang('Identity Verification')</p>
                    <p class="kyc-section-sub">@lang('Your NIN and a valid government-issued ID')</p>
                </div>
            </div>
            <div class="kyc-section-body">

                {{-- NIN --}}
                <div class="mb-4">
                    <label class="kyc-label" for="nin">
                        @lang('National Identification Number (NIN)') <span>*</span>
                    </label>
                    <input
                        type="text" id="nin" name="nin"
                        class="kyc-input @error('nin') border-danger @enderror"
                        placeholder="e.g. 12345678901"
                        value="{{ old('nin', $user->nin) }}"
                        maxlength="20" autocomplete="off"
                    >
                    @error('nin')
                        <span class="kyc-error-msg">{{ $message }}</span>
                    @enderror
                    <p class="kyc-hint">@lang('Enter your 11-digit National Identification Number.')</p>
                </div>

                {{-- ID Card Type --}}
                <div class="mb-4">
                    <label class="kyc-label">@lang('ID Card Type') <span>*</span></label>
                    @error('id_card_type')
                        <span class="kyc-error-msg mb-2 d-block">{{ $message }}</span>
                    @enderror
                    @php $selType = old('id_card_type', $user->id_card_type); @endphp
                    <div class="card-type-grid">
                        <label class="card-type-pill {{ $selType === 'driving_license' ? 'active' : '' }}">
                            <input type="radio" name="id_card_type" value="driving_license" {{ $selType === 'driving_license' ? 'checked' : '' }}>
                            <span class="pill-icon">🪪</span>
                            <span class="pill-text">@lang("Driver's License")</span>
                        </label>
                        <label class="card-type-pill {{ $selType === 'international_passport' ? 'active' : '' }}">
                            <input type="radio" name="id_card_type" value="international_passport" {{ $selType === 'international_passport' ? 'checked' : '' }}>
                            <span class="pill-icon">🛂</span>
                            <span class="pill-text">@lang('International Passport')</span>
                        </label>
                        <label class="card-type-pill {{ $selType === 'nin' ? 'active' : '' }}">
                            <input type="radio" name="id_card_type" value="nin" {{ $selType === 'nin' ? 'checked' : '' }}>
                            <span class="pill-icon">🆔</span>
                            <span class="pill-text">@lang('NIN Card')</span>
                        </label>
                    </div>
                </div>

                {{-- ID Card Image --}}
                <div>
                    <label class="kyc-label">
                        @lang('ID Card Image')
                        @if(!$user->id_card_image)<span>*</span>@endif
                    </label>
                    @error('id_card_image')
                        <span class="kyc-error-msg mb-2 d-block">{{ $message }}</span>
                    @enderror

                    @if($user->id_card_image)
                    <div class="kyc-existing-file mb-2">
                        <i class="fas fa-check-circle kyc-existing-icon"></i>
                        <div>
                            <p class="kyc-existing-text">@lang('ID card already on file')</p>
                            <p class="kyc-existing-sub">@lang('Upload a new image only if you want to replace it')</p>
                        </div>
                    </div>
                    @endif

                    <div class="kyc-file-zone" id="fileZone">
                        <input type="file" name="id_card_image" id="idCardFile" accept="image/jpg,image/jpeg,image/png">
                        <div class="kyc-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                        <p class="kyc-upload-title">@lang('Click to upload or drag & drop')</p>
                        <p class="kyc-upload-hint">@lang('JPG, JPEG, PNG — max 2 MB')</p>
                    </div>
                    <div class="kyc-file-preview" id="filePreview">
                        <img id="fileThumb" src="" alt="">
                        <div>
                            <p class="kyc-file-preview-name" id="fileName"></p>
                            <p class="kyc-file-preview-size" id="fileSize"></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- ── Section 3: Guarantor Nomination ── --}}
        <div class="kyc-section">
            <div class="kyc-section-header">
                <div class="kyc-section-num">{{ $secNum + 1 }}</div>
                <div>
                    <p class="kyc-section-title">@lang('Guarantor Nomination')</p>
                    <p class="kyc-section-sub">@lang('Nominate another registered user to vouch for you')</p>
                </div>
            </div>
            <div class="kyc-section-body">

                <input type="hidden" name="guarantor_id" id="guarantorId"
                    value="{{ old('guarantor_id', optional($guarantorRequest)->guarantor_id) }}">

                @error('guarantor_id')
                    <span class="kyc-error-msg mb-2 d-block">{{ $message }}</span>
                @enderror

                <div class="guarantor-search-wrap mb-2">
                    <label class="kyc-label" for="guarantorSearch">@lang('Search by name or username') <span>*</span></label>
                    <div class="kyc-input-icon">
                        <i class="fas fa-search"></i>
                        <input type="text" id="guarantorSearch" class="kyc-input"
                            placeholder="{{ __('Type at least 2 characters…') }}" autocomplete="off">
                    </div>
                    <div class="guarantor-dropdown" id="guarantorDropdown"></div>
                </div>

                {{-- Selected card --}}
                <div class="guarantor-selected" id="guarantorSelected">
                    <div class="g-sel-avatar" id="gSelAvatar"></div>
                    <div>
                        <p class="g-sel-name" id="gSelName"></p>
                        <p class="g-sel-user" id="gSelUser"></p>
                    </div>
                    <span class="g-sel-change" id="gSelChange">@lang('Change')</span>
                </div>

                {{-- Previous request status --}}
                @if($guarantorRequest)
                @php
                    $statusCls   = ['pending' => 'g-pending', 'accepted' => 'g-accepted', 'declined' => 'g-declined'];
                    $statusIcon  = ['pending' => 'fas fa-hourglass-half', 'accepted' => 'fas fa-check-circle', 'declined' => 'fas fa-times-circle'];
                    $statusTitle = ['pending' => 'Awaiting Response', 'accepted' => 'Guarantor Accepted', 'declined' => 'Guarantor Declined'];
                    $statusSubs  = [
                        'pending'  => ':name has been notified and has not yet responded.',
                        'accepted' => ':name has accepted your guarantor request.',
                        'declined' => ':name declined. You may select a different guarantor.',
                    ];
                    $gs    = $guarantorRequest->status;
                    $gNom  = $guarantorRequest->guarantor ? trim($guarantorRequest->guarantor->firstname . ' ' . $guarantorRequest->guarantor->lastname) : 'Guarantor';
                @endphp
                <div class="g-status-card {{ $statusCls[$gs] }}" id="prevGuarantorStatus">
                    <i class="{{ $statusIcon[$gs] }} g-status-icon"></i>
                    <div>
                        <p class="g-status-title">@lang($statusTitle[$gs])</p>
                        <p class="g-status-sub">{{ str_replace(':name', e($gNom), __($statusSubs[$gs])) }}</p>
                    </div>
                </div>
                @endif

                <p class="kyc-hint mt-3">@lang('Your nominated guarantor will receive a request on their dashboard and must accept it before your KYC can be fully approved.')</p>

            </div>
        </div>

        {{-- Submit --}}
        <button type="submit" class="kyc-submit-btn" id="kycSubmitBtn">
            <span class="btn-idle"><i class="fas fa-paper-plane"></i> @lang('Submit KYC Application')</span>
            <span class="btn-spinner"><i class="fas fa-spinner fa-spin"></i> @lang('Submitting…')</span>
        </button>

    </form>
</div>
</div>
@endsection

@push('script')
<script>
$(function () {

    /* ── ID Card Type Pills ── */
    $('.card-type-pill').on('click', function () {
        $('.card-type-pill').removeClass('active');
        $(this).addClass('active');
    });

    /* ── File Upload Preview ── */
    var $zone    = $('#fileZone');
    var $input   = $('#idCardFile');
    var $preview = $('#filePreview');
    var $thumb   = $('#fileThumb');
    var $fname   = $('#fileName');
    var $fsize   = $('#fileSize');

    $zone.on('dragover', function (e) { e.preventDefault(); $(this).addClass('drag-over'); })
         .on('dragleave drop', function (e) { e.preventDefault(); $(this).removeClass('drag-over'); });

    $zone.on('drop', function (e) {
        var file = e.originalEvent.dataTransfer.files[0];
        if (file) showFilePreview(file);
    });

    $input.on('change', function () {
        if (this.files[0]) showFilePreview(this.files[0]);
    });

    function showFilePreview(file) {
        if (!file.type.match('image.*')) return;
        var reader = new FileReader();
        reader.onload = function (e) { $thumb.attr('src', e.target.result); };
        reader.readAsDataURL(file);
        $fname.text(file.name);
        $fsize.text((file.size / 1024).toFixed(1) + ' KB');
        $preview.css('display', 'flex');
    }

    /* ── Guarantor Search ── */
    var $search   = $('#guarantorSearch');
    var $dropdown = $('#guarantorDropdown');
    var $selected = $('#guarantorSelected');
    var $hiddenId = $('#guarantorId');
    var $avatar   = $('#gSelAvatar');
    var $gName    = $('#gSelName');
    var $gUser    = $('#gSelUser');
    var searchTimer;

    @if($guarantorRequest && $guarantorRequest->guarantor)
    @php
        $g = $guarantorRequest->guarantor;
        $gFull = trim($g->firstname . ' ' . $g->lastname);
        $gParts = explode(' ', $gFull);
        $gInit = strtoupper(substr($gParts[0] ?? '', 0, 1) . substr($gParts[1] ?? '', 0, 1));
    @endphp
    (function () {
        selectGuarantor(
            {{ (int)$guarantorRequest->guarantor_id }},
            @json($gFull),
            @json($g->username),
            @json($gInit)
        );
    })();
    @endif

    $search.on('input', function () {
        clearTimeout(searchTimer);
        var q = $.trim($(this).val());
        if (q.length < 2) { $dropdown.hide().empty(); return; }
        searchTimer = setTimeout(function () {
            $.getJSON("{{ route('user.kyc.user.search') }}", { q: q })
             .done(function (data) {
                if (!data.length) {
                    $dropdown.html('<div class="g-no-results">{{ __("No users found") }}</div>').show();
                    return;
                }
                var html = '';
                $.each(data, function (i, u) {
                    var parts    = $.trim(u.name).split(' ');
                    var initials = ((parts[0] || '').charAt(0) + (parts[1] || '').charAt(0)).toUpperCase();
                    html += '<div class="guarantor-option"'
                          + ' data-id="'   + u.id + '"'
                          + ' data-name="' + $('<span>').text(u.name).html() + '"'
                          + ' data-user="' + $('<span>').text(u.username).html() + '"'
                          + ' data-init="' + initials + '">'
                          + '<div class="g-option-avatar">' + initials + '</div>'
                          + '<div>'
                          + '<div class="g-option-name">' + $('<span>').text(u.name).html() + '</div>'
                          + '<div class="g-option-user">@' + $('<span>').text(u.username).html() + '</div>'
                          + '</div></div>';
                });
                $dropdown.html(html).show();
             });
        }, 280);
    });

    $(document).on('click', '.guarantor-option', function () {
        selectGuarantor(
            $(this).data('id'),
            $(this).data('name'),
            $(this).data('user'),
            $(this).data('init')
        );
        $dropdown.hide();
    });

    function selectGuarantor(id, name, username, initials) {
        $hiddenId.val(id);
        $avatar.text(initials);
        $gName.text(name);
        $gUser.text('@' + username);
        $search.val(name);
        $selected.css('display', 'flex');
        $('#prevGuarantorStatus').hide();
    }

    $('#gSelChange').on('click', function () {
        $selected.hide();
        $hiddenId.val('');
        $search.val('').focus();
        $('#prevGuarantorStatus').show();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.guarantor-search-wrap').length) $dropdown.hide();
    });

    /* ── Form Submit ── */
    $('#kycForm').on('submit', function () {
        $('#kycSubmitBtn').addClass('submitting').prop('disabled', true);
    });

});
</script>
@endpush

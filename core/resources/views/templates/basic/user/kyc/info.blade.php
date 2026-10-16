@extends($activeTemplate . 'layouts.master')

@section('content')
@php
    use App\Constants\Status;
    $kv = $user->kv;

    if ($kv == Status::KYC_VERIFIED) {
        $heroClass = 'verified';
        $heroIcon  = 'fas fa-shield-alt';
        $heroTitle = 'Identity Verified';
        $heroSub   = 'Your KYC has been approved. You are eligible to apply for loans.';
        $badgeIcon = 'fas fa-check-circle';
        $badgeText = 'Verified';
    } elseif ($kv == Status::KYC_PENDING) {
        $heroClass = 'pending';
        $heroIcon  = 'fas fa-hourglass-half';
        $heroTitle = 'KYC Under Review';
        $heroSub   = 'Your documents are being reviewed by our team. We will notify you once complete.';
        $badgeIcon = 'fas fa-clock';
        $badgeText = 'Pending Review';
    } elseif ($user->kyc_rejection_reason) {
        $heroClass = 'rejected';
        $heroIcon  = 'fas fa-times-circle';
        $heroTitle = 'KYC Rejected';
        $heroSub   = 'Your submission was not accepted. Please review the reason and resubmit.';
        $badgeIcon = 'fas fa-exclamation-circle';
        $badgeText = 'Rejected';
    } else {
        $heroClass = 'empty';
        $heroIcon  = 'fas fa-id-card';
        $heroTitle = 'KYC Not Submitted';
        $heroSub   = 'Complete your identity verification to unlock loan applications.';
        $badgeIcon = 'fas fa-exclamation-circle';
        $badgeText = 'Action Required';
    }

    $cardTypeLabels = [
        'driving_license'        => "Driver's License",
        'international_passport' => 'International Passport',
        'nin'                    => 'NIN Card',
    ];
@endphp

<div class="kyc-info-page">
<div class="container">

    {{-- ── Hero ── --}}
    <div class="ki-hero {{ $heroClass }}">
        <div class="ki-hero-top">
            <div class="ki-hero-icon"><i class="{{ $heroIcon }}"></i></div>
            <div>
                <p class="ki-hero-title">@lang($heroTitle)</p>
                <p class="ki-hero-sub">@lang($heroSub)</p>
            </div>
        </div>
        <div class="ki-hero-badge">
            <i class="{{ $badgeIcon }}"></i> @lang($badgeText)
        </div>

        @if($user->kyc_rejection_reason)
        <div class="ki-rejection mt-3">
            <p class="ki-rejection-label">@lang('Rejection Reason')</p>
            <p class="ki-rejection-text">{{ $user->kyc_rejection_reason }}</p>
        </div>
        @endif
    </div>

    {{-- ── Not submitted yet ── --}}
    @if(!$user->kyc_data && !$user->nin && $kv == Status::KYC_UNVERIFIED)
    <div class="ki-section">
        <div class="ki-empty">
            <div class="ki-empty-icon"><i class="fas fa-id-card-alt"></i></div>
            <p class="ki-empty-title">@lang('No KYC Submission Found')</p>
            <p class="ki-empty-sub">@lang('Fill out your identity details, upload your ID card, and nominate a guarantor to get started.')</p>
            <a href="{{ route('user.kyc.form') }}" class="ki-cta ">
                <i class="fas fa-paper-plane"></i> @lang('Start KYC Verification')
            </a>
        </div>
    </div>
    @else

    {{-- ── Basic Info (dynamic form data) ── --}}
    @if($user->kyc_data)
    <div class="ki-section">
        <div class="ki-section-header">
            <div class="ki-section-icon"><i class="fas fa-user"></i></div>
            <p class="ki-section-title">@lang('Basic Information')</p>
        </div>
        @foreach($user->kyc_data as $val)
            @continue(!$val->value)
            <div class="ki-row">
                <span class="ki-row-label">{{ __($val->name) }}</span>
                <span class="ki-row-value">
                    @if($val->type == 'checkbox')
                        {{ implode(', ', (array)$val->value) }}
                    @elseif($val->type == 'file')
                        @php
                            $fHash = encrypt(getFilePath('verify') . '/' . $val->value);
                            $fExt  = strtolower(pathinfo($val->value, PATHINFO_EXTENSION));
                            $fImg  = in_array($fExt, ['jpg','jpeg','png','gif','webp']);
                        @endphp
                        <div style="display:flex;gap:8px;align-items:center;justify-content:flex-end;flex-wrap:wrap;">
                            @if($fImg)
                            <img src="{{ route('user.view.attachment', $fHash) }}"
                                 onclick="kycLbOpen('{{ route('user.view.attachment', $fHash) }}')"
                                 style="height:40px;width:60px;object-fit:cover;border-radius:5px;cursor:zoom-in;border:1px solid #E5E7EB;">
                            <a href="#" onclick="kycLbOpen('{{ route('user.view.attachment', $fHash) }}');return false;"
                               style="color:#059669;font-size:.8rem;font-weight:600;text-decoration:none;">
                                <i class="fas fa-eye"></i> @lang('View')
                            </a>
                            @endif
                            <a href="{{ route('user.download.attachment', $fHash) }}"
                               style="color:#6B7280;font-size:.8rem;font-weight:600;text-decoration:none;">
                                <i class="fas fa-download"></i> @lang('Download')
                            </a>
                        </div>
                    @else
                        {{ __($val->value) }}
                    @endif
                </span>
            </div>
        @endforeach
    </div>
    @endif

    {{-- ── Identity Verification ── --}}
    @if($user->nin || $user->id_card_type || $user->id_card_image)
    <div class="ki-section">
        <div class="ki-section-header">
            <div class="ki-section-icon"><i class="fas fa-id-card"></i></div>
            <p class="ki-section-title">@lang('Identity Verification')</p>
        </div>
        @if($user->nin)
        <div class="ki-row">
            <span class="ki-row-label">@lang('NIN')</span>
            <span class="ki-row-value">{{ $user->nin }}</span>
        </div>
        @endif
        @if($user->id_card_type)
        <div class="ki-row">
            <span class="ki-row-label">@lang('ID Card Type')</span>
            <span class="ki-row-value">{{ $cardTypeLabels[$user->id_card_type] ?? ucwords(str_replace('_', ' ', $user->id_card_type)) }}</span>
        </div>
        @endif
        @if($user->id_card_image)
        @php $idHash = encrypt(getFilePath('verify') . '/id_cards/' . $user->id_card_image); @endphp
        <div class="ki-row">
            <span class="ki-row-label">@lang('ID Card Image')</span>
            <span class="ki-row-value">
                <div style="display:flex;gap:8px;align-items:center;justify-content:flex-end;flex-wrap:wrap;">
                    <img src="{{ route('user.view.attachment', $idHash) }}"
                         onclick="kycLbOpen('{{ route('user.view.attachment', $idHash) }}')"
                         style="height:44px;width:66px;object-fit:cover;border-radius:6px;cursor:zoom-in;border:1px solid #E5E7EB;">
                    <a href="#" onclick="kycLbOpen('{{ route('user.view.attachment', $idHash) }}');return false;"
                       style="color:#059669;font-size:.8rem;font-weight:600;text-decoration:none;">
                        <i class="fas fa-eye"></i> @lang('View')
                    </a>
                    <a href="{{ route('user.download.attachment', $idHash) }}"
                       style="color:#6B7280;font-size:.8rem;font-weight:600;text-decoration:none;">
                        <i class="fas fa-download"></i> @lang('Download')
                    </a>
                </div>
            </span>
        </div>
        @endif
    </div>
    @endif

    {{-- ── Guarantor ── --}}
    <div class="ki-section">
        <div class="ki-section-header">
            <div class="ki-section-icon"><i class="fas fa-handshake"></i></div>
            <p class="ki-section-title">@lang('Guarantor')</p>
        </div>
        @if($guarantorRequest && $guarantorRequest->guarantor)
        @php
            $g = $guarantorRequest->guarantor;
            $gName = trim($g->firstname . ' ' . $g->lastname);
        @endphp
        <div class="ki-row">
            <span class="ki-row-label">@lang('Nominated Guarantor')</span>
            <span class="ki-row-value">{{ $gName }} <span style="color:#6B7280;font-weight:400;">(@ {{ $g->username }})</span></span>
        </div>
        <div class="ki-row">
            <span class="ki-row-label">@lang('Guarantor Status')</span>
            <span class="ki-row-value">
                <span class="ki-g-pill {{ $guarantorRequest->status }}">
                    @if($guarantorRequest->status === 'accepted')
                        <i class="fas fa-check-circle"></i> @lang('Accepted')
                    @elseif($guarantorRequest->status === 'pending')
                        <i class="fas fa-hourglass-half"></i> @lang('Awaiting Response')
                    @else
                        <i class="fas fa-times-circle"></i> @lang('Declined')
                    @endif
                </span>
            </span>
        </div>
        @if($guarantorRequest->responded_at)
        <div class="ki-row">
            <span class="ki-row-label">@lang('Responded')</span>
            <span class="ki-row-value">{{ $guarantorRequest->responded_at->format('d M Y, g:i A') }}</span>
        </div>
        @endif
        @else
        <div class="ki-row">
            <span class="ki-row-label">@lang('Guarantor')</span>
            <span class="ki-row-value" style="color:#9CA3AF;">@lang('Not nominated')</span>
        </div>
        @endif
    </div>

    {{-- ── Actions ── --}}
    @if($kv != Status::KYC_VERIFIED)
    <div class="ki-section">
        <div class="ki-action-row">
            @if($kv == Status::KYC_PENDING)
                <span style="font-size:.85rem;color:#6B7280;align-self:center;">
                    <i class="fas fa-info-circle" style="color:#D97706;"></i>
                    @lang('Your submission is under review. No action needed right now.')
                </span>
            @else
                <a href="{{ route('user.kyc.form') }}" class="ki-cta">
                    <i class="fas fa-redo-alt"></i>
                    {{ $user->kyc_rejection_reason ? __('Resubmit KYC') : __('Edit & Resubmit') }}
                </a>
            @endif
        </div>
    </div>
    @endif

    @endif {{-- end not-empty block --}}

</div>
</div>

{{-- ── Lightbox ── --}}
<div id="kycLightbox" onclick="kycLbClose()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:99999;align-items:center;justify-content:center;cursor:zoom-out;padding:20px;">
    <img id="kycLightboxImg" src="" alt="KYC Attachment"
         style="max-width:92vw;max-height:88vh;border-radius:10px;box-shadow:0 12px 48px rgba(0,0,0,.6);object-fit:contain;cursor:default;"
         onclick="event.stopPropagation()">
    <button onclick="kycLbClose()"
            style="position:fixed;top:18px;right:22px;background:rgba(255,255,255,.15);border:none;color:#fff;font-size:1.6rem;line-height:1;width:40px;height:40px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;">&times;</button>
</div>

@push('script')
<script>
function kycLbOpen(url) {
    var lb  = document.getElementById('kycLightbox');
    var img = document.getElementById('kycLightboxImg');
    img.src = url;
    lb.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function kycLbClose() {
    document.getElementById('kycLightbox').style.display = 'none';
    document.getElementById('kycLightboxImg').src = '';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') kycLbClose();
});
</script>
@endpush

@endsection

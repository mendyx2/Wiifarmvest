@extends($activeTemplate . 'layouts.master')

@section('content')
@php $user = auth()->user(); @endphp
<div class="nc-wrap" id="ncWrap">
    <br/>
<form method="POST" enctype="multipart/form-data" class="prof-page">
    @csrf

    {{-- ── Profile Header Card ── --}}
    <div class="prof-header-card">

        {{-- Photo upload zone --}}
        <div class="prof-photo-zone">
            <label for="imageInput" class="prof-photo-label">
                <div class="prof-photo-preview" id="photoPreviewWrap">
                    @if($user->image)
                        <img id="avatarPreview" src="{{ getImage(getFilePath('userProfile') . '/' . $user->image, null, true) }}" alt="avatar">
                    @else
                        <div class="prof-photo-initials" id="avatarInitials">
                            {{ strtoupper(substr($user->firstname, 0, 1)) }}{{ strtoupper(substr($user->lastname, 0, 1)) }}
                        </div>
                        <img id="avatarPreview" src="" alt="avatar" style="display:none;">
                    @endif
                    <div class="prof-photo-overlay">
                        <i class="las la-camera"></i>
                        <span>Change</span>
                    </div>
                </div>
            </label>
            <input type="file" id="imageInput" name="image" accept=".png,.jpg,.jpeg" style="display:none;" onchange="previewAvatar(event)">
            <p class="prof-photo-hint">
                <i class="las la-image"></i> JPG or PNG · 350×300px
            </p>
        </div>

        {{-- User info --}}
        <div class="prof-header-info">
            <h5 class="prof-header-name">{{ $user->fullname }}</h5>
            <p class="prof-header-username">@ {{ $user->username }}</p>
            <div class="prof-header-badges">
                <span class="prof-badge green"><i class="las la-check-circle"></i> Active</span>
                <span class="prof-badge slate"><i class="las la-envelope"></i> {{ $user->email }}</span>
            </div>
        </div>

    </div>

    {{-- ── Two Column: Personal + Bank ── --}}
    <div class="prof-two-col">

        {{-- Personal Info --}}
        <div class="prof-section-card">
            <div class="prof-section-head">
                <div class="prof-section-icon green"><i class="las la-user"></i></div>
                <div>
                    <p class="prof-section-title">Personal Info</p>
                    <p class="prof-section-sub">Your name and contact details</p>
                </div>
            </div>
            <div class="prof-section-body">
                <div class="prof-field-grid">
                    <div class="prof-field">
                        <label>First Name</label>
                        <input type="text" name="firstname" value="{{ $user->firstname }}" required placeholder="First name">
                    </div>
                    <div class="prof-field">
                        <label>Last Name</label>
                        <input type="text" name="lastname" value="{{ $user->lastname }}" required placeholder="Last name">
                    </div>
                    <div class="prof-field">
                        <label>Email Address</label>
                        <div class="prof-input-icon-wrap">
                            <i class="las la-lock-open prof-input-icon"></i>
                            <input type="email" name="email" value="{{ $user->email }}" readonly class="prof-readonly">
                        </div>
                    </div>
                    <div class="prof-field">
                        <label>Mobile Number</label>
                        <div class="prof-input-icon-wrap">
                            <i class="las la-phone prof-input-icon"></i>
                            <input type="text" name="mobile" value="{{ $user->mobile }}" required placeholder="e.g. 08012345678">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bank Details --}}
        <div class="prof-section-card">
            <div class="prof-section-head">
                <div class="prof-section-icon violet"><i class="las la-university"></i></div>
                <div>
                    <p class="prof-section-title">Bank Details</p>
                    <p class="prof-section-sub">Where your withdrawals are sent</p>
                </div>
            </div>
            <div class="prof-section-body">
                <div class="prof-field-grid">
                    <div class="prof-field" style="grid-column: 1 / -1;">
                        <label>Bank Name</label>
                        <div class="prof-input-icon-wrap">
                            <i class="las la-building prof-input-icon"></i>
                            <input type="text" name="bname" value="{{ $user->bname }}" required placeholder="e.g. First Bank">
                        </div>
                    </div>
                    <div class="prof-field">
                        <label>Account Name</label>
                        <input type="text" name="aname" value="{{ $user->aname }}" required placeholder="As on bank card">
                    </div>
                    <div class="prof-field">
                        <label>Account Number</label>
                        <input type="text" name="ano" value="{{ $user->ano }}" required placeholder="10-digit number" maxlength="10">
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Address Card ── --}}
    <div class="prof-section-card">
        <div class="prof-section-head">
            <div class="prof-section-icon amber"><i class="las la-map-marker-alt"></i></div>
            <div>
                <p class="prof-section-title">Address</p>
                <p class="prof-section-sub">Your residential information</p>
            </div>
        </div>
        <div class="prof-section-body">
            <div class="prof-field-grid prof-field-grid--4">
                <div class="prof-field" style="grid-column: span 2;">
                    <label>Street Address</label>
                    <input type="text" name="address" value="{{ @$user->address }}" placeholder="House / Street">
                </div>
                <div class="prof-field">
                    <label>State</label>
                    <input type="text" name="state" value="{{ @$user->state }}" placeholder="State">
                </div>
                <div class="prof-field">
                    <label>City</label>
                    <input type="text" name="city" value="{{ @$user->city }}" placeholder="City">
                </div>
                <div class="prof-field">
                    <label>Zip Code</label>
                    <input type="text" name="zip" value="{{ @$user->zip }}" placeholder="ZIP">
                </div>
                <div class="prof-field">
                    <label>Country</label>
                    <div class="prof-input-icon-wrap">
                        <i class="las la-globe prof-input-icon"></i>
                        <input type="text" value="{{ @$user->country_name }}" disabled class="prof-readonly">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Save Button ── --}}
    <button type="submit" class="prof-save-btn">
        <i class="las la-save"></i> Save Changes
    </button>

</form>

{{-- ── Bank Detail History ── --}}
@if($bankHistories->count() > 0)
<div class="prof-section-card" style="margin-top: 1.5rem;">
    <div class="prof-section-head">
        <div class="prof-section-icon amber"><i class="las la-history"></i></div>
        <div>
            <p class="prof-section-title">Bank Details History</p>
            <p class="prof-section-sub">Previous bank accounts linked to your profile</p>
        </div>
    </div>
    <div class="prof-section-body">
        <div class="table-responsive">
            <table class="table table-bordered mb-0" style="font-size: 0.875rem;">
                <thead style="background: var(--prof-bg, #f8f9ff);">
                    <tr>
                        <th>#</th>
                        <th>Bank Name</th>
                        <th>Account Name</th>
                        <th>Account Number</th>
                        <th>Changed On</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bankHistories as $index => $history)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $history->bname }}</td>
                        <td>{{ $history->aname }}</td>
                        <td>{{ $history->ano }}</td>
                        <td>{{ $history->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
</div>
@endsection

@push('script')
<script>
'use strict';
function previewAvatar(event) {
    var file = event.target.files[0];
    if (!file) return;
    var preview = document.getElementById('avatarPreview');
    var initials = document.getElementById('avatarInitials');
    preview.src = URL.createObjectURL(file);
    preview.style.display = 'block';
    preview.onload = function() { URL.revokeObjectURL(preview.src); };
    if (initials) initials.style.display = 'none';
}
</script>
@endpush

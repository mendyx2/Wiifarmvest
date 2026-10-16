@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="nc-wrap" id="ncWrap">
  <br>
{{-- ── Page Header ─────────────────────────────────── --}}
<div class="sl-page-header d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('user.stockist.dashboard') }}" class="sl-back-btn" aria-label="Back">
        <i class="las la-arrow-left"></i>
    </a>
    <div>
        <h4 class="sl-page-title mb-0">Stockist Profile</h4>
        <p class="sl-page-subtitle mb-0">Manage your business information and locations.</p>
    </div>
</div>

<div class="row g-4">
    {{-- ── LEFT: Section nav + status ──────────────────── --}}
    <div class="col-12 col-lg-3">
        <div class="sl-card mb-3 sticky-lg-top" style="top:100px;">
            <ul class="sl-profile-nav" role="tablist">
                <li><a class="sl-profile-nav-item active" href="#basic-info" data-bs-toggle="tab"><i class="las la-building"></i> Basic Info</a></li>
                <li><a class="sl-profile-nav-item" href="#location" data-bs-toggle="tab"><i class="las la-map-marker-alt"></i> Location</a></li>
                <li><a class="sl-profile-nav-item" href="#opening-hours" data-bs-toggle="tab"><i class="las la-clock"></i> Opening Hours</a></li>
                <li><a class="sl-profile-nav-item" href="#services" data-bs-toggle="tab"><i class="las la-concierge-bell"></i> Services</a></li>
                <li><a class="sl-profile-nav-item" href="#preview" data-bs-toggle="tab"><i class="las la-eye"></i> Preview</a></li>
            </ul>
        </div>

        <div class="sl-card">
            <div class="sl-card-body text-center">
                @if($stockist->is_active)
                    <span class="sl-status sl-status-active" style="font-size:.8rem;padding:.4rem .9rem;">
                        <i class="las la-check-circle me-1"></i> Verified Stockist
                    </span>
                @else
                    <span class="sl-status sl-status-pending" style="font-size:.8rem;padding:.4rem .9rem;">
                        <i class="las la-hourglass-half me-1"></i> Pending Verification
                    </span>
                @endif
                <p class="text-muted mt-3 mb-0" style="font-size:.8rem;">
                    {{ $stockist->is_active ? 'Your account is verified and active' : 'Your account is pending admin verification' }}
                </p>
            </div>
        </div>
    </div>

    {{-- ── RIGHT: Tab content ──────────────────────────── --}}
    <div class="col-12 col-lg-9">
        <div class="tab-content">

            {{-- Basic Info --}}
            <div class="tab-pane fade show active" id="basic-info">
                <div class="sl-card">
                    <div class="sl-card-header"><i class="las la-building me-1"></i> Basic Business Information</div>
                    <div class="sl-card-body">
                        <form id="basicInfoForm">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="sl-label" for="business_name">Business Name <span class="sl-required">*</span></label>
                                    <input type="text" class="sl-input" id="business_name" name="business_name"
                                           value="{{ $stockist->business_name }}" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="sl-label" for="business_registration_number">Registration Number</label>
                                    <input type="text" class="sl-input" id="business_registration_number" name="business_registration_number"
                                           value="{{ $stockist->business_registration_number }}" placeholder="Optional">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="sl-label" for="business_email">Business Email <span class="sl-required">*</span></label>
                                    <input type="email" class="sl-input" id="business_email" name="business_email"
                                           value="{{ $stockist->business_email }}" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="sl-label" for="business_phone">Business Phone <span class="sl-required">*</span></label>
                                    <input type="text" class="sl-input" id="business_phone" name="business_phone"
                                           value="{{ $stockist->business_phone }}" required>
                                </div>
                                <div class="col-12">
                                    <label class="sl-label" for="website">Website</label>
                                    <input type="url" class="sl-input" id="website" name="website"
                                           value="{{ $stockist->website }}" placeholder="https://example.com">
                                </div>
                                <div class="col-12">
                                    <label class="sl-label" for="business_description">Business Description</label>
                                    <textarea class="sl-input" id="business_description" name="business_description" rows="4"
                                              placeholder="Describe your business…">{{ $stockist->business_description }}</textarea>
                                    <p class="sl-field-hint">Tell customers about your business (max 1000 characters)</p>
                                </div>
                            </div>
                            <div class="sl-form-actions mt-4" style="justify-content:flex-end;">
                                <button type="submit" class="sl-btn sl-btn-primary">
                                    <i class="las la-save me-1"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Location --}}
            <div class="tab-pane fade" id="location">
                <div class="sl-card">
                    <div class="sl-card-header"><i class="las la-map-marker-alt me-1"></i> Business Location</div>
                    <div class="sl-card-body">
                        <form id="locationForm">
                            @csrf
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="sl-label" for="address_line_1">Address Line 1 <span class="sl-required">*</span></label>
                                    <input type="text" class="sl-input" id="address_line_1" name="address_line_1"
                                           value="{{ $stockist->primaryLocation->address_line_1 ?? '' }}"
                                           placeholder="Street address, P.O. box" required>
                                </div>
                                <div class="col-12">
                                    <label class="sl-label" for="address_line_2">Address Line 2</label>
                                    <input type="text" class="sl-input" id="address_line_2" name="address_line_2"
                                           value="{{ $stockist->primaryLocation->address_line_2 ?? '' }}"
                                           placeholder="Apartment, suite, unit, building, floor, etc.">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="sl-label" for="city">City <span class="sl-required">*</span></label>
                                    <input type="text" class="sl-input" id="city" name="city"
                                           value="{{ $stockist->primaryLocation->city ?? '' }}" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="sl-label" for="state">State <span class="sl-required">*</span></label>
                                    <input type="text" class="sl-input" id="state" name="state"
                                           value="{{ $stockist->primaryLocation->state ?? '' }}" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="sl-label" for="postal_code">Postal Code</label>
                                    <input type="text" class="sl-input" id="postal_code" name="postal_code"
                                           value="{{ $stockist->primaryLocation->postal_code ?? '' }}">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="sl-label" for="country">Country <span class="sl-required">*</span></label>
                                    <select class="sl-select" id="country" name="country" required>
                                        <option value="Nigeria" {{ ($stockist->primaryLocation->country ?? 'Nigeria') == 'Nigeria' ? 'selected' : '' }}>Nigeria</option>
                                        <option value="Ghana" {{ ($stockist->primaryLocation->country ?? '') == 'Ghana' ? 'selected' : '' }}>Ghana</option>
                                        <option value="Kenya" {{ ($stockist->primaryLocation->country ?? '') == 'Kenya' ? 'selected' : '' }}>Kenya</option>
                                        <option value="South Africa" {{ ($stockist->primaryLocation->country ?? '') == 'South Africa' ? 'selected' : '' }}>South Africa</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="sl-label" for="location_phone">Location Phone</label>
                                    <input type="text" class="sl-input" id="location_phone" name="phone"
                                           value="{{ $stockist->primaryLocation->phone ?? '' }}">
                                </div>
                                <div class="col-12">
                                    <label class="sl-label" for="location_email">Location Email</label>
                                    <input type="email" class="sl-input" id="location_email" name="email"
                                           value="{{ $stockist->primaryLocation->email ?? '' }}">
                                </div>
                            </div>
                            <div class="sl-form-actions mt-4" style="justify-content:flex-end;">
                                <button type="submit" class="sl-btn sl-btn-primary">
                                    <i class="las la-save me-1"></i> Save Location
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Opening Hours --}}
            <div class="tab-pane fade" id="opening-hours">
                <div class="sl-card">
                    <div class="sl-card-header"><i class="las la-clock me-1"></i> Opening Hours</div>
                    <div class="sl-card-body">
                        <form id="openingHoursForm">
                            @csrf
                            <div class="table-responsive">
                                <table class="sl-table" aria-label="Opening hours">
                                    <thead>
                                        <tr>
                                            <th>Day</th>
                                            <th>Open</th>
                                            <th>Close</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="openingHoursBody"></tbody>
                                </table>
                            </div>
                            <div class="sl-form-actions mt-4" style="justify-content:flex-end;">
                                <button type="submit" class="sl-btn sl-btn-primary">
                                    <i class="las la-save me-1"></i> Save Hours
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Services --}}
            <div class="tab-pane fade" id="services">
                <div class="sl-card">
                    <div class="sl-card-header"><i class="las la-concierge-bell me-1"></i> Services Offered</div>
                    <div class="sl-card-body">
                        <form id="servicesForm">
                            @csrf
                            <div id="servicesContainer"></div>
                            <button type="button" class="sl-btn sl-btn-outline mt-2" id="addServiceBtn">
                                <i class="las la-plus me-1"></i> Add Another Service
                            </button>
                            <div class="sl-form-actions mt-4" style="justify-content:flex-end;">
                                <button type="submit" class="sl-btn sl-btn-primary">
                                    <i class="las la-save me-1"></i> Save Services
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Preview --}}
            <div class="tab-pane fade" id="preview">
                <div class="sl-card">
                    <div class="sl-card-header"><i class="las la-eye me-1"></i> Profile Preview</div>
                    <div class="sl-card-body">
                        <div id="profilePreview"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
</div>
@endsection

@push('modal')
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="mb-3" style="color:var(--sl-green);">
                    <i class="las la-check-circle" style="font-size:3.5rem;"></i>
                </div>
                <h4 class="mb-3" id="successTitle">Success!</h4>
                <p class="text-muted mb-4" id="successMessage">Your changes have been saved successfully.</p>
                <button type="button" class="sl-btn sl-btn-primary" data-bs-dismiss="modal">Continue</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('script')
<script>
$(document).ready(function() {
    loadOpeningHours();
    loadServices();

    $('#basicInfoForm').on('submit', function(e) {
        e.preventDefault();
        submitForm('{{ route("user.stockist.profile.basic-info") }}', $(this).serialize(), 'Basic information');
    });

    $('#locationForm').on('submit', function(e) {
        e.preventDefault();
        submitForm('{{ route("user.stockist.profile.location") }}', $(this).serialize(), 'Location information');
    });

    $('#openingHoursForm').on('submit', function(e) {
        e.preventDefault();
        const openingHours = {};
        $('#openingHoursBody tr').each(function() {
            const day = $(this).data('day');
            const open = $(this).find('.open-time').val();
            const close = $(this).find('.close-time').val();
            openingHours[day] = { open, close };
        });

        submitForm('{{ route("user.stockist.profile.opening-hours") }}', { opening_hours: openingHours }, 'Opening hours');
    });

    $('#servicesForm').on('submit', function(e) {
        e.preventDefault();
        submitForm('{{ route("user.stockist.profile.services") }}', $(this).serialize(), 'Services');
    });

    $('#addServiceBtn').on('click', function() {
        addServiceField('', '');
    });

    $('a[data-bs-toggle="tab"]').on('click', function() {
        $('.sl-profile-nav-item').removeClass('active');
        $(this).addClass('active');
    });

    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        if (e.target.getAttribute('href') === '#preview') {
            loadProfilePreview();
        }
    });

    function submitForm(url, data, title) {
        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $('#successTitle').text('Success!');
                    $('#successMessage').text(response.message);
                    $('#successModal').modal('show');
                } else {
                    showError(response.message);
                }
            },
            error: function(xhr) {
                showError('An error occurred. Please try again.');
            }
        });
    }

    function loadOpeningHours() {
        $.get('{{ route("user.stockist.profile.data") }}', function(response) {
            if (response.success) {
                const openingHours = response.primary_location?.opening_hours || {};
                const days = [
                    { key: 'monday', label: 'Monday' },
                    { key: 'tuesday', label: 'Tuesday' },
                    { key: 'wednesday', label: 'Wednesday' },
                    { key: 'thursday', label: 'Thursday' },
                    { key: 'friday', label: 'Friday' },
                    { key: 'saturday', label: 'Saturday' },
                    { key: 'sunday', label: 'Sunday' }
                ];

                let html = '';
                days.forEach(day => {
                    const hours = openingHours[day.key] || { open: '08:00', close: '17:00' };
                    const isClosed = !hours.open || !hours.close;

                    html += `
                        <tr data-day="${day.key}">
                            <td data-label="Day" class="fw-600">${day.label}</td>
                            <td data-label="Open">
                                <input type="time" class="sl-input open-time" style="padding:.4rem .6rem;"
                                       value="${hours.open}" ${isClosed ? 'disabled' : ''}>
                            </td>
                            <td data-label="Close">
                                <input type="time" class="sl-input close-time" style="padding:.4rem .6rem;"
                                       value="${hours.close}" ${isClosed ? 'disabled' : ''}>
                            </td>
                            <td data-label="Status">
                                <label class="sl-switch">
                                    <input type="checkbox" class="hours-toggle" ${isClosed ? '' : 'checked'}>
                                    <span class="sl-switch-track"></span>
                                </label>
                                <span class="ms-2 hours-label" style="font-size:.78rem;">${isClosed ? 'Closed' : 'Open'}</span>
                            </td>
                        </tr>
                    `;
                });

                $('#openingHoursBody').html(html);

                $('.hours-toggle').on('change', function() {
                    const $row = $(this).closest('tr');
                    const isOpen = $(this).is(':checked');
                    $row.find('.open-time, .close-time').prop('disabled', !isOpen);
                    $row.find('.hours-label').text(isOpen ? 'Open' : 'Closed');
                });
            }
        });
    }

    function loadServices() {
        $.get('{{ route("user.stockist.profile.data") }}', function(response) {
            if (response.success) {
                $('#servicesContainer').empty();
                if (response.services.length > 0) {
                    response.services.forEach(service => {
                        addServiceField(service.service_name, service.description);
                    });
                } else {
                    addServiceField('Product Redemption', 'Redeem customer products using invoice codes');
                    addServiceField('Customer Support', 'Provide support and assistance to customers');
                }
            }
        });
    }

    function addServiceField(name = '', description = '') {
        const serviceId = Date.now();
        const html = `
            <div class="sl-card mb-3" data-id="${serviceId}" style="box-shadow:none;">
                <div class="sl-card-body">
                    <div class="row g-2">
                        <div class="col-12 col-md-5">
                            <label class="sl-label">Service Name</label>
                            <input type="text" class="sl-input service-name" name="serviceName[]"
                                   value="${name}" placeholder="e.g., Product Redemption">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="sl-label">Description</label>
                            <textarea class="sl-input service-description" name="description[]" rows="1">${description}</textarea>
                        </div>
                        <div class="col-12 col-md-1 d-flex align-items-end">
                            <button type="button" class="sl-btn sl-btn-outline remove-service w-100"
                                    onclick="$(this).closest('[data-id]').remove()" title="Remove">
                                <i class="las la-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#servicesContainer').append(html);
    }

    function loadProfilePreview() {
        $.get('{{ route("user.stockist.profile.data") }}', function(response) {
            if (response.success) {
                const stockist = response.stockist;
                const location = response.primary_location;
                const services = response.services;

                let html = `
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <h6 class="fw-600 mb-3" style="color:var(--sl-green);">Business Information</h6>
                            <div class="mb-3">
                                <strong>Business Name:</strong> ${stockist.business_name}<br>
                                <strong>Email:</strong> ${stockist.business_email}<br>
                                <strong>Phone:</strong> ${stockist.business_phone}<br>
                                ${stockist.website ? `<strong>Website:</strong> <a href="${stockist.website}" target="_blank">${stockist.website}</a><br>` : ''}
                                ${stockist.business_registration_number ? `<strong>Registration:</strong> ${stockist.business_registration_number}<br>` : ''}
                            </div>
                            ${stockist.business_description ? `
                            <div class="mb-3">
                                <strong>Description:</strong>
                                <p class="text-muted mt-1">${stockist.business_description}</p>
                            </div>` : ''}
                        </div>

                        <div class="col-12 col-md-6">
                            <h6 class="fw-600 mb-3" style="color:var(--sl-green);">Location</h6>
                `;

                if (location) {
                    html += `
                        <div class="mb-3">
                            <strong>Address:</strong><br>
                            ${location.address_line_1}<br>
                            ${location.address_line_2 ? location.address_line_2 + '<br>' : ''}
                            ${location.city}, ${location.state}<br>
                            ${location.country}${location.postal_code ? ' - ' + location.postal_code : ''}
                        </div>
                        ${location.phone ? `<div><strong>Phone:</strong> ${location.phone}</div>` : ''}
                        ${location.email ? `<div><strong>Email:</strong> ${location.email}</div>` : ''}
                    `;
                } else {
                    html += `<div class="text-muted">No location information added yet.</div>`;
                }

                html += `</div></div>`;

                if (services.length > 0) {
                    html += `
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="fw-600 mb-3" style="color:var(--sl-green);">Services Offered</h6>
                                <div class="d-flex flex-wrap gap-2">
                    `;
                    services.forEach(service => {
                        html += `<span class="sl-type-badge" style="background:var(--sl-green-lt);color:var(--sl-green);">${service.service_name}</span>`;
                    });
                    html += `</div></div></div>`;
                }

                $('#profilePreview').html(html);
            }
        });
    }

    function showError(message) {
        alert('Error: ' + message);
    }
});
</script>
@endpush

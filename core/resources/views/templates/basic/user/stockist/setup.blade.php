
@extends($activeTemplate . 'layouts.master_stockist')
@section('title', 'Become a Stockist')
@section('content')
 @include($activeTemplate.'layouts.breadcrumb')
<div class="container-fluid py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-lg rounded-3">
                    <div class="card-header bg-gradient-primary text-white py-4 rounded-top">
                        <h2 class="text-center mb-0">
                            <i class="fas fa-store me-2"></i>
                            Become a Stockist
                        </h2> 
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted text-center mb-4">
                            Complete your stockist profile to start redeeming customer invoices
                        </p>

                        <form method="POST" action="{{ route('user.stockist.setup.store') }}">
                            @csrf

                            <!-- Personal Information -->
                            <div class="mb-4">
                                <h5 class="fw-semibold text-dark mb-3">
                                    <i class="fas fa-user me-2 text-primary"></i>
                                    Personal Information
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Full Name *</label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                               name="name" value="{{ old('name') }}" required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Company Name *</label>
                                        <input type="text" class="form-control @error('company_name') is-invalid @enderror" 
                                               name="company_name" value="{{ old('company_name') }}" required>
                                        @error('company_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="mb-4">
                                <h5 class="fw-semibold text-dark mb-3">
                                    <i class="fas fa-address-card me-2 text-success"></i>
                                    Contact Information
                                </h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Email Address *</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                               name="email" value="{{ old('email') }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Phone Number *</label>
                                        <input type="tel" class="form-control @error('phone') is-invalid @enderror" 
                                               name="phone" value="{{ old('phone') }}" required>
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Location Information -->
                            <div class="mb-4">
                                <h5 class="fw-semibold text-dark mb-3">
                                    <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                    Business Location
                                </h5>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Address *</label>
                                        <input type="text" class="form-control @error('address') is-invalid @enderror" 
                                               name="address" value="{{ old('address') }}" 
                                               placeholder="Street address" required>
                                        @error('address')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">City *</label>
                                        <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                               name="city" value="{{ old('city') }}" required>
                                        @error('city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">State *</label>
                                        <input type="text" class="form-control @error('state') is-invalid @enderror" 
                                               name="state" value="{{ old('state') }}" required>
                                        @error('state')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Country *</label>
                                        <input type="text" class="form-control @error('country') is-invalid @enderror" 
                                               name="country" value="{{ old('country', 'Nigeria') }}" required>
                                        @error('country')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Postal Code</label>
                                        <input type="text" class="form-control @error('postal_code') is-invalid @enderror" 
                                               name="postal_code" value="{{ old('postal_code') }}">
                                        @error('postal_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Terms -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input @error('terms') is-invalid @enderror" 
                                           type="checkbox" name="terms" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        I agree to the <a href="#" class="text-primary">Stockist Agreement</a> 
                                        and understand my responsibilities in processing customer redemptions.
                                    </label>
                                    @error('terms')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5">
                                    <i class="fas fa-check me-2"></i>
                                    Complete Setup
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Benefits Card -->
                <div class="card border-0 shadow-sm rounded-3 mt-4">
                    <div class="card-body">
                        <h5 class="fw-semibold mb-3">Stockist Benefits</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-users text-success me-3 fa-lg"></i>
                                    <div>
                                        <h6 class="mb-1">Customer Traffic</h6>
                                        <small class="text-muted">Attract more customers to your business</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-chart-line text-primary me-3 fa-lg"></i>
                                    <div>
                                        <h6 class="mb-1">Business Growth</h6>
                                        <small class="text-muted">Increase your sales and visibility</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-shield-alt text-warning me-3 fa-lg"></i>
                                    <div>
                                        <h6 class="mb-1">Verified System</h6>
                                        <small class="text-muted">Secure and reliable redemption process</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-headset text-info me-3 fa-lg"></i>
                                    <div>
                                        <h6 class="mb-1">Support</h6>
                                        <small class="text-muted">24/7 customer support available</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
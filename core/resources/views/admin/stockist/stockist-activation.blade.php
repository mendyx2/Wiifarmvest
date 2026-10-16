@extends('admin.layouts.app')

@section('title', 'Activate Stockist')

@section('panel')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-dark">Activate New Stockist</h1>
        <a href="{{ route('admin.stockist.dashboard') }}" class="btn btn-success btn-lg">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-success py-3">
                    <h6 class="m-0 font-weight-bold text-white">Stockist Activation Form</h6>
                </div>
                <div class="card-body">
                    <!-- Search Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="usernameSearch" class="font-weight-bold text-dark">Search User by Username</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="usernameSearch" 
                                           placeholder="Enter username..." autocomplete="off">
                                    <div class="input-group-append">
                                        <button class="form btn btn-lg btn-success" type="button" id="searchUserBtn">
                                            <i class="fas fa-search"></i> Search
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- User Details Card -->
                     <!-- User Details Card -->
                    <div class="card mt-4 d-none" id="userDetailsCard">
                        <div class="card-header bg-success">
                            <h6 class="m-0 font-weight-bold text-white">User Found</h6>
                        </div>
                        <div class="card-body">
                            <div class="row" id="userDetailsContent">
                                <!-- User details will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- Activation Form -->
                    <form id="activationForm" class="mt-4 d-none" method="POST" action="{{ route('admin.stockist.activate') }}">
                        @csrf
                        <input type="hidden" name="user_id" id="user_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold text-dark">Store Type *</label>
                                    <div class="mt-2">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="store_type" id="megaStore" value="1" required>
                                            <label class="form-check-label font-weight-bold text-success" for="megaStore">
                                                <i class="fas fa-store-alt"></i> Mega Store
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="store_type" id="miniStore" value="2" required>
                                            <label class="form-check-label font-weight-bold text-info" for="miniStore">
                                                <i class="fas fa-store"></i> Mini Store
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="activation_cost" class="font-weight-bold text-dark">Activation Cost (₦) *</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">₦</span>
                                        </div>
                                        <input type="number" class="form-control" id="activation_cost" 
                                               name="activation_cost" step="0.01" min="0" required 
                                               placeholder="Enter activation cost">
                                    </div>
                                    <small class="form-text text-muted">This amount will be deducted from user's balance</small>
                                </div>
                            </div>
                        </div>
                        <!-- Add this after the store type selection in stockist-activation.blade.php -->
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="business_phone" class="font-weight-bold text-dark">Business Phone *</label>
                                    <input type="text" class="form-control" id="business_phone" name="business_phone" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="state_id" class="font-weight-bold text-dark">State *</label>
                                    <select class="form form-control select2" id="state_id" name="state_id" required>
                                        <option value="">Select State</option>
                                        @foreach($states as $state)
                                            <option value="{{ $state->id }}">{{ $state->name }} ({{ $state->code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="business_name" class="font-weight-bold text-dark">Business Name *</label>
                                    <input type="text" class="form-control" id="business_name" name="business_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="business_email" class="font-weight-bold text-dark">Business Email *</label>
                                    <input type="email" class="form-control" id="business_email" name="business_email" required>
                                </div>
                            </div>
                        
                            
                           
                        </div>
                        {{-- 
                        <div class="row">
                             <div class="col-md-6">
                                <div class="form-group">
                                    <label for="business_registration_number" class="font-weight-bold text-dark">Registration Number</label>
                                    <input type="text" class="form-control" id="business_registration_number" name="business_registration_number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="website" class="font-weight-bold text-dark">Website</label>
                                    <input type="url" class="form-control" id="website" name="website" placeholder="https://">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="business_hours" class="font-weight-bold text-dark">Business Hours</label>
                                    <input type="text" class="form-control" id="business_hours" name="business_hours" placeholder="e.g., Mon-Fri 9AM-5PM">
                                </div>
                            </div>
                        </div>

                        
                        <div class="form-group">
                            <label for="business_description" class="font-weight-bold text-dark">Business Description</label>
                            <textarea class="form-control" id="business_description" name="business_description" rows="3" placeholder="Describe the business..."></textarea>
                        </div>
                        --}}
                        <div class="mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check-circle"></i> Activate Stockist
                            </button>
                            <button type="button" class="btn btn-danger btn-lg" onclick="resetForm()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({
        placeholder: "Select State",
        allowClear: true
    });
});
</script>
@endpush
@push('script')
<script>
$(document).ready(function() {
    // Search user function
    $('#searchUserBtn').click(function() {
        const username = $('#usernameSearch').val().trim();
        
        if (!username) {
            alert('Please enter a username');
            return;
        }

        // Show loading state
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Searching...').prop('disabled', true);

        $.ajax({
            url: '{{ route("admin.stockist.search-user") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                username: username
            },
            success: function(response) {
                $('#searchUserBtn').html('<i class="fas fa-search"></i> Search').prop('disabled', false);
                
                if (response.success) {
                    displayUserDetails(response.user);
                } else {
                    $('#userDetailsCard').addClass('d-none');
                    $('#activationForm').addClass('d-none');
                    alert(response.message);
                }
            },
            error: function() {
                $('#searchUserBtn').html('<i class="fas fa-search"></i> Search').prop('disabled', false);
                alert('An error occurred while searching for the user');
            }
        });
    });

    // Display user details
    function displayUserDetails(user) {
        const userHtml = `
            <div class="col-md-3">
                <div class="text-center">
                    <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-user text-white fa-2x"></i>
                    </div>
                    <h5 class="mt-2 mb-1">${user.name}</h5>
                    <p class="text-muted">@${user.username}</p>
                </div>
            </div>
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Email:</strong> ${user.email}</p>
                        <p><strong>Current Balance:</strong> <span class="text-success font-weight-bold">₦${parseFloat(user.balance).toFixed(2)}</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Member Since:</strong> ${user.created_at}</p>
                       
                    </div>
                </div>
                <div class="alert alert-info mt-2">
                    <i class="fas fa-info-circle"></i> User found! You can now proceed with stockist activation.
                </div>
            </div>
        `;
        
        $('#userDetailsContent').html(userHtml);
        $('#userDetailsCard').removeClass('d-none');
        $('#user_id').val(user.id);
        $('#activationForm').removeClass('d-none');
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#activationForm').offset().top - 100
        }, 500);
    }

    // Form submission handling
    $('#activationForm').on('submit', function() {
        
        return confirm('Are you sure you want to activate this user as a stockist? ');
    });
});

function resetForm() {
    $('#userDetailsCard').addClass('d-none');
    $('#activationForm').addClass('d-none');
    $('#usernameSearch').val('');
    $('#activationForm')[0].reset();
}
</script>
@endpush
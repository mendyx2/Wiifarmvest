@extends($activeTemplate . 'layouts.master')
@section('title', 'Find Stockist - Locate Nearby Stores')
@section('content')
@include($activeTemplate.'layouts.breadcrumb')
<div class="container-fluid py-5 bg-gradient-light">
    <div class="container">
        <!-- Header Section -->
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h1 class="display-5 fw-bold text-dark mb-3">Find a Stockist Near You</h1>
                <p class="lead text-muted">Locate authorized stockists to redeem your products</p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Search Card -->
                <div class="card border-0 shadow-lg rounded-3 mb-5">
                    <div class="card-header bg-gradient-success text-white py-4 rounded-top">
                        <h4 class="mb-0 text-center text-white">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Search Stockists by Location
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <form id="searchStockistForm">
                            @csrf
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">City</label>
                                    <input type="text" class="cityInput form-control form-control-lg" 
                                           name="city" id="cityInput" placeholder="Enter your city" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold">State</label>
                                    <input type="text" class="stateInput form-control form-control-lg" 
                                           name="state" id="stateInput" placeholder="Enter your state" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-success btn-lg w-100 h-100" id="searchBtn">
                                        <i class="fas fa-search me-2"></i>Search
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Quick Location Buttons -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <small class="text-muted">Quick search popular locations:</small>
                                    <div class="d-flex flex-wrap gap-2 mt-2">
                                        <button type="button" class="btn btn-outline-success btn-sm quick-location" 
                                                data-city="Lagos" data-state="Lagos">
                                            Lagos
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-sm quick-location" 
                                                data-city="Abuja" data-state="FCT">
                                            Abuja
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-sm quick-location" 
                                                data-city="Port Harcourt" data-state="Rivers">
                                            Port Harcourt
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-sm quick-location" 
                                                data-city="Ibadan" data-state="Oyo">
                                            Ibadan
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-sm quick-location" 
                                                data-city="Kano" data-state="Kano">
                                            Kano
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results Section -->
                <div id="stockistResults" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="fw-bold mb-0">Available Stockists</h3>
                        <div>
                            <span class="badge bg-success fs-6" id="resultsCount">0 stockists found</span>
                            <button class="btn btn-outline-success btn-sm ms-2" id="toggleViewBtn">
                                <i class="fas fa-map me-1"></i>Map View
                            </button>
                        </div>
                    </div>
                    
                    <!-- Results Grid -->
                    <div id="stockistList" class="row g-4"></div>
                </div>

                <!-- Map View -->
                <div id="mapView" class="card border-0 shadow-sm rounded-3 mt-4 d-none">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-map-marked-alt me-2 text-success"></i>
                            Stockist Locations
                        </h5>
                        <button class="btn btn-outline-secondary btn-sm" id="closeMapBtn">
                            <i class="fas fa-times me-1"></i>Close Map
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div id="mapContainer" style="height: 500px; border-radius: 0 0 12px 12px; background: #f8f9fa;">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="text-center text-muted">
                                    <i class="fas fa-map fa-3x mb-3"></i>
                                    <p>Map will load with stockist locations after search</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Results Message -->
                <div id="noResults" class="text-center py-5 d-none">
                    <i class="fas fa-search fa-4x text-muted mb-4"></i>
                    <h4 class="text-muted">No Stockists Found</h4>
                    <p class="text-muted mb-4">We couldn't find any stockists in the specified location.</p>
                    <button class="btn btn-success" onclick="clearSearch()">
                        <i class="fas fa-undo me-2"></i>Try Another Search
                    </button>
                </div>

                <!-- How to Redeem Section -->
                <div class="card border-0 shadow-sm rounded-3 mt-5">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2 text-info"></i>
                            How to Redeem Your Products
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="step-icon bg-success rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                                     style="width: 80px; height: 80px;">
                                    <i class="fas fa-receipt fa-2x text-white"></i>
                                </div>
                                <h6>1. Get Invoice Code</h6>
                                <p class="text-muted small">Find your invoice code in order details</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="step-icon bg-success rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                                     style="width: 80px; height: 80px;">
                                    <i class="fas fa-map-marker-alt fa-2x text-white"></i>
                                </div>
                                <h6>2. Find Stockist</h6>
                                <p class="text-muted small">Search for stockists near your location</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="step-icon bg-warning rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                                     style="width: 80px; height: 80px;">
                                    <i class="fas fa-store fa-2x text-white"></i>
                                </div>
                                <h6>3. Visit Location</h6>
                                <p class="text-muted small">Go to stockist with invoice code</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="step-icon bg-info rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                                     style="width: 80px; height: 80px;">
                                    <i class="fas fa-box-open fa-2x text-white"></i>
                                </div>
                                <h6>4. Redeem Products</h6>
                                <p class="text-muted small">Present code and collect items</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('modal') 
<!-- Stockist Details Modal -->

<div class="modal fade" id="stockistDetailsModal" tabindex="-1" aria-labelledby="stockistDetailsModal" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stockistModalTitle">
                    <i class="fas fa-store me-2"></i>Stockist Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="stockistModalBody">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="getDirectionsBtn">
                    <i class="fas fa-directions me-2"></i>Get Directions
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->

<div class="loadingM modal fade " id="loadingModal" tabindex="-1" aria-labelledby="loadingModal" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 bg-transparent shadow-none">
            <div class="modal-body text-center">
                <div class="spinner-border text-success mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="text-white">Searching for stockists...</h5>
                <p class="text-white-50 mb-0">Please wait while we find stockists in your area</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary align-items-center" data-bs-dismiss="modal">Close</button>
                
            </div>
        </div>
    </div>
</div>
@endpush

@push('style')
<style>
    .bg-gradient-light {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    }


    .step-icon {
        transition: transform 0.3s ease;
    }

    .step-icon:hover {
        transform: scale(1.1);
    }

    .stockist-card {
        transition: all 0.3s ease;
        border: 1px solid transparent;
        cursor: pointer;
    }

    .stockist-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
        border-color: #667eea;
    }

    .quick-location {
        transition: all 0.2s ease;
    }

    .quick-location:hover {
        transform: translateY(-1px);
        background-color: #12715d !important;
        color: white !important;
        border-color: #667eea !important;
    }

    #mapContainer {
        min-height: 400px;
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }

    .contact-info a {
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .contact-info a:hover {
        color: #667eea !important;
    }

    .badge {
        font-weight: 500;
    }

    /* Animation for search results */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .stockist-card {
        animation: fadeInUp 0.5s ease;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .display-5 {
            font-size: 2rem;
        }
        
        .btn-group-sm {
            flex-wrap: wrap;
        }
        
        .quick-location {
            margin-bottom: 5px;
        }
        
        #mapContainer {
            height: 300px !important;
        }
    }
</style>
@endpush

@push('script')
<script>
    // Global variables
    let currentStockist = null;
    let searchResults = [];

    $(document).ready(function() {
        // Quick location buttons
        $('.quick-location').on('click', function() {
            const city = $(this).data('city');
            const state = $(this).data('state');
            
            $('.cityInput').val(city);
            $('.stateInput').val(state);
            performSearch(city, state);
        });

        // Search form submission
        $('#searchStockistForm').on('submit', function(e) {
            e.preventDefault();
           
            var city = $('.cityInput').val();
            var state = $('.stateInput').val();
            //alert(city);
            
            if (!city || !state) {
                showAlert('Please enter both city and state to search.', 'warning');
                return;
            }

            performSearch(city, state);
        });

        // Toggle map view
        $('#toggleViewBtn').on('click', function() {
            $('#mapView').removeClass('d-none');
            $('#stockistResults').addClass('d-none');
            initializeMap();
        });

        // Close map view
        $('#closeMapBtn').on('click', function() {
            $('#mapView').addClass('d-none');
            $('#stockistResults').removeClass('d-none');
        });

        // Get Directions button
        $('#getDirectionsBtn').on('click', function() {
            if (currentStockist) {
                const address = encodeURIComponent(currentStockist.full_address);
                const mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${address}`;
                window.open(mapsUrl, '_blank');
            }
        });
    });

    function performSearch(city, state) {
        //showLoading(true);
        
        $.ajax({
            url: "{{ route('user.stockist.search') }}",
            method: 'POST',
            data: {
                city: city,
                state: state,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                searchResults = response;
                displayResults(response);
                showLoading(false);

                
            },
            error: function(xhr) {
                showLoading(false);
                let errorMessage = 'Failed to search for stockists. Please try again.';
                
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                }
                
                showAlert(errorMessage, 'danger');
            }
        });

        
       
    }

    function displayResults(stockists) {
        const $results = $('#stockistResults');
        const $stockistList = $('#stockistList');
        const $resultsCount = $('#resultsCount');
        const $noResults = $('#noResults');
        
        // Hide map view
        $('#mapView').addClass('d-none');
        
        if (stockists.length === 0) {
            $results.addClass('d-none');
            $noResults.removeClass('d-none');
            return;
        }
        
        $noResults.addClass('d-none');
        $resultsCount.text(`${stockists.length} stockist${stockists.length !== 1 ? 's' : ''} found`);
        
        let html = '';
        stockists.forEach(stockist => {
            html += createStockistCard(stockist);
        });
        
        $stockistList.html(html);
        
        // Add click handlers to stockist cards
        $('.stockist-card').on('click', function() {
            const locationId = $(this).data('id');
            const stockist = searchResults.find(s => s.id == locationId);
            showStockistDetails(stockist);
        });
        
        $results.removeClass('d-none');
        
        // Scroll to results
        $('html, body').animate({
            scrollTop: $results.offset().top - 100
        }, 500);
    }

    function createStockistCard(stockist) {
        const services = stockist.services || [];
        const displayServices = services.slice(0, 2);
        
        return `
            <div class="col-md-6 col-lg-4">
                <div class="card stockist-card border-0 shadow-sm rounded-3 h-100" data-id="${stockist.id}">
                    <div class="card-body">
                        <!-- Header with verification badge -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-dark mb-1">${stockist.business_name}</h6>
                                <p class="text-muted small mb-0">${stockist.name}</p>
                            </div>
                            ${stockist.is_verified ? `
                                <span class="badge bg-success ms-2">
                                    <i class="fas fa-check-circle me-1"></i>Verified
                                </span>
                            ` : ''}
                        </div>
                        
                        <!-- Contact Information -->
                        <div class="contact-info mb-3">
                            <p class="small mb-2">
                                <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                ${stockist.city}, ${stockist.state}
                            </p>
                            ${stockist.business_phone ? `
                            <p class="small mb-2">
                                <i class="fas fa-phone text-success me-2"></i>
                                ${stockist.business_phone}
                            </p>` : ''}
                        </div>
                        
                        <!-- Services -->
                        ${services.length > 0 ? `
                        <div class="services mb-3">
                            <small class="text-muted d-block mb-1">Services:</small>
                            ${displayServices.map(service => `
                                <span class="badge bg-light text-dark border small me-1 mb-1">${service}</span>
                            `).join('')}
                            ${services.length > 2 ? `
                                <span class="badge bg-light text-dark border small">+${services.length - 2} more</span>
                            ` : ''}
                        </div>
                        ` : ''}
                        
                        <!-- Action Button -->
                        <div class="d-grid mt-auto">
                            <button class="btn btn-outline-success btn-sm rounded-pill view-details-btn" 
                                    onclick="event.stopPropagation(); showStockistDetailsFromId(${stockist.id})">
                                <i class="fas fa-eye me-1"></i>View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;
    }

    function showStockistDetailsFromId(locationId) {
        const stockist = searchResults.find(s => s.id == locationId);
        if (stockist) {
            showStockistDetails(stockist);
        }
    }

    function showStockistDetails(stockist) {
        currentStockist = stockist;
        
        const modalTitle = $('#stockistModalTitle');
        const modalBody = $('#stockistModalBody');
        
        modalTitle.html(`<i class="fas fa-store me-2"></i>${stockist.business_name}`);
        
        // Format opening hours
        let openingHoursHtml = '<p class="text-muted small">Not specified</p>';
        if (stockist.formatted_opening_hours && stockist.formatted_opening_hours.length > 0) {
            openingHoursHtml = stockist.formatted_opening_hours.map(hours => 
                `<p class="small mb-1">${hours}</p>`
            ).join('');
        }
        
        modalBody.html(`
            <div class="row">
                <div class="col-md-6">
                    <!-- Business Information -->
                    <div class="mb-4">
                        <h6 class="fw-semibold text-success mb-3">
                            <i class="fas fa-info-circle me-2"></i>Business Information
                        </h6>
                        <div class="ps-3">
                            <p class="mb-2">
                                <strong>Contact Person:</strong><br>
                                ${stockist.name}
                            </p>
                            <p class="mb-2">
                                <strong>Business Name:</strong><br>
                                ${stockist.business_name}
                            </p>
                            ${stockist.business_phone ? `
                            <p class="mb-2">
                                <strong>Phone:</strong><br>
                                <a href="tel:${stockist.business_phone}" class="text-decoration-none">${stockist.business_phone}</a>
                            </p>` : ''}
                            ${stockist.business_email ? `
                            <p class="mb-0">
                                <strong>Email:</strong><br>
                                <a href="mailto:${stockist.business_email}" class="text-decoration-none">${stockist.business_email}</a>
                            </p>` : ''}
                        </div>
                    </div>
                    
                    <!-- Location Details -->
                    <div class="mb-4">
                        <h6 class="fw-semibold text-success mb-3">
                            <i class="fas fa-map-marker-alt me-2"></i>Location
                        </h6>
                        <div class="ps-3">
                            <p class="mb-1">${stockist.address_line_1}</p>
                            ${stockist.address_line_2 ? `<p class="mb-1">${stockist.address_line_2}</p>` : ''}
                            <p class="mb-1">${stockist.city}, ${stockist.state}</p>
                            <p class="mb-0">${stockist.country}</p>
                            ${stockist.postal_code ? `<p class="mb-0">Postal Code: ${stockist.postal_code}</p>` : ''}
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <!-- Opening Hours -->
                    <div class="mb-4">
                        <h6 class="fw-semibold text-warning mb-3">
                            <i class="fas fa-clock me-2"></i>Opening Hours
                        </h6>
                        <div class="ps-3">
                            ${openingHoursHtml}
                        </div>
                    </div>
                    
                    <!-- Services -->
                    ${stockist.services && stockist.services.length > 0 ? `
                    <div class="mb-4">
                        <h6 class="fw-semibold text-success mb-3">
                            <i class="fas fa-concierge-bell me-2"></i>Services
                        </h6>
                        <div class="ps-3">
                            ${stockist.services.map(service => `
                                <span class="badge bg-success text-white me-1 mb-1">${service}</span>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Verification Status -->
                    ${stockist.is_verified ? `
                    <div class="alert alert-success small">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Verified Stockist</strong><br>
                        This business has been verified and approved by our team.
                    </div>
                    ` : ''}
                </div>
            </div>
            
            <!-- Redemption Instructions -->
            <div class="alert alert-info mt-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-2 fa-lg"></i>
                    <div>
                        <strong>How to Redeem:</strong> Visit this location with your invoice code and a valid ID to redeem your products. 
                        The stockist will process your redemption and provide your items.
                    </div>
                </div>
            </div>
        `);
        
        $('#stockistDetailsModal').modal('show');
    }

    function initializeMap() {
        const mapContainer = document.getElementById('mapContainer');
        mapContainer.innerHTML = `
            <div class="h-100 w-100 bg-light rounded-bottom position-relative">
                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
                    <div class="text-center text-muted">
                        <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                        <h5>Stockist Locations</h5>
                        <p class="mb-2">${searchResults.length} stockists found in your area</p>
                        <small class="text-muted">
                            Stockists are shown as markers on the map<br>
                            Click on markers to view details
                        </small>
                    </div>
                </div>
                
                <!-- Simple marker simulation -->
                ${searchResults.map((stockist, index) => `
                    <div class="position-absolute stockist-marker" 
                         style="left: ${15 + (index * 12)}%; top: ${20 + (index * 15)}%; cursor: pointer;"
                         onclick="showStockistDetailsFromId(${stockist.id})"
                         data-bs-toggle="tooltip" title="${stockist.business_name}">
                        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center text-white"
                             style="width: 30px; height: 30px; border: 3px solid white; box-shadow: 0 2px 6px rgba(0,0,0,0.3);">
                            <i class="fas fa-store"></i>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
        
        // Initialize tooltips
        $('[data-bs-toggle="tooltip"]').tooltip();
    }

    function showLoading(show) {
        if (show) {
            $('#loadingModal').modal('show');
        } else {
            $('#loadingModal').modal('hide');
        }
    }

    function showAlert(message, type = 'info') {
        // Remove any existing alerts
        $('.alert-dismissible').remove();
        
        const alertClass = {
            'info': 'alert-info',
            'success': 'alert-success',
            'warning': 'alert-warning',
            'danger': 'alert-danger'
        }[type] || 'alert-info';
        
        const iconClass = {
            'info': 'fa-info-circle',
            'success': 'fa-check-circle',
            'warning': 'fa-exclamation-triangle',
            'danger': 'fa-exclamation-circle'
        }[type] || 'fa-info-circle';
        
        const alertHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" 
                 style="z-index: 9999; min-width: 300px; max-width: 90%;" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas ${iconClass} me-2"></i>
                    <div class="flex-grow-1">${message}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        `;
        
        $('body').append(alertHTML);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            $('.alert-dismissible').alert('close');
        }, 5000);
    }

    function clearSearch() {
        $('.cityInput').val('');
        $('.stateInput').val('');
        $('#stockistResults').addClass('d-none');
        $('#noResults').addClass('d-none');
        $('#mapView').addClass('d-none');
        $('#cityInput').focus();
    }
</script>
@endpush
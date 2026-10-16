{{-- resources/views/stockist/find.blade.php --}}

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
                            <div class="row g-3">
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
                                    <label class="form-label fw-semibold invisible">Search</label>
                                    <button type="submit" class="btn btn-success btn-lg w-100" id="searchBtn">
                                        <i class="fas fa-search me-2"></i>Search
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Quick Location Buttons -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <small class="text-muted">Quick search:</small>
                                    <div class="btn-group btn-group-sm ms-2">
                                        <button type="button" class="btn btn-outline-secondary quick-location" data-city="Lagos" data-state="Lagos">Lagos</button>
                                        <button type="button" class="btn btn-outline-secondary quick-location" data-city="Abuja" data-state="FCT">Abuja</button>
                                        <button type="button" class="btn btn-outline-secondary quick-location" data-city="Port Harcourt" data-state="Rivers">Port Harcourt</button>
                                        <button type="button" class="btn btn-outline-secondary quick-location" data-city="Ibadan" data-state="Oyo">Ibadan</button>
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
                        <span class="badge bg-success fs-6" id="resultsCount">0 stockists found</span>
                    </div>
                    
                    <!-- Results Grid -->
                    <div id="stockistList" class="row g-4"></div>
                    
                    <!-- Map View Toggle -->
                    <div class="text-center mt-4">
                        <button class="btn btn-outline-success rounded-pill" id="toggleViewBtn">
                            <i class="fas fa-map me-2"></i>Show Map View
                        </button>
                    </div>
                </div>

                <!-- Map View (Hidden by Default) -->
                <div id="mapView" class="card border-0 shadow-sm rounded-3 mt-4 d-none">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="fas fa-map-marked-alt me-2 text-success"></i>
                            Stockist Locations
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="mapContainer" style="height: 400px; border-radius: 0 0 12px 12px;">
                            <!-- Map will be initialized here -->
                            <div class="d-flex align-items-center justify-content-center h-100 bg-light rounded-bottom">
                                <div class="text-center text-muted">
                                    <i class="fas fa-map fa-3x mb-3"></i>
                                    <p>Map view will appear here after search</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- How to Redeem Section -->
                <div class="card border-0 shadow-sm rounded-3 mt-5">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2 text-success"></i>
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
                                <h6>1. Get Your Invoice Code</h6>
                                <p class="text-muted small">Find your invoice code in your order details</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="step-icon bg-success rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                                     style="width: 80px; height: 80px;">
                                    <i class="fas fa-map-marker-alt fa-2x text-white"></i>
                                </div>
                                <h6>2. Find a Stockist</h6>
                                <p class="text-muted small">Use the search above to find stockists near you</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="step-icon bg-warning rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                                     style="width: 80px; height: 80px;">
                                    <i class="fas fa-store fa-2x text-white"></i>
                                </div>
                                <h6>3. Visit the Location</h6>
                                <p class="text-muted small">Go to the stockist with your invoice code</p>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="step-icon bg-success rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                                     style="width: 80px; height: 80px;">
                                    <i class="fas fa-box-open fa-2x text-white"></i>
                                </div>
                                <h6>4. Redeem Products</h6>
                                <p class="text-muted small">Present your code and collect your items</p>
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
                <h5 class="modal-title" id="stockistModalTitle">Stockist Details</h5>
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

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModal" aria-hidden="true" data-bs-backdrop="static">


    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-body text-center">
                <div class="spinner-border text-success mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="text-white">Searching for stockists...</h5>
                <p class="text-white-50 mb-0">Please wait while we find stockists in your area</p>
            </div>
        </div>
    </div>
</div>
@endpush

@push('script')
<script>
// Remove the sampleStockists object completely

    let map;
    let markers = [];
    let currentStockist = null;

    $(document).ready(function() {
        // Quick location buttons
        $('.quick-location').on('click', function() {
            const city = $(this).data('city');
            const state = $(this).data('state');
            
            $('#cityInput').val(city);
            $('#stateInput').val(state);
            $('#searchStockistForm').submit();
        });

        // Search form submission
        $('#searchStockistForm').on('submit', function(e) {
            e.preventDefault();
            
            const city = $('#cityInput').val().trim();
            const state = $('#stateInput').val().trim();
            
            if (!city || !state) {
                showAlert('Please enter both city and state to search.', 'warning');
                return;
            }

            performSearch(city, state);
        });

        // Toggle between list and map view
        $('#toggleViewBtn').on('click', function() {
            const $mapView = $('#mapView');
            const $results = $('#stockistResults');
            
            if ($mapView.hasClass('d-none')) {
                // Show map view
                $mapView.removeClass('d-none');
                $(this).html('<i class="fas fa-list me-2"></i>Show List View');
                initializeMap();
            } else {
                // Show list view
                $mapView.addClass('d-none');
                $(this).html('<i class="fas fa-map me-2"></i>Show Map View');
            }
        });
    });

    function performSearch(city, state) {
        showLoading(true);
        
        $.ajax({
            url: "{{ route('user.stockist.search') }}",
            method: 'POST',
            data: {
                city: city,
                state: state,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
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
        
        $resultsCount.text(`${stockists.length} stockist${stockists.length !== 1 ? 's' : ''} found`);
        
        if (stockists.length === 0) {
            $stockistList.html(`
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No Stockists Found</h4>
                    <p class="text-muted">We couldn't find any stockists in the specified location.</p>
                    <button class="btn btn-outline-primary" onclick="clearSearch()">Try Another Location</button>
                </div>
            `);
        } else {
            let html = '';
            stockists.forEach(stockist => {
                html += createStockistCard(stockist);
            });
            $stockistList.html(html);
            
            // Add click handlers to stockist cards
            $('.stockist-card').on('click', function() {
                const locationId = $(this).data('id');
                const stockist = stockists.find(s => s.id == locationId);
                showStockistDetails(stockist);
            });
        }
        
        $results.removeClass('d-none');
        $('html, body').animate({
            scrollTop: $results.offset().top - 100
        }, 500);
    }

    function createStockistCard(stockist) {
        const services = stockist.services || [];
        const displayServices = services.slice(0, 3); // Show max 3 services
        
        return `
            <div class="col-md-6 col-lg-4">
                <div class="card stockist-card border-0 shadow-sm rounded-3 h-100" data-id="${stockist.id}">
                    <div class="card-body">
                        <div class="d-flex align-items-start mb-3">
                            <div class="stockist-avatar bg-primary rounded-circle d-flex align-items-center justify-content-center me-3"
                                 style="width: 50px; height: 50px;">
                                <i class="fas fa-store fa-lg text-white"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="fw-bold text-dark mb-1">${stockist.business_name}</h6>
                                <p class="text-muted small mb-0">${stockist.name}</p>
                                ${stockist.is_verified ? `
                                    <span class="badge bg-success badge-sm mt-1">
                                        <i class="fas fa-check-circle me-1"></i>Verified
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                        
                        <div class="contact-info mb-3">
                            <p class="small mb-2">
                                <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                ${stockist.full_address}
                            </p>
                            ${stockist.business_phone ? `
                            <p class="small mb-2">
                                <i class="fas fa-phone text-success me-2"></i>
                                <a href="tel:${stockist.business_phone}" class="text-dark">${stockist.business_phone}</a>
                            </p>` : ''}
                        </div>
                        
                        ${services.length > 0 ? `
                        <div class="services mb-3">
                            ${displayServices.map(service => `
                                <span class="badge bg-light text-dark border small me-1 mb-1">${service}</span>
                            `).join('')}
                            ${services.length > 3 ? `
                                <span class="badge bg-light text-dark border small">+${services.length - 3} more</span>
                            ` : ''}
                        </div>
                        ` : ''}
                        
                        <div class="d-grid">
                            <button class="btn btn-outline-primary btn-sm rounded-pill view-details-btn" data-id="${stockist.id}">
                                <i class="fas fa-eye me-1"></i>View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;
    }

    function showStockistDetails(stockist) {
        currentStockist = stockist;
        
        const modalTitle = $('#stockistModalTitle');
        const modalBody = $('#stockistModalBody');
        
        modalTitle.text(stockist.business_name);
        
        // Format opening hours for display
        let openingHoursHtml = '';
        if (stockist.formatted_opening_hours && stockist.formatted_opening_hours.length > 0) {
            openingHoursHtml = stockist.formatted_opening_hours.map(hours => 
                `<p class="small mb-1">${hours}</p>`
            ).join('');
        } else if (stockist.opening_hours) {
            // Fallback to raw opening hours data
            const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            days.forEach(day => {
                if (stockist.opening_hours[day] && stockist.opening_hours[day].open) {
                    openingHoursHtml += `<p class="small mb-1">${day.charAt(0).toUpperCase() + day.slice(1)}: ${stockist.opening_hours[day].open} - ${stockist.opening_hours[day].close}</p>`;
                }
            });
        } else {
            openingHoursHtml = '<p class="text-muted small">Opening hours not specified</p>';
        }
        
        modalBody.html(`
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <h6 class="fw-semibold text-primary mb-3">
                            <i class="fas fa-info-circle me-2"></i>Contact Information
                        </h6>
                        <div class="ps-3">
                            <p class="mb-2">
                                <i class="fas fa-user me-2 text-muted"></i>
                                <strong>Contact:</strong> ${stockist.name}
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-map-marker-alt me-2 text-muted"></i>
                                <strong>Address:</strong> ${stockist.full_address}
                            </p>
                            ${stockist.business_phone ? `
                            <p class="mb-2">
                                <i class="fas fa-phone me-2 text-muted"></i>
                                <strong>Phone:</strong> <a href="tel:${stockist.business_phone}" class="text-decoration-none">${stockist.business_phone}</a>
                            </p>` : ''}
                            ${stockist.business_email ? `
                            <p class="mb-0">
                                <i class="fas fa-envelope me-2 text-muted"></i>
                                <strong>Email:</strong> <a href="mailto:${stockist.business_email}" class="text-decoration-none">${stockist.business_email}</a>
                            </p>` : ''}
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-semibold text-success mb-3">
                            <i class="fas fa-clock me-2"></i>Opening Hours
                        </h6>
                        <div class="ps-3">
                            ${openingHoursHtml}
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    ${stockist.services && stockist.services.length > 0 ? `
                    <div class="mb-4">
                        <h6 class="fw-semibold text-info mb-3">
                            <i class="fas fa-concierge-bell me-2"></i>Services Offered
                        </h6>
                        <div class="ps-3">
                            ${stockist.services.map(service => `
                                <span class="badge bg-info text-dark me-1 mb-1">${service}</span>
                            `).join('')}
                        </div>
                    </div>
                    ` : ''}
                    
                    <div class="mb-4">
                        <h6 class="fw-semibold text-warning mb-3">
                            <i class="fas fa-map-marked-alt me-2"></i>Location Map
                        </h6>
                        <div id="miniMap" style="height: 150px; border-radius: 8px; background: #f8f9fa;" class="d-flex align-items-center justify-content-center">
                            <div class="text-center text-muted">
                                <i class="fas fa-map fa-2x mb-2"></i>
                                <p class="small mb-0">Location: ${stockist.city}, ${stockist.state}</p>
                                ${stockist.latitude && stockist.longitude ? `
                                <button class="btn btn-sm btn-outline-primary mt-2" onclick="openInGoogleMaps(${stockist.latitude}, ${stockist.longitude})">
                                    <i class="fas fa-external-link-alt me-1"></i>Open in Maps
                                </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>

                    ${stockist.is_verified ? `
                    <div class="alert alert-success small">
                        <i class="fas fa-shield-alt me-2"></i>
                        <strong>Verified Stockist</strong> - This business has been verified by our team.
                    </div>
                    ` : ''}
                </div>
            </div>
            
            <div class="alert alert-info mt-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-2 fa-lg"></i>
                    <div>
                        <strong>Redemption Process:</strong> Visit this location with your invoice code to redeem your products. 
                        Make sure to bring a valid ID for verification.
                    </div>
                </div>
            </div>
        `);
        
        $('#stockistDetailsModal').modal('show');
    }

    function initializeMap() {
        // For now, we'll keep the simple map representation
        // In a real implementation, you would fetch stockists and plot them on a real map
        const mapContainer = document.getElementById('mapContainer');
        
        // Clear previous map content
        mapContainer.innerHTML = '';
        
        // Create a simple map representation
        const mapHTML = `
            <div class="h-100 w-100 bg-light rounded-bottom position-relative">
                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
                    <div class="text-center text-muted">
                        <i class="fas fa-map-marked-alt fa-3x mb-3"></i>
                        <h5>Interactive Map</h5>
                        <p class="mb-2">Stockist locations would be shown here on a real map</p>
                        <small class="text-muted">Map integration would show actual locations from database</small>
                    </div>
                </div>
            </div>
        `;
        
        mapContainer.innerHTML = mapHTML;
    }

    function openInGoogleMaps(lat, lng) {
        const mapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;
        window.open(mapsUrl, '_blank');
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
        
        const alertHTML = `
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" 
                 style="z-index: 9999; min-width: 300px;" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'warning' ? 'exclamation-triangle' : type === 'danger' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                    <div>${message}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('body').append(alertHTML);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            $('.alert-dismissible').alert('close');
        }, 5000);
    }

    function clearSearch() {
        $('#cityInput').val('');
        $('#stateInput').val('');
        $('#cityInput').focus();
    }

    // Get Directions button handler
    $('#getDirectionsBtn').on('click', function() {
        if (currentStockist) {
            const address = encodeURIComponent(currentStockist.full_address);
            const mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${address}`;
            window.open(mapsUrl, '_blank');
        }
    });

    // Prevent form submission when clicking view details buttons
    $(document).on('click', '.view-details-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const locationId = $(this).data('id');
        
        // We need to find the stockist from the current results
        // This would be better if we stored the results in a variable
        // For now, we'll rely on the currentStockist being set when displaying results
        if (currentStockist && currentStockist.id == locationId) {
            showStockistDetails(currentStockist);
        }
    });

    // Initialize tooltips
    $(function () {
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>
@endpush
@extends($activeTemplate . 'layouts.app')
@section('panel')
    @include($activeTemplate . 'partials.header')

     @if (Auth::user()->section == 1)
       @include($activeTemplate.'partials.dashboard')      
    @else
        @include($activeTemplate.'partials.dashboard2')
    @endif
    @include($activeTemplate . 'partials.footer')

@endsection
@push('style')
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%) !important;
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;
    }
    .bg-gradient-success { 
        background: linear-gradient(135deg, #b8efc4 0%, #26d98e 100%) !important;
    }

    .redemption-card {
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .redemption-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.1) !important;
        border-color: #667eea;
    }

    .redemption-icon .bg-success {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    }

    .quick-info-card {
        border-left: 4px solid #667eea;
    }

    .product-icon {
        transition: transform 0.3s ease;
    }

    .product-icon:hover {
        transform: scale(1.1);
    }

    .info-item {
        transition: background-color 0.2s ease;
    }

    .info-item:hover {
        background-color: #f8f9fa !important;
    }

    .empty-history-icon {
        opacity: 0.5;
    }

    /* Custom pagination */
    .pagination .page-link {
        border: none;
        border-radius: 8px;
        margin: 0 2px;
        color: #667eea;
    }

    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }

    .pagination .page-link:hover {
        background-color: #f8f9fa;
        color: #764ba2;
    }

    /* Table enhancements */
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }

    /* Badge enhancements */
    .badge {
        font-weight: 500;
    }

    /* Animation for cards */
    .redemption-card {
        animation: fadeInUp 0.5s ease;
    }

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

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .redemption-amount {
            text-align: left !important;
            margin-top: 1rem;
        }
        
        .redemption-meta {
            text-align: left !important;
            margin-top: 1rem;
        }
        
        .quick-info-card {
            margin-top: 1rem;
        }
        
        .stats-card .card-body {
            padding: 1rem !important;
        }
        .display-5 {
            font-size: 2rem;
        }
        
        .btn-group-sm {
            flex-wrap: wrap;
        }
        
        .quick-location {
            margin-bottom: 5px;
        }
    }

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
    }

    #mapContainer {
        min-height: 400px;
    }

    .stockist-marker {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: 3px solid white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
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
    .stockist-card {
        animation: fadeInUp 0.5s ease;
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

</style>
@endpush
@push('script')
    <script>
        (function($) {
            "use strict";
            window.addEventListener('scroll', function(){
              var header = document.querySelector('header');
              header.classList.toggle('sticky', window.scrollY > 0);
            });   
        })(jQuery);
    </script>
@endpush

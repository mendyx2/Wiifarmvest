@extends('admin.layouts.master')
@section('content')
@php
    $sidenav = file_get_contents(resource_path('views/admin/partials/sidenav.json'));
@endphp
    <!-- page-wrapper start -->
    <div class="page-wrapper default-version">
 
        @include('admin.partials.sidenav')
        @include('admin.partials.topnav')

        <div class="container-fluid px-3 px-sm-0">
            <div class="body-wrapper">
                <div class="bodywrapper__inner">

                    @stack('topBar')
                    @include('admin.partials.breadcrumb')

                    @yield('panel')

                </div><!-- bodywrapper__inner end -->
            </div><!-- body-wrapper end -->
        </div>
    </div>
@endsection
@push('style')
    <style> 
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #1b8754 0%, #26d98e 100%) !important;
        }

        .bg-gradient-info {
            background: linear-gradient(135deg, #38f9d7 0%, #38f9d7 100%) !important;
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;
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
        }
    </style>
@endpush

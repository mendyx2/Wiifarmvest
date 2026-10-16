@extends('admin.layouts.app')

@section('title', 'Stockist Dashboard')

@section('panel')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Stockist Dashboard</h1>
        <a href="{{ route('admin.stockist.activation-page') }}" class="btn btn-primary btn-lg">
            <i class="fas fa-user-plus"></i> Activate New Stockist
        </a>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <!-- Total Stockists -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Stockists</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalStockists }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Verified Stockists -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Verified Stockists</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $verifiedStockists }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Stockists -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Active Stockists</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activeStockists }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-store fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Products -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Products in Stock</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalProducts) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stockists List -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Stockists</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="stockistsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Business Info</th>
                                     <th>State</th>
                                    <th>Store Type</th>
                                    <th>Contact</th>
                                    <th>Wallet Balance</th> 
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th>Verified</th>
                                    <th>Actions</th> 
                                    
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($stockists as $stockist)
                                <tr>
                                    <td>{{ $stockist->id }}</td>
                                    <td>
                                        <strong>{{ $stockist->business_name }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            <a href="{{ route('admin.users.detail', $stockist->user->id) }}" class="btn btn-sm btn-outline--primary">
                                            <i class="las la-desktop"></i> User: {{ $stockist->user->username ?? 'N/A' }}
                                        </a>

                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge--success">{{ $stockist->state_name }}</span>
                                    </td>
                                    <td>
                                        @if($stockist->store_type == 1)
                                            <span class="badge badge--success badge-pill">Mega Store</span>
                                        
                                        @else
                                            <span class="badge badge--primary badge-pill">Mini Store</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>
                                            <i class="fas fa-envelope text-primary"></i> {{ $stockist->business_email }}<br>
                                            <i class="fas fa-phone text-success"></i> {{ $stockist->business_phone }}
                                        </small>
                                    </td>
                                    
                                    <td>
                                        <span class="font-weight-bold text-success">
                                            ₦{{ number_format($stockist->wallet, 2) }}
                                        </span>
                                    </td>
                                   
                                    <td>
                                        <span class="badge badge--primary">
                                            {{ number_format($stockist->total_products) }} items
                                        </span>
                                    </td>
                                    <td>
                                        @if($stockist->is_active)
                                            <span class="badge badge--success">Active</span>
                                        @else
                                            <span class="badge badge--danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($stockist->is_verified)
                                            <span class="badge badge--success">Verified</span>
                                            <br>
                                            <small class="text-muted">{{ $stockist->verified_at->format('M d, Y') }}</small>
                                        @else
                                            <span class="badge badge--warning">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.stockist.details', $stockist) }}" 
                                               class="btn btn-primary" title="View Details">
                                                <i class="fas fa-eye"></i> View
                                            </a> 
                                            <a href="{{ route('admin.stockist.wallet-page', $stockist) }}" 
                                               class="btn btn-success" title="Top Up Wallet">
                                                <i class="fas fa-wallet"></i> Top-up Wallet
                                            </a>
                                            
                                        </div>
                                    </td>
                                    
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-store fa-3x mb-3"></i>
                                        <p>No stockists found</p>
                                        <a href="{{ route('admin.stockist.activation-page') }}" class="btn btn-primary">
                                            Activate First Stockist
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    @if($stockists->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $stockists->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
.card {
    border: none;
    border-radius: 10px;
    transition: transform 0.2s ease-in-out;
}

.card:hover {
    transform: translateY(-2px);
}

.border-left-primary {
    border-left: 4px solid #4e73df !important;
}

.border-left-success {
    border-left: 4px solid #1cc88a !important;
}

.border-left-info {
    border-left: 4px solid #36b9cc !important;
}

.border-left-warning {
    border-left: 4px solid #f6c23e !important;
}

.table th {
    border-top: none;
    font-weight: 700;
    background-color: #f8f9fc;
}

.badge-pill {
    border-radius: 10rem;
}

.btn-group .btn {
    border-radius: 6px;
    margin: 2px;
}
</style>
@endpush
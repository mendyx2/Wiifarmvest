@extends('admin.layouts.app')

@section('title', 'Order Details - ' . $order->order_number)

@section('panel')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
            <div class="mr-3">
                <i class="fas fa-file-invoice-dollar fa-2x text-primary"></i>
            </div>
            <div>
                <h1 class="h3 mb-0 text-gray-800">Order #{{ $order->order_number }}</h1>
                <p class="text-muted mb-0">Stockist: {{ $order->stockist->user->name ?? 'N/A' }}</p>
            </div>
        </div>
        <div class="d-flex">
            <a href="{{ route('admin.stockist-orders.index') }}" class="btn btn-secondary mr-2">
                <i class="fas fa-arrow-left mr-2"></i>Back to Orders
            </a>
            <button class="btn btn-light" onclick="window.print()">
                <i class="fas fa-print mr-2"></i>Print
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Amount</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">₦{{ number_format($order->grand_total, 2) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Items</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $order->items->sum('quantity') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Order Date</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $order->created_at->format('M d, Y') }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Status</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <span class="badge badge--{{ $order->getStatusBadgeClass() }} px-3 py-2">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-flag fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Order Items Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center bg-gradient-primary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-shopping-cart mr-2"></i>Order Items
                    </h6>
                    <span class="badge badge-light">{{ $order->items->count() }} items</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-right">Unit Price</th>
                                    <th class="text-right">Total Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            

                                            @if($item->product->thumbnail)
                                            <img src="{{ getImage(getFilePath('products') . '/' . $item->product->thumbnail, getFileSize('products')) }}" 
                                                
                                                 class="card-img-top"
                                                 style="height: 60px; object-fit: cover;" alt="{{ $item->product->name ?? 'N/A' }}">

                                            @else
                                                <div class="mr-3">
                                                <i class="fas fa-box text-primary"></i>
                                            </div>
                                            @endif

                                            <div>
                                                <strong>{{ $item->product->name ?? 'N/A' }}</strong>
                                                @if($item->product->sku ?? false)
                                                <br><small class="text-muted">SKU: {{ $item->product->sku }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-pill badge--primary px-3">{{ $item->quantity }}</span>
                                    </td>
                                    <td class="text-right">₦{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="text-right font-weight-bold">₦{{ number_format($item->total_price, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="thead-light">
                                <tr>
                                    <th colspan="3" class="text-right">Subtotal:</th>
                                    <th class="text-right">₦{{ number_format($order->total_amount, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-right">Shipping:</th>
                                    <th class="text-right">₦{{ number_format($order->shipping_cost, 2) }}</th>
                                </tr>
                                <tr>
                                    <th colspan="3" class="text-right">Tax:</th>
                                    <th class="text-right">₦{{ number_format($order->tax_amount, 2) }}</th>
                                </tr>
                                <tr class="table-primary">
                                    <th colspan="3" class="text-right">Grand Total:</th>
                                    <th class="text-right">₦{{ number_format($order->grand_total, 2) }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Progress Timeline -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-gradient-info text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-road mr-2"></i>Order Journey
                    </h6>
                </div>
                <div class="card-body">
                    <div class="order-progress">
                        <div class="progress-step {{ $order->created_at ? 'completed' : '' }} {{ $order->status === 'pending' ? 'current' : '' }}">
                            <div class="step-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div class="step-content">
                                <h6>Order Placed</h6>
                                <p>{{ $order->created_at ? $order->created_at->format('M d, Y H:i') : 'Pending' }}</p>
                            </div>
                        </div>
                        <div class="progress-step {{ $order->approved_at ? 'completed' : '' }} {{ $order->status === 'approved' ? 'current' : '' }}">
                            <div class="step-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="step-content">
                                <h6>Approved</h6>
                                <p>{{ $order->approved_at ? $order->approved_at->format('M d, Y H:i') : 'Pending' }}</p>
                            </div>
                        </div>
                        <div class="progress-step {{ $order->status === 'processing' ? 'current' : '' }}">
                            <div class="step-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="step-content">
                                <h6>Processing</h6>
                                <p>In preparation</p>
                            </div>
                        </div>
                        <div class="progress-step {{ $order->shipped_at ? 'completed' : '' }} {{ $order->status === 'shipped' ? 'current' : '' }}">
                            <div class="step-icon">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <div class="step-content">
                                <h6>Shipped</h6>
                                <p>{{ $order->shipped_at ? $order->shipped_at->format('M d, Y H:i') : 'Pending' }}</p>
                            </div>
                        </div>
                        <div class="progress-step {{ $order->delivered_at ? 'completed' : '' }} {{ $order->status === 'delivered' ? 'current' : '' }}">
                            <div class="step-icon">
                                <i class="fas fa-truck-loading"></i>
                            </div>
                            <div class="step-content">
                                <h6>Delivered</h6>
                                <p>{{ $order->delivered_at ? $order->delivered_at->format('M d, Y H:i') : 'Pending' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-gradient-success text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-bolt mr-2"></i>Quick Actions
                    </h6>
                </div>
                <div class="card-body">
                    @if($order->canBeApproved())
                        <form action="{{ route('admin.stockist-orders.approve', [$order->id]) }}" method="POST"
                                enctype="multipart/form-data" onsubmit="return confirm('Are you sure you want to Approve this  Order?');">
                                @csrf
                        <button class="btn btn-success btn-block btn-action mb-3" onclick="approveOrder({{ $order->id }})">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="fas fa-check-circle fa-2x mr-3"></i>
                                <div class="text-left">
                                    <strong>Approve Order</strong>
                                    <br><small>Confirm and process this order</small>
                                </div>
                            </div>
                        </button>
                    </form>
                    @endif

                    @if($order->status === 'approved')
                     <form action="{{ route('admin.stockist-orders.processOrder', [$order->id]) }}" method="POST"
                                enctype="multipart/form-data" onsubmit="return confirm('Are you sure you want to Mark as processing?');">
                                @csrf
                        <button class="btn btn-info btn-block btn-action mb-3" >
                        <div class="d-flex align-items-center justify-content-center">
                            <i class="fas fa-cogs fa-2x mr-3"></i>
                            <div class="text-left">
                                <strong>Mark as Processing</strong>
                                <br><small>Order is being prepared</small>
                            </div>
                        </div>
                    </button>
                    </form>

                    
                    @endif

                    @if($order->canBeShipped())

                    <form action="{{ route('admin.stockist-orders.ship', [$order->id]) }}" method="POST"
                                enctype="multipart/form-data" onsubmit="return confirm('Are you sure you want to Mark as Shipped?');">
                                @csrf
                        <button class="btn btn-primary btn-block btn-action mb-3"  >
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="fas fa-shipping-fast fa-2x mr-3"></i>
                                <div class="text-left">
                                    <strong>Mark as Shipped</strong>
                                    <br><small>Order has been dispatched</small>
                                </div>
                            </div>
                        </button>
                    </form>

                    
                    @endif

                    @if($order->canBeDelivered())
                    <form action="{{ route('admin.stockist-orders.deliver', [$order->id]) }}" method="POST"
                                enctype="multipart/form-data" onsubmit="return confirm('Are you sure you want to Mark as Delivered?');">
                                @csrf
                        <button class="btn btn-warning btn-block btn-action mb-3">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="fas fa-truck-loading fa-2x mr-3"></i>
                                <div class="text-left">
                                    <strong>Mark as Delivered</strong>
                                    <br><small>Update inventory & complete</small>
                                </div>
                            </div>
                        </button>
                    </form>

                    @endif

                    @if($order->canBeCancelled())
                    <form action="{{ route('admin.stockist-orders.cancel', [$order->id]) }}" method="POST"
                                enctype="multipart/form-data" onsubmit="return confirm('Are you sure you want to Cancel this Order?');">
                                @csrf
                        <button class="btn btn-danger btn-block btn-action">
                            <div class="d-flex align-items-center justify-content-center">
                                <i class="fas fa-times-circle fa-2x mr-3"></i>
                                <div class="text-left">
                                    <strong>Cancel Order</strong>
                                    <br><small>Refund and cancel order</small>
                                </div>
                            </div>
                        </button>
                    </form>

                    
                    @endif

                    @if(!$order->canBeApproved() && !$order->canBeShipped() && !$order->canBeDelivered() && !$order->canBeCancelled())
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                        <p>No actions available<br><small>This order has been completed</small></p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Order Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-gradient-secondary text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-info-circle mr-2"></i>Order Details
                    </h6>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <i class="fas fa-user text-primary"></i>
                        <div class="info-content">
                            <strong>Stockist</strong>
                            <p>{{ $order->stockist->user->fullname ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-building text-success"></i>
                        <div class="info-content">
                            <strong>Business</strong>
                            <p>{{ $order->stockist->business_name ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar-alt text-info"></i>
                        <div class="info-content">
                            <strong>Order Date</strong>
                            <p>{{ $order->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>
                    @if($order->notes)
                    <div class="info-item">
                        <i class="fas fa-sticky-note text-warning"></i>
                        <div class="info-content">
                            <strong>Notes</strong>
                            <p class="mb-0">{{ $order->notes }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Status History -->
            <div class="card shadow">
                <div class="card-header py-3 bg-gradient-warning text-white">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-history mr-2"></i>Status History
                    </h6>
                </div>
                <div class="card-body">
                    <div class="status-history">
                        <div class="status-item">
                            <i class="fas fa-circle text-primary"></i>
                            <div class="status-content">
                                <strong>Order Created</strong>
                                <span class="text-muted">{{ $order->created_at->format('M d, Y H:i') }}</span>
                            </div>
                        </div>
                        @if($order->approved_at)
                        <div class="status-item">
                            <i class="fas fa-circle text-success"></i>
                            <div class="status-content">
                                <strong>Order Approved</strong>
                                <span class="text-muted">{{ $order->approved_at->format('M d, Y H:i') }}</span>
                            </div>
                        </div>
                        @endif
                        @if($order->shipped_at)
                        <div class="status-item">
                            <i class="fas fa-circle text-info"></i>
                            <div class="status-content">
                                <strong>Order Shipped</strong>
                                <span class="text-muted">{{ $order->shipped_at->format('M d, Y H:i') }}</span>
                            </div>
                        </div>
                        @endif
                        @if($order->delivered_at)
                        <div class="status-item">
                            <i class="fas fa-circle text-success"></i>
                            <div class="status-content">
                                <strong>Order Delivered</strong>
                                <span class="text-muted">{{ $order->delivered_at->format('M d, Y H:i') }}</span>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


@endsection
@push('modal')

<!-- Result Modal -->

<div class="modal fade" id="resultModal" tabindex="-1" aria-labelledby="resultModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="resultModalLabel">
                    <i class="fas fa-info-circle mr-2"></i>Order Status Update
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center py-4">
                <div id="modalMessage"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="reloadButton" style="display: none;" onclick="location.reload()">
                    <i class="fas fa-sync-alt mr-2"></i>Reload Page
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->

<div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-warning text-white">
                <h5 class="modal-title" id="confirmationModalLabel">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Confirm Action
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="confirmationMessage"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-2"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmActionButton">
                    <i class="fas fa-check mr-2"></i>Confirm
                </button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('style')
<style>
    /* Custom Styles */
    .card-header.bg-gradient-primary { background: linear-gradient(45deg, #4e73df, #224abe); }
    .card-header.bg-gradient-success { background: linear-gradient(45deg, #1cc88a, #13855c); }
    .card-header.bg-gradient-info { background: linear-gradient(45deg, #36b9cc, #258391); }
    .card-header.bg-gradient-warning { background: linear-gradient(45deg, #f6c23e, #dda20a); }
    .card-header.bg-gradient-secondary { background: linear-gradient(45deg, #858796, #656776); }

    .btn-action {
        transition: all 0.3s ease;
        border: none;
        padding: 15px;
        border-radius: 10px;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    /* Order Progress */
    .order-progress {
        display: flex;
        justify-content: space-between;
        position: relative;
    }

    .order-progress::before {
        content: '';
        position: absolute;
        top: 30px;
        left: 0;
        right: 0;
        height: 3px;
        background: #e0e0e0;
        z-index: 1;
    }

    .progress-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
        flex: 1;
    }

    .step-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        border: 4px solid white;
        transition: all 0.3s ease;
    }

    .step-icon i {
        font-size: 1.2rem;
        color: #6c757d;
    }

    .progress-step.completed .step-icon {
        background: #1cc88a;
        border-color: #1cc88a;
    }

    .progress-step.completed .step-icon i {
        color: white;
    }

    .progress-step.current .step-icon {
        background: #4e73df;
        border-color: #4e73df;
        transform: scale(1.1);
    }

    .progress-step.current .step-icon i {
        color: white;
    }

    .step-content {
        text-align: center;
    }

    .step-content h6 {
        margin: 0;
        font-weight: 600;
    }

    .step-content p {
        margin: 5px 0 0 0;
        font-size: 0.8rem;
        color: #6c757d;
    }

    /* Info Items */
    .info-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 20px;
    }

    .info-item:last-child {
        margin-bottom: 0;
    }

    .info-item i {
        font-size: 1.2rem;
        margin-right: 15px;
        margin-top: 2px;
    }

    .info-content strong {
        display: block;
        color: #5a5c69;
        font-size: 0.9rem;
    }

    .info-content p {
        margin: 5px 0 0 0;
        color: #858796;
    }

    /* Status History */
    .status-history {
        position: relative;
        padding-left: 20px;
    }

    .status-history::before {
        content: '';
        position: absolute;
        left: 9px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e0e0e0;
    }

    .status-item {
        display: flex;
        align-items: flex-start;
        margin-bottom: 20px;
        position: relative;
    }

    .status-item:last-child {
        margin-bottom: 0;
    }

    .status-item i {
        font-size: 0.8rem;
        margin-right: 15px;
        margin-top: 4px;
        z-index: 2;
        background: white;
    }

    .status-content {
        flex: 1;
    }

    .status-content strong {
        display: block;
        font-size: 0.9rem;
        color: #5a5c69;
    }

    .status-content span {
        font-size: 0.8rem;
    }

    /* Hover Effects */
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 20px rgba(0,0,0,0.1) !important;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(78, 115, 223, 0.05);
    }
</style>
@endpush

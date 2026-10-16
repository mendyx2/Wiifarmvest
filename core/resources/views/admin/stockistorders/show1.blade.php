
@extends('admin.layouts.app')

@section('title', 'Order Details - ' . $order->order_number)

@section('panel')

<div class="container-fluid">

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    
</div>
<div class="container-fluid py-4">
    <div class="container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 fw-bold text-dark mb-1">Order Details</h1>
                        <p class="text-muted mb-0">Order #{{ $order->order_number }}</p>
                    </div>
                    <div class="text-end">
                        <a href="{{ route('admin.stockist-orders.index') }}" class="btn btn-outline-success">
                            <i class="fas fa-arrow-left me-2"></i>Back to Orders
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Order Information -->
            <div class="col-lg-8">
                <!-- Order Status Card -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center">
                                    <div class="status-icon me-4">
                                        @switch($order->status)
                                            @case('pending')
                                                <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 60px; height: 60px;">
                                                    <i class="fas fa-clock fa-lg text-white"></i>
                                                </div>
                                                @break
                                            @case('approved')
                                                <div class="bg-info rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 60px; height: 60px;">
                                                    <i class="fas fa-check fa-lg text-white"></i>
                                                </div>
                                                @break
                                            @case('processing')
                                                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 60px; height: 60px;">
                                                    <i class="fas fa-cog fa-lg text-white"></i>
                                                </div>
                                                @break
                                            @case('shipped')
                                                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 60px; height: 60px;">
                                                    <i class="fas fa-shipping-fast fa-lg text-white"></i>
                                                </div>
                                                @break
                                            @case('delivered')
                                                <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 60px; height: 60px;">
                                                    <i class="fas fa-check-circle fa-lg text-white"></i>
                                                </div>
                                                @break
                                            @case('cancelled')
                                                <div class="bg-danger rounded-circle d-flex align-items-center justify-content-center" 
                                                     style="width: 60px; height: 60px;">
                                                    <i class="fas fa-times fa-lg text-white"></i>
                                                </div>
                                                @break
                                        @endswitch
                                    </div>
                                    <div>
                                        <h4 class="fw-bold text-capitalize mb-1">{{ $order->status }}</h4>
                                        <p class="text-muted mb-0">
                                            Order placed on {{ $order->created_at->format('F j, Y \a\t g:i A') }}
                                        </p>
                                        @if($order->approved_at)
                                            <p class="text-success mb-0 small">
                                                Approved on {{ $order->approved_at->format('F j, Y') }}
                                            </p>
                                        @endif
                                        @if($order->delivered_at)
                                            <p class="text-success mb-0 small">
                                                Delivered on {{ $order->delivered_at->format('F j, Y') }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="total-amount">
                                    <h3 class="fw-bold text-success mb-0">₦{{ number_format($order->grand_total, 2) }}</h3>
                                    <small class="text-muted">Total Amount</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="fas fa-boxes me-2 text-success"></i>
                            Order Items
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)

                                    <tr>
                                        <td> 
                                            <div class="d-flex align-items-center"> 
                                                @if($item->product->thumbnail)
                                                    <img src="{{ getImage(getFilePath('products') . '/' . $item->product->thumbnail, getFilePath('products')) }}" 
                                                         alt="{{ $item->product->name }}" 
                                                         class="rounded me-3"
                                                         style="width: 50px; height: 50px; object-fit: cover;">
                                                    
                                                @else
                                                    <div class="bg-light rounded d-flex align-items-center justify-content-center me-3"
                                                         style="width: 50px; height: 50px;">
                                                        <i class="fas fa-box text-muted"></i>
                                                    </div>
                                                @endif
                                                <div>
                                                    <h6 class="mb-0">{{ $item->product->name }}</h6>
                                                    <small class="text-muted">SKU: {{ $item->product->id }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>₦{{ number_format($item->unit_price, 2) }}</td>
                                        <td>
                                            <span class="badge bg-secondary fs-6">{{ $item->quantity }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-success">₦{{ number_format($item->total_price, 2) }}</strong>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Subtotal:</td>
                                        <td class="fw-bold">₦{{ number_format($order->total_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Tax (5%):</td>
                                        <td class="fw-bold">₦{{ number_format($order->tax_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">Shipping:</td>
                                        <td class="fw-bold text-success">₦{{ number_format($order->shipping_cost, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold fs-5">Grand Total:</td>
                                        <td class="fw-bold fs-5 text-success">₦{{ number_format($order->grand_total, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                @if($order->notes)
                <!-- Order Notes -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="fas fa-sticky-note me-2 text-info"></i>
                            Order Notes
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-0">{{ $order->notes }}</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Order Summary & Timeline -->
            <div class="col-lg-4">
                <!-- Order Summary -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-gradient-primary text-white py-3 rounded-top">
                        <h5 class="mb-0 text-center">Order Summary</h5>
                    </div>
                     

                    <div class="card-body">
                        <div class="info-item d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded-2">
                            <span class="fw-semibold">{{ $order->stockist->user->fullname }}</span><br/>
                           
                            <code>{{ $order->stockist->user->username }}</code>
                        </div>
                        <div class="info-item d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded-2">
                            <span class="fw-semibold">Order Number:</span>
                            <code>{{ $order->order_number }}</code>
                        </div>
                        <div class="info-item d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded-2">
                            <span class="fw-semibold">Order Date:</span>
                            <span>{{ $order->created_at->format('M j, Y') }}</span>
                        </div>
                        <div class="info-item d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded-2">
                            <span class="fw-semibold">Items:</span>
                            <span>{{ $order->items->count() }}</span>
                        </div>
                        <div class="info-item d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded-2">
                            <span class="fw-semibold">Total Units:</span>
                            <span>{{ $order->items->sum('quantity') }}</span>
                        </div>
                        <div class="info-item d-flex justify-content-between align-items-center p-2 bg-light rounded-2">
                            <span class="fw-semibold">Payment Method:</span>
                            <span class="text-success">Wallet</span>
                        </div>
                    </div>
                </div>

                <!-- Order Timeline -->
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0">
                            <i class="fas fa-history me-2 text-info"></i>
                            Order Timeline
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item d-flex mb-3">
                                <div class="timeline-icon me-3">
                                    <div class="bg-success rounded-circle d-flex align-items-center justify-content-center"
                                         style="width: 40px; height: 40px;">
                                        <i class="fas fa-shopping-cart text-white"></i>
                                    </div>
                                </div>
                                <div class="timeline-content flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Order Placed</h6>
                                    <small class="text-muted">{{ $order->created_at->format('M j, Y \a\t g:i A') }}</small>
                                </div>
                            </div>

                            @if($order->approved_at)
                            <div class="timeline-item d-flex mb-3">
                                <div class="timeline-icon me-3">
                                    <div class="bg-info rounded-circle d-flex align-items-center justify-content-center"
                                         style="width: 40px; height: 40px;">
                                        <i class="fas fa-check text-white"></i>
                                    </div>
                                </div>
                                <div class="timeline-content flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Order Approved</h6>
                                    <small class="text-muted">{{ $order->approved_at->format('M j, Y \a\t g:i A') }}</small>
                                </div>
                            </div>
                            @endif

                            @if($order->shipped_at)
                            <div class="timeline-item d-flex mb-3">
                                <div class="timeline-icon me-3">
                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
                                         style="width: 40px; height: 40px;">
                                        <i class="fas fa-shipping-fast text-white"></i>
                                    </div>
                                </div>
                                <div class="timeline-content flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Order Shipped</h6>
                                    <small class="text-muted">{{ $order->shipped_at->format('M j, Y \a\t g:i A') }}</small>
                                </div>
                            </div>
                            @endif

                            @if($order->delivered_at)
                            <div class="timeline-item d-flex">
                                <div class="timeline-icon me-3">
                                    <div class="bg-success rounded-circle d-flex align-items-center justify-content-center"
                                         style="width: 40px; height: 40px;">
                                        <i class="fas fa-check-circle text-white"></i>
                                    </div>
                                </div>
                                <div class="timeline-content flex-grow-1">
                                    <h6 class="mb-1 fw-semibold">Order Delivered</h6>
                                    <small class="text-muted">{{ $order->delivered_at->format('M j, Y \a\t g:i A') }}</small>
                                </div>
                            </div>
                            @endif


                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Order Actions</h6>
                </div>
                <div class="card-body">
                    @if($order->status === 'pending') 
                    <form action="{{ route('admin.stockist-orders.approve', $order) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-block mb-2">Confirm Order</button>
                    </form>
                    @endif

                    @if($order->status === 'confirmed')
                    <form action="{{ route('admin.stockist-orders.ship', $order) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-block mb-2">Mark as Shipped</button>
                    </form>
                    @endif

                    @if($order->status === 'shipped')
                    <form action="{{ route('admin.stockist-orders.deliver', $order) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-info btn-block">Mark as Delivered</button>
                    </form>
                    @endif
                </div>
            </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
.timeline-item {
    position: relative;
}

.timeline-item:not(:last-child):after {
    content: '';
    position: absolute;
    left: 20px;
    top: 50px;
    bottom: -20px;
    width: 2px;
    background: #e9ecef;
}

.status-icon .bg-warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important; }
.status-icon .bg-info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important; }
.status-icon .bg-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; }
.status-icon .bg-success { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%) !important; }
</style>
@endpush


@extends('admin.layouts.app')

@section('title', 'Stockist Orders - Admin Panel')
@section('panel')
<div class="container-fluid py-4">
    <div class="container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 fw-bold text-dark mb-1">Stockist Orders</h1>
                        <p class="text-muted mb-0">Manage and process stockist product orders</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-2"> 
                <div class="card border-0 bg-gradient-primary text-white shadow-sm rounded-3">
                    <div class="card-body text-center py-3">
                        <h4 class="fw-bold mb-0">{{ $stats['total'] }}</h4>
                        <small>Total Orders</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 bg-gradient-warning text-white shadow-sm rounded-3">
                    <div class="card-body text-center py-3">
                        <h4 class="fw-bold mb-0">{{ $stats['pending'] }}</h4>
                        <small>Pending</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 bg-gradient-info text-white shadow-sm rounded-3">
                    <div class="card-body text-center py-3">
                        <h4 class="fw-bold mb-0">{{ $stats['approved'] }}</h4>
                        <small>Approved</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 bg-gradient-primary text-white shadow-sm rounded-3">
                    <div class="card-body text-center py-3">
                        <h4 class="fw-bold mb-0">{{ $stats['shipped'] }}</h4>
                        <small>Shipped</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card border-0 bg-gradient-success text-white shadow-sm rounded-3">
                    <div class="card-body text-center py-3">
                        <h4 class="fw-bold mb-0">{{ $stats['delivered'] }}</h4>
                        <small>Delivered</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body">
                <form id="filterForm" method="GET">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select class="form-select" name="status" onchange="this.form.submit()">
                                <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Statuses</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                                <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Shipped</option>
                                <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">From Date</label>
                            <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">To Date</label>
                            <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Search</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Order # or Stockist...">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2 text-primary"></i>
                    Orders List
                </h5>
            </div>
            <div class="card-body p-0">
                @if($orders->isEmpty())
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Orders Found</h4>
                        <p class="text-muted">No orders match your current filters.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>S/N</th>
                                    <th>Order #</th>
                                    <th>Stockist</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($orders as $order)
                                <tr>
                                    <td>
                                        <strong>{{ $loop->iteration }}</strong>
                                    </td>
                                    <td>
                                        <strong>{{ $order->order_number }}</strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $order->stockist->business_name }}</strong><br>
                                            <small class="text-muted">{{ $order->stockist->user->fullname ?? 'N/A' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $order->created_at->format('M j, Y') }}<br>
                                            {{ $order->created_at->format('g:i A') }}
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            {{ $order->items->count() }} item(s)<br>
                                            <span class="text-muted">{{ $order->items->sum('quantity') }} units</span>
                                        </small>
                                    </td>
                                    <td>
                                        <strong class="text-primary">₦{{ number_format($order->grand_total, 2) }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge {{ $order->status_badge }}">
                                            <i class="fas {{ $order->status_icon }} me-1"></i>
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.stockist-orders.show', $order->id) }}" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($order->status == 'pending')
                                                <button class="btn btn-outline-success approve-btn" 
                                                        data-order-id="{{ $order->id }}">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @endif
                                            @if(in_array($order->status, ['approved', 'processing']))
                                                <button class="btn btn-outline-info ship-btn" 
                                                        data-order-id="{{ $order->id }}">
                                                    <i class="fas fa-shipping-fast"></i>
                                                </button>
                                            @endif
                                            @if($order->status == 'shipped')
                                                <button class="btn btn-outline-success deliver-btn" 
                                                        data-order-id="{{ $order->id }}">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($orders->hasPages())
                    <div class="card-body border-top">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} orders
                                </small>
                            </div>
                            <div>
                                {{ $orders->links() }}
                            </div>
                        </div>
                    </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
@push('modal')
<!-- Approve Order Modal -->
<div class="modal fade" id="approveOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve order <strong id="approveOrderNumber"></strong>?</p>
                <p class="text-muted">This will move the order to the next processing stage.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmApprove">Approve Order</button>
            </div>
        </div>
    </div>
</div>

<!-- Ship Order Modal -->
<div class="modal fade" id="shipOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ship Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to mark order <strong id="shipOrderNumber"></strong> as shipped?</p>
                <p class="text-muted">This will notify the stockist that their order is on the way.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmShip">Mark as Shipped</button>
            </div>
        </div>
    </div>
</div>

<!-- Deliver Order Modal -->
<div class="modal fade" id="deliverOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Deliver Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to mark order <strong id="deliverOrderNumber"></strong> as delivered?</p>
                <p class="text-muted">This will update the stockist's inventory and complete the order process.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmDeliver">Mark as Delivered</button>
            </div>
        </div>
    </div>
</div>

@endpush
@push('script')
<script>
$(document).ready(function() {
    let currentOrderId = null;

    // Approve Order
    $('.approve-btn').on('click', function() {
        currentOrderId = $(this).data('order-id');
        const orderNumber = $(this).closest('tr').find('strong').first().text();
        $('#approveOrderNumber').text(orderNumber);
        $('#approveOrderModal').modal('show');
    });

    $('#confirmApprove').on('click', function() {
        const button = $(this);
        const originalText = button.html();

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Approving...');

        $.ajax({
            url: "{{ route('admin.stockist-orders.approve', '') }}/" + currentOrderId,
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    $('#approveOrderModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                    button.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                alert('An error occurred. Please try again.');
                button.prop('disabled', false).html(originalText);
            }
        });
    });

    // Ship Order
    $('.ship-btn').on('click', function() {
        currentOrderId = $(this).data('order-id');
        const orderNumber = $(this).closest('tr').find('strong').first().text();
        $('#shipOrderNumber').text(orderNumber);
        $('#shipOrderModal').modal('show');
    });

    $('#confirmShip').on('click', function() {
        const button = $(this);
        const originalText = button.html();

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Shipping...');

        $.ajax({
            url: "{{ route('admin.stockist-orders.ship', '') }}/" + currentOrderId,
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    $('#shipOrderModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                    button.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                alert('An error occurred. Please try again.');
                button.prop('disabled', false).html(originalText);
            }
        });
    });

    // Deliver Order
    $('.deliver-btn').on('click', function() {
        currentOrderId = $(this).data('order-id');
        const orderNumber = $(this).closest('tr').find('strong').first().text();
        $('#deliverOrderNumber').text(orderNumber);
        $('#deliverOrderModal').modal('show');
    });

    $('#confirmDeliver').on('click', function() {
        const button = $(this);
        const originalText = button.html();

        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Delivering...');

        $.ajax({
            url: "{{ route('admin.stockist-orders.deliver', '') }}/" + currentOrderId,
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    $('#deliverOrderModal').modal('hide');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                    button.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                alert('An error occurred. Please try again.');
                button.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>
@endpush
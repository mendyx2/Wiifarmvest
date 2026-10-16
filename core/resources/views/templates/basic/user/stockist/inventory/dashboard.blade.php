@extends($activeTemplate . 'layouts.master')

@section('content')
@php
    // A Mega orders from the company; a Mini restocks by buying from a Mega stockist
    $restockUrl = $canOrderFromCompany ? route('user.stockist.inventory.catalog') : route('user.stockist.transfers.create');
    $ordersUrl  = $canOrderFromCompany ? route('user.stockist.inventory.orders') : route('user.stockist.transfers.index');
    $restockCta = $canOrderFromCompany ? 'Order Products' : 'Buy from Mega';
@endphp
<div class="nc-wrap" id="ncWrap">
    <br>
{{-- ── Page Header ─────────────────────────────────── --}}
<div class="sl-page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="sl-page-title mb-1">Inventory Management</h4>
        <p class="sl-page-subtitle mb-0">{{ $canOrderFromCompany ? 'Manage your product inventory and place orders.' : 'Your stock. To restock, buy from a Mega stockist in your state.' }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if ($canOrderFromCompany)
            <a href="{{ route('user.stockist.inventory.checkout') }}" class="sl-btn sl-btn-outline position-relative">
                <i class="las la-shopping-cart me-1"></i> Cart
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartCount">
                    {{ count($cart) }}
                </span>
            </a>
        @else
            <a href="{{ $ordersUrl }}" class="sl-btn sl-btn-outline">
                <i class="las la-shopping-basket me-1"></i> My Orders
            </a>
        @endif
        <a href="{{ $restockUrl }}" class="sl-btn sl-btn-primary">
            <i class="las la-{{ $canOrderFromCompany ? 'shopping-bag' : 'truck-loading' }} me-1"></i> {{ $restockCta }}
        </a>
    </div>
</div>

{{-- ── Stats cards ───────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-primary">
            <div class="sl-stat-icon"><i class="las la-boxes"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Total Products</p>
                <h3 class="sl-stat-value">{{ $stats['total_products'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-gold">
            <div class="sl-stat-icon"><i class="las la-exclamation-triangle"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Low Stock</p>
                <h3 class="sl-stat-value">{{ $stats['low_stock_count'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-danger">
            <div class="sl-stat-icon"><i class="las la-times-circle"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Out of Stock</p>
                <h3 class="sl-stat-value">{{ $stats['out_of_stock_count'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-sm-6 col-xl-3">
        <div class="sl-stat-card sl-stat-success">
            <div class="sl-stat-icon"><i class="las la-wallet"></i></div>
            <div class="sl-stat-body">
                <p class="sl-stat-label">Wallet Balance</p>
                <h3 class="sl-stat-value">{{ showAmount($stats['wallet_balance']) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- ── LEFT: Current inventory ─────────────────────── --}}
    <div class="col-12 col-lg-8">
        <div class="sl-card">
            <div class="sl-card-header"><i class="las la-boxes me-1"></i> Current Inventory</div>
            <div class="sl-card-body p-0">
                @if($inventory->isEmpty())
                    <div class="sl-empty-state">
                        <div class="sl-empty-icon"><i class="las la-box-open"></i></div>
                        <p class="sl-empty-title">No Inventory Yet</p>
                        <p class="sl-empty-sub">You haven't added any products to your inventory yet.</p>
                        <a href="{{ $restockUrl }}" class="sl-btn sl-btn-primary mt-3">
                            <i class="las la-shopping-cart me-1"></i> {{ $restockCta }}
                        </a>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="sl-table" aria-label="Current inventory">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Current Stock</th>
                                    <th>Stock Levels</th>
                                    <th>Status</th>
                                    {{-- <th>Actions</th> --}}
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inventory as $item)
                                <tr>
                                    <td data-label="Product">
                                        <div class="sl-table-name">
                                            @if($item->product->thumbnail)
                                                <img src="{{ getImage(getFilePath('products') . '/' . $item->product->thumbnail, getFilePath('products')) }}"
                                                     alt="{{ $item->product->name }}"
                                                     class="sl-table-avatar" style="object-fit:cover;">
                                            @else
                                                <div class="sl-table-avatar" style="background:#F3F4F6;color:#9CA3AF;">
                                                    <i class="las la-box"></i>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="fw-600 mb-0">{{ $item->product->name }}</p>
                                                <x-product.sku :value="$item->product->sku" />
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="Current Stock">
                                        <span class="fw-700" style="{{ $item->is_low_stock ? 'color:#D97706;' : '' }}">
                                            {{ $item->quantity }}
                                        </span>
                                    </td>
                                    <td data-label="Stock Levels">
                                        <small class="text-muted">Min: {{ $item->min_stock_level }} &middot; Max: {{ $item->max_stock_level }}</small>
                                    </td>
                                    <td data-label="Status">
                                        @switch($item->stock_status)
                                            @case('out_of_stock')
                                                <span class="sl-status sl-status-out-of-stock">Out of Stock</span>
                                                @break
                                            @case('low_stock')
                                                <span class="sl-status sl-status-low-stock">Low Stock</span>
                                                @break
                                            @case('over_stock')
                                                <span class="sl-status sl-status-over-stock">Over Stock</span>
                                                @break
                                            @default
                                                <span class="sl-status sl-status-in-stock">In Stock</span>
                                        @endswitch
                                    </td>
                                    {{--
                                    <td>
                                        <strong>{{ showAmount($item->unit_cost) }}</strong><br/>
                                        <strong>{{ showAmount($item->total_value) }}</strong>
                                    </td>
                                    <td>
                                        <button class="sl-btn sl-btn-outline"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editInventoryModal"
                                                data-inventory-id="{{ $item->id }}"
                                                data-min-level="{{ $item->min_stock_level }}"
                                                data-max-level="{{ $item->max_stock_level }}">
                                            <i class="las la-edit"></i>
                                        </button>
                                    </td>
                                    --}}
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── RIGHT: Quick actions + alerts ───────────────── --}}
    <div class="col-12 col-lg-4">
        <div class="sl-card mb-4">
            <div class="sl-card-header"><i class="las la-bolt me-1"></i> Quick Actions</div>
            <div class="sl-card-body d-grid gap-2">
                <a href="{{ $restockUrl }}" class="sl-btn sl-btn-primary">
                    <i class="las la-shopping-cart me-1"></i> {{ $restockCta }}
                </a>
                <a href="{{ $ordersUrl }}" class="sl-btn sl-btn-outline">
                    <i class="las la-history me-1"></i> View Order History
                </a>
                <button class="sl-btn sl-btn-outline">
                    <i class="las la-chart-line me-1"></i> View Reports
                </button>
            </div>
        </div>

        @if($lowStockItems->isNotEmpty())
        <div class="sl-card mb-4">
            <div class="sl-card-header" style="background:#FFFBEB;color:#92400E;">
                <i class="las la-exclamation-triangle me-1"></i> Low Stock Alerts
            </div>
            <div class="sl-card-body">
                @foreach($lowStockItems->take(3) as $item)
                <div class="alert sl-alert-warning d-flex align-items-center gap-2 mb-2 py-2">
                    <i class="las la-exclamation-circle"></i>
                    <div class="flex-grow-1">
                        <strong>{{ $item->product->name }}</strong><br>
                        <small>Stock: {{ $item->quantity }} (Min: {{ $item->min_stock_level }})</small>
                    </div>
                </div>
                @endforeach
                @if($lowStockItems->count() > 3)
                <div class="text-center">
                    <small class="text-muted">+{{ $lowStockItems->count() - 3 }} more items</small>
                </div>
                @endif
                <div class="text-center mt-2">
                    <a href="{{ $restockUrl }}" class="sl-btn sl-btn-outline" style="font-size:.78rem;padding:.4rem .9rem;">
                        Reorder Now
                    </a>
                </div>
            </div>
        </div>
        @endif

        @if($outOfStockItems->isNotEmpty())
        <div class="sl-card">
            <div class="sl-card-header" style="background:#FEF2F2;color:#991B1B;">
                <i class="las la-times-circle me-1"></i> Out of Stock
            </div>
            <div class="sl-card-body">
                @foreach($outOfStockItems->take(3) as $item)
                <div class="alert sl-alert-danger d-flex align-items-center gap-2 mb-2 py-2">
                    <i class="las la-times"></i>
                    <div class="flex-grow-1">
                        <strong>{{ $item->product->name }}</strong><br>
                        <small>Completely out of stock</small>
                    </div>
                </div>
                @endforeach
                <div class="text-center">
                    <a href="{{ $restockUrl }}" class="sl-btn sl-btn-outline" style="font-size:.78rem;padding:.4rem .9rem;">
                        Restock Now
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
</div>
@endsection

@push('modal')
<div class="modal fade" id="editInventoryModal" tabindex="-1" aria-labelledby="editInventoryModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Stock Levels</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editInventoryForm">
                    @csrf
                    <input type="hidden" name="inventory_id" id="editInventoryId">

                    <div class="mb-3">
                        <label class="sl-label">Minimum Stock Level</label>
                        <input type="number" class="sl-input" name="min_stock_level" id="editMinLevel" min="0" required>
                        <p class="sl-field-hint">Alert when stock falls below this level</p>
                    </div>

                    <div class="mb-3">
                        <label class="sl-label">Maximum Stock Level</label>
                        <input type="number" class="sl-input" name="max_stock_level" id="editMaxLevel" min="1" required>
                        <p class="sl-field-hint">Recommended maximum stock level</p>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="sl-btn sl-btn-outline" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="sl-btn sl-btn-primary" id="saveInventoryLevels">Save Changes</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('script')
<script>
$(document).ready(function() {
    $('#editInventoryModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const inventoryId = button.data('inventory-id');
        const minLevel = button.data('min-level');
        const maxLevel = button.data('max-level');

        $('#editInventoryId').val(inventoryId);
        $('#editMinLevel').val(minLevel);
        $('#editMaxLevel').val(maxLevel);
    });

    $('#saveInventoryLevels').on('click', function() {
        const formData = $('#editInventoryForm').serialize();

        $.ajax({
            url: "{{ route('user.stockist.inventory.levels.update') }}",
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#editInventoryModal').modal('hide');
                    location.reload();
                }
            },
            error: function(xhr) {
                alert('Error updating inventory levels.');
            }
        });
    });
});
</script>
@endpush

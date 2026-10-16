@extends('admin.layouts.app')

@section('panel')
<div class="prod-page">

    <div class="prod-table-card">
        <div class="prod-table-head">
            <div class="prod-table-head-left">
                <span class="prod-count-badge">{{ $products->total() }} products</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table prod-table">
                <thead>
                    <tr>
                        <th style="width:56px"></th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th class="text-center">PV</th>
                        
                        <th class="text-center">Stock</th>
                        <th class="text-center">Featured</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>
                            <img class="prod-thumb"
                                src="{{ getImage(getFilePath('products') . '/' . $product->thumbnail, getFileSize('products')) }}"
                                alt="{{ $product->name }}">
                        </td>
                        <td>
                            <div class="prod-name">{{ strLimit($product->name, 32) }}</div>
                            <div class="prod-sku-tag">SKU {{ $product->sku }}kg</div>
                        </td>
                        <td>
                            <span class="prod-cat-chip">{{ optional($product->category)->name ?? '—' }}</span>
                        </td>
                        <td>
                            <span class="prod-price">{{ showAmount($product->price) }}</span>
                        </td>
                        <td class="text-center">
                            <span class="prod-metric-chip prod-metric-pv">{{ number_format($product->pv ?? 0, 2) }}</span>
                        </td>
                        
                        <td class="text-center">
                            @if($product->quantity <= 0)
                                <span class="prod-stock-chip prod-stock-out">Out</span>
                            @elseif($product->quantity <= 10)
                                <span class="prod-stock-chip prod-stock-low">{{ $product->quantity }}</span>
                            @else
                                <span class="prod-stock-chip prod-stock-ok">{{ $product->quantity }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($product->is_featured == \App\Constants\Status::ENABLE)
                                <span class="prod-badge prod-badge-featured">Featured</span>
                            @else
                                <span class="prod-badge prod-badge-unfeatured">Standard</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($product->status == \App\Constants\Status::ENABLE)
                                <span class="prod-badge prod-badge-active">Active</span>
                            @else
                                <span class="prod-badge prod-badge-inactive">Off</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="prod-actions">
                                <a href="{{ route('admin.product.edit', $product->id) }}" class="prod-action-btn prod-action-edit" title="Edit">
                                    <i class="las la-pen"></i>
                                </a>
                                @if($product->is_featured == \App\Constants\Status::ENABLE)
                                    <button class="prod-action-btn prod-action-unfeature confirmationBtn"
                                        data-question="@lang('Remove featured status from this product?')"
                                        data-action="{{ route('admin.product.feature', $product->id) }}"
                                        title="Unfeature">
                                        <i class="las la-star"></i>
                                    </button>
                                @else
                                    <button class="prod-action-btn prod-action-feature confirmationBtn"
                                        data-question="@lang('Mark this product as featured?')"
                                        data-action="{{ route('admin.product.feature', $product->id) }}"
                                        title="Feature">
                                        <i class="lar la-star"></i>
                                    </button>
                                @endif
                                @if($product->status == \App\Constants\Status::ENABLE)
                                    <button class="prod-action-btn prod-action-disable confirmationBtn"
                                        data-question="@lang('Disable this product?')"
                                        data-action="{{ route('admin.product.status', $product->id) }}"
                                        title="Disable">
                                        <i class="las la-toggle-on"></i>
                                    </button>
                                @else
                                    <button class="prod-action-btn prod-action-enable confirmationBtn"
                                        data-question="@lang('Enable this product?')"
                                        data-action="{{ route('admin.product.status', $product->id) }}"
                                        title="Enable">
                                        <i class="las la-toggle-off"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10">
                            <div class="prod-empty">
                                <i class="las la-box-open"></i>
                                <p>No products found</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
        <div class="prod-pagination">
            {{ paginateLinks($products) }}
        </div>
        @endif
    </div>

</div>

<x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <x-search-form />
    <a class="btn btn-outline--primary h-45" href="{{ route('admin.product.create') }}">
        <i class="las la-plus"></i> @lang('Add Product')
    </a>
@endpush

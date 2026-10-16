@extends('admin.layouts.app')

@section('panel')
<form action="{{ route('admin.product.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="pf-grid">

        {{-- LEFT COLUMN --}}
        <div class="pf-col-main">

            {{-- Basic Info --}}
            <div class="pf-card">
                <div class="pf-card-header">
                    <i class="las la-tag"></i> Basic Information
                </div>
                <div class="pf-card-body">
                    <div class="pf-field">
                        <label class="pf-label">Product Name <span class="pf-req">*</span></label>
                        <input class="pf-input" name="name" type="text" value="{{ old('name') }}" placeholder="Enter product name" required>
                    </div>
                    <div class="pf-field-row">
                        <div class="pf-field">
                            <label class="pf-label">Category <span class="pf-req">*</span></label>
                            <select class="pf-input select2" name="category" required>
                                <option value="">Select category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category') == $category->id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pf-field">
                            <label class="pf-label">SKU (kg) <span class="pf-req">*</span></label>
                            <input class="pf-input" name="sku" type="number" value="{{ old('sku') }}" placeholder="e.g. 25" required>
                        </div>
                    </div>
                    <div class="pf-field-row">
                        <div class="pf-field">
                            <label class="pf-label">Stock Quantity <span class="pf-req">*</span></label>
                            <input class="pf-input" name="quantity" type="number" value="{{ old('quantity') }}" placeholder="Available units" required>
                        </div>
                        <div class="pf-field">
                            <label class="pf-label">Min Order Qty <span class="pf-req">*</span></label>
                            <input class="pf-input" name="min_order_quantity" type="number" value="{{ old('min_order_quantity') }}" placeholder="Minimum" required>
                        </div>
                        <div class="pf-field">
                            <label class="pf-label">Max Order Qty <span class="pf-req">*</span></label>
                            <input class="pf-input" name="max_order_quantity" type="number" value="{{ old('max_order_quantity') }}" placeholder="Maximum" required>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pricing & Metrics --}}
            <div class="pf-card">
                <div class="pf-card-header">
                    <i class="las la-coins"></i> Pricing &amp; Metrics
                </div>
                <div class="pf-card-body">
                    <div class="pf-metrics-grid">
                        <div class="pf-metric-block">
                            <label class="pf-label">Price <span class="pf-req">*</span></label>
                            <div class="pf-input-prefix">
                                <span class="pf-prefix">₦</span>
                                <input class="pf-input" name="price" type="number" step="any" value="{{ old('price') }}" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="pf-metric-block">
                            <label class="pf-label">Selling Price</label>
                            <div class="pf-input-prefix">
                                <span class="pf-prefix">₦</span>
                                <input class="pf-input" name="selling_price" type="number" step="any" value="{{ old('selling_price') }}" placeholder="0.00">
                            </div>
                            <p class="pf-hint">Public price shown on /shop. Leave blank to use the Price above.</p>
                        </div>
                        <div class="pf-metric-block">
                            <label class="pf-label pf-label-pv">Point Value (PV) <span class="pf-req">*</span></label>
                            <input class="pf-input" name="pv" type="number" step="any" value="{{ old('pv', 0) }}" placeholder="0.00" required>
                            <p class="pf-hint">Accumulated for member rewards</p>
                        </div>
                        
                    </div>
                </div>
            </div>

            {{-- Affiliate Bonus --}}
            <div class="pf-card">
                <div class="pf-card-header">
                    <i class="las la-link"></i> Affiliate Bonus
                </div>
                <div class="pf-card-body">
                    <div class="pf-metric-grid">
                        <div class="pf-metric-block">
                            <label class="pf-label">Bonus Type</label>
                            <select class="pf-input" name="affiliate_bonus_type">
                                <option value="" @selected(!old('affiliate_bonus_type'))>None</option>
                                <option value="fixed" @selected(old('affiliate_bonus_type') === 'fixed')>Fixed Amount</option>
                                <option value="percentage" @selected(old('affiliate_bonus_type') === 'percentage')>Percentage of Sale</option>
                            </select>
                            <p class="pf-hint">Paid to the referring member on /shop sales</p>
                        </div>
                        <div class="pf-metric-block">
                            <label class="pf-label">Bonus Value</label>
                            <input class="pf-input" name="affiliate_bonus_value" type="number" step="any" min="0" value="{{ old('affiliate_bonus_value') }}" placeholder="0.00">
                            <p class="pf-hint">₦ amount, or % if Percentage selected</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- State Prices & Stockist Bonus --}}
            <div class="pf-card">
                <div class="pf-card-header pf-card-header--state">
                    <div class="pf-state-header-left">
                        <i class="las la-map-marker"></i> State Prices &amp; Stockist Bonus
                    </div>
                    <button class="pf-state-add-toggle" id="addStateRow" type="button">
                        <i class="las la-plus"></i> Add State
                    </button>
                </div>
                <div class="pf-card-body">
                    <p class="pf-hint">
                        The price in each state, and the rebate a Mega or Micro stockist there earns for every unit they
                        redeem: bonus % × product PV × ₦{{ number_format(rDollar()) }}. Stockists can only redeem this
                        product in states listed here.
                    </p>
                    <div id="statePriceRows">
                        @foreach (old('state_prices', []) as $i => $row)
                            @include('admin.product.partials.state_price_row', ['index' => $i, 'row' => $row, 'states' => $states])
                        @endforeach
                    </div>
                    <template id="statePriceRowTemplate">
                        @include('admin.product.partials.state_price_row', ['index' => '__INDEX__', 'row' => [], 'states' => $states])
                    </template>
                </div>
            </div>

            {{-- Description & Specs --}}
            <div class="pf-card">
                <div class="pf-card-header">
                    <i class="las la-align-left"></i> Description &amp; Specifications
                </div>
                <div class="pf-card-body">
                    <div class="pf-field">
                        <label class="pf-label">Product Description <span class="pf-req">*</span></label>
                        <textarea class="pf-input nicEdit" name="description" rows="5">{{ old('description') }}</textarea>
                    </div>
                    <div class="pf-field pf-field--spec">
                        <div class="pf-spec-header">
                            <label class="pf-label">Specifications</label>
                            <button type="button" class="pf-spec-add-btn add-specification">
                                <i class="las la-plus"></i> Add Row
                            </button>
                        </div>
                        <div id="specification">
                            <p class="pf-spec-empty" id="specifications-title">Click "Add Row" to add product specifications</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- SEO --}}
            <div class="pf-card">
                <div class="pf-card-header pf-card-header--muted">
                    <i class="las la-search"></i> SEO <span class="pf-optional">optional</span>
                </div>
                <div class="pf-card-body">
                    <div class="pf-field">
                        <label class="pf-label">Meta Title</label>
                        <input class="pf-input" name="meta_title" type="text" value="{{ old('meta_title') }}" placeholder="Meta title">
                    </div>
                    <div class="pf-field">
                        <label class="pf-label">Meta Keywords</label>
                        <select class="pf-input select2-auto-tokenize" name="meta_keywords[]" data-placeholder="Type and press enter or comma" multiple></select>
                    </div>
                    <div class="pf-field">
                        <label class="pf-label">Meta Description</label>
                        <textarea class="pf-input" name="meta_description" rows="3" placeholder="Meta description">{{ old('meta_description') }}</textarea>
                    </div>
                </div>
            </div>

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="pf-col-side">

            {{-- Thumbnail --}}
            <div class="pf-card">
                <div class="pf-card-header">
                    <i class="las la-image"></i> Thumbnail <span class="pf-req">*</span>
                </div>
                <div class="pf-card-body">
                    <x-image-uploader class="w-100" name="thumbnail" type="products" :required="true" />
                </div>
            </div>

            {{-- Gallery --}}
            <div class="pf-card">
                <div class="pf-card-header">
                    <i class="las la-images"></i> Gallery
                </div>
                <div class="pf-card-body">
                    <div class="input-images"></div>
                    <p class="pf-hint pf-hint--mt">PNG, JPG, JPEG &mdash; max 10 images, 3MB each</p>
                </div>
            </div>

            {{-- Submit --}}
            <button class="pf-submit-btn" type="submit">
                <i class="las la-save"></i> Save Product
            </button>

        </div>
    </div>
</form>
@endsection

@push('breadcrumb-plugins')
    <a class="btn btn-sm btn-outline--dark" href="{{ route('admin.product.index') }}">
        <i class="las la-undo"></i> Back
    </a>
@endpush

@push('script-lib')
    <script src="{{ asset('assets/admin/js/image-uploader.min.js') }}"></script>
@endpush

@push('style-lib')
    <link href="{{ asset('assets/admin/css/image-uploader.min.css') }}" rel="stylesheet">
@endpush

@push('script')
<script>
"use strict";
(function($) {

    $(".add-specification").on('click', function() {
        let index = $(".specification").length + 1;
        let html = `
            <div class="pf-spec-row specification">
                <input type="text" class="pf-input" name="specification[${index}][name]" placeholder="Name">
                <input type="text" class="pf-input" name="specification[${index}][value]" placeholder="Value">
                <button type="button" class="pf-spec-remove minus-specification"><i class="las la-times"></i></button>
            </div>`;
        $("#specification").append(html);
        $("#specifications-title").hide();
    });

    $("body").on('click', '.minus-specification', function() {
        $(this).closest('.specification').remove();
        if ($(".specification").length === 0) $("#specifications-title").show();
    });

    // State prices: clone the row template; indexes only need to be unique
    let stateRowIndex = $('#statePriceRows .pf-state-row').length;
    $('#addStateRow').on('click', function () {
        const html = $('#statePriceRowTemplate').html().replace(/__INDEX__/g, Date.now() + '' + stateRowIndex++);
        $('#statePriceRows').append(html);
    });
    $('#statePriceRows').on('click', '.pf-state-row-remove', function () {
        $(this).closest('.pf-state-row').remove();
    });

    $('.input-images').imageUploader({
        preloaded: [],
        imagesInputName: 'gallery',
        preloadedInputName: 'old',
        maxSize: 3 * 1024 * 1024,
        maxFiles: 10,
    });

})(jQuery);
</script>
@endpush

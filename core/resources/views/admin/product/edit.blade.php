@extends('admin.layouts.app')

@section('panel')
<form action="{{ route('admin.product.update', $product->id) }}" method="POST" enctype="multipart/form-data">
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
                        <input class="pf-input" name="name" type="text" value="{{ $product->name }}" placeholder="Enter product name" required>
                    </div>
                    <div class="pf-field-row">
                        <div class="pf-field">
                            <label class="pf-label">Category <span class="pf-req">*</span></label>
                            <select class="pf-input select2" name="category" required>
                                <option value="">Select category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" @selected($category->id == $product->category_id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="pf-field">
                            <label class="pf-label">SKU (kg) <span class="pf-req">*</span></label>
                            <input class="pf-input" name="sku" type="number" value="{{ $product->sku }}" placeholder="e.g. 25" required>
                        </div>
                    </div>
                    <div class="pf-field-row">
                        <div class="pf-field">
                            <label class="pf-label">Stock Quantity <span class="pf-req">*</span></label>
                            <input class="pf-input" name="quantity" type="number" value="{{ $product->quantity }}" placeholder="Available units" required>
                        </div>
                        <div class="pf-field">
                            <label class="pf-label">Min Order Qty <span class="pf-req">*</span></label>
                            <input class="pf-input" name="min_order_quantity" type="number" value="{{ $product->min_order_quantity }}" placeholder="Minimum" required>
                        </div>
                        <div class="pf-field">
                            <label class="pf-label">Max Order Qty <span class="pf-req">*</span></label>
                            <input class="pf-input" name="max_order_quantity" type="number" value="{{ $product->max_order_quantity }}" placeholder="Maximum" required>
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
                                <input class="pf-input" name="price" type="number" step="any" value="{{ getAmount($product->price) }}" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="pf-metric-block">
                            <label class="pf-label">Selling Price</label>
                            <div class="pf-input-prefix">
                                <span class="pf-prefix">₦</span>
                                <input class="pf-input" name="selling_price" type="number" step="any" value="{{ $product->selling_price ? getAmount($product->selling_price) : '' }}" placeholder="0.00">
                            </div>
                            <p class="pf-hint">Public price shown on /shop. Leave blank to use the Price above.</p>
                        </div>
                        <div class="pf-metric-block">
                            <label class="pf-label pf-label-pv">Point Value (PV) <span class="pf-req">*</span></label>
                            <input class="pf-input" name="pv" type="number" step="any" value="{{ $product->pv ?? 0 }}" placeholder="0.00" required>
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
                                <option value="" {{ !$product->affiliate_bonus_type ? 'selected' : '' }}>None</option>
                                <option value="fixed" {{ $product->affiliate_bonus_type === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                <option value="percentage" {{ $product->affiliate_bonus_type === 'percentage' ? 'selected' : '' }}>Percentage of Sale</option>
                            </select>
                            <p class="pf-hint">Paid to the referring member on /shop sales</p>
                        </div>
                        <div class="pf-metric-block">
                            <label class="pf-label">Bonus Value</label>
                            <input class="pf-input" name="affiliate_bonus_value" type="number" step="any" min="0" value="{{ $product->affiliate_bonus_value }}" placeholder="0.00">
                            <p class="pf-hint">₦ amount, or % if Percentage selected</p>
                        </div>
                    </div>
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
                        <textarea class="pf-input nicEdit" name="description" rows="5">{{ $product->description }}</textarea>
                    </div>
                    <div class="pf-field pf-field--spec">
                        <div class="pf-spec-header">
                            <label class="pf-label">Specifications</label>
                            <button type="button" class="pf-spec-add-btn add-specification">
                                <i class="las la-plus"></i> Add Row
                            </button>
                        </div>
                        <div id="specification">
                            @php $hasSpecs = !empty($product->specifications); @endphp
                            @foreach($product->specifications ?? [] as $k => $spec)
                                <div class="pf-spec-row specification">
                                    <input type="text" class="pf-input" name="specification[{{ $k }}][name]" value="{{ $spec['name'] ?? '' }}" placeholder="Name">
                                    <input type="text" class="pf-input" name="specification[{{ $k }}][value]" value="{{ $spec['value'] ?? '' }}" placeholder="Value">
                                    <button type="button" class="pf-spec-remove minus-specification"><i class="las la-times"></i></button>
                                </div>
                            @endforeach
                            <p class="pf-spec-empty {{ $hasSpecs ? 'd-none' : '' }}" id="specifications-title">Click "Add Row" to add product specifications</p>
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
                        <input class="pf-input" name="meta_title" type="text" value="{{ $product->meta_title }}" placeholder="Meta title">
                    </div>
                    <div class="pf-field">
                        <label class="pf-label">Meta Keywords</label>
                        <select class="pf-input select2-auto-tokenize" name="meta_keywords[]" data-placeholder="Type and press enter or comma" multiple>
                            @foreach($product->meta_keyword ?? [] as $kw)
                                <option value="{{ $kw }}" selected>{{ $kw }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pf-field">
                        <label class="pf-label">Meta Description</label>
                        <textarea class="pf-input" name="meta_description" rows="3" placeholder="Meta description">{{ $product->meta_description }}</textarea>
                    </div>
                </div>
            </div>

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="pf-col-side">

            {{-- Thumbnail --}}
            <div class="pf-card">
                <div class="pf-card-header">
                    <i class="las la-image"></i> Thumbnail
                </div>
                <div class="pf-card-body">
                    <x-image-uploader class="w-100" name="thumbnail" type="products" image="{{ $product->thumbnail }}" :required="false" />
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
                <i class="las la-save"></i> Update Product
            </button>

        </div>
    </div>
</form>

{{-- State Prices Section --}}
<div class="pf-card pf-state-prices-card">
    <div class="pf-card-header pf-card-header--state">
        <div class="pf-state-header-left">
            <i class="las la-map-marker"></i> State-Specific Prices
        </div>
        <button class="pf-state-add-toggle" id="toggleAddState" type="button">
            <i class="las la-plus"></i> Add State Price
        </button>
    </div>
    <div class="pf-card-body">

        {{-- Add form --}}
        <div class="pf-state-add-form" id="addStatePriceForm" style="display:none;">
            <form action="{{ route('admin.product.state-price.store', $product->id) }}" method="POST">
                @csrf
                @php $assignedStateIds = $product->statePrices->pluck('state_id')->toArray(); @endphp
                <div class="pf-state-add-row">
                    <div class="pf-field">
                        <label class="pf-label">State</label>
                        {{-- States that already have a price stay listed (disabled) so the Edit button can pick them --}}
                        <select class="pf-input" name="state_id" id="statePriceState" required>
                            <option value="">Select state</option>
                            @foreach($states as $state)
                                @php $isSet = in_array($state->id, $assignedStateIds); @endphp
                                <option value="{{ $state->id }}" @disabled($isSet) data-assigned="{{ $isSet ? 1 : 0 }}">
                                    {{ $state->name }} ({{ $state->code }}){{ $isSet ? ' — set' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pf-field">
                        <label class="pf-label">Price (₦)</label>
                        <div class="pf-input-prefix">
                            <span class="pf-prefix">₦</span>
                            <input class="pf-input" name="price" id="statePricePrice" type="number" step="any" min="0" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="pf-field">
                        <label class="pf-label">Mega Stockist Bonus</label>
                        <div class="pf-input-suffix">
                            <input class="pf-input" name="mega_stockist_bonus" id="statePriceMega" type="number" step="0.01" min="0" max="100" placeholder="0">
                            <span class="pf-suffix">%</span>
                        </div>
                    </div>
                    <div class="pf-field">
                        <label class="pf-label">Micro Stockist Bonus</label>
                        <div class="pf-input-suffix">
                            <input class="pf-input" name="micro_stockist_bonus" id="statePriceMicro" type="number" step="0.01" min="0" max="100" placeholder="0">
                            <span class="pf-suffix">%</span>
                        </div>
                    </div>
                    <div class="pf-state-add-actions">
                        <button type="submit" class="pf-btn-save" id="statePriceSubmit">Save</button>
                        <button type="button" class="pf-btn-cancel" id="cancelAddState">Cancel</button>
                    </div>
                </div>
                <p class="pf-hint pf-hint--mt">
                    Paid into the redeeming stockist's rebate for every unit: bonus % × product PV × ₦{{ number_format(rDollar()) }}.
                    For this product (PV {{ $product->pv + 0 }}), 1% = ₦{{ number_format(0.01 * $product->pv * rDollar(), 2) }} per unit.
                </p>
            </form>
        </div>

        {{-- Existing state prices --}}
        @if($product->statePrices->count())
        <table class="pf-state-table">
            <thead>
                <tr>
                    <th>State</th>
                    <th>Code</th>
                    <th>State Price</th>
                    <th>vs Default</th>
                    <th>Stockist Bonus</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($product->statePrices->sortBy('state.name') as $sp)
                <tr>
                    <td class="pf-state-name">{{ optional($sp->state)->name }}</td>
                    <td><span class="pf-state-code-chip">{{ optional($sp->state)->code }}</span></td>
                    <td class="pf-state-price">₦{{ number_format($sp->price, 2) }}</td>
                    <td>
                        @php $diff = $sp->price - $product->price; @endphp
                        @if($diff > 0)
                            <span class="pf-diff pf-diff-up">+₦{{ number_format($diff, 2) }}</span>
                        @elseif($diff < 0)
                            <span class="pf-diff pf-diff-down">-₦{{ number_format(abs($diff), 2) }}</span>
                        @else
                            <span class="pf-diff pf-diff-same">Same</span>
                        @endif
                    </td>
                    <td>
                        @if($sp->mega_stockist_bonus > 0 || $sp->micro_stockist_bonus > 0)
                            @foreach(['Mega' => $sp->mega_stockist_bonus, 'Micro' => $sp->micro_stockist_bonus] as $tier => $pct)
                                <div class="pf-state-bonus-line">
                                    <span class="pf-state-bonus-tier">{{ $tier }}</span>
                                    @if($pct > 0)
                                        <span class="pf-state-bonus">{{ $pct + 0 }}%</span>
                                        <span class="pf-state-bonus-unit">≈ ₦{{ number_format($pct / 100 * $product->pv * rDollar(), 2) }} / unit</span>
                                    @else
                                        <span class="pf-state-bonus-unset">Not set</span>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <span class="pf-state-bonus-unset" title="Stockists in this state earn no bonus on this product">Not set</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <div class="pf-state-actions">
                            <button type="button" class="pf-state-edit-btn" title="Edit"
                                data-state="{{ $sp->state_id }}" data-price="{{ $sp->price + 0 }}" data-mega="{{ $sp->mega_stockist_bonus + 0 }}" data-micro="{{ $sp->micro_stockist_bonus + 0 }}">
                                <i class="las la-pen"></i>
                            </button>
                            <form action="{{ route('admin.product.state-price.destroy', [$product->id, $sp->id]) }}" method="POST"
                                onsubmit="return confirm('Remove state price for {{ addslashes(optional($sp->state)->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="pf-state-del-btn" title="Remove">
                                    <i class="las la-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="pf-state-empty">
            <i class="las la-map-marker-slash"></i>
            <p>No state-specific prices set &mdash; the default price applies everywhere</p>
        </div>
        @endif
    </div>
</div>

@endsection

@push('breadcrumb-plugins')
    <x-back route="{{ route('admin.product.index') }}" />
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

    $("body").on('click', '.removeBtn', function() {
        $(this).closest('.__gallery_image').remove();
    });

    @if(isset($images))
        let preloaded = @json($images);
    @else
        let preloaded = [];
    @endif

    $('.input-images').imageUploader({
        preloaded: preloaded,
        imagesInputName: 'gallery',
        preloadedInputName: 'old',
        maxSize: 3 * 1024 * 1024,
        maxFiles: 10,
    });

    const $stateSelect = $('#statePriceState');

    // Back to "add a new state": assigned states disabled again, fields cleared
    function resetStatePriceForm() {
        $stateSelect.find('option[data-assigned="1"]').prop('disabled', true);
        $stateSelect.val('');
        $('#statePricePrice, #statePriceMega, #statePriceMicro').val('');
        $('#statePriceSubmit').text('Save');
    }

    $('#toggleAddState').on('click', function() {
        resetStatePriceForm();
        $('#addStatePriceForm').slideDown(200);
        $(this).hide();
    });
    $('#cancelAddState').on('click', function() {
        resetStatePriceForm();
        $('#addStatePriceForm').slideUp(200);
        $('#toggleAddState').show();
    });

    // Edit an existing row: the form saves with updateOrCreate, so re-submitting updates it
    $('.pf-state-edit-btn').on('click', function() {
        const data = $(this).data();
        resetStatePriceForm();
        $stateSelect.find(`option[value="${data.state}"]`).prop('disabled', false);
        $stateSelect.val(data.state);
        $('#statePricePrice').val(data.price);
        $('#statePriceMega').val(data.mega || '');
        $('#statePriceMicro').val(data.micro || '');
        $('#statePriceSubmit').text('Update');
        $('#toggleAddState').hide();
        $('#addStatePriceForm').slideDown(200, function () {
            this.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

})(jQuery);
</script>
@endpush

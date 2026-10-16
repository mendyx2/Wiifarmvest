{{--
    The product's SKU as a small chip. Renders nothing when the product has none.

    <x-product.sku :value="$product->sku" />
--}}
@props(['value' => null])
@if (filled($value))
    <span {{ $attributes->class(['pp-sku']) }} title="Stock keeping unit"><span class="pp-sku__label">SKU</span>{{ $value }}</span>
@endif

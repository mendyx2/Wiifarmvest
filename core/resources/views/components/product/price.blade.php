{{--
    A product price with its currency: symbol, amount and the currency code
    (₦67,000 NGN), taken from General Settings so a currency change shows
    everywhere at once. Whole amounts drop the ".00".

    <x-product.price :amount="$product->shop_price" />
    <x-product.price :amount="$min" :max="$max" />          range, e.g. state prices
    <x-product.price :amount="$min" from />                 "From ₦59,000"
    <x-product.price :amount="$price" :code="false" />      symbol only, for tight rows
--}}
@props([
    'amount' => null,
    'max'    => null,
    'from'   => false,
    'code'   => true,
])
@php
    $money = function ($value) {
        $value = (float) $value;
        return gs('cur_sym') . number_format($value, fmod($value, 1.0) == 0.0 ? 0 : 2);
    };
    $isRange = $max !== null && (float) $max > (float) $amount;
@endphp
@if ($amount !== null && $amount !== '')
    <span {{ $attributes->class(['pp-price']) }}>
        @if ($from && !$isRange)<span class="pp-price__from">From</span>@endif
        <span class="pp-price__amount">{{ $money($amount) }}@if ($isRange)<span class="pp-price__dash">–</span>{{ $money($max) }}@endif</span>
        @if ($code)<abbr class="pp-price__code" title="Prices in {{ gs('cur_text') }}">{{ gs('cur_text') }}</abbr>@endif
    </span>
@endif

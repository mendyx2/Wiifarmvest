@extends($activeTemplate . 'layouts.master')
@section('content')
<div class="nc-wrap" id="ncWrap">
    <br/>
    <section class="product-section padding-top padding-bottom mb-5">
        <div class="container">
            <ul class="mr-list justify-content-center">
                <li class="mr-list__item">
                    <a class="mr-list__btn @if (!$categoryId) active @endif" href="{{ route('user.products') }}">@lang('All Products')</a>
                </li>
                @foreach ($categories as $category)
                    <li class="mr-list__item">
                        <a class="mr-list__btn @if ($categoryId == $category->id) active @endif"
                            href="{{ route('user.products', $category->id) }}">{{ __($category->name) }}</a>
                    </li>
                @endforeach
            </ul>

            <div class="row g-3 justify-content-center">
                @foreach ($products as $product)
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="product-item h-100">
                            <div class="product-thumb">
                                <img src="{{ getImage(getFilePath('products') . '/' . $product->thumbnail, getFileSize('products')) }}" alt="products">
                                <ul class="product-options">
                                    <li>
                                        <a class="image"
                                            href="{{ getImage(getFilePath('products') . '/' . $product->thumbnail, getFileSize('products')) }}"><i
                                                class="las la-expand"></i>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="product-content">
                                <h6 class="product-title">
                                    <a
                                        href="{{ route('user.product.details', ['id' => $product->id, 'slug' => slug($product->name)]) }}">{{ __(shortDescription($product->name, 35)) }}</a>
                                </h6>

                                @if ($product->quantity >= 0)
                                    <span class="product-availablity text--success">@lang('in stock')</span>
                                @else
                                    <span class="product-availablity text--danger">@lang('out stock')</span>
                                @endif

                                <div class="pp-meta">
                                    <x-product.sku :value="$product->sku" />
                                </div>
                                <div class="product-price">
                                    <x-product.price :amount="$product->state_price_min ?? $product->price" :max="$product->state_price_max" class="current-price" />
                                    @if ($product->state_price_max > $product->state_price_min)<span class="pp-hint">depends on your state</span>@endif
                                </div>
                                
                                <a class="add-to-cart cmn--btn-2"
                                    href="{{ route('user.product.details', ['id' => $product->id, 'slug' => slug($product->name)]) }}">@lang('Buy Now')</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if ($products->hasPages())
                <div class="pagination-wrapper">
                    {{ paginateLinks($products) }}
                </div>
            @endif
        </div>
    </section>
</div>
@endsection


@if (!request()->routeIs(['home']))
    @php
        $breadcrumbContent = getContent('breadcrumb.content', true);
    @endphp

    <!-- <div class="mt-100 text-white bg-black">
        <div class="container">
            <div class="inner-banner-wrapper">
                <h2 class="title pt-40 pb-50">{{ __($pageTitle) }}</h2>
            </div>
        </div>
    </div> -->

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h3 class="h3" style="color: #135D26;">{{ __($pageTitle) }}</h3>
        <div class="btn-toolbar mb-2 mb-md-0">
          <div class="btn-group me-2">
            
            {{-- 
            <a href="{{ route('user.cart.index') }}"   class="btn btn-sm btn-outline-success">{{ __('QTY') }} 
                <span class="badge bg-primary rounded-pill">{{ auth()->user()->cart_items_count }}</span>
            </a>
            --}}


            <a href="{{ route('user.cart.index') }}"   class="btn btn-sm btn-outline-success">{{ __('Cart') }} 
                <span id="cart-count" class="badge bg-success rounded-pill">{{ auth()->user()->cart_item_count }}</span>
            </a>
             
          </div>
        </div>
      </div>
@endif



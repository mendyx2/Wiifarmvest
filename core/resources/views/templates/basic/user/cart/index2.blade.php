@extends($activeTemplate . 'layouts.master')
@section('content')
@include($activeTemplate.'layouts.breadcrumb')
<div class="container">
   
    
    @if($cart->items->isEmpty())
        <div class="alert alert-info">Your cart is empty</div>
    @else
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        @foreach($cart->items as $item)
                            <div class="row mb-3 cart-item" data-item-id="{{ $item->id }}">
                                <div class="col-md-2">
                                    <img src="{{ getImage(getFilePath('products') . '/' . $item->product->thumbnail, getFilePath('products')) }}" alt="{{ $item->product->name }}" class="img-fluid">
                                </div>
                                <div class="col-md-4">
                                    <h5>{{ $item->product->name }}</h5>
                                    <p class="text-muted">${{ number_format($item->price, 2) }}</p>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control quantity-input" value="{{ $item->quantity }}" min="1">
                                </div>
                                <div class="col-md-2">
                                    <strong>${{ number_format($item->price * $item->quantity, 2) }}</strong>
                                </div>
                                <div class="col-md-1">
                                    <button class="remove-item btn btn-danger btn-sm" id="remove-item">×</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-lg-4 order-md-last">
                <ul class="list-group mb-3">
                  <li class="list-group-item d-flex justify-content-between lh-sm">
                    <div>
                      <h6 class="my-0">Total</h6>
                      <small class="text-muted">Brief description</small>
                    </div>
                    <span class="text-muted" id="cart-total" >{{ number_format($cart->total_amount, 2) }}</span>
                  </li>

                  
                  <li class="list-group-item d-flex justify-content-between lh-sm">
                    <div>
                      <h6 class="my-0">Wallet Balance:</h6>
                      <small class="text-muted">Current balance in you account </small>
                    </div>
                    <span class="text-muted">{{ number_format(auth()->user()->wallet_balance, 2) }}</span>
                  </li>
                  
                </ul>
                

                <div class="input-group">
                    <a href="{{ route('user.checkout') }}" class="btn btn-success btn-block">Proceed to Checkout</a>
                </div>
                
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function($) {
    "use strict";
       // Update quantity
        $('.quantity-input').on('change', function() {
            const itemId = $(this).closest('.cart-item').data('item-id');
            const quantity = $(this).val();
            
            $.ajax({
                url: "{{ route('user.cart.update', '') }}/" + itemId,
                method: 'PUT',
                data: {
                    quantity: quantity,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    location.reload();
                }
        });
            

        // Remove item
        $('remove-item').on('click', function() {
            alert('kk');
            const itemId = $(this).closest('.cart-item').data('item-id');
            
            $.ajax({
                url: "{{ route('user.cart.remove', '') }}/" + itemId,
                method: 'DELETE',
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    location.reload();
                }
            });
        });    


})(jQuery);
</script>
@endpush


@push('style')
<style>
      .bd-placeholder-img {
        font-size: 1.125rem;
        text-anchor: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        user-select: none;
      }

      @media (min-width: 768px) {
        .bd-placeholder-img-lg {
          font-size: 3.5rem;
        }
      }

      .b-example-divider {
        height: 3rem;
        background-color: rgba(0, 0, 0, .1);
        border: solid rgba(0, 0, 0, .15);
        border-width: 1px 0;
        box-shadow: inset 0 .5em 1.5em rgba(0, 0, 0, .1), inset 0 .125em .5em rgba(0, 0, 0, .15);
      }

      .b-example-vr {
        flex-shrink: 0;
        width: 1.5rem;
        height: 100vh;
      }

      .bi {
        vertical-align: -.125em;
        fill: currentColor;
      }

      .nav-scroller {
        position: relative;
        z-index: 2;
        height: 2.75rem;
        overflow-y: hidden;
      }

      .nav-scroller .nav {
        display: flex;
        flex-wrap: nowrap;
        padding-bottom: 1rem;
        margin-top: -1px;
        overflow-x: auto;
        text-align: center;
        white-space: nowrap;
        -webkit-overflow-scrolling: touch;
      }
</style>
@endpush
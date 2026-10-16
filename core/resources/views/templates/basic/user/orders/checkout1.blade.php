@extends($activeTemplate . 'layouts.master')
@section('title', 'Checkout - Complete Your Purchase')
@section('content')  
<div class="container">
    
    <div class="row">

        <div class="col-lg-12">
            <div class="card custom--card">
                <div class="card-body p-0">
                    <h5>Order Summary</h5>
                    <div class="table-responsive--sm">
                        <table class="custom--table table">
                            <thead>
                                <tr>
                                    <th>@lang('Product')</th>
                                    <th>@lang('QTY')</th>
                                    <th>@lang('Price')</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cart->items as $item)
                                    <tr>
                                        <td>{{ $item->product->name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ showAmount($item->price * $item->quantity, 2) }}</td>
                                    </tr>
                                @endforeach
                               

                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>



        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-9"><strong>Total:</strong></div>
                        <div class="col-md-3"><strong>{{ showAmount($cart->total_amount, 2) }}</strong></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5>Payment</h5>
                    <p>Wallet Balance: {{ showAmount(auth()->user()->wallet_balance, 2) }}</p>
                    
                    @if(auth()->user()->wallet_balance < $cart->total_amount)
                        <div class="alert alert-warning">
                            Insufficient wallet balance.
                        </div>
                    @else
                        <button id="pay-now" class="btn btn-sm  btn-success btn-block">Pay Now</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
$(document).ready(function() {
    $('#pay-now').on('click', function() {
       // alert('i am here');
        $(this).prop('disabled', true).text('Processing...');
         
        $.ajax({
            url: "{{ route('user.process.payment') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect_url;
                } else {
                    alert(response.message);
                    $('#pay-now').prop('disabled', false).text('Pay Now');
                }
            }
        });
    });
});
</script>
@endpush
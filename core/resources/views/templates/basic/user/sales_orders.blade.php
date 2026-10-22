@extends($activeTemplate . 'layouts.master')
@section('content')
 @include($activeTemplate.'layouts.breadcrumb')
    <div class="card custom--card p-0 p-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="custom--table table">
                    <thead>
                        <tr>
                            <th class="bg-success">@lang('Product')</th>
                            <th class="bg-success">@lang('Quantity')</th>
                            <th class="bg-success">@lang('Price')</th>
                            <th class="bg-success">@lang('Total Price')</th>
                            
                            <th class="bg-success">@lang('Status')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)

                            <tr>
                                <td>
                                    @if (@$order->product)
                                        <a href="{{ route('user.product.details', ['id' => @$order->product->id, 'slug' => slug($order->product->name)]) }}">
                                            {{ __(strLimit($order->product->name, '30')) }}</a>
                                        <x-product.sku :value="$order->product?->sku" class="ms-1" />
                                    @endif
                                </td>
                                <td>{{ $order->quantity }}</td>
                                <td>{{ showAmount($order->price) }}</td>
                                <td>{{ showAmount($order->total_price) }}</td>
                                
                                <td>
                                    @php echo $order->statusOrderBadge @endphp
                                </td>
                            @if(!$loop->last)
                                </tr><tr><td colspan="6">&nbsp; <hr></td></tr>
                            @endif
                        @empty
                            <tr>
                                <td class="text-center" colspan="100%">@lang('No order found')</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @if ($orders->hasPages())
        <div class="mt-4">
            {{ paginateLinks($orders) }}
        </div>
    @endif
@endsection


@push('script')
    <script>
        'use strict';

        function myFunction(id) {
            var copyText = document.getElementById(id);
            copyText.select();
            copyText.setSelectionRange(0, 99999)
            document.execCommand("copy");
            notify('success', 'Url copied successfully ' + copyText.value);
        }
    </script>
@endpush

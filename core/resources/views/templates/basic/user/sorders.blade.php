@extends($activeTemplate . 'layouts.master')
@section('content')
 @include($activeTemplate.'layouts.breadcrumb')
    <div class="card custom--card p-0 p-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="custom--table table">
                    <thead>
                        <tr>
                            <th class="bg-success">@lang('S/N')</th>
                            <th class="bg-success">@lang('Product')</th>
                            <th class="bg-success">@lang('Quantity')</th>
                            <th class="bg-success">@lang('Supplied Price')</th>
                            <th class="bg-success">@lang('TRX')</th>
                            
                            <th class="bg-success">@lang('Status')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sorders as $sorder)

                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    @if (@$sorder->product)
                                        <a href="{{ route('user.product.details', ['id' => @$sorder->product->id, 'slug' => slug($sorder->product->name)]) }}">
                                            {{ __(strLimit($sorder->product->name, '30')) }}</a>
                                        <x-product.sku :value="$sorder->product?->sku" class="ms-1" />
                                    @endif
                                </td>
                                <td>{{ $sorder->quantity }}</td>
                                <td>
                                    @if($sorder->price > 0)
                                    {{ showAmount($sorder->price) }}
                                    @else
                                        NILL
                                    @endif
                                </td>
                                <td>@if($sorder->status != 0)
                                    {{ $sorder->trx }}
                                    @else
                                       NILL
                                    @endif
                                    </td>
                                
                                <td>
                                    @php echo $sorder->statusSorderBadge @endphp
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
    @if ($sorders->hasPages())
        <div class="mt-4">
            {{ paginateLinks($sorders) }}
        </div>
    @endif
@endsection


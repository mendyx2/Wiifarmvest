@extends('admin.layouts.app') 
@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                            <tr>  
                                <th>@lang('User')</th>
                                <th>@lang('Email-Mobile')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Activated At')</th>
                                <th>@lang('Balance')</th>
                                <th>@lang('Action')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($stockists as $stockist)
                            <tr>
                                <td>
                                    <span class="fw-bold">{{$stockist->user->fullname}}</span>
                                    <br>
                                    <span class="small">
                                    <a href="{{ route('admin.users.detail', $stockist->user->id) }}"><span>@</span>{{ $stockist->user->username }}</a>
                                    </span>
                                </td>


                                <td>
                                    {{ $stockist->user->email }}<br>{{ $stockist->user->mobileNumber }}
                                </td>
                                <td>
                                    @if ($stockist->status == 1)
                                                <span class="badge badge--success">@lang('Active')</span>
                                                <br>
                                                {{ diffForHumans($stockist->updated_at) }}
                                    @elseif($stockist->status == 0)
                                                <span class="badge badge--danger">@lang('InActive')</span>
                                                <br>
                                                {{ diffForHumans($stockist->updated_at) }}
                                    @endif
                                </td>



                                <td>
                                    {{ showDateTime($stockist->created_at) }} <br> {{ diffForHumans($stockist->created_at) }}
                                </td>


                                <td>
                                    <span class="fw-bold">

                                    {{ showAmount($stockist->wallet) }}
                                    </span>
                                </td>

                                <td>
                                   
                                </td>

                            </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                </tr>
                            @endforelse

                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
                @if ($stockists->hasPages())
                <div class="card-footer py-4">
                    {{ paginateLinks($stockists) }}
                </div>
                @endif
            </div>
        </div>


    </div>
@endsection



@push('breadcrumb-plugins')
    <x-search-form placeholder="Username / Email" />
@endpush

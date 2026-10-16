@extends('admin.layouts.app')

@section('panel')

    <div class="row">
        <div class="col-12">
            <div class="row gy-4">
                <div class="col-xxl-3 col-sm-6">
                    <x-widget
                        style="7"
                        link="#"
                        title=" Stockist Balance"
                        icon="las la-money-bill-wave-alt"
                        value="{{ showAmount($stockist->wallet) }}"
                        bg="indigo"
                        type="2"
                    />
                </div>

                <div class="d-flex flex-wrap gap-3 mt-4">
                <div class="flex-fill">
                    <button data-bs-toggle="modal" data-bs-target="#addSubModal" class="btn btn--success btn--shadow w-100 btn-lg bal-btn" data-act="add">
                        <i class="las la-plus-circle"></i> @lang('Balance')
                    </button>
                </div>

                <div class="flex-fill">
                    <button data-bs-toggle="modal" data-bs-target="#addSubModal" class="btn btn--danger btn--shadow w-100 btn-lg bal-btn" data-act="sub">
                        <i class="las la-minus-circle"></i> @lang('Balance')
                    </button>
                </div>

                <div class="flex-fill">
                    <a href="{{route('admin.report.login.history')}}?search={{ $stockist->user->username }}" class="btn btn--primary btn--shadow w-100 btn-lg">
                        <i class="las la-list-alt"></i>@lang('Logins')
                    </a>
                </div>

                <div class="flex-fill">
                    <a href="{{ route('admin.users.notification.log',$stockist->user_id) }}" class="btn btn--secondary btn--shadow w-100 btn-lg">
                        <i class="las la-bell"></i>@lang('Notifications')
                    </a>
                </div>

               

                <div class="flex-fill">
                    @if($stockist->status == Status::ACTIVE)
                    <button type="button" class="btn btn--warning btn--shadow w-100 btn-lg userStatus" data-bs-toggle="modal" data-bs-target="#stockistStatusModal">
                        <i class="las la-ban"></i>@lang('Deactive stockist')
                    </button>
                    @else
                    <button type="button" class="btn btn--success btn--shadow w-100 btn-lg userStatus" data-bs-toggle="modal" data-bs-target="#stockistStatusModal">
                        <i class="las la-undo"></i>@lang('Activate stockist')
                    </button>
                    @endif
                </div>
            </div>


                


            </div>


        </div>

            
    </div>


<div id="stockistStatusModal" class="modal fade" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if($stockist->status == Status::ACTIVE) @lang('Deactivate stockist') @else @lang('Activate stockist') @endif
                    </h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{route('admin.stockist.changeUserStockistStatus', $stockist->id)}}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @if($stockist->status == Status::ACTIVE)
                        <h6 class="mb-2">@lang('If you Deactivate this user he/she won\'t be able to access his/her Stockist dashboard.')</h6>
                        <div class="form-group">
                            <label>@lang('Reason')</label>
                            <textarea class="form-control" name="status_reason" rows="4" required></textarea>
                        </div>
                        @else
                        <p><span>@lang('Deactivate reason was'):</span></p>
                        <p>{{ $stockist->status_reason }}</p>
                        <h4 class="text-center mt-3">@lang('Are you sure you want to Activate this user?')</h4>
                        @endif
                    </div>
                    <div class="modal-footer">
                        @if($stockist->status == Status::ACTIVE)
                        <button type="submit" class="btn btn--primary h-45 w-100">@lang('Deactivate')</button>
                        @else
                        <button type="button" class="btn btn--dark" data-bs-dismiss="modal">@lang('No')</button>
                        <button type="submit" class="btn btn--primary">@lang('Activate')</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a href="{{route('admin.stockist.index')}}"  class="btn btn-sm btn-outline--warning" ><i class="las la-sign-in-alt"></i>@lang('Back')</a>
    <a href="{{route('admin.users.login',$stockist->user_id)}}" target="_blank" class="btn btn-sm btn-outline--primary" ><i class="las la-sign-in-alt"></i>@lang('Login as User')</a>
@endpush

@push('script')
<script>
    (function($){
    "use strict"

        $('.bal-btn').on('click',function(){
            $('.balanceAddSub')[0].reset();
            var act = $(this).data('act');
            $('#addSubModal').find('input[name=act]').val(act);
            if (act == 'add') {
                $('.type').text('Add');
            }else{
                $('.type').text('Subtract');
            }
        });

        let mobileElement = $('.mobile-code');
        $('select[name=country]').on('change',function(){
            mobileElement.text(`+${$('select[name=country] :selected').data('mobile_code')}`);
        });

    })(jQuery);
</script>
@endpush

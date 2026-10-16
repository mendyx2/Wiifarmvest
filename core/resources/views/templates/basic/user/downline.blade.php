@extends($activeTemplate . 'layouts.master')
@section('content')
 @include($activeTemplate.'layouts.breadcrumb')
    <div class="container">
        <div class="row">
             

            <div class="row justify-content-center g-3">
               
                
            </div>
            <div class="card custom--card responsive-filter-card b-radius--10 mb-4">
                <div class="card-body">
                    <form  action="{{ route('user.downline1') }}" method="post" >
                        @csrf
                        <div class="d-flex flex-wrap gap-4">
                            <div class="flex-grow-1">
                                <label class="form--label form-label">@lang('Enter user name')</label>
                                <input class="form-control form--control" name="username" type="search" required >
                            </div>
                            
                            
                            <div class="flex-grow-1 align-self-end">
                                <button class="btn btn--base w-100 h-50"><i class="las la-filter"></i> @lang('Submit')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
    @endsection
    @push('script')
        <script>
            'use strict';
            (function($) { $('body').on('click', '#__modal_close', function(e) {
                    $("#plan_info_modal").modal('hide');
                });

                $('.__subscribe').on('click', function(e) {
                    let id = $(this).attr('data-id');
                    $('#plan_id').attr('value', id);
                    $("#subscribe_modal").modal('show');
                })
            })(jQuery)
        </script>
@endpush
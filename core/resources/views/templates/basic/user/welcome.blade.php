@extends($activeTemplate . 'layouts.master1')
@section('content')

@include($activeTemplate.'layouts.breadcrumb')
<div class="container px-4" id="custom-cards">
    <div class="row row-cols-1 row-cols-lg-2 align-items-stretch g-4 ">
        <div class="col">
            <div class="card card-cover h-100 overflow-hidden text-bg-dark rounded-4 shadow-lg" style="background-image: url('https://wiifarmcoop.org/food_production.png')
                   ">
                  
                <div class="d-flex flex-column h-100 p-5 pb-3 text-white text-shadow-1">
                    <h3 class="pt-5 mt-5 mb-4 display-6 lh-1 fw-bold text-white"><a href="{{route('user.home')}}" class="text-white"> Food Distribution </a></h3>
                    
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card card-cover h-100 overflow-hidden text-bg-dark rounded-4 shadow-lg" style="background-image: url('https://wiifarmcoop.org/food_distribution.png')
                   ">
                  
                <div class="d-flex flex-column h-100 p-5 pb-3 text-white text-shadow-1">
                    <h3 class="pt-5 mt-5 mb-4 display-6 lh-1 fw-bold text-white">
                    <a href="{{route('user.land')}}" class="text-white">Food Production
                    </a></h3>
                    
                </div>
            </div>
        </div>
    </div>
</div>
   
@endsection

       

@push('modal')
        <div class="modal fade" id="subscribe_modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">@lang('Activate Account')</h4>
                                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                        <i class="las la-times"></i>
                                    </button>
                            </div>
                            <div class="modal-body">
                                <h5>@lang('Ensure you have up to the activation fee in your account ')</h5>
                            </div>

                            <div class="modal-footer">
                                <form method="post" action="{{ route('user.matrix.activate') }}">
                                    @csrf
                                    <input class="form-control form--control" class="d-none" id="plan_id" name="activate" type="hidden">
                                    <button class="btn btn--dark btn--sm" data-bs-dismiss="modal" type="button">@lang('Close')</button>
                                    <button class="btn btn--base btn--sm" type="submit"> @lang('Activated')</button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
@endpush

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
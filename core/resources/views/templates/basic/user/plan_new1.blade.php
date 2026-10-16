@extends($activeTemplate . 'layouts.master2')
 
@section('content')
 @include($activeTemplate.'layouts.breadcrumb')

<div class="bd-example-snippet bd-code-snippet">
    <div class="bd-example">
        <nav>
            <div class="nav nav-tabs mb-3" id="nav-tab" role="tablist">
                <button class="nav-link active text-success" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true">Current Offering</button>
                <button class="nav-link text-success" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                    Proposed Project
                </button>
                <button class="nav-link text-success" id="nav-contact-tab" data-bs-toggle="tab" data-bs-target="#nav-contact" type="button" role="tab" aria-controls="nav-contact" aria-selected="false">Closed Project</button>
            </div>
        </nav>
        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                <div class="row  row-cols-1 row-cols-md-2 g-4">
                    <div class="col px-4">
                        <div class="card">
                          <img src="https://wiifarmcoop.org/food2.png" class="d-block mx-lg-auto img-fluid" alt="Bootstrap Themes" width="700" height="500" loading="lazy">

                          <div class="card-body">
                            <h5 class="card-title">Rice Farm</h5>
                            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                            <a href="#" class="btn btn-sm btn-outline-success">View contract</a>
                          </div>
                        </div>
                    </div>
                    <div class="col px-4">
                        <div class="card">
                          <img src="https://wiifarmcoop.org/food2.png" class="d-block mx-lg-auto img-fluid" alt="Bootstrap Themes" width="700" height="500" loading="lazy">

                          <div class="card-body">
                            <h5 class="card-title">Rice Farm</h5>
                            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                            <a href="#" class="btn btn-sm btn-outline-success">View contract</a>
                          </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                    <p>...</p>
            </div>
            <div class="tab-pane fade" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab">
                    <p>....</p>
            </div>
        </div>
    </div>
</div>


 <div class="container col-xl-12 col-xxl-12 px-4 py-1">
    <div class="row align-items-center g-lg-5 py-5">
      <div class="col-lg-7 text-center text-lg-start">
        <h1 class="display-4 fw-bold lh-1 mb-3">Food Production</h1>
        <p class="col-lg-10 fs-4">Below is an example form built entirely with Bootstrap’s form controls. Each required form group has a validation state that can be triggered by attempting to submit the form without completing it.</p>
      </div>
      <div class="col-md-10 mx-auto col-lg-5">
        <form class="p-4 p-md-5 border rounded-3 bg-light">
          <div class="form-floating mb-3">
            <input type="email" class="form-control" id="floatingInput" placeholder="name@example.com">
            <label for="floatingInput">Email address</label>
          </div>
          <div class="form-floating mb-3">
            <input type="password" class="form-control" id="floatingPassword" placeholder="Password">
            <label for="floatingPassword">Password</label>
          </div>
          <div class="checkbox mb-3">
            <label>
              <input type="checkbox" value="remember-me"> Remember me
            </label>
          </div>
          <button class="w-100 btn btn-lg btn-primary" type="submit">Sign up</button>
          <hr class="my-4">
          <small class="text-muted">By clicking Sign up, you agree to the terms of use.</small>
        </form>
      </div>
    </div>
</div>
    <div class="row">
        @foreach ($plans as $data)
            <div class="col-xl-4 col-md-4 mb-30 mb-4">
                <div class="card custom--card">
                    <div class="card-body">
                        <div class="pricing-table mb-4 text-center">
                            <h4 class="package-name text- mb-20"><strong>{{ __(strtoupper('Fractional Land Ownership')) }}</strong></h4>
                            <span class="price text--dark font-weight-bold d-block">{{ showAmount($data->price) }} per unit</span>
                            <hr>
                            <ul class="package-features-list mt-30">
                                
                                {{-- <li>
                                    <i class="las la-comment-dollar __plan_info text--primary" data="ref_com"></i><span> @lang('Referral Commission'):
                                        {{ gs('cur_sym') }}{{ getAmount($data->ref_com) }}
                                    </span>
                                </li>
                                <li>
                                    <i class="las la-business-time __plan_info text--primary" data="bv"></i> <span>@lang('Leve 2 Commission'):
                                        {{ getAmount($data->lev2) }}</span>
                                </li>
                                <li>
                                    <i class="las la-comments-dollar __plan_info text--primary" data="tree_com"></i>
                                    <span>@lang('Level 3 Commission'):
                                        {{ gs('cur_sym') }}{{ getAmount($data->lev3) }}
                                    </span>
                                </li>
                                <li>
                                    <i class="las la-comments-dollar __plan_info text--primary" data="tree_com"></i>
                                    <span>@lang('Level 4 Commission'):
                                        {{ gs('cur_sym') }}{{ getAmount($data->lev4) }}
                                    </span>
                                </li>
                                --}}
                                <li>
                                    <i class="las la-comments-dollar __plan_info text--primary" data="tree_com"></i>
                                    <span>Click the link to read more on this offer
                                    </span><br>
                                    <span ><a href="https://wiifarmcoop.org/fractional-farmland-ownership/" target="_blank" class="text-primary">Read More</a></span>
                                </li>
                                 <li>
                                    
                                    <strong>
                                        <span>@lang('Total Cost'):
                                        {{ gs('cur_sym') }} 
                                          <span id="total_inv">{{ getAmount($data->price) }}</span>
                                          <br/>
                                          5%: <span id="total_inv_five">{{ getAmount($data->price * 0.05) }}</span>
                                        </span>
                                    </strong>
                                    <br/><br/>
                                    <div class="row d-flex justify-content-center">

                                        <input type="number" id="total_unit" class=" total_invest form-control align-center" name="total_unit"  data-amount="{{ getAmount($data->price) }}" style="width: 140px !important; "/>
                                    </div>


                                </li>
                            </ul>
                        </div>
                        <div class="text-center">
                            
                                <a class="cmn--btn active __subscribe" data-id="{{ $data->id }}"  href="#"><span>@lang('Reserve Unit')</span></a>
                           {{-- @if (auth()->user()->plan_id != $data->id)
                            @else
                                <a class="cmn--btn active"><span>@lang('Already Reserved')</span></a>
                            @endif
                            --}} 
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('modal')
    <div class="modal fade" id="plan_info_modal" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="plan_info_modal_title">@lang('Commission to tree info')</h5>
                </div>
                <div class="modal-body">
                </div>
                <div class="modal-footer text-right">
                    <div class="row">
                        <div class="col-lg-12">
                            <button class="btn btn--dark btn--sm" id="__modal_close" type="button">@lang('Close')</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="subscribe_modal" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Confirm Reservation')?</h4>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="las la-times"></i>
                        </button>
                </div>
                <div class="modal-body">
                    <h5>@lang('Total Amount is : ')<span id="invest_amount1"></span>
                        <br>
                        @lang('5% is : ')<span id="invest_amount_five1"></span>
                    </h5>

                </div>

                <div class="modal-footer">
                    <form method="post" action="{{ route('user.plan.reserve') }}">
                        @csrf
                        <input class="form-control form--control" class="d-none" id="plan_id" name="plan_id" type="hidden">
                        <input class="form-control form--control" class="d-none" id="invest_amount" name="invest_amount" type="hidden">
                        <input class="form-control form--control" class="d-none" id="invest_amount_five" name="invest_amount_five" type="hidden">
                        <input class="form-control form--control" class="d-none" id="units" name="units" type="hidden">
                        <button class="btn btn--danger btn--sm" data-bs-dismiss="modal" type="button">@lang('Close')</button>
                        <button class="btn btn--base btn--sm" type="submit"> @lang('Reserve It')</button>
                    </form>
                </div>

            </div>
        </div>
    </div>
@endpush

@push('script')
    <script>
        'use strict';
        (function($) {
            $('.__plan_info').on('click', function(e) {
                let html = "";
                let data = $(this).attr('data');
                let modal = $("#plan_info_modal");
                if (data == 'bv') {
                    html = ` <h5>   <span class="text--danger">@lang('When someone from your below tree subscribe this plan, You will get this Business Volume  which will be used for matching bonus').</span>
                </h5>`
                    modal.find('#plan_info_modal_title').html("@lang('Business Volume (BV) info')")

                }
                if (data == 'ref_com') {
                    html = `  <h5>  <span class=" text--danger">@lang('When Your Direct-Referred/Sponsored  User Subscribe in') <b> @lang('ANY PLAN') </b>, @lang('You will get this amount').</span>
                        <br>
                        <br>
                        <span class="text--success"> @lang('This is the reason You should Choose a Plan With Bigger Referral Commission').</span> </h5>`
                    modal.find('#plan_info_modal_title').html("@lang('Referral Commission info')")

                }
                if (data == 'tree_com') {
                    html = ` <h5 class=" text--danger">@lang('When someone from your below tree subscribe this plan, You will get this amount as Tree Commission'). </h5>`
                    modal.find('#plan_info_modal_title').html("@lang('Referral Commission info')")

                }
                modal.find('.modal-body').html(html)
                $(modal).modal('show')
            });

            $('body').on('click', '#__modal_close', function(e) {
                $("#plan_info_modal").modal('hide');
            });

            $('.total_invest').on('keyup', function(e) {
                var q = $(this).val();
                if (q > 0) {
                    let unit = $(this).attr('data-amount');
                    var tt = unit * q;
                    var five = 0.05 * tt;
                   $('#total_inv').html(tt);
                   $('#total_inv_five').html(five);
                   $('#invest_amount').attr('value', tt);
                   $('#invest_amount1').html(tt);
                   $('#invest_amount_five1').html(five);
                   $('#invest_amount_five').attr('value', five);
                   $('#units').attr('value', unit);
                   
                }
                
            });

            $('.__subscribe').on('click', function(e) {
                let id = $(this).attr('data-id');
                $('#plan_id').attr('value', id);
                $("#subscribe_modal").modal('show');
            })
        })(jQuery)
    </script>
@endpush

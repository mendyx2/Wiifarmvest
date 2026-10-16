@extends($activeTemplate . 'layouts.master2')
 
@section('content')
 @include($activeTemplate.'layouts.breadcrumb')

<div class="bd-example-snippet bd-code-snippet">
    <div class="bd-example">
        <nav>
            <div class="nav nav-tabs mb-3" id="nav-tab" role="tablist">
                <button class="nav-link active text-success" id="nav-home-tab" data-bs-toggle="tab" data-bs-target="#nav-home" type="button" role="tab" aria-controls="nav-home" aria-selected="true">Current Offering</button>

                <button class="nav-link text-success" id="nav-profile-tab" data-bs-toggle="tab" data-bs-target="#nav-profile" type="button" role="tab" aria-controls="nav-profile" aria-selected="false">
                    Subscription 
                </button>
 
                <button class="nav-link text-success" id="nav-contact-tab" data-bs-toggle="tab" data-bs-target="#nav-contact" type="button" role="tab" aria-controls="nav-contact" aria-selected="false">Documents</button>

                <button class="nav-link text-success" id="nav-contact-tab" data-bs-toggle="tab" data-bs-target="#nav-reservation" type="button" role="tab" aria-controls="nav-reservation" aria-selected="false">My Subscriptions</button>
            </div>
        </nav>
        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab">
                <div class="row  row-cols-1 row-cols-md-2 g-4">
                    <div class="col px-6">
                        <div class="card">
                          <img src="{{ asset($activeTemplateTrue . 'images/rice_field.jpeg') }}" class="d-block mx-lg-auto img-fluid  shadow-lg" alt="Bootstrap Themes" width="700" height="500" loading="lazy">

                          <div class="card-body">
                            <h5 class="card-title">WiiFarmCoorp  Farm Estate
                                <span class="float-end badge bg-success">
                                    <svg height="22" width="22" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
                                         viewBox="0 0 255.856 255.856" xml:space="preserve">
                                    <g>
                                        <path style="fill:#ffffff;" d="M127.928,38.8c-30.75,0-55.768,25.017-55.768,55.767s25.018,55.767,55.768,55.767
                                            s55.768-25.017,55.768-55.767S158.678,38.8,127.928,38.8z M127.928,135.333c-22.479,0-40.768-18.288-40.768-40.767
                                            S105.449,53.8,127.928,53.8s40.768,18.288,40.768,40.767S150.408,135.333,127.928,135.333z"/>
                                        <path style="fill:#ffffff;" d="M127.928,0C75.784,0,33.362,42.422,33.362,94.566c0,30.072,25.22,74.875,40.253,98.904
                                            c9.891,15.809,20.52,30.855,29.928,42.365c15.101,18.474,20.506,20.02,24.386,20.02c3.938,0,9.041-1.547,24.095-20.031
                                            c9.429-11.579,20.063-26.616,29.944-42.342c15.136-24.088,40.527-68.971,40.527-98.917C222.495,42.422,180.073,0,127.928,0z
                                             M171.569,181.803c-19.396,31.483-37.203,52.757-43.73,58.188c-6.561-5.264-24.079-26.032-43.746-58.089
                                            c-22.707-37.015-35.73-68.848-35.73-87.336C48.362,50.693,84.055,15,127.928,15c43.873,0,79.566,35.693,79.566,79.566
                                            C207.495,112.948,194.4,144.744,171.569,181.803z"/>
                                    </g>
                                    </svg>
                                Uyo</span>

                            </h5>
                            <p class="card-text text-black">WiiFarmCoop Multipurpose Farm Estate
                            The WiiFarmCoop Multipurpose Farm Estate is a 10,000-hectare, member-driven agricultural development project designed to unlock the vast potential of Nigeria’s food economy.<br/>
                            .

                            <img src="{{ asset($activeTemplateTrue . 'images/rice_field2.jpeg') }}" class="d-block mx-lg-auto img-fluid rounded-4 shadow-lg m-4" alt="Bootstrap Themes" width="700" height="500" loading="lazy">

                            Strategically starting from Itu in Akwa Ibom State, the estate will be developed in phases and expanded across multiple local government areas within the state.
                            <br/>
                            The project focuses on the large-scale production of rice, corn, beef, chicken, and fish, using fully mechanised, technology-enabled farming practices supported by strategic partnerships in agronomy, irrigation, and GPS-based land management.
                            <br/>
                            <img src="{{ asset($activeTemplateTrue . 'images/rice_featured.png') }}" class="d-block mx-lg-auto img-fluid  shadow-lg m-4" alt="Bootstrap Themes" width="700" height="500" loading="lazy">

                            
                            <br/>
                            This initiative is part of WiiFarmCoop’s broader vision to build wealth for its members, drive rural transformation, and contribute to national food security through cooperative land, capital, and labour mobilization.
                        </p>
                            <a href="#nav-profile" class="btn btn-sm btn-success tabShownav">Subscribe unit</a>
                            <!-- <a href="#" class="btn btn-sm btn-outline-success">Reserve unit</a> -->
                          </div>
                        </div>
                    </div>
                    <!-- 
                    <div class="col px-4">
                        <div class="card">
                          <img src="{{ asset($activeTemplateTrue . 'images/food2.png') }}" class="d-block mx-lg-auto img-fluid rounded-4 shadow-lg" alt="Bootstrap Themes" width="700" height="500" loading="lazy">

                          <div class="card-body">
                            <h5 class="card-title">Rice Farm &nbsp;
                                <span class="float-end badge bg-success">
                                    <svg height="22" width="22" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
                                         viewBox="0 0 255.856 255.856" xml:space="preserve">
                                    <g>
                                        <path style="fill:#000002;" d="M127.928,38.8c-30.75,0-55.768,25.017-55.768,55.767s25.018,55.767,55.768,55.767
                                            s55.768-25.017,55.768-55.767S158.678,38.8,127.928,38.8z M127.928,135.333c-22.479,0-40.768-18.288-40.768-40.767
                                            S105.449,53.8,127.928,53.8s40.768,18.288,40.768,40.767S150.408,135.333,127.928,135.333z"/>
                                        <path style="fill:#000002;" d="M127.928,0C75.784,0,33.362,42.422,33.362,94.566c0,30.072,25.22,74.875,40.253,98.904
                                            c9.891,15.809,20.52,30.855,29.928,42.365c15.101,18.474,20.506,20.02,24.386,20.02c3.938,0,9.041-1.547,24.095-20.031
                                            c9.429-11.579,20.063-26.616,29.944-42.342c15.136-24.088,40.527-68.971,40.527-98.917C222.495,42.422,180.073,0,127.928,0z
                                             M171.569,181.803c-19.396,31.483-37.203,52.757-43.73,58.188c-6.561-5.264-24.079-26.032-43.746-58.089
                                            c-22.707-37.015-35.73-68.848-35.73-87.336C48.362,50.693,84.055,15,127.928,15c43.873,0,79.566,35.693,79.566,79.566
                                            C207.495,112.948,194.4,144.744,171.569,181.803z"/>
                                    </g>
                                    </svg>
                                Abuja</span></h5>
                            
                            <p class="card-text text-black">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                            <a href="#" class="btn btn-sm btn-outline-success">View Contract</a>
                          </div>
                        </div>
                    </div> 
                    -->
                </div>
            </div>
            <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab">
                <div class="col">
                    @foreach ($plans as $data)
                        <div class="col-xl-6 col-lg-6 col-md-6 mb-4">
                            <form class=" p-md-5 border  bg-light rounded-4">
                                <div class="mb-3">
                                    <label for="exampleInputPassword1" class="form-label">Price per unit</label>
                                    <input type="text" class="form-control" id="exampleInputPassword1" readonly value="{{ showAmount($data->price)}}">
                                </div>
                                <div class="mb-3">
                                    <label for="exampleInputPassword1" class="form-label">@lang('Total Payable')</label>
                                    <input type="text" readonly class="form-control" id="total_inv" value="{{ getAmount($data->price) }}">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="exampleInputPassword1" class="form-label">Number of Units</label>
                                    <input type="number" min="5" class="form-control total_invest unit-input" placeholder="Number of Units" name="total_unit"  data-amount="{{ getAmount($data->price) }}">
                                    <div class="form-text text-success">Minimum 5 units required.</div>
                                </div>

                                <!-- <div class="form-floating mb-3">
                                 
                                    <input type="number" class="form-control total_invest" id="total_unit" placeholder="Number of Units" name="total_unit"  data-amount="{{ getAmount($data->price) }}">
                                    <label for="floatingInput">Number of Units</label>
                                </div> -->

                               <a class="btn btn-sm btn-success rounded-pill  __subscribe " data-id="{{ $data->id }}"  href="#"><span>@lang('Subscribe Units')</span></a>
                            </form>
                            
                        </div>
                    @endforeach

                </div>
            </div>
            <div class="tab-pane fade" id="nav-contact" role="tabpanel" aria-labelledby="nav-contact-tab">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  Documents to be issued at allocation
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                    <div class="row">
                        <div class="col-lg-8">

                            <div class="document-item">
                                <div class="d-flex justify-content-between align-items-center">


                                    <div>
                                        <h6 class="mb-1">Deed of Sublease ( from Master Lease)</h6>
                                        
                                    </div>
                                    <button class="btn btn-outline-success btn-sm" >
                                        <i class="bi bi-download me-1"></i>Download
                                    </button>
                                </div>
                            </div>
                            <div class="document-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Farm Management Agreement</h6>
                                        
                                    </div>
                                    <button class="btn btn-outline-success btn-sm" >
                                        <i class="bi bi-download me-1"></i>Download
                                    </button>
                                </div>
                            </div>
                            
                        </div>
                    </div>

            </div>
            <div class="tab-pane fade" id="nav-reservation" role="tabpanel" aria-labelledby="nav-reservation-tab">
                <div class="row">
                    
                
                    @foreach($rinvest as $rivest)
                        <div class="col-lg-6  col-xxl-4 col-sm-6">
                          <div class="modal-dialog" role="document">
                            <div class="modal-content rounded-4 shadow">
                              <div class="modal-body p-3">
                                <ul class="d-grid  list-unstyled">
                                  <li class="d-flex ">
                                    <div>
                                      <h5 class="mb-0">{{ $rivest->plan->name }}</h5>
                                      <hr>
                                     <span class="text-dark"><strong>Subscribed Unit:</strong> </span> &nbsp;<span class="badge bg-secondary">{{ $rivest->units }}</span> 

                                     <br>
                                     <span class="text-dark"><strong>Unit cost:</strong> </span>&nbsp;<span class="badge bg-secondary">{{ showAmount($rivest->unit_cost) }}</span>
                                     
                                     <span class="badge bg-info float-right">Approved</span></p>
                                    
                                    </div>
                                  </li>
                                  
                                </ul>
                                <form id="paymentForm" action="{{ route('user.plan.payment-receipt.generate-pdf') }}" method="POST">
                                    @csrf
                                    <input type="hidden" id="rivest_id" name="rivest_id" value="{{ $rivest->id }}">
                                    <input type="hidden" id="check" name="check" value="1">

                                    <button type="submit" class="btn btn-sm btn-info mt-2 w-100" data-bs-dismiss="modal">Generate PDF Receipt <svg width="22px" height="22px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path opacity="0.5" d="M3 15C3 17.8284 3 19.2426 3.87868 20.1213C4.75736 21 6.17157 21 9 21H15C17.8284 21 19.2426 21 20.1213 20.1213C21 19.2426 21 17.8284 21 15" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M12 3V16M12 16L16 11.625M12 16L8 11.625" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>

                                    </button>
                                </form>
                                <form id="paymentForm" action="{{ route('user.plan.payment-receipt.generate-pdf') }}" method="POST">
                                    @csrf
                                    <input type="hidden" id="rivest_id" name="rivest_id" value="{{ $rivest->id }}">
                                    <input type="hidden" id="check" name="check" value="2">

                                    <button type="submit" class="btn btn-sm btn-secondary mt-2 w-100" data-bs-dismiss="modal">Download Certificate <svg width="22px" height="22px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path opacity="0.5" d="M3 15C3 17.8284 3 19.2426 3.87868 20.1213C4.75736 21 6.17157 21 9 21H15C17.8284 21 19.2426 21 20.1213 20.1213C21 19.2426 21 17.8284 21 15" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M12 3V16M12 16L16 11.625M12 16L8 11.625" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>

                                    </button>
                                </form>
                                
                              </div>
                            </div>
                          </div>

                        </div>
                    @endforeach
                </div>

            </div>
        </div>
    </div>
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

   
<div class="modal fade" id="subscribe_modal" tabindex="-1" aria-labelledby="exampleModalCenteredScrollableTitle" aria-hidden="true">
  
  <div class="modal-dialog" role="document">
    <div class="modal-content rounded-4 shadow">
        <div class="modal-header border-0 text-center">
            <div class="w-100">
                <img src="{{ asset($activeTemplateTrue . 'images/logo/Wordmark.png') }}" style="width:60px !important">                   
            </div>         
        </div>
      <div class="modal-body p-4">
        <h3 class="fw-bold mb-0">@lang('Confirm Subscription')</h3>

        <ul class="d-grid gap-4 my-5 list-unstyled">
          <ul class="list-group mb-3">
             
              <li class="list-group-item d-flex justify-content-between lh-sm">
                <div>
                  <h6 class="my-0">@lang('Total Amount Payable is : ')</h6>
                </div>
                <span class="text-muted">₦<span id="invest_amount1"></span></span>

              </li>

             {{--  <li class="list-group-item d-flex justify-content-between lh-sm">
                <div>
                  <h6 class="my-0">@lang('10% Down Payment : ')</h6>
                </div>
                <span class="text-muted">₦<span id="invest_amount_five1"></span></span>
              </li> --}}
          </ul>
          
        </ul>
            <form method="post" action="{{ route('user.plan.reserve') }}">
                @csrf
                <input class="form-control form--control" class="d-none" id="plan_id" name="plan_id" type="hidden">
                <input class="form-control form--control" class="d-none" id="invest_amount" name="invest_amount" type="hidden">
                <input class="form-control form--control" class="d-none" id="invest_amount_five" name="invest_amount_five" type="hidden">
                        <input class="form-control form--control" class="d-none" id="units" name="units" type="hidden">
                        <input class="form-control form--control" class="d-none" id="qtys" name="qtys" type="hidden">

                        <button class="btn btn--danger btn--sm" data-bs-dismiss="modal" type="button">@lang('Close')</button>
                        <button class="btn btn--base btn--sm" type="submit"> @lang('Pay Now')</button>
                    </form>

      </div>
    </div>
  </div>
</div>
<svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
  
  <symbol id="check2-circle" viewBox="0 0 16 16">
    <path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0z"/>
    <path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l7-7z"/>
  </symbol>

</svg>
@endpush

@push('style')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .project-header {
            color: #28a745;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 2rem;
        }
        .nav-tabs .nav-link {
            color: #28a745;
            border: none;
            font-weight: 500;
            padding: 12px 24px;
        }
        .nav-tabs .nav-link.active {
            background-color: #e8f5e8;
            color: #28a745;
            border-bottom: 3px solid #28a745;
        }
       
      
        .location-badge {
            background-color: #28a745;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        .reserve-btn {
            background-color: #28a745;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
        }
        .document-item {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.2s;
        }
        .document-item:hover {
            border-color: #28a745;
            box-shadow: 0 2px 10px rgba(40, 167, 69, 0.1);
        }
        .reservation-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
    </style>

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
                    var five = 0.10 * tt;
                   $('#total_inv').html(tt);
                   $('#total_inv').attr('value', tt);
                   //$('#total_inv_five').attr('value', five)
                   //$('#total_inv_five').html(five);
                   $('#invest_amount').attr('value', tt);
                   $('#invest_amount1').html(tt);
                   $('#invest_amount_five1').html(five);
                   $('#invest_amount_five').attr('value', five);
                   $('#units').attr('value', unit); 
                   $('#qtys').attr('value', q);         
                }    
            });
            // 
               
            $('.tabShownav').on('click', function(e) {

                $('#nav-profile-tab').tab('show');
            });
            


            $('.__subscribe').on('click', function(e) {
                let id = $(this).attr('data-id');
                const form = $(this).closest('form');
                let qty = parseInt(form.find('.unit-input').val() || 0);
                let unitCost = parseFloat(form.find('.total_invest').data('amount') || 0);

                if (qty < 5 || isNaN(qty)) {
                    alert('Minimum 5 units required to subscribe.');
                    return;
                }

                let totalAmount = unitCost * qty;
                let amountFive = (totalAmount * 0.10).toFixed(2);

                $('#plan_id').val(id);
                $('#invest_amount').val(totalAmount);
                $('#invest_amount_five').val(amountFive);
                $('#units').val(unitCost);
                $('#qtys').val(qty);
                $('#invest_amount1').text(totalAmount.toFixed(2));

                $("#subscribe_modal").modal('show');
            });


        })(jQuery)

          $(document).ready(function () {
            // Get the hash from the URL, e.g., "#nav-profile"
            var hash = window.location.hash;

            // Check if a hash is present in the URL
            if (hash) {
              // Find the tab link with the matching href and trigger a click
               $('#nav-profile-tab').tab('show');
              //$('ul.nav a[href="' + hash + '"]').tab('show');
            }
          });

    </script>
@endpush

@extends($activeTemplate . 'layouts.master2')
 
@section('content')
 @include($activeTemplate.'layouts.breadcrumb')
<div class="container px-4" id="custom-cards">
    <div class="d-flex justify-content-end mb-4">
        <button type="button" class="btn btn-success btn-lg rounded-pill shadow" id="btnOpenBuySharesModal" style="font-weight: bold; padding: 12px 24px;">
            <i class="fas fa-coins me-2"></i>Buy Shares
        </button>
    </div>
    <div class="row row-cols-1 row-cols-lg-2 align-items-stretch g-4 ">
        <div class="col">
            @foreach ($plans as $data)
            <div class="col-xl-12 col-md-12 mb-4">
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
                        <label for="exampleInputPassword1" class="form-label">Reserve at 10%:</label>
                        <input type="text" readonly class="form-control" id="total_inv_five" value="{{ getAmount($data->price * 0.10) }}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="exampleInputPassword1" class="form-label">Number of Units</label>
                        <input type="text" class="form-control total_invest" d="total_unit" placeholder="Number of Units" name="total_unit"  data-amount="{{ getAmount($data->price) }}">
                    </div>

                    <!-- <div class="form-floating mb-3">
                     
                        <input type="number" class="form-control total_invest" id="total_unit" placeholder="Number of Units" name="total_unit"  data-amount="{{ getAmount($data->price) }}">
                        <label for="floatingInput">Number of Units</label>
                    </div> -->

                   <a class="btn btn-sm btn-success rounded-pill  __subscribe " data-id="{{ $data->id }}"  href="#"><span>@lang('Reserve Units')</span></a>
                </form>
                
            </div>
        @endforeach
        </div>
        <div class="col">
            <img src="{{ asset($activeTemplateTrue . 'images/rice_featured.jpg') }}" class="img-fluid h-100 rounded-4 shadow-lg image-responsive">
        </div>
    </div>
</div>

{{----}}

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

    <!-- <div class="modal fade" id="subscribe_modal1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Confirm Reservation')?</h4>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <i class="las la-times"></i>
                        </button>
                </div>
                <div class="modal-body">
                    <h5>@lang('Total Amount Payable is : ')<span id="invest_amount1"></span>
                        <br>
                        @lang('10% is : ')<span id="invest_amount_five1"></span>
                    </h5>
                </div>

                <div class="modal-footer">
                    <form method="post" action="{{ route('user.plan.reserve') }}">
                        @csrf
                        <input class="form-control form--control" class="d-none" id="plan_id" name="plan_id" type="hidden">
                        <input class="form-control form--control" class="d-none" id="invest_amount" name="invest_amount" type="hidden">
                        <input class="form-control form--control" class="d-none" id="invest_amount_five" name="invest_amount_five" type="hidden">
                        <input class="form-control form--control" class="d-none" id="units" name="units" type="hidden">
                        <input class="form-control form--control" class="d-none" id="qtys" name="qtys" type="hidden">

                        <button class="btn btn--danger btn--sm" data-bs-dismiss="modal" type="button">@lang('Close')</button>
                        <button class="btn btn--base btn--sm" type="submit"> @lang('Reserve It')</button>
                    </form>
                </div>

            </div>
        </div>
    </div> -->
 

<div class="modal fade" id="subscribe_modal" tabindex="-1" aria-labelledby="exampleModalCenteredScrollableTitle" aria-hidden="true">
  
  <div class="modal-dialog" role="document">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-body p-5">
        <h2 class="fw-bold mb-0">@lang('Confirm Reservation')?</h2>

        <ul class="d-grid gap-4 my-5 list-unstyled">
          <ul class="list-group mb-3">
             
              <li class="list-group-item d-flex justify-content-between lh-sm">
                <div>
                  <h6 class="my-0">@lang('Total Amount Payable is : ')</h6>
                </div>
                <span class="text-muted">₦<span id="invest_amount1"></span></span>

              </li>

              <li class="list-group-item d-flex justify-content-between lh-sm">
                <div>
                  <h6 class="my-0">@lang('10% Down Payment : ')</h6>
                </div>
                <span class="text-muted">₦<span id="invest_amount_five1"></span></span>
              </li>
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

<div class="modal fade" id="buySharesModal" tabindex="-1" aria-labelledby="buySharesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header border-0">
        <h5 class="modal-title" id="buySharesModalLabel">Buy Shares</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <form method="POST" action="{{ route('user.shares.purchase') }}" id="buySharesForm">
            @csrf
            <div class="mb-3">
                <label for="modalPlanId" class="form-label">Select Plan</label>
                <select id="modalPlanId" class="form-select" name="plan_id" required>
                    <option value="">Choose a plan</option>
                    @foreach($plans as $plan)
                        <option value="{{ $plan->id }}" data-price="{{ $plan->price }}">{{ $plan->name }} - {{ showAmount($plan->price) }} / unit</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label for="modalQty" class="form-label">Units to Buy</label>
                <input type="number" id="modalQty" name="qtys" min="5" class="form-control" value="5" required>
                <small class="text-muted">Minimum 5 units.</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Total Payable</label>
                <div class="p-3 bg-light rounded-3" id="modalTotalPayable">₦0.00</div>
            </div>
            <button type="submit" class="btn btn--base w-100">Purchase Shares</button>
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
          
            $('.total_invest').on('keyup', function(e) {
                var q = $(this).val();
                if (q > 0) {
                    let unit = $(this).attr('data-amount');
                    var tt = unit * q;
                    var five = 0.10 * tt;
                   //$('#total_inv').html(tt);
                   $('#total_inv').attr('value', tt);
                   $('#total_inv_five').attr('value', five)
                   $('#total_inv_five').html(five);
                   $('#invest_amount').attr('value', tt);
                   $('#invest_amount1').html(tt);
                   $('#invest_amount_five1').html(five);
                   $('#invest_amount_five').attr('value', five);
                   $('#units').attr('value', unit); 
                   $('#qtys').attr('value', q);         
                }    
            });

            $('.__subscribe').on('click', function(e) {
                let id = $(this).attr('data-id');
                $('#plan_id').attr('value', id);
                $("#subscribe_modal").modal('show');
            });

            $('#btnOpenBuySharesModal').on('click', function () {
                $('#modalPlanId').val('');
                $('#modalQty').val(5);
                $('#modalTotalPayable').text('₦0.00');
                $("#buySharesModal").modal('show');
            });

            function updateModalTotal() {
                var planPrice = Number($('#modalPlanId option:selected').data('price') || 0);
                var qty = Number($('#modalQty').val() || 0);
                if (!planPrice || qty < 5) {
                    $('#modalTotalPayable').text('₦0.00');
                    return;
                }
                var total = planPrice * qty;
                $('#modalTotalPayable').text(new Intl.NumberFormat('en-US', { style: 'currency', currency: 'NGN' }).format(total));
            }

            $('#modalPlanId, #modalQty').on('change keyup', updateModalTotal);

            // initial update
            updateModalTotal();
        })(jQuery)
    </script>
@endpush

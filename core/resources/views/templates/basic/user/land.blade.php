@extends($activeTemplate . 'layouts.master2')
@section('content')
@include($activeTemplate.'layouts.breadcrumb')


<div class="container">
    <div class="row">
        <div class="row justify-content-center g-3">
            <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                <div class="dashboard-item">
                    <div class="dashboard-item-header">
                        <div class="header-left">
                            <h6 class="title">@lang('Money Box')</h6>
                            <h3 class="ammount theme-two">{{ showAmount(auth()->user()->balance) }}</h3>
                        </div>
                    <div class="right-content">
                        <div class="icon">
                             <i class="flaticon-wallet spin"></i>                            
                           <!--  <i class="flaticon-wallet spin"></i> --></div>
                        </div>
                    </div>
                    <a href="{{route('user.deposit.index1')}}">
                        <img src="{{ asset($activeTemplateTrue . 'images/deposit.png') }}" style="width: 55px; "/> 
                    </a>
                                    
                </div>
            </div>
            <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                <div class="dashboard-item">
                    <div class="dashboard-item-header">
                        <div class="header-left">
                            <h6 class="title">@lang('Total Unit Holding')</h6>
                            <h3 class="ammount theme-two">{{ $totalUnitReserve }}</h3>
                        </div>
                        <div class="icon"><i class="flaticon-clipboards"></i></div>
                        
                    </a>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                 <div class="dashboard-item">
                    <div class="dashboard-item-header">
                        <a href="https://wiifarmcoop.org/sys/user/plan#nav-profile">
                        <img class="img-fluid" src="{{ asset($activeTemplateTrue . 'images/subscribe.png') }}" style="width: 255px; "/> 
                        </a>
                    </div>
                </div> 
            </div>
            <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                <div class="dashboard-item">
                    <div class="dashboard-item-header">
                        <div class="header-left">
                            <h6 class="title">@lang('Total Referral Earnings')</h6>
                            <h3 class="ammount theme-two">{{ showAmount($total_ref) }}</h3>
                        </div>
                        <div class="icon"><i class="flaticon-clipboards"></i></div>
                    </div>
                </div>
            </div>
            {{--
            <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                        <div class="dashboard-item">
                            <div class="dashboard-item-header">
                                <div class="header-left">
                                    <h6 class="title">
                                        @lang('Current Stage')
                                    </h6>
                                    <h3 class="ammount">
                                        @if(getMatrixStage(auth()->id()))
                                            <span>{{ getMatrixStage(auth()->id())->stage->name }}</span>
                                        @else
                                            <span class="text--danger">@lang('N/A')</span>
                                        @endif
                                    </h3>
                                </div>
                                <div class="right-content">
                                    <div class="icon"><i class="las la-paper-plane"></i></div>
                                </div>
                            </div>
                           
                        </div>
            </div>
            --}}
            <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                <div class="dashboard-item">
                    <div class="dashboard-item-header">
                        <div class="header-left">
                            <h6 class="title">@lang('Total Withdraw')</h6>
                            <h3 class="ammount theme-one">{{ showAmount($totalWithdraw) }}</h3>
                        </div>
                    <div class="icon"><i class="flaticon-withdraw"></i></div>
                    </div>
                            
                </div>
            </div>
            <div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                        <div class="dashboard-item">
                            <div class="dashboard-item-header">
                                <div class="header-left">
                                    <h6 class="title">@lang('Pending Withdraw')</h6>
                                    <h3 class="ammount text--base">{{ getAmount($pendingWithdraw) }}</h3>
                                </div>
                                <div class="spinner-border text-primary" role="status">
                                  <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            
                        </div>
                    </div>
             
        </div>
    </div>
</div>

{{--

<div class="col-sm-6 col-md-6 col-lg-6 col-xl-4">
                <div class="dashboard-item">
                    <div class="dashboard-item-header">
                        <div class="header-left">
                            <h6 class="title">@lang('Money Box')</h6>
                            <h3 class="ammount theme-one">{{ showAmount(auth()->user()->balance) }}</h3>
                        </div>
                    <div class="right-content">
                        <div class="icon"><i class="flaticon-wallet spin"></i></div>
                        </div>
                    </div>
                                    
                </div>
            </div>

<div class="container col-xxl-12 col-lg-12 px-2 py-2">
    <div class="row flex-lg-row-reverse align-items-center g-5 py-1">
      <div class="col-12 col-sm-8 col-lg-6">
        
        <img src="https://wiifarmcoop.org/food2.png" class="d-block mx-lg-auto img-fluid" alt="Bootstrap Themes" width="700" height="500" loading="lazy">


      </div>
      <div class="col-lg-6">
        <h1 class="display-5 fw-bold lh-1 mb-3" style="color: #135D26;">Enough Talk!
            Let's Grow Food</h1>
        <p class="lead" style="color: black;">Africa’s agricultural revolution won’t be actualized by discussion panels or promises, only by execution. Wiifarm Cooperative Society is building the structures to transform land, create jobs, and feed our future..</p>
        <div class="d-grid gap-2 d-md-flex justify-content-md-start">
          <a type="button" href="{{route('user.plan.index')}}" class="btn  btn-sm btn-outline-success btn-lg px-4 me-md-2">Farm project</a>
          
        </div>
      </div>
    </div>
</div>

 --}}   
            
@endsection

@push('modal')
    <!-- Fund Moneybox Modal -->
<div class="modal fade" id="fundMoneyboxModal" tabindex="-1" aria-labelledby="fundMoneyboxModalLabel" aria-hidden="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-dialog modal-dialog-centered modal-lg p-4">
                
            <div class="modal-content shadow-lg">
                <div class="modal-header border-0 text-center">
                    <div class="w-100">
                        <img src="{{ asset($activeTemplateTrue . 'images/logo/Wordmark.png') }}" style="width:60px !important">

                        <h5 class="modal-title mb-2" id="fundMoneyboxModalLabel">Welcome to WiifarmVest</h5>
                        <p class="text-muted small mb-0">Your membership is now active</p>
                    </div>
                    <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body text-center py-4">
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-2">Next Step: Fund Your Moneybox</h6>
                        <p class="text-muted small mb-0">
                            Add funds to your digital money box. This secure wallet will hold your money for transactions.
                        </p>
                    </div>

                    <div class="alert alert-warning py-2 mb-4">
                        <small class="mb-0">

                            

                            <strong>Important:</strong>  You need a minimum of {{ showAmount(12500) }} to subcribe to a unit.
                        </small>
                    </div>
                </div>

                <div class="modal-footer border-0 flex-column gap-2">
                    <a href="{{ route('user.deposit.index1') }}" class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-wallet"></i>
                        Fund My Moneybox
                        <i class="bi bi-arrow-right"></i>
                    </a>
                    <button type="button" class="btn btn-link text-muted small" data-bs-dismiss="modal">
                        I'll do this later
                    </button>
                </div>
            </div>
        
            </div>
        </div>
    </div>
@endpush
       

@push('script')
    <script> 
        $( document ).ready(function() {
            let wallet = "{{ auth()->user()->balance }}";
            if(wallet < 1250 ) {
                $("#fundMoneyboxModal").modal('show');
            }
            
        }); 
    </script>
@endpush

@extends($activeTemplate . 'layouts.master2')
 
@section('content')
 @include($activeTemplate.'layouts.breadcrumb')
    
<div class="row row-cols-1 row-cols-lg-2 align-items-stretch g-4 ">
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
                 <span class="text-dark"><strong>Reserved Unit:</strong> </span> &nbsp;<span class="badge bg-secondary">{{ $rivest->units }}</span> 

                 <br>
                 <span class="text-dark"><strong>Unit cost:</strong> </span>&nbsp;<span class="badge bg-secondary">{{ showAmount($rivest->unit_cost) }}</span>
                 @if($rivest->status == 2)
                 <span class="badge bg-info float-right">Approved</span></p>
                 @else
                 <span class="badge bg-success float-right">Pending</span></p>
                 @endif
                </div>
              </li>
              
            </ul>
            <button type="button" class="btn btn-sm btn-secondary mt-2 w-100" data-bs-dismiss="modal">Download <svg width="22px" height="22px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path opacity="0.5" d="M3 15C3 17.8284 3 19.2426 3.87868 20.1213C4.75736 21 6.17157 21 9 21H15C17.8284 21 19.2426 21 20.1213 20.1213C21 19.2426 21 17.8284 21 15" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 3V16M12 16L16 11.625M12 16L8 11.625" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>

            </button>
            
          </div>
        </div>
      </div>

    </div>
    @endforeach

</div>




@endsection
@push('styles')

@endpush

@push('modal')
   

<svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
  
  <symbol id="check2-circle" viewBox="0 0 16 16">
    <path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0z"/>
    <path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l7-7z"/>
  </symbol>

</svg>
@endpush

@push('script')
   
@endpush

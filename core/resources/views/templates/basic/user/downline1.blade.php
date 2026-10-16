@extends($activeTemplate . 'layouts.master')

@push('style')
    <link href="{{asset('assets/global/css/tree.css')}}" rel="stylesheet">
@endpush
@section('content')
 @include($activeTemplate.'layouts.breadcrumb')

 
    <div class="container">
        <div class="row">
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
        </div>
    </div>
    <div class="card custom--card">
        <div class="row text-center justify-content-center llll">
            <!-- <div class="col"> --> 
              <p>  @if(returnUserDetailsr($matric->parent_id))
                  <strong>Parent : {{ returnUserDetailsr($matric->parent_id, 'username') }}</strong>
                @endif
            </p>

        </div>

        <div class="row text-center justify-content-center llll">
            <!-- <div class="col"> --> 

            <div class="w-1">
                <p> @if($tree['a']) {{ $tree['a']->username }} @endif </p>
                @php echo showSingleUserinTree_new($tree['a']); @endphp
            </div>
        </div>

        <div class="row text-center justify-content-center llll">
            <!-- <div class="col"> -->
            <div class="w-2">
                <p> @if($tree['b']) {{ $tree['b']->username }} @endif </p>
                @php echo showSingleUserinTree_new($tree['b']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-2 ">
                <p> @if($tree['c']) {{ $tree['c']->username }} @endif </p>
                @php echo showSingleUserinTree_new($tree['c']); @endphp
            </div>
        </div>
        <div class="row text-center justify-content-center llll">
            <!-- <div class="col"> -->
            <div class="w-4 ">
                <p> @if($tree['d']) {{ $tree['d']->username }} @endif </p>
                @php echo showSingleUserinTree_new($tree['d']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-4 ">
                <p> @if($tree['e']) {{ $tree['e']->username }} @endif </p>
                @php echo showSingleUserinTree_new($tree['e']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-4 ">
                <p> @if($tree['f']) {{ $tree['f']->username }} @endif </p>
                @php echo showSingleUserinTree_new($tree['f']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-4 ">
                <p> @if($tree['g']) {{ $tree['g']->username }} @endif </p>
                @php echo showSingleUserinTree_new($tree['g']); @endphp
            </div>
            <!-- <div class="col"> -->

        </div>
        
        
        <div class="row text-center justify-content-center llll">
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['h']) {{ $tree['h']->username }} @endif </p>
                @php echo showSingleUserinTree_new($tree['h']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['i']) {{ $tree['i']->username }} @endif </p>
                @php echo showSingleUserinTree_new($tree['i']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['j']) {{ $tree['j']->username }} @endif </p>
                @php echo showSingleUserinTree_new($tree['j']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['k']) {{ $tree['k']->username }} @endif </p>
                @php echo showSingleUserinTree_new($tree['k']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['l']) {{ $tree['l']->username }} @endif </p>
                @php echo showSingleUserinTree_new($tree['l']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['m']) {{ $tree['m']->username }} @endif </p>
                @php echo showSingleUserinTree_new($tree['m']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['n']) {{ $tree['n']->username }} @endif </p>
                @php echo showSingleUserinTree_new($tree['n']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['o']) {{ $tree['o']->username }} @endif </p>
                @php echo showSingleUserinTree_new($tree['o'], 9); @endphp
            </div>

        </div>
        
        
    </div>

    @endsection
@push('modal')
<div class="modal fade user-details-modal-area" id="exampleModalCenter" tabindex="-1" role="dialog"
     aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('User Details')</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="user-details-modal">
                    <div class="user-details-header ">
                        <div class="thumb"><img src="#" alt="*" class="tree_image w-h-100-p"
                            ></div>
                        <div class="content">
                            <a class="user-name tree_url tree_name" href=""></a>
                            <span class="user-status tree_status"></span>
                            <span class="user-status tree_plan"></span>
                        </div>
                    </div>
                    <div class="user-details-body text-center">

                        <h6 class="my-3">@lang('Referred By'): <span class="tree_ref"></span></h6>


                        

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endpush 
@push('script')
    <script>
        "use strict";
        (function ($) {
            $('.showDetails').on('click', function () {
                var modal = $('#exampleModalCenter');

                $('.tree_name').text($(this).data('name'));
                $('.tree_url').attr({"href": $(this).data('treeurl')});
                $('.tree_status').text($(this).data('status'));
                $('.tree_plan').text($(this).data('plan'));
                $('.tree_image').attr({"src": $(this).data('image')});
                $('.user-details-header').removeClass('Paid');
                $('.user-details-header').removeClass('Free');
                $('.user-details-header').addClass($(this).data('status'));
                $('.tree_ref').text($(this).data('refby'));
                
                $('#exampleModalCenter').modal('show');
            });
        })(jQuery);
    </script>

@endpush
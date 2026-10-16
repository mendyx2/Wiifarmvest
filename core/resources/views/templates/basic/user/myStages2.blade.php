@extends($activeTemplate.'layouts.master')

@push('style')
    <link href="{{asset('assets/global/css/tree.css')}}" rel="stylesheet">
@endpush

@section('content')
 @include($activeTemplate.'layouts.breadcrumb')
    <div class="row">
        @if(returnMatrixStageNumber(2))
        <div class="col-sm-4 col-md-4 col-xl-3 mb-30 mb-4">
            <div class="card custom--card">
                <div class="card-body">
                    <div class="text-center">
                        <a  href="{{ route('user.my.treeStage', 2) }}"  class="cmn--btn active" ><span>@lang('Stage Two')</span></a>
                                   
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if(returnMatrixStageNumber(3))
        <div class="col-sm-4 col-md-4 col-xl-4 mb-30 mb-4">
            <div class="card custom--card">
                <div class="card-body">
                    <div class="text-center">
                        <a  href="{{ route('user.my.treeStage', 3) }}"  class="cmn--btn active" ><span>@lang('Stage Three')</span></a>
                                   
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if(returnMatrixStageNumber(4))
        <div class="col-sm-4 col-md-4 col-xl-4 mb-30 mb-4">
            <div class="card custom--card">
                <div class="card-body">
                    <div class="text-center">
                        <a  href="{{ route('user.my.treeStage', 4) }}"  class="cmn--btn active" ><span>@lang('Stage Four')</span></a>
                                   
                    </div>
                </div>
            </div>
        </div>
        @endif
        @if(returnMatrixStageNumber(5))
        <div class="col-sm-4 col-md-3 col-xl-3 mb-30 mb-4">
            <div class="card custom--card">
                <div class="card-body">
                    <div class="text-center">
                        <a  href="{{ route('user.my.treeStage', 5) }}"  class="cmn--btn active" ><span>@lang('Stage Five')</span></a>
                                   
                    </div>
                </div>
            </div>
        </div>
        
    </div>
    @endif
    <div class="card custom--card">
        <div class="row text-center justify-content-center llll">
            <!-- <div class="col"> --> 
            <div class="w-1">
                @php echo showSingleUserinTree_new($tree['a']); @endphp
            </div>
        </div>
        <div class="row text-center justify-content-center llll">
            <!-- <div class="col"> -->
            <div class="w-2">
                @php echo showSingleUserinTree_new($tree['b']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-2 ">
                @php echo showSingleUserinTree_new($tree['c']); @endphp
            </div>
        </div>
        <div class="row text-center justify-content-center llll">
            <!-- <div class="col"> -->
            <div class="w-4 ">
                @php echo showSingleUserinTree_new($tree['d']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-4 ">
                @php echo showSingleUserinTree_new($tree['e']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-4 ">
                @php echo showSingleUserinTree_new($tree['f']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-4 ">
                @php echo showSingleUserinTree_new($tree['g']); @endphp
            </div>
            <!-- <div class="col"> -->

        </div>
        
       
        <div class="row text-center justify-content-center llll">
            <!-- <div class="col"> -->
            <div class="w-8">
                @php echo showSingleUserinTree_new($tree['h']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                @php echo showSingleUserinTree_new($tree['i']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                @php echo showSingleUserinTree_new($tree['j']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                @php echo showSingleUserinTree_new($tree['k']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                @php echo showSingleUserinTree_new($tree['l']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                @php echo showSingleUserinTree_new($tree['m']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                @php echo showSingleUserinTree_new($tree['n']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                @php echo showSingleUserinTree_new($tree['o']); @endphp
            </div>

        </div>
       
        
    </div>

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



@endsection

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
@push('breadcrumb-plugins')
    <form action="{{route('user.other.tree.search')}}" method="GET" class="form-inline float-right bg--white">
        <div class="input-group has_append">
            <input type="text" name="username" class="form-control form--control" placeholder="@lang('Search by username')">
            <button class="btn btn--success" type="submit"><i class="fa fa-search"></i></button>
        </div>
    </form>
@endpush




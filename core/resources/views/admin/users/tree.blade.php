@extends('admin.layouts.app')
 
@section('panel')
    <div class="card custom--card">
        <div class="row text-center justify-content-center llll">
            <!-- <div class="col"> --> 
             @if(parentInMatrix($tree['a']->id, 1))
                  
                      <strong> Parent: <a href="{{ route('admin.users.other.tree', parentInMatrix($tree['a']->id, 1)?->username) }}">
                          {{ parentInMatrix($tree['a']->id, 1)->username }} </a> </strong>
                          ==============
                    
                @endif  
               
               
           
            <br/>
            
            <div class="w-1">
                <p> @if($tree['a'])
                    <a href="{{ route('admin.users.detail', $tree['a']->id) }}">

                        {{ $tree['a']->username }} 
                    </a>
                    @endif  
                </p>
                @php echo showSingleUserinTree_new($tree['a']); @endphp
            </div>
        </div>
        <div class="row text-center justify-content-center llll">
            <!-- <div class="col"> -->
            <div class="w-2">
                <p> @if($tree['b']) <a href="{{ route('admin.users.detail', $tree['b']->id) }}"> {{ $tree['b']->username }} </a> @endif </p>
                @php echo showSingleUserinTree_new($tree['b']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-2 ">
                <p> @if($tree['c']) <a href="{{ route('admin.users.detail', $tree['c']->id) }}"> {{ $tree['c']->username }} </a>@endif </p>
                @php echo showSingleUserinTree_new($tree['c']); @endphp
            </div>
        </div>
        <div class="row text-center justify-content-center llll">
            <!-- <div class="col"> -->
            <div class="w-4 ">
                <p> @if($tree['d']) <a href="{{ route('admin.users.detail', $tree['d']->id) }}"> {{ $tree['d']->username }} </a>  @endif </p>
                @php echo showSingleUserinTree_new($tree['d']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-4 ">
                <p> @if($tree['e']) <a href="{{ route('admin.users.detail', $tree['e']->id) }}"> {{ $tree['e']->username }} </a> @endif </p>
                @php echo showSingleUserinTree_new($tree['e']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-4 ">
                <p> @if($tree['f']) <a href="{{ route('admin.users.detail', $tree['f']->id) }}"> {{ $tree['f']->username }} </a>@endif </p>
                @php echo showSingleUserinTree_new($tree['f']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-4 ">
                <p> @if($tree['g']) <a href="{{ route('admin.users.detail', $tree['g']->id) }}"> {{ $tree['g']->username }} </a>@endif </p>
                @php echo showSingleUserinTree_new($tree['g']); @endphp
            </div>
            <!-- <div class="col"> -->

        </div>
        
        
        <div class="row text-center justify-content-center llll">
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['h']) <a href="{{ route('admin.users.detail', $tree['h']->id) }}"> {{ $tree['h']->username }}  </a>@endif </p>
                @php echo showSingleUserinTree_new($tree['h']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['i']) <a href="{{ route('admin.users.detail', $tree['i']->id) }}"> {{ $tree['i']->username }}  </a>@endif </p>
                @php echo showSingleUserinTree_new($tree['i']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['j']) <a href="{{ route('admin.users.detail', $tree['j']->id) }}"> {{ $tree['j']->username }}  </a> @endif </p>
                @php echo showSingleUserinTree_new($tree['j']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['k']) <a href="{{ route('admin.users.detail', $tree['k']->id) }}">  {{ $tree['k']->username }}  </a>@endif </p>
                @php echo showSingleUserinTree_new($tree['k']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['l']) <a href="{{ route('admin.users.detail', $tree['l']->id) }}">  {{ $tree['l']->username }}  </a> @endif </p>
                @php echo showSingleUserinTree_new($tree['l']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['m']) <a href="{{ route('admin.users.detail', $tree['m']->id) }}">  {{ $tree['m']->username }}  </a>@endif </p>
                @php echo showSingleUserinTree_new($tree['m']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['n']) <a href="{{ route('admin.users.detail', $tree['n']->id) }}">  {{ $tree['n']->username }}  </a> @endif </p>
                @php echo showSingleUserinTree_new($tree['n']); @endphp
            </div>
            <!-- <div class="col"> -->
            <div class="w-8">
                <p> @if($tree['o']) <a href="{{ route('admin.users.detail', $tree['o']->id) }}"> {{ $tree['o']->username }}  </a> @endif </p>
                @php echo showSingleUserinTree_new($tree['o'], 9); @endphp
            </div>

        </div>
        
        
    </div>

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
    <form class="form-inline bg--white float-right" action="{{ route('admin.users.other.tree.search') }}" method="GET">
        <div class="input-group flex-fill w-auto">
            <input class="form-control" name="username" type="text" placeholder="@lang('Search by username')">
            <button class="btn btn--primary" type="submit"><i class="fa fa-search"></i></button>
        </div>
    </form>
@endpush

@push('style')
    <link href="{{ asset('assets/global/css/tree.css') }}" rel="stylesheet">
@endpush

<section class="user-dashboard  padding-bottom">
    <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <div class="dashboard-sidebar">
                    <div class="close-dashboard d-lg-none">
                        <i class="las la-times"></i>
                    </div>
                    <div class="dashboard-user">
                        <div class="user-thumb">
                            <img  id="output" src="{{ getImage('assets/images/user/profile/'. auth()->user()->image, '350x300',true)}}" alt="dashboard">
                        </div>
                        <div class="user-content">
                            <span>@lang('Welcome')</span>
                            <h5 class="name">{{ auth()->user()->fullname }}</h5>
                        </div>
                    </div>
                    <ul class="user-dashboard-tab">
                        
                         <li>
                            <a class="{{menuActive('user.home')}}" href="{{route('user.home')}}">
                            <svg class="bi pe-none me-2" width="16" height="16"><use xlink:href="#speedometer2"/></svg>@lang('User-Page')</a>

                        </li>
                        <li>
                            <a href="{{ route('user.stockist.dashboard') }}" class="{{menuActive('user.stockist.dashboard')}}">
                                     <svg class="bi pe-none me-2" width="16" height="16"><use xlink:href="#chat-quote-fill"/></svg>
                                    @lang('Stockist Dashboard')
                                </a>
                        </li>
                        <li>
                            <a class="{{menuActive('user.stockist.profile')}}" href="{{route('user.stockist.profile')}}"> 
                            <svg class="bi pe-none me-2" width="16" height="16"><use xlink:href="#grid"/></svg>
                                @lang('Profile') </a>
                        </li>
                        <li>
                            <a class="{{menuActive('user.stockist.history')}}" href="{{ route('user.stockist.history') }}">
                                <i class="fas fa-history me-2"></i>Redemption History
                            </a>
                        </li>
                         
                        <li>
                            <a class="{{menuActive('user.stockist.inventory.dashboard')}}" href="{{route('user.stockist.inventory.dashboard')}}"> 
                            <i class="fas fa-boxes me-2 text-dark"></i>
                                @lang('Inventory') </a>
                        </li>
                         
                        {{-- A Mega orders from the company; a Mini's orders go to a Mega stockist --}}
                        @php $navMega = (bool) auth()->user()->stockist?->isMega(); @endphp
                        <li>
                            <a href="{{ $navMega ? route('user.stockist.inventory.orders') : route('user.stockist.transfers.index') }}" class="{{ menuActive($navMega ? 'user.stockist.inventory.orders' : 'user.stockist.transfers.*') }}">
                                <i class="fas fa-shopping-cart me-2"></i>My Orders
                            </a>
                        </li>


                        
                        
                    </ul>

                    <ul class="user-dashboard-tab">
                        <li>
                            <a class="collapsed" data-bs-toggle="collapse" data-bs-target="#account-collapse" aria-expanded="false">
                                <strong>
                                 <i class="fas fa-shopping-cart me-2"></i>
                                    Shop
                                </strong>
                            </a> 
                            <div class="collapse user-dashboard-tab" id="account-collapse">
                            <ul class="user-dashboard-tab btn-toggle-nav">
                                
                               <li>
                                    <a class="
                                    @if(Route::is('user.products') )
                                    {{menuActive('user.products')}}
                                    @else
                                    {{menuActive('user.product*')}}
                                    @endif
                                    " href="{{ route('user.products') }}">@lang('Product')</a>
                                </li> 
                                <li>
                                    <a href="{{ route('user.orders.index') }}" class="{{menuActive('user.orders.index')}}">
                                        @lang('Orders')
                                    </a>
                                </li>  
                            </ul>
                            </div>
                        </li>
                    </ul> 
                    <ul class="user-dashboard-tab">
                        <li> 
                            <a href="{{ route('user.logout') }}" class="">
                            <svg class="bi pe-none me-2" width="16" height="16"><use xlink:href="#people-circle"/></svg>@lang('Sign Out')</a>
                        </li>
                        
                    </ul>
                </div>
            </div>
            <div class="col-lg-9">

                <div class="user-toggler-wrapper d-flex d-lg-none">
                    <h4 class="title">{{ __($pageTitle) }}</h4>
                    <div class="user-toggler">
                        <i class="las la-sliders-h"></i>
                    </div>
                </div>


                @yield('content')
            </div>
        </div>
    </div>
</section>
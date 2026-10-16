<section class="user-dashboard padding-top padding-bottom">
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
                            <a class="{{menuActive('user.welcome')}}" href="{{route('user.welcome')}}">@lang('Welcome Page')</a>
                        </li>
                        <li>
                            <a class="{{menuActive('user.land')}}" href="{{route('user.land')}}">@lang('Fractional Land Ownership')</a>
                        </li>
                        <li>
                            <a class="{{menuActive('user.my.ref')}}" href="{{ route('user.my.ref') }}"> 
                                <svg class="bi pe-none me-2" width="16" height="16"><use xlink:href="#table"/></svg>
                            @lang('My Referrals')</a>
                        </li>
                        <li>
                            <a class="{{menuActive('user.home')}}" href="{{route('user.home')}}">@lang('Dasboard')</a>
                        </li>
                        <li>
                            <a href="{{ route('user.deposit.index') }}" class="{{menuActive(['user.deposit.index'])}}">
                                @lang('Deposits ')
                            </a>
                        </li>
                        {{--
                        <li>
                            <a href="{{ route('user.deposit.history') }}" class="{{menuActive(['user.deposit*'])}}">
                                @lang('Deposits history ')
                            </a>
                        </li>
                        --}}
                        <li>
                            <a class="{{menuActive('user.my.ref')}}" href="{{ route('user.my.ref') }}"> @lang('My Referrals')</a>
                        </li>
                        {{-- 
                            <li>
                                <a class="{{menuActive('user.epin.recharge')}}" href="{{ route('user.epin.recharge') }}">@lang('E-Pin Recharge')</a>
                            </li>
                            <li>
                                <a class="{{menuActive('user.recharge.log')}}" href="{{ route('user.recharge.log') }}">@lang('Recharge Log')</a>
                            </li>
                       --}}

                        <li>
                            <a class="{{menuActive('user.my.tree')}}" href="{{ route('user.my.tree') }}">@lang('Genealogy ')</a>
                        </li>
                        <li>
                            <a class="{{menuActive('user.my.stages')}}" href="{{ route('user.my.stages') }}">@lang('User Stages ')</a>
                        </li>
                        
                        <li>
                            <a class="
                            @if(Route::is('products') )
                            {{menuActive('products')}}
                            @else
                            {{menuActive('product*')}}
                            @endif
                            " href="{{ route('products') }}">@lang('Product')</a>
                        </li>
                       
                        
                        <li>
                            <a href="{{ route('user.orders') }}" class="{{menuActive('user.orders')}}">
                                @lang('Orders')
                            </a>
                        </li>
                        
                        
                        <li>
                            <a href="{{ route('user.withdraw.history') }}" class="{{menuActive('user.withdraw*')}}">
                                @lang('Withdraws ')
                            </a>
                        </li>
                       
                        <li>
                            <a href="{{ route('user.transactions') }}" class="{{menuActive('user.transactions')}}">
                                @lang('Transactions ')
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('ticket.index') }}" class="{{menuActive('ticket*')}}">
                                @lang('Support Ticket')
                            </a>
                        </li>
                        
                        <li>
                            <a class="{{menuActive('user.profile.setting')}}" href="{{route('user.profile.setting')}}" class="">@lang('Profile Setting')</a>
                        </li>
                        <li>
                            <a href="{{ route('user.twofactor') }}" class="{{menuActive('user.twofactor')}}">
                                @lang('2FA Security')
                            </a>
                        </li>
                       
                        <li>
                            <a class="{{menuActive('user.change.password')}}" href="{{route('user.change.password')}}" class="">@lang('Change Password')</a>
                        </li>
                        <li>
                            <a href="{{ route('user.logout') }}" class="">@lang('Sign Out')</a>
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
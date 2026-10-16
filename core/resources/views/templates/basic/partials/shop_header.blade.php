@php
    $shopHeaderCartCount   = session('shop_cart') ? array_sum(session('shop_cart')) : 0;
    $shopHeaderCategories  = \App\Models\Category::active()->hasActiveProduct()->get();
@endphp

<header class="shophd" id="shopHeader">
    <div class="shophd-bar">
        <div class="shophd-container">
            <a href="{{ route('shop.index') }}" class="shophd-logo">
                <img src="{{ siteLogo('dark') }}" alt="{{ gs('site_name') }}">
            </a>

            <form action="{{ route('shop.index') }}" method="GET" class="shophd-search">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search products, e.g. Rice, Beans...">
                <button type="submit" aria-label="Search"><i class="las la-search"></i></button>
            </form>

            <div class="shophd-actions">
                <div class="dropdown shophd-account">
                    <button class="shophd-icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="las la-user-circle"></i>
                        <span class="shophd-icon-label">
                            @if(auth()->check())
                                Hi, {{ Str::limit(auth()->user()->firstname, 10) }}
                            @else
                                Account
                            @endif
                        </span>
                        <i class="las la-angle-down shophd-caret"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shophd-dropdown">
                        @if(auth()->check())
                            <div class="shophd-dropdown-head">Signed in as<br><strong>{{ auth()->user()->fullname }}</strong></div>
                            <a class="dropdown-item" href="{{ route('user.home') }}"><i class="las la-tachometer-alt"></i> My Dashboard</a>
                            <a class="dropdown-item" href="{{ route('user.affiliate.dashboard') }}"><i class="las la-link"></i> Affiliate Program</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('user.logout') }}"><i class="las la-sign-out-alt"></i> Sign Out</a>
                        @else
                            <div class="shophd-dropdown-head">Welcome</div>
                            <a class="dropdown-item" href="{{ route('user.login') }}"><i class="las la-sign-in-alt"></i> Login</a>
                            <a class="dropdown-item shophd-dropdown-cta" href="{{ route('user.register') }}"><i class="las la-user-plus"></i> Create Account</a>
                        @endif
                    </div>
                </div>

                <a href="{{ route('shop.cart') }}" class="shophd-icon-btn shophd-cart-btn">
                    <span class="shophd-cart-icon-wrap">
                        <i class="las la-shopping-cart"></i>
                        <span class="shophd-cart-badge" id="shopHeaderCartCount">{{ $shopHeaderCartCount }}</span>
                    </span>
                    <span class="shophd-icon-label">Cart</span>
                </a>
            </div>
        </div>
    </div>

    @if($shopHeaderCategories->count())
    <div class="shophd-cats">
        <div class="shophd-container shophd-cats-inner">
            <a href="{{ route('shop.index') }}" class="shophd-cat-link {{ !request('category') ? 'active' : '' }}">All Products</a>
            @foreach($shopHeaderCategories as $cat)
                <a href="{{ route('shop.index', ['category' => $cat->id]) }}" class="shophd-cat-link {{ request('category') == $cat->id ? 'active' : '' }}">{{ $cat->name }}</a>
            @endforeach
        </div>
    </div>
    @endif
</header>

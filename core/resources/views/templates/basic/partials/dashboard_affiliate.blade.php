@php
    $unreadNotifCount = \App\Models\NotificationLog::where('user_id', auth()->id())
        ->where('user_read', false)
        ->count();
        $inStockistSection = request()->routeIs('user.stockist.*');
        //$inAffiliateSection = request()->routeIs('user.affiliate.*');
        //die($inAffiliateSection);
@endphp


<section class="bank-dashboard">
    <div class="bank-layout">

        {{-- ===== SIDEBAR ===== --}}
        <aside class="bank-sidebar" id="bankSidebar">
            <div class="bank-sidebar-inner">

                {{-- Logo --}}
                <div class="bank-logo">
                    <a href="{{ route('user.home') }}">
                        <img src="{{ siteLogo('dark') }}" alt="logo" class="bank-logo-img">
                    </a>
                    <button class="bank-sidebar-close d-lg-none" id="sidebarClose">
                        <i class="las la-times"></i>
                    </button>
                </div>

                {{-- Profile --}}
                <div class="bank-profile">
                    <div class="bank-avatar">
                        {{ strtoupper(substr(auth()->user()->firstname, 0, 1)) }}{{ strtoupper(substr(auth()->user()->lastname, 0, 1)) }}
                    </div>
                    <div class="bank-profile-info">
                        <p class="bank-profile-name">{{ auth()->user()->fullname }}</p>
                        @php $userIDcard = retrunUserIDcard(auth()->id()); @endphp
                        <p class="bank-profile-id">
                            @if($userIDcard) WCS{{ $userIDcard->id }} @else @ {{ auth()->user()->username }} @endif
                        </p>
                    </div>
                </div>

                {{-- Navigation --}}
                <nav class="bank-nav">

                    {{-- ── Detect which group is currently active ── --}}
                    @php
                        $grpSavings = request()->routeIs(['user.savings*', 'user.loans*']);
                        $grpFinance = request()->routeIs(['user.deposit*', 'user.withdraw*', 'user.transactions', 'user.bonus.transfer*']);
                        $grpTree    = request()->routeIs(['user.my.tree', 'user.my.stages', 'user.binary*', 'user.pv.log']);
                        $grpAwards  = request()->routeIs(['user.awards', 'user.repurchase.award', 'user.acb']);
                        $grpProject = request()->routeIs(['user.project.*']);
                        $grpShop    = request()->routeIs(['product*', 'user.orders*']);
                        $grpAccount = request()->routeIs(['user.notifications', 'user.profile*', 'user.kyc*', 'user.guarantor*', 'user.twofactor']);
                        $pendingAwardCount = \App\Models\UserAward::where('user_id', auth()->id())->where('status', 0)->count();
                        $pendingGuarantorCount = \App\Models\GuarantorRequest::where('guarantor_id', auth()->id())->where('status', 'pending')->count();
                        $kv     = auth()->user()->kv;
                        $kycUrl = ($kv == \App\Constants\Status::KYC_UNVERIFIED && !auth()->user()->kyc_data && !auth()->user()->nin)
                            ? route('user.kyc.form') : route('user.kyc.data');
                    @endphp

                    {{-- ── MAIN (flat, no dropdown) ─────────────── --}}
                        {{-- ── AFFILIATE-ONLY MENU ──────────────────── --}}
                        <ul>
                            <li>
                                <a href="{{ route('user.home') }}" class="bank-nav-link">
                                    <span class="bank-nav-icon"><i class="las la-home"></i></span>
                                    <span>Back to Main Menu</span>
                                </a>
                            </li>
                        </ul>
                        <p class="bank-nav-label">Affiliate</p>
                        <ul>
                            <li>
                                <a href="{{ route('user.affiliate.dashboard') }}" class="bank-nav-link {{ menuActive('user.affiliate.dashboard') }}">
                                    <span class="bank-nav-icon"><i class="las la-tachometer-alt"></i></span>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.affiliate.orders') }}" class="bank-nav-link {{ menuActive('user.affiliate.orders') }}">
                                    <span class="bank-nav-icon"><i class="las la-box"></i></span>
                                    <span>My Orders</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.affiliate.ads') }}" class="bank-nav-link {{ menuActive('user.affiliate.ads*') }}">
                                    <span class="bank-nav-icon"><i class="las la-bullhorn"></i></span>
                                    <span>Ad Center</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.bonus.transfer.index') }}" class="bank-nav-link {{ menuActive('user.bonus.transfer.*') }}">
                                    <span class="bank-nav-icon"><i class="las la-exchange-alt"></i></span>
                                    <span>Bonus Transfer</span>
                                </a>
                            </li>
                        </ul>
                        <p class="bank-nav-label">Account</p>
                        <ul>
                            <li>
                                <a href="{{ route('user.logout') }}" class="bank-nav-link bank-nav-logout">
                                    <span class="bank-nav-icon"><i class="las la-sign-out-alt"></i></span>
                                    <span>Sign Out</span>
                                </a>
                            </li>
                        </ul>
                    
                    
                    
                   
                    

                    {{-- ── ACCOUNT ──────────────────────────────── --}}
                    <div class="bk-nav-group {{ $grpAccount ? 'open' : '' }}">
                        <button class="bk-nav-group-toggle" type="button">
                            <span class="bank-nav-icon"><i class="las la-user-circle"></i></span>
                            <span>Account</span>
                            @if($unreadNotifCount > 0)
                                <span class="bank-nav-badge bank-nav-badge-danger">{{ $unreadNotifCount > 9 ? '9+' : $unreadNotifCount }}</span>
                            @endif
                            <i class="las la-angle-right bk-nav-chevron"></i>
                        </button>
                        <ul class="bk-nav-group-body">
                            <li>
                                <a href="{{ route('user.notifications') }}" class="bank-nav-link {{ menuActive('user.notifications') }}">
                                    <span class="bank-nav-icon"><i class="las la-bell"></i></span>
                                    <span>Notifications</span>
                                    @if($unreadNotifCount > 0)
                                        <span class="bank-nav-badge bank-nav-badge-danger">{{ $unreadNotifCount > 9 ? '9+' : $unreadNotifCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.profile.setting') }}" class="bank-nav-link {{ menuActive('user.profile.setting') }}">
                                    <span class="bank-nav-icon"><i class="las la-user-cog"></i></span>
                                    <span>Profile</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.change.password') }}" class="bank-nav-link {{ menuActive('user.change.password') }}">
                                    <span class="bank-nav-icon"><i class="las la-user-cog"></i></span>
                                    <span>Change password</span>
                                </a>
                            </li> 
                            <li>
                                <a href="{{ $kycUrl }}" class="bank-nav-link {{ menuActive(['user.kyc.form','user.kyc.data']) }}">
                                    <span class="bank-nav-icon"><i class="las la-id-card"></i></span>
                                    <span>KYC Verification</span>
                                    @if($kv == \App\Constants\Status::KYC_VERIFIED)
                                        <span class="bank-nav-badge" style="background:#D1FAE5;color:#059669;font-size:.65rem;padding:2px 7px;border-radius:20px;font-weight:700;margin-left:auto;">Verified</span>
                                    @elseif($kv == \App\Constants\Status::KYC_PENDING)
                                        <span class="bank-nav-badge" style="background:#FFFBEB;color:#D97706;font-size:.65rem;padding:2px 7px;border-radius:20px;font-weight:700;margin-left:auto;">Pending</span>
                                    @else
                                        <span class="bank-nav-badge bank-nav-badge-danger">!</span>
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.guarantor.requests') }}" class="bank-nav-link {{ menuActive('user.guarantor.requests') }}">
                                    <span class="bank-nav-icon"><i class="las la-handshake"></i></span>
                                    <span>Guarantor Requests</span>
                                    @if($pendingGuarantorCount > 0)
                                        <span class="bank-nav-badge bank-nav-badge-danger">{{ $pendingGuarantorCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.twofactor') }}" class="bank-nav-link {{ menuActive('user.twofactor') }}">
                                    <span class="bank-nav-icon"><i class="las la-shield-alt"></i></span>
                                    <span>2FA Security</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.logout') }}" class="bank-nav-link bank-nav-logout">
                                    <span class="bank-nav-icon"><i class="las la-sign-out-alt"></i></span>
                                    <span>Sign Out</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                   

                </nav>
            </div>
        </aside>
        {{-- Overlay for mobile --}}
        <div class="bank-overlay d-lg-none" id="sidebarOverlay"></div>
        {{-- ===== MAIN CONTENT ===== --}}
        <main class="bank-main">
            {{-- Top Bar --}}
            <div class="bank-topbar">
                <div class="bank-topbar-left">
                    <button class="bank-menu-toggle d-lg-none" id="sidebarToggle">
                        <i class="las la-bars"></i>
                    </button>
                    <div class="bank-topbar-greeting d-none d-md-block">
                        <h6 class="mb-0">@php
                            $hour = date('H');
                            echo $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
                        @endphp, <strong>{{ auth()->user()->firstname }}</strong></h6>
                        <p class="mb-0 text-muted small">{{ now()->format('l, F j, Y') }}</p>
                    </div>
                </div>
                @php
                    $cart      = \App\Models\Cart::where('user_id', auth()->id())->first();
                    $cartCount = $cart ? $cart->items()->sum('quantity') : 0;
                @endphp
                <div class="bank-topbar-right">
                    <a href="{{ route('user.deposit.index') }}" class="bank-topbar-btn">
                        <i class="las la-plus"></i>
                        <span class="d-none d-sm-inline">Fund Account</span>
                    </a>
                    <a href="{{ route('user.cart.index') }}" class="bank-topbar-icon bank-cart-icon" title="Cart">
                        <i class="las la-shopping-cart"></i>
                        <span class="bank-cart-badge {{ $cartCount > 0 ? 'bank-cart-badge-active' : '' }}">
                            {{ $cartCount }}
                        </span>
                    </a>
                    <a href="{{ route('user.notifications') }}" class="bank-topbar-icon bank-notif-icon" title="Notifications">
                        <i class="las la-bell"></i>
                        <span class="bank-notif-badge {{ $unreadNotifCount > 0 ? 'bank-notif-badge-active' : '' }}">
                            {{ $unreadNotifCount > 99 ? '99+' : $unreadNotifCount }}
                        </span>
                    </a>
                    <div class="bank-avatar-dropdown" id="avatarDropdown">
                        <button class="bank-topbar-avatar" id="avatarToggle" aria-expanded="false" aria-haspopup="true">
                            {{ strtoupper(substr(auth()->user()->firstname, 0, 1)) }}
                        </button>
                        <div class="bank-avatar-menu" id="avatarMenu" role="menu">
                            <div class="bank-avatar-menu-header">
                                <p class="bank-avatar-menu-name">{{ auth()->user()->fullname }}</p>
                                <p class="bank-avatar-menu-email">{{ auth()->user()->email }}</p>
                            </div>
                            <a href="{{ route('user.profile.setting') }}" class="bank-avatar-menu-item" role="menuitem">
                                <i class="las la-user-cog"></i> Profile Settings
                            </a>
                            <a href="{{ route('user.change.password') }}" class="bank-avatar-menu-item" role="menuitem">
                                <i class="las la-user-cog"></i> Change.password
                            </a>
                            
                            <a href="{{ route('user.twofactor') }}" class="bank-avatar-menu-item" role="menuitem">
                                <i class="las la-shield-alt"></i> 2FA Security
                            </a>
                            <div class="bank-avatar-menu-divider"></div>
                            <a href="{{ route('user.logout') }}" class="bank-avatar-menu-item bank-avatar-menu-logout" role="menuitem">
                                <i class="las la-sign-out-alt"></i> Sign Out
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Page Content --}}
            <div class="bank-content">
                @yield('content')
            </div>
        </main>
    </div>
</section>

@include($activeTemplate . 'partials.dashboard_script')

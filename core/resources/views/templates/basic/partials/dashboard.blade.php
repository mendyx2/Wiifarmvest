@php
    $unreadNotifCount = \App\Models\NotificationLog::where('user_id', auth()->id())
        ->where('user_read', false)
        ->count();
        // layouts.master sets this; layouts.master2 (the products1 / orders1 pages) doesn't
        $inStockistSection ??= request()->routeIs('user.stockist.*');
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
                    <p class="bank-nav-label">Main</p>
                    @if(!$inStockistSection)
                    <ul>
                        <li>
                            <a href="{{ route('user.home') }}" class="bank-nav-link {{ menuActive('user.home') }}">
                                <span class="bank-nav-icon"><i class="las la-home"></i></span>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('user.investment.portfolio') }}" class="bank-nav-link {{ menuActive('user.investment.portfolio') }}">
                                <span class="bank-nav-icon"><i class="las la-chart-line"></i></span>
                                <span>Investments</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('user.my.ref') }}" class="bank-nav-link {{ menuActive('user.my.ref') }}">
                                <span class="bank-nav-icon"><i class="las la-users"></i></span>
                                <span>My Referrals</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('user.affiliate.dashboard') }}" class="bank-nav-link {{ menuActive('user.affiliate.dashboard') }}">
                                <span class="bank-nav-icon"><i class="las la-link"></i></span>
                                <span>Affiliate Program</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('user.project.index') }}" class="bank-nav-link {{ menuActive('user.project.*') }}">
                                <span class="bank-nav-icon"><i class="las la-project-diagram"></i></span>
                                <span>My Project</span>
                                @php $userProject = auth()->user()->project; @endphp
                                @if($userProject)
                                    <span class="bank-nav-badge" style="background:{{ $userProject->color }}1a;color:{{ $userProject->color }};font-size:.6rem;padding:2px 7px;border-radius:20px;font-weight:800;margin-left:auto;max-width:70px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $userProject->title }}">{{ Str::limit($userProject->title, 8) }}</span>
                                @endif
                            </a>
                        </li>

                        @if(auth()->user()->section == 1)
                       
                        {{-- ── USER TREE (section == 1 only) ─────────── --}}
                    
                        <div class="bk-nav-group {{ $grpTree ? 'open' : '' }}">
                            <button class="bk-nav-group-toggle" type="button">
                                <span class="bank-nav-icon"><i class="las la-sitemap"></i></span>
                                <span>User Tree</span>
                                <i class="las la-angle-right bk-nav-chevron"></i>
                            </button>
                            <ul class="bk-nav-group-body">

                                <li>
                                    <a href="{{ route('user.distributor.index') }}" class="bank-nav-link {{ menuActive('user.distributor*') }}">
                                        <span class="bank-nav-icon"><i class="las la-user-plus"></i></span>
                                        <span>Add Distributor</span>
                                    </a>
                                </li>
                            
                                <li>
                                    <a href="{{ route('user.my.tree') }}" class="bank-nav-link {{ menuActive('user.my.tree') }}">
                                        <span class="bank-nav-icon"><i class="las la-sitemap"></i></span>
                                        <span>Genealogy</span>
                                    </a>
                                </li>
                                {{--
                                <li>
                                    <a href="{{ route('user.my.stages') }}" class="bank-nav-link {{ menuActive('user.my.stages') }}">
                                        <span class="bank-nav-icon"><i class="las la-layer-group"></i></span>
                                        <span>User Stages</span>
                                    </a>
                                </li>
                                --}}
                                <li>
                                    <a href="{{ route('user.binary.list') }}" class="bank-nav-link {{ menuActive('user.binary.list') }}">
                                        <span class="bank-nav-icon"><i class="las la-list-ul"></i></span>
                                        <span>Binary List</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('user.pv.log') }}" class="bank-nav-link {{ menuActive('user.pv.log') }}">
                                        <span class="bank-nav-icon"><i class="las la-list-alt"></i></span>
                                        <span>PV Log</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    
                        @endif
                        @if(returnStockist(auth()->id()))
                        <li>
                            <a href="{{ route('user.stockist.dashboard') }}" class="bank-nav-link {{ menuActive('user.stockist.dashboard') }}">
                                <span class="bank-nav-icon"><i class="las la-store-alt"></i></span>
                                <span>Stockist Dashboard</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                    @endif

                   
                    
                    {{-- ── SHOP ─────────────────────────────────── --}}
                    <div class="bk-nav-group {{ $grpShop ? 'open' : '' }}">
                        <button class="bk-nav-group-toggle" type="button">
                            <span class="bank-nav-icon"><i class="las la-shopping-bag"></i></span>
                            <span>Shop</span>
                            <i class="las la-angle-right bk-nav-chevron"></i>
                        </button>
                        <ul class="bk-nav-group-body">
                            <li>
                                <a href="{{ route('products') }}" class="bank-nav-link {{ menuActive('product*') }}">
                                    <span class="bank-nav-icon"><i class="las la-store"></i></span>
                                    <span>Browse Shop</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.orders.index') }}" class="bank-nav-link {{ menuActive('user.orders*') }}">
                                    <span class="bank-nav-icon"><i class="las la-box"></i></span>
                                    <span>Orders</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.stockist.find') }}" class="bank-nav-link {{ menuActive('user.stockist.fin') }}">
                                    <span class="bank-nav-icon"><i class="las la-map-marker"></i></span>
                                    <span>Find Stockist</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    

                    {{-- ── FINANCE ──────────────────────────────── --}}
                    <div class="bk-nav-group {{ $grpFinance ? 'open' : '' }}">
                        <button class="bk-nav-group-toggle" type="button">
                            <span class="bank-nav-icon"><i class="las la-wallet"></i></span>
                            <span>Finance</span>
                            <i class="las la-angle-right bk-nav-chevron"></i>
                        </button>
                        <ul class="bk-nav-group-body">
                            <li>
                                <a href="{{ route('user.deposit.index') }}" class="bank-nav-link {{ menuActive(['user.deposit.index']) }}">
                                    <span class="bank-nav-icon"><i class="las la-arrow-circle-down"></i></span>
                                    <span>Deposit</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.withdraw.history') }}" class="bank-nav-link {{ menuActive('user.withdraw*') }}">
                                    <span class="bank-nav-icon"><i class="las la-arrow-circle-up"></i></span>
                                    <span>Withdraw</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.transactions') }}" class="bank-nav-link {{ menuActive('user.transactions') }}">
                                    <span class="bank-nav-icon"><i class="las la-exchange-alt"></i></span>
                                    <span>Transactions</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.deposit.history') }}" class="bank-nav-link {{ menuActive(['user.deposit*']) }}">
                                    <span class="bank-nav-icon"><i class="las la-history"></i></span>
                                    <span>Deposit History</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.bonus.transfer.index') }}" class="bank-nav-link {{ menuActive('user.bonus.transfer*') }}">
                                    <span class="bank-nav-icon"><i class="las la-random"></i></span>
                                    <span>Bonus Transfer</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    
                    {{--  ACHIEVEMENTS (section == 1 only)  --}}
                    @if(auth()->user()->section == 1)
                    <div class="bk-nav-group {{ $grpAwards ? 'open' : '' }}">
                        <button class="bk-nav-group-toggle" type="button">
                            <span class="bank-nav-icon"><i class="las la-trophy"></i></span>
                            <span>Achievements</span>
                            @if($pendingAwardCount > 0)
                                <span class="bank-nav-badge" style="background:#fef3c7;color:#92400e;font-size:.6rem;padding:2px 7px;border-radius:20px;font-weight:800;">{{ $pendingAwardCount }}</span>
                            @endif
                            <i class="las la-angle-right bk-nav-chevron"></i>
                        </button>
                        <ul class="bk-nav-group-body">
                            <li>
                                <a href="{{ route('user.awards') }}" class="bank-nav-link {{ menuActive('user.awards') }}">
                                    <span class="bank-nav-icon"><i class="las la-trophy"></i></span>
                                    <span>My Awards</span>
                                    @if($pendingAwardCount > 0)
                                        <span class="bank-nav-badge" style="background:#fef3c7;color:#92400e;font-size:.65rem;padding:2px 7px;border-radius:20px;font-weight:800;margin-left:auto;">{{ $pendingAwardCount }}</span>
                                    @endif
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.repurchase.award') }}" class="bank-nav-link {{ menuActive('user.repurchase.award') }}">
                                    <span class="bank-nav-icon"><i class="las la-medal"></i></span>
                                    <span>Repurchase Award</span>
                                </a>
                            </li>
                            @if(auth()->user()->isAcb())
                            <li>
                                <a href="{{ route('user.acb') }}" class="bank-nav-link {{ menuActive('user.acb') }}">
                                    <span class="bank-nav-icon"><i class="las la-star"></i></span>
                                    <span>ACB Bonus</span>
                                    @if((float)auth()->user()->acb > 0)
                                        <span class="bank-nav-badge" style="background:#fef9c3;color:#854d0e;font-size:.6rem;padding:2px 7px;border-radius:20px;font-weight:800;margin-left:auto;">{{ showAmount(auth()->user()->acb) }}</span>
                                    @endif
                                </a>
                            </li>
                            @endif
                        </ul>
                    </div>
                    @endif

                    

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

<style>
.bank-cart-icon { position: relative; }
.bank-cart-badge {
    position: absolute;
    top: -5px;
    right: -6px;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    line-height: 18px;
    text-align: center;
    background: #D1D5DB;
    color: #6B7280;
    border: 2px solid #fff;
    pointer-events: none;
}
.bank-cart-badge-active {
    background: #DC2626;
    color: #fff;
}

/* ── Notification bell badge ── */
.bank-notif-icon { position: relative; }
.bank-notif-badge {
    position: absolute;
    top: -5px;
    right: -6px;
    min-width: 18px;
    height: 18px;
    padding: 0 4px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    line-height: 18px;
    text-align: center;
    background: #D1D5DB;
    color: #6B7280;
    border: 2px solid #fff;
    pointer-events: none;
}
.bank-notif-badge-active {
    background: #DC2626;
    color: #fff;
}

/* ── Avatar dropdown ── */
.bank-avatar-dropdown {
    position: relative;
}
.bank-avatar-menu {
    display: none;
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 220px;
    background: #fff;
    border: 1px solid #E5E9EF;
    border-radius: 14px;
    box-shadow: 0 8px 32px rgba(0,0,0,.12);
    z-index: 9999;
    overflow: hidden;
    animation: avatarMenuIn .15s ease;
}
.bank-avatar-menu.open { display: block; }

@keyframes avatarMenuIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}

.bank-avatar-menu-header {
    padding: .85rem 1rem .7rem;
    border-bottom: 1px solid #F3F4F6;
}
.bank-avatar-menu-name {
    font-size: .82rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 .1rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.bank-avatar-menu-email {
    font-size: .74rem;
    color: #6B7280;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.bank-avatar-menu-item {
    display: flex;
    align-items: center;
    gap: .6rem;
    padding: .65rem 1rem;
    font-size: .83rem;
    font-weight: 500;
    color: #374151;
    text-decoration: none;
    transition: background .15s;
}
.bank-avatar-menu-item:hover { background: #F9FAFB; color: #111827; }
.bank-avatar-menu-item i { font-size: 1rem; color: #6B7280; flex-shrink: 0; }
.bank-avatar-menu-divider { height: 1px; background: #F3F4F6; margin: .25rem 0; }
.bank-avatar-menu-logout { color: #DC2626; }
.bank-avatar-menu-logout:hover { background: #FFF1F2; color: #DC2626; }
.bank-avatar-menu-logout i { color: #DC2626; }

/* ── Accordion nav groups ───────────────────────────────── */
.bk-nav-group { margin-bottom: 2px; }

.bk-nav-group-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 9px 10px;
    background: none;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-size: .84rem;
    font-weight: 600;
    color: var(--bk-text);
    text-align: left;
    transition: background .15s, color .15s;
}
.bk-nav-group-toggle:hover {
    background: #F0F7F3;
    color: var(--bk-primary);
}
.bk-nav-group-toggle:hover .bank-nav-icon {
    background: rgba(13,92,46,.12);
    color: var(--bk-primary);
}
.bk-nav-group.open .bk-nav-group-toggle {
    color: var(--bk-primary);
    font-weight: 700;
}
.bk-nav-group.open .bk-nav-group-toggle .bank-nav-icon {
    background: rgba(13,92,46,.10);
    color: var(--bk-primary);
}
.bk-nav-chevron {
    font-size: .85rem;
    margin-left: auto;
    color: #9ca3af;
    transition: transform .25s cubic-bezier(.34,1.56,.64,1);
    flex-shrink: 0;
}
.bk-nav-group.open .bk-nav-chevron {
    transform: rotate(90deg);
    color: var(--bk-accent);
}
.bk-nav-group-body {
    list-style: none;
    padding: 2px 0 4px 12px;
    margin: 0;
    overflow: hidden;
    max-height: 0;
    transition: max-height .3s ease;
}
.bk-nav-group.open .bk-nav-group-body {
    max-height: 560px;
}
.bk-nav-group-body .bank-nav-link {
    font-size: .82rem;
    padding: 7px 10px;
    border-left: 2px solid #e5e7eb;
    border-radius: 0 8px 8px 0;
    margin-left: 6px;
}
.bk-nav-group-body .bank-nav-link:hover,
.bk-nav-group-body .bank-nav-link.active {
    border-left-color: var(--bk-accent);
}
</style>

<script>
(function() {
    const toggle = document.getElementById('sidebarToggle');
    const close  = document.getElementById('sidebarClose');
    const overlay = document.getElementById('sidebarOverlay');
    const sidebar = document.getElementById('bankSidebar');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (toggle) toggle.addEventListener('click', openSidebar);
    if (close) close.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // ── Avatar dropdown ──────────────────────────────
    const avatarToggle = document.getElementById('avatarToggle');
    const avatarMenu   = document.getElementById('avatarMenu');

    if (avatarToggle && avatarMenu) {
        avatarToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            const open = avatarMenu.classList.toggle('open');
            avatarToggle.setAttribute('aria-expanded', open);
        });

        // Close when clicking outside
        document.addEventListener('click', function (e) {
            if (!avatarMenu.contains(e.target) && e.target !== avatarToggle) {
                avatarMenu.classList.remove('open');
                avatarToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                avatarMenu.classList.remove('open');
                avatarToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ── Accordion nav groups ──────────────────────────────
    document.querySelectorAll('.bk-nav-group-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var group  = this.closest('.bk-nav-group');
            var isOpen = group.classList.contains('open');
            document.querySelectorAll('.bk-nav-group.open').forEach(function(g) {
                g.classList.remove('open');
            });
            if (!isOpen) group.classList.add('open');
        });
    });
})();
</script>

@php
    $unreadNotifCount = \App\Models\NotificationLog::where('user_id', auth()->id())
        ->where('user_read', false)
        ->count();
        //$inStockistSection = request()->routeIs('user.stockist.*');
        //$inAffiliateSection = request()->routeIs('user.affiliate.*');
        
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
                    <p class="bank-nav-label">Main</p>
                    

                
                        {{-- ── STOCKIST-ONLY MENU ───────────────────── --}}
                        <ul>
                            <li>
                                <a href="{{ route('user.home') }}" class="bank-nav-link">
                                    <span class="bank-nav-icon"><i class="las la-home"></i></span>
                                    <span>Back to Main Menu</span>
                                </a>
                            </li>

                            
                        </ul>
                        <p class="bank-nav-label">Stockist</p>
                        <ul>
                            <li>
                                <a href="{{ route('user.stockist.dashboard') }}" class="bank-nav-link {{ menuActive('user.stockist.dashboard') }}">
                                    <span class="bank-nav-icon"><i class="las la-store-alt"></i></span>
                                    <span>Dashboard</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.stockist.pos.index') }}" class="bank-nav-link {{ menuActive('user.stockist.pos.*') }}">
                                    <span class="bank-nav-icon"><i class="las la-cash-register"></i></span>
                                    <span>Point of Sale</span>
                                    <span class="pos-nav-badge">POS</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.stockist.profile') }}" class="bank-nav-link {{ menuActive('user.stockist.profile') }}">
                                    <span class="bank-nav-icon"><i class="las la-user-circle"></i></span>
                                    <span>Profile</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.stockist.history') }}" class="bank-nav-link {{ menuActive('user.stockist.history') }}">
                                    <span class="bank-nav-icon"><i class="las la-history"></i></span>
                                    <span>Redemption History</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.stockist.affiliate.redeem') }}" class="bank-nav-link {{ menuActive('user.stockist.affiliate.*') }}">
                                    <span class="bank-nav-icon"><i class="las la-link"></i></span>
                                    <span>Redeem Affiliate Products</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('user.stockist.redemptions') }}" class="bank-nav-link {{ menuActive('user.stockist.redemptions') }}">
                                    <span class="bank-nav-icon"><i class="las la-clipboard-list"></i></span>
                                    <span>Redemption Report</span>
                                </a>
                            </li>
                            {{-- Stock: a Mega orders from the company and sells to the Minis in its
                                 state; a Mini restocks only by buying from a Mega --}}
                            @php
                                $navStockist = auth()->user()->stockist;
                                $navIsMega   = (bool) $navStockist?->isMega();
                                $navPendingTransfers = $navIsMega
                                    ? \App\Models\StockTransfer::where('seller_stockist_id', $navStockist->id)->where('status', \App\Models\StockTransfer::STATUS_PENDING)->count()
                                    : 0;
                            @endphp
                            <li>
                                <a href="{{ route('user.stockist.inventory.dashboard') }}" class="bank-nav-link {{ menuActive('user.stockist.inventory.dashboard') }}">
                                    <span class="bank-nav-icon"><i class="las la-boxes"></i></span>
                                    <span>Inventory</span>
                                </a>
                            </li>
                            @if ($navIsMega)
                                <li>
                                    <a href="{{ route('user.stockist.inventory.orders') }}" class="bank-nav-link {{ menuActive(['user.stockist.inventory.orders', 'user.stockist.inventory.order.details', 'user.stockist.inventory.catalog', 'user.stockist.inventory.checkout']) }}">
                                        <span class="bank-nav-icon"><i class="las la-shopping-basket"></i></span>
                                        <span>My Orders</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('user.stockist.transfers.index') }}" class="bank-nav-link {{ menuActive('user.stockist.transfers.*') }}">
                                        <span class="bank-nav-icon"><i class="las la-exchange-alt"></i></span>
                                        <span>Stock Requests</span>
                                        @if ($navPendingTransfers > 0)
                                            <span class="bank-nav-badge bank-nav-badge-danger">{{ $navPendingTransfers > 9 ? '9+' : $navPendingTransfers }}</span>
                                        @endif
                                    </a>
                                </li>
                            @elseif ($navStockist)
                                <li>
                                    <a href="{{ route('user.stockist.transfers.create') }}" class="bank-nav-link {{ menuActive('user.stockist.transfers.create') }}">
                                        <span class="bank-nav-icon"><i class="las la-truck-loading"></i></span>
                                        <span>Buy from Mega</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('user.stockist.transfers.index') }}" class="bank-nav-link {{ menuActive(['user.stockist.transfers.index', 'user.stockist.transfers.show', 'user.stockist.inventory.orders', 'user.stockist.inventory.order.details']) }}">
                                        <span class="bank-nav-icon"><i class="las la-shopping-basket"></i></span>
                                        <span>My Orders</span>
                                    </a>
                                </li>
                            @endif
                            {{--
                            <li>
                                <a href="{{ route('user.stockist.welcome-pack.index') }}" class="bank-nav-link {{ menuActive('user.stockist.welcome-pack.index') }}">
                                    <span class="bank-nav-icon"><i class="las la-gift"></i></span>
                                    <span>Welcome Package</span>
                                </a>
                            </li>
                            --}}
                            
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

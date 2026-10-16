@extends($activeTemplate . 'layouts.master')

@section('content')

@auth
    @include($activeTemplate.'partials.flashcards')
@endauth

@php
    $user        = auth()->user();
    $userIDcard  = retrunUserIDcard($user->id);
    $inMatrix    = checkIfUserIsInMatrix($user->id);
    $hour        = (int) date('H');
    $greeting    = $hour < 12 ? '☀️ Good Morning' : ($hour < 17 ? '🌤 Good Afternoon' : '🌙 Good Evening');
@endphp

{{-- ===================================================
     TOP SECTION  —  bright background, card LEFT
     =================================================== --}}
<div class="bk-top">

    {{-- decorative blobs --}}
    <span class="bk-blob bk-blob-1"></span>
    <span class="bk-blob bk-blob-2"></span>
    <span class="bk-blob bk-blob-3"></span>
 
    {{-- LEFT  : ATM card --}}
    <div class="bk-card-col">
        <div class="bk-atm-card">

            {{-- glare / shine --}}
            <span class="bk-atm-glare"></span>
            <span class="bk-atm-circle bk-atm-c1"></span>
            <span class="bk-atm-circle bk-atm-c2"></span>

            {{-- top row --}}
            <div class="bk-atm-top">
                <span class="bk-atm-logo">WCS</span>
                <button class="bk-atm-eye" id="toggleBalance" title="Show / hide balance">
                    <i class="las la-eye" id="eyeIcon"></i>
                </button>
            </div>

            {{-- chip + balance --}}
            <div class="bk-atm-mid">
                <div class="bk-chip">
                    <div class="bk-chip-h"></div>
                    <div class="bk-chip-v"></div>
                </div>
                <div>
                    <p class="bk-atm-bal-label">Available Balance</p>
                    <p class="bk-atm-bal" id="balanceDisplay">₦{{ number_format($user->balance, 2) }}</p>
                </div>
            </div>

            {{-- card number row --}}
            <div class="bk-atm-num">
                <span>••••</span>
                <span>••••</span>
                <span>••••</span>
                <span>{{ $userIDcard ? str_pad(substr('WCS'.$userIDcard->id, -4), 4, '0', STR_PAD_LEFT) : '0000' }}</span>
            </div>

            {{-- bottom row --}}
            <div class="bk-atm-bottom">
                <div>
                    <p class="bk-atm-meta-label">Card Holder</p>
                    <p class="bk-atm-meta">{{ strtoupper($user->fullname) }}</p>
                </div>
                <div>
                    <p class="bk-atm-meta-label">Valid Thru</p>
                    <p class="bk-atm-meta">{{ now()->format('m/y') }}</p>
                </div>
                {{-- mastercard-style rings --}}
                <div class="bk-mc">
                    <span class="bk-mc-l"></span>
                    <span class="bk-mc-r"></span>
                </div>
            </div>
        </div>

        {{-- share credits badge below card --}}
        <div class="bk-card-sub-badge">
            <span class="bk-csb-icon"><i class="las la-coins"></i></span>
            <span class="bk-csb-text">
                Vise wallet  &nbsp;<strong>₦{{ number_format($user->visa ?? 0, 2) }}</strong>
            </span>
        </div>
    </div>

    {{-- RIGHT : greeting + quick actions --}}
    <div class="bk-right-col">

        {{-- greeting 
        <div class="bk-greeting">
            <p class="bk-greet-sub">{{ $greeting }},</p>
            <h3 class="bk-greet-name">{{ $user->firstname }}!</h3>
            <p class="bk-greet-date">{{ now()->format('l, F j Y') }}</p>
        </div>
        --}}
        {{-- quick actions grid 
        <p class="bk-qa-section-label">Quick Actions</p>
        --}}
        <div class="bk-qa-grid">
            <a href="{{ route('user.deposit.index') }}" class="bk-qa bk-qa-green">
                <i class="bk-qa-icon las la-wallet"></i>
                <span>Deposit</span>
            </a>
    
            @if(returnStockist(auth()->id()))
            <a href="{{ route('user.my.tree') }}" class="bk-qa bk-qa-teal">
                <i class="bk-qa-icon las la-project-diagram"></i>
                <span>Genealogy</span>
            </a>
            @else

            <a href="{{ route('user.withdraw.history') }}" class="bk-qa bk-qa-rose">
                <i class="bk-qa-icon las la-arrow-circle-up"></i>
                <span>Withdraw</span>
            </a>
            @endif
            <a href="{{ route('user.transactions') }}" class="bk-qa bk-qa-blue">
                <i class="bk-qa-icon las la-file-invoice-dollar"></i>
                <span>History</span>
            </a>
            <a href="{{ route('user.investment.portfolio') }}" class="bk-qa bk-qa-violet">
                <i class="bk-qa-icon las la-chart-line"></i>
                <span>Invest</span>
            </a>
            <a href="{{ route('products') }}" class="bk-qa bk-qa-amber">
                <i class="bk-qa-icon las la-store"></i>
                <span>Shop</span>
            </a>
            <a href="{{ route('user.my.ref') }}" class="bk-qa bk-qa-pink">
                <i class="bk-qa-icon las la-user-friends"></i>
                <span>Referrals</span>
            </a>
            
            {{--
            <a href="{{ route('user.epin.recharge') }}" class="bk-qa bk-qa-indigo">
                <i class="bk-qa-icon las la-qrcode"></i>
                <span>E-Pin</span>
            </a>
            --}}
        </div>
    </div>

</div>{{-- /bk-top --}}

{{-- NOTIFICATION CENTER --}}
@if (gs('notice'))
<div class="nc-wrap" id="ncWrap">
    <div class="nc-card">
        {{-- Left accent pulse --}}
        <div class="nc-pulse-ring"></div>

        {{-- Icon column --}}
        <div class="nc-icon-col">
            <div class="nc-icon-wrap">
                <i class="las la-bell"></i>
                <span class="nc-dot"></span>
            </div>
        </div>

        {{-- Content --}}
        <div class="nc-body">
            <div class="nc-meta">
                <span class="nc-badge">Notice</span>
                <span class="nc-time"><i class="las la-clock"></i> {{ now()->format('M d · h:i A') }}</span>
            </div>
            <div class="nc-text">@php echo gs('notice') @endphp</div>
        </div>

        {{-- Close --}}
        <button class="nc-close" id="ncClose" title="Dismiss">
            <i class="las la-times"></i>
        </button>
    </div>
</div>
@endif

@if (gs('free_user_notice'))
    <div class="nc-wrap" id="ncWrap">
        <div class="nc-card">
            {{-- Left accent pulse --}}
            <div class="nc-pulse-ring"></div>

            {{-- Icon column --}}
            <div class="nc-icon-col">
                <div class="nc-icon-wrap">
                    <i class="las la-bell"></i>
                    <span class="nc-dot"></span>
                </div>
            </div>

            {{-- Content --}}
            <div class="nc-body">
                <div class="nc-meta">
                    <span class="nc-badge">Notice</span>
                    <span class="nc-time"><i class="las la-clock"></i> {{ now()->format('M d · h:i A') }}</span>
                </div>
                 @if (gs('free_user_notice') != null)
                     <div class="nc-text">
                        @php echo gs('free_user_notice'); @endphp
                     </div>
                 @endif             
            </div>

            {{-- Close --}}
            <button class="nc-close" id="ncClose" title="Dismiss">
                <i class="las la-times"></i>
            </button>
        </div>
    </div> 
@endif

{{-- ===== STATS GRID ===== --}}
<div class="bk-stats-grid">
    <div class="bk-stat-card bk-stat-emerald">
        <div class="bk-stat-bg-icon"><i class="las la-arrow-circle-down"></i></div>
        <p class="bk-stat-label">Total Deposited</p>
        <h4 class="bk-stat-value">{{ showAmount($totalDeposit) }}</h4>
        <p class="bk-stat-sub"><i class="las la-calendar"></i> All time</p>
    </div>
    <div class="bk-stat-card bk-stat-rose">
        <div class="bk-stat-bg-icon"><i class="las la-arrow-circle-up"></i></div>
        <p class="bk-stat-label text-dark">Total Withdrawn</p>
        <h4 class="bk-stat-value">{{ showAmount($totalWithdraw) }}</h4>
        <p class="bk-stat-sub text-dark"><i class="las la-check-circle"></i> {{ $completeWithdraw }} completed</p>
    </div>
    <div class="bk-stat-card bk-stat-indigo">
        <div class="bk-stat-bg-icon"><i class="las la-users"></i></div>
        <p class="bk-stat-label">Referral Earnings</p>
        <h4 class="bk-stat-value">{{ showAmount($total_ref) }}</h4>
        <p class="bk-stat-sub"><i class="las la-user-friends"></i> {{ $totalRef ?? 0 }} referrals</p>
    </div>
    <div class="bk-stat-card bk-stat-violet">
        <div class="bk-stat-bg-icon"><i class="las la-coins"></i></div>
        <p class="bk-stat-label">Dividend Earnings</p>
        <h4 class="bk-stat-value">₦{{ number_format($totalDividends ?? 0, 2) }}</h4>
        <p class="bk-stat-sub"><i class="las la-layer-group"></i> {{ number_format($totalShares ?? 0) }} units</p>
    </div>
</div>


{{-- ===== MLM BONUSES STRIP ===== --}}
<div class="bk-mlm-section">
    <div class="bk-mlm-header">
        <span class="bk-section-dot bk-dot-violet" style="display:inline-block;margin-right:6px;"></span>
        <span class="bk-mlm-label">MLM &amp; Product Earnings</span>
        @if($user->project)
            <span class="bk-mlm-plan-chip" style="background:{{ $user->project->color }}20;color:{{ $user->project->color }};border:1px solid {{ $user->project->color }}40;">
                <i class="{{ $user->project->icon }}"></i> {{ $user->project->title }}
            </span>
        @endif
    </div>
    <div class="bk-mlm-grid">
        <div class="bk-mlm-card bk-mlm-product">
            <div class="bk-mlm-icon" style="background:#7C3AED20;color:#7C3AED;"><i class="las la-shopping-bag"></i></div>
            <div class="bk-mlm-info">
                <p class="bk-mlm-val">{{ showAmount($user->product_wallet ?? 0) }}</p>
                <p class="bk-mlm-lbl">Re-purchase Wallet</p>
                <p class="bk-mlm-sub">Used for product purchases</p>
            </div>
        </div>
        <div class="bk-mlm-card">
            <div class="bk-mlm-icon" style="background:#05966920;color:#059669;"><i class="las la-hand-holding-usd"></i></div>
            <div class="bk-mlm-info">
                <p class="bk-mlm-val">{{ showAmount($user->direct_bonus ?? 0) }}</p>
                <p class="bk-mlm-lbl">Direct Bonus</p>
                <p class="bk-mlm-sub">Lifetime earned</p>
            </div>
        </div>
        <div class="bk-mlm-card">
            <div class="bk-mlm-icon" style="background:#0EA5E920;color:#0EA5E9;"><i class="las la-network-wired"></i></div>
            <div class="bk-mlm-info">
                <p class="bk-mlm-val">{{ showAmount($user->indirect_bonus ?? 0) }}</p>
                <p class="bk-mlm-lbl">Indirect Bonus</p>
                <p class="bk-mlm-sub">Lifetime earned</p>
            </div>
        </div>
        <div class="bk-mlm-card">
            <div class="bk-mlm-icon" style="background:#F59E0B20;color:#D97706;"><i class="las la-arrow-up"></i></div>
            <div class="bk-mlm-info">
                <p class="bk-mlm-val">{{ showAmount($user->upgrade_bonus ?? 0) }}</p>
                <p class="bk-mlm-lbl">Upgrade Bonus</p>
                <p class="bk-mlm-sub">Lifetime earned</p>
            </div>
        </div>
        <div class="bk-mlm-card">
            <div class="bk-mlm-icon" style="background:#EC489920;color:#EC4899;"><i class="las la-sitemap"></i></div>
            <div class="bk-mlm-info">
                <p class="bk-mlm-val">{{ showAmount($user->unilevel_bonus ?? 0) }}</p>
                <p class="bk-mlm-lbl">Unilevel Bonus</p>
                <p class="bk-mlm-sub">Lifetime earned</p>
            </div>
        </div>
        <div class="bk-mlm-card">
            <div class="bk-mlm-icon" style="background:#EF444420;color:#EF4444;"><i class="las la-balance-scale"></i></div>
            <div class="bk-mlm-info">
                <p class="bk-mlm-val">{{ showAmount($user->matching_bonus ?? 0) }}</p>
                <p class="bk-mlm-lbl">Matching Bonus</p>
                <p class="bk-mlm-sub">Lifetime earned</p>
            </div>
        </div>
        <div class="bk-mlm-card">
            <div class="bk-mlm-icon" style="background:#7C3AED20;color:#7C3AED;"><i class="las la-keyboard"></i></div>
            <div class="bk-mlm-info">
                <p class="bk-mlm-val">{{ showAmount($user->key_in_bonus ?? 0) }}</p>
                <p class="bk-mlm-lbl">Key-In Bonus</p>
                <p class="bk-mlm-sub">Lifetime earned</p>
            </div>
        </div>
    </div>

    {{-- Notices strip — visible only when there's an unread admin notice --}}
    @if(($unreadAdminNoticeCount ?? 0) > 0)
    <a href="{{ route('user.notifications') }}" class="bk-notice-strip" style="text-decoration:none;">
        <div class="bk-notice-left">
            <span class="bk-notice-icon"><i class="las la-user-shield"></i></span>
            <div>
                <p class="bk-notice-label">Personal Notice from Admin</p>
                <p class="bk-notice-sub">{{ $latestAdminNotice->subject ?? 'You have a new notice' }}</p>
            </div>
        </div>
        <div class="bk-notice-right">
            <span class="bk-notice-count">{{ $unreadAdminNoticeCount > 9 ? '9+' : $unreadAdminNoticeCount }}</span>
            <span class="bk-notice-cta">View <i class="las la-arrow-right"></i></span>
        </div>
    </a>
    @endif

    {{-- Welcome Package strips — one per pending package --}}
    @if(($pendingWelcomePackages ?? collect())->count())
    @foreach($pendingWelcomePackages as $wp)
    <div class="bk-wp-strip">
        <div class="bk-wp-strip-left">
            <span class="bk-wp-icon"><i class="las la-gift"></i></span>
            <div>
                <p class="bk-wp-label">{{ ucfirst($wp->source) }} Welcome Package &mdash; {{ showAmount($wp->amount) }}</p>
                <p class="bk-wp-sub">Present code to a stockist to redeem your gift</p>
            </div>
        </div>
        <div class="bk-wp-right">
            <span class="bk-wp-code">{{ $wp->code }}</span>
            <button class="bk-wp-copy" data-code="{{ $wp->code }}" title="Copy code">
                <i class="las la-copy"></i>
            </button>
        </div>
    </div>
    @endforeach
    @endif

    {{-- ACB card — only for ACB members --}}
    @if($user->isAcb())
    <a href="{{ route('user.acb') }}" class="bk-acb-strip" style="text-decoration:none;">
        <div class="bk-acb-strip-left">
            <span class="bk-acb-icon"><i class="las la-star"></i></span>
            <div>
                <p class="bk-acb-label">Upline Celebrated Bonus</p>
                <p class="bk-acb-sub">ACB Member · Earns 5%/2%/1% from downline awards</p>
            </div>
        </div>
        <div class="bk-acb-balance">
            <span class="bk-acb-val">{{ showAmount($user->acb ?? 0) }}</span>
            <span class="bk-acb-cta">View History <i class="las la-arrow-right"></i></span>
        </div>
    </a>
    @endif

    {{-- Autoship card — visible only when there is a balance to action --}}
    @php $autoshipBal = (float)($user->autoship ?? 0); @endphp
    @if($autoshipBal > 0)
    <a href="{{ route('user.bonus.transfer.index') }}" class="bk-autoship-strip" style="text-decoration:none;">
        <div class="bk-autoship-left">
            <span class="bk-autoship-icon"><i class="las la-sync-alt"></i></span>
            <div>
                <p class="bk-autoship-label">Autoship Balance</p>
                <p class="bk-autoship-sub">20% of Matching Bonus · Buy {{ \App\Services\AutoshipService::MIN_PV }} PV of products this month to transfer</p>
            </div>
        </div>
        <div class="bk-autoship-right">
            <span class="bk-autoship-val">{{ showAmount($autoshipBal) }}</span>
            <span class="bk-autoship-cta">Transfer <i class="las la-arrow-right"></i></span>
        </div>
    </a>
    @endif

    {{-- PV Left / Right row --}}
    @if($userMatrix)
    <div class="bk-pv-row">
        <div class="bk-pv-label">
            <i class="las la-project-diagram"></i> Binary Tree PV
            @if($userMatrix->position)
                <span class="bk-pv-pos-chip bk-pv-pos-{{ $userMatrix->position }}">{{ ucfirst($userMatrix->position) }}</span>
            @endif
        </div>
        <div class="bk-pv-bars">
            @php
                $pvL   = (float)($userMatrix->pv_left_pairing  ?? 0);
                $pvR   = (float)($userMatrix->pv_right_pairing ?? 0);
                $pvMax = max($pvL, $pvR, 1);
            @endphp
            <div class="bk-pv-leg">
                <div class="bk-pv-leg-top">
                    <span class="bk-pv-leg-lbl"><i class="las la-arrow-alt-circle-left"></i> Left PV</span>
                    <span class="bk-pv-leg-val">{{ number_format($pvL, 2) }}</span>
                </div>
                <div class="bk-pv-bar-track">
                    <div class="bk-pv-bar-fill bk-pv-left" style="width:{{ min(100, round($pvL / $pvMax * 100)) }}%"></div>
                </div>
            </div>
            <div class="bk-pv-leg">
                <div class="bk-pv-leg-top">
                    <span class="bk-pv-leg-lbl"><i class="las la-arrow-alt-circle-right"></i> Right PV</span>
                    <span class="bk-pv-leg-val">{{ number_format($pvR, 2) }}</span>
                </div>
                <div class="bk-pv-bar-track">
                    <div class="bk-pv-bar-fill bk-pv-right" style="width:{{ min(100, round($pvR / $pvMax * 100)) }}%"></div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- ===== BOTTOM ROW ===== --}}
<div class="bk-bottom-row">

    {{-- Recent Transactions --}}
    <div class="bk-txn-card">
        <div class="bk-section-header">
            <div class="bk-section-title-wrap">
                <span class="bk-section-dot bk-dot-blue"></span>
                <h6 class="bk-section-title">Recent Transactions</h6>
            </div>
            <a href="{{ route('user.transactions') }}" class="bk-section-link">View all <i class="las la-arrow-right"></i></a>
        </div>
        <div class="bk-txn-list">
            @forelse($recentTransactions ?? [] as $txn)
            @php $c = $txn->trx_type === '+' ? 'emerald' : 'rose'; @endphp
            <div class="bk-txn-item bk-txn-{{ $c }}">
                <div class="bk-txn-icon bk-icon-{{ $c }}">
                    <i class="las la-{{ $txn->trx_type === '+' ? 'arrow-down' : 'arrow-up' }}"></i>
                </div>
                <div class="bk-txn-details">
                    <p class="bk-txn-name">{{ Str::limit($txn->details, 32) }}</p>
                    <p class="bk-txn-date">{{ $txn->created_at->format('M d, Y · h:i A') }}</p>
                </div>
                <div class="bk-txn-right">
                    <span class="bk-txn-amount bk-amount-{{ $c }}">
                        {{ $txn->trx_type }}₦{{ number_format($txn->amount, 2) }}
                    </span>
                    <span class="bk-txn-type-badge bk-badge-{{ $c }}">
                        {{ $txn->trx_type === '+' ? 'Credit' : 'Debit' }}
                    </span>
                </div>
            </div>
            @empty
            <div class="bk-empty-state">
                <div class="bk-empty-icon"><i class="las la-receipt"></i></div>
                <h6>No Transactions Yet</h6>
                <p>Your history will appear here</p>
                <a href="{{ route('user.deposit.index') }}" class="bk-empty-btn">Make a Deposit</a>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Right Panel --}}
    <div class="bk-right-panel">

        {{-- Portfolio --}}
        <div class="bk-portfolio-card">
            <div class="bk-portfolio-header">
                <div class="bk-portfolio-header-icon"><i class="las la-chart-pie"></i></div>
                <div>
                    <h6 class="bk-portfolio-title">Portfolio</h6>
                    <p class="bk-portfolio-sub">Investment overview</p>
                </div>
                <a href="{{ route('user.investment.portfolio') }}" class="bk-port-link">View →</a>
            </div>
            <div class="bk-portfolio-body">
                <div class="bk-port-item">
                    <div class="bk-port-pill bk-pill-teal"><i class="las la-layer-group"></i></div>
                    <div class="bk-port-info">
                        <span class="bk-port-label">Share Units</span>
                        <span class="bk-port-val">{{ number_format($totalShares ?? 0) }}</span>
                    </div>
                </div>
                <div class="bk-port-item">
                    <div class="bk-port-pill bk-pill-green"><i class="las la-coins"></i></div>
                    <div class="bk-port-info">
                        <span class="bk-port-label">Share Credits</span>
                        <span class="bk-port-val">₦{{ number_format($user->shares ?? 0, 2) }}</span>
                    </div>
                </div>
                <div class="bk-port-item">
                    <div class="bk-port-pill bk-pill-violet"><i class="las la-hand-holding-usd"></i></div>
                    <div class="bk-port-info">
                        <span class="bk-port-label">Dividends</span>
                        <span class="bk-port-val">₦{{ number_format($totalDividends ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Account Status --}}
        <div class="bk-status-card">
            <div class="bk-section-header">
                <div class="bk-section-title-wrap">
                    <span class="bk-section-dot bk-dot-green"></span>
                    <h6 class="bk-section-title">Account Status</h6>
                </div>
            </div>
            <div class="bk-status-list">
                <div class="bk-status-row">
                    <span class="bk-status-label"><i class="las la-shield-alt"></i> Account</span>
                    <span class="bk-pill-badge {{ $user->status == 1 ? 'bk-pill-green' : 'bk-pill-red' }}">
                        {{ $user->status == 1 ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="bk-status-row">
                    <span class="bk-status-label"><i class="las la-id-card"></i> KYC</span>
                    @if($user->kv == 1)
                        <span class="bk-pill-badge bk-pill-green">Verified</span>
                    @elseif($user->kv == 2)
                        <span class="bk-pill-badge bk-pill-amber">Pending</span>
                    @else
                        <span class="bk-pill-badge bk-pill-red">Unverified</span>
                    @endif
                </div>
                <div class="bk-status-row">
                    <span class="bk-status-label"><i class="las la-clock"></i> Pending W/D</span>
                    <span class="bk-pill-badge {{ $pendingWithdraw > 0 ? 'bk-pill-amber' : 'bk-pill-green' }}">{{ $pendingWithdraw }}</span>
                </div>
                <div class="bk-status-row">
                    <span class="bk-status-label"><i class="las la-check-circle"></i> Matrix</span>
                    <span class="bk-pill-badge {{ $inMatrix ? 'bk-pill-green' : 'bk-pill-red' }}">
                        {{ $inMatrix ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Quick Links --}}
        <div class="bk-links-card">
            <div class="bk-section-header">
                <div class="bk-section-title-wrap">
                    <span class="bk-section-dot bk-dot-violet"></span>
                    <h6 class="bk-section-title">Quick Links</h6>
                </div>
            </div>
            <div class="bk-grid-links">
                <a href="{{ route('user.my.ref') }}"         class="bk-grid-link bk-gl-blue">
                    <i class="las la-users"></i><span>Referrals</span>
                    <em class="bk-gl-badge">{{ $totalRef ?? 0 }}</em>
                </a>
                <a href="{{ route('user.my.tree') }}"        class="bk-grid-link bk-gl-teal"><i class="las la-sitemap"></i><span>Genealogy</span></a>
                <a href="{{ route('user.my.stages') }}"      class="bk-grid-link bk-gl-amber"><i class="las la-medal"></i><span>Stages</span></a>
                <a href="{{ route('user.epin.recharge') }}"  class="bk-grid-link bk-gl-pink"><i class="las la-qrcode"></i><span>E-Pin</span></a>
                <a href="{{ route('products') }}"            class="bk-grid-link bk-gl-green"><i class="las la-shopping-bag"></i><span>Shop</span></a>
                <a href="{{ route('user.profile.setting') }}" class="bk-grid-link bk-gl-violet"><i class="las la-user-edit"></i><span>Profile</span></a>
            </div>
        </div>

    </div>
</div>

@if ($user->kv == \App\Constants\Status::KYC_UNVERIFIED && $user->kyc_rejection_reason)
<div class="modal fade" id="kycRejectionReason">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">KYC Rejection Reason</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body"><p>{{ $user->kyc_rejection_reason }}</p></div>
        </div>
    </div>
</div>
@endif

@endsection

@push('script')
<script>
(function () {
    // Welcome package copy buttons
    document.querySelectorAll('.bk-wp-copy').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var code = btn.dataset.code;
            var icon = btn.querySelector('i');
            navigator.clipboard.writeText(code).then(function() {
                if (icon) { icon.className = 'las la-check'; }
                btn.style.background = '#059669';
                setTimeout(function() {
                    if (icon) { icon.className = 'las la-copy'; }
                    btn.style.background = '';
                }, 2000);
            }).catch(function() { alert('Copy failed — code: ' + code); });
        });
    });

    // Notification dismiss
    var ncClose = document.getElementById('ncClose');
    var ncWrap  = document.getElementById('ncWrap');
    if (ncClose && ncWrap) {
        ncClose.addEventListener('click', function () {
            ncWrap.style.transition = 'opacity .35s ease, transform .35s ease, max-height .4s ease, margin .4s ease';
            ncWrap.style.opacity    = '0';
            ncWrap.style.transform  = 'translateY(-10px)';
            ncWrap.style.maxHeight  = '0';
            ncWrap.style.margin     = '0';
            ncWrap.style.overflow   = 'hidden';
            setTimeout(function () { ncWrap.style.display = 'none'; }, 420);
        });
    }
})();
(function () {
    const el  = document.getElementById('balanceDisplay');
    const eye = document.getElementById('eyeIcon');
    const btn = document.getElementById('toggleBalance');
    if (!el || !btn) return;
    const real   = el.textContent.trim();
    const masked = '₦ ••••••';
    let hidden   = false;
    btn.addEventListener('click', function () {
        hidden = !hidden;
        el.textContent  = hidden ? masked : real;
        eye.className   = hidden ? 'las la-eye-slash' : 'las la-eye';
    });
})();
</script>
@endpush

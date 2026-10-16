@extends('Template::layouts.master')

@section('content')
<div class="rpu-page">

    {{-- ── HEADER STRIP ─────────────────────────────────── --}}
    <div class="rpu-header">
        <div class="rpu-header-left">
            <div class="rpu-header-icon"><i class="las la-medal"></i></div>
            <div>
                <h1 class="rpu-header-title">Repurchase (Unilevel) Award</h1>
                <p class="rpu-header-sub">Track your product PV and unlock reward milestones</p>
            </div>
        </div>
        <div class="rpu-total-pv-box">
            <span class="rpu-total-pv-label">Your Total PV</span>
            <span class="rpu-total-pv-val">{{ number_format($totalPv, 2) }}</span>
        </div>
    </div>

    {{-- ── WALLET BALANCE CARD ────────────────────────────── --}}
    <div class="rpu-wallet-card">
        <div class="rpu-wallet-left">
            <div class="rpu-wallet-icon"><i class="las la-wallet"></i></div>
            <div>
                <p class="rpu-wallet-label">Repurchase Award Wallet</p>
                <p class="rpu-wallet-hint">Credited by admin when you qualify — transferable to your Money Box</p>
            </div>
        </div>
        <div class="rpu-wallet-right">
            <span class="rpu-wallet-amount">₦{{ number_format($walletBalance, 2) }}</span>
            @if($walletBalance > 0)
                <a href="{{ route('user.bonus.transfer.index') }}" class="rpu-wallet-transfer-btn">
                    <i class="las la-exchange-alt"></i> Transfer to Money Box
                </a>
            @endif
        </div>
    </div>

    @if($awards->count())

        {{-- ── PROGRESS TIERS ─────────────────────────────── --}}
        <div class="rpu-tiers">
            @foreach($awards as $award)
            @php
                $reqPv     = (float) $award->required_pv;
                $qualified = $totalPv >= $reqPv;
                $pct       = $reqPv > 0 ? min(100, round($totalPv / $reqPv * 100, 1)) : 100;
                $remaining = max(0, $reqPv - $totalPv);
            @endphp
            <div class="rpu-tier {{ $qualified ? 'rpu-tier--qualified' : 'rpu-tier--pending' }}">
                <div class="rpu-tier-left">
                    <div class="rpu-tier-medal {{ $qualified ? 'rpu-medal--gold' : 'rpu-medal--grey' }}">
                        <i class="las la-medal"></i>
                    </div>
                    <div class="rpu-tier-info">
                        <div class="rpu-tier-name">{{ $award->title }}</div>
                        <div class="rpu-tier-pv-req">{{ number_format($reqPv, 2) }} PV required</div>
                    </div>
                </div>
                <div class="rpu-tier-center">
                    <div class="rpu-bar-wrap">
                        <div class="rpu-bar">
                            <div class="rpu-bar-fill {{ $qualified ? 'rpu-bar-fill--done' : '' }}" style="width:{{ $pct }}%"></div>
                        </div>
                        <span class="rpu-bar-pct">{{ $pct }}%</span>
                    </div>
                    @if(!$qualified)
                        <p class="rpu-tier-remaining">{{ number_format($remaining, 2) }} PV remaining</p>
                    @else
                        <p class="rpu-tier-done-note"><i class="las la-check-circle"></i> Target reached — await admin credit</p>
                    @endif
                </div>
                <div class="rpu-tier-right">
                    <div class="rpu-tier-reward">
                        <span class="rpu-reward-label">Reward</span>
                        <span class="rpu-reward-val">₦{{ number_format($award->amount, 2) }}</span>
                    </div>
                    @if($qualified)
                        <span class="rpu-qualified-tag"><i class="las la-check"></i> Qualified</span>
                    @else
                        <span class="rpu-pending-tag">In Progress</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

    @else
    <div class="rpu-empty">
        <i class="las la-medal"></i>
        <h3>No awards configured</h3>
        <p>Award milestones will appear here once the admin sets them up.</p>
    </div>
    @endif

    {{-- ── HOW IT WORKS ──────────────────────────────────── --}}
    <div class="rpu-how-card">
        <div class="rpu-how-head"><i class="las la-info-circle"></i> How It Works</div>
        <div class="rpu-how-body">
            <div class="rpu-how-step">
                <div class="rpu-how-num">1</div>
                <div>Purchase products through your stockist. Every product carries a Point Value (PV).</div>
            </div>
            <div class="rpu-how-step">
                <div class="rpu-how-num">2</div>
                <div>Your PV accumulates automatically each time your stockist redeems your invoice.</div>
            </div>
            <div class="rpu-how-step">
                <div class="rpu-how-num">3</div>
                <div>Once your total PV crosses an award threshold, you are marked as <strong>Qualified</strong>.</div>
            </div>
            <div class="rpu-how-step">
                <div class="rpu-how-num">4</div>
                <div>Admin reviews qualified members and credits the reward directly to your Repurchase Award Wallet.</div>
            </div>
            <div class="rpu-how-step">
                <div class="rpu-how-num">5</div>
                <div>Visit <a href="{{ route('user.bonus.transfer.index') }}" class="rpu-inline-link">Bonus Transfer</a> to move your wallet balance into your Money Box.</div>
            </div>
        </div>
    </div>

</div>
@endsection

@extends($activeTemplate . 'layouts.master')

@section('content')
@include($activeTemplate . 'layouts.breadcrumb')

@php
    $visaBalance = (float) ($user->visa ?? 0);
    $sym         = gs('cur_sym');
@endphp
 
{{-- ── Page header ──────────────────────────────────────────── --}}
<div class="mp-header">
    <div class="mp-header-icon">
        <i class="las la-project-diagram"></i>
    </div>
    <div class="mp-header-meta">
        <h1 class="mp-header-title">My Project</h1>
        <p class="mp-header-sub">
            @if($currentProject)
                Currently on <strong>{{ $currentProject->title }}</strong> &mdash; upgrade to unlock higher tiers
            @else
                Subscribe to a project to activate your network membership
            @endif
        </p>
    </div>
    <div class="mp-header-wallet">
        <div class="mp-header-wallet-label">VISA Wallet</div>
        <div class="mp-header-wallet-amount">{{ $sym }}{{ showAmount($visaBalance, currencyFormat: false) }}</div>
    </div>
</div>

{{-- ── Project grid ──────────────────────────────────────────── --}}
@if($projects->isEmpty())
    <div class="text-center py-5">
        <i class="las la-project-diagram" style="font-size:3rem;color:#D1D5DB;"></i>
        <p class="mt-3 text-muted">No projects available at this time.</p>
    </div>
@else
    <p class="mp-section-label">
        @if($currentProject)
            Available Tiers
        @else
            All Projects
        @endif
    </p>

    <div class="mp-grid">
        @foreach($projects as $project)
            @php
                $isCurrent  = $currentProject && $currentProject->id === $project->id;
                $isUpgrade  = $currentProject && $project->sort_order > $currentProject->sort_order;
                $isFirst    = !$currentProject;  // no project yet — all are available
                $currentAmt = $currentProject ? (float) $currentProject->amount : 0.0;
                $cost       = round((float) $project->amount - $currentAmt, 2);
                $canAfford  = $visaBalance >= $cost;
                $upgradeAllowed = (bool) $project->upgrade_allowed;
            @endphp
            <div class="mp-card {{ $isCurrent ? 'mp-card--current' : '' }}">

                {{-- colour band --}}
                <div class="mp-card-band" style="background:{{ $project->color }};"></div>

                {{-- head --}}
                <div class="mp-card-head">
                    <div class="mp-card-icon" style="background:{{ $project->color }};">
                        <i class="{{ $project->icon }}"></i>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <p class="mp-card-title">{{ $project->title }}</p>
                        <p class="mp-card-amount">{{ $sym }}{{ showAmount($project->amount, currencyFormat: false) }}</p>
                        <div class="mp-card-badges">
                            @if($isCurrent)
                                <span class="mp-badge mp-badge--current"><i class="las la-check-circle"></i> Current Plan</span>
                            @elseif($isUpgrade || $isFirst)
                                @if($upgradeAllowed)
                                    <span class="mp-badge mp-badge--upgrade">Upgrade Available</span>
                                @else
                                    <span class="mp-badge mp-badge--locked">Not Available</span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                {{-- stats --}}
                <div class="mp-stats">
                    <div class="mp-stat">
                        <p class="mp-stat-val">{{ showAmount($project->pv, currencyFormat: false) }}</p>
                        <p class="mp-stat-lbl">PV</p>
                    </div>
                    <div class="mp-stat">
                        <p class="mp-stat-val">{{ showAmount($project->direct_commission, currencyFormat: false) }}</p>
                        <p class="mp-stat-lbl">Direct bonus.</p>
                    </div>
                    <div class="mp-stat">
                        <p class="mp-stat-val">{{ showAmount($project->indirect_commission, currencyFormat: false) }}</p>
                        <p class="mp-stat-lbl">Indirect bonus.</p>
                    </div>
                </div>

                {{-- details --}}
                <div class="mp-details">
                    @if($project->pairing_per_day > 0)
                    <div class="mp-detail-row">
                        <span class="mp-detail-lbl"><i class="las la-code-branch"></i> Pairing/Day</span>
                        <span class="mp-detail-val mp-detail-val--green">{{ $sym }}{{ showAmount($project->pairing_per_day, currencyFormat: false) }}</span>
                    </div>
                    @endif
                    @if($project->cash_back > 0)
                    <div class="mp-detail-row">
                        <span class="mp-detail-lbl"><i class="las la-percentage"></i> Cash Back</span>
                        <span class="mp-detail-val">{{ showAmount($project->cash_back, currencyFormat: false) }}%</span>
                    </div>
                    @endif
                    @if($project->upgrade_bonus > 0)
                    <div class="mp-detail-row">
                        <span class="mp-detail-lbl"><i class="las la-arrow-circle-up"></i> Upgrade Bonus</span>
                        <span class="mp-detail-val">{{ showAmount($project->upgrade_bonus, currencyFormat: false) }}%</span>
                    </div>
                    @endif
                    @if($project->monthly_maintenance > 0)
                    <div class="mp-detail-row">
                        <span class="mp-detail-lbl"><i class="las la-calendar-check"></i> Monthly Fee</span>
                        <span class="mp-detail-val">{{ $sym }}{{ showAmount($project->monthly_maintenance, currencyFormat: false) }}</span>
                    </div>
                    @endif
                    @if($project->description)
                    <div class="mp-detail-row" style="align-items:flex-start;">
                        <span class="mp-detail-lbl" style="min-width:0;flex:1;line-height:1.5;">
                            <i class="las la-info-circle"></i>
                            {{ Str::limit($project->description, 80) }}
                        </span>
                    </div>
                    @endif
                </div>

                {{-- footer / CTA --}}
                <div class="mp-card-footer">
                    @if($isCurrent)
                        <div class="mp-current-tag">
                            <i class="las la-check-double"></i>
                            Active Plan
                        </div>
                    @elseif(($isUpgrade || $isFirst) && $upgradeAllowed)
                        <button
                            class="mp-btn-upgrade {{ !$canAfford ? 'mp-btn-upgrade--low' : '' }}"
                            type="button"
                            data-id="{{ $project->id }}"
                            data-title="{{ e($project->title) }}"
                            data-icon="{{ e($project->icon) }}"
                            data-color="{{ e($project->color) }}"
                            data-amount="{{ $project->amount }}"
                            data-cost="{{ $cost }}"
                            data-pv="{{ $project->pv }}"
                            data-direct="{{ $project->direct_commission }}"
                            data-indirect="{{ $project->indirect_commission }}"
                            data-upgrade-bonus="{{ $project->upgrade_bonus }}"
                            data-can-afford="{{ $canAfford ? '1' : '0' }}"
                            onclick="mpOpenModal(this)"
                        >
                            <i class="las la-arrow-circle-up"></i>
                            @if($isFirst)
                                Subscribe &mdash; {{ $sym }}{{ showAmount($cost, currencyFormat: false) }}
                            @else
                                Upgrade &mdash; {{ $sym }}{{ showAmount($cost, currencyFormat: false) }}
                            @endif
                        </button>

                        @if(!$canAfford)
                            <div class="mp-insufficient">
                                <i class="las la-exclamation-triangle"></i>
                                Need {{ $sym }}{{ showAmount($cost - $visaBalance, currencyFormat: false) }} more in VISA wallet
                            </div>
                        @else
                            <div class="mp-cost-diff">
                                You pay only the <strong>difference</strong> &mdash; {{ $sym }}{{ showAmount($cost, currencyFormat: false) }}
                            </div>
                        @endif
                    @else
                        <button class="mp-btn-upgrade" disabled type="button">
                            <i class="las la-lock"></i> Not Available
                        </button>
                    @endif
                </div>

            </div>
        @endforeach
    </div>
@endif

{{-- ── Upgrade confirmation modal ────────────────────────────── --}}
<div class="mp-overlay" id="mpOverlay" role="dialog" aria-modal="true" aria-labelledby="mpModalTitle">
    <div class="mp-modal">

        <div class="mp-modal-head">
            <div class="mp-modal-head-icon" id="mpModalIcon">
                <i class="las la-arrow-circle-up"></i>
            </div>
            <div>
                <p class="mp-modal-head-title" id="mpModalTitle">Confirm Upgrade</p>
                <p class="mp-modal-head-sub" id="mpModalSub">Review the details below</p>
            </div>
            <button class="mp-modal-close" type="button" onclick="mpCloseModal()" aria-label="Close">
                <i class="las la-times"></i>
            </button>
        </div>

        <div class="mp-modal-body">

            {{-- VISA balance --}}
            <div class="mp-visa-row">
                <span class="mp-visa-row-label">
                    <i class="las la-wallet"></i> VISA Wallet Balance
                </span>
                <span class="mp-visa-row-val">
                    {{ $sym }}{{ showAmount($visaBalance, currencyFormat: false) }}
                </span>
            </div>

            {{-- Cost breakdown --}}
            <div class="mp-breakdown">
                <div class="mp-breakdown-row">
                    <span class="mp-breakdown-lbl">New project price</span>
                    <span class="mp-breakdown-val" id="mpBreakProjectAmt">&mdash;</span>
                </div>
                @if($currentProject)
                <div class="mp-breakdown-row">
                    <span class="mp-breakdown-lbl">Your current plan ({{ $currentProject->title }})</span>
                    <span class="mp-breakdown-val" id="mpBreakCurrentAmt">
                        &minus; {{ $sym }}{{ showAmount((float)$currentProject->amount, currencyFormat: false) }}
                    </span>
                </div>
                @endif
                <div class="mp-breakdown-row">
                    <span class="mp-breakdown-lbl">PV gain</span>
                    <span class="mp-breakdown-val" id="mpBreakPv">&mdash;</span>
                </div>
                <div class="mp-breakdown-row">
                    <span class="mp-breakdown-lbl">Sponsor upgrade bonus</span>
                    <span class="mp-breakdown-val" id="mpBreakBonus">&mdash;</span>
                </div>
                <div class="mp-breakdown-row mp-breakdown-total">
                    <span class="mp-breakdown-lbl">You pay</span>
                    <span class="mp-breakdown-val" id="mpBreakTotal">&mdash;</span>
                </div>
            </div>

            {{-- Insufficient warning --}}
            <div class="mp-modal-warn" id="mpWarn" style="display:none;">
                <i class="las la-exclamation-triangle" style="font-size:1.1rem;flex-shrink:0;margin-top:1px;"></i>
                <span id="mpWarnText">Insufficient VISA balance to complete this upgrade.</span>
            </div>

            {{-- Action buttons --}}
            <div class="mp-modal-actions">
                <button class="mp-modal-cancel" type="button" onclick="mpCloseModal()">Cancel</button>
                <form id="mpUpgradeForm" method="POST" action="{{ route('user.project.upgrade') }}" style="flex:2;display:flex;">
                    @csrf
                    <input type="hidden" name="project_id" id="mpProjectIdInput">
                    <button class="mp-modal-confirm" id="mpConfirmBtn" type="submit">
                        <i class="las la-arrow-circle-up"></i>
                        Confirm Upgrade
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection

@push('script')
<script>
(function () {
    'use strict';

    var SYM        = '{{ $sym }}';
    var VISA_BAL   = {{ $visaBalance }};
    var CUR_AMT    = {{ $currentProject ? (float)$currentProject->amount : 0 }};
    var CUR_PV     = {{ $currentProject ? (float)$currentProject->pv : 0 }};

    function fmt(n) {
        return SYM + parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function fmtN(n) {
        return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    window.mpOpenModal = function (btn) {
        var id           = btn.getAttribute('data-id');
        var title        = btn.getAttribute('data-title');
        var icon         = btn.getAttribute('data-icon');
        var color        = btn.getAttribute('data-color');
        var amount       = parseFloat(btn.getAttribute('data-amount'));
        var cost         = parseFloat(btn.getAttribute('data-cost'));
        var pv           = parseFloat(btn.getAttribute('data-pv'));
        var upgBonus     = parseFloat(btn.getAttribute('data-upgrade-bonus'));
        var canAfford    = btn.getAttribute('data-can-afford') === '1';

        var pvDiff       = Math.max(0, pv - CUR_PV);
        var bonusAmt     = upgBonus > 0 ? (upgBonus / 100 * cost) : 0;

        // Populate modal head
        document.getElementById('mpModalTitle').textContent = 'Upgrade to ' + title;
        document.getElementById('mpModalSub').textContent   = canAfford
            ? 'Review the cost breakdown below and confirm.'
            : 'You do not have enough VISA balance for this upgrade.';
        var iconEl = document.getElementById('mpModalIcon');
        iconEl.innerHTML                      = '<i class="' + icon + '"></i>';
        iconEl.parentElement.style.background = 'linear-gradient(135deg,' + color + ', ' + shadeColor(color, -20) + ')';

        // Breakdown
        document.getElementById('mpBreakProjectAmt').textContent = fmt(amount);
        document.getElementById('mpBreakPv').textContent         = '+' + fmtN(pvDiff) + ' PV';
        document.getElementById('mpBreakBonus').textContent      = upgBonus > 0
            ? fmt(bonusAmt) + ' (' + fmtN(upgBonus) + '%) to sponsor'
            : 'None';
        document.getElementById('mpBreakTotal').textContent      = fmt(cost);

        // Hidden input
        document.getElementById('mpProjectIdInput').value = id;

        // Warn / confirm state
        var warn    = document.getElementById('mpWarn');
        var confirm = document.getElementById('mpConfirmBtn');
        if (!canAfford) {
            var deficit = cost - VISA_BAL;
            document.getElementById('mpWarnText').textContent =
                'You need ' + fmt(deficit) + ' more in your VISA wallet to complete this upgrade.';
            warn.style.display    = 'flex';
            confirm.disabled      = true;
        } else {
            warn.style.display    = 'none';
            confirm.disabled      = false;
        }

        document.getElementById('mpOverlay').classList.add('active');
        document.body.style.overflow = 'hidden';
    };

    window.mpCloseModal = function () {
        document.getElementById('mpOverlay').classList.remove('active');
        document.body.style.overflow = '';
    };

    // Close on backdrop click
    document.getElementById('mpOverlay').addEventListener('click', function (e) {
        if (e.target === this) { mpCloseModal(); }
    });

    // Close on Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { mpCloseModal(); }
    });

    // Prevent double-submit
    document.getElementById('mpUpgradeForm').addEventListener('submit', function () {
        var btn = document.getElementById('mpConfirmBtn');
        btn.disabled      = true;
        btn.innerHTML     = '<i class="las la-circle-notch la-spin"></i> Processing…';
    });

    // Hex colour shade helper
    function shadeColor(hex, pct) {
        var num = parseInt(hex.replace('#', ''), 16);
        var r   = Math.min(255, Math.max(0, (num >> 16) + pct));
        var g   = Math.min(255, Math.max(0, ((num >> 8) & 0x00FF) + pct));
        var b   = Math.min(255, Math.max(0, (num & 0x0000FF) + pct));
        return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
    }
})();
</script>
@endpush

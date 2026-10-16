@extends($activeTemplate . 'layouts.master')
@section('content')
<div class="nc-wrap" id="ncWrap">
    <br/>
{{-- ══════════════════════════════════════════════════════
     HERO STRIP
══════════════════════════════════════════════════════ --}}
<div class="bt-hero">
    <div class="bt-hero-grid">
        <div class="bt-hero-stat">
            <p class="bt-hero-stat-label">Total Transferable</p>
            <p class="bt-hero-stat-value">{{ showAmount($totalTransferable) }}</p>
        </div>
        <div class="bt-hero-divider"></div>
        <div class="bt-hero-stat">
            <p class="bt-hero-stat-label">Money Box Balance</p>
            <p class="bt-hero-stat-value">{{ showAmount($user->balance) }}</p>
        </div>
        <div class="bt-hero-cta">
            @if($totalTransferable > 0)
            <button type="button" class="bt-btn-all" onclick="openAllModal()">
                Transfer All
                <i class="las la-arrow-right"></i>
            </button>
            @else
            <span class="bt-hero-empty">No balance available</span>
            @endif
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     BONUS WALLET LIST
══════════════════════════════════════════════════════ --}}
<div class="bt-card">
    <div class="bt-card-head">
        <p class="bt-card-title">
            <i class="las la-coins"></i> Bonus Wallets
        </p>
        <p class="bt-card-subtitle">Select a bonus to transfer it to your Money Box</p>
    </div>

    <div class="bt-list">
        @foreach($bonusFields as $field => $cfg)
        @php
            $balance         = (float) ($user->$field ?? 0);
            $isPairing       = !empty($cfg['requires_purchase']);
            $isLocked        = $isPairing && !$hasPurchasedThisMonth;
            $isAcbField      = !empty($cfg['requires_acb']);
            $isAutoshipField = !empty($cfg['is_autoship']);
            $hasBalance      = $balance > 0;
            // ACB members always see ACB row at zero; autoship and others only when balance > 0
            if (!$hasBalance && !($isAcbField && ($isAcb ?? false))) continue;
        @endphp

        {{-- Welcome Packages inject before ACB row --}}
        @if($isAcbField && $welcomePackages->count())
        <div class="wp-section">
            <div class="wp-section-label">
                <i class="las la-gift"></i>
                Welcome Packages
                <span class="wp-count-badge">{{ $welcomePackages->count() }}</span>
            </div>
            @foreach($welcomePackages as $wp)
            <div class="wp-gift-card {{ $wp->isRedeemed() ? 'wp-gift-card--redeemed' : '' }}">
                {{-- Decorative ribbon bars --}}
                <div class="wp-ribbon-h"></div>
                <div class="wp-ribbon-v"></div>

                <div class="wp-gift-inner">
                    {{-- Top: icon + title + status --}}
                    <div class="wp-gift-top">
                        <div class="wp-gift-icon-wrap">
                            <i class="las la-gift"></i>
                        </div>
                        <div class="wp-gift-title-wrap">
                            <p class="wp-gift-title">{{ ucfirst($wp->source) }} Welcome Package</p>
                            <p class="wp-gift-amount">{{ showAmount($wp->amount) }}</p>
                        </div>
                        <div class="wp-status-badge {{ $wp->isRedeemed() ? 'wp-status-badge--done' : 'wp-status-badge--pending' }}">
                            @if($wp->isRedeemed())
                                <i class="las la-check-circle"></i> Redeemed
                            @else
                                <i class="las la-hourglass-half"></i> Pending
                            @endif
                        </div>
                    </div>

                    {{-- Tear line --}}
                    <div class="wp-tear"></div>

                    {{-- Code section --}}
                    <div class="wp-code-section">
                        <span class="wp-code-label">Redemption Code</span>
                        <span class="wp-code">{{ $wp->code }}</span>
                    </div>

                    {{-- Footer hint --}}
                    @if(!$wp->isRedeemed())
                    <p class="wp-gift-hint">
                        <i class="las la-map-marker-alt"></i>
                        Present this code to any authorized stockist to claim your gift package.
                    </p>
                    @else
                    <p class="wp-gift-hint wp-gift-hint--done">
                        <i class="las la-check"></i>
                        Redeemed {{ $wp->redeemed_at?->format('M d, Y') }}
                    </p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div class="bt-row {{ $isLocked ? 'bt-row--locked' : '' }} {{ ($isAcbField && !$hasBalance) ? 'bt-row--zero' : '' }}">
            {{-- Icon --}}
            <div class="bt-row-icon {{ ($isPairing && !$isAutoshipField) ? 'bt-row-icon--pairing' : '' }}"
                 style="
                    {{ $isAcbField      ? 'background:linear-gradient(135deg,#DC2626,#991B1B);color:#fff;' : '' }}
                    {{ $isAutoshipField ? 'background:linear-gradient(135deg,#0891B2,#0E7490);color:#fff;' : '' }}
                 ">
                <i class="{{ $cfg['icon'] }}"></i>
            </div>

            {{-- Info --}}
            <div class="bt-row-info">
                <span class="bt-row-name">
                    {{ $cfg['label'] }}
                    @if($isPairing && !$isAutoshipField)
                        @if($hasPurchasedThisMonth)
                            <span class="bt-pill bt-pill--green"><i class="las la-check"></i> Eligible</span>
                        @else
                            <span class="bt-pill bt-pill--amber"><i class="las la-lock"></i> Purchase Required</span>
                        @endif
                    @endif
                    @if($isAutoshipField)
                        @if($hasPurchasedThisMonth)
                            <span class="bt-pill" style="background:#cffafe;color:#0e7490;border:1px solid #a5f3fc;font-size:.65rem;"><i class="las la-check"></i> Unlocked</span>
                        @else
                            <span class="bt-pill bt-pill--amber"><i class="las la-lock"></i> {{ $autoshipMinPv }} PV Required</span>
                        @endif
                    @endif
                    @if($isAcbField)
                        <span class="bt-pill" style="background:#fef9c3;color:#854d0e;border:1px solid #fde68a;font-size:.65rem;"><i class="las la-star"></i> ACB Member</span>
                    @endif
                </span>
                <span class="bt-row-amount {{ !$hasBalance ? 'bt-row-amount--zero' : '' }}">
                    {{ showAmount($balance) }}
                </span>
                @if($isAutoshipField)
                <span style="font-size:.7rem;color:#6b7280;display:block;margin-top:.2rem;line-height:1.4;">
                    20% of Matching Bonus held here
                    @if(!$hasPurchasedThisMonth)
                        — <span style="color:#d97706;font-weight:600;">buy {{ $autoshipMinPv }} PV of products this month to transfer ({{ $autoshipPv + 0 }} of {{ $autoshipMinPv }} PV so far)</span>
                    @else
                        — <span style="color:#059669;font-weight:600;">ready to transfer</span>
                    @endif
                </span>
                @endif
            </div>

            {{-- Action --}}
            <div class="bt-row-action">
                @if($isLocked)
                    <button type="button" class="bt-btn-check" onclick="openPairingCheck()">
                        <i class="las la-shield-alt"></i> Check
                    </button>
                @elseif($isAcbField && !$hasBalance)
                    <button type="button" class="bt-btn-transfer" disabled style="opacity:.4;cursor:not-allowed;">
                        No Balance
                    </button>
                @else
                    <button type="button" class="bt-btn-transfer"
                        data-field="{{ $field }}"
                        data-label="{{ e($cfg['label']) }}"
                        data-balance="{{ $balance }}"
                        onclick="handleTransferClick(this)">
                        Transfer
                    </button>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Info note --}}
    <div class="bt-card-foot">
        <i class="las la-info-circle"></i>
        All bonuses transfer directly into your Money Box (main wallet balance).
        Matching Bonus requires at least one product purchase per calendar month.
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     AUTHORIZE TRANSFER MODAL  (single bonus)
══════════════════════════════════════════════════════ --}}
<div class="bt-overlay" id="transferOverlay" onclick="closeTransferModal(event)">
    <div class="bt-modal" role="dialog" aria-modal="true" aria-labelledby="authModalTitle">

        <div class="bt-modal-head">
            <div class="bt-modal-lock">
                <i class="las la-shield-alt"></i>
            </div>
            <div class="bt-modal-head-text">
                <h3 class="bt-modal-title" id="authModalTitle">Authorize Transfer</h3>
                <p class="bt-modal-desc">Confirm your identity to proceed</p>
            </div>
            <button class="bt-modal-x" onclick="closeTransferModal()" aria-label="Close">&times;</button>
        </div>

        <form action="{{ route('user.bonus.transfer.submit') }}" method="POST" id="singleTransferForm">
            @csrf
            <input type="hidden" name="bonus_field" id="tf_field">
            <input type="hidden" name="amount"      id="tf_hidden_amount">

            <div class="bt-modal-body">
                {{-- Transfer summary --}}
                <div class="bt-summary">
                    <div class="bt-summary-row">
                        <span class="bt-summary-key">From</span>
                        <span class="bt-summary-val" id="tf_label_display">—</span>
                    </div>
                    <div class="bt-summary-row">
                        <span class="bt-summary-key">Amount</span>
                        <div class="bt-amount-wrap">
                            <input type="number" id="tf_amount_input" step="0.01" min="0.01"
                                   class="bt-amount-input"
                                   oninput="syncAmount(this.value)">
                            <span class="bt-amount-max" id="tf_max_label"></span>
                        </div>
                    </div>
                    <div class="bt-summary-row">
                        <span class="bt-summary-key">To</span>
                        <span class="bt-summary-val">Money Box</span>
                    </div>
                </div>

                {{-- Password field --}}
                <div class="bt-pw-group">
                    <label class="bt-pw-label" for="tf_password">Account Password</label>
                    <div class="bt-pw-wrap">
                        <input type="password" name="password" id="tf_password"
                               class="bt-pw-input {{ $errors->has('password') && !session('reopen_all_modal') ? 'bt-pw-input--error' : '' }}"
                               placeholder="Enter your account password"
                               autocomplete="current-password">
                        <button type="button" class="bt-pw-eye" onclick="togglePw(this,'eye1')" tabindex="-1">
                            <i class="las la-eye" id="eye1"></i>
                        </button>
                    </div>
                    @if($errors->has('password') && !session('reopen_all_modal'))
                    <p class="bt-pw-error"><i class="las la-exclamation-circle"></i> {{ $errors->first('password') }}</p>
                    @endif
                </div>
            </div>

            <div class="bt-modal-foot">
                <button type="button" class="bt-btn-cancel" onclick="closeTransferModal()">Cancel</button>
                <button type="submit" class="bt-btn-authorize">
                    <i class="las la-lock-open"></i> Authorize & Transfer
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     AUTHORIZE TRANSFER MODAL  (transfer all)
══════════════════════════════════════════════════════ --}}
<div class="bt-overlay" id="allOverlay" onclick="closeAllModal(event)">
    <div class="bt-modal" role="dialog" aria-modal="true" aria-labelledby="allModalTitle">

        <div class="bt-modal-head">
            <div class="bt-modal-lock">
                <i class="las la-paper-plane"></i>
            </div>
            <div class="bt-modal-head-text">
                <h3 class="bt-modal-title" id="allModalTitle">Transfer All Bonuses</h3>
                <p class="bt-modal-desc">Confirm your identity to proceed</p>
            </div>
            <button class="bt-modal-x" onclick="closeAllModal()" aria-label="Close">&times;</button>
        </div>

        <form action="{{ route('user.bonus.transfer.all') }}" method="POST" id="allTransferForm">
            @csrf

            <div class="bt-modal-body">
                {{-- Summary breakdown --}}
                <div class="bt-summary">
                    @foreach($bonusFields as $field => $cfg)
                    @php
                        $bal       = (float) ($user->$field ?? 0);
                        $isPair    = !empty($cfg['requires_purchase']);
                        $excluded  = $isPair && !$hasPurchasedThisMonth;
                    @endphp
                    @if($bal > 0)
                    <div class="bt-summary-row {{ $excluded ? 'bt-summary-row--excluded' : '' }}">
                        <span class="bt-summary-key">
                            {{ $cfg['label'] }}
                            @if($excluded)
                                <span class="bt-pill bt-pill--grey" style="font-size:.65rem">Skipped</span>
                            @endif
                        </span>
                        <span class="bt-summary-val {{ $excluded ? 'bt-summary-val--excluded' : '' }}">
                            {{ showAmount($bal) }}
                        </span>
                    </div>
                    @endif
                    @endforeach
                    <div class="bt-summary-row bt-summary-total">
                        <span class="bt-summary-key">Total</span>
                        <span class="bt-summary-val">{{ showAmount($totalTransferable) }}</span>
                    </div>
                </div>

                @if(!$hasPurchasedThisMonth && ($user->autoship ?? 0) > 0)
                <div class="bt-notice">
                    <i class="las la-exclamation-triangle"></i>
                    Autoship is excluded — buy {{ $autoshipMinPv }} PV of products this month to include it ({{ $autoshipPv + 0 }} PV so far).
                </div>
                @endif

                {{-- Password field --}}
                <div class="bt-pw-group">
                    <label class="bt-pw-label" for="all_password">Account Password</label>
                    <div class="bt-pw-wrap">
                        <input type="password" name="password" id="all_password"
                               class="bt-pw-input {{ $errors->has('password') && session('reopen_all_modal') ? 'bt-pw-input--error' : '' }}"
                               placeholder="Enter your account password"
                               autocomplete="current-password">
                        <button type="button" class="bt-pw-eye" onclick="togglePw(this,'eye2')" tabindex="-1">
                            <i class="las la-eye" id="eye2"></i>
                        </button>
                    </div>
                    @if($errors->has('password') && session('reopen_all_modal'))
                    <p class="bt-pw-error"><i class="las la-exclamation-circle"></i> {{ $errors->first('password') }}</p>
                    @endif
                </div>
            </div>

            <div class="bt-modal-foot">
                <button type="button" class="bt-btn-cancel" onclick="closeAllModal()">Cancel</button>
                <button type="submit" class="bt-btn-authorize">
                    <i class="las la-lock-open"></i> Authorize & Transfer All
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     PAIRING ELIGIBILITY MODAL
══════════════════════════════════════════════════════ --}}
<div class="bt-overlay" id="pairingOverlay" onclick="closePairingModal(event)">
    <div class="bt-modal" role="dialog" aria-modal="true">

        <div class="bt-modal-head">
            <div class="bt-modal-lock bt-modal-lock--amber">
                <i class="las la-sync-alt"></i>
            </div>
            <div class="bt-modal-head-text">
                <h3 class="bt-modal-title">Autoship</h3>
                <p class="bt-modal-desc">Checking the PV of this month's purchases</p>
            </div>
            <button class="bt-modal-x" onclick="closePairingModal()">&times;</button>
        </div>

        <div class="bt-modal-body">
            {{-- Loading --}}
            <div id="pState_loading" class="bt-state">
                <div class="bt-spinner"></div>
                <p class="bt-state-text">Checking your records&hellip;</p>
            </div>

            {{-- Eligible --}}
            <div id="pState_eligible" class="bt-state bt-state--hidden">
                <div class="bt-state-icon bt-state-icon--green"><i class="las la-check-circle"></i></div>
                <p class="bt-state-heading bt-state-heading--green">You're Eligible</p>
                <div class="bt-summary" id="eligibleDetails" style="margin-top:1rem"></div>

                <div class="bt-pw-group" style="margin-top:1.25rem">
                    <label class="bt-pw-label" for="pair_password">Account Password</label>
                    <div class="bt-pw-wrap">
                        <input type="password" id="pair_password" class="bt-pw-input"
                               placeholder="Enter your account password" autocomplete="current-password">
                        <button type="button" class="bt-pw-eye" onclick="togglePw(this,'eye3')" tabindex="-1">
                            <i class="las la-eye" id="eye3"></i>
                        </button>
                    </div>
                </div>

                <form action="{{ route('user.bonus.transfer.submit') }}" method="POST" id="pairingTransferForm">
                    @csrf
                    <input type="hidden" name="bonus_field" value="autoship">
                    <input type="hidden" name="amount" id="pairingAmountInput"
                           value="{{ number_format($user->autoship ?? 0, 2, '.', '') }}">
                    <input type="hidden" name="password" id="pairingPasswordHidden">
                </form>
            </div>

            {{-- Ineligible --}}
            <div id="pState_ineligible" class="bt-state bt-state--hidden">
                <div class="bt-state-icon bt-state-icon--red"><i class="las la-times-circle"></i></div>
                <p class="bt-state-heading bt-state-heading--red">Not Eligible Yet</p>
                <p class="bt-state-msg" id="pairingErrMsg"></p>
                <div class="bt-notice bt-notice--info">
                    <i class="las la-info-circle"></i>
                    Autoship unlocks once the products you buy in a calendar month add up to {{ $autoshipMinPv }} PV. Anything still held at month end is forfeited.
                </div>
            </div>
        </div>

        <div class="bt-modal-foot" id="pairingFooter" style="display:none">
            <button type="button" class="bt-btn-cancel" onclick="closePairingModal()">Cancel</button>
            <button type="button" class="bt-btn-authorize" id="pairingSubmitBtn" onclick="submitPairing()">
                <i class="las la-lock-open"></i> Authorize & Transfer
            </button>
        </div>

        <div class="bt-modal-foot" id="pairingShopFooter" style="display:none">
            <button type="button" class="bt-btn-cancel" onclick="closePairingModal()">Maybe Later</button>
            <a href="{{ route('user.products') }}" class="bt-btn-authorize">
                <i class="las la-shopping-cart"></i> Visit Shop
            </a>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     TRANSFER HISTORY
══════════════════════════════════════════════════════ --}}
<div class="bt-card" style="margin-top:1.5rem">
    <div class="bt-card-head">
        <p class="bt-card-title"><i class="las la-history"></i> Transfer History</p>
    </div>

    @if($transfers->count())
    <div class="bt-list bt-list--table">
        <div class="bt-thead">
            <span>#</span>
            <span>Source</span>
            <span>Amount</span>
            <span>Status</span>
            <span>Date</span>
        </div>
        @foreach($transfers as $t)
        <div class="bt-trow">
            <span class="bt-tc bt-tc--num">{{ ($transfers->currentPage()-1) * $transfers->perPage() + $loop->iteration }}</span>
            <span class="bt-tc">
                <span class="bt-source-tag">{{ $t->source_label }}</span>
            </span>
            <span class="bt-tc bt-tc--amount">{{ showAmount($t->amount) }}</span>
            <span class="bt-tc">
                <span class="bt-status-tag bt-status-tag--{{ strtolower($t->status_text) }}">
                    {{ $t->status_text }}
                </span>
            </span>
            <span class="bt-tc bt-tc--date">{{ $t->created_at->format('M d, Y') }}<br>
                <small>{{ $t->created_at->format('h:i A') }}</small>
            </span>
        </div>
        @endforeach
    </div>
    <div style="padding:.75rem 1.25rem">{{ $transfers->links() }}</div>
    @else
    <div class="bt-empty">
        <i class="las la-inbox"></i>
        <p>No transfers yet. Use the buttons above to move your bonuses into your Money Box.</p>
    </div>
    @endif
</div>

{{-- Data bridge: PHP → JS without Blade inside < script > --}}
<div id="btAutoReopen"
     data-pw-error="{{ ($errors->has('password') && !session('reopen_all_modal')) ? '1' : '0' }}"
     data-all-error="{{ session('reopen_all_modal') ? '1' : '0' }}"
     data-field="{{ old('bonus_field') }}"
     data-amount="{{ old('amount', 0) }}"
     data-labels="{{ json_encode(array_map(fn($c) => $c['label'], $bonusFields)) }}"
     data-keys="{{ json_encode(array_keys($bonusFields)) }}"
     style="display:none">
    </div>
</div>
@endsection

@push('style')
<style>
:root {
    --bt-green:   var(--bk-primary, #0D5C2E);
    --bt-accent:  var(--bk-accent,  #16A34A);
    --bt-text:    var(--bk-text,    #1A1F2E);
    --bt-muted:   var(--bk-muted,   #6B7280);
    --bt-border:  #E5E9EF;
    --bt-surface: #F8FAFC;
    --bt-radius:  14px;
    --bt-shadow:  0 2px 16px rgba(0,0,0,.07);
    --bt-trans:   .18s ease;
}

/* ── Hero ──────────────────────────────────────────── */
.bt-hero {
    background: linear-gradient(135deg, var(--bt-green) 0%, #16a34a 100%);
    border-radius: var(--bt-radius);
    padding: 1.75rem 2rem;
    margin-bottom: 1.25rem;
    position: relative;
    overflow: hidden;
}
.bt-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 180px; height: 180px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    pointer-events: none;
}
.bt-hero-grid {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}
.bt-hero-stat { flex: 1; min-width: 130px; }
.bt-hero-stat-label {
    font-size: .72rem;
    font-weight: 600;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: rgba(255,255,255,.65);
    margin: 0 0 .3rem;
}
.bt-hero-stat-value {
    font-size: 1.7rem;
    font-weight: 800;
    color: #fff;
    margin: 0;
    letter-spacing: -.02em;
}
.bt-hero-divider {
    width: 1px;
    height: 44px;
    background: rgba(255,255,255,.2);
    flex-shrink: 0;
}
.bt-hero-cta { margin-left: auto; flex-shrink: 0; }
.bt-btn-all {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .7rem 1.4rem;
    background: #fff;
    color: var(--bt-green);
    font-size: .83rem;
    font-weight: 700;
    border: none;
    border-radius: 50px;
    cursor: pointer;
    transition: var(--bt-trans);
    box-shadow: 0 4px 14px rgba(0,0,0,.18);
    white-space: nowrap;
}
.bt-btn-all:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,0,0,.22); }
.bt-hero-empty {
    font-size: .8rem;
    color: rgba(255,255,255,.6);
    font-style: italic;
}

/* ── Card shell ────────────────────────────────────── */
.bt-card {
    background: #fff;
    border-radius: var(--bt-radius);
    box-shadow: var(--bt-shadow);
    overflow: hidden;
}
.bt-card-head {
    padding: 1.1rem 1.4rem .8rem;
    border-bottom: 1px solid var(--bt-border);
}
.bt-card-title {
    font-size: .82rem;
    font-weight: 700;
    letter-spacing: .05em;
    text-transform: uppercase;
    color: var(--bt-muted);
    margin: 0 0 .15rem;
    display: flex;
    align-items: center;
    gap: .4rem;
}
.bt-card-title i { font-size: 1rem; }
.bt-card-subtitle {
    font-size: .78rem;
    color: var(--bt-muted);
    margin: 0;
}
.bt-card-foot {
    padding: .8rem 1.4rem;
    border-top: 1px solid var(--bt-border);
    font-size: .75rem;
    color: var(--bt-muted);
    display: flex;
    gap: .45rem;
    align-items: flex-start;
    line-height: 1.5;
}
.bt-card-foot i { font-size: .95rem; flex-shrink: 0; margin-top: 1px; }

/* ── Bonus row list ────────────────────────────────── */
.bt-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.4rem;
    border-bottom: 1px solid var(--bt-border);
    transition: background var(--bt-trans);
}
.bt-row:last-child { border-bottom: none; }
.bt-row:hover { background: var(--bt-surface); }
.bt-row--zero { opacity: .6; }
.bt-row--locked { opacity: .75; }

/* Row icon */
.bt-row-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: #ECFDF5;
    color: #059669;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.bt-row-icon--pairing {
    background: #FFFBEB;
    color: #D97706;
}

/* Row info */
.bt-row-info {
    flex: 1;
    min-width: 0;
}
.bt-row-name {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: .4rem;
    font-size: .83rem;
    font-weight: 600;
    color: var(--bt-text);
    margin-bottom: .15rem;
}
.bt-row-amount {
    display: block;
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--bt-text);
    letter-spacing: -.01em;
}
.bt-row-amount--zero { color: var(--bt-muted); font-weight: 500; }

/* Pills */
.bt-pill {
    display: inline-flex;
    align-items: center;
    gap: .25rem;
    font-size: .62rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    letter-spacing: .03em;
    text-transform: uppercase;
}
.bt-pill--green { background: #D1FAE5; color: #065F46; }
.bt-pill--amber { background: #FEF3C7; color: #92400E; }
.bt-pill--grey  { background: #F3F4F6; color: #6B7280; }

/* Row action */
.bt-row-action { flex-shrink: 0; }
.bt-btn-transfer {
    padding: .4rem 1rem;
    background: var(--bt-green);
    color: #fff;
    border: none;
    border-radius: 50px;
    font-size: .78rem;
    font-weight: 700;
    cursor: pointer;
    transition: var(--bt-trans);
    white-space: nowrap;
}
.bt-btn-transfer:hover { background: #0a4a24; transform: scale(1.04); }
.bt-btn-check {
    padding: .4rem .9rem;
    background: #FEF3C7;
    color: #92400E;
    border: 1.5px solid #FDE68A;
    border-radius: 50px;
    font-size: .78rem;
    font-weight: 700;
    cursor: pointer;
    transition: var(--bt-trans);
    display: inline-flex;
    align-items: center;
    gap: .3rem;
    white-space: nowrap;
}
.bt-btn-check:hover { background: #FDE68A; }
.bt-no-balance { color: #D1D5DB; font-size: .9rem; }

/* ── History table (list-based) ────────────────────── */
.bt-thead, .bt-trow {
    display: grid;
    grid-template-columns: 2rem 1fr 7rem 6rem 7rem;
    align-items: center;
    gap: .75rem;
    padding: .7rem 1.4rem;
}
.bt-thead {
    background: var(--bt-surface);
    border-bottom: 1px solid var(--bt-border);
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--bt-muted);
}
.bt-trow {
    border-bottom: 1px solid var(--bt-border);
    font-size: .82rem;
    color: var(--bt-text);
    transition: background var(--bt-trans);
}
.bt-trow:last-child { border-bottom: none; }
.bt-trow:hover { background: var(--bt-surface); }
.bt-tc--num   { color: var(--bt-muted); font-size: .75rem; }
.bt-tc--amount { font-weight: 800; color: #059669; }
.bt-tc--date  { font-size: .75rem; color: var(--bt-muted); line-height: 1.4; }

.bt-source-tag {
    background: #EDE9FE;
    color: #5B21B6;
    padding: 2px 9px;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 700;
}
.bt-status-tag {
    padding: 2px 9px;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 700;
    text-transform: capitalize;
}
.bt-status-tag--completed { background: #D1FAE5; color: #065F46; }
.bt-status-tag--pending   { background: #FEF3C7; color: #92400E; }
.bt-status-tag--failed    { background: #FEE2E2; color: #991B1B; }

.bt-empty {
    padding: 3rem 1.4rem;
    text-align: center;
    color: var(--bt-muted);
}
.bt-empty i { font-size: 2.5rem; opacity: .35; display: block; margin-bottom: .6rem; }
.bt-empty p { font-size: .85rem; margin: 0; }

/* ── Modal overlay ─────────────────────────────────── */
.bt-overlay {
    position: fixed;
    inset: 0;
    background: rgba(10,22,16,.52);
    backdrop-filter: blur(5px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    opacity: 0;
    pointer-events: none;
    transition: opacity .22s ease;
}
.bt-overlay.active {
    opacity: 1;
    pointer-events: all;
}

/* ── Modal ─────────────────────────────────────────── */
.bt-modal {
    background: #fff;
    border-radius: 18px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 20px 60px rgba(0,0,0,.18);
    transform: translateY(16px) scale(.97);
    transition: transform .28s cubic-bezier(.34,1.56,.64,1);
    overflow: hidden;
}
.bt-overlay.active .bt-modal {
    transform: translateY(0) scale(1);
}

.bt-modal-head {
    display: flex;
    align-items: center;
    gap: .9rem;
    padding: 1.25rem 1.4rem;
    border-bottom: 1px solid var(--bt-border);
    position: relative;
}
.bt-modal-lock {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: var(--bt-green);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
}
.bt-modal-lock--amber { background: linear-gradient(135deg,#F59E0B,#D97706); }
.bt-modal-head-text { flex: 1; min-width: 0; }
.bt-modal-title {
    font-size: .95rem;
    font-weight: 800;
    color: var(--bt-text);
    margin: 0 0 .1rem;
}
.bt-modal-desc {
    font-size: .75rem;
    color: var(--bt-muted);
    margin: 0;
}
.bt-modal-x {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #F3F4F6;
    border: none;
    font-size: 1rem;
    color: #6B7280;
    cursor: pointer;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--bt-trans);
}
.bt-modal-x:hover { background: #E5E7EB; color: var(--bt-text); }

.bt-modal-body { padding: 1.25rem 1.4rem; }
.bt-modal-foot {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: .6rem;
    padding: .9rem 1.4rem;
    border-top: 1px solid var(--bt-border);
    background: var(--bt-surface);
}

/* ── Summary block ─────────────────────────────────── */
.bt-summary {
    background: var(--bt-surface);
    border: 1px solid var(--bt-border);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 1.1rem;
}
.bt-summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: .65rem .9rem;
    border-bottom: 1px solid var(--bt-border);
    font-size: .83rem;
    gap: .5rem;
}
.bt-summary-row:last-child { border-bottom: none; }
.bt-summary-row--excluded { opacity: .5; }
.bt-summary-key { color: var(--bt-muted); font-weight: 500; flex-shrink: 0; }
.bt-summary-val { font-weight: 700; color: var(--bt-text); text-align: right; }
.bt-summary-val--excluded { text-decoration: line-through; }
.bt-summary-total { background: #ECFDF5; }
.bt-summary-total .bt-summary-key,
.bt-summary-total .bt-summary-val { color: #065F46; font-weight: 800; font-size: .88rem; }

/* Amount input inside summary */
.bt-amount-wrap { display: flex; flex-direction: column; align-items: flex-end; gap: .2rem; }
.bt-amount-input {
    width: 130px;
    padding: .35rem .55rem;
    border: 1.5px solid var(--bt-border);
    border-radius: 7px;
    font-size: .88rem;
    font-weight: 700;
    color: var(--bt-text);
    text-align: right;
    outline: none;
    transition: border-color .15s;
}
.bt-amount-input:focus { border-color: var(--bt-accent); }
.bt-amount-max { font-size: .68rem; color: var(--bt-muted); }

/* ── Password field ────────────────────────────────── */
.bt-pw-label {
    display: block;
    font-size: .75rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: var(--bt-muted);
    margin-bottom: .45rem;
}
.bt-pw-wrap {
    position: relative;
}
.bt-pw-input {
    width: 100%;
    padding: .65rem 2.6rem .65rem .85rem;
    border: 1.5px solid var(--bt-border);
    border-radius: 9px;
    font-size: .88rem;
    color: var(--bt-text);
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    background: #fff;
}
.bt-pw-input:focus {
    border-color: var(--bt-accent);
    box-shadow: 0 0 0 3px rgba(22,163,74,.1);
}
.bt-pw-input--error {
    border-color: #DC2626;
    box-shadow: 0 0 0 3px rgba(220,38,38,.1);
}
.bt-pw-eye {
    position: absolute;
    right: .7rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--bt-muted);
    cursor: pointer;
    font-size: 1.05rem;
    padding: .2rem;
    line-height: 1;
    transition: color .15s;
}
.bt-pw-eye:hover { color: var(--bt-text); }
.bt-pw-error {
    display: flex;
    align-items: center;
    gap: .35rem;
    font-size: .75rem;
    color: #DC2626;
    margin: .4rem 0 0;
}

/* ── Notice block ──────────────────────────────────── */
.bt-notice {
    background: #FFFBEB;
    border-left: 3px solid #F59E0B;
    border-radius: 0 8px 8px 0;
    padding: .65rem .9rem;
    font-size: .78rem;
    color: #92400E;
    display: flex;
    gap: .45rem;
    margin-bottom: 1rem;
    line-height: 1.5;
}
.bt-notice i { flex-shrink: 0; margin-top: 1px; font-size: .95rem; }
.bt-notice--info { background: #EFF6FF; border-left-color: #3B82F6; color: #1E40AF; }

/* ── Modal buttons ─────────────────────────────────── */
.bt-btn-cancel {
    padding: .55rem 1.1rem;
    background: none;
    border: 1.5px solid var(--bt-border);
    border-radius: 50px;
    font-size: .8rem;
    font-weight: 600;
    color: var(--bt-muted);
    cursor: pointer;
    transition: var(--bt-trans);
}
.bt-btn-cancel:hover { border-color: #9CA3AF; color: var(--bt-text); }
.bt-btn-authorize {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .55rem 1.2rem;
    background: var(--bt-green);
    color: #fff;
    border: none;
    border-radius: 50px;
    font-size: .82rem;
    font-weight: 700;
    cursor: pointer;
    transition: var(--bt-trans);
    text-decoration: none;
}
.bt-btn-authorize:hover { background: #0a4a24; color: #fff; transform: scale(1.03); }

/* ── Pairing eligibility states ────────────────────── */
.bt-state { text-align: center; padding: .5rem 0; }
.bt-state--hidden { display: none; }
.bt-spinner {
    width: 44px; height: 44px;
    border: 3px solid #E5E7EB;
    border-top-color: var(--bt-green);
    border-radius: 50%;
    animation: btSpin .7s linear infinite;
    margin: 0 auto 1rem;
}
@keyframes btSpin { to { transform: rotate(360deg); } }
.bt-state-text { font-size: .85rem; color: var(--bt-muted); }
.bt-state-icon {
    width: 58px; height: 58px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto .9rem;
    animation: btPop .3s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes btPop { from { transform: scale(0); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.bt-state-icon--green { background: #D1FAE5; color: #059669; }
.bt-state-icon--red   { background: #FEE2E2; color: #DC2626; }
.bt-state-heading { font-size: .95rem; font-weight: 800; margin: 0 0 .4rem; }
.bt-state-heading--green { color: #059669; }
.bt-state-heading--red   { color: #DC2626; }
.bt-state-msg { font-size: .82rem; color: var(--bt-muted); margin: 0 0 1rem; }

/* ── Responsive ────────────────────────────────────── */
@media (max-width: 767px) {
    .bt-hero-divider { display: none; }
    .bt-hero-grid { gap: .9rem; }
    .bt-hero-stat-value { font-size: 1.35rem; }
    .bt-hero-cta { width: 100%; }
    .bt-btn-all { width: 100%; justify-content: center; }

    .bt-thead { display: none; }
    .bt-trow {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto auto;
        gap: .35rem .75rem;
    }
    .bt-tc--num  { display: none; }
    .bt-tc--date { font-size: .72rem; }
}
@media (max-width: 480px) {
    .bt-row { gap: .75rem; padding: .9rem 1rem; }
    .bt-modal-head, .bt-modal-body, .bt-modal-foot { padding-left: 1rem; padding-right: 1rem; }
    .bt-hero { padding: 1.25rem 1rem; }
}
</style>
@endpush

@push('script')
<script>
(function () {
'use strict';

/* ── Helpers ─────────────────────────────────────── */
function show(id) { var el = document.getElementById(id); if (el) el.style.display = ''; }
function hide(id) { var el = document.getElementById(id); if (el) el.style.display = 'none'; }
function openOverlay(id)  { var el = document.getElementById(id); if (el) el.classList.add('active'); }
function closeOverlay(id) { var el = document.getElementById(id); if (el) el.classList.remove('active'); }

// app.blade.php line 165 rewrites named-input IDs to their name value,
// so always find password inputs via DOM traversal, never by ID.
function togglePw(btn, eyeId) {
    var wrap  = btn.closest('.bt-pw-wrap');
    var input = wrap ? wrap.querySelector('input') : null;
    var icon  = document.getElementById(eyeId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) { icon.classList.remove('la-eye'); icon.classList.add('la-eye-slash'); }
    } else {
        input.type = 'password';
        if (icon) { icon.classList.remove('la-eye-slash'); icon.classList.add('la-eye'); }
    }
}
window.togglePw = togglePw;

/* ── data-* bridge for transfer buttons ─────────── */
function handleTransferClick(btn) {
    openTransferModal(
        btn.dataset.field,
        btn.dataset.label,
        parseFloat(btn.dataset.balance) || 0
    );
}
window.handleTransferClick = handleTransferClick;

/* ── Single transfer modal ───────────────────────── */
function openTransferModal(field, label, balance) {
    var form = document.getElementById('singleTransferForm');
    // Named inputs: IDs are rewritten by app.blade.php — use name selectors
    form.querySelector('[name="bonus_field"]').value = field;
    form.querySelector('[name="amount"]').value      = balance.toFixed(2);
    form.querySelector('[name="password"]').value    = '';
    // Non-named elements keep their IDs
    document.getElementById('tf_label_display').textContent  = label;
    document.getElementById('tf_amount_input').value         = balance.toFixed(2);
    document.getElementById('tf_amount_input').max           = balance.toFixed(2);
    document.getElementById('tf_max_label').textContent      = 'Max: ' + formatNum(balance);
    openOverlay('transferOverlay');
    setTimeout(function () { form.querySelector('[name="password"]').focus(); }, 320);
}
function closeTransferModal(e) {
    if (e && e.target !== document.getElementById('transferOverlay')) return;
    closeOverlay('transferOverlay');
}
function syncAmount(val) {
    var form = document.getElementById('singleTransferForm');
    form.querySelector('[name="amount"]').value = val;
}
window.openTransferModal  = openTransferModal;
window.closeTransferModal = closeTransferModal;
window.syncAmount         = syncAmount;

/* ── Transfer All modal ──────────────────────────── */
function openAllModal() {
    var pw = document.querySelector('#allTransferForm [name="password"]');
    if (pw) pw.value = '';
    openOverlay('allOverlay');
    setTimeout(function () { var p = document.querySelector('#allTransferForm [name="password"]'); if (p) p.focus(); }, 320);
}
function closeAllModal(e) {
    if (e && e.target !== document.getElementById('allOverlay')) return;
    closeOverlay('allOverlay');
}
window.openAllModal  = openAllModal;
window.closeAllModal = closeAllModal;

/* ── Pairing check modal ─────────────────────────── */
function showPairingState(state) {
    ['loading','eligible','ineligible'].forEach(function(s) {
        var el = document.getElementById('pState_' + s);
        if (el) el.classList.toggle('bt-state--hidden', s !== state);
    });
    hide('pairingFooter');
    hide('pairingShopFooter');
    if (state === 'eligible')   show('pairingFooter');
    if (state === 'ineligible') show('pairingShopFooter');
}

function openPairingCheck() {
    document.getElementById('pair_password').value = '';
    openOverlay('pairingOverlay');
    showPairingState('loading');

    fetch('{{ route("user.bonus.transfer.check.purchase") }}', {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.eligible) {
            var det = document.getElementById('eligibleDetails');
            det.innerHTML = '';
            var rows = [
                { key: 'Month', val: data.month },
                { key: 'PV Bought', val: data.pv + ' of ' + data.min_pv + ' PV' },
                data.order ? { key: 'Latest Invoice', val: data.order.invoice } : null,
                data.order ? { key: 'Purchase Date', val: data.order.date } : null,
                { key: 'Autoship Balance', val: '{{ showAmount($user->autoship ?? 0) }}' }
            ];
            rows.filter(Boolean).forEach(function(r) {
                det.innerHTML += '<div class="bt-summary-row">' +
                    '<span class="bt-summary-key">' + r.key + '</span>' +
                    '<span class="bt-summary-val">' + r.val + '</span></div>';
            });
            showPairingState('eligible');
            setTimeout(function () { document.getElementById('pair_password').focus(); }, 50);
        } else {
            document.getElementById('pairingErrMsg').textContent = data.message;
            showPairingState('ineligible');
        }
    })
    .catch(function() { showPairingState('ineligible'); });
}

function closePairingModal(e) {
    if (e && e.target !== document.getElementById('pairingOverlay')) return;
    closeOverlay('pairingOverlay');
}

function submitPairing() {
    // pair_password has no name attr so keeps its ID; pairingPasswordHidden's
    // ID is rewritten by app.blade.php so target it by name within the form
    var pw = document.getElementById('pair_password').value;
    document.querySelector('#pairingTransferForm [name="password"]').value = pw;
    document.getElementById('pairingTransferForm').submit();
}
window.openPairingCheck  = openPairingCheck;
window.closePairingModal = closePairingModal;
window.submitPairing     = submitPairing;

/* ── ESC key ─────────────────────────────────────── */
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    closeOverlay('transferOverlay');
    closeOverlay('allOverlay');
    closeOverlay('pairingOverlay');
});

/* ── Number formatter ────────────────────────────── */
function formatNum(n) {
    return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/* ── Auto-reopen on validation error ─────────────── */
(function() {
    var el = document.getElementById('btAutoReopen');
    if (!el) return;

    var keys   = JSON.parse(el.dataset.keys   || '[]');
    var labels = JSON.parse(el.dataset.labels  || '[]');
    var labelMap = {};
    keys.forEach(function(k, i) { labelMap[k] = labels[i]; });

    if (el.dataset.allError === '1') {
        openAllModal();
        return;
    }

    if (el.dataset.pwError === '1') {
        var field  = el.dataset.field;
        var amount = parseFloat(el.dataset.amount) || 0;
        var label  = labelMap[field] || field;
        if (field === 'matching_bonus') {
            openPairingCheck();
        } else if (field) {
            openTransferModal(field, label, amount);
        }
    }
})();

})();
</script>
@endpush

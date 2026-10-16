@extends($activeTemplate . 'layouts.master')

@section('content')
@php
    $refUrl     = route('user.register') . '?ref=' . auth()->user()->username;
    $refCode    = strtoupper(auth()->user()->username);
    $refCount   = $logs->total();
    $avatarPalette = ['#059669','#2563EB','#7C3AED','#D97706','#DB2777','#0D9488','#EA580C','#4F46E5'];
@endphp

<div class="ref-page">

    {{-- ── Stats Strip ── --}}
    <div class="ref-stats-strip">
        <div class="ref-stat">
            <div class="ref-stat-icon green"><i class="las la-user-friends"></i></div>
            <div>
                <p class="ref-stat-label">Total Referrals</p>
                <p class="ref-stat-value">{{ $refCount }}</p>
                <p class="ref-stat-sub">All time</p>
            </div>
        </div>
        <div class="ref-stat">
            <div class="ref-stat-icon blue"><i class="las la-link"></i></div>
            <div>
                <p class="ref-stat-label">Ref Code</p>
                <p class="ref-stat-value" style="font-size:1.1rem; letter-spacing:.06em;">{{ $refCode }}</p>
                <p class="ref-stat-sub">Your unique code</p>
            </div>
        </div>
        <div class="ref-stat">
            <div class="ref-stat-icon violet"><i class="las la-chart-line"></i></div>
            <div>
                <p class="ref-stat-label">Network</p>
                <p class="ref-stat-value">{{ $refCount > 0 ? 'Active' : 'None' }}</p>
                <p class="ref-stat-sub">{{ $refCount }} member{{ $refCount !== 1 ? 's' : '' }}</p>
            </div>
        </div>
    </div>

    {{-- ── Invite Card ── --}}
    <div class="ref-invite-card">
        <div class="ref-invite-header">
            <p class="ref-invite-title">Referral Program</p>
            <h5 class="ref-invite-heading">Invite & Earn Together</h5>
            <p class="ref-invite-sub">Share your link and grow your network</p>
        </div>
        <div class="ref-invite-body">

            {{-- Code row --}}
            <div class="ref-code-row">
                <span class="ref-code-label">Your Code</span>
                <div class="ref-code-badge">{{ $refCode }}</div>
            </div>

            {{-- Full link --}}
            <div class="ref-link-group">
                <input type="text" id="refLinkInput" value="{{ $refUrl }}" readonly>
                <button class="ref-link-copy" id="copyBtn" onclick="copyRefLink()">
                    <i class="las la-copy"></i>
                    <span id="copyLabel">Copy</span>
                </button>
            </div>

            {{-- Share --}}
            <div class="ref-share-row">
                <span class="ref-share-label">Share via:</span>
                <a href="https://wa.me/?text={{ urlencode('Join me! ' . $refUrl) }}" target="_blank" class="ref-share-btn s-wa" title="WhatsApp">
                    <i class="fab fa-whatsapp"></i>
                </a>
                <a href="https://t.me/share/url?url={{ urlencode($refUrl) }}" target="_blank" class="ref-share-btn s-tg" title="Telegram">
                    <i class="fab fa-telegram-plane"></i>
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($refUrl) }}" target="_blank" class="ref-share-btn s-fb" title="Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?url={{ urlencode($refUrl) }}" target="_blank" class="ref-share-btn s-tw" title="X / Twitter">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="mailto:?subject=Join+me&body={{ urlencode('Join using my referral link: ' . $refUrl) }}" class="ref-share-btn s-em" title="Email">
                    <i class="las la-envelope"></i>
                </a>
            </div>

        </div>
    </div>

    {{-- ── Members List ── --}}
    <div class="ref-members-card">
        <div class="ref-members-head">
            <h6 class="ref-members-title">
                <i class="las la-users"></i>
                Referral Members
            </h6>
            @if($refCount > 0)
                <span class="ref-count-pill">{{ $refCount }} total</span>
            @endif
        </div>

        <div class="ref-cards-grid">
        @forelse($logs as $index => $data)
        @php
            $initial = strtoupper(substr($data->fullname ?? $data->username, 0, 1));
            $color   = $avatarPalette[($logs->firstItem() + $index - 1) % count($avatarPalette)];
        @endphp
        <div class="ref-mc">
            <div class="ref-mc-top">
                <div class="ref-mc-avatar" style="background:{{ $color }};">{{ $initial }}</div>
                <div class="ref-mc-num">#{{ $logs->firstItem() + $index }}</div>
            </div>
            <p class="ref-mc-name">{{ $data->fullname }}</p>
            <p class="ref-mc-username">@<span>{{ $data->username }}</span></p>
            <div class="ref-mc-divider"></div>
            <div class="ref-mc-foot">
                <i class="las la-calendar-alt"></i>
                <span>{{ showDateTime($data->created_at, 'd M Y') }}</span>
                <span class="ref-mc-ago">{{ diffForHumans($data->created_at) }}</span>
            </div>
        </div>
        @empty
        <div class="ref-empty">
            <div class="ref-empty-icon"><i class="las la-user-friends"></i></div>
            <h6>No referrals yet</h6>
            <p>Share your referral link or code above to start building your network.</p>
        </div>
        @endforelse
        </div>
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
        <div>{{ paginateLinks($logs) }}</div>
    @endif

</div>
@endsection

@push('script')
<script>
'use strict';
function copyRefLink() {
    var input = document.getElementById('refLinkInput');
    var btn   = document.getElementById('copyBtn');
    var label = document.getElementById('copyLabel');
    input.select(); input.setSelectionRange(0, 99999);
    try { navigator.clipboard.writeText(input.value); } catch(e) { document.execCommand('copy'); }
    label.textContent = 'Copied!';
    btn.classList.add('copied');
    notify('success', '@lang("Referral link copied!")');
    setTimeout(function(){ label.textContent = 'Copy'; btn.classList.remove('copied'); }, 2500);
}
</script>
@endpush

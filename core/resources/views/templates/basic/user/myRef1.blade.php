@extends($activeTemplate . 'layouts.master2')

@push('style')
<style>
    /* ── Hero Stats ── */
    .ref-hero {
        background: linear-gradient(135deg, #1a6e35 0%, #26d98e 100%);
        border-radius: 16px;
        padding: 2rem 2.5rem;
        color: #fff;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    .ref-hero::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 180px; height: 180px;
        background: rgba(255,255,255,.08);
        border-radius: 50%;
    }
    .ref-hero::after {
        content: '';
        position: absolute;
        bottom: -60px; right: 60px;
        width: 240px; height: 240px;
        background: rgba(255,255,255,.05);
        border-radius: 50%;
    }
    .ref-hero .stat-box {
        background: rgba(255,255,255,.15);
        border: 1px solid rgba(255,255,255,.25);
        border-radius: 12px;
        padding: 1rem 1.5rem;
        backdrop-filter: blur(4px);
        min-width: 140px;
    }
    .ref-hero .stat-box .stat-num {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
    }
    .ref-hero .stat-box .stat-label {
        font-size: .8rem;
        opacity: .85;
        margin-top: 4px;
    }

    /* ── Link Card ── */
    .ref-link-card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 4px 24px rgba(38,217,142,.15);
        overflow: hidden;
    }
    .ref-link-card .card-header {
        background: linear-gradient(90deg, #135D26, #26d98e);
        border: none;
        padding: 1rem 1.5rem;
    }
    .ref-link-input {
        border-radius: 8px 0 0 8px !important;
        background: #f8fffe !important;
        border-color: #b2f0d5 !important;
        font-size: .875rem;
        color: #135D26;
        font-weight: 500;
    }
    .ref-link-input:focus {
        box-shadow: 0 0 0 3px rgba(38,217,142,.2) !important;
        border-color: #26d98e !important;
    }
    .btn-copy {
        background: linear-gradient(135deg, #135D26, #26d98e);
        color: #fff;
        border: none;
        border-radius: 0 8px 8px 0 !important;
        padding: 0 1.25rem;
        font-weight: 600;
        transition: opacity .2s;
    }
    .btn-copy:hover { opacity: .88; color: #fff; }
    .btn-copy.copied {
        background: linear-gradient(135deg, #0a3d18, #1bb574);
    }

    /* ── Share Buttons ── */
    .share-btn {
        width: 38px; height: 38px;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: .9rem;
        transition: transform .2s, box-shadow .2s;
        text-decoration: none;
    }
    .share-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.18); }
    .share-wa  { background:#25D366; color:#fff; }
    .share-tg  { background:#0088cc; color:#fff; }
    .share-fb  { background:#1877F2; color:#fff; }
    .share-tw  { background:#1DA1F2; color:#fff; }
    .share-em  { background:#555;    color:#fff; }

    /* ── Section Header ── */
    .ref-section-header {
        border: none;
        border-radius: 14px 14px 0 0;
        background: #fff;
        border-bottom: 1px solid #e9f7ef;
        padding: 1rem 1.5rem;
    }

    /* ── Member Card ── */
    .ref-member-card {
        border: 1px solid #e9f7ef;
        border-radius: 14px;
        background: #fff;
        padding: 1.25rem;
        transition: transform .2s, box-shadow .2s, border-color .2s;
        height: 100%;
    }
    .ref-member-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 28px rgba(38,217,142,.18);
        border-color: #26d98e;
    }

    /* ── Avatar ── */
    .ref-avatar {
        width: 52px; height: 52px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        color: #fff;
        flex-shrink: 0;
        box-shadow: 0 3px 10px rgba(0,0,0,.15);
    }

    /* ── Divider ── */
    .ref-member-divider {
        border-color: #e9f7ef;
        margin: .75rem 0;
    }

    /* ── Info row ── */
    .ref-info-row {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .8rem;
        color: #666;
        margin-bottom: .35rem;
    }
    .ref-info-row i { color: #26d98e; font-size: .95rem; flex-shrink: 0; }
    .ref-info-row span { word-break: break-all; }

    /* ── Empty State ── */
    .empty-state {
        padding: 3.5rem 1rem;
        text-align: center;
        color: #aaa;
    }
    .empty-state i { font-size: 3.5rem; margin-bottom: 1rem; opacity: .4; display: block; }
    .empty-state p { font-size: .95rem; margin: 0; }
</style>
@endpush

@section('content')
@include($activeTemplate.'layouts.breadcrumb')

@php
    $userDetails = getUserDetails(auth()->id());
    $refUrl      = ($userDetails->section == 1)
                        ? route('user.register')  . '?ref=' . auth()->user()->username
                        : route('user.register1') . '?ref=' . auth()->user()->username;
    $refCount    = $logs->total();
    $avatarColors = ['#26d98e','#135D26','#1a6e35','#38f9d7','#43e97b','#0db868','#05a85a'];
@endphp

{{-- ── Hero Stats ── --}}
<div class="ref-hero mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-white">@lang('My Referral Program')</h4>
            <p class="mb-0 opacity-75" style="font-size:.9rem;">@lang('Invite friends and grow your network')</p>
        </div>
        <div class="d-flex gap-3 flex-wrap">
            <div class="stat-box text-center">
                <div class="stat-num">{{ $refCount }}</div>
                <div class="stat-label">@lang('Total Referrals')</div>
            </div>
            <div class="stat-box text-center">
                <div class="stat-num">
                    <i class="las la-users" style="font-size:1.6rem;"></i>
                </div>
                <div class="stat-label">@lang('Your Network')</div>
            </div>
        </div>
    </div>
</div>

{{-- ── Referral Link Card ── --}}
<div class="card ref-link-card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="las la-link text-white fs-5"></i>
            <h6 class="mb-0 text-white fw-semibold">@lang('Your Referral Link')</h6>
        </div>
    </div>
    <div class="card-body p-4">
        <p class="text-muted mb-3" style="font-size:.875rem;">
            @lang('Share your unique link and earn rewards when your friends join.')
        </p>

        {{-- Link Input + Copy --}}
        <div class="input-group mb-3">
            <input class="form-control ref-link-input"
                   id="refLinkInput"
                   type="url"
                   value="{{ $refUrl }}"
                   readonly>
            <button class="btn btn-copy" id="copyBtn" type="button" onclick="copyRefLink()">
                <i class="fa fa-copy me-1"></i> <span id="copyLabel">@lang('Copy')</span>
            </button>
        </div>

        {{-- Share Buttons --}}
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="text-muted me-1" style="font-size:.8rem;">@lang('Share via'):</span>
            <a href="https://wa.me/?text={{ urlencode('Join me! ' . $refUrl) }}" target="_blank" class="share-btn share-wa" title="WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </a>
            <a href="https://t.me/share/url?url={{ urlencode($refUrl) }}" target="_blank" class="share-btn share-tg" title="Telegram">
                <i class="fab fa-telegram-plane"></i>
            </a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($refUrl) }}" target="_blank" class="share-btn share-fb" title="Facebook">
                <i class="fab fa-facebook-f"></i>
            </a>
            <a href="https://twitter.com/intent/tweet?url={{ urlencode($refUrl) }}" target="_blank" class="share-btn share-tw" title="Twitter / X">
                <i class="fab fa-twitter"></i>
            </a>
            <a href="mailto:?subject=Join+me&body={{ urlencode('Join me using my referral link: ' . $refUrl) }}" class="share-btn share-em" title="Email">
                <i class="las la-envelope"></i>
            </a>
        </div>
    </div>
</div>

{{-- ── Referral Members Cards ── --}}
<div class="ref-section-header d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
        <i class="las la-users" style="color:#26d98e; font-size:1.3rem;"></i>
        <h6 class="mb-0 fw-semibold">@lang('Referral Members')
            @if($refCount > 0)
                <span class="badge ms-1" style="background:#e6fff3; color:#135D26; font-size:.7rem; font-weight:600; border-radius:20px; padding:.25rem .6rem;">{{ $refCount }}</span>
            @endif
        </h6>
    </div>
</div>

<div class="row g-3">
@forelse($logs as $index => $data)
@php
    $initial = strtoupper(substr($data->fullname ?? $data->username, 0, 1));
    $color   = $avatarColors[$index % count($avatarColors)];
@endphp

    <div class="col-6 col-sm-6 col-md-4 col-lg-4 col-xl-3">
        <div class="ref-member-card">

            {{-- Top: avatar + name + status --}}
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="ref-avatar" style="background:{{ $color }};">{{ $initial }}</div>
                <div class="flex-grow-1" style="min-width:0;">
                    <div class="fw-bold text-truncate" style="font-size:.9rem; color:#1a2e1a;">{{ $data->fullname }}</div>
                    <span class="badge" style="background:#f0fff7; color:#135D26; font-size:.72rem; font-weight:600; border-radius:6px; padding:.2rem .55rem;">
                        @ {{ $data->username }}
                    </span>
                </div>
                {{-- @if($data->status == 1)
                    <span class="badge align-self-start flex-shrink-0" style="background:#e6fff3; color:#0db868; border-radius:20px; font-size:.7rem; padding:.25rem .65rem;">
                        <i class="las la-check-circle"></i> @lang('Active')
                    </span>
                @else
                    <span class="badge align-self-start flex-shrink-0" style="background:#fff3f3; color:#e00055; border-radius:20px; font-size:.7rem; padding:.25rem .65rem;">
                        <i class="las la-times-circle"></i> @lang('Inactive')
                    </span>
                @endif
                 --}}
            </div>

            <hr class="ref-member-divider">

            {{-- Email --}}
            <div class="ref-info-row">
                <i class="las la-envelope"></i>
                <span class="text-truncate">{{ $data->email }}</span>
            </div>

            {{-- Join date --}}
            <div class="ref-info-row mb-0">
                <i class="las la-calendar-alt"></i>
                <span>{{ showDateTime($data->created_at) }}
                    <span style="color:#bbb; font-size:.72rem;"> &bull; {{--  diffForHumans($data->created_at) --}}</span>
                </span>
            </div>

        </div>
    </div>

@empty
    <div class="col-12">
        <div class="empty-state">
            <i class="las la-user-friends"></i>
            <p>@lang('You have no referrals yet. Share your link to get started!')</p>
        </div>
    </div>
@endforelse
</div>

@if ($logs->hasPages())
    <div class="mt-4">
        {{ paginateLinks($logs) }}
    </div>
@endif

@endsection

@push('script')
<script>
'use strict';

function copyRefLink() {
    var input  = document.getElementById('refLinkInput');
    var btn    = document.getElementById('copyBtn');
    var label  = document.getElementById('copyLabel');

    input.select();
    input.setSelectionRange(0, 99999);
    document.execCommand('copy');

    label.textContent = '@lang("Copied!")';
    btn.classList.add('copied');
    notify('success', '@lang("Referral link copied!")');

    setTimeout(function () {
        label.textContent = '@lang("Copy")';
        btn.classList.remove('copied');
    }, 2500);
}
</script>
@endpush

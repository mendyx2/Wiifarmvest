@extends('admin.layouts.master')

@push('style')
<style>
html, body { height: 100%; overflow: hidden; }

/* ── Full-page shell ─────────────────────────────────── */
.al-page {
    min-height: 100vh;
    width: 100vw;
    background: #060d1a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Poppins', sans-serif;
    position: relative;
    overflow: hidden;
    padding: 1.5rem;
}

/* Ambient glows spread across the whole page */
.al-page::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 60% at 15% 50%,  rgba(16,185,129,.16) 0%, transparent 65%),
        radial-gradient(ellipse 50% 50% at 85% 20%,  rgba(99,102,241,.13) 0%, transparent 60%),
        radial-gradient(ellipse 40% 40% at 70% 80%,  rgba(6,95,70,.18)    0%, transparent 60%);
    pointer-events: none;
}

/* Fine grid overlay */
.al-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
    background-size: 48px 48px;
    pointer-events: none;
}

/* Floating blobs */
.al-blob { position: absolute; border-radius: 50%; filter: blur(80px); pointer-events: none; }
.al-blob-1 { width:480px; height:480px; background:rgba(16,185,129,.11); top:-140px;  left:-140px; animation: bfloat 9s  ease-in-out    infinite; }
.al-blob-2 { width:300px; height:300px; background:rgba(99,102,241,.11); bottom:-60px; right:-80px; animation: bfloat 11s ease-in-out 3s infinite; }
.al-blob-3 { width:200px; height:200px; background:rgba(16,185,129,.09); top:40%;     right:20%;   animation: bfloat 7s  ease-in-out 5s infinite; }
@keyframes bfloat {
    0%,100% { transform: translateY(0)     scale(1);    }
    50%      { transform: translateY(-28px) scale(1.06); }
}

/* ── Centered card ───────────────────────────────────── */
.al-card {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 440px;
    background: #f1f5f9;
    border-radius: 20px;
    padding: 2.6rem 2.8rem 2.2rem;
    box-shadow: 0 32px 80px rgba(0,0,0,.45), 0 0 0 1px rgba(255,255,255,.06);
    animation: cardIn .55s ease both;
}
@keyframes cardIn {
    from { opacity: 0; transform: translateY(24px) scale(.98); }
    to   { opacity: 1; transform: translateY(0)    scale(1);   }
}

/* Shimmer top border */
.al-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 3px;
    border-radius: 20px 20px 0 0;
    background: linear-gradient(90deg, #10b981, #6366f1, #10b981);
    background-size: 300% 100%;
    animation: shimmer 4s linear infinite;
}
@keyframes shimmer {
    from { background-position:  300% center; }
    to   { background-position: -300% center; }
}

/* ── Brand top ───────────────────────────────────────── */
.al-brand {
    text-align: center;
    margin-bottom: 2rem;
}

.al-logo-ring {
    width: 68px; height: 68px;
    margin: 0 auto .9rem;
    border-radius: 16px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.7rem; color: #fff;
    animation: ring-pulse 3s ease-in-out infinite;
    box-shadow: 0 8px 32px rgba(16,185,129,.35);
}
@keyframes ring-pulse {
    0%,100% { box-shadow: 0 8px 32px rgba(16,185,129,.35), 0 0 0  0px rgba(16,185,129,.4); }
    50%      { box-shadow: 0 8px 40px rgba(16,185,129,.55), 0 0 0 10px rgba(16,185,129,0); }
}

.al-site-logo {
    max-height: 44px; width: auto;
    margin: 0 auto .9rem; display: block;
}

.al-site-name {
    font-size: 1.15rem; font-weight: 700;
    color: #0f172a; letter-spacing: -.02em;
    margin-bottom: .15rem;
}
.al-site-sub {
    font-size: .7rem; color: #94a3b8;
    text-transform: uppercase; letter-spacing: .15em;
}

/* ── Form header ─────────────────────────────────────── */
.al-head { margin-bottom: 1.6rem; }
.al-head-tag {
    font-size: .7rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .16em;
    color: #10b981; margin-bottom: .3rem;
}
.al-head h2 {
    font-size: 1.4rem; font-weight: 700;
    color: #0f172a; letter-spacing: -.025em;
    margin: 0 0 .25rem;
}
.al-head p { font-size: .8rem; color: #64748b; margin: 0; }

/* Divider */
.al-divider {
    height: 1px; background: #e2e8f0;
    margin: 0 0 1.5rem;
}

/* ── Field group ─────────────────────────────────────── */
.al-field { margin-bottom: 1.1rem; }
.al-label {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: .4rem;
}
.al-label span { font-size: .76rem; font-weight: 600; color: #334155; }
.al-forgot {
    font-size: .72rem; color: #10b981; font-weight: 500;
    text-decoration: none; transition: color .2s;
}
.al-forgot:hover { color: #059669; }

.al-input-wrap { position: relative; }

.al-input {
    width: 100%; height: 46px;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    background: #fff;
    padding: 0 1rem 0 2.75rem;
    font-size: .865rem; font-family: 'Poppins', sans-serif;
    color: #0f172a;
    transition: border-color .22s, box-shadow .22s;
    outline: none; -webkit-appearance: none;
}
.al-input:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 4px rgba(16,185,129,.11);
}
.al-input.has-right { padding-right: 2.75rem; }

.al-ic {
    position: absolute; top: 50%; transform: translateY(-50%);
    font-size: .9rem; color: #94a3b8;
    pointer-events: none; transition: color .22s;
}
.al-ic.l { left: .95rem; }
.al-ic.r { right: .95rem; cursor: pointer; pointer-events: all; }
.al-input-wrap:focus-within .al-ic.l { color: #10b981; }

/* Captcha */
.al-captcha { margin: .6rem 0 .9rem; }

/* ── Submit button ───────────────────────────────────── */
.al-btn {
    width: 100%; height: 48px;
    border: none; border-radius: 10px; cursor: pointer;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
    font-size: .84rem; font-weight: 600;
    letter-spacing: .06em; text-transform: uppercase;
    font-family: 'Poppins', sans-serif;
    position: relative; overflow: hidden;
    transition: transform .25s, box-shadow .25s;
}
.al-btn::after {
    content: '';
    position: absolute;
    top: 0; left: -100%; width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.22), transparent);
    transition: left .5s ease;
}
.al-btn:hover::after { left: 100%; }
.al-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(16,185,129,.38); }
.al-btn:active { transform: translateY(0); }

/* ── Security badge ──────────────────────────────────── */
.al-badge {
    display: flex; align-items: center; justify-content: center; gap: .35rem;
    margin-top: 1.4rem; font-size: .7rem; color: #94a3b8;
}
.al-badge i { color: #10b981; }

/* ── Responsive ──────────────────────────────────────── */
@media (max-width: 480px) {
    .al-card { padding: 2rem 1.4rem 1.8rem; border-radius: 16px; }
}
</style>
@endpush

@section('content')
<div class="al-page">
    <div class="al-grid"></div>
    <div class="al-blob al-blob-1"></div>
    <div class="al-blob al-blob-2"></div>
    <div class="al-blob al-blob-3"></div>

    <div class="al-card">

        {{-- Brand --}}
        <div class="al-brand">
            @php $logoSrc = siteLogo(); @endphp
            @if($logoSrc)
                <img src="{{ $logoSrc }}" alt="{{ __(gs('site_name')) }}" class="al-site-logo">
            @else
                <div class="al-logo-ring"><i class="las la-leaf"></i></div>
            @endif
            <div class="al-site-name">{{ __(gs('site_name')) }}</div>
            <div class="al-site-sub">@lang('Admin Control Centre')</div>
        </div>

        <div class="al-divider"></div>

        {{-- Header --}}
        <div class="al-head">
            <div class="al-head-tag">@lang('Secure Access')</div>
            <h2>@lang('Welcome back')</h2>
            <p>@lang('Sign in to your admin account to continue')</p>
        </div>

        <form
            action="{{ route('admin.login') }}"
            method="POST"
            class="verify-gcaptcha login-form"
            autocomplete="off"
            novalidate
        >
            @csrf

            {{-- Username --}}
            <div class="al-field">
                <div class="al-label">
                    <span>@lang('Username')</span>
                </div>
                <div class="al-input-wrap">
                    <input
                        type="text"
                        id="al-username"
                        name="username"
                        class="al-input"
                        value="{{ old('username') }}"
                        placeholder="@lang('Enter your username')"
                        autocomplete="username"
                        spellcheck="false"
                        required
                    >
                    <!-- <i class="las la-user al-ic l"></i> -->
                </div>
            </div>

            {{-- Password --}}
            <div class="al-field">
                <div class="al-label">
                    <span>@lang('Password')</span>
                    <a href="{{ route('admin.password.reset') }}" class="al-forgot">
                        @lang('Forgot password?')
                    </a>
                </div>
                <div class="al-input-wrap">
                    <input
                        type="password"
                        id="al-password"
                        name="password"
                        class="al-input has-right"
                        placeholder="@lang('Enter your password')"
                        autocomplete="current-password"
                        required
                    >
                    <!-- <i class="las la-lock al-ic l"></i> -->
                    <i class="las la-eye al-ic r" id="al-pwd-eye"
                       title="@lang('Toggle password visibility')"
                       role="button"
                       aria-label="@lang('Toggle password visibility')"></i>
                </div>
            </div>

            {{-- Captcha --}}
            <div class="al-captcha">
                <x-captcha />
            </div>

            <button type="submit" class="al-btn">
                @lang('Sign In') &nbsp;<i class="las la-arrow-right"></i>
            </button>
        </form>

        <div class="al-badge">
            
            {{--  <i class="las la-shield-alt"></i>
            @lang('Protected by SSL · CSRF · Rate limiting') --}}
        </div>

    </div>
</div>
@endsection

@push('script')
<script>
"use strict";
(function () {
    var btn   = document.getElementById('al-pwd-eye');
    var input = document.getElementById('al-password');
    if (!btn || !input) return;
    btn.addEventListener('click', function () {
        var hidden     = input.type === 'password';
        input.type     = hidden ? 'text' : 'password';
        btn.className  = hidden ? 'las la-eye-slash al-ic r' : 'las la-eye al-ic r';
    });
})();
</script>
@endpush

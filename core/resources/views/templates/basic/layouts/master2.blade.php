@extends($activeTemplate . 'layouts.app')
@section('panel')
    @auth
        @include($activeTemplate.'partials.flashcards', ['flashcards' => Auth::user()->activeFlashcards()->get()])
    @endauth
    @include($activeTemplate.'partials.dashboard')
@endsection 
@push('style')
<style>
/* =============================================
   BANKING DASHBOARD — CORE LAYOUT
   ============================================= */
/* Reset for banking layout */
html, body { overflow: auto !important; padding: 0 !important; margin: 0 !important; }
body { background: #F2F5F8 !important; }
.scrollToTop, .cookies-card { display: none !important; }
#preloader, .overlay { display: none !important; }

:root {
    --bk-primary:    #0D5C2E;
    --bk-primary-dk: #094422;
    --bk-accent:     #16A34A;
    --bk-gold:       #C8973A;
    --bk-bg:         #F2F5F8;
    --bk-surface:    #FFFFFF;
    --bk-border:     #E5E9EF;
    --bk-text:       #1A1F2E;
    --bk-muted:      #6B7280;
    --bk-sidebar-w:  260px;
    --bk-radius:     14px;
    --bk-shadow:     0 2px 16px rgba(0,0,0,.07);
    --bk-shadow-lg:  0 8px 32px rgba(0,0,0,.12);
}

/* Layout */
.bank-dashboard { min-height: 100vh; background: var(--bk-bg); }
.bank-layout     { display: flex; min-height: 100vh; }

/* ===== SIDEBAR ===== */
.bank-sidebar {
    width: var(--bk-sidebar-w);
    background: var(--bk-surface);
    border-right: 1px solid var(--bk-border);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0;
    height: 100vh;
    z-index: 1000;
    overflow-y: auto;
    scrollbar-width: thin;
    transition: transform .28s cubic-bezier(.4,0,.2,1);
}
.bank-sidebar-inner { display: flex; flex-direction: column; min-height: 100%; padding-bottom: 20px; }
.bank-logo { padding: 20px 20px 10px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--bk-border); margin-bottom: 6px; }
.bank-logo-img { height: 36px; object-fit: contain; }
.bank-sidebar-close { background: none; border: none; font-size: 1.3rem; color: var(--bk-muted); cursor: pointer; padding: 4px 8px; }

/* Profile block */
.bank-profile { display: flex; align-items: center; gap: 12px; padding: 14px 20px 16px; background: linear-gradient(135deg, #F0FDF4, #DCFCE7); border-bottom: 1px solid #BBF7D0; margin-bottom: 4px; }
.bank-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #064E3B, #059669); color: #fff; font-weight: 800; font-size: .88rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 3px 10px rgba(5,150,105,.4); }
.bank-profile-name { font-size: .84rem; font-weight: 700; color: var(--bk-text); margin: 0; line-height: 1.3; }
.bank-profile-id   { font-size: .7rem; color: #059669; margin: 0; font-weight: 600; }

/* Nav */
.bank-nav { padding: 0 12px; flex: 1; }
.bank-nav-label { font-size: .65rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--bk-muted); padding: 18px 8px 6px; margin: 0; }
.bank-nav ul { list-style: none; padding: 0; margin: 0; }
.bank-nav-link {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 10px;
    color: var(--bk-muted); font-size: .84rem; font-weight: 500;
    text-decoration: none; margin-bottom: 2px;
    transition: all .18s ease;
}
.bank-nav-link:hover { background: #F0F7F3; color: var(--bk-primary); }
.bank-nav-link.active { background: #E8F5EE; color: var(--bk-primary); font-weight: 600; }
.bank-nav-icon { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; background: transparent; }
.bank-nav-link:hover .bank-nav-icon,
.bank-nav-link.active .bank-nav-icon { background: rgba(13,92,46,.12); color: var(--bk-primary); }
.bank-nav-logout:hover { background: #FEF2F2; color: #DC2626; }
.bank-nav-logout:hover .bank-nav-icon { background: rgba(220,38,38,.1); color: #DC2626; }

/* Nav notification badges */
.bank-nav-badge {
    margin-left: auto; min-width: 18px; height: 18px;
    padding: 0 5px; border-radius: 999px;
    background: var(--bk-accent); color: #fff;
    font-size: .62rem; font-weight: 800;
    display: inline-flex; align-items: center; justify-content: center;
    line-height: 1;
}
.bank-nav-badge-danger { background: #DC2626; }

/* Overlay */
.bank-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 999; display: none; }
.bank-overlay.active { display: block; }

/* ===== MAIN ===== */
.bank-main { flex: 1; margin-left: var(--bk-sidebar-w); min-height: 100vh; display: flex; flex-direction: column; }

/* Top Bar */
.bank-topbar {
    background: var(--bk-surface);
    border-bottom: 1px solid var(--bk-border);
    padding: 0 28px;
    height: 62px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
    box-shadow: 0 1px 6px rgba(0,0,0,.05);
}
.bank-topbar-left { display: flex; align-items: center; gap: 14px; }
.bank-menu-toggle { background: none; border: none; font-size: 1.4rem; color: var(--bk-text); cursor: pointer; padding: 6px; line-height: 1; }
.bank-topbar-greeting h6 { font-size: .88rem; color: var(--bk-text); }
.bank-topbar-greeting p  { font-size: .75rem; }
.bank-topbar-right { display: flex; align-items: center; gap: 10px; }
.bank-topbar-btn {
    display: flex; align-items: center; gap: 6px;
    background: linear-gradient(135deg, #059669, #064E3B); color: #fff;
    padding: 8px 18px; border-radius: 8px; font-size: .8rem; font-weight: 700;
    text-decoration: none; transition: all .18s;
    box-shadow: 0 3px 10px rgba(5,150,105,.4);
}
.bank-topbar-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(5,150,105,.5); color: #fff; }
.bank-topbar-icon {
    width: 36px; height: 36px; border-radius: 50%;
    background: var(--bk-bg); display: flex; align-items: center; justify-content: center;
    color: var(--bk-text); font-size: 1.1rem; text-decoration: none; transition: background .15s;
}
.bank-topbar-icon:hover { background: #E5E9EF; }
.bank-topbar-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, var(--bk-primary), var(--bk-accent));
    color: #fff; font-weight: 700; font-size: .8rem;
    display: flex; align-items: center; justify-content: center;
    text-decoration: none;
}

/* Content area */
.bank-content { padding: 0; flex: 1; max-width: 1280px; width: 100%; overflow-x: hidden; }
.bk-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin: 0 28px 22px; }
.bk-bottom-row { display: grid; grid-template-columns: 1fr 340px; gap: 20px; margin: 0 28px 32px; }

/* =============================================
   BANKING DASHBOARD — COLORFUL CONTENT
   ============================================= */

/* ─────────────────────────────────────────────
   TOP SECTION  —  bright bg, card left
   ───────────────────────────────────────────── */
.bk-top {
    position: relative;
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 32px;
    align-items: center;
    background: #ffffff;
    border-radius: 24px;
    padding: 32px 32px 28px;
    margin: 28px 28px 24px;
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,.06);
}
/* colourful gradient blobs in background */
.bk-blob {
    position: absolute; border-radius: 50%; pointer-events: none;
}
.bk-blob-1 {
    width: 320px; height: 320px; top: -120px; right: -80px;
    background: radial-gradient(circle, rgba(16,185,129,.18) 0%, transparent 70%);
}
.bk-blob-2 {
    width: 200px; height: 200px; bottom: -80px; left: 10%;
    background: radial-gradient(circle, rgba(99,102,241,.14) 0%, transparent 70%);
}
.bk-blob-3 {
    width: 140px; height: 140px; top: 20px; right: 38%;
    background: radial-gradient(circle, rgba(251,191,36,.16) 0%, transparent 70%);
}

/* ── ATM card column ── */
.bk-card-col { position: relative; z-index: 1; }

/* The actual ATM card */
.bk-atm-card {
    width: 320px;
    aspect-ratio: 1.586 / 1;           /* standard card ratio */
    background: linear-gradient(135deg, #0B3D22 0%, #166534 45%, #15803D 75%, #16A34A 100%);
    border-radius: 18px;
    padding: 20px 22px;
    color: #fff;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow:
        0 20px 48px rgba(11,61,34,.45),
        0 4px 12px rgba(0,0,0,.2),
        inset 0 1px 0 rgba(255,255,255,.12);
}
/* card glare */
.bk-atm-glare {
    position: absolute; top: -40%; left: -10%;
    width: 70%; height: 180%;
    background: linear-gradient(105deg, transparent 30%, rgba(255,255,255,.06) 50%, transparent 70%);
    pointer-events: none;
    transform: rotate(-10deg);
}
/* decorative circles on card */
.bk-atm-circle {
    position: absolute; border-radius: 50%;
    border: 1px solid rgba(255,255,255,.08); pointer-events: none;
}
.bk-atm-c1 { width: 220px; height: 220px; right: -60px; bottom: -60px; }
.bk-atm-c2 { width: 140px; height: 140px; right: -20px; bottom: -20px; background: rgba(255,255,255,.04); border: none; }

/* top row of card */
.bk-atm-top { display: flex; justify-content: space-between; align-items: center; }
.bk-atm-logo { font-size: 1.1rem; font-weight: 900; letter-spacing: .12em; color: #fff; opacity: .95; }
.bk-atm-eye {
    background: rgba(255,255,255,.15); border: none; border-radius: 50%;
    width: 30px; height: 30px; color: #fff; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; transition: background .15s;
}
.bk-atm-eye:hover { background: rgba(255,255,255,.28); }

/* chip + balance row */
.bk-atm-mid { display: flex; align-items: center; gap: 14px; }
.bk-chip {
    width: 34px; height: 26px;
    background: linear-gradient(135deg, #D4A017, #F5C842);
    border-radius: 5px; position: relative; flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(0,0,0,.25);
}
.bk-chip::after {
    content: ''; position: absolute; inset: 3px;
    border: 1.5px solid rgba(0,0,0,.2); border-radius: 3px;
}
.bk-chip-h {
    position: absolute; top: 50%; left: 0; right: 0; height: 1.5px;
    background: rgba(0,0,0,.18); transform: translateY(-50%);
}
.bk-chip-v {
    position: absolute; left: 50%; top: 0; bottom: 0; width: 1.5px;
    background: rgba(0,0,0,.18); transform: translateX(-50%);
}
.bk-atm-bal-label { font-size: .6rem; text-transform: uppercase; letter-spacing: .07em; opacity: .7; margin: 0 0 2px; }
.bk-atm-bal { font-size: 1.45rem; font-weight: 800; margin: 0; letter-spacing: -.01em; text-shadow: 0 2px 6px rgba(0,0,0,.2); }

/* card number row */
.bk-atm-num {
    display: flex; gap: 12px; font-size: .82rem;
    font-family: 'Courier New', monospace; letter-spacing: .15em; opacity: .9;
}
.bk-atm-num span { display: inline-block; }

/* card bottom row */
.bk-atm-bottom { display: flex; align-items: flex-end; gap: 18px; }
.bk-atm-meta-label { font-size: .55rem; text-transform: uppercase; letter-spacing: .06em; opacity: .65; margin: 0 0 2px; }
.bk-atm-meta { font-size: .78rem; font-weight: 700; margin: 0; letter-spacing: .04em; }

/* mastercard-style rings */
.bk-mc { display: flex; margin-left: auto; }
.bk-mc-l, .bk-mc-r {
    width: 28px; height: 28px; border-radius: 50%; display: block;
}
.bk-mc-l { background: rgba(255,180,0,.7); }
.bk-mc-r { background: rgba(255,80,0,.55); margin-left: -10px; mix-blend-mode: screen; }

/* sub badge below card */
.bk-card-sub-badge {
    display: flex; align-items: center; gap: 8px;
    margin-top: 12px;
    background: linear-gradient(135deg, #ECFDF5, #D1FAE5);
    border: 1px solid #A7F3D0;
    border-radius: 12px; padding: 9px 14px;
}
.bk-csb-icon { color: #059669; font-size: 1.1rem; }
.bk-csb-text { font-size: .78rem; color: #065F46; }
.bk-csb-text strong { font-weight: 800; }

/* ── Right column ── */
.bk-right-col { position: relative; z-index: 1; }

/* Greeting */
.bk-greeting { margin-bottom: 22px; }
.bk-greet-sub  { font-size: .82rem; color: #6B7280; margin: 0 0 2px; }
.bk-greet-name { font-size: 1.7rem; font-weight: 900; color: #111827; margin: 0 0 4px;
    background: linear-gradient(135deg, #064E3B, #059669); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.bk-greet-date { font-size: .75rem; color: #9CA3AF; margin: 0; }

/* ── Quick Actions ── */
.bk-qa-section-label {
    font-size: .65rem; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: #9CA3AF; margin: 0 0 14px;
}
.bk-qa-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0;
}
.bk-qa {
    display: flex; flex-direction: column; align-items: center; gap: 7px;
    padding: 14px 6px 12px;
    text-decoration: none;
    border-radius: 12px;
    transition: background .15s, transform .15s;
    position: relative;
}
.bk-qa:hover {
    background: rgba(0,0,0,.035);
    transform: translateY(-1px);
}
.bk-qa-icon {
    font-size: 2rem;
    line-height: 1;
    display: block;
    transition: transform .15s;
}
.bk-qa:hover .bk-qa-icon { transform: scale(1.12); }
.bk-qa span {
    font-size: .67rem; font-weight: 600;
    color: #6B7280; letter-spacing: .01em;
    transition: color .15s;
}
/* Icon colors — no backgrounds */
.bk-qa-green .bk-qa-icon  { color: #059669; }
.bk-qa-rose .bk-qa-icon   { color: #E11D48; }
.bk-qa-blue .bk-qa-icon   { color: #2563EB; }
.bk-qa-violet .bk-qa-icon { color: #7C3AED; }
.bk-qa-amber .bk-qa-icon  { color: #D97706; }
.bk-qa-pink .bk-qa-icon   { color: #DB2777; }
.bk-qa-teal .bk-qa-icon   { color: #0D9488; }
.bk-qa-indigo .bk-qa-icon { color: #4F46E5; }
/* Label color on hover matches icon theme */
.bk-qa-green:hover span  { color: #059669; }
.bk-qa-rose:hover span   { color: #E11D48; }
.bk-qa-blue:hover span   { color: #2563EB; }
.bk-qa-violet:hover span { color: #7C3AED; }
.bk-qa-amber:hover span  { color: #D97706; }
.bk-qa-pink:hover span   { color: #DB2777; }
.bk-qa-teal:hover span   { color: #0D9488; }
.bk-qa-indigo:hover span { color: #4F46E5; }
/* Subtle dividers between cells */
.bk-qa-grid > .bk-qa:not(:nth-child(3n)) {
    border-right: 1px solid #F3F4F6;
}
.bk-qa-grid > .bk-qa:nth-child(-n+3) {
    border-bottom: 1px solid #F3F4F6;
}

/* ── Stats Grid ── */
.bk-stat-card {
    border-radius: var(--bk-radius); padding: 22px 20px;
    position: relative; overflow: hidden;
    box-shadow: 0 6px 24px rgba(0,0,0,.14);
    transition: transform .2s, box-shadow .2s; color: #fff;
}
.bk-stat-card:hover { transform: translateY(-3px); box-shadow: 0 12px 32px rgba(0,0,0,.2); }
.bk-stat-bg-icon {
    position: absolute; right: -8px; bottom: -8px;
    font-size: 5rem; opacity: .15; line-height: 1;
    pointer-events: none;
}
.bk-stat-label { font-size: .7rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; opacity: .85; margin: 0 0 8px; }
.bk-stat-value { font-size: 1.4rem; font-weight: 800; margin: 0 0 8px; text-shadow: 0 1px 4px rgba(0,0,0,.15); }
.bk-stat-sub   { font-size: .7rem; opacity: .8; margin: 0; }
.bk-stat-sub i { margin-right: 3px; }
.bk-stat-emerald { background: linear-gradient(135deg, #059669 0%, #10B981 100%); }
.bk-stat-rose    { background: linear-gradient(135deg, #98e6b2 0%, #98e6b2 100%); }
.bk-stat-rose1    { background: linear-gradient(135deg, #DC2626 0%, #F43F5E 100%); }
.bk-stat-indigo  { background: linear-gradient(135deg, #4F46E5 0%, #60A5FA 100%); }
.bk-stat-violet  { background: linear-gradient(135deg, #7C3AED 0%, #C084FC 100%); }

/* Shared card base */
.bk-txn-card, .bk-portfolio-card, .bk-status-card, .bk-links-card {
    background: var(--bk-surface); border-radius: var(--bk-radius);
    box-shadow: var(--bk-shadow); overflow: hidden;
}
.bk-portfolio-card { margin-bottom: 16px; }
.bk-status-card    { margin-bottom: 16px; }

/* Section header */
.bk-section-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px 12px; border-bottom: 1px solid var(--bk-border); }
.bk-section-title-wrap { display: flex; align-items: center; gap: 8px; }
.bk-section-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
.bk-dot-blue   { background: #3B82F6; box-shadow: 0 0 0 3px rgba(59,130,246,.2); }
.bk-dot-green  { background: #10B981; box-shadow: 0 0 0 3px rgba(16,185,129,.2); }
.bk-dot-violet { background: #8B5CF6; box-shadow: 0 0 0 3px rgba(139,92,246,.2); }
.bk-section-title { font-size: .9rem; font-weight: 700; color: var(--bk-text); margin: 0; }
.bk-section-link  { font-size: .78rem; color: #059669; text-decoration: none; font-weight: 600; }
.bk-section-link:hover { color: var(--bk-primary); }

/* ── Transactions ── */
.bk-txn-list { padding: 6px 0; }
.bk-txn-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 20px; border-left: 3px solid transparent; transition: all .15s;
}
.bk-txn-item:hover { background: var(--bk-bg); }
.bk-txn-emerald { border-left-color: #10B981; }
.bk-txn-rose    { border-left-color: #F43F5E; }
.bk-txn-icon-wrap { flex-shrink: 0; }
.bk-txn-icon { width: 38px; height: 38px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1rem; }
.bk-icon-emerald { background: linear-gradient(135deg, #D1FAE5, #A7F3D0); color: #059669; }
.bk-icon-rose    { background: linear-gradient(135deg, #FFE4E6, #FECDD3); color: #E11D48; }
.bk-txn-details { flex: 1; min-width: 0; }
.bk-txn-name { font-size: .82rem; font-weight: 600; color: var(--bk-text); margin: 0 0 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.bk-txn-date { font-size: .7rem; color: var(--bk-muted); margin: 0; }
.bk-txn-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
.bk-txn-amount { font-size: .85rem; font-weight: 800; white-space: nowrap; }
.bk-amount-emerald { color: #059669; }
.bk-amount-rose    { color: #E11D48; }
.bk-txn-type-badge { font-size: .62rem; font-weight: 700; padding: 1px 7px; border-radius: 20px; }
.bk-badge-emerald { background: #D1FAE5; color: #059669; }
.bk-badge-rose    { background: #FFE4E6; color: #E11D48; }

/* Empty state */
.bk-empty-state { text-align: center; padding: 40px 20px; }
.bk-empty-icon { width: 64px; height: 64px; border-radius: 20px; background: linear-gradient(135deg, #E0F2FE, #BAE6FD); display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
.bk-empty-icon i { font-size: 1.8rem; color: #0284C7; }
.bk-empty-state h6 { font-size: .88rem; font-weight: 700; color: var(--bk-text); margin: 0 0 4px; }
.bk-empty-state p  { font-size: .78rem; color: var(--bk-muted); margin: 0 0 14px; }
.bk-empty-btn { display: inline-block; background: linear-gradient(135deg, #059669, #10B981); color: #fff; padding: 8px 20px; border-radius: 8px; font-size: .78rem; font-weight: 700; text-decoration: none; }

/* ── Portfolio Card ── */
.bk-portfolio-header {
    display: flex; align-items: center; gap: 12px; padding: 16px 20px;
    background: linear-gradient(135deg, #064E3B, #065F46);
}
.bk-portfolio-header-icon {
    width: 40px; height: 40px; border-radius: 12px;
    background: rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; color: #fff; flex-shrink: 0;
}
.bk-portfolio-title { font-size: .9rem; font-weight: 700; color: #fff; margin: 0; }
.bk-portfolio-sub   { font-size: .7rem; color: rgba(255,255,255,.7); margin: 0; }
.bk-port-link { margin-left: auto; color: rgba(255,255,255,.85); font-size: .75rem; font-weight: 700; text-decoration: none; }
.bk-port-link:hover { color: #fff; }
.bk-portfolio-body { padding: 14px 20px; display: flex; flex-direction: column; gap: 10px; }
.bk-port-item  { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid var(--bk-border); }
.bk-port-item:last-child { border-bottom: none; }
.bk-port-pill  { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.bk-pill-teal   { background: linear-gradient(135deg, #CCFBF1, #99F6E4); color: #0D9488; }
.bk-pill-green  { background: linear-gradient(135deg, #D1FAE5, #A7F3D0); color: #059669; }
.bk-pill-violet { background: linear-gradient(135deg, #EDE9FE, #DDD6FE); color: #7C3AED; }
.bk-port-info   { display: flex; flex-direction: column; }
.bk-port-label  { font-size: .68rem; color: var(--bk-muted); margin: 0; }
.bk-port-val    { font-size: .88rem; font-weight: 700; color: var(--bk-text); margin: 0; }

/* ── Status Card ── */
.bk-status-list { padding: 4px 20px 8px; }
.bk-status-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--bk-border); }
.bk-status-row:last-child { border-bottom: none; }
.bk-status-label { font-size: .8rem; color: var(--bk-muted); }
.bk-status-label i { margin-right: 5px; }
.bk-pill-badge { font-size: .68rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
.bk-pill-green  { background: linear-gradient(135deg, #D1FAE5, #A7F3D0); color: #065F46; }
.bk-pill-red    { background: linear-gradient(135deg, #FFE4E6, #FECDD3); color: #9F1239; }
.bk-pill-amber  { background: linear-gradient(135deg, #FEF9C3, #FEF08A); color: #92400E; }

/* ── Quick Links Grid ── */
.bk-grid-links { padding: 12px 16px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.bk-grid-link {
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    padding: 12px 8px; border-radius: 12px; text-decoration: none;
    font-size: .7rem; font-weight: 700; color: #fff;
    transition: transform .18s, box-shadow .18s; position: relative;
    text-align: center;
}
.bk-grid-link:hover { transform: translateY(-2px); color: #fff; }
.bk-grid-link i { font-size: 1.3rem; }
.bk-gl-badge { position: absolute; top: 6px; right: 6px; background: #fff; color: #1A1F2E; border-radius: 20px; padding: 0 5px; font-size: .6rem; font-weight: 800; line-height: 1.4; }
.bk-gl-blue   { background: linear-gradient(135deg, #3B82F6, #2563EB); box-shadow: 0 4px 12px rgba(37,99,235,.35); }
.bk-gl-teal   { background: linear-gradient(135deg, #14B8A6, #0D9488); box-shadow: 0 4px 12px rgba(13,148,136,.35); }
.bk-gl-amber  { background: linear-gradient(135deg, #FBBF24, #D97706); box-shadow: 0 4px 12px rgba(217,119,6,.35); }
.bk-gl-pink   { background: linear-gradient(135deg, #F472B6, #DB2777); box-shadow: 0 4px 12px rgba(219,39,119,.35); }
.bk-gl-green  { background: linear-gradient(135deg, #34D399, #059669); box-shadow: 0 4px 12px rgba(5,150,105,.35); }
.bk-gl-violet { background: linear-gradient(135deg, #A78BFA, #7C3AED); box-shadow: 0 4px 12px rgba(124,58,237,.35); }

/* ===== RESPONSIVE ===== */

/* Tablet – sidebar collapses */
@media (max-width: 991.98px) {
    .bank-sidebar { transform: translateX(-100%); }
    .bank-sidebar.open { transform: translateX(0); }
    .bank-main { margin-left: 0; }
    /* top section stacks: card above, actions below */
    .bk-top {
        grid-template-columns: 1fr;
        margin: 14px 14px 18px;
        padding: 22px 18px 20px;
        gap: 22px;
    }
    .bk-card-col { display: flex; flex-direction: column; align-items: center; }
    .bk-atm-card { width: 100%; max-width: 380px; }
    .bk-card-sub-badge { max-width: 380px; width: 100%; }
    .bk-qa-grid { grid-template-columns: repeat(4, 1fr); }
    .bk-stats-grid { grid-template-columns: repeat(2, 1fr); margin: 0 14px 18px; }
    .bk-bottom-row { grid-template-columns: 1fr; margin: 0 14px 20px; }
    .bk-right-panel { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
    .bk-portfolio-card, .bk-status-card { margin-bottom: 0; }
    .bk-links-card { grid-column: 1 / -1; }
}

/* Mobile */
@media (max-width: 575.98px) {
    .bk-top { margin: 10px 10px 14px; padding: 16px 14px 14px; gap: 16px; }
    .bk-atm-card { border-radius: 14px; padding: 14px 15px; }
    .bk-atm-bal { font-size: 1.15rem; }
    .bk-atm-num { font-size: .68rem; gap: 6px; }
    .bk-greet-name { font-size: 1.25rem; }
    .bk-greeting { margin-bottom: 14px; }

    /* ── Quick actions: own floating card on mobile ── */
    .bk-right-col { background: transparent; padding: 0; }
    .bk-qa-section-label {
        font-size: .6rem; letter-spacing: .09em; color: #9CA3AF;
        margin: 0 0 8px 2px;
    }
    .bk-qa-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0;
        background: #fff;
        border-radius: 18px;
        border: 1px solid #ECEEF2;
        box-shadow: 0 4px 18px rgba(0,0,0,.07);
        overflow: hidden;
    }
    .bk-qa {
        padding: 20px 6px 15px;
        border-radius: 0;
        gap: 8px;
        background: transparent;
    }
    .bk-qa:hover { background: #F9FAFB; transform: none; }
    .bk-qa-icon { font-size: 1.85rem; }
    .bk-qa span { font-size: .63rem; font-weight: 600; color: #6B7280; }
    /* cell dividers stay sharp inside the card */
    .bk-qa-grid > .bk-qa:not(:nth-child(3n)) { border-right: 1px solid #F0F0F2; }
    .bk-qa-grid > .bk-qa:nth-child(-n+3) { border-bottom: 1px solid #F0F0F2; }

    .bk-stats-grid { grid-template-columns: 1fr 1fr; margin: 0 10px 14px; }
    .bk-bottom-row { margin: 0 10px 16px; }
    .bk-right-panel { grid-template-columns: 1fr; }
    .bk-topbar { padding: 0 14px; }
    .bk-txn-name { max-width: 130px; }
    .bk-grid-links { grid-template-columns: repeat(3, 1fr); }
}

/* Very small phones */
@media (max-width: 380px) {
    .bk-qa { width: 52px; padding: 9px 3px 7px; border-radius: 12px; font-size: .55rem; }
    .bk-qa { padding: 17px 4px 13px; }
    .bk-qa-icon { font-size: 1.65rem; }
    .bk-qa span { font-size: .59rem; }
    .bk-atm-bal { font-size: 1rem; }
}

    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    }

    .bg-gradient-info {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%) !important;
    }

    .bg-gradient-warning {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;
    }
    .bg-gradient-success { 
        background: linear-gradient(135deg, #b8efc4 0%, #26d98e 100%) !important;
    }

    .redemption-card {
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .redemption-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 35px rgba(0, 0, 0, 0.1) !important;
        border-color: #667eea;
    }

    .redemption-icon .bg-success {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
    }

    .quick-info-card {
        border-left: 4px solid #667eea;
    }

    .product-icon {
        transition: transform 0.3s ease;
    }

    .product-icon:hover {
        transform: scale(1.1);
    }

    .info-item {
        transition: background-color 0.2s ease;
    }

    .info-item:hover {
        background-color: #f8f9fa !important;
    }

    .empty-history-icon {
        opacity: 0.5;
    }

    /* Custom pagination */
    .pagination .page-link {
        border: none;
        border-radius: 8px;
        margin: 0 2px;
        color: #667eea;
    }

    .pagination .page-item.active .page-link {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
    }

    .pagination .page-link:hover {
        background-color: #f8f9fa;
        color: #764ba2;
    }

    /* Table enhancements */
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }

    /* Badge enhancements */
    .badge {
        font-weight: 500;
    }

    /* Animation for cards */
    .redemption-card {
        animation: fadeInUp 0.5s ease;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .redemption-amount {
            text-align: left !important;
            margin-top: 1rem;
        }
        
        .redemption-meta {
            text-align: left !important;
            margin-top: 1rem;
        }
        
        .quick-info-card {
            margin-top: 1rem;
        }
        
        .stats-card .card-body {
            padding: 1rem !important;
        }
        .display-5 {
            font-size: 2rem;
        }
        
        .btn-group-sm {
            flex-wrap: wrap;
        }
        
        .quick-location {
            margin-bottom: 5px;
        }
    }

    .bg-gradient-light {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.step-icon {
    transition: transform 0.3s ease;
}

.step-icon:hover {
    transform: scale(1.1);
}

.stockist-card {
    transition: all 0.3s ease;
    border: 1px solid transparent;
    cursor: pointer;
}

.stockist-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
    border-color: #667eea;
}

.quick-location {
    transition: all 0.2s ease;
}

.quick-location:hover {
    transform: translateY(-1px);
}

#mapContainer {
    min-height: 400px;
}

.stockist-marker {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: 3px solid white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.contact-info a {
    text-decoration: none;
    transition: color 0.2s ease;
}

.contact-info a:hover {
    color: #667eea !important;
}

.badge {
    font-weight: 500;
}
.stockist-card {
    animation: fadeInUp 0.5s ease;
}

/* Animation for search results */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ══════════════════════════════════════
   REFERRAL PAGE — BANKING STYLE
══════════════════════════════════════ */
.ref-page { display: flex; flex-direction: column; gap: 20px; }

/* Stat Strip */
.ref-stats-strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
.ref-stat {
    background: #fff; border-radius: 16px; padding: 20px 18px;
    display: flex; align-items: center; gap: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid #F0F2F5;
}
.ref-stat-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; flex-shrink: 0; }
.ref-stat-icon.green  { background: #D1FAE5; color: #059669; }
.ref-stat-icon.blue   { background: #DBEAFE; color: #2563EB; }
.ref-stat-icon.violet { background: #EDE9FE; color: #7C3AED; }
.ref-stat-label { font-size: .7rem; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: .05em; margin: 0 0 4px; }
.ref-stat-value { font-size: 1.55rem; font-weight: 800; color: #111827; margin: 0; line-height: 1; }
.ref-stat-sub   { font-size: .7rem; color: #9CA3AF; margin: 3px 0 0; }

/* Invite Card */
.ref-invite-card { background: #fff; border-radius: 20px; box-shadow: 0 2px 14px rgba(0,0,0,.07); border: 1px solid #F0F2F5; overflow: hidden; }
.ref-invite-header { background: linear-gradient(135deg, #0B3D22 0%, #166534 55%, #15803D 100%); padding: 22px 24px 20px; position: relative; overflow: hidden; }
.ref-invite-header::before { content: ''; position: absolute; top: -30px; right: -30px; width: 130px; height: 130px; background: rgba(255,255,255,.07); border-radius: 50%; }
.ref-invite-header::after  { content: ''; position: absolute; bottom: -50px; right: 80px; width: 180px; height: 180px; background: rgba(255,255,255,.04); border-radius: 50%; }
.ref-invite-title   { font-size: .7rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,255,255,.65); margin: 0 0 6px; }
.ref-invite-heading { font-size: 1.15rem; font-weight: 800; color: #fff; margin: 0 0 4px; }
.ref-invite-sub     { font-size: .78rem; color: rgba(255,255,255,.7); margin: 0; }
.ref-invite-body    { padding: 22px 24px 24px; }

/* Code badge */
.ref-code-row   { display: flex; align-items: center; gap: 10px; margin-bottom: 18px; }
.ref-code-label { font-size: .68rem; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: .06em; white-space: nowrap; }
.ref-code-badge { background: #F0FDF4; border: 1.5px dashed #86EFAC; border-radius: 10px; padding: 7px 16px; font-size: 1rem; font-weight: 800; color: #15803D; letter-spacing: .12em; font-family: 'Courier New', monospace; flex: 1; text-align: center; }

/* Link input */
.ref-link-group { display: flex; border-radius: 12px; overflow: hidden; border: 1.5px solid #E5E7EB; background: #F9FAFB; margin-bottom: 18px; }
.ref-link-group input { flex: 1; border: none; background: transparent; padding: 11px 14px; font-size: .8rem; color: #374151; font-weight: 500; outline: none; min-width: 0; }
.ref-link-copy { background: linear-gradient(135deg, #166534, #16A34A); color: #fff; border: none; padding: 0 18px; font-size: .78rem; font-weight: 700; cursor: pointer; transition: opacity .2s; display: flex; align-items: center; gap: 6px; white-space: nowrap; }
.ref-link-copy:hover  { opacity: .88; }
.ref-link-copy.copied { background: linear-gradient(135deg, #0a3d18, #0D9488); }

/* Share row */
.ref-share-row  { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.ref-share-label { font-size: .7rem; font-weight: 600; color: #9CA3AF; white-space: nowrap; }
.ref-share-btn  { width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem; text-decoration: none; transition: transform .18s, box-shadow .18s; }
.ref-share-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.2); }
.s-wa { background:#25D366; color:#fff; }
.s-tg { background:#0088cc; color:#fff; }
.s-fb { background:#1877F2; color:#fff; }
.s-tw { background:#000;    color:#fff; }
.s-em { background:#6B7280; color:#fff; }

/* Members section wrapper */
.ref-members-card { background: #fff; border-radius: 20px; box-shadow: 0 2px 14px rgba(0,0,0,.07); border: 1px solid #F0F2F5; overflow: hidden; }
.ref-members-head { padding: 18px 22px; border-bottom: 1px solid #F3F4F6; display: flex; align-items: center; justify-content: space-between; }
.ref-members-title { font-size: .95rem; font-weight: 800; color: #111827; margin: 0; display: flex; align-items: center; gap: 8px; }
.ref-members-title i { color: #16A34A; font-size: 1.1rem; }
.ref-count-pill { background: #F0FDF4; color: #15803D; font-size: .68rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; border: 1px solid #BBF7D0; }

/* Member cards grid */
.ref-cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    padding: 20px;
}
.ref-mc {
    background: #fff;
    border: 1px solid #F0F2F5;
    border-radius: 16px;
    padding: 18px 16px 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
    transition: transform .2s, box-shadow .2s, border-color .2s;
}
.ref-mc:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,.10);
    border-color: #BBF7D0;
}
.ref-mc-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    margin-bottom: 12px;
}
.ref-mc-avatar {
    width: 48px; height: 48px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 1.1rem; color: #fff;
    box-shadow: 0 3px 10px rgba(0,0,0,.15);
}
.ref-mc-num {
    font-size: .65rem; font-weight: 700; color: #D1D5DB;
    background: #F9FAFB; border-radius: 20px;
    padding: 3px 8px; letter-spacing: .03em;
}
.ref-mc-name {
    font-size: .88rem; font-weight: 700; color: #111827;
    margin: 0 0 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ref-mc-username {
    font-size: .72rem; color: #9CA3AF; margin: 0 0 12px; font-weight: 500;
}
.ref-mc-username span { color: #16A34A; font-weight: 600; }
.ref-mc-divider { border: none; border-top: 1px solid #F3F4F6; margin: 0 0 10px; }
.ref-mc-foot {
    display: flex; align-items: center; gap: 5px;
    font-size: .68rem; color: #9CA3AF;
}
.ref-mc-foot i { color: #16A34A; font-size: .82rem; }
.ref-mc-ago { margin-left: auto; font-size: .65rem; color: #D1D5DB; }

/* Empty state */
.ref-empty { padding: 56px 20px; text-align: center; }
.ref-empty-icon { width: 72px; height: 72px; border-radius: 50%; background: #F0FDF4; color: #86EFAC; font-size: 2rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
.ref-empty h6 { font-size: .95rem; font-weight: 700; color: #374151; margin: 0 0 6px; }
.ref-empty p  { font-size: .82rem; color: #9CA3AF; margin: 0; }

/* Referral responsive */
@media (max-width: 1199px) {
    .ref-cards-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 767px) {
    .ref-stats-strip { grid-template-columns: 1fr 1fr; }
    .ref-stat:last-child { grid-column: 1 / -1; }
    .ref-invite-header { padding: 18px 18px 16px; }
    .ref-invite-body { padding: 18px; }
    .ref-members-head { padding: 14px 16px; }
    .ref-cards-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; padding: 14px; }
}
@media (max-width: 480px) {
    .ref-stats-strip { grid-template-columns: 1fr; }
    .ref-stat:last-child { grid-column: auto; }
    .ref-stat { padding: 16px 14px; gap: 12px; }
    .ref-stat-icon { width: 42px; height: 42px; font-size: 1.2rem; }
    .ref-stat-value { font-size: 1.3rem; }
    .ref-cards-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 12px; }
    .ref-mc { padding: 14px 12px 12px; }
    .ref-mc-avatar { width: 40px; height: 40px; font-size: .95rem; }
}

/* ══════════════════════════════════════
   E-PIN PAGE — BANKING STYLE
══════════════════════════════════════ */
.epin-page { display: flex; flex-direction: column; gap: 20px; }

/* Stats Strip */
.epin-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
.epin-stat { background: #fff; border-radius: 16px; padding: 18px 16px; display: flex; align-items: center; gap: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid #F0F2F5; }
.epin-stat-icon { width: 46px; height: 46px; border-radius: 13px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.epin-stat-icon.green  { background: #D1FAE5; color: #059669; }
.epin-stat-icon.amber  { background: #FEF3C7; color: #D97706; }
.epin-stat-icon.slate  { background: #F1F5F9; color: #64748B; }
.epin-stat-label { font-size: .68rem; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: .05em; margin: 0 0 3px; }
.epin-stat-value { font-size: 1.5rem; font-weight: 800; color: #111827; margin: 0; line-height: 1; }
.epin-stat-sub   { font-size: .68rem; color: #9CA3AF; margin: 3px 0 0; }

/* Action Row */
.epin-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.epin-action-card { background: #fff; border-radius: 20px; box-shadow: 0 2px 14px rgba(0,0,0,.07); border: 1px solid #F0F2F5; overflow: hidden; }
.epin-action-header { padding: 18px 22px 16px; border-bottom: 1px solid #F3F4F6; display: flex; align-items: center; gap: 10px; }
.epin-action-header-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; }
.epin-action-header-icon.green  { background: #D1FAE5; color: #059669; }
.epin-action-header-icon.violet { background: #EDE9FE; color: #7C3AED; }
.epin-action-title { font-size: .88rem; font-weight: 800; color: #111827; margin: 0; }
.epin-action-sub   { font-size: .7rem; color: #9CA3AF; margin: 2px 0 0; }
.epin-action-body  { padding: 20px 22px 22px; }

/* Amount display */
.epin-amount-display { display: flex; align-items: center; justify-content: space-between; background: #F0FDF4; border: 1.5px solid #BBF7D0; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; }
.epin-amount-label { font-size: .7rem; color: #6B7280; font-weight: 600; }
.epin-amount-value { font-size: 1.3rem; font-weight: 800; color: #15803D; }
.epin-amount-cur   { font-size: .78rem; font-weight: 600; color: #16A34A; margin-left: 3px; }

/* Button */
.epin-btn { width: 100%; padding: 13px; border: none; border-radius: 12px; font-size: .82rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: opacity .2s, transform .15s; }
.epin-btn:hover { opacity: .88; transform: translateY(-1px); }
.epin-btn.green { background: linear-gradient(135deg, #166534, #16A34A); color: #fff; }

/* Pin List */
.epin-list-card { background: #fff; border-radius: 20px; box-shadow: 0 2px 14px rgba(0,0,0,.07); border: 1px solid #F0F2F5; overflow: hidden; }
.epin-list-head { padding: 18px 22px; border-bottom: 1px solid #F3F4F6; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
.epin-list-title { font-size: .95rem; font-weight: 800; color: #111827; margin: 0; display: flex; align-items: center; gap: 8px; }
.epin-list-title i { color: #16A34A; }
.epin-filter { display: flex; gap: 8px; padding: 14px 22px; border-bottom: 1px solid #F3F4F6; flex-wrap: wrap; background: #FAFBFC; }
.epin-filter input, .epin-filter select { border: 1.5px solid #E5E7EB; border-radius: 10px; padding: 8px 12px; font-size: .78rem; color: #374151; background: #fff; outline: none; flex: 1; min-width: 120px; }
.epin-filter input:focus, .epin-filter select:focus { border-color: #16A34A; }
.epin-filter-btn { background: linear-gradient(135deg, #166534, #16A34A); color: #fff; border: none; border-radius: 10px; padding: 8px 16px; font-size: .78rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; white-space: nowrap; }
.epin-pin-row { display: flex; align-items: center; gap: 14px; padding: 14px 22px; border-bottom: 1px solid #F9FAFB; transition: background .15s; }
.epin-pin-row:last-child { border-bottom: none; }
.epin-pin-row:hover { background: #FAFBFC; }
.epin-status-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.epin-status-dot.unused { background: #16A34A; box-shadow: 0 0 0 3px #D1FAE5; }
.epin-status-dot.used   { background: #9CA3AF; box-shadow: 0 0 0 3px #F3F4F6; }
.epin-pin-code { flex: 1; min-width: 0; font-family: 'Courier New', monospace; font-size: .78rem; font-weight: 700; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; letter-spacing: .03em; }
.epin-pin-meta { font-size: .68rem; color: #9CA3AF; margin-top: 2px; }
.epin-pin-right { display: flex; align-items: center; gap: 10px; flex-shrink: 0; }
.epin-badge { font-size: .65rem; font-weight: 700; padding: 3px 9px; border-radius: 20px; white-space: nowrap; }
.epin-badge.unused { background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0; }
.epin-badge.used   { background: #F3F4F6; color: #6B7280; border: 1px solid #E5E7EB; }
.epin-copy-btn { width: 32px; height: 32px; border-radius: 9px; background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; display: flex; align-items: center; justify-content: center; font-size: .85rem; cursor: pointer; transition: background .15s, transform .15s; }
.epin-copy-btn:hover { background: #DCFCE7; transform: scale(1.08); }
.epin-copy-btn.done { background: #16A34A; color: #fff; border-color: #16A34A; }
.epin-amount-chip { font-size: .72rem; font-weight: 700; color: #15803D; background: #F0FDF4; border-radius: 8px; padding: 3px 8px; white-space: nowrap; flex-shrink: 0; }
.epin-empty { padding: 48px 20px; text-align: center; }
.epin-empty-icon { width: 68px; height: 68px; border-radius: 50%; background: #F0FDF4; color: #86EFAC; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
.epin-empty h6 { font-size: .9rem; font-weight: 700; color: #374151; margin: 0 0 5px; }
.epin-empty p  { font-size: .78rem; color: #9CA3AF; margin: 0; }

/* ══════════════════════════════════════
   PROFILE SETTINGS — BANKING STYLE
══════════════════════════════════════ */
.prof-page { display: flex; flex-direction: column; gap: 20px; }

/* Header card */
.prof-header-card {
    background: #fff; border-radius: 20px;
    box-shadow: 0 2px 14px rgba(0,0,0,.07); border: 1px solid #F0F2F5;
    padding: 24px 28px; display: flex; align-items: center; gap: 28px; flex-wrap: wrap;
}

/* Photo upload zone */
.prof-photo-zone { display: flex; flex-direction: column; align-items: center; gap: 10px; flex-shrink: 0; }
.prof-photo-label { cursor: pointer; display: block; }
.prof-photo-preview {
    width: 110px; height: 110px; border-radius: 50%;
    border: 3px solid #BBF7D0;
    background: #D1FAE5;
    overflow: hidden; position: relative;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 18px rgba(22,163,74,.18);
    transition: box-shadow .2s;
}
.prof-photo-preview:hover { box-shadow: 0 6px 24px rgba(22,163,74,.3); }
.prof-photo-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
.prof-photo-initials { font-size: 2rem; font-weight: 800; color: #059669; line-height: 1; }
.prof-photo-overlay {
    position: absolute; inset: 0;
    background: rgba(11,61,34,.55);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 4px; opacity: 0; transition: opacity .2s;
    color: #fff; font-size: .72rem; font-weight: 700;
}
.prof-photo-overlay i { font-size: 1.4rem; }
.prof-photo-preview:hover .prof-photo-overlay { opacity: 1; }
.prof-photo-hint { font-size: .65rem; color: #9CA3AF; text-align: center; display: flex; align-items: center; gap: 4px; margin: 0; }

.prof-header-info { flex: 1; min-width: 0; }
.prof-header-name { font-size: 1.15rem; font-weight: 800; color: #111827; margin: 0 0 2px; }
.prof-header-username { font-size: .78rem; color: #9CA3AF; font-weight: 500; margin: 0 0 10px; }
.prof-header-badges { display: flex; gap: 8px; flex-wrap: wrap; }
.prof-badge { font-size: .68rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: inline-flex; align-items: center; gap: 4px; }
.prof-badge.green { background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0; }
.prof-badge.slate { background: #F8FAFC; color: #64748B; border: 1px solid #E2E8F0; }

/* Two-column layout */
.prof-two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

/* Section card */
.prof-section-card {
    background: #fff; border-radius: 20px;
    box-shadow: 0 2px 14px rgba(0,0,0,.07); border: 1px solid #F0F2F5; overflow: hidden;
}
.prof-section-head {
    padding: 18px 22px 16px; border-bottom: 1px solid #F3F4F6;
    display: flex; align-items: center; gap: 12px;
}
.prof-section-icon {
    width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 1.05rem;
}
.prof-section-icon.green  { background: #D1FAE5; color: #059669; }
.prof-section-icon.violet { background: #EDE9FE; color: #7C3AED; }
.prof-section-icon.amber  { background: #FEF3C7; color: #D97706; }
.prof-section-title { font-size: .9rem; font-weight: 800; color: #111827; margin: 0 0 2px; }
.prof-section-sub   { font-size: .7rem; color: #9CA3AF; margin: 0; }
.prof-section-body  { padding: 20px 22px 22px; }

/* Field grid */
.prof-field-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.prof-field-grid--4 { grid-template-columns: repeat(4, 1fr); }
.prof-field { display: flex; flex-direction: column; gap: 6px; }
.prof-field label { font-size: .68rem; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: .05em; }
.prof-field input {
    border: 1.5px solid #E5E7EB; border-radius: 10px;
    padding: 10px 13px; font-size: .82rem; color: #111827;
    background: #fff; outline: none; width: 100%;
    transition: border-color .15s, box-shadow .15s;
}
.prof-field input:focus { border-color: #16A34A; box-shadow: 0 0 0 3px rgba(22,163,74,.1); }
.prof-readonly { background: #F9FAFB !important; color: #9CA3AF !important; cursor: not-allowed; }

/* Input with left icon */
.prof-input-icon-wrap { position: relative; }
.prof-input-icon-wrap input { padding-left: 36px; }
.prof-input-icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #9CA3AF; font-size: .95rem; pointer-events: none; }

/* Save button */
.prof-save-btn {
    background: linear-gradient(135deg, #166534, #16A34A);
    color: #fff; border: none; border-radius: 14px;
    padding: 15px 32px; font-size: .88rem; font-weight: 700;
    display: flex; align-items: center; gap: 8px;
    cursor: pointer; transition: opacity .2s, transform .15s;
    align-self: flex-end;
}
.prof-save-btn:hover { opacity: .88; transform: translateY(-1px); }

/* Profile responsive */
@media (max-width: 991px) {
    .prof-two-col { grid-template-columns: 1fr; }
    .prof-field-grid--4 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 575px) {
    .prof-header-card { padding: 18px; gap: 16px; flex-direction: column; align-items: flex-start; }
    .prof-photo-preview { width: 88px; height: 88px; }
    .prof-photo-initials { font-size: 1.6rem; }
    .prof-photo-zone { flex-direction: row; align-items: center; gap: 16px; }
    .prof-photo-hint { text-align: left; }
    .prof-section-body { padding: 16px; }
    .prof-section-head { padding: 14px 16px 12px; }
    .prof-field-grid, .prof-field-grid--4 { grid-template-columns: 1fr; }
    .prof-field { grid-column: auto !important; }
    .prof-save-btn { width: 100%; justify-content: center; }
    .prof-header-hint { display: none; }
}

/* ══════════════════════════════════════
   TRANSACTIONS PAGE — BANKING STYLE
══════════════════════════════════════ */
.txn-page { display: flex; flex-direction: column; gap: 20px; }

/* Filter card */
.txn-filter-card { background: #fff; border-radius: 18px; box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid #F0F2F5; padding: 18px 22px; }
.txn-filter-form { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
.txn-filter-field { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 140px; }
.txn-filter-field label { font-size: .65rem; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: .06em; }
.txn-filter-field input,
.txn-filter-field select {
    border: 1.5px solid #E5E7EB; border-radius: 10px;
    padding: 9px 12px; font-size: .8rem; color: #374151;
    background: #fff; outline: none; width: 100%;
    transition: border-color .15s;
}
.txn-filter-field input:focus,
.txn-filter-field select:focus { border-color: #16A34A; box-shadow: 0 0 0 3px rgba(22,163,74,.1); }
.txn-filter-input-wrap { position: relative; }
.txn-filter-input-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #9CA3AF; font-size: .9rem; pointer-events: none; }
.txn-filter-input-wrap input { padding-left: 32px; }
.txn-filter-btn-wrap { flex: 0 0 auto; min-width: unset; }
.txn-filter-btn {
    background: linear-gradient(135deg, #166534, #16A34A); color: #fff;
    border: none; border-radius: 10px; padding: 10px 20px;
    font-size: .8rem; font-weight: 700; cursor: pointer;
    display: flex; align-items: center; gap: 6px;
    transition: opacity .2s; white-space: nowrap; height: 100%;
}
.txn-filter-btn:hover { opacity: .88; }

/* List card */
.txn-list-card { background: #fff; border-radius: 20px; box-shadow: 0 2px 14px rgba(0,0,0,.07); border: 1px solid #F0F2F5; overflow: hidden; }
.txn-list-head { padding: 18px 22px; border-bottom: 1px solid #F3F4F6; display: flex; align-items: center; justify-content: space-between; }
.txn-list-title { font-size: .95rem; font-weight: 800; color: #111827; margin: 0; display: flex; align-items: center; gap: 8px; }
.txn-list-title i { color: #16A34A; }
.txn-count-pill { background: #F0FDF4; color: #15803D; font-size: .68rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; border: 1px solid #BBF7D0; }

/* Transaction row */
.txn-row {
    display: flex; align-items: center; gap: 14px;
    padding: 14px 22px; border-bottom: 1px solid #F9FAFB;
    transition: background .15s;
}
.txn-row:last-child { border-bottom: none; }
.txn-row:hover { background: #FAFBFC; }

/* Type icon */
.txn-type-icon {
    width: 40px; height: 40px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.txn-type-icon.credit { background: #D1FAE5; color: #059669; }
.txn-type-icon.debit  { background: #FEE2E2; color: #DC2626; }

/* Row info */
.txn-row-info { flex: 1; min-width: 0; }
.txn-row-desc { font-size: .83rem; font-weight: 600; color: #111827; margin: 0 0 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.txn-row-ref  { font-size: .68rem; color: #9CA3AF; margin: 0; display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
.txn-row-ref i { font-size: .75rem; }
.txn-row-dot  { color: #D1D5DB; }
.txn-inline-date { display: none; }

/* Right side */
.txn-row-right { text-align: right; flex-shrink: 0; }
.txn-row-amount { font-size: .92rem; font-weight: 800; margin: 0 0 2px; }
.txn-row-amount.credit { color: #059669; }
.txn-row-amount.debit  { color: #DC2626; }
.txn-row-charge { font-size: .67rem; color: #D97706; margin: 0 0 1px; }
.txn-row-bal    { font-size: .67rem; color: #9CA3AF; margin: 0; }

/* Date column */
.txn-row-date { text-align: right; flex-shrink: 0; min-width: 44px; }
.txn-row-date span { display: block; font-size: .72rem; font-weight: 700; color: #374151; }
.txn-row-year { font-size: .65rem !important; color: #D1D5DB !important; font-weight: 500 !important; }

/* Empty */
.txn-empty { padding: 52px 20px; text-align: center; }
.txn-empty-icon { width: 68px; height: 68px; border-radius: 50%; background: #F0FDF4; color: #86EFAC; font-size: 1.8rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
.txn-empty h6 { font-size: .9rem; font-weight: 700; color: #374151; margin: 0 0 5px; }
.txn-empty p  { font-size: .78rem; color: #9CA3AF; margin: 0; }

/* ══════════════════════════════════════
   GENEALOGY TREE PAGE — BANKING STYLE
══════════════════════════════════════ */
.tree-page { display: flex; flex-direction: column; gap: 20px; }

/* Header */
.tree-header-card {
    background: #fff; border-radius: 18px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid #F0F2F5;
    padding: 18px 22px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;
}
.tree-header-left { display: flex; align-items: center; gap: 14px; }
.tree-header-icon { width: 44px; height: 44px; border-radius: 13px; background: #D1FAE5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.tree-header-title { font-size: .95rem; font-weight: 800; color: #111827; margin: 0 0 2px; }
.tree-header-sub   { font-size: .72rem; color: #9CA3AF; margin: 0; }
.tree-search-form  { display: flex; gap: 8px; align-items: center; }
.tree-search-wrap  { display: flex; align-items: center; border: 1.5px solid #E5E7EB; border-radius: 10px; background: #F9FAFB; padding: 0 12px; gap: 8px; }
.tree-search-wrap i { color: #9CA3AF; font-size: .9rem; }
.tree-search-wrap input { border: none; background: transparent; outline: none; padding: 9px 0; font-size: .8rem; color: #374151; width: 170px; }
.tree-search-btn { background: linear-gradient(135deg, #166534, #16A34A); color: #fff; border: none; border-radius: 10px; padding: 9px 16px; font-size: .78rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: opacity .2s; white-space: nowrap; }
.tree-search-btn:hover { opacity: .88; }

/* Stage badge */
.tree-stage-badge {
    background: linear-gradient(135deg, #166534, #16A34A);
    color: #fff; font-size: .68rem; font-weight: 800;
    padding: 4px 12px; border-radius: 20px; letter-spacing: .04em;
    flex-shrink: 0;
}

/* Stage nav pills */
.tree-stage-nav {
    display: flex; gap: 8px; flex-wrap: wrap;
}
.tree-stage-pill {
    padding: 7px 18px; border-radius: 20px; font-size: .75rem; font-weight: 700;
    text-decoration: none; color: #6B7280;
    background: #fff; border: 1.5px solid #E5E7EB;
    transition: all .15s;
}
.tree-stage-pill:hover { border-color: #16A34A; color: #16A34A; }
.tree-stage-pill.active { background: linear-gradient(135deg, #166534, #16A34A); color: #fff; border-color: transparent; box-shadow: 0 4px 12px rgba(22,163,74,.3); }

/* Legend */
.tree-legend { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
.tree-legend-item { display: flex; align-items: center; gap: 6px; font-size: .72rem; color: #6B7280; font-weight: 500; }
.tree-legend-dot { width: 10px; height: 10px; border-radius: 50%; }
.tree-legend-dot.active { background: #059669; box-shadow: 0 0 0 3px #D1FAE5; }
.tree-legend-dot.empty  { background: #D1D5DB; box-shadow: 0 0 0 3px #F3F4F6; }

/* Tree card */
.tree-card { background: #fff; border-radius: 20px; box-shadow: 0 2px 14px rgba(0,0,0,.07); border: 1px solid #F0F2F5; overflow: hidden; }
.tree-scroll-wrap { padding: 32px 20px 28px; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.tree-canvas-outer { overflow: hidden; display: flex; justify-content: center; min-width: max-content; }
.tree-canvas { display: flex; flex-direction: column; align-items: center; gap: 0; transform-origin: top center; }
@media (max-width: 767px) {
    .tree-scroll-wrap { overflow: hidden; }
    .tree-canvas-outer { min-width: unset; width: 100%; }
}

/* Levels */
.tree-level { display: flex; justify-content: center; gap: 0; width: 100%; }
.tree-node-wrap { display: flex; flex-direction: column; align-items: center; flex: 1; }

/* Helper-output node styles */
.tree-node-wrap .user {
    display: flex; flex-direction: column; align-items: center; gap: 6px;
    cursor: pointer; padding: 8px 6px; border-radius: 14px;
    transition: background .15s, transform .15s;
    position: relative;
}
.tree-node-wrap .user:hover { background: #F0FDF4; transform: translateY(-2px); }
.tree-node-wrap .user img {
    width: 52px; height: 52px; border-radius: 50%;
    border: 3px solid #BBF7D0;
    object-fit: cover; background: #D1FAE5;
    box-shadow: 0 3px 12px rgba(22,163,74,.2);
}
.tree-node-wrap .user img.no-user {
    border-color: #E5E7EB; opacity: .4;
    box-shadow: none;
    filter: grayscale(1);
}
.tree-node-wrap .user .user-name {
    font-size: .62rem; font-weight: 700; color: #374151;
    margin: 0; text-align: center; max-width: 64px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.tree-node-wrap .user .line { display: none; } /* suppress helper's line span */

/* Connector system */
.tree-connector { display: flex; flex-direction: column; align-items: center; width: 100%; }
.tree-v-line       { width: 2px; height: 14px; background: #BBF7D0; }
.tree-v-line-short { width: 2px; height: 12px; background: #BBF7D0; }

/* H-spread: horizontal bar that fans out to N children */
.tree-h-spread { display: flex; justify-content: space-around; width: 100%; position: relative; }
.tree-h-spread::before {
    content: ''; position: absolute; top: 0; left: 50%; right: 50%;
    height: 2px; background: #BBF7D0;
}
.tree-h-spread-2 { width: 50%; }
.tree-h-spread-2::before { left: 25%; right: 25%; }
.tree-h-spread-4 { width: 75%; }
.tree-h-spread-4::before { left: 12.5%; right: 12.5%; }
.tree-h-spread-8 { width: 90%; }
.tree-h-spread-8::before { left: 6.25%; right: 6.25%; }
.tree-h-spread .tree-h-line { width: 2px; height: 10px; background: #BBF7D0; }

/* Vertical pairs / quads / octs before children */
.tree-v-pair, .tree-v-quad, .tree-v-oct, .tree-v-oct-pre {
    display: flex; justify-content: space-around; width: 100%;
}
.tree-v-pair  { width: 50%; }
.tree-v-quad  { width: 75%; }
.tree-v-oct   { width: 90%; }
.tree-v-oct-pre { width: 75%; }

/* Level spacing */
.tree-level-1 { margin-bottom: 0; }
.tree-level-2 .tree-node-wrap,
.tree-level-3 .tree-node-wrap,
.tree-level-4 .tree-node-wrap { padding: 0 4px; }

/* Modal */
.tree-modal-content { border: none; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.18); }
.tree-modal-header  { background: linear-gradient(135deg, #0B3D22, #166534); padding: 22px 22px 18px; display: flex; align-items: center; gap: 16px; position: relative; }
.tree-modal-avatar  { width: 58px; height: 58px; border-radius: 50%; border: 3px solid rgba(255,255,255,.3); overflow: hidden; background: rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.tree-modal-avatar img  { width: 100%; height: 100%; object-fit: cover; }
.tree-modal-avatar span { font-size: 1.4rem; font-weight: 800; color: #fff; }
.tree-modal-info { flex: 1; min-width: 0; }
.tree-modal-name   { font-size: .92rem; font-weight: 800; color: #fff; margin: 0 0 3px; }
.tree-modal-status { font-size: .72rem; color: rgba(255,255,255,.75); margin: 0 0 1px; }
.tree-modal-plan   { font-size: .68rem; color: rgba(255,255,255,.55); margin: 0; }
.tree-modal-close  { position: absolute; top: 14px; right: 16px; background: rgba(255,255,255,.15); border: none; color: #fff; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .9rem; cursor: pointer; transition: background .15s; }
.tree-modal-close:hover { background: rgba(255,255,255,.25); }
.tree-modal-body   { padding: 20px 22px 22px; }
.tree-modal-row    { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #F3F4F6; margin-bottom: 14px; }
.tree-modal-label  { font-size: .72rem; font-weight: 600; color: #9CA3AF; display: flex; align-items: center; gap: 6px; }
.tree-modal-label i{ color: #16A34A; }
.tree-modal-value  { font-size: .82rem; font-weight: 700; color: #111827; }
.tree-modal-btn    { display: flex; align-items: center; justify-content: center; gap: 8px; background: linear-gradient(135deg, #166534, #16A34A); color: #fff; border-radius: 12px; padding: 12px; font-size: .82rem; font-weight: 700; text-decoration: none; transition: opacity .2s; }
.tree-modal-btn:hover { opacity: .88; color: #fff; }

/* Tree responsive */
@media (max-width: 767px) {
    .tree-scroll-wrap { padding: 20px 12px 16px; }
    .tree-header-card { flex-direction: column; align-items: flex-start; padding: 16px; }
    .tree-search-form { width: 100%; }
    .tree-search-wrap { flex: 1; }
    .tree-search-wrap input { width: 100%; }
}

/* Transactions responsive */
@media (max-width: 575px) {
    .txn-list-head { padding: 14px 16px; }
    .txn-filter-card { padding: 14px 16px; }
    .txn-filter-form { gap: 8px; }
    .txn-filter-field { min-width: 100%; }

    /* Row: icon + right block stacked in 2 lines */
    .txn-row {
        padding: 12px 16px;
        gap: 10px;
        flex-wrap: wrap;
        align-items: flex-start;
    }
    .txn-type-icon { width: 36px; height: 36px; font-size: .85rem; flex-shrink: 0; margin-top: 2px; }

    /* Info takes full remaining width on first line */
    .txn-row-info { flex: 1; min-width: 0; }
    .txn-row-desc { font-size: .8rem; white-space: normal; }
    .txn-row-ref  { flex-wrap: wrap; font-size: .65rem; }

    /* Amount block moves beside info, right-aligned */
    .txn-row-right { flex-shrink: 0; text-align: right; }
    .txn-row-amount { font-size: .88rem; }
    .txn-row-bal, .txn-row-charge { font-size: .65rem; }

    /* Date folded into ref line — hide standalone date column */
    .txn-row-date { display: none; }
    .txn-inline-date { display: inline; }
}

/* ══════════════════════════════════════
   SHOP / PRODUCTS PAGE — BANKING STYLE
══════════════════════════════════════ */
.shop-page { display: flex; flex-direction: column; gap: 20px; }

/* Header */
.shop-header-card {
    background: #fff; border-radius: 18px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid #F0F2F5;
    padding: 18px 22px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;
}
.shop-header-left { display: flex; align-items: center; gap: 14px; }
.shop-header-icon  { width: 44px; height: 44px; border-radius: 13px; background: #FEF3C7; color: #D97706; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.shop-header-title { font-size: .95rem; font-weight: 800; color: #111827; margin: 0 0 2px; }
.shop-header-sub   { font-size: .72rem; color: #9CA3AF; margin: 0; }
.shop-state-pill   { display: flex; align-items: center; gap: 7px; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 20px; padding: 6px 14px; font-size: .75rem; font-weight: 600; color: #15803D; }
.shop-state-pill i { color: #16A34A; }

/* Filter bar */
.shop-filter-bar {
    display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
    background: #fff; border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 1px solid #F0F2F5;
    padding: 14px 18px;
}
.shop-search-wrap {
    flex: 1; min-width: 200px; position: relative;
    display: flex; align-items: center; gap: 10px;
    border: 1.5px solid #E5E7EB; border-radius: 12px;
    padding: 0 14px; background: #F9FAFB;
    transition: border-color .15s;
}
.shop-search-wrap:focus-within { border-color: #16A34A; background: #fff; }
.shop-search-wrap > i { color: #9CA3AF; font-size: .95rem; flex-shrink: 0; }
.shop-search-wrap input { flex: 1; border: none; background: transparent; outline: none; padding: 10px 0; font-size: .82rem; color: #374151; }
.shop-search-wrap input::placeholder { color: #D1D5DB; }
.shop-search-spinner { flex-shrink: 0; }
.shop-spinner-dot { width: 16px; height: 16px; border: 2px solid #BBF7D0; border-top-color: #16A34A; border-radius: 50%; animation: shopSpin .6s linear infinite; }
@keyframes shopSpin { to { transform: rotate(360deg); } }

.shop-state-select {
    border: 1.5px solid #E5E7EB; border-radius: 12px;
    padding: 10px 14px; font-size: .8rem; color: #374151;
    background: #F9FAFB; outline: none; min-width: 160px;
    transition: border-color .15s;
}
.shop-state-select:focus { border-color: #16A34A; background: #fff; }
.shop-clear-btn {
    border: 1.5px solid #E5E7EB; border-radius: 12px; background: #fff;
    padding: 10px 16px; font-size: .78rem; font-weight: 700; color: #6B7280;
    cursor: pointer; display: flex; align-items: center; gap: 6px;
    transition: border-color .15s, color .15s; white-space: nowrap;
}
.shop-clear-btn:hover { border-color: #DC2626; color: #DC2626; }

/* Results row */
.shop-results-row { display: flex; align-items: center; }
.shop-results-title { font-size: .88rem; font-weight: 800; color: #374151; margin: 0; }

/* Products grid */
.shop-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    transition: opacity .2s;
}

/* Product card */
.shop-card {
    background: #fff; border-radius: 18px;
    border: 1px solid #F0F2F5;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
    overflow: hidden; display: flex; flex-direction: column;
    transition: transform .2s, box-shadow .2s, border-color .2s;
}
.shop-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(0,0,0,.1); border-color: #BBF7D0; }

/* Card image */
.shop-card-img {
    height: 180px; overflow: hidden; position: relative;
    background: #F9FAFB;
}
.shop-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .3s; }
.shop-card:hover .shop-card-img img { transform: scale(1.04); }
.shop-card-img-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #D1D5DB; font-size: 2.5rem; }
.shop-card-cat {
    position: absolute; top: 10px; left: 10px;
    background: rgba(255,255,255,.92); color: #374151;
    font-size: .62rem; font-weight: 700; padding: 3px 9px;
    border-radius: 20px; letter-spacing: .03em;
    border: 1px solid #E5E7EB;
}

/* Card body */
.shop-card-body { padding: 14px 16px 16px; display: flex; flex-direction: column; flex: 1; }
.shop-card-name { font-size: .88rem; font-weight: 800; color: #111827; margin: 0 0 5px; line-height: 1.3; }
.shop-card-desc { font-size: .73rem; color: #9CA3AF; margin: 0 0 14px; line-height: 1.5; flex: 1; }

/* Card footer */
.shop-card-footer { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-top: auto; }
.shop-card-price-wrap { display: flex; flex-direction: column; gap: 2px; }
.shop-card-price { font-size: 1rem; font-weight: 800; color: #111827; line-height: 1; }
.shop-card-price-badge { font-size: .6rem; font-weight: 700; padding: 2px 7px; border-radius: 10px; }
.shop-card-price-badge.state   { background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0; }
.shop-card-price-badge.default { background: #FEF3C7; color: #D97706; border: 1px solid #FDE68A; }
.shop-card-price-hint { font-size: .65rem; color: #9CA3AF; display: flex; align-items: center; gap: 3px; }
.shop-card-btn {
    width: 38px; height: 38px; border-radius: 11px; flex-shrink: 0;
    border: none; cursor: pointer; font-size: 1rem;
    display: flex; align-items: center; justify-content: center;
    transition: transform .15s, box-shadow .15s;
}
.shop-card-btn.add-to-cart { background: linear-gradient(135deg, #166534, #16A34A); color: #fff; box-shadow: 0 4px 12px rgba(22,163,74,.3); }
.shop-card-btn.add-to-cart:hover { transform: scale(1.1); box-shadow: 0 6px 18px rgba(22,163,74,.4); }
.shop-card-btn.disabled { background: #F3F4F6; color: #D1D5DB; cursor: not-allowed; }

/* Loading */
.shop-loading { display: flex; flex-direction: column; align-items: center; gap: 12px; padding: 48px 20px; }
.shop-loading-ring { width: 40px; height: 40px; border: 3px solid #BBF7D0; border-top-color: #16A34A; border-radius: 50%; animation: shopSpin .7s linear infinite; }
.shop-loading p { font-size: .82rem; color: #9CA3AF; margin: 0; }

/* Empty */
.shop-empty { text-align: center; padding: 52px 20px; }
.shop-empty-icon { width: 72px; height: 72px; border-radius: 50%; background: #FEF3C7; color: #D97706; font-size: 2rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; }
.shop-empty h6 { font-size: .92rem; font-weight: 700; color: #374151; margin: 0 0 6px; }
.shop-empty p  { font-size: .78rem; color: #9CA3AF; margin: 0 0 16px; }
.shop-reset-btn { background: linear-gradient(135deg, #166534, #16A34A); color: #fff; border: none; border-radius: 12px; padding: 11px 22px; font-size: .8rem; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; }

/* Shop responsive */
@media (max-width: 1199px) { .shop-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 767px)  { .shop-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; } }
@media (max-width: 575px) {
    .shop-filter-bar { padding: 12px 14px; gap: 8px; }
    .shop-search-wrap, .shop-state-select { min-width: 100%; width: 100%; }
    .shop-clear-btn { width: 100%; justify-content: center; }
    .shop-header-card { padding: 14px 16px; }
    .shop-card-img { height: 150px; }
}
@media (max-width: 380px) { .shop-grid { grid-template-columns: 1fr; } }

/* E-Pin responsive */
@media (max-width: 767px) {
    .epin-actions { grid-template-columns: 1fr; }
    .epin-stats { grid-template-columns: 1fr 1fr; }
    .epin-stat:last-child { grid-column: 1 / -1; }
    .epin-pin-row { padding: 12px 16px; gap: 10px; }
    .epin-list-head, .epin-filter { padding: 14px 16px; }
    .epin-action-body { padding: 16px; }
    .epin-action-header { padding: 14px 16px 12px; }
}
@media (max-width: 480px) {
    .epin-stats { grid-template-columns: 1fr; }
    .epin-stat:last-child { grid-column: auto; }
    .epin-pin-code { font-size: .7rem; }
    .epin-filter { gap: 6px; }
}

/* ── Product Detail Page (.pd-*) ── */
.pd-page { max-width: 1100px; margin: 0 auto; padding: 20px 16px 40px; display: flex; flex-direction: column; gap: 20px; }

/* Hero two-column */
.pd-hero { display: grid; grid-template-columns: 1fr 1fr; gap: 0; background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; overflow: hidden; }

/* Gallery */
.pd-gallery { padding: 20px; display: flex; flex-direction: column; gap: 12px; border-right: 1px solid #F0F2F5; }
.pd-main-img-wrap .pd-thumb img { width: 100%; height: 320px; object-fit: cover; border-radius: 12px; display: block; }
.pd-thumbs-strip { margin-top: 4px; }
.pd-thumb-mini { cursor: pointer; border-radius: 8px; overflow: hidden; border: 2px solid transparent; transition: border-color .2s; margin: 2px; }
.pd-thumb-mini img { width: 100%; height: 70px; object-fit: cover; display: block; }
.pd-thumb-mini.active, .pd-thumb-mini:hover { border-color: #16A34A; }
.pd-gallery .sync1 .owl-stage-outer { border-radius: 12px; overflow: hidden; }

/* Info panel */
.pd-info { padding: 24px; display: flex; flex-direction: column; gap: 16px; }
.pd-cat-link { display: inline-flex; align-items: center; gap: 5px; font-size: .75rem; color: #16A34A; text-decoration: none; background: #F0FDF4; padding: 4px 12px; border-radius: 20px; font-weight: 600; }
.pd-cat-link:hover { background: #DCFCE7; color: #166534; }
.pd-title { font-size: 1.3rem; font-weight: 700; color: #111827; line-height: 1.35; margin: 0; }

.pd-badge { display: inline-flex; align-items: center; gap: 5px; font-size: .75rem; font-weight: 600; padding: 5px 12px; border-radius: 20px; width: fit-content; }
.pd-badge--in  { background: #F0FDF4; color: #166534; }
.pd-badge--out { background: #FEF2F2; color: #991B1B; }

/* State select */
.pd-state-wrap { display: flex; flex-direction: column; gap: 6px; }
.pd-label { font-size: .75rem; font-weight: 600; color: #6B7280; display: flex; align-items: center; gap: 5px; }
.pd-state-select { width: 100%; padding: 10px 36px 10px 14px; border: 1.5px solid #E5E7EB; border-radius: 10px; font-size: .875rem; color: #111827; background: #F9FAFB url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E") no-repeat right 12px center / 16px; outline: none; cursor: pointer; transition: border-color .2s; appearance: none; }
.pd-state-select:focus { border-color: #16A34A; background-color: #fff; }

/* Price box */
.pd-price-box { display: flex; align-items: baseline; gap: 10px; padding: 14px 16px; background: #F0FDF4; border-radius: 12px; border: 1px solid #BBF7D0; }
.pd-price-label { font-size: .75rem; font-weight: 600; color: #166534; text-transform: uppercase; letter-spacing: .04em; }
.pd-price { font-size: 1.5rem; font-weight: 800; color: #166534; }

/* Quantity & cart */
.pd-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.pd-qty { display: flex; align-items: center; border: 1.5px solid #E5E7EB; border-radius: 10px; overflow: hidden; background: #F9FAFB; }
.pd-qty-btn { width: 40px; height: 44px; border: none; background: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #374151; font-size: 1rem; transition: background .15s; }
.pd-qty-btn:hover { background: #F0FDF4; color: #16A34A; }
.pd-qty-input { width: 48px; height: 44px; border: none; border-left: 1.5px solid #E5E7EB; border-right: 1.5px solid #E5E7EB; background: #fff; text-align: center; font-size: .9rem; font-weight: 700; color: #111827; outline: none; }
.pd-cart-btn { flex: 1; min-width: 160px; height: 44px; background: linear-gradient(135deg, #166534 0%, #16A34A 100%); color: #fff; border: none; border-radius: 10px; font-size: .875rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: opacity .2s, transform .1s; }
.pd-cart-btn:hover { opacity: .88; transform: translateY(-1px); }
.pd-cart-btn:active { transform: translateY(0); }

/* Meta / Tags */
.pd-meta { display: flex; flex-direction: column; gap: 8px; }
.pd-meta-row { display: flex; align-items: flex-start; gap: 10px; }
.pd-meta-label { font-size: .7rem; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: .05em; min-width: 40px; padding-top: 4px; }
.pd-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.pd-tag { font-size: .72rem; padding: 3px 10px; background: #F3F4F6; color: #374151; border-radius: 20px; border: 1px solid #E5E7EB; }

/* Specifications */
.pd-specs { border-top: 1px solid #F0F2F5; padding-top: 14px; }
.pd-specs-title { font-size: .72rem; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 10px; }
.pd-spec-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #F9FAFB; font-size: .82rem; }
.pd-spec-name { color: #6B7280; font-weight: 500; }
.pd-spec-val  { color: #111827; font-weight: 600; text-align: right; max-width: 55%; }

/* Description card */
.pd-desc-card { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; overflow: hidden; }
.pd-desc-header { padding: 14px 20px; font-size: .85rem; font-weight: 700; color: #111827; border-bottom: 1px solid #F0F2F5; display: flex; align-items: center; gap: 8px; }
.pd-desc-header i { color: #16A34A; font-size: 1rem; }
.pd-desc-body { padding: 20px; font-size: .875rem; color: #374151; line-height: 1.7; }
.pd-desc-body img { max-width: 100%; border-radius: 8px; }

/* Share card */
.pd-share-card { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; padding: 14px 20px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.pd-share-label { font-size: .72rem; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: .06em; }
.pd-share-links { display: flex; gap: 10px; }
.pd-share-btn { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: .8rem; text-decoration: none; transition: transform .2s, opacity .2s; }
.pd-share-btn:hover { transform: translateY(-2px); opacity: .88; color: #fff; }
.pd-share--fb { background: #1877F2; }
.pd-share--tw { background: #1DA1F2; }
.pd-share--li { background: #0A66C2; }
.pd-share--ig { background: linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888); }

/* Related products */
.pd-related { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; overflow: hidden; }
.pd-related-header { padding: 14px 20px; font-size: .85rem; font-weight: 700; color: #111827; border-bottom: 1px solid #F0F2F5; display: flex; align-items: center; gap: 8px; }
.pd-related-header i { color: #16A34A; font-size: 1rem; }
.pd-related-scroll { display: flex; gap: 14px; overflow-x: auto; padding: 16px 20px; scrollbar-width: thin; scrollbar-color: #E5E7EB transparent; }
.pd-related-scroll::-webkit-scrollbar { height: 4px; }
.pd-related-scroll::-webkit-scrollbar-thumb { background: #E5E7EB; border-radius: 4px; }
.pd-rel-card { min-width: 160px; max-width: 160px; border: 1px solid #F0F2F5; border-radius: 12px; overflow: hidden; text-decoration: none; transition: box-shadow .2s, transform .2s; display: flex; flex-direction: column; background: #fff; }
.pd-rel-card:hover { box-shadow: 0 4px 16px rgba(22,100,52,.1); transform: translateY(-2px); }
.pd-rel-img { height: 120px; overflow: hidden; }
.pd-rel-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s; display: block; }
.pd-rel-card:hover .pd-rel-img img { transform: scale(1.05); }
.pd-rel-body { padding: 10px 12px; }
.pd-rel-name { font-size: .78rem; font-weight: 600; color: #111827; margin: 0 0 6px; line-height: 1.3; }
.pd-rel-stock { font-size: .68rem; font-weight: 600; padding: 2px 8px; border-radius: 10px; }
.pd-rel-stock.in  { background: #F0FDF4; color: #166534; }
.pd-rel-stock.out { background: #FEF2F2; color: #991B1B; }

/* Modals */
.pd-modal-content { border-radius: 16px; border: none; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,.15); }
.pd-modal-header { display: flex; align-items: center; gap: 12px; padding: 18px 20px; background: linear-gradient(135deg,#166534 0%,#16A34A 100%); }
.pd-modal-header--success { background: linear-gradient(135deg,#166534 0%,#16A34A 100%); }
.pd-modal-header--warn    { background: linear-gradient(135deg,#92400E 0%,#D97706 100%); }
.pd-modal-icon { width: 36px; height: 36px; background: rgba(255,255,255,.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.1rem; flex-shrink: 0; }
.pd-modal-title { color: #fff; font-size: .95rem; font-weight: 700; margin: 0; flex: 1; }
.pd-modal-close { background: rgba(255,255,255,.2); border: none; border-radius: 50%; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; color: #fff; cursor: pointer; font-size: .85rem; flex-shrink: 0; }
.pd-modal-close:hover { background: rgba(255,255,255,.3); }
.pd-modal-body { padding: 20px; font-size: .875rem; color: #374151; line-height: 1.6; }
.pd-modal-footer { padding: 14px 20px; border-top: 1px solid #F0F2F5; display: flex; gap: 10px; justify-content: flex-end; }
.pd-modal-btn-cancel  { padding: 8px 16px; background: #F3F4F6; border: none; border-radius: 8px; font-size: .82rem; font-weight: 600; color: #374151; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
.pd-modal-btn-cancel:hover  { background: #E5E7EB; color: #374151; }
.pd-modal-btn-confirm { padding: 8px 20px; background: linear-gradient(135deg,#166534,#16A34A); border: none; border-radius: 8px; font-size: .82rem; font-weight: 600; color: #fff; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
.pd-modal-btn-confirm:hover { opacity: .9; color: #fff; }

/* Product detail responsive */
@media (max-width: 767px) {
    .pd-hero { grid-template-columns: 1fr; }
    .pd-gallery { border-right: none; border-bottom: 1px solid #F0F2F5; padding: 16px; }
    .pd-main-img-wrap .pd-thumb img { height: 240px; }
    .pd-info { padding: 16px; }
    .pd-actions { flex-direction: column; }
    .pd-cart-btn { width: 100%; }
    .pd-qty { width: 100%; justify-content: center; }
}
@media (max-width: 480px) {
    .pd-main-img-wrap .pd-thumb img { height: 200px; }
    .pd-title { font-size: 1.1rem; }
    .pd-price { font-size: 1.25rem; }
    .pd-thumb-mini img { height: 55px; }
}

/* ── Cart Page (.cart-*) ── */
.cart-page { max-width: 1100px; margin: 0 auto; padding: 20px 16px 40px; display: flex; flex-direction: column; gap: 20px; }
.cart-header-card { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.cart-header-left { display: flex; align-items: center; gap: 14px; }
.cart-header-icon { width: 44px; height: 44px; background: linear-gradient(135deg,#166534,#16A34A); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.2rem; flex-shrink: 0; }
.cart-header-title { font-size: 1rem; font-weight: 700; color: #111827; margin: 0; }
.cart-header-sub { font-size: .78rem; color: #6B7280; margin: 0; }
.cart-continue-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border: 1.5px solid #16A34A; border-radius: 20px; color: #16A34A; font-size: .8rem; font-weight: 600; text-decoration: none; transition: background .2s, color .2s; }
.cart-continue-btn:hover { background: #F0FDF4; color: #166534; }

.cart-empty { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; padding: 60px 20px; text-align: center; }
.cart-empty-icon { font-size: 3rem; color: #D1D5DB; margin-bottom: 12px; }
.cart-empty h6 { font-size: 1.1rem; color: #374151; font-weight: 700; margin-bottom: 6px; }
.cart-empty p { color: #6B7280; font-size: .875rem; margin-bottom: 20px; }
.cart-empty-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; background: linear-gradient(135deg,#166534,#16A34A); color: #fff; border-radius: 20px; font-size: .875rem; font-weight: 600; text-decoration: none; }
.cart-empty-btn:hover { opacity: .9; color: #fff; }

.cart-layout { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }

/* Items card */
.cart-items-card { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; overflow: hidden; }
.cart-items-head { padding: 14px 20px; font-size: .85rem; font-weight: 700; color: #111827; border-bottom: 1px solid #F0F2F5; display: flex; align-items: center; gap: 8px; }
.cart-items-head i { color: #16A34A; }
.cart-item { display: flex; align-items: center; gap: 14px; padding: 16px 20px; border-bottom: 1px solid #F9FAFB; transition: background .15s; flex-wrap: wrap; }
.cart-item:last-child { border-bottom: none; }
.cart-item:hover { background: #FAFAFA; }
.cart-item-img { width: 72px; height: 72px; border-radius: 10px; overflow: hidden; flex-shrink: 0; border: 1px solid #F0F2F5; }
.cart-item-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
.cart-item-img-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #F3F4F6; color: #9CA3AF; font-size: 1.4rem; }
.cart-item-info { flex: 1; min-width: 120px; }
.cart-item-name { font-size: .875rem; font-weight: 700; color: #111827; margin: 0 0 4px; }
.cart-item-state { font-size: .72rem; color: #6B7280; margin: 0 0 4px; display: flex; align-items: center; gap: 4px; }
.cart-item-state i { color: #16A34A; }
.cart-item-price { font-size: .82rem; color: #16A34A; font-weight: 600; margin: 0; }
.cart-item-price span { color: #9CA3AF; font-weight: 400; }
.cart-item-qty { display: flex; align-items: center; border: 1.5px solid #E5E7EB; border-radius: 8px; overflow: hidden; background: #F9FAFB; }
.cart-qty-btn { width: 34px; height: 36px; border: none; background: none; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #374151; font-size: .9rem; transition: background .15s; }
.cart-qty-btn:hover:not([disabled]) { background: #F0FDF4; color: #16A34A; }
.cart-qty-btn[disabled] { opacity: .35; cursor: not-allowed; }
.cart-qty-input { width: 44px; height: 36px; border: none; border-left: 1.5px solid #E5E7EB; border-right: 1.5px solid #E5E7EB; background: #fff; text-align: center; font-size: .85rem; font-weight: 700; color: #111827; outline: none; }
.cart-item-total { font-size: .95rem; font-weight: 700; color: #111827; min-width: 80px; text-align: right; }
.cart-item-remove { background: none; border: none; color: #9CA3AF; cursor: pointer; font-size: 1rem; padding: 6px; border-radius: 8px; transition: background .15s, color .15s; display: flex; align-items: center; }
.cart-item-remove:hover { background: #FEF2F2; color: #DC2626; }

/* Summary card */
.cart-summary-card { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; overflow: hidden; position: sticky; top: 80px; }
.cart-summary-head { padding: 14px 20px; font-size: .85rem; font-weight: 700; color: #111827; border-bottom: 1px solid #F0F2F5; display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg,#166534,#16A34A); color: #fff; }
.cart-summary-head i { font-size: 1rem; }
.cart-summary-body { padding: 16px 20px; display: flex; flex-direction: column; gap: 10px; }
.cart-summary-row { display: flex; justify-content: space-between; align-items: center; font-size: .875rem; color: #374151; }
.cart-free { color: #16A34A; font-weight: 600; }
.cart-summary-divider { height: 1px; background: #F0F2F5; margin: 4px 0; }
.cart-summary-total { font-size: 1rem; font-weight: 700; color: #111827; }
.cart-wallet-box { background: #F9FAFB; border-radius: 10px; padding: 12px; border: 1px solid #F0F2F5; display: flex; flex-direction: column; gap: 8px; }
.cart-wallet-row { display: flex; justify-content: space-between; align-items: center; font-size: .82rem; font-weight: 600; color: #374151; }
.cart-wallet-row i { color: #16A34A; }
.cart-wallet-amt { color: #166534; font-weight: 700; }
.cart-wallet-alert { font-size: .75rem; padding: 6px 10px; border-radius: 8px; display: flex; align-items: center; gap: 5px; font-weight: 500; }
.cart-wallet-alert.ok { background: #F0FDF4; color: #166534; }
.cart-wallet-alert.warn { background: #FFFBEB; color: #92400E; }
.cart-checkout-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; background: linear-gradient(135deg,#166534,#16A34A); color: #fff; border: none; border-radius: 10px; font-size: .875rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: opacity .2s; }
.cart-checkout-btn:hover { opacity: .88; color: #fff; }
.cart-checkout-btn.disabled { background: #9CA3AF; cursor: not-allowed; opacity: 1; }
.cart-topup-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; border: 1.5px solid #F59E0B; border-radius: 10px; color: #92400E; font-size: .82rem; font-weight: 600; text-decoration: none; background: #FFFBEB; }
.cart-topup-btn:hover { background: #FEF3C7; color: #78350F; }
.cart-secure-note { font-size: .72rem; color: #9CA3AF; text-align: center; display: flex; align-items: center; justify-content: center; gap: 5px; margin: 0; }

/* Cart responsive */
@media (max-width: 991px) { .cart-layout { grid-template-columns: 1fr; } .cart-summary-card { position: static; } }
@media (max-width: 575px) {
    .cart-item { gap: 10px; }
    .cart-item-total { width: 100%; text-align: left; font-size: .85rem; }
    .cart-item-img { width: 56px; height: 56px; }
}

/* ── Orders Page (.ord-*) ── */
.ord-page { max-width: 1100px; margin: 0 auto; padding: 20px 16px 40px; display: flex; flex-direction: column; gap: 20px; }
.ord-header-card { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.ord-header-left { display: flex; align-items: center; gap: 14px; }
.ord-header-icon { width: 44px; height: 44px; background: linear-gradient(135deg,#166534,#16A34A); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.2rem; flex-shrink: 0; }
.ord-header-title { font-size: 1rem; font-weight: 700; color: #111827; margin: 0; }
.ord-header-sub { font-size: .78rem; color: #6B7280; margin: 0; }
.ord-shop-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border: 1.5px solid #16A34A; border-radius: 20px; color: #16A34A; font-size: .8rem; font-weight: 600; text-decoration: none; }
.ord-shop-btn:hover { background: #F0FDF4; color: #166534; }

.ord-empty { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; padding: 60px 20px; text-align: center; }
.ord-empty-icon { font-size: 3rem; color: #D1D5DB; margin-bottom: 12px; }
.ord-empty h6 { font-size: 1.1rem; color: #374151; font-weight: 700; margin-bottom: 6px; }
.ord-empty p { color: #6B7280; font-size: .875rem; margin-bottom: 20px; }
.ord-empty-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 24px; background: linear-gradient(135deg,#166534,#16A34A); color: #fff; border-radius: 20px; font-size: .875rem; font-weight: 600; text-decoration: none; }
.ord-empty-btn:hover { opacity: .9; color: #fff; }

/* Stats */
.ord-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.ord-stat { background: #fff; border-radius: 14px; border: 1px solid #F0F2F5; padding: 16px; text-align: center; }
.ord-stat-val { display: block; font-size: 1.5rem; font-weight: 800; color: #111827; }
.ord-stat-val.pend { color: #D97706; }
.ord-stat-val.ok   { color: #16A34A; }
.ord-stat-val.warn { color: #DC2626; }
.ord-stat-label { font-size: .72rem; color: #9CA3AF; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }

/* Filters */
.ord-filter-bar { background: #fff; border-radius: 14px; border: 1px solid #F0F2F5; padding: 16px 20px; display: flex; gap: 14px; flex-wrap: wrap; }
.ord-filter-group { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 140px; }
.ord-search-group { min-width: 220px; }
.ord-filter-label { font-size: .7rem; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: .05em; }
.ord-select { padding: 8px 30px 8px 10px; border: 1.5px solid #E5E7EB; border-radius: 8px; font-size: .82rem; color: #111827; background: #F9FAFB url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E") no-repeat right 8px center / 14px; appearance: none; outline: none; }
.ord-select:focus { border-color: #16A34A; }
.ord-search-wrap { position: relative; display: flex; align-items: center; }
.ord-search-wrap i { position: absolute; left: 10px; color: #9CA3AF; font-size: 1rem; }
.ord-search-input { width: 100%; padding: 8px 10px 8px 32px; border: 1.5px solid #E5E7EB; border-radius: 8px; font-size: .82rem; color: #111827; background: #F9FAFB; outline: none; }
.ord-search-input:focus { border-color: #16A34A; background: #fff; }

/* Order cards */
.ord-list { display: flex; flex-direction: column; gap: 14px; }
.ord-card { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; overflow: hidden; transition: box-shadow .2s, transform .2s; }
.ord-card:hover { box-shadow: 0 6px 24px rgba(22,100,52,.08); transform: translateY(-1px); }
.ord-card-head { display: flex; align-items: flex-start; gap: 14px; padding: 16px 20px; border-bottom: 1px solid #F0F2F5; background: #FAFAFA; flex-wrap: wrap; }
.ord-status-icon { width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: #fff; flex-shrink: 0; }
.ord-status-icon.pend   { background: linear-gradient(135deg,#D97706,#F59E0B); }
.ord-status-icon.ok     { background: linear-gradient(135deg,#166534,#16A34A); }
.ord-status-icon.cancel { background: linear-gradient(135deg,#991B1B,#DC2626); }
.ord-card-meta { flex: 1; min-width: 140px; }
.ord-invoice { font-size: .9rem; font-weight: 700; color: #111827; margin: 0 0 3px; }
.ord-date { font-size: .75rem; color: #6B7280; margin: 0 0 3px; display: flex; align-items: center; gap: 4px; }
.ord-state { font-size: .75rem; color: #6B7280; margin: 0; display: flex; align-items: center; gap: 4px; }
.ord-state i { color: #16A34A; }
.ord-card-right { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; margin-left: auto; }
.ord-amount { font-size: 1.1rem; font-weight: 800; color: #16A34A; margin: 0; }
.ord-badge { font-size: .68rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: .04em; }
.ord-badge--pend   { background: #FFFBEB; color: #92400E; }
.ord-badge--ok     { background: #F0FDF4; color: #166534; }
.ord-badge--cancel { background: #FEF2F2; color: #991B1B; }

.ord-card-items { padding: 14px 20px; border-bottom: 1px solid #F9FAFB; }
.ord-items-label { font-size: .72rem; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
.ord-items-label i { color: #16A34A; }
.ord-items-row { display: flex; gap: 12px; flex-wrap: wrap; }
.ord-item-chip { display: flex; align-items: center; gap: 8px; background: #F9FAFB; border: 1px solid #F0F2F5; border-radius: 10px; padding: 6px 10px; min-width: 0; }
.ord-item-chip img { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
.ord-item-chip-placeholder { width: 40px; height: 40px; background: #E5E7EB; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #9CA3AF; font-size: .85rem; flex-shrink: 0; }
.ord-item-name { font-size: .75rem; font-weight: 600; color: #111827; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px; }
.ord-item-qty { font-size: .7rem; color: #6B7280; margin: 0; }
.ord-item-more { display: flex; align-items: center; justify-content: center; width: 60px; height: 54px; background: #F3F4F6; border-radius: 10px; font-size: .75rem; font-weight: 700; color: #6B7280; }

.ord-card-foot { display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; background: #FAFAFA; flex-wrap: wrap; gap: 8px; }
.ord-card-foot-left { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.ord-foot-note { font-size: .75rem; font-weight: 500; padding: 4px 10px; border-radius: 20px; display: flex; align-items: center; gap: 4px; }
.ord-foot-note.pend { background: #FFFBEB; color: #92400E; }
.ord-foot-note.ok   { background: #F0FDF4; color: #166534; }
.ord-foot-invoice { font-size: .75rem; color: #6B7280; }
.ord-foot-invoice code { color: #16A34A; font-size: .75rem; }
.ord-card-foot-right { display: flex; align-items: center; gap: 8px; }
.ord-btn-copy { display: inline-flex; align-items: center; gap: 5px; padding: 7px 14px; border: 1.5px solid #E5E7EB; border-radius: 20px; background: #fff; color: #374151; font-size: .78rem; font-weight: 600; cursor: pointer; transition: border-color .2s, color .2s; }
.ord-btn-copy:hover { border-color: #16A34A; color: #16A34A; }
.ord-btn-view { display: inline-flex; align-items: center; gap: 5px; padding: 7px 16px; background: linear-gradient(135deg,#166534,#16A34A); color: #fff; border-radius: 20px; font-size: .78rem; font-weight: 600; text-decoration: none; }
.ord-btn-view:hover { opacity: .88; color: #fff; }

.ord-pagination { background: #fff; border-radius: 14px; border: 1px solid #F0F2F5; padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
.ord-page-info { font-size: .78rem; color: #6B7280; }

/* Orders responsive */
@media (max-width: 767px) {
    .ord-stats { grid-template-columns: repeat(2, 1fr); }
    .ord-card-head { gap: 10px; }
    .ord-card-right { margin-left: 0; width: 100%; flex-direction: row; align-items: center; }
}
@media (max-width: 480px) {
    .ord-stats { grid-template-columns: repeat(2, 1fr); }
    .ord-filter-bar { flex-direction: column; }
    .ord-card-foot { flex-direction: column; align-items: flex-start; }
}

/* ── Checkout Page (.chk-*) ── */
.chk-page { max-width: 1100px; margin: 0 auto; padding: 20px 16px 40px; display: flex; flex-direction: column; gap: 16px; }
.chk-empty { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; padding: 60px 20px; text-align: center; }
.chk-empty-icon { font-size: 3rem; color: #D1D5DB; margin-bottom: 12px; }
.chk-empty h6 { font-size: 1.1rem; color: #374151; font-weight: 700; margin-bottom: 6px; }
.chk-empty p { color: #6B7280; font-size: .875rem; margin-bottom: 20px; }
.chk-back-btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; background: linear-gradient(135deg,#166534,#16A34A); color: #fff; border-radius: 20px; font-size: .875rem; font-weight: 600; text-decoration: none; }
.chk-back-btn:hover { opacity: .9; color: #fff; }
.chk-layout { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
.chk-items-card, .chk-pay-card { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; overflow: hidden; }
.chk-card-head { padding: 14px 20px; font-size: .85rem; font-weight: 700; color: #111827; border-bottom: 1px solid #F0F2F5; display: flex; align-items: center; gap: 8px; }
.chk-card-head i { color: #16A34A; font-size: 1rem; }
.chk-card-head--pay { background: linear-gradient(135deg,#166534,#16A34A); color: #fff; }
.chk-card-head--pay i { color: #fff; }
.chk-items-body { padding: 4px 0; }
.chk-item { display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid #F9FAFB; }
.chk-item:last-child { border-bottom: none; }
.chk-item-img { width: 60px; height: 60px; border-radius: 10px; overflow: hidden; flex-shrink: 0; border: 1px solid #F0F2F5; }
.chk-item-img img { width: 100%; height: 100%; object-fit: cover; }
.chk-item-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #F3F4F6; color: #9CA3AF; }
.chk-item-info { flex: 1; }
.chk-item-name { font-size: .875rem; font-weight: 700; color: #111827; margin: 0 0 4px; }
.chk-item-state { font-size: .72rem; color: #6B7280; margin: 0; display: flex; align-items: center; gap: 4px; }
.chk-item-state i { color: #16A34A; }
.chk-item-right { text-align: right; flex-shrink: 0; }
.chk-item-qty { font-size: .78rem; color: #9CA3AF; display: block; }
.chk-item-total { font-size: .9rem; font-weight: 700; color: #111827; display: block; }
.chk-item-each { font-size: .68rem; color: #9CA3AF; }
.chk-info-box { margin: 0; padding: 16px 20px; background: #F0FDF4; border-top: 1px solid #BBF7D0; }
.chk-info-title { font-size: .78rem; font-weight: 700; color: #166534; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
.chk-info-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 6px; }
.chk-info-list li { font-size: .78rem; color: #374151; display: flex; align-items: flex-start; gap: 8px; }
.chk-info-list li i { color: #16A34A; margin-top: 1px; flex-shrink: 0; }
.chk-pay-body { padding: 16px 20px; display: flex; flex-direction: column; gap: 10px; }
.chk-pay-row { display: flex; justify-content: space-between; font-size: .875rem; color: #374151; }
.chk-free { color: #16A34A; font-weight: 600; }
.chk-pay-divider { height: 1px; background: #F0F2F5; }
.chk-pay-total { font-size: 1rem; font-weight: 700; color: #111827; }
.chk-wallet-box { background: #F9FAFB; border-radius: 10px; padding: 12px; border: 1px solid #F0F2F5; display: flex; flex-direction: column; gap: 8px; }
.chk-wallet-row { display: flex; justify-content: space-between; font-size: .82rem; font-weight: 600; color: #374151; }
.chk-wallet-row i { color: #16A34A; }
.chk-wallet-amt { color: #166534; font-weight: 700; }
.chk-wallet-alert { font-size: .75rem; padding: 6px 10px; border-radius: 8px; display: flex; align-items: flex-start; gap: 5px; font-weight: 500; line-height: 1.4; }
.chk-wallet-alert.ok { background: #F0FDF4; color: #166534; }
.chk-wallet-alert.warn { background: #FFFBEB; color: #92400E; }
.chk-pay-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; background: linear-gradient(135deg,#166534,#16A34A); color: #fff; border: none; border-radius: 10px; font-size: .875rem; font-weight: 600; cursor: pointer; transition: opacity .2s; }
.chk-pay-btn:hover { opacity: .88; }
.chk-pay-btn.disabled { background: #9CA3AF; cursor: not-allowed; }
.chk-topup-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; border: 1.5px solid #F59E0B; border-radius: 10px; color: #92400E; font-size: .82rem; font-weight: 600; text-decoration: none; background: #FFFBEB; }
.chk-topup-btn:hover { background: #FEF3C7; color: #78350F; }
.chk-back-link { display: flex; align-items: center; justify-content: center; gap: 6px; font-size: .8rem; color: #6B7280; text-decoration: none; padding: 6px; }
.chk-back-link:hover { color: #16A34A; }
.chk-secure { font-size: .72rem; color: #9CA3AF; text-align: center; display: flex; align-items: center; justify-content: center; gap: 5px; margin: 0; }
.chk-spinner { width: 40px; height: 40px; border: 3px solid #F0F2F5; border-top-color: #16A34A; border-radius: 50%; animation: chk-spin .7s linear infinite; margin: 12px auto; }
@keyframes chk-spin { to { transform: rotate(360deg); } }
.chk-alert { padding: 12px 16px; border-radius: 10px; font-size: .85rem; display: flex; align-items: center; gap: 8px; margin-bottom: 12px; position: relative; }
.chk-alert--ok   { background: #F0FDF4; color: #166534; border: 1px solid #BBF7D0; }
.chk-alert--warn { background: #FEF2F2; color: #991B1B; border: 1px solid #FECACA; }
.chk-alert-close { background: none; border: none; cursor: pointer; margin-left: auto; font-size: 1.1rem; opacity: .6; color: inherit; }
@media (max-width: 991px) { .chk-layout { grid-template-columns: 1fr; } }
@media (max-width: 575px) { .chk-item { gap: 8px; } .chk-item-img { width: 48px; height: 48px; } }

/* ── Order Detail Page (.osd-*) ── */
.osd-page { max-width: 1100px; margin: 0 auto; padding: 20px 16px 40px; display: flex; flex-direction: column; gap: 20px; }
.osd-header-card { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.osd-header-left { display: flex; align-items: center; gap: 14px; }
.osd-header-icon { width: 44px; height: 44px; background: linear-gradient(135deg,#166534,#16A34A); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.2rem; flex-shrink: 0; }
.osd-header-title { font-size: 1rem; font-weight: 700; color: #111827; margin: 0; }
.osd-header-sub { font-size: .78rem; color: #6B7280; margin: 0; }
.osd-back-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border: 1.5px solid #16A34A; border-radius: 20px; color: #16A34A; font-size: .8rem; font-weight: 600; text-decoration: none; }
.osd-back-btn:hover { background: #F0FDF4; color: #166534; }
.osd-layout { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }
.osd-left, .osd-right { display: flex; flex-direction: column; gap: 16px; }

/* Status card */
.osd-status-card { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; padding: 20px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.osd-status-icon { width: 56px; height: 56px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #fff; flex-shrink: 0; }
.osd-status-icon.pend     { background: linear-gradient(135deg,#D97706,#F59E0B); }
.osd-status-icon.ok       { background: linear-gradient(135deg,#166534,#16A34A); }
.osd-status-icon.redeemed { background: linear-gradient(135deg,#0369A1,#0EA5E9); }
.osd-status-icon.cancel   { background: linear-gradient(135deg,#991B1B,#DC2626); }
.osd-status-info { flex: 1; }
.osd-status-label { font-size: 1rem; font-weight: 700; color: #111827; margin: 0 0 4px; text-transform: capitalize; }
.osd-status-date { font-size: .78rem; color: #6B7280; margin: 0; }
.osd-status-redeemed { font-size: .75rem; color: #16A34A; margin: 4px 0 0; display: flex; align-items: center; gap: 4px; }
.osd-status-amount { text-align: right; flex-shrink: 0; }
.osd-amount { font-size: 1.4rem; font-weight: 800; color: #16A34A; display: block; }
.osd-amount-label { font-size: .7rem; color: #9CA3AF; text-transform: uppercase; letter-spacing: .04em; }

/* Generic card */
.osd-card { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; overflow: hidden; }
.osd-card-head { padding: 14px 20px; font-size: .85rem; font-weight: 700; color: #111827; border-bottom: 1px solid #F0F2F5; display: flex; align-items: center; gap: 8px; }
.osd-card-head i { color: #16A34A; font-size: 1rem; }
.osd-card-head--green { background: linear-gradient(135deg,#166534,#16A34A); color: #fff; }
.osd-card-head--green i { color: #fff; }

/* Order items */
.osd-item { display: flex; align-items: center; gap: 12px; padding: 14px 20px; border-bottom: 1px solid #F9FAFB; }
.osd-item:last-child { border-bottom: none; }
.osd-item-img { width: 64px; height: 64px; border-radius: 10px; overflow: hidden; flex-shrink: 0; border: 1px solid #F0F2F5; }
.osd-item-img img { width: 100%; height: 100%; object-fit: cover; }
.osd-item-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #F3F4F6; color: #9CA3AF; }
.osd-item-info { flex: 1; }
.osd-item-name { font-size: .875rem; font-weight: 700; color: #111827; margin: 0 0 3px; }
.osd-item-desc { font-size: .75rem; color: #6B7280; margin: 0 0 4px; }
.osd-item-price { font-size: .8rem; color: #16A34A; font-weight: 600; margin: 0; }
.osd-item-right { text-align: right; flex-shrink: 0; }
.osd-item-qty { font-size: .78rem; color: #9CA3AF; display: block; }
.osd-item-total { font-size: .95rem; font-weight: 700; color: #111827; display: block; }

/* Invoice */
.osd-invoice-body { padding: 16px 20px; display: flex; flex-direction: column; gap: 14px; }
.osd-invoice-row { display: flex; flex-direction: column; gap: 6px; }
.osd-invoice-label { font-size: .7rem; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: .05em; }
.osd-code-wrap { display: flex; gap: 0; }
.osd-code-input { flex: 1; padding: 9px 12px; border: 1.5px solid #E5E7EB; border-right: none; border-radius: 8px 0 0 8px; font-size: .85rem; font-weight: 700; color: #16A34A; background: #F9FAFB; outline: none; }
.osd-copy-btn { padding: 9px 14px; background: linear-gradient(135deg,#166534,#16A34A); color: #fff; border: none; border-radius: 0 8px 8px 0; font-size: .8rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px; transition: opacity .2s; }
.osd-copy-btn:hover { opacity: .88; }
.osd-code-hint { font-size: .72rem; color: #9CA3AF; }
.osd-inv-badge { font-size: .78rem; font-weight: 600; padding: 5px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 5px; width: fit-content; }
.osd-inv-badge.ok   { background: #F0FDF4; color: #166534; }
.osd-inv-badge.pend { background: #FFFBEB; color: #92400E; }
.osd-redeem-steps { background: #F0FDF4; border-radius: 10px; padding: 14px; border: 1px solid #BBF7D0; }
.osd-redeem-title { font-size: .78rem; font-weight: 700; color: #166534; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
.osd-redeem-list { margin: 0; padding-left: 18px; font-size: .78rem; color: #374151; display: flex; flex-direction: column; gap: 4px; }
.osd-redeem-list code { color: #16A34A; font-size: .75rem; background: #DCFCE7; padding: 1px 5px; border-radius: 4px; }

/* Summary */
.osd-summary-body { padding: 16px 20px; display: flex; flex-direction: column; gap: 10px; }
.osd-sum-row { display: flex; justify-content: space-between; font-size: .875rem; color: #374151; }
.osd-free { color: #16A34A; font-weight: 600; }
.osd-sum-div { height: 1px; background: #F0F2F5; }
.osd-sum-total { font-size: 1rem; font-weight: 700; color: #111827; }
.osd-payment-method { display: flex; align-items: center; gap: 12px; background: #F9FAFB; border-radius: 10px; padding: 12px; border: 1px solid #F0F2F5; margin-top: 4px; }
.osd-pm-icon { width: 36px; height: 36px; background: linear-gradient(135deg,#166534,#16A34A); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1rem; flex-shrink: 0; }
.osd-pm-name { font-size: .85rem; font-weight: 600; color: #111827; margin: 0; }
.osd-pm-sub { font-size: .72rem; color: #6B7280; margin: 0; }

/* Timeline */
.osd-timeline { padding: 16px 20px; display: flex; flex-direction: column; gap: 0; }
.osd-tl-item { display: flex; gap: 12px; position: relative; padding-bottom: 16px; }
.osd-tl-item:last-child { padding-bottom: 0; }
.osd-tl-item.has-line::before { content: ''; position: absolute; left: 19px; top: 40px; bottom: 0; width: 2px; background: #F0F2F5; }
.osd-tl-dot { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg,#166534,#16A34A); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1rem; flex-shrink: 0; }
.osd-tl-info { padding-top: 8px; }
.osd-tl-label { font-size: .85rem; font-weight: 600; color: #111827; margin: 0 0 2px; }
.osd-tl-date { font-size: .75rem; color: #6B7280; margin: 0; }

/* Actions */
.osd-actions { padding: 16px 20px; display: flex; flex-direction: column; gap: 8px; }
.osd-action-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 16px; border-radius: 20px; font-size: .82rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: opacity .2s, background .2s; }
.osd-action-btn.outline { border: 1.5px solid #E5E7EB; background: #fff; color: #374151; }
.osd-action-btn.outline:hover { border-color: #16A34A; color: #16A34A; }
.osd-action-btn.green { background: linear-gradient(135deg,#166534,#16A34A); color: #fff; border: none; }
.osd-action-btn.green:hover { opacity: .88; color: #fff; }

/* Order detail responsive */
@media (max-width: 991px) { .osd-layout { grid-template-columns: 1fr; } }
@media (max-width: 767px) {
    .osd-status-card { flex-wrap: wrap; }
    .osd-status-amount { width: 100%; text-align: left; }
}
@media (max-width: 480px) {
    .osd-item { gap: 8px; }
    .osd-item-img { width: 50px; height: 50px; }
}

/* ── Deposit History Page (.dph-*) ── */
.dph-page { max-width: 1100px; margin: 0 auto; padding: 20px 16px 40px; display: flex; flex-direction: column; gap: 16px; }

/* Header */
.dph-header-card { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; padding: 16px 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.dph-header-left { display: flex; align-items: center; gap: 14px; }
.dph-header-icon { width: 44px; height: 44px; background: linear-gradient(135deg,#166534,#16A34A); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.2rem; flex-shrink: 0; }
.dph-header-title { font-size: 1rem; font-weight: 700; color: #111827; margin: 0; }
.dph-header-sub { font-size: .78rem; color: #6B7280; margin: 0; }
.dph-deposit-btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 20px; background: linear-gradient(135deg,#166534,#16A34A); color: #fff; border-radius: 20px; font-size: .82rem; font-weight: 600; text-decoration: none; transition: opacity .2s; white-space: nowrap; }
.dph-deposit-btn:hover { opacity: .88; color: #fff; }

/* Search */
.dph-search-card { background: #fff; border-radius: 14px; border: 1px solid #F0F2F5; padding: 14px 20px; }
.dph-search-form { display: flex; gap: 10px; align-items: center; }
.dph-search-wrap { flex: 1; position: relative; display: flex; align-items: center; }
.dph-search-wrap i { position: absolute; left: 12px; color: #9CA3AF; font-size: 1.05rem; pointer-events: none; }
.dph-search-input { width: 100%; padding: 9px 12px 9px 36px; border: 1.5px solid #E5E7EB; border-radius: 10px; font-size: .85rem; color: #111827; background: #F9FAFB; outline: none; transition: border-color .2s; }
.dph-search-input:focus { border-color: #16A34A; background: #fff; }
.dph-search-btn { padding: 9px 20px; background: linear-gradient(135deg,#166534,#16A34A); color: #fff; border: none; border-radius: 10px; font-size: .82rem; font-weight: 600; cursor: pointer; white-space: nowrap; transition: opacity .2s; }
.dph-search-btn:hover { opacity: .88; }

/* List card */
.dph-list-card { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; overflow: hidden; }
.dph-list-head { display: grid; grid-template-columns: 2fr 1.4fr 1.2fr 1.3fr 1fr .6fr; gap: 0; padding: 11px 20px; background: #F9FAFB; border-bottom: 1px solid #F0F2F5; font-size: .7rem; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: .05em; }

/* Rows */
.dph-row { display: grid; grid-template-columns: 2fr 1.4fr 1.2fr 1.3fr 1fr .6fr; gap: 0; padding: 14px 20px; border-bottom: 1px solid #F9FAFB; align-items: center; transition: background .15s; }
.dph-row:last-child { border-bottom: none; }
.dph-row:hover { background: #FAFAFA; }

/* Cells */
.dph-cell { display: flex; flex-direction: column; justify-content: center; }
.dph-cell--gateway { flex-direction: row; align-items: center; gap: 12px; }
.dph-gw-icon { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg,#F0FDF4,#DCFCE7); border: 1px solid #BBF7D0; display: flex; align-items: center; justify-content: center; color: #16A34A; font-size: 1.1rem; flex-shrink: 0; }
.dph-gw-name { font-size: .85rem; font-weight: 700; color: #111827; margin: 0 0 2px; }
.dph-trx { font-size: .72rem; color: #9CA3AF; font-family: monospace; margin: 0; }
.dph-date-mobile { display: none; font-size: .7rem; color: #9CA3AF; margin: 4px 0 0; }
.dph-date-main { font-size: .8rem; color: #374151; margin: 0 0 2px; }
.dph-date-ago { font-size: .72rem; color: #9CA3AF; margin: 0; }
.dph-amount { font-size: .95rem; font-weight: 700; color: #111827; margin: 0 0 2px; }
.dph-charge { font-size: .72rem; color: #6B7280; margin: 0; }
.dph-charge-val { color: #DC2626; font-weight: 600; }
.dph-conv-rate { font-size: .75rem; color: #6B7280; margin: 0 0 2px; }
.dph-conv-final { font-size: .82rem; color: #111827; margin: 0; }
.dph-cell--status { align-items: flex-start; }
.dph-cell--action { align-items: center; }
.dph-detail-btn { width: 34px; height: 34px; border-radius: 8px; border: 1.5px solid #E5E7EB; background: #fff; color: #374151; font-size: .95rem; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: border-color .2s, color .2s, background .2s; }
.dph-detail-btn:hover { border-color: #16A34A; color: #16A34A; background: #F0FDF4; }
.dph-auto-badge { width: 34px; height: 34px; border-radius: 8px; background: #F0FDF4; border: 1.5px solid #BBF7D0; color: #16A34A; font-size: 1.05rem; display: flex; align-items: center; justify-content: center; cursor: default; }
.dph-col-hide { }

/* Empty state */
.dph-empty { padding: 52px 20px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.dph-empty-icon { font-size: 2.8rem; color: #D1D5DB; }
.dph-empty p { color: #6B7280; font-size: .875rem; margin: 0; }

/* Pagination */
.dph-pagination { display: flex; justify-content: center; }

/* Modal list */
.dph-modal-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0; }
.dph-modal-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #F0F2F5; font-size: .85rem; }
.dph-modal-item:last-child { border-bottom: none; }
.dph-modal-key { color: #6B7280; font-weight: 500; }
.dph-modal-val { color: #111827; font-weight: 600; text-align: right; max-width: 55%; word-break: break-all; }
.dph-modal-link { color: #16A34A; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 4px; }
.dph-modal-link:hover { color: #166534; }
.dph-feedback { margin-top: 12px; padding: 12px; background: #FFFBEB; border-radius: 10px; border: 1px solid #FDE68A; }
.dph-feedback strong { font-size: .78rem; color: #92400E; text-transform: uppercase; letter-spacing: .04em; display: block; margin-bottom: 4px; }
.dph-feedback p { font-size: .85rem; color: #78350F; margin: 0; }

/* Deposit history responsive */
@media (max-width: 991px) {
    .dph-list-head { grid-template-columns: 2fr 1.2fr 1fr .6fr; }
    .dph-row { grid-template-columns: 2fr 1.2fr 1fr .6fr; }
    .dph-col-hide { display: none; }
    .dph-date-mobile { display: block; }
}
@media (max-width: 575px) {
    .dph-list-head { display: none; }
    .dph-row { grid-template-columns: 1fr auto; grid-template-rows: auto auto; gap: 8px; padding: 14px 16px; }
    .dph-cell--gateway { grid-column: 1 / 2; grid-row: 1; }
    .dph-cell--amount  { grid-column: 2 / 3; grid-row: 1; align-items: flex-end; }
    .dph-cell--status  { grid-column: 1 / 2; grid-row: 2; flex-direction: row; }
    .dph-cell--action  { grid-column: 2 / 3; grid-row: 2; align-items: flex-end; }
    .dph-search-form { flex-direction: column; }
    .dph-search-btn { width: 100%; justify-content: center; }
}

/* ═══════════════════════════════════════════════
   DEPOSIT PAGE  (.dep-*)
   ═══════════════════════════════════════════════ */
.dep-page { max-width: 1060px; margin: 0 auto; padding: 20px 16px 48px; display: flex; flex-direction: column; gap: 20px; }

/* Top bar */
.dep-topbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.dep-topbar-left { display: flex; align-items: center; gap: 14px; }
.dep-topbar-icon { width: 46px; height: 46px; border-radius: 14px; background: linear-gradient(135deg,#166534,#16A34A); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.3rem; flex-shrink: 0; }
.dep-topbar-icon.man { background: linear-gradient(135deg,#1D4ED8,#3B82F6); }
.dep-topbar-title { font-size: 1rem; font-weight: 700; color: #111827; margin: 0; }
.dep-topbar-sub { font-size: .78rem; color: #6B7280; margin: 0; }
.dep-hist-btn { display: inline-flex; align-items: center; gap: 7px; padding: 8px 18px; border: 1.5px solid #16A34A; border-radius: 20px; color: #16A34A; font-size: .8rem; font-weight: 600; text-decoration: none; transition: background .2s; }
.dep-hist-btn:hover { background: #F0FDF4; color: #166534; }

/* Hero banner */
.dep-hero { position: relative; border-radius: 22px; overflow: hidden; padding: 36px 36px 40px; min-height: 200px; display: flex; align-items: center; }
.dep-hero-bg { position: absolute; inset: 0; background: linear-gradient(135deg, #064E3B 0%, #065F46 30%, #047857 60%, #059669 100%); z-index: 0; }
.dep-hero-bg::before { content: ''; position: absolute; inset: 0; background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat; }
.dep-hero-bg::after { content: ''; position: absolute; right: -60px; top: -60px; width: 280px; height: 280px; background: radial-gradient(circle, rgba(255,255,255,.08) 0%, transparent 70%); border-radius: 50%; }
.dep-hero-content { position: relative; z-index: 1; }
.dep-hero-badge { display: inline-flex; align-items: center; gap: 6px; background: rgba(255,255,255,.15); color: #fff; font-size: .72rem; font-weight: 700; padding: 5px 12px; border-radius: 20px; border: 1px solid rgba(255,255,255,.2); margin-bottom: 14px; letter-spacing: .03em; }
.dep-hero-heading { font-size: 1.75rem; font-weight: 800; color: #fff; margin: 0 0 10px; line-height: 1.2; }
.dep-hero-sub { font-size: .85rem; color: rgba(255,255,255,.75); margin: 0 0 18px; max-width: 480px; }
.dep-hero-pills { display: flex; gap: 10px; flex-wrap: wrap; }
.dep-hero-pill { display: inline-flex; align-items: center; gap: 5px; background: rgba(255,255,255,.12); color: rgba(255,255,255,.9); font-size: .75rem; font-weight: 600; padding: 5px 12px; border-radius: 20px; border: 1px solid rgba(255,255,255,.15); }

/* Cards */
.dep-card { background: #fff; border-radius: 20px; border: 1px solid #F0F2F5; overflow: hidden; }
.dep-card-head { display: flex; align-items: center; gap: 14px; padding: 18px 22px; border-bottom: 1px solid #F0F2F5; }
.dep-card-head-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
.dep-card-head-icon.gw  { background: linear-gradient(135deg,#EFF6FF,#DBEAFE); color: #1D4ED8; }
.dep-card-head-icon.amt { background: linear-gradient(135deg,#F0FDF4,#DCFCE7); color: #16A34A; }
.dep-card-title { font-size: .95rem; font-weight: 700; color: #111827; margin: 0; }
.dep-card-sub   { font-size: .75rem; color: #6B7280; margin: 0; }

/* Two-column layout */
.dep-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

/* Gateway list */
.dep-gw-list { padding: 14px 16px; display: flex; flex-direction: column; gap: 8px; max-height: 380px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #E5E7EB transparent; }
.dep-gw-list::-webkit-scrollbar { width: 4px; }
.dep-gw-list::-webkit-scrollbar-thumb { background: #E5E7EB; border-radius: 4px; }
.dep-gw-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; border: 1.5px solid #F0F2F5; border-radius: 14px; cursor: pointer; transition: all .2s; background: #FAFAFA; }
.dep-gw-item:hover { border-color: #BBF7D0; background: #F0FDF4; }
.dep-gw-item.selected, .dep-gw-item:has(input:checked) { border-color: #16A34A; background: linear-gradient(135deg,#F0FDF4,#DCFCE7); box-shadow: 0 2px 12px rgba(22,163,74,.12); }
.dep-gw-item-left { display: flex; align-items: center; gap: 10px; }
.dep-gw-radio-wrap { width: 20px; height: 20px; border-radius: 50%; border: 2px solid #D1D5DB; display: flex; align-items: center; justify-content: center; transition: all .2s; flex-shrink: 0; }
.dep-gw-item.selected .dep-gw-radio-wrap,
.dep-gw-item:has(input:checked) .dep-gw-radio-wrap { border-color: #16A34A; background: #16A34A; }
.dep-gw-radio::after { content: ''; display: block; width: 8px; height: 8px; border-radius: 50%; background: #fff; }
.dep-gw-name { font-size: .85rem; font-weight: 600; color: #111827; }
.dep-gw-logo { height: 32px; max-width: 70px; display: flex; align-items: center; }
.dep-gw-logo img { max-height: 32px; max-width: 70px; object-fit: contain; }
.dep-more-btn { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px; border: 1.5px dashed #D1D5DB; border-radius: 12px; background: none; color: #6B7280; font-size: .82rem; font-weight: 600; cursor: pointer; width: 100%; transition: border-color .2s, color .2s; }
.dep-more-btn:hover { border-color: #16A34A; color: #16A34A; }
.dep-crypto-msg { margin: 0 16px 14px; padding: 10px 14px; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 10px; font-size: .78rem; color: #92400E; display: flex; align-items: center; gap: 6px; }

/* Amount input */
.dep-amount-wrap { margin: 18px 22px 12px; display: flex; align-items: center; border: 2px solid #E5E7EB; border-radius: 14px; overflow: hidden; background: #F9FAFB; transition: border-color .2s; }
.dep-amount-wrap:focus-within { border-color: #16A34A; background: #fff; }
.dep-cur-sym { padding: 0 14px; font-size: 1.1rem; font-weight: 700; color: #16A34A; background: #F0FDF4; height: 56px; display: flex; align-items: center; border-right: 2px solid #E5E7EB; flex-shrink: 0; }
.dep-amount-input { flex: 1; border: none; background: transparent; padding: 0 14px; font-size: 1.4rem; font-weight: 700; color: #111827; outline: none; height: 56px; }
.dep-amount-input::placeholder { color: #D1D5DB; font-weight: 400; }
.dep-cur-text { padding: 0 14px; font-size: .78rem; font-weight: 600; color: #9CA3AF; white-space: nowrap; }

/* Quick amounts */
.dep-quick-amounts { display: flex; flex-wrap: wrap; gap: 8px; padding: 0 22px 16px; }
.dep-quick-btn { padding: 6px 14px; border: 1.5px solid #E5E7EB; border-radius: 20px; background: #fff; color: #374151; font-size: .78rem; font-weight: 600; cursor: pointer; transition: all .2s; }
.dep-quick-btn:hover { border-color: #16A34A; color: #16A34A; background: #F0FDF4; }
.dep-quick-btn.active { border-color: #16A34A; background: linear-gradient(135deg,#166534,#16A34A); color: #fff; }

/* Summary */
.dep-summary { margin: 0 22px 18px; background: #F9FAFB; border-radius: 14px; padding: 6px 0; border: 1px solid #F0F2F5; }
.dep-summary-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; font-size: .85rem; }
.dep-summary-label { color: #6B7280; display: flex; align-items: center; gap: 6px; }
.dep-summary-label i { color: #9CA3AF; }
.dep-summary-val { color: #111827; font-weight: 600; }
.dep-charge { color: #DC2626; }
.dep-summary-divider { height: 1px; background: #E5E7EB; margin: 4px 16px; }
.dep-summary-total { background: linear-gradient(135deg,#F0FDF4,#DCFCE7); border-radius: 0 0 12px 12px; }
.dep-summary-total .dep-summary-label { color: #166534; font-weight: 700; font-size: .9rem; }
.dep-total-val { font-size: 1.1rem; font-weight: 800; color: #166534; display: flex; align-items: baseline; gap: 4px; }
.dep-total-val small { font-size: .75rem; font-weight: 600; color: #16A34A; }
.dep-conv-val { color: #1D4ED8; }
.dep-fee-info { cursor: help; color: #9CA3AF; font-size: .9rem; }

/* Submit btn */
.dep-submit-btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: calc(100% - 44px); margin: 0 22px 12px; padding: 14px; background: linear-gradient(135deg,#064E3B 0%,#16A34A 100%); color: #fff; border: none; border-radius: 14px; font-size: .95rem; font-weight: 700; cursor: pointer; transition: opacity .2s, transform .1s; letter-spacing: .02em; }
.dep-submit-btn:hover:not([disabled]) { opacity: .9; transform: translateY(-1px); }
.dep-submit-btn[disabled] { background: linear-gradient(135deg,#9CA3AF,#D1D5DB); cursor: not-allowed; }
.dep-secure-note { font-size: .72rem; color: #9CA3AF; text-align: center; display: flex; align-items: center; justify-content: center; gap: 5px; padding: 0 22px 18px; margin: 0; }

/* Trust strip */
.dep-trust-strip { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
.dep-trust-item { background: #fff; border-radius: 16px; border: 1px solid #F0F2F5; padding: 18px 16px; display: flex; align-items: center; gap: 12px; transition: transform .2s, box-shadow .2s; }
.dep-trust-item:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,.07); }
.dep-trust-icon { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0; }
.dep-trust-icon.t1 { background: linear-gradient(135deg,#EFF6FF,#DBEAFE); color: #1D4ED8; }
.dep-trust-icon.t2 { background: linear-gradient(135deg,#F0FDF4,#DCFCE7); color: #16A34A; }
.dep-trust-icon.t3 { background: linear-gradient(135deg,#FFF7ED,#FFEDD5); color: #EA580C; }
.dep-trust-icon.t4 { background: linear-gradient(135deg,#FDF4FF,#F3E8FF); color: #9333EA; }
.dep-trust-label { font-size: .82rem; font-weight: 700; color: #111827; margin: 0 0 2px; }
.dep-trust-sub { font-size: .72rem; color: #9CA3AF; margin: 0; }

/* ── Manual Confirm (.man-*) ── */
.man-banner { background: linear-gradient(135deg,#1E3A5F 0%,#1D4ED8 50%,#3B82F6 100%); border-radius: 20px; padding: 28px 32px; display: flex; align-items: center; justify-content: space-between; position: relative; overflow: hidden; }
.man-banner::before { content: ''; position: absolute; right: -40px; top: -40px; width: 200px; height: 200px; background: rgba(255,255,255,.06); border-radius: 50%; }
.man-banner-label { font-size: .75rem; font-weight: 600; color: rgba(255,255,255,.65); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 8px; }
.man-banner-amount { font-size: 2.2rem; font-weight: 800; color: #fff; line-height: 1; margin-bottom: 8px; }
.man-banner-sub { font-size: .82rem; color: rgba(255,255,255,.75); }
.man-banner-sub strong { color: #fff; }
.man-banner-icon { font-size: 3.5rem; color: rgba(255,255,255,.2); flex-shrink: 0; }

/* Steps */
.man-steps { display: flex; align-items: center; justify-content: center; gap: 0; padding: 4px 0; }
.man-step { display: flex; flex-direction: column; align-items: center; gap: 5px; }
.man-step-num { width: 36px; height: 36px; border-radius: 50%; border: 2px solid #E5E7EB; background: #fff; display: flex; align-items: center; justify-content: center; font-size: .82rem; font-weight: 700; color: #9CA3AF; }
.man-step-num.done { background: linear-gradient(135deg,#166534,#16A34A); border-color: #16A34A; color: #fff; font-size: 1rem; }
.man-step-num.current { background: linear-gradient(135deg,#1D4ED8,#3B82F6); border-color: #3B82F6; color: #fff; }
.man-step-label { font-size: .7rem; font-weight: 600; color: #9CA3AF; text-transform: uppercase; letter-spacing: .04em; }
.man-step-line { flex: 1; max-width: 80px; height: 2px; background: #E5E7EB; margin-bottom: 20px; }
.man-step-line.active { background: linear-gradient(90deg,#16A34A,#3B82F6); }

.man-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.man-instr-body { padding: 18px 22px; font-size: .875rem; color: #374151; line-height: 1.7; }
.man-instr-body img { max-width: 100%; border-radius: 8px; }
.man-form { }
.man-form-body { padding: 18px 22px; display: flex; flex-direction: column; gap: 14px; }
.man-form-body .form-control, .man-form-body .form--control { border: 1.5px solid #E5E7EB; border-radius: 10px; padding: 10px 14px; font-size: .875rem; color: #111827; background: #F9FAFB; outline: none; transition: border-color .2s; }
.man-form-body .form-control:focus, .man-form-body .form--control:focus { border-color: #16A34A; background: #fff; box-shadow: none; }
.man-form-body label { font-size: .78rem; font-weight: 600; color: #374151; margin-bottom: 4px; display: block; }
.man-form-footer { padding: 14px 22px 20px; border-top: 1px solid #F0F2F5; display: flex; flex-direction: column; gap: 10px; }
.man-secure-note { font-size: .72rem; color: #9CA3AF; display: flex; align-items: center; gap: 5px; justify-content: center; }
.man-submit-btn { width: 100%; border-radius: 14px; }

/* Deposit & manual responsive */
@media (max-width: 991px) {
    .dep-grid, .man-layout { grid-template-columns: 1fr; }
    .dep-trust-strip { grid-template-columns: repeat(2, 1fr); }
    .dep-hero-heading { font-size: 1.4rem; }
}
@media (max-width: 575px) {
    .dep-hero { padding: 24px 20px 28px; }
    .dep-trust-strip { grid-template-columns: 1fr 1fr; gap: 10px; }
    .dep-trust-item { padding: 14px 12px; }
    .dep-quick-amounts { gap: 6px; }
    .dep-quick-btn { font-size: .72rem; padding: 5px 10px; }
    .man-banner { flex-direction: column; align-items: flex-start; gap: 12px; }
    .man-banner-icon { display: none; }
    .man-steps { gap: 0; overflow-x: auto; justify-content: flex-start; padding: 4px 16px; }
    .dep-submit-btn { width: calc(100% - 32px); margin: 0 16px 12px; }
    .dep-amount-wrap { margin: 14px 16px 10px; }
    .dep-summary { margin: 0 16px 14px; }
    .dep-quick-amounts { padding: 0 16px 12px; }
    .dep-secure-note { padding: 0 16px 16px; }
}

/* ═══════════════════════════════════════════════
   GATEWAY CONFIRM PAGES  (.gco-*)
   ═══════════════════════════════════════════════ */
.gco-page { max-width: 1000px; margin: 0 auto; padding: 20px 16px 48px; }
.gco-page--centered { max-width: 560px; }

/* Two-col layout (card + form gateways) */
.gco-layout { display: grid; grid-template-columns: 340px 1fr; gap: 20px; align-items: start; }
.gco-summary-col, .gco-action-col { display: flex; flex-direction: column; gap: 14px; }

/* Summary card */
.gco-summary-card { background: linear-gradient(150deg,#064E3B 0%,#065F46 40%,#059669 100%); border-radius: 22px; padding: 28px 24px; position: relative; overflow: hidden; }
.gco-summary-card::before { content: ''; position: absolute; right: -50px; top: -50px; width: 220px; height: 220px; background: rgba(255,255,255,.06); border-radius: 50%; }
.gco-summary-card::after { content: ''; position: absolute; left: -30px; bottom: -30px; width: 140px; height: 140px; background: rgba(255,255,255,.04); border-radius: 50%; }
.gco-summary-top { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; position: relative; z-index: 1; }
.gco-gw-badge { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.gco-gw-badge.stripe  { background: linear-gradient(135deg,#6772E5,#7C83FF); color: #fff; }
.gco-gw-badge.nmi     { background: linear-gradient(135deg,#1D4ED8,#3B82F6); color: #fff; }
.gco-gw-badge.rzp     { background: linear-gradient(135deg,#072654,#3395FF); color: #fff; }
.gco-gw-badge.flw     { background: linear-gradient(135deg,#F5A623,#F76B1C); color: #fff; }
.gco-gw-badge.pstk    { background: linear-gradient(135deg,#00C3F7,#0BA4E0); color: #fff; }
.gco-gw-badge.crypto  { background: linear-gradient(135deg,#F59E0B,#D97706); color: #fff; }
.gco-gw-name { font-size: .95rem; font-weight: 700; color: #fff; margin: 0; }
.gco-gw-sub  { font-size: .75rem; color: rgba(255,255,255,.65); margin: 0; }
.gco-amount-display { position: relative; z-index: 1; margin-bottom: 16px; }
.gco-amount-label { font-size: .7rem; font-weight: 700; color: rgba(255,255,255,.6); text-transform: uppercase; letter-spacing: .08em; margin: 0 0 4px; }
.gco-amount-val { font-size: 2rem; font-weight: 800; color: #fff; margin: 0; line-height: 1.1; }
.gco-amount-val span { font-size: 1rem; font-weight: 600; opacity: .75; margin-left: 4px; }
.gco-receive-row { position: relative; z-index: 1; display: flex; align-items: center; gap: 10px; background: rgba(255,255,255,.1); border-radius: 12px; padding: 10px 14px; margin-bottom: 24px; }
.gco-receive-row > i { font-size: 1.2rem; color: rgba(255,255,255,.5); }
.gco-receive-label { font-size: .7rem; color: rgba(255,255,255,.6); margin: 0 0 2px; }
.gco-receive-val { font-size: 1rem; font-weight: 700; color: #fff; margin: 0; }

/* Steps inside summary */
.gco-steps { position: relative; z-index: 1; display: flex; align-items: center; gap: 0; }
.gco-step { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.gco-step-dot { width: 30px; height: 30px; border-radius: 50%; border: 2px solid rgba(255,255,255,.3); background: rgba(255,255,255,.1); display: flex; align-items: center; justify-content: center; font-size: .78rem; font-weight: 700; color: rgba(255,255,255,.5); }
.gco-step-dot.done    { background: rgba(255,255,255,.95); border-color: #fff; color: #16A34A; }
.gco-step-dot.current { background: #fff; border-color: #fff; color: #1D4ED8; font-weight: 800; }
.gco-step span { font-size: .62rem; color: rgba(255,255,255,.55); font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
.gco-step-line { flex: 1; height: 2px; background: rgba(255,255,255,.2); margin-bottom: 18px; min-width: 20px; }

/* Secure badges card */
.gco-secure-card { background: #fff; border-radius: 14px; border: 1px solid #F0F2F5; padding: 14px 18px; display: flex; flex-direction: column; gap: 10px; }
.gco-secure-item { display: flex; align-items: center; gap: 8px; font-size: .78rem; font-weight: 600; color: #374151; }
.gco-secure-item i { color: #16A34A; font-size: 1rem; }

/* Action card */
.gco-action-card { background: #fff; border-radius: 20px; border: 1px solid #F0F2F5; overflow: hidden; }
.gco-action-head { display: flex; align-items: center; gap: 14px; padding: 20px 24px; border-bottom: 1px solid #F0F2F5; background: #FAFAFA; }
.gco-action-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
.gco-action-icon.stripe { background: linear-gradient(135deg,#EEF0FF,#D8DAFF); color: #6772E5; }
.gco-action-icon.nmi    { background: linear-gradient(135deg,#EFF6FF,#DBEAFE); color: #1D4ED8; }
.gco-action-title { font-size: .95rem; font-weight: 700; color: #111827; margin: 0; }
.gco-action-sub { font-size: .75rem; color: #6B7280; margin: 0; }
.gco-card-preview { margin: 20px 24px 0; }

/* Form fields */
.gco-form { padding: 20px 24px 24px; }
.gco-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px; }
.gco-field-group { display: flex; flex-direction: column; gap: 5px; }
.gco-field-group.full { grid-column: 1 / -1; }
.gco-field-group.half { grid-column: span 1; }
.gco-label { font-size: .75rem; font-weight: 600; color: #374151; }
.gco-input-wrap { position: relative; display: flex; align-items: center; }
.gco-input-wrap > i { position: absolute; left: 12px; color: #9CA3AF; font-size: 1rem; pointer-events: none; }
.gco-input { width: 100%; padding: 11px 12px 11px 36px; border: 1.5px solid #E5E7EB; border-radius: 10px; font-size: .875rem; color: #111827; background: #F9FAFB; outline: none; transition: border-color .2s, background .2s; }
.gco-input:focus { border-color: #6772E5; background: #fff; }

/* Pay button */
.gco-pay-btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px; background: linear-gradient(135deg,#064E3B 0%,#059669 50%,#16A34A 100%); color: #fff; border: none; border-radius: 12px; font-size: .95rem; font-weight: 700; cursor: pointer; transition: opacity .2s, transform .1s; letter-spacing: .02em; }
.gco-pay-btn:hover { opacity: .9; transform: translateY(-1px); color: #fff; }
.gco-pay-btn:active { transform: translateY(0); }

/* ── Single-card layout (Razorpay, Flutterwave, Paystack, StripeJs, Crypto) ── */
.gco-single-card { background: #fff; border-radius: 24px; border: 1px solid #F0F2F5; overflow: hidden; box-shadow: 0 8px 40px rgba(0,0,0,.07); }
.gco-single-head { display: flex; align-items: center; gap: 14px; padding: 24px; background: linear-gradient(135deg,#FAFAFA,#F3F4F6); border-bottom: 1px solid #F0F2F5; }
.gco-single-amounts { padding: 24px; }
.gco-single-amt-row { display: flex; align-items: center; gap: 0; }
.gco-single-amt-box { flex: 1; text-align: center; padding: 20px 12px; border-radius: 16px; }
.gco-single-amt-box.pay { background: linear-gradient(135deg,#FEF3C7,#FDE68A); border: 1px solid #FCD34D; }
.gco-single-amt-box.get { background: linear-gradient(135deg,#F0FDF4,#DCFCE7); border: 1px solid #86EFAC; }
.gco-single-amt-label { font-size: .68rem; font-weight: 700; color: #6B7280; text-transform: uppercase; letter-spacing: .06em; margin: 0 0 4px; }
.gco-single-amt-val { font-size: 1.6rem; font-weight: 800; color: #111827; margin: 0; line-height: 1; }
.gco-single-amt-box.pay .gco-single-amt-val { color: #92400E; }
.gco-single-amt-box.get .gco-single-amt-val { color: #166534; }
.gco-single-amt-cur { font-size: .75rem; font-weight: 600; color: #9CA3AF; margin: 4px 0 0; }
.gco-single-arrow { width: 48px; text-align: center; flex-shrink: 0; }
.gco-single-arrow i { font-size: 1.6rem; color: #D1D5DB; }
.gco-single-steps { display: flex; align-items: center; padding: 0 24px 20px; }
.gco-single-steps .gco-step-dot { background: #F3F4F6; border-color: #E5E7EB; color: #9CA3AF; }
.gco-single-steps .gco-step-dot.done { background: #16A34A; border-color: #16A34A; color: #fff; }
.gco-single-steps .gco-step-dot.current { background: #1D4ED8; border-color: #1D4ED8; color: #fff; }
.gco-single-steps .gco-step span { color: #9CA3AF; }
.gco-single-steps .gco-step-line { background: #E5E7EB; }
.gco-single-form { padding: 0 24px 8px; }
.gco-single-secure { display: flex; align-items: center; justify-content: center; gap: 20px; padding: 16px 24px; border-top: 1px solid #F0F2F5; background: #FAFAFA; flex-wrap: wrap; }
.gco-single-secure span { font-size: .72rem; font-weight: 600; color: #9CA3AF; display: flex; align-items: center; gap: 5px; }
.gco-single-secure i { color: #16A34A; }

/* Crypto-specific */
.gco-crypto-card .gco-single-head { background: linear-gradient(135deg,#FEF3C7,#FFFBEB); }
.gco-qr-wrap { display: flex; flex-direction: column; align-items: center; gap: 10px; padding: 20px 24px 0; }
.gco-qr-frame { padding: 12px; background: #fff; border-radius: 16px; border: 2px solid #F0F2F5; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
.gco-qr-frame img { width: 180px; height: 180px; display: block; border-radius: 8px; }
.gco-qr-hint { font-size: .75rem; font-weight: 600; color: #9CA3AF; display: flex; align-items: center; gap: 5px; margin: 0; }
.gco-crypto-amount-box { text-align: center; padding: 20px 24px 16px; }
.gco-crypto-amount-label { font-size: .7rem; font-weight: 700; color: #9CA3AF; text-transform: uppercase; letter-spacing: .08em; margin: 0 0 6px; }
.gco-crypto-amount-val { font-size: 2rem; font-weight: 800; color: #92400E; margin: 0; line-height: 1.1; }
.gco-crypto-amount-cur { font-size: .85rem; font-weight: 600; color: #D97706; margin: 4px 0 0; }
.gco-wallet-box { margin: 0 20px 20px; background: #FFFBEB; border: 1px solid #FDE68A; border-radius: 14px; padding: 16px; }
.gco-wallet-label { font-size: .72rem; font-weight: 700; color: #92400E; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 8px; display: flex; align-items: center; gap: 5px; }
.gco-wallet-addr-wrap { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #FCD34D; border-radius: 10px; padding: 10px 12px; margin-bottom: 10px; }
.gco-wallet-addr { font-family: monospace; font-size: .78rem; color: #111827; flex: 1; word-break: break-all; margin: 0; }
.gco-copy-addr { background: none; border: none; cursor: pointer; color: #9CA3AF; font-size: 1.1rem; padding: 2px 4px; transition: color .2s; flex-shrink: 0; }
.gco-copy-addr:hover { color: #16A34A; }
.gco-wallet-warn { font-size: .72rem; color: #92400E; margin: 0; display: flex; align-items: flex-start; gap: 5px; line-height: 1.4; }
.gco-wallet-warn i { flex-shrink: 0; margin-top: 1px; }

/* Responsive */
@media (max-width: 767px) {
    .gco-layout { grid-template-columns: 1fr; }
    .gco-summary-card { border-radius: 18px; padding: 22px 18px; }
    .gco-amount-val { font-size: 1.6rem; }
    .gco-single-amt-val { font-size: 1.25rem; }
    .gco-single-arrow i { font-size: 1.2rem; }
}
@media (max-width: 480px) {
    .gco-fields { grid-template-columns: 1fr; }
    .gco-field-group.half { grid-column: 1; }
    .gco-form { padding: 16px; }
    .gco-single-amounts { padding: 16px; }
    .gco-single-secure { gap: 12px; }
    .gco-wallet-box { margin: 0 12px 16px; }
    .gco-qr-frame img { width: 150px; height: 150px; }
}

</style>
@endpush
 
@push('script')
    <script>
        (function($) {
            "use strict";
            window.addEventListener('scroll', function(){
              var header = document.querySelector('header');
              header.classList.toggle('sticky', window.scrollY > 0);
            });   
        })(jQuery);
    </script>
@endpush

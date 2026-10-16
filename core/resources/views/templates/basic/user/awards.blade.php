@extends($activeTemplate . 'layouts.master')

@push('style')
<style>
/* ═══════════════════════════════════════════════════════
   USER AWARDS PAGE — Premium Trophy Room
   ═══════════════════════════════════════════════════════ */

.ua-page { padding: 28px 28px 60px; }

/* Hero */
.ua-hero {
    background: linear-gradient(135deg, #0D5C2E 0%, #16A34A 60%, #22c55e 100%);
    border-radius: 20px;
    padding: 32px 36px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    color: #fff;
}
.ua-hero::before {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 240px; height: 240px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    pointer-events: none;
}
.ua-hero::after {
    content: '';
    position: absolute;
    bottom: -60px; right: 100px;
    width: 300px; height: 300px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    pointer-events: none;
}
.ua-hero-icon {
    position: absolute;
    right: 36px; top: 50%;
    transform: translateY(-50%);
    font-size: 5rem;
    opacity: .09;
    pointer-events: none;
}
.ua-hero-eyebrow {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .12em;
    opacity: .8;
    margin-bottom: 6px;
}
.ua-hero-title {
    font-size: 1.65rem;
    font-weight: 900;
    letter-spacing: -.025em;
    margin-bottom: 4px;
}
.ua-hero-sub { font-size: .88rem; opacity: .8; margin-bottom: 24px; }
.ua-hero-stats {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.ua-stat-pill {
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 50px;
    padding: 7px 18px;
    display: flex; align-items: center; gap: 8px;
    font-size: .82rem;
    backdrop-filter: blur(4px);
}
.ua-stat-pill b { font-size: .98rem; font-weight: 800; }

/* Award card grid */
.ua-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 20px;
}

/* Award card */
.ua-card {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid #f0f0f0;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    position: relative;
    transition: transform .3s cubic-bezier(.34,1.56,.64,1), box-shadow .3s ease;
}
.ua-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 40px rgba(13,92,46,.14);
}

/* Earned state */
.ua-card.earned {
    border-color: #bbf7d0;
    box-shadow: 0 2px 12px rgba(22,163,74,.12);
}
.ua-card.earned:hover {
    box-shadow: 0 16px 40px rgba(22,163,74,.22);
}

/* Paid state gets gold accent */
.ua-card.paid {
    border-color: #fde68a;
}
.ua-card.paid:hover {
    box-shadow: 0 16px 40px rgba(217,119,6,.18);
}

/* Locked overlay tint */
.ua-card.locked .ua-card-head { filter: grayscale(.6); opacity: .8; }
.ua-card.locked { border-color: #f0f0f0; }

/* Card head */
.ua-card-head {
    background: linear-gradient(160deg, #f0fdf4 0%, #dcfce7 100%);
    padding: 24px 20px 18px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.ua-card.paid .ua-card-head {
    background: linear-gradient(160deg, #fffbeb 0%, #fef3c7 100%);
}
.ua-card.locked .ua-card-head {
    background: linear-gradient(160deg, #f9fafb 0%, #f3f4f6 100%);
}
.ua-card-head::before {
    content: '';
    position: absolute; top: -35px; left: 50%;
    transform: translateX(-50%);
    width: 130px; height: 130px;
    background: radial-gradient(circle, rgba(22,163,74,.15) 0%, transparent 70%);
    pointer-events: none;
}
.ua-card.paid .ua-card-head::before {
    background: radial-gradient(circle, rgba(217,119,6,.15) 0%, transparent 70%);
}
.ua-card.locked .ua-card-head::before {
    background: radial-gradient(circle, rgba(156,163,175,.12) 0%, transparent 70%);
}

/* Status corner */
.ua-status-badge {
    position: absolute;
    top: 11px; right: 11px;
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .65rem; font-weight: 800;
    letter-spacing: .05em;
    text-transform: uppercase;
}
.ua-status-earned {
    background: #dcfce7; color: #15803d;
    animation: ua-earned-pulse 3s ease-out infinite;
}
@keyframes ua-earned-pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(22,163,74,.0); }
    50%       { box-shadow: 0 0 0 5px rgba(22,163,74,.15); }
}
.ua-status-paid    { background: linear-gradient(135deg,#fef3c7,#fde68a); color: #92400e; }
.ua-status-locked  { background: #f3f4f6; color: #9ca3af; }

/* Sort order badge */
.ua-order-badge {
    position: absolute;
    top: 11px; left: 11px;
    background: rgba(13,92,46,.12);
    color: #0D5C2E;
    font-size: .64rem; font-weight: 800;
    padding: 2px 9px; border-radius: 20px;
    text-transform: uppercase; letter-spacing: .06em;
}
.ua-card.locked .ua-order-badge {
    background: rgba(156,163,175,.15);
    color: #9ca3af;
}

/* Avatar ring */
.ua-avatar-ring {
    display: inline-flex; align-items: center; justify-content: center;
    width: 90px; height: 90px;
    border-radius: 50%;
    background: linear-gradient(135deg, #16a34a, #0D5C2E);
    padding: 3px;
    margin-bottom: 12px;
    box-shadow: 0 4px 18px rgba(13,92,46,.28);
}
.ua-card.paid .ua-avatar-ring {
    background: linear-gradient(135deg, #d97706, #92400e);
    box-shadow: 0 4px 18px rgba(217,119,6,.3);
}
.ua-card.locked .ua-avatar-ring {
    background: linear-gradient(135deg, #9ca3af, #6b7280);
    box-shadow: 0 4px 12px rgba(107,114,128,.2);
}
.ua-avatar-ring img,
.ua-avatar-inner {
    width: 84px; height: 84px;
    border-radius: 50%;
    object-fit: cover;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    display: flex; align-items: center; justify-content: center;
    font-size: 2.2rem; color: #16a34a;
}
.ua-card.locked .ua-avatar-inner { background: #f9fafb; color: #9ca3af; }
.ua-card.paid   .ua-avatar-inner { background: linear-gradient(135deg, #fffbeb, #fef3c7); color: #d97706; }

.ua-card-name {
    font-size: 1rem; font-weight: 800; color: #0D5C2E;
    letter-spacing: -.01em; margin-bottom: 4px; line-height: 1.2;
}
.ua-card.locked .ua-card-name { color: #6b7280; }
.ua-card.paid   .ua-card-name { color: #92400e; }
.ua-card-desc {
    font-size: .75rem; color: #6b7280; line-height: 1.5; min-height: 34px;
}

/* PV block */
.ua-pv-block {
    padding: 14px 16px;
    border-top: 1px solid #f0fdf4;
    background: #fafffe;
}
.ua-card.locked .ua-pv-block { background: #f9fafb; border-top-color: #f3f4f6; }
.ua-card.paid   .ua-pv-block { background: #fffdf5; border-top-color: #fef3c7; }
.ua-pv-row {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 8px;
}
.ua-pv-row:last-child { margin-bottom: 0; }
.ua-pv-label {
    display: flex; align-items: center; gap: 6px;
    font-size: .71rem; font-weight: 700; color: #6b7280;
    text-transform: uppercase; letter-spacing: .06em;
}
.ua-pv-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.dot-total { background: #6366f1; }
.dot-left  { background: #16a34a; }
.dot-right { background: #e11d48; }
.ua-pv-val { font-size: .84rem; font-weight: 800; color: #1a1f2e; }

/* PV progress bars */
.ua-pv-progress-track {
    height: 6px; border-radius: 999px; background: #eef2f0;
    overflow: hidden; margin-bottom: 10px;
}
.ua-pv-row + .ua-pv-progress-track:last-child { margin-bottom: 0; }
.ua-pv-progress-fill {
    height: 100%; border-radius: 999px;
    transition: width .6s ease;
}
.fill-total { background: linear-gradient(90deg, #818cf8, #6366f1); }
.fill-left  { background: linear-gradient(90deg, #4ade80, #16a34a); }
.fill-right { background: linear-gradient(90deg, #fb7185, #e11d48); }
.ua-card.locked .ua-pv-progress-track { background: #eef0f2; }

/* Meta strip */
.ua-meta-strip {
    padding: 10px 14px;
    display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
    border-top: 1px solid #f3f4f6;
    min-height: 44px;
}
.ua-chip {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 11px; border-radius: 20px;
    font-size: .71rem; font-weight: 700;
}
.chip-reward  { background: linear-gradient(135deg,#fef3c7,#fde68a); color: #92400e; box-shadow: 0 1px 4px rgba(251,191,36,.18); }
.chip-prereq  { background: #f3e8ff; color: #7c3aed; }
.chip-earned  { background: #dcfce7; color: #15803d; margin-left: auto; }
.chip-pending { background: #fef3c7; color: #b45309; margin-left: auto; }
.chip-locked  { background: #f3f4f6; color: #9ca3af; margin-left: auto; }

/* Earned date line */
.ua-earned-date {
    padding: 10px 16px;
    border-top: 1px solid #f3f4f6;
    font-size: .74rem;
    color: #6b7280;
    display: flex; align-items: center; gap: 6px;
}
.ua-earned-date.paid-date { color: #92400e; background: linear-gradient(135deg,#fffbeb,#fef9ec); }

/* Empty state */
.ua-empty {
    text-align: center;
    padding: 80px 24px;
    background: #fff;
    border-radius: 20px;
    border: 2px dashed #e5e7eb;
}
.ua-empty-icon {
    width: 84px; height: 84px; border-radius: 50%;
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 18px;
    font-size: 2.4rem; color: #16a34a;
}

@media (max-width: 767px) {
    .ua-page { padding: 16px 14px 40px; }
    .ua-hero { padding: 22px 20px; }
    .ua-hero-title { font-size: 1.3rem; }
}
@media (max-width: 575px) {
    .ua-grid { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<div class="ua-page">

    {{-- Hero --}}
    <div class="ua-hero">
        <i class="las la-trophy ua-hero-icon"></i>
        <div class="ua-hero-eyebrow">Achievement Centre</div>
        <div class="ua-hero-title">My Awards</div>
        <div class="ua-hero-sub">Track your achievements and unlock new milestones</div>
        @php
            $earnedCount = $earnedMap->count();
            $paidCount   = $earnedMap->where('status', 1)->count();
            $totalReward = $earnedMap->where('status', 1)->sum(fn($ua) => $ua->paid_amount ?? 0);
        @endphp
        <div class="ua-hero-stats">
            <div class="ua-stat-pill">
                <i class="las la-trophy"></i>
                <b>{{ $allAwards->count() }}</b>
                <span>Total Awards</span>
            </div>
            <div class="ua-stat-pill">
                <i class="las la-user-check"></i>
                <b>{{ $earnedCount }}</b>
                <span>Earned</span>
            </div>
            <div class="ua-stat-pill">
                <i class="las la-coins"></i>
                <b>{{ $paidCount }}</b>
                <span>Paid Out</span>
            </div>
        </div>
    </div>

    @if($allAwards->isEmpty())
        <div class="ua-empty">
            <div class="ua-empty-icon"><i class="las la-trophy"></i></div>
            <h5 style="font-size:1.1rem;font-weight:800;color:#374151;margin-bottom:8px;">No Awards Available</h5>
            <p style="font-size:.86rem;color:#9ca3af;max-width:260px;margin:0 auto;">
                Awards have not been configured yet. Keep building your network!
            </p>
        </div>
    @else
    <div class="ua-grid">
        @foreach($allAwards as $award)
        @php
            $earned   = $earnedMap->get($award->id);
            $isPaid   = $earned && $earned->status == 1;
            $isEarned = $earned && !$isPaid;
            $isLocked = !$earned;

            $cardClass = $isPaid ? 'paid' : ($isEarned ? 'earned' : 'locked');

            // Progress is only meaningful while the award is still locked — pairing
            // PV depletes as it's consumed by matching bonus payouts, so an already
            // earned/paid award always shows as complete rather than re-computing
            // against (possibly since-depleted) current PV.
            if ($isLocked) {
                $totalPct = $award->required_total_pv > 0 ? min(100, round($currentTotalPv / $award->required_total_pv * 100)) : 100;
                $leftPct  = $award->required_left_pv  > 0 ? min(100, round($currentLeftPv  / $award->required_left_pv  * 100)) : 100;
                $rightPct = $award->required_right_pv > 0 ? min(100, round($currentRightPv / $award->required_right_pv * 100)) : 100;
            } else {
                $totalPct = $leftPct = $rightPct = 100;
            }
        @endphp
        <div class="ua-card {{ $cardClass }}">
            <div class="ua-card-head">
                <span class="ua-order-badge"># {{ $award->sort_order }}</span>

                @if($isPaid)
                    <span class="ua-status-badge ua-status-paid">
                        <i class="las la-check-circle"></i> Paid
                    </span>
                @elseif($isEarned)
                    <span class="ua-status-badge ua-status-earned">
                        <i class="las la-star"></i> Earned
                    </span>
                @else
                    <span class="ua-status-badge ua-status-locked">
                        <i class="las la-lock"></i> Locked
                    </span>
                @endif

                <div class="ua-avatar-ring">
                    @if($award->image_url)
                        <img src="{{ $award->image_url }}" alt="{{ $award->name }}">
                    @else
                        <div class="ua-avatar-inner">
                            @if($isLocked)
                                <i class="las la-lock"></i>
                            @else
                                <i class="las la-trophy"></i>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="ua-card-name">{{ $award->name }}</div>
                <div class="ua-card-desc">{{ Str::limit($award->description, 72) ?: 'No description provided.' }}</div>
            </div>

            {{-- PV Requirements --}}
            <div class="ua-pv-block">
                <div class="ua-pv-row">
                    <span class="ua-pv-label"><span class="ua-pv-dot dot-total"></span> Total PV</span>
                    <span class="ua-pv-val">
                        @if($isLocked)
                            {{ number_format($currentTotalPv, 0) }} / {{ number_format($award->required_total_pv, 0) }}
                        @else
                            {{ number_format($award->required_total_pv, 0) }}
                        @endif
                    </span>
                </div>
                <div class="ua-pv-progress-track">
                    <div class="ua-pv-progress-fill fill-total" style="width:{{ $totalPct }}%"></div>
                </div>

                <div class="ua-pv-row">
                    <span class="ua-pv-label"><span class="ua-pv-dot dot-left"></span> Left Leg</span>
                    <span class="ua-pv-val">
                        @if($isLocked)
                            {{ number_format($currentLeftPv, 0) }} / {{ number_format($award->required_left_pv, 0) }}
                        @else
                            {{ number_format($award->required_left_pv, 0) }}
                        @endif
                    </span>
                </div>
                <div class="ua-pv-progress-track">
                    <div class="ua-pv-progress-fill fill-left" style="width:{{ $leftPct }}%"></div>
                </div>

                <div class="ua-pv-row">
                    <span class="ua-pv-label"><span class="ua-pv-dot dot-right"></span> Right Leg</span>
                    <span class="ua-pv-val">
                        @if($isLocked)
                            {{ number_format($currentRightPv, 0) }} / {{ number_format($award->required_right_pv, 0) }}
                        @else
                            {{ number_format($award->required_right_pv, 0) }}
                        @endif
                    </span>
                </div>
                <div class="ua-pv-progress-track">
                    <div class="ua-pv-progress-fill fill-right" style="width:{{ $rightPct }}%"></div>
                </div>
            </div>

            {{-- Meta --}}
            <div class="ua-meta-strip">
                <span class="ua-chip chip-reward">
                    <i class="las la-coins"></i> {{ showAmount($award->payment_amount) }}
                </span>
                @if($award->prerequisite)
                    <span class="ua-chip chip-prereq" title="Requires: {{ $award->prerequisite->name }}">
                        <i class="las la-link"></i> {{ Str::limit($award->prerequisite->name, 14) }}
                    </span>
                @endif
                @if($isPaid)
                    <span class="ua-chip chip-earned" style="background:linear-gradient(135deg,#fef3c7,#fde68a);color:#92400e;margin-left:auto;">
                        <i class="las la-coins"></i> {{ showAmount($earned->paid_amount ?? $award->payment_amount) }}
                    </span>
                @elseif($isEarned)
                    <span class="ua-chip chip-pending">
                        <i class="las la-clock"></i> Awaiting Payment
                    </span>
                @else
                    <span class="ua-chip chip-locked">
                        <i class="las la-lock"></i> Not Qualified
                    </span>
                @endif
            </div>

            {{-- Date line --}}
            @if($earned)
                <div class="ua-earned-date {{ $isPaid ? 'paid-date' : '' }}">
                    @if($isPaid)
                        <i class="las la-check-circle" style="color:#d97706;"></i>
                        Paid on {{ $earned->paid_at?->format('M d, Y') ?? '—' }}
                    @else
                        <i class="las la-calendar-check" style="color:#16a34a;"></i>
                        Qualified on {{ $earned->earned_at?->format('M d, Y') ?? '—' }}
                    @endif
                </div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection

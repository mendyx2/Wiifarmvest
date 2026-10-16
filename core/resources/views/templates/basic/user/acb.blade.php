@extends($activeTemplate . 'layouts.master')

@push('style')
<style>
/* ═══════════════════════════════════════════════════════════════
   ACB — Achievers Celebrated Bonus — User History Page
   ═══════════════════════════════════════════════════════════════ */
.acb-page { padding: 24px 24px 60px; }

/* ── Hero ──────────────────────────────────────────────────── */
.acb-hero {
    background: linear-gradient(135deg, #15803d 0%, #64ae7f 55%, #68e295 100%);
    border-radius: 20px;
    padding: 32px 36px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    color: #fff;
}
.acb-hero::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 260px; height: 260px;
    border-radius: 50%;
    background: rgba(255,255,255,.05);
    pointer-events: none;
}
.acb-hero::after {
    content: '';
    position: absolute;
    bottom: -80px; right: 80px;
    width: 320px; height: 320px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    pointer-events: none;
}
.acb-hero-watermark {
    position: absolute;
    right: 32px; top: 50%;
    transform: translateY(-50%);
    font-size: 6rem;
    opacity: .07;
    pointer-events: none;
    line-height: 1;
}
.acb-hero-eyebrow {
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .14em;
    opacity: .75; margin-bottom: 8px;
    display: flex; align-items: center; gap: 6px;
}
.acb-hero-eyebrow::before {
    content: '';
    display: inline-block;
    width: 18px; height: 2px;
    background: rgba(255,255,255,.6);
    border-radius: 2px;
}
.acb-hero-title {
    font-size: 1.7rem; font-weight: 900;
    letter-spacing: -.03em; margin-bottom: 6px; line-height: 1.1;
}
.acb-hero-sub { font-size: .85rem; opacity: .75; margin-bottom: 28px; }

/* Stat pills */
.acb-hero-pills { display: flex; gap: 10px; flex-wrap: wrap; }
.acb-pill {
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 50px;
    padding: 8px 20px;
    display: flex; align-items: center; gap: 10px;
    backdrop-filter: blur(4px);
    font-size: .82rem;
}
.acb-pill-icon {
    width: 30px; height: 30px;
    background: rgba(255,255,255,.15);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem;
}
.acb-pill-body { line-height: 1.25; }
.acb-pill-body b { font-size: 1rem; font-weight: 800; display: block; }
.acb-pill-body span { font-size: .7rem; opacity: .75; }

/* Rate breakdown bar */
.acb-rates {
    display: flex;
    gap: 1px;
    background: #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 24px;
    font-size: .8rem;
}
.acb-rate {
    flex: 1;
    padding: 14px 16px;
    background: #fff;
    display: flex; align-items: center; gap: 10px;
}
.acb-rate-dot {
    width: 36px; height: 36px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; font-weight: 900;
    flex-shrink: 0;
}
.acb-rate-label { font-size: .7rem; color: #64748b; font-weight: 500; }
.acb-rate-pct   { font-size: 1rem; font-weight: 800; color: #0f172a; line-height: 1; }

/* ── History Table Card ────────────────────────────────────── */
.acb-hist-card {
    background: #fff;
    border: 1px solid #e8edf2;
    border-radius: 18px;
    overflow: hidden;
}
.acb-hist-head {
    padding: 18px 22px 16px;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; flex-wrap: wrap;
}
.acb-hist-head h5 {
    font-size: .78rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em;
    color: #64748b; margin: 0;
    display: flex; align-items: center; gap: 6px;
}

/* Table */
.acb-table { width: 100%; border-collapse: collapse; }
.acb-table th {
    padding: 10px 18px;
    text-align: left;
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .08em;
    color: #94a3b8;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}
.acb-table td {
    padding: 14px 18px;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
    font-size: .85rem;
}
.acb-table tbody tr:last-child td { border-bottom: none; }
.acb-table tbody tr:hover td { background: #fafbfc; }

/* TRX chip */
.acb-trx {
    font-size: .72rem; font-family: monospace;
    background: #f1f5f9; color: #475569;
    border-radius: 5px; padding: 3px 8px;
    display: inline-block;
}

/* Amount */
.acb-amount {
    font-size: .92rem; font-weight: 800; color: #15803d;
    display: flex; align-items: center; gap: 5px;
}
.acb-amount::before {
    content: '+';
    color: #16a34a;
    font-size: .7rem;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 4px;
    padding: 1px 5px;
    font-weight: 800;
}

/* Post-balance */
.acb-balance {
    font-size: .8rem; color: #64748b;
    display: flex; align-items: center; gap: 4px;
}
.acb-balance i { font-size: .65rem; color: #94a3b8; }

/* Gen badge */
.acb-gen {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: .73rem; font-weight: 700;
    padding: 3px 10px; border-radius: 20px;
}
.acb-gen-1 { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.acb-gen-2 { background: #faf5ff; color: #7c3aed; border: 1px solid #ddd6fe; }
.acb-gen-3 { background: #fdf4ff; color: #a21caf; border: 1px solid #f0abfc; }
.acb-gen-other { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

/* Date */
.acb-date { font-size: .78rem; color: #64748b; }
.acb-date-rel { font-size: .7rem; color: #94a3b8; }

/* Details text */
.acb-detail { font-size: .78rem; color: #374151; max-width: 260px; }
.acb-detail .from-user { font-weight: 700; color: #1d4ed8; }

/* Empty state */
.acb-empty {
    padding: 72px 24px;
    text-align: center;
}
.acb-empty-icon {
    width: 72px; height: 72px;
    background: linear-gradient(135deg, #1d4ed8, #3b82f6);
    border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; color: #fff;
    margin: 0 auto 18px;
    opacity: .25;
}
.acb-empty h6 { font-size: .95rem; font-weight: 700; color: #334155; margin-bottom: 6px; }
.acb-empty p  { font-size: .82rem; color: #94a3b8; max-width: 320px; margin: 0 auto; }

@media (max-width: 767px) {
    .acb-page { padding: 16px 14px 48px; }
    .acb-hero { padding: 24px 20px; }
    .acb-hero-watermark { display: none; }
    .acb-hero-title { font-size: 1.35rem; }
    .acb-rates { flex-direction: column; border-radius: 14px; }
    .acb-table th:nth-child(4),
    .acb-table td:nth-child(4) { display: none; }
}
</style>
@endpush

@section('content')
<div class="acb-page">

    {{-- ── Hero ─────────────────────────────────────────────── --}}
    <div class="acb-hero">
        <div class="acb-hero-watermark"><i class="las la-star"></i></div>
        <div class="acb-hero-eyebrow">Achievements</div>
        <h1 class="acb-hero-title">Achievers Celebrated Bonus</h1>
        <p class="acb-hero-sub">You earn when your team wins. Every award your downlines qualify for puts money in your pocket — up to 3 generations deep.</p>
        <div class="acb-hero-pills">
            <div class="acb-pill">
                <div class="acb-pill-icon"><i class="las la-wallet"></i></div>
                <div class="acb-pill-body">
                    <b>{{ showAmount($user->acb ?? 0) }}</b>
                    <span>Current Balance</span>
                </div>
            </div>
            <div class="acb-pill">
                <div class="acb-pill-icon"><i class="las la-exchange-alt"></i></div>
                <div class="acb-pill-body">
                    <b>{{ $history->total() }}</b>
                    <span>Total Payments</span>
                </div>
            </div>
            <div class="acb-pill">
                <div class="acb-pill-icon"><i class="las la-layer-group"></i></div>
                <div class="acb-pill-body">
                    <b>3 Levels</b>
                    <span>Upline Depth</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Rate breakdown ──────────────────────────────────── --}}
    <div class="acb-rates">
        <div class="acb-rate">
            <div class="acb-rate-dot" style="background:#eff6ff;color:#1d4ed8;">1</div>
            <div>
                <div class="acb-rate-pct">5%</div>
                <div class="acb-rate-label">Generation 1 · Direct Upline</div>
            </div>
        </div>
        <div class="acb-rate">
            <div class="acb-rate-dot" style="background:#faf5ff;color:#7c3aed;">2</div>
            <div>
                <div class="acb-rate-pct">2%</div>
                <div class="acb-rate-label">Generation 2 · Grandupline</div>
            </div>
        </div>
        <div class="acb-rate">
            <div class="acb-rate-dot" style="background:#fdf4ff;color:#a21caf;">3</div>
            <div>
                <div class="acb-rate-pct">1%</div>
                <div class="acb-rate-label">Generation 3 · Great-Grandupline</div>
            </div>
        </div>
    </div>

    {{-- ── History card ─────────────────────────────────────── --}}
    <div class="acb-hist-card">
        <div class="acb-hist-head">
            <h5><i class="las la-history"></i> Payment History</h5>
        </div>

        @if($history->count())
        <div class="table-responsive">
            <table class="acb-table">
                <thead>
                    <tr>
                        <th>TRX</th>
                        <th>Details</th>
                        <th>Amount</th>
                        <th>Balance After</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $txn)
                    @php
                        // Extract generation from details: "ACB Gen-1 — ..."
                        preg_match('/Gen-(\d)/', $txn->details ?? '', $genMatch);
                        $gen = $genMatch[1] ?? null;

                        // Extract the member username from details
                        preg_match('/Gen-\d+ — (\S+) /', $txn->details ?? '', $memberMatch);
                        $memberUser = $memberMatch[1] ?? null;
                    @endphp
                    <tr>
                        <td><span class="acb-trx">{{ $txn->trx }}</span></td>
                        <td>
                            <div class="acb-detail">
                                @if($gen)
                                    <span class="acb-gen acb-gen-{{ $gen <= 3 ? $gen : 'other' }}">
                                        <i class="las la-layer-group"></i> Gen {{ $gen }}
                                    </span>
                                    <br>
                                @endif
                                @if($memberUser)
                                    <span style="font-size:.73rem;color:#64748b;margin-top:4px;display:block;">
                                        from <span class="from-user">@{{ $memberUser }}</span>
                                    </span>
                                @else
                                    <span style="font-size:.73rem;color:#64748b;">{{ $txn->details }}</span>
                                @endif
                            </div>
                        </td>
                        <td><span class="acb-amount">{{ showAmount($txn->amount) }}</span></td>
                        <td>
                            <span class="acb-balance">
                                <i class="las la-wallet"></i>
                                {{ showAmount($txn->post_balance) }}
                            </span>
                        </td>
                        <td>
                            <div class="acb-date">{{ showDateTime($txn->created_at, 'd M Y') }}</div>
                            <div class="acb-date-rel">{{ diffForHumans($txn->created_at) }}</div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($history->hasPages())
        <div style="padding:16px 20px;border-top:1px solid #f1f5f9;">
            {{ paginateLinks($history) }}
        </div>
        @endif

        @else
        <div class="acb-empty">
            <div class="acb-empty-icon"><i class="las la-star"></i></div>
            <h6>No ACB payments yet</h6>
            <p>When members in your downline qualify for awards, your bonus will appear here automatically.</p>
        </div>
        @endif

    </div>

</div>
@endsection

@extends('admin.layouts.app')

@section('panel')

@push('style')
<style>
/* ═══════════════════════════════════════════════════════
   AWARD PAYMENTS — Premium Stats + Table
   ═══════════════════════════════════════════════════════ */

.awp-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 12px;
    flex-wrap: wrap;
}
.awp-eyebrow {
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em;
    color: #16a34a; margin-bottom: 4px;
}
.awp-title {
    font-size: 1.5rem; font-weight: 800;
    color: #1a1f2e; letter-spacing: -.025em; margin-bottom: 3px;
}
.awp-sub { font-size: .82rem; color: #9ca3af; }

/* Stats strip */
.awp-stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 22px;
}
@media (max-width: 768px) { .awp-stats-row { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px) { .awp-stats-row { grid-template-columns: 1fr 1fr; } }

.awp-stat {
    background: #fff;
    border-radius: 14px;
    border: 1px solid #f0f0f0;
    padding: 16px 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    display: flex; align-items: center; gap: 14px;
}
.awp-stat-icon {
    width: 46px; height: 46px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.icon-pending  { background: #fef3c7; color: #d97706; }
.icon-paid     { background: #dcfce7; color: #16a34a; }
.icon-amt-pend { background: #fff7ed; color: #ea580c; }
.icon-amt-paid { background: #eff6ff; color: #2563eb; }
.awp-stat-val {
    font-size: 1.3rem; font-weight: 800; color: #1a1f2e;
    line-height: 1; margin-bottom: 3px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.awp-stat-label { font-size: .72rem; color: #9ca3af; font-weight: 500; }

/* Filter tabs */
.awp-filter-row {
    display: flex; align-items: center;
    gap: 6px; margin-bottom: 18px; flex-wrap: wrap;
}
.awp-tab {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 18px;
    border-radius: 50px;
    font-size: .8rem; font-weight: 600;
    text-decoration: none;
    border: 1.5px solid transparent;
    transition: all .2s;
    white-space: nowrap;
}
.awp-tab:not(.active) {
    background: #f3f4f6; color: #374151; border-color: #e5e7eb;
}
.awp-tab:not(.active):hover { border-color: #16a34a; color: #16a34a; }
.awp-tab.active {
    background: #16a34a; color: #fff !important;
    border-color: #16a34a;
    box-shadow: 0 3px 10px rgba(22,163,74,.25);
}
.awp-tab-count {
    background: rgba(255,255,255,.25);
    border-radius: 20px; font-size: .68rem;
    padding: 1px 7px; font-weight: 700;
}
.awp-tab:not(.active) .awp-tab-count { background: #e5e7eb; color: #6b7280; }

/* Table card */
.awp-table-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f0f0f0;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
    overflow: hidden;
}
.awp-table { width: 100%; border-collapse: collapse; }
.awp-table thead tr {
    background: #f9fafb;
    border-bottom: 2px solid #f0f0f0;
}
.awp-table thead th {
    padding: 12px 16px;
    font-size: .69rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .08em;
    color: #6b7280; white-space: nowrap;
}
.awp-table tbody tr {
    border-bottom: 1px solid #f9fafb;
    transition: background .15s;
}
.awp-table tbody tr:last-child { border-bottom: none; }
.awp-table tbody tr:hover { background: #fafffe; }
.awp-table tbody td { padding: 14px 16px; vertical-align: middle; }

/* User cell */
.awp-user-cell { display: flex; align-items: center; gap: 10px; }
.awp-avatar {
    width: 40px; height: 40px;
    border-radius: 50%; object-fit: cover;
    border: 2px solid #f0f0f0; flex-shrink: 0;
}
.awp-avatar-ph {
    width: 40px; height: 40px;
    border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
    display: flex; align-items: center; justify-content: center;
    color: #94a3b8; font-size: 1rem;
    border: 2px solid #f0f0f0;
}
.awp-user-name  { font-size: .88rem; font-weight: 700; color: #1a1f2e; line-height: 1.1; }
.awp-user-handle { font-size: .73rem; color: #9ca3af; }

/* Award cell */
.awp-award-cell { display: flex; align-items: center; gap: 10px; }
.awp-award-thumb {
    width: 38px; height: 38px;
    border-radius: 10px; object-fit: cover;
    border: 2px solid #f0f0f0; flex-shrink: 0;
}
.awp-award-thumb-ph {
    width: 38px; height: 38px;
    border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    display: flex; align-items: center; justify-content: center;
    color: #16a34a; font-size: .95rem;
    border: 2px solid #d1fae5;
}
.awp-award-name { font-size: .85rem; font-weight: 600; color: #1a1f2e; }

/* Date */
.awp-date-d { font-size: .82rem; font-weight: 500; color: #374151; }
.awp-date-t { font-size: .72rem; color: #9ca3af; }

/* Amount */
.awp-amount {
    font-size: .95rem; font-weight: 800; color: #16a34a;
    letter-spacing: -.01em; white-space: nowrap;
}

/* Status chips */
.awp-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px;
    font-size: .74rem; font-weight: 600; white-space: nowrap;
}
.awp-badge-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.awp-badge-pending { background: #fef3c7; color: #92400e; }
.awp-badge-pending .awp-badge-dot { background: #f59e0b; }
.awp-badge-paid    { background: #dcfce7; color: #15803d; }
.awp-badge-paid .awp-badge-dot {
    background: #22c55e;
    animation: awp-glow 2s ease-out infinite;
}
@keyframes awp-glow {
    0%   { box-shadow: 0 0 0 0 rgba(34,197,94,.7); }
    70%  { box-shadow: 0 0 0 6px rgba(34,197,94,0); }
    100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
}

/* Pay button */
.awp-pay-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 14px; font-size: .76rem; font-weight: 700;
    background: linear-gradient(135deg, #16a34a, #0D5C2E);
    color: #fff; border-radius: 10px; border: none;
    cursor: pointer; transition: all .2s;
    box-shadow: 0 2px 8px rgba(13,92,46,.25);
    white-space: nowrap;
}
.awp-pay-btn:hover {
    box-shadow: 0 4px 14px rgba(13,92,46,.35);
    transform: translateY(-1px);
}

/* Empty */
.awp-empty {
    text-align: center;
    padding: 64px 20px;
}
.awp-empty-icon {
    width: 64px; height: 64px;
    border-radius: 50%;
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 14px;
    font-size: 1.8rem; color: #16a34a;
}

/* Pay modal */
.awp-modal-head {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    padding: 22px 24px;
    border-bottom: 1px solid #bbf7d0;
}
.awp-modal-head-icon {
    width: 48px; height: 48px; border-radius: 50%;
    background: #fff; border: 2px solid #bbf7d0;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; color: #16a34a; margin-bottom: 12px;
}
.awp-pay-summary {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border-radius: 14px; padding: 18px;
    border: 1px solid #bbf7d0; margin-bottom: 16px;
}
.awp-pay-summary-row {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 8px;
}
.awp-pay-summary-row:last-child { margin-bottom: 0; }
.awp-pay-lbl { font-size: .78rem; color: #6b7280; font-weight: 500; }
.awp-pay-val { font-size: .88rem; font-weight: 700; color: #1a1f2e; }
.awp-pay-amount { font-size: 1.1rem; font-weight: 800; color: #16a34a; }
</style>
@endpush

@php
    $totalPending = \App\Models\UserAward::where('status', 0)->count();
    $totalPaid    = \App\Models\UserAward::where('status', 1)->count();
    $amtPending   = \App\Models\UserAward::where('user_awards.status', 0)
                        ->join('awards', 'user_awards.award_id', '=', 'awards.id')
                        ->sum('awards.payment_amount');
    $amtPaid      = \App\Models\UserAward::where('status', 1)->sum('paid_amount');
@endphp

<div class="awp-header">
    <div>
        <div class="awp-eyebrow"><i class="las la-trophy me-1"></i>Award Management</div>
        <div class="awp-title">Award Payments</div>
        <div class="awp-sub">{{ $payments->total() }} payment record(s) found</div>
    </div>
    <a href="{{ route('admin.awards.index') }}" class="btn btn--secondary btn-sm">
        <i class="las la-trophy me-1"></i> Manage Awards
    </a>
</div>

{{-- Stats strip --}}
<div class="awp-stats-row">
    <div class="awp-stat">
        <div class="awp-stat-icon icon-pending"><i class="las la-clock"></i></div>
        <div>
            <div class="awp-stat-val">{{ $totalPending }}</div>
            <div class="awp-stat-label">Pending Payments</div>
        </div>
    </div>
    <div class="awp-stat">
        <div class="awp-stat-icon icon-paid"><i class="las la-check-circle"></i></div>
        <div>
            <div class="awp-stat-val">{{ $totalPaid }}</div>
            <div class="awp-stat-label">Paid Out</div>
        </div>
    </div>
    <div class="awp-stat">
        <div class="awp-stat-icon icon-amt-pend"><i class="las la-hand-holding-usd"></i></div>
        <div>
            <div class="awp-stat-val">{{ showAmount($amtPending) }}</div>
            <div class="awp-stat-label">Pending Amount</div>
        </div>
    </div>
    <div class="awp-stat">
        <div class="awp-stat-icon icon-amt-paid"><i class="las la-coins"></i></div>
        <div>
            <div class="awp-stat-val">{{ showAmount($amtPaid) }}</div>
            <div class="awp-stat-label">Total Paid Out</div>
        </div>
    </div>
</div>

{{-- Filter tabs --}}
<div class="awp-filter-row">
    <a href="{{ route('admin.awards.payments') }}"
       class="awp-tab {{ request('status') === null ? 'active' : '' }}">
        <i class="las la-list"></i> All
        <span class="awp-tab-count">{{ $payments->total() }}</span>
    </a>
    <a href="{{ route('admin.awards.payments', ['status' => 0]) }}"
       class="awp-tab {{ request('status') === '0' ? 'active' : '' }}">
        <i class="las la-clock"></i> Pending
        <span class="awp-tab-count">{{ $totalPending }}</span>
    </a>
    <a href="{{ route('admin.awards.payments', ['status' => 1]) }}"
       class="awp-tab {{ request('status') === '1' ? 'active' : '' }}">
        <i class="las la-check-circle"></i> Paid
        <span class="awp-tab-count">{{ $totalPaid }}</span>
    </a>
</div>

{{-- Table --}}
<div class="awp-table-card">
    <div class="table-responsive">
        <table class="awp-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Member</th>
                    <th>Award</th>
                    <th>Earned</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Paid On</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                <tr>
                    <td style="font-size:.8rem;color:#9ca3af;font-weight:700;">
                        {{ $payments->firstItem() + $loop->index }}
                    </td>
                    <td>
                        <div class="awp-user-cell">
                            @if(isset($payment->user->image) && $payment->user->image)
                                <img src="{{ getImage(getFilePath('userProfile') . '/' . $payment->user->image, '40x40') }}"
                                     alt="{{ $payment->user->fullname }}" class="awp-avatar">
                            @else
                                <div class="awp-avatar-ph"><i class="las la-user"></i></div>
                            @endif
                            <div>
                                <div class="awp-user-name">{{ $payment->user->fullname ?? '—' }}</div>
                                <div class="awp-user-handle">@ {{ $payment->user->username ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="awp-award-cell">
                            @if($payment->award && $payment->award->image_url)
                                <img src="{{ $payment->award->image_url }}" alt="{{ $payment->award->name }}" class="awp-award-thumb">
                            @else
                                <div class="awp-award-thumb-ph"><i class="las la-trophy"></i></div>
                            @endif
                            <div class="awp-award-name">{{ $payment->award->name ?? '—' }}</div>
                        </div>
                    </td>
                    <td>
                        @if($payment->earned_at)
                            <div class="awp-date-d">{{ $payment->earned_at->format('M d, Y') }}</div>
                            <div class="awp-date-t">{{ $payment->earned_at->format('h:i A') }}</div>
                        @else
                            <span style="color:#e5e7eb;">—</span>
                        @endif
                    </td>
                    <td>
                        <span class="awp-amount">{{ showAmount($payment->award->payment_amount ?? 0) }}</span>
                    </td>
                    <td>
                        @if($payment->status == 1)
                            <span class="awp-badge awp-badge-paid">
                                <span class="awp-badge-dot"></span> Paid
                            </span>
                        @else
                            <span class="awp-badge awp-badge-pending">
                                <span class="awp-badge-dot"></span> Pending
                            </span>
                        @endif
                    </td>
                    <td>
                        @if($payment->paid_at)
                            <div class="awp-date-d">{{ $payment->paid_at->format('M d, Y') }}</div>
                            <div class="awp-date-t">{{ $payment->paid_at->format('h:i A') }}</div>
                        @else
                            <span style="color:#e5e7eb;">—</span>
                        @endif
                    </td>
                    <td>
                        @if($payment->status == 0)
                            <button type="button"
                                    class="awp-pay-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#payModal"
                                    data-id="{{ $payment->id }}"
                                    data-name="{{ $payment->user->fullname ?? 'User' }}"
                                    data-award="{{ $payment->award->name ?? 'Award' }}"
                                    data-amount="{{ showAmount($payment->award->payment_amount ?? 0) }}"
                                    data-url="{{ route('admin.awards.pay', $payment->id) }}">
                                <i class="las la-hand-holding-usd"></i> Pay Now
                            </button>
                        @else
                            <span style="font-size:.76rem;color:#9ca3af;display:flex;align-items:center;gap:4px;">
                                <i class="las la-check-circle" style="color:#22c55e;font-size:.9rem;"></i> Processed
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="awp-empty">
                            <div class="awp-empty-icon"><i class="las la-trophy"></i></div>
                            <p style="font-size:.88rem;margin:0;font-weight:700;color:#374151;">No payment records found</p>
                            <p style="font-size:.78rem;margin:6px 0 0;color:#9ca3af;">Award payments will appear here once members qualify.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div style="padding:14px 18px;border-top:1px solid #f3f4f6;">
        {{ paginateLinks($payments) }}
    </div>
    @endif
</div>

{{-- Pay Modal --}}
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content" style="border-radius:18px;border:none;overflow:hidden;">
            <div class="awp-modal-head">
                <div class="awp-modal-head-icon"><i class="las la-hand-holding-usd"></i></div>
                <h5 class="mb-1 fw-bold" style="color:#0D5C2E;">Process Award Payment</h5>
                <p class="mb-0" style="font-size:.8rem;color:#6b7280;">
                    This will credit the award amount directly to the member's awards wallet.
                </p>
            </div>
            <form id="payForm" method="POST">
                @csrf
                <div class="modal-body px-4 py-4">
                    <div class="awp-pay-summary">
                        <div class="awp-pay-summary-row">
                            <span class="awp-pay-lbl"><i class="las la-user me-1"></i>Member</span>
                            <span class="awp-pay-val" id="payMemberName">—</span>
                        </div>
                        <div class="awp-pay-summary-row">
                            <span class="awp-pay-lbl"><i class="las la-trophy me-1"></i>Award</span>
                            <span class="awp-pay-val" id="payAwardName">—</span>
                        </div>
                        <div class="awp-pay-summary-row">
                            <span class="awp-pay-lbl"><i class="las la-coins me-1"></i>Amount</span>
                            <span class="awp-pay-amount" id="payAmount">—</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label fw-semibold" style="font-size:.82rem;">
                            Payment Note <small class="text-muted fw-normal">(optional)</small>
                        </label>
                        <textarea name="note" class="form-control" rows="3"
                                  placeholder="Add an internal note about this payment…"
                                  style="border-radius:10px;font-size:.84rem;resize:none;"></textarea>
                    </div>
                </div>
                <div style="padding:0 24px 20px;display:flex;gap:8px;justify-content:flex-end;">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--success">
                        <i class="las la-check me-1"></i> Confirm Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('payModal').addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        document.getElementById('payMemberName').textContent = btn.getAttribute('data-name');
        document.getElementById('payAwardName').textContent  = btn.getAttribute('data-award');
        document.getElementById('payAmount').textContent     = btn.getAttribute('data-amount');
        document.getElementById('payForm').action            = btn.getAttribute('data-url');
    });
});
</script>
@endpush

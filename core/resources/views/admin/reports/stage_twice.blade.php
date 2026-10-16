@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-12">

        {{-- Search ──────────────────────────────────────────────────── --}}
        <form method="GET" action="{{ route('admin.report.stage.twice') }}" class="mb-3" id="searchForm">
            <input type="hidden" name="stage" value="{{ $stage }}">
            <div class="input-group" style="max-width:440px;">
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="@lang('Search by username, name or TRX…')"
                    value="{{ $search ?? '' }}"
                >
                <button class="btn btn--primary" type="submit">
                    <i class="las la-search"></i>
                </button>
                @if($search)
                    <a href="{{ route('admin.report.stage.twice', ['stage' => $stage]) }}"
                       class="btn btn-outline--danger" title="Clear search">
                        <i class="las la-times"></i>
                    </a>
                @endif
            </div>
        </form>

        {{-- Stage tabs ──────────────────────────────────────────────── --}}
        <ul class="nav nav-tabs mb-3">
            @foreach ([1, 2, 3] as $s)
                <li class="nav-item">
                    <a class="nav-link {{ $stage == $s ? 'active' : '' }}"
                       href="{{ route('admin.report.stage.twice', ['stage' => $s]) }}">
                        Stage {{ $s }}
                        @if ($stage == $s && $transactions->total() > 0)
                            <span class="badge bg--danger ms-1">{{ $transactions->total() }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- Download form (POST) — hidden inputs filled by JS before submit ── --}}
        <form id="downloadForm" method="POST"
              action="{{ route('admin.report.stage.twice.download') }}" target="_blank">
            @csrf
            <input type="hidden" name="stage"  value="{{ $stage }}">
            <input type="hidden" name="search" value="{{ $search ?? '' }}">
            <input type="hidden" name="ids"    id="downloadIds">
        </form>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="card-title mb-0">
                    @lang('Users Who Received Stage') {{ $stage }} @lang('Bonus More Than Once')
                    @if ($transactions->total() > 0)
                        <span class="badge bg--danger ms-2">{{ $transactions->total() }} @lang('records')</span>
                    @endif
                </h5>

                <div class="d-flex align-items-center gap-2">
                    <span id="selectedCount" class="text-muted small" style="display:none;">
                        <span id="selectedNum">0</span> @lang('selected')
                    </span>
                    <button type="button" id="downloadBtn" class="btn btn-outline--primary btn-sm">
                        <i class="las la-file-excel"></i> @lang('Download Checked')
                    </button>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive--lg table-responsive">
                    <table class="table--light style--two table" id="mainTable">
                        <thead>
                            <tr>
                                <th style="width:42px;">
                                    <input type="checkbox" id="checkAll" title="Select / deselect all">
                                </th>
                                <th>@lang('User')</th>
                                <th>@lang('TRX')</th>
                                <th>@lang('Times Received')</th>
                                <th>@lang('Transacted')</th>
                                <th>@lang('Amount')</th>
                                <th>@lang('Post Balance')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transactions as $trx)
                                <tr>
                                    <td>
                                        <input
                                            type="checkbox"
                                            class="row-check"
                                            value="{{ $trx->id }}"
                                        >
                                    </td>
                                    <td>
                                        <span class="fw-bold">{{ $trx->user?->fullname }}</span><br>
                                        <span class="small">
                                            <a href="{{ route('admin.users.detail', $trx->user_id) }}">
                                                <span>@</span>{{ $trx->user?->username }}
                                            </a>
                                        </span>
                                    </td>
                                    <td><strong>{{ $trx->trx }}</strong></td>
                                    <td>
                                        <span class="badge bg--danger">
                                            {{ $countPerUser[$trx->user_id] ?? 1 }}x
                                        </span>
                                    </td>
                                    <td>
                                        {{ showDateTime($trx->created_at) }}<br>
                                        <span class="small text-muted">{{ diffForHumans($trx->created_at) }}</span>
                                    </td>
                                    <td class="budget">
                                        <span class="fw-bold text--success">
                                            + {{ showAmount($trx->amount) }}
                                        </span>
                                    </td>
                                    <td class="budget">{{ showAmount($trx->post_balance) }}</td>
                                    <td>
                                        @if(isset($refundedMap[$trx->user_id]))
                                            @php $rf = $refundedMap[$trx->user_id]; @endphp
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <span class="badge bg--success">
                                                    <i class="las la-check-circle"></i>
                                                    @lang('Refunded')
                                                </span>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm rd-detail-trigger"
                                                    data-user-fullname="{{ $trx->user?->fullname }}"
                                                    data-user-username="{{ $trx->user?->username }}"
                                                    data-stage="{{ $stage }}"
                                                    data-original-trx="{{ $rf->original_trx }}"
                                                    data-refund-trx="{{ $rf->refund_trx }}"
                                                    data-amount="{{ showAmount($rf->amount) }}"
                                                    data-balance-before="{{ showAmount($rf->balance_before) }}"
                                                    data-balance-after="{{ showAmount($rf->balance_after) }}"
                                                    data-admin="{{ $rf->admin?->name ?? $rf->admin?->username ?? 'System' }}"
                                                    data-date="{{ showDateTime($rf->created_at) }}"
                                                    data-note="{{ $rf->note }}"
                                                >
                                                    <i class="las la-receipt"></i> @lang('Details')
                                                </button>
                                            </div>
                                        @else
                                            <button
                                                type="button"
                                                class="btn btn-outline--danger btn-sm confirm-refund"
                                                data-id="{{ $trx->id }}"
                                                data-trx="{{ $trx->trx }}"
                                                data-amount="{{ showAmount($trx->amount) }}"
                                                data-user="{{ $trx->user?->username }}"
                                                data-fullname="{{ $trx->user?->fullname }}"
                                            >
                                                <i class="las la-undo-alt"></i> @lang('Refund')
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="8">
                                        @if($search)
                                            @lang('No results for') "<strong>{{ $search }}</strong>".
                                        @else
                                            @lang('No users have received the stage') {{ $stage }} @lang('bonus more than once.')
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($transactions->hasPages())
                <div class="card-footer py-4">
                    {{ paginateLinks($transactions) }}
                </div>
            @endif
        </div>

    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     Refund details modal
════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="detailsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered rdt-dialog">
        <div class="modal-content rdt-card">

            {{-- Close --}}
            <button type="button" class="rd-close" data-bs-dismiss="modal" aria-label="Close">
                <i class="las la-times"></i>
            </button>

            {{-- Header stamp zone --}}
            <div class="rdt-header">
                <div class="rdt-stamp-ring">
                    <div class="rdt-stamp-icon">
                        <i class="las la-check-double"></i>
                    </div>
                </div>
                <div class="rdt-header-text">
                    <span class="rdt-label-tag">@lang('Deduction Record')</span>
                    <h4 class="rdt-title">@lang('Refund Details')</h4>
                    <p class="rdt-date" id="rdt-date"></p>
                </div>
                <div class="rdt-processed-stamp">@lang('PROCESSED')</div>
            </div>

            {{-- User card --}}
            <div class="rdt-user-card">
                <div class="rdt-user-avatar" id="rdt-avatar-initials"></div>
                <div class="rdt-user-info">
                    <span class="rdt-user-name" id="rdt-user-fullname"></span>
                    <span class="rdt-user-handle" id="rdt-user-username"></span>
                </div>
                <div class="rdt-stage-pill" id="rdt-stage-pill"></div>
            </div>

            {{-- Amount flow --}}
            <div class="rdt-flow">
                <div class="rdt-flow-item">
                    <span class="rdt-flow-label">@lang('Balance Before')</span>
                    <span class="rdt-flow-value rdt-flow-before" id="rdt-balance-before"></span>
                </div>
                <div class="rdt-flow-arrow">
                    <i class="las la-long-arrow-alt-right"></i>
                    <span class="rdt-flow-deducted" id="rdt-amount-deducted"></span>
                </div>
                <div class="rdt-flow-item">
                    <span class="rdt-flow-label">@lang('Balance After')</span>
                    <span class="rdt-flow-value rdt-flow-after" id="rdt-balance-after"></span>
                </div>
            </div>

            {{-- TRX details --}}
            <div class="rdt-trx-grid">
                <div class="rdt-trx-row">
                    <span class="rdt-trx-label">
                        <i class="las la-receipt"></i> @lang('Original TRX')
                    </span>
                    <code class="rdt-trx-code" id="rdt-original-trx"></code>
                </div>
                <div class="rdt-trx-row">
                    <span class="rdt-trx-label">
                        <i class="las la-undo-alt"></i> @lang('Refund TRX')
                    </span>
                    <code class="rdt-trx-code rdt-trx-code--refund" id="rdt-refund-trx"></code>
                </div>
                <div class="rdt-trx-row">
                    <span class="rdt-trx-label">
                        <i class="las la-user-shield"></i> @lang('Processed By')
                    </span>
                    <span class="rdt-trx-val" id="rdt-admin"></span>
                </div>
                <div class="rdt-trx-row" id="rdt-note-row">
                    <span class="rdt-trx-label">
                        <i class="las la-sticky-note"></i> @lang('Note')
                    </span>
                    <span class="rdt-trx-val rdt-note" id="rdt-note"></span>
                </div>
            </div>

            {{-- Footer --}}
            <div class="rdt-footer">
                <button type="button" class="rdt-btn-close" data-bs-dismiss="modal">
                    @lang('Close')
                </button>
            </div>

        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     Refund confirmation modal
════════════════════════════════════════════════════════════════ --}}
<div class="modal fade rd-modal" id="refundModal" tabindex="-1"
     aria-labelledby="refundModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered rd-dialog">
        <div class="modal-content rd-card">

            {{-- Close pill --}}
            <button type="button" class="rd-close" data-bs-dismiss="modal" aria-label="Close">
                <i class="las la-times"></i>
            </button>

            {{-- Icon zone --}}
            <div class="rd-icon-zone">
                <div class="rd-icon-ring rd-ring-1"></div>
                <div class="rd-icon-ring rd-ring-2"></div>
                <div class="rd-icon-wrap">
                    <i class="las la-exclamation-triangle"></i>
                </div>
            </div>

            {{-- Heading --}}
            <h4 class="rd-title" id="refundModalLabel">@lang('Confirm Deduction')</h4>
            <p class="rd-sub">@lang('This will permanently deduct funds from the user\'s account.')</p>

            {{-- Detail cards --}}
            <div class="rd-detail-grid">
                <div class="rd-detail-row">
                    <span class="rd-detail-label"><i class="las la-user"></i> @lang('User')</span>
                    <span class="rd-detail-value">
                        <span id="rm-fullname" class="rd-val-primary"></span>
                        <span id="rm-user"     class="rd-val-sub"></span>
                    </span>
                </div>
                <div class="rd-detail-row">
                    <span class="rd-detail-label"><i class="las la-hashtag"></i> @lang('TRX')</span>
                    <span class="rd-detail-value">
                        <code id="rm-trx" class="rd-code"></code>
                    </span>
                </div>
                <div class="rd-detail-row rd-detail-row--highlight">
                    <span class="rd-detail-label"><i class="las la-money-bill-wave"></i> @lang('Amount')</span>
                    <span class="rd-detail-value">
                        <span id="rm-amount" class="rd-val-amount"></span>
                    </span>
                </div>
            </div>

            {{-- Warning strip --}}
            <div class="rd-warning">
                <i class="las la-shield-alt rd-warning-icon"></i>
                <p class="rd-warning-text">
                    @lang('Original transaction is preserved. A new deduction record will be created. Balance may go negative. </strong>.')
                </p>
            </div>

            {{-- Actions --}}
            <div class="rd-actions">
                <button type="button" class="rd-btn-cancel" data-bs-dismiss="modal">
                    @lang('Cancel')
                </button>
                <form id="refundForm" method="POST" action="" class="d-contents">
                    @csrf
                    <button type="submit" class="rd-btn-confirm" id="rd-confirm-btn">
                        <span class="rd-btn-confirm__idle text-white">
                            <i class="las la-undo-alt"></i>
                            @lang('Yes, Deduct Now')
                        </span>
                        <span class="rd-btn-confirm__busy" style="display:none;">
                            <span class="rd-spinner"></span>
                            @lang('Processing…')
                        </span>
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection

@push('style')
<style>
/* ════════════════════════════════════════════════
   DETAILS MODAL
════════════════════════════════════════════════ */

/* Dialog sizing */
.rdt-dialog { max-width: 500px; }

/* Card shell */
.rdt-card {
    border: none;
    border-radius: 24px;
    overflow: hidden;
    box-shadow:
        0 4px 6px -1px rgba(0,0,0,.06),
        0 24px 64px -8px rgba(0,0,0,.2),
        0 0 0 1px rgba(0,0,0,.04);
    animation: rdSlideIn .28s cubic-bezier(.22,1,.36,1) both;
    background: #fff;
    padding: 0;
}

/* ── Header zone ───────────────────────────────── */
.rdt-header {
    position: relative;
    background: linear-gradient(135deg, #0f172a 0%, #134e4a 60%, #0f172a 100%);
    padding: 2rem 2rem 1.6rem;
    display: flex;
    align-items: center;
    gap: 1.1rem;
    overflow: hidden;
}
.rdt-header::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        radial-gradient(ellipse 60% 80% at 80% 50%, rgba(16,185,129,.15) 0%, transparent 70%),
        radial-gradient(ellipse 40% 60% at 10% 20%, rgba(99,102,241,.1) 0%, transparent 60%);
    pointer-events: none;
}
/* Fine grid overlay on header */
.rdt-header::after {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
    background-size: 28px 28px;
    pointer-events: none;
}

.rdt-stamp-ring {
    position: relative;
    flex-shrink: 0;
    width: 60px; height: 60px;
    z-index: 1;
}
.rdt-stamp-ring::before {
    content: '';
    position: absolute;
    inset: -5px;
    border-radius: 50%;
    border: 1.5px solid rgba(16,185,129,.4);
    animation: rdt-ring-rotate 8s linear infinite;
    background: conic-gradient(from 0deg, transparent 75%, rgba(16,185,129,.5) 100%);
}
@keyframes rdt-ring-rotate { to { transform: rotate(360deg); } }

.rdt-stamp-icon {
    width: 60px; height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981, #059669);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
    color: #fff;
    box-shadow: 0 0 0 4px rgba(16,185,129,.2), 0 8px 20px rgba(16,185,129,.35);
    position: relative;
    z-index: 1;
}

.rdt-header-text {
    flex: 1;
    min-width: 0;
    z-index: 1;
}
.rdt-label-tag {
    font-size: .65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .15em;
    color: #10b981;
    display: block;
    margin-bottom: .2rem;
}
.rdt-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: #fff;
    margin: 0 0 .2rem;
    letter-spacing: -.02em;
}
.rdt-date {
    font-size: .72rem;
    color: rgba(255,255,255,.5);
    margin: 0;
}

/* PROCESSED stamp */
.rdt-processed-stamp {
    position: absolute;
    top: 1.1rem; right: 1.2rem;
    font-size: .6rem;
    font-weight: 800;
    letter-spacing: .18em;
    color: rgba(16,185,129,.55);
    border: 1.5px solid rgba(16,185,129,.35);
    border-radius: 6px;
    padding: .18rem .5rem;
    transform: rotate(8deg);
    z-index: 1;
    pointer-events: none;
}

/* ── User card ─────────────────────────────────── */
.rdt-user-card {
    display: flex;
    align-items: center;
    gap: .9rem;
    padding: 1.1rem 1.6rem;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}
.rdt-user-avatar {
    width: 44px; height: 44px;
    border-radius: 14px;
    background: linear-gradient(135deg, #10b981, #0284c7);
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
    letter-spacing: -.02em;
}
.rdt-user-info { flex: 1; min-width: 0; }
.rdt-user-name {
    display: block;
    font-size: .9rem;
    font-weight: 700;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.rdt-user-handle {
    display: block;
    font-size: .75rem;
    color: #64748b;
}
.rdt-stage-pill {
    flex-shrink: 0;
    font-size: .72rem;
    font-weight: 700;
    color: #0369a1;
    background: #e0f2fe;
    border-radius: 20px;
    padding: .25rem .75rem;
    letter-spacing: .04em;
}

/* ── Amount flow ───────────────────────────────── */
.rdt-flow {
    display: flex;
    align-items: center;
    gap: 0;
    padding: 1.2rem 1.6rem 1rem;
    border-bottom: 1px solid #f1f5f9;
}
.rdt-flow-item {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: .2rem;
}
.rdt-flow-item:last-child { align-items: flex-end; }
.rdt-flow-label {
    font-size: .68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: #94a3b8;
}
.rdt-flow-value {
    font-size: 1.05rem;
    font-weight: 700;
    letter-spacing: -.02em;
}
.rdt-flow-before { color: #0f172a; }
.rdt-flow-after  { color: #ef4444; }

.rdt-flow-arrow {
    flex: 0 0 auto;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: .1rem;
    padding: 0 1rem;
    color: #cbd5e1;
    font-size: 1.4rem;
    line-height: 1;
}
.rdt-flow-deducted {
    font-size: .68rem;
    font-weight: 700;
    color: #ef4444;
    background: #fef2f2;
    border-radius: 10px;
    padding: .1rem .4rem;
    white-space: nowrap;
}

/* ── TRX grid ──────────────────────────────────── */
.rdt-trx-grid {
    padding: .8rem 1.6rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0;
}
.rdt-trx-row {
    display: flex;
    align-items: flex-start;
    gap: .75rem;
    padding: .65rem 0;
    border-bottom: 1px solid #f8fafc;
}
.rdt-trx-row:last-child { border-bottom: none; }
.rdt-trx-label {
    flex: 0 0 130px;
    font-size: .72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: .3rem;
    padding-top: .1rem;
}
.rdt-trx-label i { font-size: .85rem; }
.rdt-trx-code {
    font-size: .8rem;
    background: #f1f5f9;
    color: #334155;
    padding: .2rem .55rem;
    border-radius: 7px;
    word-break: break-all;
    display: inline-block;
    flex: 1;
}
.rdt-trx-code--refund {
    background: #f0fdf4;
    color: #15803d;
}
.rdt-trx-val {
    font-size: .84rem;
    font-weight: 600;
    color: #1e293b;
    flex: 1;
}
.rdt-note {
    font-weight: 400;
    font-size: .78rem;
    color: #64748b;
    font-style: italic;
}

/* ── Footer ────────────────────────────────────── */
.rdt-footer {
    padding: .9rem 1.6rem 1.4rem;
    display: flex;
    justify-content: flex-end;
}
.rdt-btn-close {
    height: 40px;
    padding: 0 1.6rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    background: #fff;
    color: #475569;
    font-size: .84rem;
    font-weight: 600;
    cursor: pointer;
    transition: border-color .18s, background .18s, color .18s;
}
.rdt-btn-close:hover {
    border-color: #10b981;
    color: #10b981;
    background: #f0fdf4;
}

/* Details trigger button */
.rd-detail-trigger {
    font-size: .72rem;
    font-weight: 600;
    color: #0369a1;
    background: #e0f2fe;
    border: none;
    border-radius: 8px;
    padding: .2rem .6rem;
    transition: background .18s, color .18s;
    line-height: 1.4;
}
.rd-detail-trigger:hover {
    background: #bae6fd;
    color: #0c4a6e;
}

/* ── Modal backdrop ────────────────────────────────────────────── */
.rd-modal .modal-backdrop,
.rd-modal.show ~ .modal-backdrop { backdrop-filter: blur(6px); }

/* ── Dialog sizing ─────────────────────────────────────────────── */
.rd-dialog {
    max-width: 480px;
    margin: 1.5rem auto;
}

/* ── Card shell ────────────────────────────────────────────────── */
.rd-card {
    position: relative;
    border: none;
    border-radius: 24px;
    padding: 2.4rem 2.2rem 2rem;
    background: #ffffff;
    box-shadow:
        0 4px 6px -1px rgba(0,0,0,.06),
        0 20px 60px -8px rgba(0,0,0,.18),
        0 0 0 1px rgba(0,0,0,.04);
    animation: rdSlideIn .28s cubic-bezier(.22,1,.36,1) both;
    overflow: visible;
}
@keyframes rdSlideIn {
    from { opacity: 0; transform: translateY(20px) scale(.97); }
    to   { opacity: 1; transform: translateY(0)    scale(1);   }
}

/* Top danger accent bar */
.rd-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 4px;
    border-radius: 24px 24px 0 0;
    background: linear-gradient(90deg, #ef4444, #f97316, #ef4444);
    background-size: 200% 100%;
    animation: rdBar 3s linear infinite;
}
@keyframes rdBar {
    from { background-position: 200% center; }
    to   { background-position: -200% center; }
}

/* ── Close pill ────────────────────────────────────────────────── */
.rd-close {
    position: absolute;
    top: 1rem; right: 1rem;
    width: 32px; height: 32px;
    border: none;
    border-radius: 50%;
    background: #f1f5f9;
    color: #64748b;
    font-size: .95rem;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: background .18s, color .18s, transform .18s;
    line-height: 1;
}
.rd-close:hover {
    background: #fee2e2;
    color: #ef4444;
    transform: rotate(90deg);
}

/* ── Icon zone ─────────────────────────────────────────────────── */
.rd-icon-zone {
    position: relative;
    width: 76px; height: 76px;
    margin: 0 auto 1.4rem;
}
.rd-icon-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    animation: rdPulse 2.4s ease-in-out infinite;
}
.rd-ring-1 {
    background: rgba(239,68,68,.12);
    animation-delay: 0s;
}
.rd-ring-2 {
    background: rgba(239,68,68,.07);
    transform: scale(1.3);
    animation-delay: .4s;
}
@keyframes rdPulse {
    0%,100% { transform: scale(1);    opacity: 1; }
    50%      { transform: scale(1.15); opacity: .6; }
}
.rd-ring-2 { animation-name: rdPulse2; }
@keyframes rdPulse2 {
    0%,100% { transform: scale(1.3);  opacity: .7; }
    50%      { transform: scale(1.5);  opacity: .35; }
}

.rd-icon-wrap {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 8px 24px rgba(239,68,68,.38);
    font-size: 1.85rem;
    color: #fff;
}

/* ── Heading ───────────────────────────────────────────────────── */
.rd-title {
    text-align: center;
    font-size: 1.2rem;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -.02em;
    margin: 0 0 .35rem;
}
.rd-sub {
    text-align: center;
    font-size: .78rem;
    color: #94a3b8;
    margin: 0 0 1.6rem;
}

/* ── Detail grid ───────────────────────────────────────────────── */
.rd-detail-grid {
    border: 1.5px solid #f1f5f9;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 1.2rem;
}
.rd-detail-row {
    display: flex;
    align-items: center;
    padding: .7rem 1rem;
    border-bottom: 1px solid #f8fafc;
    gap: .75rem;
    transition: background .15s;
}
.rd-detail-row:last-child { border-bottom: none; }
.rd-detail-row--highlight  { background: #fff7f7; }

.rd-detail-label {
    flex: 0 0 110px;
    font-size: .73rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: .35rem;
}
.rd-detail-label i { font-size: .9rem; }

.rd-detail-value {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: .1rem;
    min-width: 0;
}
.rd-val-primary {
    font-size: .88rem;
    font-weight: 600;
    color: #0f172a;
}
.rd-val-sub {
    font-size: .75rem;
    color: #64748b;
}
.rd-val-amount {
    font-size: 1.15rem;
    font-weight: 700;
    color: #ef4444;
}
.rd-code {
    font-size: .8rem;
    background: #f1f5f9;
    color: #475569;
    padding: .15rem .45rem;
    border-radius: 6px;
    word-break: break-all;
    display: inline-block;
}

/* ── Warning strip ─────────────────────────────────────────────── */
.rd-warning {
    display: flex;
    gap: .65rem;
    align-items: flex-start;
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 12px;
    padding: .75rem 1rem;
    margin-bottom: 1.6rem;
}
.rd-warning-icon {
    font-size: 1.15rem;
    color: #d97706;
    flex-shrink: 0;
    margin-top: .05rem;
}
.rd-warning-text {
    font-size: .75rem;
    color: #78350f;
    line-height: 1.55;
    margin: 0;
}

/* ── Action row ────────────────────────────────────────────────── */
.rd-actions {
    display: flex;
    gap: .75rem;
    align-items: center;
}
.d-contents { display: contents; }

.rd-btn-cancel {
    flex: 1;
    height: 46px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    color: #475569;
    font-size: .84rem;
    font-weight: 600;
    cursor: pointer;
    transition: border-color .2s, background .2s, color .2s;
}
.rd-btn-cancel:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
    color: #0f172a;
}

.rd-btn-confirm {
    flex: 1.4;
    height: 46px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #fff;
    font-size: .84rem;
    font-weight: 600;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    box-shadow: 0 4px 14px rgba(239,68,68,.32);
    transition: transform .2s, box-shadow .2s;
}
.rd-btn-confirm:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(239,68,68,.42);
}
.rd-btn-confirm:active { transform: translateY(0); }
.rd-btn-confirm::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.18), transparent);
    transform: translateX(-100%);
    transition: transform .5s ease;
}
.rd-btn-confirm:hover::after { transform: translateX(100%); }

.rd-btn-confirm__idle,
.rd-btn-confirm__busy {
    display: flex;
    align-items: center;
    gap: .4rem;
}

/* Spinner */
.rd-spinner {
    width: 15px; height: 15px;
    border: 2px solid rgba(255,255,255,.35);
    border-top-color: #fff;
    border-radius: 50%;
    animation: rdSpin .65s linear infinite;
    display: inline-block;
}
@keyframes rdSpin { to { transform: rotate(360deg); } }
</style>
@endpush

@push('script')
<script>
"use strict";

/* ── Checkbox logic ──────────────────────────────────────────── */
var $checkAll  = $('#checkAll');
var $rowChecks = $('.row-check');

// Toggle all when header checkbox changes
$checkAll.on('change', function () {
    $rowChecks.prop('checked', this.checked);
    updateSelectedCount();
});

// Keep header checkbox in sync; update counter
$rowChecks.on('change', function () {
    var total   = $rowChecks.length;
    var checked = $rowChecks.filter(':checked').length;
    $checkAll.prop('indeterminate', checked > 0 && checked < total);
    $checkAll.prop('checked', checked === total && total > 0);
    updateSelectedCount();
});

function updateSelectedCount() {
    var n = $rowChecks.filter(':checked').length;
    if (n > 0) {
        $('#selectedNum').text(n);
        $('#selectedCount').show();
    } else {
        $('#selectedCount').hide();
    }
}

/* ── Download — only checked rows ───────────────────────────── */
$('#downloadBtn').on('click', function () {
    var ids = $rowChecks.filter(':checked').map(function () {
        return $(this).val();
    }).get();

    if (ids.length === 0) {
        alert('{{ __("Please check at least one row before downloading.") }}');
        return;
    }

    $('#downloadIds').val(ids.join(','));
    $('#downloadForm').trigger('submit');
});

/* ── Refund modal ────────────────────────────────────────────── */
$(document).on('click', '.confirm-refund', function () {
    var id       = $(this).data('id');
    var trx      = $(this).data('trx');
    var amount   = $(this).data('amount');
    var user     = $(this).data('user');
    var fullname = $(this).data('fullname');

    $('#rm-amount').text(amount);
    $('#rm-user').text('@' + user);
    $('#rm-fullname').text(fullname);
    $('#rm-trx').text(trx);
    $('#refundForm').attr('action', '{{ url("admin/report/stage-twice/refund") }}/' + id);

    // Reset button state each time modal opens
    var $idle = $('.rd-btn-confirm__idle');
    var $busy = $('.rd-btn-confirm__busy');
    $idle.show();
    $busy.hide();
    $('#rd-confirm-btn').prop('disabled', false);

    new bootstrap.Modal(document.getElementById('refundModal')).show();
});

// Show spinner while form submits
$('#refundForm').on('submit', function () {
    var $idle = $('.rd-btn-confirm__idle');
    var $busy = $('.rd-btn-confirm__busy');
    $idle.hide();
    $busy.show();
    $('#rd-confirm-btn').prop('disabled', true);
});

/* ── Details modal ───────────────────────────────────────────── */
$(document).on('click', '.rd-detail-trigger', function () {
    var d = $(this).data();

    // Populate fields
    $('#rdt-date').text(d.date);
    $('#rdt-user-fullname').text(d.userFullname);
    $('#rdt-user-username').text('@' + d.userUsername);
    $('#rdt-stage-pill').text('Stage ' + d.stage);
    $('#rdt-balance-before').text(d.balanceBefore);
    $('#rdt-balance-after').text(d.balanceAfter);
    $('#rdt-amount-deducted').text('− ' + d.amount);
    $('#rdt-original-trx').text(d.originalTrx);
    $('#rdt-refund-trx').text(d.refundTrx);
    $('#rdt-admin').text(d.admin);
    $('#rdt-note').text(d.note || '—');

    // Avatar initials
    var parts    = (d.userFullname || '').trim().split(' ');
    var initials = (parts[0] ? parts[0][0] : '') + (parts[1] ? parts[1][0] : '');
    $('#rdt-avatar-initials').text(initials.toUpperCase());

    new bootstrap.Modal(document.getElementById('detailsModal')).show();
});
</script>
@endpush

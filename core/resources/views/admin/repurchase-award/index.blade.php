@extends('admin.layouts.app')

@section('panel')
<div class="rpa-page">

    {{-- ── AWARDS GRID ────────────────────────────────────────── --}}
    <div class="rpa-awards-grid">
        @forelse($awards as $award)
        <div class="rpa-award-card {{ $award->status ? '' : 'rpa-award-card--off' }}">
            <div class="rpa-award-card-top">
                <div class="rpa-award-icon">
                    <i class="las la-medal"></i>
                </div>
                <div class="rpa-award-meta">
                    <div class="rpa-award-title">{{ $award->title }}</div>
                    <div class="rpa-award-status {{ $award->status ? 'rpa-status-on' : 'rpa-status-off' }}">
                        {{ $award->status ? 'Active' : 'Inactive' }}
                    </div>
                </div>
                <div class="rpa-award-card-actions">
                    <button class="rpa-card-btn rpa-card-btn-edit" onclick="openEdit({{ $award->id }}, '{{ addslashes($award->title) }}', {{ $award->required_pv }}, {{ $award->amount }})" title="Edit">
                        <i class="las la-pen"></i>
                    </button>
                    <form action="{{ route('admin.repurchase-award.toggle', $award->id) }}" method="POST" style="display:inline">
                        @csrf
                        <button type="submit" class="rpa-card-btn {{ $award->status ? 'rpa-card-btn-deactivate' : 'rpa-card-btn-activate' }}" title="{{ $award->status ? 'Deactivate' : 'Activate' }}">
                            <i class="las {{ $award->status ? 'la-toggle-on' : 'la-toggle-off' }}"></i>
                        </button>
                    </form>
                    <form action="{{ route('admin.repurchase-award.destroy', $award->id) }}" method="POST" style="display:inline"
                        onsubmit="return confirm('Delete {{ addslashes($award->title) }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="rpa-card-btn rpa-card-btn-del" title="Delete">
                            <i class="las la-trash-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="rpa-award-stats">
                <div class="rpa-stat">
                    <span class="rpa-stat-label">Required PV</span>
                    <span class="rpa-stat-val rpa-stat-pv">{{ number_format($award->required_pv, 2) }}</span>
                </div>
                <div class="rpa-stat-sep"></div>
                <div class="rpa-stat">
                    <span class="rpa-stat-label">Reward Amount</span>
                    <span class="rpa-stat-val rpa-stat-amt">₦{{ number_format($award->amount, 2) }}</span>
                </div>
                <div class="rpa-stat-sep"></div>
                <div class="rpa-stat">
                    <span class="rpa-stat-label">Qualified</span>
                    <a href="{{ route('admin.repurchase-award.qualified', $award->id) }}" class="rpa-stat-val rpa-stat-count">
                        {{ $qualifiedCounts[$award->id] ?? 0 }} users
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="rpa-empty-awards">
            <i class="las la-medal"></i>
            <p>No awards configured yet. Add one using the button above.</p>
        </div>
        @endforelse
    </div>

    {{-- ── PV LEADERBOARD ─────────────────────────────────────── --}}
    <div class="rpa-board-card">
        <div class="rpa-board-head">
            <div class="rpa-board-head-left">
                <i class="las la-chart-bar"></i>
                <span>Member PV Leaderboard</span>
            </div>
            <span class="rpa-board-count">{{ $pvUsers->total() }} members with PV</span>
        </div>
        <div class="table-responsive">
            <table class="table rpa-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Member</th>
                        <th>Username</th>
                        <th class="text-center">Total PV</th>
                        <th>Qualifies For</th>
                        <th class="text-right">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pvUsers as $i => $pv)
                    @php
                        $userTotalPv = (float) $pv->total_pv;
                        $nextAward   = $awards->where('status', true)->where('required_pv', '>', $userTotalPv)->sortBy('required_pv')->first();
                        $qualifiedForCount = $awards->where('status', true)->where('required_pv', '<=', $userTotalPv)->count();
                        $topAward = $awards->where('status', true)->where('required_pv', '<=', $userTotalPv)->sortByDesc('required_pv')->first();
                    @endphp
                    <tr>
                        <td class="rpa-rank">
                            @if($pvUsers->currentPage() == 1 && $i < 3)
                                <span class="rpa-rank-medal rpa-rank-{{ $i+1 }}">{{ $i+1 }}</span>
                            @else
                                <span class="rpa-rank-num">{{ ($pvUsers->currentPage()-1)*$pvUsers->perPage() + $i + 1 }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="rpa-member">
                                <div class="rpa-avatar">{{ strtoupper(substr(optional($pv->user)->firstname ?? 'U', 0, 1)) }}{{ strtoupper(substr(optional($pv->user)->lastname ?? '', 0, 1)) }}</div>
                                <div>
                                    <div class="rpa-member-name">{{ optional($pv->user)->fullname ?? '—' }}</div>
                                    <div class="rpa-member-email">{{ optional($pv->user)->email ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="rpa-username">{{ optional($pv->user)->username ?? '—' }}</td>
                        <td class="text-center">
                            <span class="rpa-pv-chip">{{ number_format($userTotalPv, 2) }} PV</span>
                        </td>
                        <td>
                            @if($topAward)
                                <span class="rpa-qualified-badge">{{ $topAward->title }}</span>
                                @if($qualifiedForCount > 1)
                                    <span class="rpa-more-badge">+{{ $qualifiedForCount - 1 }} more</span>
                                @endif
                            @else
                                <span class="rpa-none-badge">None yet</span>
                            @endif
                        </td>
                        <td>
                            @if($nextAward)
                                @php
                                    $pct = $nextAward->required_pv > 0
                                        ? min(100, round($userTotalPv / $nextAward->required_pv * 100, 1))
                                        : 100;
                                @endphp
                                <div class="rpa-progress-wrap">
                                    <div class="rpa-progress-bar">
                                        <div class="rpa-progress-fill" style="width:{{ $pct }}%"></div>
                                    </div>
                                    <span class="rpa-progress-pct">{{ $pct }}%</span>
                                </div>
                                <div class="rpa-progress-label">Next: {{ $nextAward->title }}</div>
                            @else
                                <span class="rpa-complete-badge"><i class="las la-check-circle"></i> All qualified</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6">
                            <div class="rpa-table-empty">
                                <i class="las la-users"></i>
                                <p>No members have accumulated PV yet</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($pvUsers->hasPages())
        <div class="rpa-pagination">{{ paginateLinks($pvUsers) }}</div>
        @endif
    </div>

</div>

{{-- OVERLAY + DRAWER --}}
<div class="rpa-overlay" id="rpaOverlay"></div>
<div class="rpa-drawer" id="rpaDrawer">
    <div class="rpa-drawer-head">
        <span id="rpaDrawerTitle">Add Award</span>
        <button class="rpa-drawer-close" id="rpaDrawerClose"><i class="las la-times"></i></button>
    </div>
    <div class="rpa-drawer-body">
        <form id="rpaForm" method="POST">
            @csrf
            <div class="rpa-form-field">
                <label class="rpa-form-label">Award Title <span class="pf-req">*</span></label>
                <input class="rpa-form-input" id="rpaFieldTitle" name="title" type="text" placeholder="e.g. Silver Star" required maxlength="150">
            </div>
            <div class="rpa-form-field">
                <label class="rpa-form-label">Required PV <span class="pf-req">*</span></label>
                <input class="rpa-form-input" id="rpaFieldPv" name="required_pv" type="number" step="any" placeholder="e.g. 5000" required min="0.01">
                <p class="rpa-form-hint">Cumulative product PV the member must reach</p>
            </div>
            <div class="rpa-form-field">
                <label class="rpa-form-label">Reward Amount (₦) <span class="pf-req">*</span></label>
                <div class="pf-input-prefix">
                    <span class="pf-prefix">₦</span>
                    <input class="rpa-form-input" id="rpaFieldAmount" name="amount" type="number" step="any" placeholder="0.00" required min="0.01" style="padding-left:28px;">
                </div>
            </div>
            <button type="submit" class="rpa-form-submit" id="rpaDrawerSubmit">Save Award</button>
        </form>
    </div>
</div>

<x-confirmation-modal />
@endsection

@push('breadcrumb-plugins')
    <button class="btn btn-outline--primary h-45" id="rpaOpenAdd">
        <i class="las la-plus"></i> Add Award
    </button>
@endpush

@push('script')
<script>
"use strict";
(function($) {

    const addUrl    = "{{ route('admin.repurchase-award.store') }}";
    const updateBase = "{{ url('admin/repurchase-award') }}/";

    function openDrawer(title, action, aTitle, pv, amount) {
        $('#rpaDrawerTitle').text(title);
        $('#rpaForm').attr('action', action);
        $('#rpaFieldTitle').val(aTitle || '');
        $('#rpaFieldPv').val(pv || '');
        $('#rpaFieldAmount').val(amount || '');
        $('#rpaOverlay, #rpaDrawer').addClass('active');
        $('#rpaFieldTitle').focus();
    }

    function closeDrawer() {
        $('#rpaOverlay, #rpaDrawer').removeClass('active');
        $('#rpaForm')[0].reset();
    }

    $('#rpaOpenAdd').on('click', function() {
        openDrawer('Add Award', addUrl, '', '', '');
    });

    window.openEdit = function(id, title, pv, amount) {
        openDrawer('Edit Award', updateBase + id, title, pv, amount);
    };

    $('#rpaDrawerClose, #rpaOverlay').on('click', closeDrawer);

})(jQuery);
</script>
@endpush

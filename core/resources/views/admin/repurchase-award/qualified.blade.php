@extends('admin.layouts.app')

@section('panel')
<div class="rpa-page">

    {{-- Banner --}}
    <div class="rpa-qual-banner">
        <div class="rpa-qual-banner-icon"><i class="las la-medal"></i></div>
        <div class="rpa-qual-banner-info">
            <h2 class="rpa-qual-title">{{ $award->title }}</h2>
            <p class="rpa-qual-subtitle">Members who reached {{ number_format($award->required_pv, 2) }} PV and qualify for the ₦{{ number_format($award->amount, 2) }} award</p>
        </div>
        <div class="rpa-qual-chips">
            <div class="rpa-qual-chip">
                <span class="rpa-qual-chip-label">Required PV</span>
                <span class="rpa-qual-chip-val rpa-qual-chip-pv">{{ number_format($award->required_pv, 2) }}</span>
            </div>
            <div class="rpa-qual-chip">
                <span class="rpa-qual-chip-label">Reward</span>
                <span class="rpa-qual-chip-val rpa-qual-chip-amt">₦{{ number_format($award->amount, 2) }}</span>
            </div>
            <div class="rpa-qual-chip">
                <span class="rpa-qual-chip-label">Qualified</span>
                <span class="rpa-qual-chip-val rpa-qual-chip-count">{{ $pvUsers->total() }}</span>
            </div>
            <div class="rpa-qual-chip">
                <span class="rpa-qual-chip-label">Credited</span>
                <span class="rpa-qual-chip-val rpa-qual-chip-credited">{{ $creditedUserIds->count() }}</span>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="rpa-board-card">
        <div class="table-responsive">
            <table class="table rpa-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Member</th>
                        <th>Username</th>
                        <th class="text-center">Total PV</th>
                        <th class="text-center">Surplus PV</th>
                        <th class="text-center">Reward</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pvUsers as $i => $pv)
                    @php
                        $userId    = optional($pv->user)->id;
                        $isPaid    = isset($creditedUserIds[$userId]);
                    @endphp
                    <tr class="{{ $isPaid ? 'rpa-row-paid' : '' }}">
                        <td class="rpa-rank">
                            <span class="rpa-rank-num">{{ ($pvUsers->currentPage()-1)*$pvUsers->perPage() + $i + 1 }}</span>
                        </td>
                        <td>
                            <div class="rpa-member">
                                <div class="rpa-avatar rpa-avatar--{{ $isPaid ? 'paid' : 'pending' }}">
                                    {{ strtoupper(substr(optional($pv->user)->firstname ?? 'U', 0, 1)) }}{{ strtoupper(substr(optional($pv->user)->lastname ?? '', 0, 1)) }}
                                </div>
                                <div>
                                    <div class="rpa-member-name">{{ optional($pv->user)->fullname ?? '—' }}</div>
                                    <div class="rpa-member-email">{{ optional($pv->user)->email ?? '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="rpa-username">{{ optional($pv->user)->username ?? '—' }}</td>
                        <td class="text-center">
                            <span class="rpa-pv-chip">{{ number_format($pv->total_pv, 2) }} PV</span>
                        </td>
                        <td class="text-center">
                            <span class="rpa-surplus-chip">+{{ number_format($pv->total_pv - $award->required_pv, 2) }}</span>
                        </td>
                        <td class="text-center">
                            <span class="rpa-reward-amount">₦{{ number_format($award->amount, 2) }}</span>
                        </td>
                        <td class="text-center">
                            @if($isPaid)
                                <span class="rpa-paid-badge"><i class="las la-check-circle"></i> Credited</span>
                            @else
                                <span class="rpa-pending-pill">Pending</span>
                            @endif
                        </td>
                        <td class="text-right">
                            @if($isPaid)
                                <span class="rpa-credited-label"><i class="las la-lock"></i> Paid</span>
                            @else
                                <form action="{{ route('admin.repurchase-award.credit', [$award->id, $userId]) }}" method="POST"
                                    onsubmit="return confirm('Credit ₦{{ number_format($award->amount,2) }} to {{ addslashes(optional($pv->user)->fullname ?? '') }} for \'{{ addslashes($award->title) }}\'?')">
                                    @csrf
                                    <button type="submit" class="rpa-credit-btn">
                                        <i class="las la-coins"></i> Credit User
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="rpa-table-empty">
                                <i class="las la-user-times"></i>
                                <p>No members have qualified for this award yet</p>
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
@endsection

@push('breadcrumb-plugins')
    <a class="btn btn-sm btn-outline--dark" href="{{ route('admin.repurchase-award.index') }}">
        <i class="las la-undo"></i> Back to Awards
    </a>
@endpush

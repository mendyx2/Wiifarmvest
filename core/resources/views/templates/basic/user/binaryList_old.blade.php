@extends($activeTemplate . 'layouts.master')
@section('content')

@php
    $offset = ($paginator->currentPage() - 1) * $paginator->perPage();
@endphp

<div class="bx-page">

    {{-- ══ HERO ══ --}}
    <div class="bx-hero">
        <div class="bx-hero-glow1"></div>
        <div class="bx-hero-glow2"></div>
        <div class="bx-hero-body">
            <div class="bx-hero-left">
                <div class="bx-hero-eyebrow">
                    <span class="bx-live-dot"></span>
                    Genealogy Network
                </div>
                <h2 class="bx-hero-title">Binary List</h2>
                <!-- <p class="bx-hero-desc">Every member in your downline tree — searchable.</p> -->
            </div>
            <div class="bx-hero-bubble">
                <span class="bx-bubble-num">{{ number_format($total) }}</span>
                <span class="bx-bubble-lbl">Members</span>
            </div>
        </div>
    </div>

    {{-- ══ SEARCH ══ --}}
    <div class="bx-search-card">
        <form method="GET" action="{{ route('user.binary.list') }}" class="bx-search-form">
            <div class="bx-search-box">
                <i class="las la-search bx-search-ico"></i>
                <input
                    type="text"
                    name="username"
                    class="bx-search-inp"
                    placeholder="Search by username…"
                    value="{{ $searchUsername }}"
                    autocomplete="off"
                    spellcheck="false"
                >
                @if($searchUsername)
                <a href="{{ route('user.binary.list') }}" class="bx-search-x" title="Clear">
                    <i class="las la-times"></i>
                </a>
                @endif
            </div>
            <button type="submit" class="bx-search-btn">
                <i class="las la-search"></i>
                <span>Search</span>
            </button>
        </form>
    </div>

    {{-- ══ NOT IN TREE ══ --}}
    @if($notInTree)
    <div class="bx-state bx-state--miss">
        <div class="bx-state-orb"></div>
        <div class="bx-state-ico"><i class="las la-user-slash"></i></div>
        <h5 class="bx-state-title">Not in your Genealogy Tree</h5>
        <p class="bx-state-msg">
            <strong>{{ $searchUsername }}</strong> is not a member of your downline network.
        </p>
        <a href="{{ route('user.binary.list') }}" class="bx-state-cta bx-state-cta--miss">
            <i class="las la-arrow-left"></i> Back to Full List
        </a>
    </div>

    {{-- ══ EMPTY ══ --}}
    @elseif($paginator->isEmpty())
    <div class="bx-state bx-state--empty">
        <div class="bx-state-orb"></div>
        <div class="bx-state-ico"><i class="las la-sitemap"></i></div>
        <h5 class="bx-state-title">No Members Yet</h5>
        <p class="bx-state-msg">Your binary network is empty. Add your first distributor to start building your tree.</p>
        <a href="{{ route('user.distributor.index') }}" class="bx-state-cta bx-state-cta--empty">
            <i class="las la-user-plus"></i> Add Distributor
        </a>
    </div>

    @else

    {{-- match notice --}}
    @if($searchUsername)
    <div class="bx-match-notice">
        <i class="las la-check-circle"></i>
        Match found in your network for <strong>{{ $searchUsername }}</strong>
    </div>
    @endif

    {{-- ══════════════════════════════════
         DESKTOP TABLE  ≥ md
    ══════════════════════════════════ --}}
    <div class="bx-table-card d-none d-md-block">
        <div class="bx-toolbar">
            <span class="bx-toolbar-count">
                Showing <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
                of <strong>{{ number_format($total) }}</strong> members
            </span>
        </div>
        <div class="bx-table-scroll">
            <table class="bx-table">
                <thead>
                    <tr>
                        <th class="bx-th-c">#</th>
                        <th>Member</th>
                        <th>Level</th>
                        <th>Leg</th>
                        <th>Plan</th>
                        <th>Left PV</th>
                        <th>Right PV</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($paginator->items() as $i => $member)
                @php
                    $rowNum  = $offset + $i + 1;
                    $ci      = ($rowNum - 1) % 8;
                    $initial = strtoupper(substr($member->fullname ?? $member->username, 0, 1));
                    $pvLeft  = $member->_matrix->pv_left  ?? 0;
                    $pvRight = $member->_matrix->pv_right ?? 0;
                    $plan    = $member->_project->title   ?? '—';
                    $leg     = $member->_leg   ?? '—';
                    $level   = $member->_level ?? '—';
                    $isLeft  = strtolower($leg) === 'left';
                @endphp
                <tr class="bx-row {{ $isLeft ? 'bx-row--left' : 'bx-row--right' }}">
                    <td class="bx-td-c">
                        <span class="bx-seq">{{ $rowNum }}</span>
                    </td>
                    <td>
                        <div class="bx-member">
                            <div class="bx-av bx-av-{{ $ci }}">{{ $initial }}</div>
                            <div class="bx-member-text">
                                <span class="bx-fullname">{{ $member->fullname }}</span>
                                <span class="bx-uname"><i class="las la-at"></i>{{ $member->username }}</span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="bx-badge bx-badge--lvl">
                            <i class="las la-layer-group"></i> L{{ $level }}
                        </span>
                    </td>
                    <td>
                        <span class="bx-badge {{ $isLeft ? 'bx-badge--l' : 'bx-badge--r' }}">
                            <i class="las la-{{ $isLeft ? 'arrow-left' : 'arrow-right' }}"></i>
                            {{ $leg }}
                        </span>
                    </td>
                    <td><span class="bx-plan">{{ $plan }}</span></td>
                    <td><span class="bx-pv bx-pv--l">{{ showAmount($pvLeft) }}</span></td>
                    <td><span class="bx-pv bx-pv--r">{{ showAmount($pvRight) }}</span></td>
                    <td>
                        <span class="bx-date">{{ showDateTime($member->created_at, 'd M Y') }}</span>
                        <span class="bx-ago">{{ diffForHumans($member->created_at) }}</span>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($paginator->hasPages())
        <div class="bx-pager">{{ paginateLinks($paginator) }}</div>
        @endif
    </div>

    {{-- ══════════════════════════════════
         MOBILE STACK  < md
    ══════════════════════════════════ --}}
    <div class="bx-stack d-md-none">
        <p class="bx-stack-meta">
            Showing <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
            of <strong>{{ number_format($total) }}</strong>
        </p>

        @foreach($paginator->items() as $i => $member)
        @php
            $rowNum  = $offset + $i + 1;
            $ci      = ($rowNum - 1) % 8;
            $initial = strtoupper(substr($member->fullname ?? $member->username, 0, 1));
            $pvLeft  = $member->_matrix->pv_left  ?? 0;
            $pvRight = $member->_matrix->pv_right ?? 0;
            $plan    = $member->_project->title   ?? '—';
            $leg     = $member->_leg   ?? '—';
            $level   = $member->_level ?? '—';
            $isLeft  = strtolower($leg) === 'left';
        @endphp
        <div class="bx-card {{ $isLeft ? 'bx-card--l' : 'bx-card--r' }}">

            <div class="bx-card-stripe"></div>

            <div class="bx-card-head">
                <div class="bx-card-av bx-av-{{ $ci }}">{{ $initial }}</div>
                <div class="bx-card-id">
                    <div class="bx-card-name">{{ $member->fullname }}</div>
                    <div class="bx-card-handle"><i class="las la-at"></i>{{ $member->username }}</div>
                    <div class="bx-card-tags">
                        <span class="bx-badge bx-badge--lvl">
                            <i class="las la-layer-group"></i> L{{ $level }}
                        </span>
                        <span class="bx-badge {{ $isLeft ? 'bx-badge--l' : 'bx-badge--r' }}">
                            <i class="las la-{{ $isLeft ? 'arrow-left' : 'arrow-right' }}"></i>
                            {{ $leg }}
                        </span>
                        @if($plan !== '—')
                        <span class="bx-badge bx-badge--plan">{{ $plan }}</span>
                        @endif
                    </div>
                </div>
                <span class="bx-card-seq">#{{ $rowNum }}</span>
            </div>

            <div class="bx-card-pv">
                <div class="bx-pv-half">
                    <span class="bx-pv-lbl"><span class="bx-pvdot bx-pvdot--l"></span>Left PV</span>
                    <span class="bx-pv bx-pv--l">{{ showAmount($pvLeft) }}</span>
                </div>
                <div class="bx-pv-sep"></div>
                <div class="bx-pv-half">
                    <span class="bx-pv-lbl"><span class="bx-pvdot bx-pvdot--r"></span>Right PV</span>
                    <span class="bx-pv bx-pv--r">{{ showAmount($pvRight) }}</span>
                </div>
            </div>

            <div class="bx-card-foot">
                <i class="las la-calendar-check"></i>
                {{ showDateTime($member->created_at, 'd M Y') }}
                <span class="bx-card-ago">&middot; {{ diffForHumans($member->created_at) }}</span>
            </div>

        </div>
        @endforeach

        @if($paginator->hasPages())
        <div class="bx-pager bx-pager--mob">{{ paginateLinks($paginator) }}</div>
        @endif
    </div>

    @endif

</div>
@endsection

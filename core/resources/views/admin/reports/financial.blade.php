@extends('admin.layouts.app')
@section('panel')
<div class="fr-page">
    @php
        $standing = $report['standing'];
        $status   = [
            'profit' => ['label' => 'Making money',  'icon' => 'las la-arrow-circle-up',   'bg' => 'success'],
            'loss'   => ['label' => 'Losing money',  'icon' => 'las la-arrow-circle-down', 'bg' => 'danger'],
            'even'   => ['label' => 'Breaking even', 'icon' => 'las la-equals',            'bg' => 'secondary'],
        ][$standing['verdict']];
        $trend    = [
            'up'   => ['label' => 'Growing',   'icon' => 'las la-chart-line',  'bg' => 'success'],
            'down' => ['label' => 'Shrinking', 'icon' => 'las la-chart-area',  'bg' => 'danger'],
            'flat' => ['label' => 'Flat',      'icon' => 'las la-minus',       'bg' => 'secondary'],
        ][$standing['trend']];
        $netChange = !is_null($standing['net_change']) && abs($standing['net_change']) < 1000
            ? ' (' . ($standing['net_change'] > 0 ? '+' : '') . $standing['net_change'] . '%)'
            : '';
    @endphp

    <div class="fr-period">
        <span><i class="las la-calendar-alt"></i> {{ $report['period'] }}</span>
        @unless ($report['is_all_time'])
            <a href="{{ route('admin.report.financial') }}">@lang('Show all time')</a>
        @endunless
    </div>

    {{-- ── At a glance ─────────────────────────────────────────── --}}
    <div class="row gy-4 fr-boxes">
        <div class="col-xxl-4 col-md-6">
            <x-widget style="6" bg="success" icon="las la-arrow-down" title="Total Income"
                :value="showAmount($report['income']['total'])" subheading="Money the company earned" :viewMoreIcon="false" />
        </div>
        <div class="col-xxl-4 col-md-6">
            <x-widget style="6" bg="9" icon="las la-arrow-up" title="Total Outflow"
                :value="showAmount($report['outflow']['total'])" subheading="Paid to members & stockists" :viewMoreIcon="false" />
        </div>
        <div class="col-xxl-4 col-md-12">
            <x-widget style="6" :bg="$status['bg']" :icon="$status['icon']" title="Net Result"
                :value="showAmount($standing['net'])" :subheading="$status['label']" :viewMoreIcon="false" />
        </div>
    </div>

    {{-- ── Income ──────────────────────────────────────────────── --}}
    <div class="db-sec-head">
        <span class="db-sec-head__icon"><i class="las la-arrow-down"></i></span>
        <span class="db-sec-head__label">@lang('Income')</span>
        <span class="db-sec-head__sub">@lang('Money the company earned')</span>
    </div>

    @foreach ($report['income']['groups'] as $group)
        @include('admin.reports.partials.financial_boxes', ['group' => $group, 'bg' => 'success'])
    @endforeach

    <div class="fr-total-box fr-total-box--income">
        <span><i class="las la-arrow-down"></i> @lang('Total Income')</span>
        <strong>{{ showAmount($report['income']['total']) }}</strong>
    </div>

    @include('admin.reports.partials.financial_boxes', [
        'group' => ['label' => 'Cash In — not added to income', 'icon' => 'las la-wallet', 'lines' => $report['cash_in']['lines'], 'total' => $report['cash_in']['total']],
        'bg'    => '1',
        'note'  => __('Members spend this money on subscriptions, shares and products, which are already counted in Income. Adding it again would count the same naira twice.'),
    ])

    {{-- ── Outflow ─────────────────────────────────────────────── --}}
    <div class="db-sec-head">
        <span class="db-sec-head__icon"><i class="las la-arrow-up"></i></span>
        <span class="db-sec-head__label">@lang('Outflow')</span>
        <span class="db-sec-head__sub">@lang('Bonuses, dividends and payouts to members and stockists')</span>
    </div>

    @foreach ($report['outflow']['groups'] as $group)
        @include('admin.reports.partials.financial_boxes', ['group' => $group, 'bg' => '9'])
    @endforeach

    <div class="db-finance-block fr-boxes">
        <div class="db-finance-block__head fr-block-head">
            <span><i class="las la-briefcase"></i> @lang('Operating Expenses')</span>
        </div>
        <div class="row gy-4">
            <div class="col-xxl-4 col-md-6">
                <x-widget style="6" outline="true" bg="secondary" icon="las la-briefcase" title="Salaries, Rent & Logistics"
                    value="Not recorded" subheading="Not tracked in the system yet" :viewMoreIcon="false" />
            </div>
        </div>
    </div>

    <div class="fr-total-box fr-total-box--outflow">
        <span><i class="las la-arrow-up"></i> @lang('Total Outflow')</span>
        <strong>{{ showAmount($report['outflow']['total']) }}</strong>
    </div>

    @include('admin.reports.partials.financial_boxes', [
        'group' => ['label' => 'Cash Out — not added to outflow', 'icon' => 'las la-money-bill-wave', 'lines' => $report['cash_out']['lines'], 'total' => $report['cash_out']['total']],
        'bg'    => '1',
        'note'  => __('Withdrawals pay out bonuses and balances that were already counted in Outflow when they were credited. Loans are lent, not spent, and come back as repayments.'),
    ])

    @if ($report['unclassified'])
        @include('admin.reports.partials.financial_boxes', [
            'group' => [
                'label' => 'Unclassified Credits — not in Income or Outflow',
                'icon'  => 'las la-exclamation-triangle',
                'lines' => array_map(fn ($row) => [
                    'label' => $row['remark'], 'amount' => $row['amount'], 'count' => $row['count'],
                    'note'  => $row['ledger'] . ' ledger · ' . number_format($row['count']) . ' records',
                    'icon'  => 'las la-question-circle', 'link' => route('admin.report.transaction', ['remark' => $row['remark']]),
                ], $report['unclassified']),
            ],
            'bg'    => 'warning',
            'note'  => __('These credits use a ledger remark the report does not know yet. Add each one to config/financial_report.php so it is counted in the right place.'),
        ])
    @endif

    {{-- ── Right now ───────────────────────────────────────────── --}}
    <div class="db-sec-head">
        <span class="db-sec-head__icon"><i class="las la-balance-scale"></i></span>
        <span class="db-sec-head__label">@lang('Right Now')</span>
        <span class="db-sec-head__sub">@lang('Balances as of today, whatever period is selected')</span>
    </div>

    @include('admin.reports.partials.financial_boxes', [
        'group' => ['label' => 'Owed to members & stockists', 'icon' => 'las la-users', 'lines' => $report['owed']['members']['lines'], 'total' => $report['owed']['members']['total']],
        'bg'    => '10',
    ])

    @include('admin.reports.partials.financial_boxes', [
        'group' => ['label' => 'Owed to the company', 'icon' => 'las la-building', 'lines' => $report['owed']['company']['lines'], 'total' => $report['owed']['company']['total']],
        'bg'    => 'success',
    ])

    {{-- ── Company financial standing ──────────────────────────── --}}
    <div class="db-sec-head">
        <span class="db-sec-head__icon"><i class="las la-chart-line"></i></span>
        <span class="db-sec-head__label">@lang('Company Financial Standing')</span>
        <span class="db-sec-head__sub">{{ $report['period'] }}</span>
    </div>

    <div class="db-finance-block fr-boxes fr-standing fr-standing--{{ $standing['verdict'] }}">
        <div class="row gy-4">
            <div class="col-md-6">
                <x-widget style="6" :bg="$status['bg']" :icon="$status['icon']" title="Company Status"
                    :value="$status['label']" subheading="Income minus outflow" :viewMoreIcon="false" />
            </div>
            <div class="col-md-6">
                <x-widget style="6" :bg="$status['bg']" icon="las la-balance-scale" title="Net Result"
                    :value="showAmount($standing['net'])"
                    :subheading="showAmount($standing['income']) . ' − ' . showAmount($standing['outflow'])" :viewMoreIcon="false" />
            </div>
            <div class="col-md-6">
                <x-widget style="6" :bg="$status['bg']" icon="las la-percentage" title="Profit Margin"
                    :value="is_null($standing['margin']) ? '—' : $standing['margin'] . '%'"
                    subheading="Kept from every naira earned" :viewMoreIcon="false" />
            </div>
            <div class="col-md-6">
                <x-widget style="6" :bg="$trend['bg']" :icon="$trend['icon']" title="Trend"
                    :value="$trend['label']"
                    :subheading="showAmount($standing['recent_net']) . ' vs ' . showAmount($standing['previous_net']) . $netChange" :viewMoreIcon="false" />
            </div>
        </div>
        <p class="fr-note">
            @lang('Trend compares the net result for the') {{ __($standing['compare_label']) }}.
            @lang('Net result is before operating expenses, which are not recorded in the system yet. Right now the company owes members and stockists')
            <strong>{{ showAmount($report['owed']['members']['total']) }}</strong>
            @lang('and is owed')
            <strong>{{ showAmount($report['owed']['company']['total']) }}</strong>.
        </p>
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
    <form class="d-flex flex-wrap gap-2" method="GET">
        <x-search-date-field />
    </form>
    <button class="btn btn-outline--primary fr-print" type="button"><i class="las la-print"></i> @lang('Print')</button>
@endpush

@push('script')
    <script>
        (function ($) {
            "use strict";
            $('.fr-print').on('click', () => window.print());
        })(jQuery);
    </script>
@endpush

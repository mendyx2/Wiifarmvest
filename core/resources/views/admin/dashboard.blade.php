@extends('admin.layouts.app')
@section('panel')
<div class="db-wrap">

    {{-- ── Greeting banner ─────────────────────────────── --}}
    <div class="db-greeting">
        <div class="db-greeting__left">
            <p class="db-greeting__tag">Admin Dashboard</p>
            <h2 class="db-greeting__name">@lang('Welcome back'), {{ auth('admin')->user()->name }}</h2>
            <p class="db-greeting__sub">@lang("Here's your financial overview") &mdash; {{ now()->format('l, F j, Y') }}</p>
        </div>
        <div class="db-greeting__right">
            <p class="db-greeting__date"><i class="las la-clock"></i>{{ now()->format('h:i A') }}</p>
            <button class="btn btn-outline--primary btn-sm" data-bs-toggle="modal" data-bs-target="#cronModal">
                <i class="las la-server"></i> @lang('Cron Setup')
            </button>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════
         FINANCIAL GOVERNANCE & BLUEPRINT POOLS (PHASE 4)
    ════════════════════════════════════════════════════ --}}
    <div class="db-sec-head">
        <span class="db-sec-head__icon"><i class="las la-balance-scale"></i></span>
        <span class="db-sec-head__label">@lang('Financial Governance & Blueprint Reserve Pools')</span>
    </div>

    <div class="row gy-4 mb-4">
        <div class="col-xxl-3 col-md-6">
            <div class="card bg--primary text-white h-100 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-primary mb-2 fw-bold">30.00% Reserve</span>
                            <h4 class="text-white mb-1">₦{{ number_format($widget['pools']['company_reserve'] ?? 0, 2) }}</h4>
                            <p class="text-white-50 mb-0 small">@lang('Company Reserve Pool')</p>
                        </div>
                        <div class="rounded-circle bg-white bg-opacity-25 p-3 text-white">
                            <i class="las la-landmark fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card bg--info text-white h-100 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-info mb-2 fw-bold">10.00% Pool</span>
                            <h4 class="text-white mb-1">₦{{ number_format($widget['pools']['executive_pool'] ?? 0, 2) }}</h4>
                            <p class="text-white-50 mb-0 small">@lang('Executive Leadership Pool')</p>
                        </div>
                        <div class="rounded-circle bg-white bg-opacity-25 p-3 text-white">
                            <i class="las la-user-tie fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card bg--warning text-dark h-100 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-dark text-white mb-2 fw-bold">5.00% Pool</span>
                            <h4 class="text-dark mb-1">₦{{ number_format($widget['pools']['operational'] ?? 0, 2) }}</h4>
                            <p class="text-muted mb-0 small">@lang('Operational Expenses Pool')</p>
                        </div>
                        <div class="rounded-circle bg-dark bg-opacity-10 p-3 text-dark">
                            <i class="las la-cogs fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-md-6">
            <div class="card bg--success text-white h-100 shadow-sm" style="border-radius: 12px; overflow: hidden;">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-white text-success mb-2 fw-bold">55.00% Reserve</span>
                            <h4 class="text-white mb-1">₦{{ number_format($widget['pools']['compensation_reserve'] ?? 0, 2) }}</h4>
                            <p class="text-white-50 mb-0 small">@lang('Compensation Reserve Pool')</p>
                        </div>
                        <div class="rounded-circle bg-white bg-opacity-25 p-3 text-white">
                            <i class="las la-shield-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════
         USERS
    ════════════════════════════════════════════════════ --}}
    <div class="db-sec-head">
        <span class="db-sec-head__icon"><i class="las la-users"></i></span>
        <span class="db-sec-head__label">@lang('Users')</span>
    </div>

    <div class="row gy-4">
        <div class="col-xxl-3 col-md-4 col-sm-6">
            <x-widget value="{{ $widget['total_users'] }}" title="Total Users" style="6"
                link="{{ route('admin.users.all') }}" icon="las la-users" bg="primary" outline=false />
        </div>
        <div class="col-xxl-3 col-md-4 col-sm-6">
            <x-widget value="{{ $widget['verified_users'] }}" title="Active Users" style="6"
                link="{{ route('admin.users.active') }}" icon="las la-user-check" bg="success" outline=false />
        </div>
        <div class="col-xxl-3 col-md-4 col-sm-6">
            <x-widget value="{{ $widget['email_unverified_users'] }}" title="Email Unverified Users" style="6"
                link="{{ route('admin.users.email.unverified') }}" icon="lar la-envelope" bg="danger" outline=false />
        </div>
        <div class="col-xxl-3 col-md-4 col-sm-6">
            <x-widget value="{{ $widget['mobile_unverified_users'] }}" title="Mobile Unverified Users" style="6"
                link="{{ route('admin.users.mobile.unverified') }}" icon="las la-comment-slash" bg="warning" outline=false />
        </div>
    </div>

    {{-- ── Pins ─────────────────────────── --}}
    <div class="db-sec-head">
        <span class="db-sec-head__icon"><i class="las la-key"></i></span>
        <span class="db-sec-head__label">@lang('Pins')</span>
    </div>
    <div class="row gy-4">
        <div class="col-xxl-3 col-md-4 col-sm-6">
            <x-widget style="6" link="{{ route('admin.pin.index') }}" title="All Pins"
                icon="las la-key" value="{{ $widget['total_pins'] }}" bg="warning" />
        </div>
        <div class="col-xxl-3 col-md-4 col-sm-6">
            <x-widget style="6" link="{{ route('admin.pin.user') }}" title="User Generated Pins"
                icon="las la-key" value="{{ $widget['total_pins_user'] }}" bg="warning" />
        </div>
        <div class="col-xxl-3 col-md-4 col-sm-6">
            <x-widget style="6" link="{{ route('admin.pin.generate') }}" title="Admin Pins"
                icon="las la-key" value="{{ $widget['total_pins_admin'] }}" bg="warning" />
        </div>
        <div class="col-xxl-3 col-md-4 col-sm-6">
            <x-widget style="6" link="{{ route('admin.pin.used', ['source' => 'admin']) }}" title="Used Pins (Admin)"
                icon="las la-key" value="{{ $widget['total_admin_used_pins'] }}" bg="success" />
        </div>
        <div class="col-xxl-3 col-md-4 col-sm-6">
            <x-widget style="6" link="{{ route('admin.pin.used', ['source' => 'user']) }}" title="Used Pins (User)"
                icon="las la-key" value="{{ $widget['total_user_used_pins'] }}" bg="success" />
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════
         STOCKIST
    ════════════════════════════════════════════════════ --}}
    <div class="db-sec-head">
        <span class="db-sec-head__icon"><i class="las la-store"></i></span>
        <span class="db-sec-head__label">@lang('Stockist')</span>
    </div>

    <div class="row gy-4">
        <div class="col-xxl-3 col-sm-6">
            <x-widget value="{{ $widget['total_stockist'] }}" title="Total Stockists" style="6"
                link="{{ route('admin.stockist.dashboard') }}" icon="las la-users" bg="primary" outline=false />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget value="{{ $widget['active_stockist'] }}" title="Active Stockists" style="6"
                link="{{ route('admin.stockist.dashboard', ['status' => 'active']) }}" icon="las la-user-check" bg="success" outline=false />
        </div>
        <div class="col-xxl-3 col-sm-6">
            <x-widget value="{{ $widget['stockist_pending_order'] }}" title="Pending Stock Orders" style="6"
                link="{{ route('admin.stockist-orders.index', ['status' => 'pending']) }}" icon="lar la-times-circle" bg="danger" outline=false />
        </div>
    </div>

    @if(auth('admin')->user()->hasRole('super-admin|admin'))

    {{-- ── Product Orders ──────────────────────────────── --}}
    <div class="db-sec-head">
        <span class="db-sec-head__icon"><i class="las la-shopping-bag"></i></span>
        <span class="db-sec-head__label">@lang('Product Orders')</span>
    </div>

    <div class="row gy-4">
        <div class="col-xxl-4 col-lg-4 col-md-6 col-sm-6">
            <x-widget value="{{ showAmount($widget['total_order_sales']) }}" title="Product Sales Income" style="6"
                link="{{ route('admin.order.index') }}" icon="las la-money-bill-wave" bg="14" outline="true" />
        </div>
        <div class="col-xxl-4 col-lg-4 col-md-6 col-sm-6">
            <x-widget value="{{ showAmount($widget['total_order_sales_pending']) }}" title="Pending Sales Value" style="6"
                link="{{ route('admin.order.index', ['status' => 'pending']) }}" icon="las la-hourglass-half" bg="10" outline="true" />
        </div>
        <div class="col-xxl-4 col-lg-4 col-md-6 col-sm-6">
            <x-widget value="{{ $widget['total_order'] }}" title="Total Orders" style="7"
                link="{{ route('admin.order.index') }}" icon="las la-shopping-bag" bg="2" />
        </div>
        <div class="col-xxl-4 col-lg-4 col-md-6 col-sm-6">
            <x-widget value="{{ $widget['total_pending_order'] }}" title="Pending Orders" style="7"
                link="{{ route('admin.order.index', ['status' => 'pending']) }}" icon="las la-clock" bg="1" />
        </div>
        <div class="col-xxl-4 col-lg-4 col-md-6 col-sm-6">
            <x-widget value="{{ $widget['total_cancel_order'] }}" title="Cancelled Orders" style="7"
                link="{{ route('admin.order.index', ['status' => 'cancelled']) }}" icon="las la-times-circle" bg="2" />
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════
         DEPOSITS & WITHDRAWALS
    ════════════════════════════════════════════════════ --}}
    <div class="db-sec-head">
        <span class="db-sec-head__icon"><i class="las la-exchange-alt"></i></span>
        <span class="db-sec-head__label">@lang('Cash Flow')</span>
    </div>

    <div class="row gy-4 mt-2">
        <div class="col-xxl-6">
            <div class="card db-flow-card h-100">
                <div class="card-body">
                    <h5 class="card-title">@lang('Deposits')</h5>
                    <div class="widget-card-wrapper">
                        <div class="widget-card bg--success">
                            <a class="widget-card-link" href="{{ route('admin.deposit.list') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon"><i class="fas fa-hand-holding-usd"></i></div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ showAmount($deposit['total_deposit_amount']) }}</h6>
                                    <p class="widget-card-title">@lang('Total Deposited')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow"><i class="las la-angle-right"></i></span>
                        </div>
                        <div class="widget-card bg--warning">
                            <a class="widget-card-link" href="{{ route('admin.deposit.pending') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon"><i class="fas fa-spinner"></i></div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ $deposit['total_deposit_pending'] }}</h6>
                                    <p class="widget-card-title">@lang('Pending Deposits')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow"><i class="las la-angle-right"></i></span>
                        </div>
                        <div class="widget-card bg--danger">
                            <a class="widget-card-link" href="{{ route('admin.deposit.rejected') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon"><i class="fas fa-ban"></i></div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ $deposit['total_deposit_rejected'] }}</h6>
                                    <p class="widget-card-title">@lang('Rejected Deposits')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow"><i class="las la-angle-right"></i></span>
                        </div>
                        <div class="widget-card bg--primary">
                            <a class="widget-card-link" href="{{ route('admin.deposit.list') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon"><i class="fas fa-percentage"></i></div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ showAmount($deposit['total_deposit_charge']) }}</h6>
                                    <p class="widget-card-title">@lang('Deposit Charges')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow"><i class="las la-angle-right"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xxl-6">
            <div class="card db-flow-card h-100">
                <div class="card-body">
                    <h5 class="card-title">@lang('Withdrawals')</h5>
                    <div class="widget-card-wrapper">
                        <div class="widget-card bg--success">
                            <a class="widget-card-link" href="{{ route('admin.withdraw.data.all') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon"><i class="lar la-credit-card"></i></div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ showAmount($withdrawals['total_withdraw_amount']) }}</h6>
                                    <p class="widget-card-title">@lang('Total Withdrawn')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow"><i class="las la-angle-right"></i></span>
                        </div>
                        <div class="widget-card bg--warning">
                            <a class="widget-card-link" href="{{ route('admin.withdraw.data.pending') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon"><i class="fas fa-spinner"></i></div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ $withdrawals['total_withdraw_pending'] }}</h6>
                                    <p class="widget-card-title">@lang('Pending Withdrawals')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow"><i class="las la-angle-right"></i></span>
                        </div>
                        <div class="widget-card bg--success">
                            <a class="widget-card-link" href="{{ route('admin.withdraw.data.all') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon"><i class="lar la-credit-card"></i></div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ showAmount($withdrawals['total_withdraw_pending_sum']) }}</h6>
                                    <p class="widget-card-title">@lang('Pending Amount')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow"><i class="las la-angle-right"></i></span>
                        </div>
                        <div class="widget-card bg--danger">
                            <a class="widget-card-link" href="{{ route('admin.withdraw.data.rejected') }}"></a>
                            <div class="widget-card-left">
                                <div class="widget-card-icon"><i class="las la-times-circle"></i></div>
                                <div class="widget-card-content">
                                    <h6 class="widget-card-amount">{{ $withdrawals['total_withdraw_rejected'] }}</h6>
                                    <p class="widget-card-title">@lang('Rejected Withdrawals')</p>
                                </div>
                            </div>
                            <span class="widget-card-arrow"><i class="las la-angle-right"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════
         CHARTS
    ════════════════════════════════════════════════════ --}}
    <div class="db-sec-head">
        <span class="db-sec-head__icon"><i class="las la-chart-bar"></i></span>
        <span class="db-sec-head__label">@lang('Reports & Analytics')</span>
    </div>

    <div class="row mb-none-30 mt-2">
        <div class="col-xl-6 mb-30">
            <div class="card db-chart-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between flex-wrap align-items-center mb-3">
                        <h5 class="card-title mb-0">@lang('Deposit, Withdraw & Invest')</h5>
                        <div class="cursor-pointer rounded border p-1" id="dwDatePicker">
                            <i class="la la-calendar"></i>&nbsp;<span></span> <i class="la la-caret-down"></i>
                        </div>
                    </div>
                    <div id="dwChartArea"></div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 mb-30">
            <div class="card db-chart-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between flex-wrap align-items-center mb-3">
                        <h5 class="card-title mb-0">@lang('Transaction Report')</h5>
                        <div class="cursor-pointer rounded border p-1" id="trxDatePicker">
                            <i class="la la-calendar"></i>&nbsp;<span></span> <i class="la la-caret-down"></i>
                        </div>
                    </div>
                    <div id="transactionChartArea"></div>
                </div>
            </div>
        </div>
    </div>

    @endif

    {{-- ── Login Analytics ──────────────────────────────── --}}
    <div class="db-sec-head">
        <span class="db-sec-head__icon"><i class="las la-globe"></i></span>
        <span class="db-sec-head__label">@lang('Login Analytics')</span>
        <span class="db-sec-head__sub">@lang('Last 30 days')</span>
    </div>

    <div class="row mb-none-30 mt-2">
        <div class="col-xl-4 col-lg-6 mb-30">
            <div class="card db-analytics-card overflow-hidden">
                <div class="card-body">
                    <h5 class="card-title">@lang('By Browser')</h5>
                    <canvas id="userBrowserChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6 mb-30">
            <div class="card db-analytics-card">
                <div class="card-body">
                    <h5 class="card-title">@lang('By Operating System')</h5>
                    <canvas id="userOsChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6 mb-30">
            <div class="card db-analytics-card">
                <div class="card-body">
                    <h5 class="card-title">@lang('By Country')</h5>
                    <canvas id="userCountryChart"></canvas>
                </div>
            </div>
        </div>
    </div>

</div>{{-- /db-wrap --}}
@endsection

@push('script-lib')
    <script src="{{ asset('assets/admin/js/vendor/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/vendor/chart.js.2.8.0.js') }}"></script>
    <script src="{{ asset('assets/admin/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/charts.js') }}"></script>
@endpush

@push('style-lib')
    <link type="text/css" href="{{ asset('assets/admin/css/daterangepicker.css') }}" rel="stylesheet">
@endpush

@push('script')
<script>
"use strict";

const start = moment().subtract(14, 'days');
const end   = moment();

const dateRangeOptions = {
    startDate: start, endDate: end,
    ranges: {
        'Today':         [moment(), moment()],
        'Yesterday':     [moment().subtract(1,'days'), moment().subtract(1,'days')],
        'Last 7 Days':   [moment().subtract(6,'days'), moment()],
        'Last 15 Days':  [moment().subtract(14,'days'), moment()],
        'Last 30 Days':  [moment().subtract(30,'days'), moment()],
        'This Month':    [moment().startOf('month'), moment().endOf('month')],
        'Last Month':    [moment().subtract(1,'month').startOf('month'), moment().subtract(1,'month').endOf('month')],
        'Last 6 Months': [moment().subtract(6,'months').startOf('month'), moment().endOf('month')],
        'This Year':     [moment().startOf('year'), moment().endOf('year')],
    },
    maxDate: moment()
};

const changeDatePickerText = (el, s, e) => $(el).html(s.format('MMM D, YYYY') + ' – ' + e.format('MMM D, YYYY'));

let dwChart = barChart(
    document.querySelector("#dwChartArea"),
    @json(__(gs('cur_text'))),
    [{ name:'Deposited', data:[] }, { name:'Withdrawn', data:[] }, { name:'Invest', data:[] }],
    []
);

let trxChart = lineChart(
    document.querySelector("#transactionChartArea"),
    [{ name:"Plus Transactions", data:[] }, { name:"Minus Transactions", data:[] }],
    []
);

const depositWithdrawChart = (s, e) => {
    $.get(@json(route('admin.chart.deposit.withdraw')), { start_date: s.format('YYYY-MM-DD'), end_date: e.format('YYYY-MM-DD') }, function(d) {
        if (d) { dwChart.updateSeries(d.data); dwChart.updateOptions({ xaxis:{ categories: d.created_on } }); }
    });
};

const transactionChart = (s, e) => {
    $.get(@json(route('admin.chart.transaction')), { start_date: s.format('YYYY-MM-DD'), end_date: e.format('YYYY-MM-DD') }, function(d) {
        if (d) { trxChart.updateSeries(d.data); trxChart.updateOptions({ xaxis:{ categories: d.created_on } }); }
    });
};

$('#dwDatePicker').daterangepicker(dateRangeOptions, (s,e) => changeDatePickerText('#dwDatePicker span',s,e));
$('#trxDatePicker').daterangepicker(dateRangeOptions, (s,e) => changeDatePickerText('#trxDatePicker span',s,e));
changeDatePickerText('#dwDatePicker span', start, end);
changeDatePickerText('#trxDatePicker span', start, end);
depositWithdrawChart(start, end);
transactionChart(start, end);

$('#dwDatePicker').on('apply.daterangepicker',  (ev, p) => depositWithdrawChart(p.startDate, p.endDate));
$('#trxDatePicker').on('apply.daterangepicker', (ev, p) => transactionChart(p.startDate, p.endDate));

piChart(document.getElementById('userBrowserChart'), @json(@$chart['user_browser_counter']->keys()), @json(@$chart['user_browser_counter']->flatten()));
piChart(document.getElementById('userOsChart'),      @json(@$chart['user_os_counter']->keys()),      @json(@$chart['user_os_counter']->flatten()));
piChart(document.getElementById('userCountryChart'), @json(@$chart['user_country_counter']->keys()), @json(@$chart['user_country_counter']->flatten()));
</script>
@endpush

@push('style')
<style>
.apexcharts-menu { min-width: 120px !important; }
</style>
@endpush

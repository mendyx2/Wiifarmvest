@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="container-fluid px-3 px-lg-5 py-4">
    <!-- Page Header -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="portfolio-title">
                    <i class="fas fa-briefcase me-2"></i>Investment Portfolio
                </h3>
            </div>
            <button type="button" class="btn btn-buy-shares" data-bs-toggle="modal" data-bs-target="#buySharesModal">
                <i class="fas fa-plus-circle me-2"></i>Buy Shares
            </button> 
        </div>
    </div>

    <!-- KPI Cards Section -->
    <div class="row g-3 mb-5">
        <!-- Total Shares Card -->
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card">
                <div class="kpi-card-body">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <p class="kpi-label">Total Shares</p>
                            <h4 class="kpi-value kpi-value-primary">{{ $totalUnits }}</h4>
                        </div>
                        <div class="kpi-icon kpi-icon-primary">
                            <i class="fas fa-share-alt"></i>
                        </div>
                    </div>
                    <small class="kpi-desc">Units held</small>
                </div>
            </div>
        </div>

        <!-- Total Investment Value Card -->
        
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card">
                <div class="kpi-card-body">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <p class="kpi-label">Investment Value</p>
                            <h3 class="kpi-value kpi-value-primary">{{ showAmount($totalInvestmentValue) }}</h3>
                        </div>
                        <div class="kpi-icon kpi-icon-primary">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                    <!-- <small class="kpi-desc">Current value of all investments</small> -->
                </div>
            </div>
        </div>
       

        <!-- Total Dividends Card -->
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card">
                <div class="kpi-card-body">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <p class="kpi-label">Total Dividends</p>
                            <h4 class="kpi-value kpi-value-info">{{ showAmount($totalDividends) }}</h4>
                        </div>
                        <div class="kpi-icon kpi-icon-primary">
                            <i class="fas fa-gift"></i>
                        </div>
                    </div>
                    <small class="kpi-desc">Total earnings from dividends</small>
                </div>
            </div>
        </div>

        <!-- Shares Balance Card -->
        {{--
        <div class="col-lg-3 col-md-6">
            <div class="kpi-card">
                <div class="kpi-card-body">
                    <div class="d-flex align-items-start justify-content-between mb-3">
                        <div>
                            <p class="kpi-label">Total Shares Value</p>
                            <h4 class="kpi-value kpi-value-warning">{{ showAmount(auth()->user()->shares) }}</h4>
                        </div>
                        <div class="kpi-icon kpi-icon-primary">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                    <!-- <small class="kpi-desc">Accumulated shares wallet</small> -->
                </div>
            </div>
        </div>
        --}}
    </div>

    <div class="row g-4">
        <!-- My Investments Section -->
        <div class="col-lg-8">

            <div class="card border-0 shadow-sm card-dividend-summary">
                <div class="card-header border-bottom py-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title-custom">My Investments</h5>
                            <small class="card-subtitle">All your active and inactive investments</small>
                        </div>
                        <span class="badge badge-primary-gradient">{{ $investments->count() }} Active</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($investments->count() > 0)
                        <div class="investment-cards">
                            <div class="row g-3">
                                @foreach($investments as $investment)
                                    <div class="col-12">
                                        <div class="investment-card p-4">
                                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-start">
                                                <div class="investment-card-main">
                                                    <div class="d-flex align-items-center gap-3 mb-3">
                                                        <div class="investment-card-icon bg-primary-light text-primary">
                                                            <i class="fas fa-briefcase"></i>
                                                        </div>
                                                        <div>
                                                            <h6 class="plan-name mb-1">{{ $investment->plan->name ?? 'N/A' }}</h6>
                                                            <p class="plan-date mb-0 text-muted">
                                                                <i class="fas fa-calendar me-1"></i>{{ $investment->created_at->format('M d, Y - h:i') }}
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <div class="investment-card-meta row gx-2 gy-2">
                                                        <div class="col-5 col-sm-5 ">
                                                            <div class="investment-meta-item">
                                                                <span>Units</span>
                                                                <strong>{{ $investment->units }}</strong>
                                                            </div>
                                                        </div>
                                                        <div class="col-6 col-sm-6">
                                                            <div class="investment-meta-item">
                                                                <span>Unit Cost</span>
                                                                <strong>{{ showAmount($investment->unit_cost) }}</strong>
                                                            </div>
                                                        </div>
                                                        <div class="col-12 col-sm-4">
                                                            <div class="investment-meta-item">
                                                                <span>Total Value</span>
                                                                <strong>{{ showAmount($investment->units * $investment->unit_cost) }}</strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="investment-card-actions text-lg-end">
                                                    
                                                    <a href="{{ route('user.investment.details', $investment->id) }}" class="btn btn-sm btn-action">
                                                        <i class="fas fa-arrow-right me-1"></i>View
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p class="empty-state-text">You don't have any investments yet.</p>
                            <a href="{{ route('user.plan.index') }}" class="btn btn-primary-gradient">
                                <i class="fas fa-plus me-2"></i>Start Investing
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Dividend Summary Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm card-dividend-summary">
                <div class="card-header border-bottom py-4">
                    <h5 class="card-title-custom">
                        <i class="fas fa-chart-pie me-2"></i>Dividend Summary
                    </h5>
                </div>
                <div class="card-body">
                    @if($dividendsByPlan->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($dividendsByPlan as $index => $item)
                                @php
                                    $plan = $item['plan'];
                                    $amount = $item['amount'];
                                    $colors = ['primary', 'danger', 'info', 'warning', 'success'];
                                    $colorClass = $colors[$index % count($colors)];
                                @endphp
                                <div class="dividend-item">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="dividend-icon dividend-icon-{{ $colorClass }}">
                                            <i class="fas fa-cubes"></i>
                                        </div>
                                        <div>
                                            <strong class="dividend-plan">{{ $plan }}</strong>
                                            <small class="dividend-label">Dividend Earnings</small>
                                        </div>
                                    </div>
                                    <span class="badge badge-{{ $colorClass }}-light">{{ showAmount($amount) }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="alert alert-dividend-summary">
                            <i class="fas fa-info-circle"></i>
                            <span class="ms-2"><strong>Total earned:</strong> {{ showAmount($totalDividends) }}</span>
                        </div>
                    @else 
                        <div class="empty-state-small">
                            <i class="fas fa-gift"></i>
                            <p>No dividends yet.<br><small>They'll appear as they are distributed.</small></p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Dividend History Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom py-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title-custom">Dividend History</h5>
                            <small class="card-subtitle">All dividend transactions received</small>
                        </div>
                        <span class="badge badge-danger-gradient">{{ $dividendTransactions->total() }} Total</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($dividendTransactions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Date</th>
                                        <th>Reference</th>
                                        <th>Investment</th>
                                        <th class="text-center">Units</th>
                                        <th>Rate/Unit</th>
                                        <th>Amount</th>
                                        <th class="pe-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($dividendTransactions as $transaction)
                                        <tr>
                                            <td class="py-4 ps-3">
                                                <small class="date-value">{{ $transaction->created_at->format('M d, Y') }}</small><br>
                                                <small class="date-time">{{ $transaction->created_at->format('H:i') }}</small>
                                            </td>
                                            <td class="py-4">
                                                <code class="reference-code">{{ $transaction->reference }}</code>
                                            </td>
                                            <td class="py-4">{{ $transaction->rinvestment->plan->name ?? 'N/A' }}</td>
                                            <td class="py-4 text-center">
                                                <span class="badge badge-light">{{ $transaction->units_held }}</span>
                                            </td>
                                            <td class="py-4">{{ showAmount($transaction->amount_per_unit) }}</td>
                                            <td class="py-4">
                                                <span class="amount-success">{{ showAmount($transaction->amount) }}</span>
                                            </td>
                                            <td class="py-4 pe-3">
                                                @if($transaction->status == 'completed')
                                                    <span class="badge badge-success-light">
                                                        <i class="fas fa-check-circle me-1"></i>Completed
                                                    </span>
                                                @elseif($transaction->status == 'pending')
                                                    <span class="badge badge-warning-light">
                                                        <i class="fas fa-hourglass-half me-1"></i>Pending
                                                    </span>
                                                @else
                                                    <span class="badge badge-danger-light">
                                                        <i class="fas fa-times-circle me-1"></i>Failed
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($dividendTransactions->hasPages())
                            <div class="card-footer bg-light">
                                {{ $dividendTransactions->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    @else
                        <div class="empty-state">
                            <i class="fas fa-gift"></i>
                            <p class="empty-state-text">No dividend transactions yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('style')
<style>
    /* ===== PAGE STYLING ===== */
    body { background-color: #f8f9fb; }

    /* ===== TITLES ===== */
    .portfolio-title {
        font-size: 2.0rem;
        font-weight: 700;
        color: #229e3b;
        margin-bottom: 0.5rem;
    }

    .portfolio-title i { color: #229e3b; }

    .portfolio-subtitle {
        color: #6c757d;
        font-size: 0.95rem;
        margin: 0;
    }

    /* ===== KPI CARDS ===== */
    .kpi-card {
        position: relative;
        overflow: hidden;
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        height: 100%;
    }

    .kpi-card:hover {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        transform: translateY(-5px);
    }

    .kpi-card-body {
        position: relative;
        z-index: 1;
        padding: 2rem;
        background: white;
    }

    .kpi-label {
        color: #6c757d;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .kpi-value {
        margin: 0;
        font-weight: 700;
        font-size: 1.5rem;
    }

    .kpi-value-primary { color: #229e3b; }
    .kpi-value-danger { color: #f5576c; }
    .kpi-value-info { color: #4facfe; }
    .kpi-value-warning { color: #fa709a; }

    .kpi-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }

    .kpi-icon-primary { background: linear-gradient(135deg, #229e3b, #229e3b); }
    .kpi-icon-danger { background: linear-gradient(135deg, #f5576c, #f0937b); }
    .kpi-icon-info { background: linear-gradient(135deg, #4facfe, #00f2fe); }
    .kpi-icon-warning { background: linear-gradient(135deg, #fa709a, #fee140); }

    .kpi-desc { color: #6c757d; }

    /* ===== CARD STYLING ===== */
    .card-title-custom {
        margin-bottom: 0.5rem;
        font-weight: 700;
        color: #1a1a2e;
    }

    .card-subtitle {
        color: #6c757d;
        display: block;
    }

    .card {
        border-radius: 12px !important;
        transition: all 0.3s ease;
    }

    .card:hover { box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important; }
    .card-header { padding: 1.5rem; background-color: white; }
    .card-body { padding: 1.5rem; }

    /* ===== BADGES ===== */
    .badge-primary-gradient {
        background: linear-gradient(135deg, #229e3b, #764ba2);
        color: white;
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }

    .badge-danger-gradient {
        background: linear-gradient(135deg, #f5576c, #f0937b);
        color: white;
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
    }

    .badge-success-gradient {
        background: linear-gradient(135deg, #00d4ff, #0099ff);
        color: white;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
    }

    .badge-success-light {
        background: linear-gradient(135deg, #00d08415, #00d08430);
        color: #00d084;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
    }

    .badge-warning-light {
        background: linear-gradient(135deg, #ffc10715, #ffc10730);
        color: #ff9800;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
    }

    .badge-danger-light {
        background: linear-gradient(135deg, #f5576c15, #f5576c30);
        color: #f5576c;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
    }

    .badge-primary-light {
        background: linear-gradient(135deg, #229e3b15, #764ba215);
        color: #229e3b;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
    }

    .badge-info-light {
        background: linear-gradient(135deg, #4facfe15, #4facfe30);
        color: #4facfe;
        padding: 0.5rem 0.75rem;
        border-radius: 6px;
    }

    .badge-light {
        background-color: #f8f9fb !important;
        color: #1a1a2e;
        padding: 0.4rem 0.6rem;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-weight: 600;
    }

    /* ===== TABLE STYLES ===== */
    .table-custom { border: none; }
    .table-custom thead { background-color: #f8f9fb; }
    .table-custom th {
        color: #1a1a2e;
        font-weight: 600;
        border: none;
        padding: 1rem;
    }

    .table-custom tbody tr {
        transition: all 0.3s ease;
        border-bottom: 1px solid #e0e0e0;
    }

    .table-custom tbody tr:hover { background-color: #f8f9fb !important; }

    .investment-cards {
        padding: 1rem;
    }

    .investment-card {
        background: white;
        border-radius: 18px;
        border: 1px solid #e9ecef;
        box-shadow: 0 15px 35px rgba(18, 38, 77, 0.04);
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .investment-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 18px 38px rgba(18, 38, 77, 0.08);
    }

    .investment-card-icon {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .investment-card-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .investment-meta-item {
        background: #f8f9fb;
        border-radius: 14px;
        padding: 1rem;
        min-width: 120px;
    }

    .investment-meta-item span {
        display: block;
        color: #6c757d;
        font-size: 0.85rem;
        margin-bottom: 0.35rem;
    }

    .investment-meta-item strong {
        display: block;
        font-size: 1rem;
        color: #1a1a2e;
    }

    .investment-card-actions {
        min-width: 160px;
    }

    .investment-card-actions .btn-action {
        width: 100%;
    }

    .investment-card-main {
        flex: 1 1 auto;
    }

    .investment-card p.plan-date {
        margin-bottom: 0;
    }

    .investment-card .badge {
        font-size: 0.9rem;
        padding: 0.55rem 0.85rem;
    }

    .bg-primary-light {
        background: rgba(34, 158, 59, .12);
    }

    .text-primary {
        color: #229e3b !important;
    }

    .investment-card-meta .col-12 {
        flex: 1 1 100%;
    }

    @media (max-width: 767.98px) {
        .investment-card {
            padding: 1.25rem;
        }

        .investment-card-actions {
            width: 100%;
        }
    }

    .plan-name {
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #1a1a2e;
    }

    .plan-date { color: #6c757d; }
    .value-bold { color: #1a1a2e; font-weight: 600; }

    .reference-code {
        background-color: #f0f1ff;
        color: #229e3b;
        padding: 0.3rem 0.6rem;
        border-radius: 4px;
        font-size: 0.85rem;
    }

    .date-value { color: #1a1a2e; font-weight: 500; }
    .date-time { color: #6c757d; }
    .amount-success { color: #00d084; font-weight: 700; font-size: 1.05rem; }

    /* ===== BUTTONS ===== */
    .btn-action {
        background: linear-gradient(135deg, #229e3b, #764ba2);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        color: white;
    }

    .btn-primary-gradient {
        background: linear-gradient(135deg, #229e3b, #764ba2);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0.6rem 1.5rem;
    }

    .btn-primary-gradient:hover {
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }

    /* ===== EMPTY STATES ===== */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        background-color: #f8f9fb;
    }

    .empty-state i {
        font-size: 3.5rem;
        color: #6c757d;
        opacity: 0.3;
        display: block;
        margin-bottom: 1rem;
    }

    .empty-state-text { color: #6c757d; margin: 1rem 0; }

    .empty-state-small {
        text-align: center;
        padding: 2rem;
        background-color: #f8f9fb;
        border-radius: 10px;
    }

    .empty-state-small i {
        font-size: 2.5rem;
        color: #6c757d;
        opacity: 0.3;
        display: block;
        margin-bottom: 0.75rem;
    }

    .empty-state-small p { color: #6c757d; margin: 0; }

    /* ===== DIVIDEND SUMMARY ===== */
    .card-dividend-summary {
        overflow: hidden;
        border-radius: 12px;
        position: relative;
    }

    .card-dividend-summary::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #229e3b, #764ba2, #f5576c, #00f2fe);
    }

    .dividend-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e0e0e0;
    }

    .dividend-item:last-child { border-bottom: none; }

    .dividend-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .dividend-icon-primary {
        background: linear-gradient(135deg, #229e3b20, #229e3b40);
        color: #229e3b;
    }

    .dividend-icon-danger {
        background: linear-gradient(135deg, #f5576c20, #f5576c40);
        color: #f5576c;
    }

    .dividend-icon-info {
        background: linear-gradient(135deg, #4facfe20, #4facfe40);
        color: #4facfe;
    }

    .dividend-icon-warning {
        background: linear-gradient(135deg, #fa709a20, #fa709a40);
        color: #fa709a;
    }

    .dividend-icon-success {
        background: linear-gradient(135deg, #00d08420, #00d08440);
        color: #00d084;
    }

    .dividend-plan { display: block; color: #1a1a2e; font-weight: 600; }
    .dividend-label { color: #6c757d; display: block; }

    /* ===== ALERTS ===== */
    .alert-dividend-summary {
        background: linear-gradient(135deg, #229e3b15, #764ba215);
        border: 1px solid #229e3b30;
        border-radius: 10px;
        color: #1a1a2e;
        margin-top: 1rem;
        margin-bottom: 0;
        padding: 1rem;
    }

    .alert-dividend-summary i { color: #229e3b; }

    /* ===== BUY SHARES BUTTON ===== */
    .btn-buy-shares {
        background: linear-gradient(135deg, #229e3b, #1e7e34);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        font-size: 1rem;
        box-shadow: 0 4px 15px rgba(34, 158, 59, 0.3);
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-buy-shares:hover {
        background: linear-gradient(135deg, #1e7e34, #155724);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(34, 158, 59, 0.4);
        color: white;
    }

    .btn-buy-shares:active {
        transform: translateY(0);
        box-shadow: 0 2px 10px rgba(34, 158, 59, 0.3);
    }

    /* ===== MODAL STYLING ===== */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #229e3b, #764ba2);
    }

    .modal-content {
        border-radius: 16px;
        overflow: hidden;
    }

    .modal-header {
        border-radius: 16px 16px 0 0;
    }

    .form-label {
        font-size: 0.95rem;
        margin-bottom: 0.5rem;
    }

    .input-group-text {
        border-radius: 10px 0 0 10px;
    }

    .form-select, .form-control {
        border-radius: 0 10px 10px 0;
        border-left: none;
    }

    .form-select:focus, .form-control:focus {
        box-shadow: 0 0 0 0.2rem rgba(34, 158, 59, 0.25);
        border-color: #229e3b;
    }

    #modalTotalAmount {
        font-size: 1.8rem;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }
</style>
@endpush

<!-- Buy Shares Modal -->
@push('modal')
<div class="modal fade" id="buySharesModal" tabindex="-1" aria-labelledby="buySharesModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
            <!-- Modal Header -->
            <div class="modal-header bg-gradient-primary text-white border-0">
                <h5 class="modal-title fw-bold" id="buySharesModalLabel">
                    <i class="fas fa-coins me-2"></i>Buy Shares
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4">
                <form method="POST" action="{{ route('user.shares.purchase') }}" id="buySharesForm">
                    @csrf

                    <!-- Plan Selection -->
                    <div class="mb-4">
                        <label for="modalPlanSelect" class="form-label fw-semibold text-dark">
                            <i class="fas fa-list-ul me-1"></i>Select Investment Plan
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-chart-line text-primary"></i>
                            </span>
                            <select class="form-select border-start-0 ps-0" id="modalPlanSelect" name="plan_id" required>
                                <option value="">Choose your investment plan...</option>
                                @foreach(\App\Models\Plan::active()->get() as $plan)
                                    <option value="{{ $plan->id }}" data-price="{{ $plan->price }}">
                                        {{ $plan->name }} - {{ showAmount($plan->price) }} per unit
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-text text-muted">
                            <i class="fas fa-info-circle me-1"></i>Choose the plan that best fits your investment goals
                        </div>
                    </div>

                    <!-- Units Input -->
                    <div class="mb-4">
                        <label for="modalUnitsInput" class="form-label fw-semibold text-dark">
                            <i class="fas fa-hashtag me-1"></i>Number of Units
                        </label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-cubes text-success"></i>
                            </span>
                            <input type="number" class="form-control border-start-0 ps-0" id="modalUnitsInput" name="qtys" min="5" value="5" required>
                            <span class="input-group-text bg-light">
                                <small class="text-muted">units</small>
                            </span>
                        </div>
                        <div class="form-text text-muted">
                            <i class="fas fa-exclamation-triangle me-1 text-warning"></i>Minimum purchase: 5 units
                        </div>
                    </div>

                    <!-- Total Calculation -->
                    <div class="mb-4">
                        <div class="card border-0 bg-light">
                            <div class="card-body p-3">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="mb-1 fw-semibold text-dark">
                                            <i class="fas fa-calculator me-2 text-primary"></i>Total Investment Amount
                                        </h6>
                                        {{-- <small class="text-muted">Calculated automatically based on your selection</small> --}}
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <h4 class="mb-0 fw-bold text-success" id="modalTotalAmount">₦0.00</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Balance Check -->
                    <div class="mb-4">
                        <div class="alert alert-info border-0">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-wallet me-3 text-info fs-4"></i>
                                <div>
                                    <strong>Available Balance:</strong>
                                    <span class="fw-bold text-info">{{ showAmount(auth()->user()->balance) }}</span>
                                    <br>
                                    <small class="text-muted">This amount will be deducted from your account</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-3 justify-content-end">
                        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-buy-shares px-4" id="submitBuyShares">
                            <i class="fas fa-shopping-cart me-2"></i>Purchase Shares
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endpush
@push('script')
<script>
(function() {
    const planSelect = document.getElementById('modalPlanSelect');
    const unitsInput = document.getElementById('modalUnitsInput');
    const totalAmount = document.getElementById('modalTotalAmount');
    const buySharesModal = document.getElementById('buySharesModal');
    const buySharesForm = document.getElementById('buySharesForm');
    const submitBtn = document.getElementById('submitBuyShares');

    function formatNgn(value) {
        return new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(value);
    }

    function updateTotalAmount() {
        if (!planSelect || !unitsInput || !totalAmount) return;

        const selectedOption = planSelect.options[planSelect.selectedIndex];
        const price = parseFloat(selectedOption?.getAttribute('data-price') || selectedOption?.dataset?.price || 0) || 0;
        const units = parseInt(unitsInput.value, 10);

        // While typing: only calculate if we have a valid number >= 5, otherwise show 0
        const total = (!Number.isNaN(units) && units >= 5) ? price * units : 0;
        totalAmount.textContent = price > 0 && total > 0 ? formatNgn(total) : '₦0.00';
    }

    function enforceMinUnits() {
        const units = parseInt(unitsInput.value, 10);
        if (Number.isNaN(units) || units < 5) {
            unitsInput.value = 5;
            updateTotalAmount();
        }
    }

    function resetModal() {
        if (!buySharesForm || !totalAmount) return;
        buySharesForm.reset();
        totalAmount.textContent = '₦0.00';
    }

    if (planSelect && unitsInput) {
        planSelect.addEventListener('change', updateTotalAmount);
        unitsInput.addEventListener('input', updateTotalAmount);
        // Enforce minimum only when the user leaves the field
        unitsInput.addEventListener('blur', enforceMinUnits);
    }

    if (buySharesModal) {
        buySharesModal.addEventListener('show.bs.modal', function() {
            if (planSelect) {
                const firstPlan = planSelect.querySelector('option[data-price]');
                if (planSelect.value === '' && firstPlan) {
                    planSelect.value = firstPlan.value;
                }
            }

            if (unitsInput) {
                unitsInput.value = 5;
            }

            updateTotalAmount();
        });

        buySharesModal.addEventListener('hidden.bs.modal', resetModal);
    }

    if (buySharesForm && submitBtn) {
        buySharesForm.addEventListener('submit', function(e) {
            const units = parseInt(unitsInput.value, 10);
            if (Number.isNaN(units) || units < 5) {
                e.preventDefault();
                unitsInput.value = 5;
                unitsInput.focus();
                return;
            }
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            setTimeout(function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 10000);
        });
    }
})();
</script>
@endpush

@endsection

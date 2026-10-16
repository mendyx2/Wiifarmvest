@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="container-fluid px-3 px-lg-5 py-4">
    <!-- Back Button & Header -->
    <div class="mb-5">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h3 class="portfolio-title">
                    <i class="fas fa-briefcase me-2"></i>{{ $investment->plan->name ?? 'Investment' }}
                </h3>
            </div>
            <a href="{{ route('user.investment.portfolio') }}" class="btn btn-back">
            <i class="fas fa-arrow-left me-2"></i>Back to Shares Portfolio
        </a>
        </div>
    </div>
    <div class="row g-4">
        <!-- Left Column: Investment Details & History -->
        <div class="col-lg-8">
            <!-- Investment Information Card -->
            <div class="card border-0 shadow-sm mb-4 card-detail-section">
                <div class="card-body">
                    <h5 class="detail-section-title">Investment Details</h5>

                    <div class="row mb-4">
                        <div class="col-6 col-md-6 mb-4">
                            <label class="detail-label">
                                <i class="fas fa-tag"></i>Shares
                            </label>
                            <h5 class="detail-value">{{ $investment->plan->name ?? 'N/A' }}</h5>
                        </div>
                        <div class="col-6   col-md-5 mb-4">
                            <label class="detail-label">
                                <i class="fas fa-calendar"></i>Purchase Date
                            </label>
                            <h5 class="detail-value">{{ $investment->created_at->format('M d, Y H:i') }}</h5>
                        </div>
                        {{--
                        <div class="col-md-5">
                            <label class="detail-label">
                                <i class="fas fa-info-circle"></i>Status
                            </label>
                            @if($investment->status == 'active')
                                <span class="badge badge-detail-active">
                                    <i class="fas fa-check-circle me-1"></i>Active Investment
                                </span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($investment->status) }}</span>
                            @endif
                        </div>
                        --}}
                    </div>

                    <div class="row mb-4">
                        <div class="col-6 col-md-5 mb-1">
                            <label class="detail-label">
                                <i class="fas fa-cubes"></i>Units Purchased
                            </label>

                            <div class="d-flex align-items-center gap-2">
                                <span class="badge badge-detail-units">{{ $investment->units }}</span>
                                <small class="text-muted">Units</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-5 mb-1">
                            <label class="detail-label">
                                <i class="fas fa-dollar-sign"></i>Unit Cost
                            </label>
                            <h5 class="detail-value">{{ showAmount($investment->unit_cost) }}</h5>
                        </div>
                    </div>

                    <hr class="detail-divider">

                    <div class="row">
                        <div class="col-6">
                            <label class="detail-label">
                                <i class="fas fa-chart-line"></i>Total Investment
                            </label>
                            <h4 class="detail-value-primary">{{ showAmount($investment->unit_cost * $investment->units) }}</h4>
                        </div>
                        
                    </div>
                </div>
            </div>

            <!-- Dividends from This Investment Card -->
            <div class="card border-0 shadow-sm card-detail-section">
                <div class="card-header border-bottom py-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title-custom">Dividends from This Investment</h5>
                            <small class="card-subtitle">All dividend payments received</small>
                        </div>
                        <span class="badge badge-danger-gradient">{{ $dividends->total() }} Total</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($dividends->count() > 0)

                    @foreach($dividends as $dividend)


                        <div class="card border-0 shadow-sm mb-4 card-detail-section">
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-6 col-md-6 mb-4">
                                        <label class="detail-label">
                                            <i class="fas fa-tag"></i>Reference
                                        </label>
                                        <h5 class="detail-value">{{ $dividend->reference }}</h5>
                                    </div>
                                    <div class="col-6   col-md-5 mb-4">
                                        <label class="detail-label">
                                            <i class="fas fa-calendar"></i>Date
                                        </label>
                                        <h5 class="detail-value">{{ $dividend->created_at->format('M d, Y') }}</h5>
                                    </div>
                                   
                                    <div class="col-md-5">
                                        <label class="detail-label">
                                            <i class="fas fa-info-circle"></i>Status
                                        </label>


                                        @if($dividend->status == 'completed')
                                            <span class="badge badge-success-light">
                                                <i class="fas fa-check-circle me-1"></i>Completed
                                            </span>
                                        @elseif($dividend->status == 'pending')
                                            <span class="badge badge-warning-light">
                                                <i class="fas fa-hourglass-half me-1"></i>Pending
                                            </span>
                                        @else
                                            <span class="badge badge-danger-light">
                                                <i class="fas fa-times-circle me-1"></i>Failed
                                            </span>
                                        @endif


                                    </div>
                                   
                                </div>

                                <div class="row mb-4">
                                    <div class="col-6 col-md-5 mb-1">
                                        <label class="detail-label">
                                            <i class="fas fa-cubes"></i>Rate/Unit
                                        </label>

                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge badge-detail-units">{{ $dividend->units_held }}</span>
                                            <small class="text-muted">Units</small>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-5 mb-1">
                                        <label class="detail-label">
                                            <i class="fas fa-dollar-sign"></i>Dividend Amount
                                        </label>
                                        <h5 class="detail-value">{{ showAmount($dividend->amount) }}</h5>
                                    </div>
                                </div>

                            </div>
                        </div>
                    @endforeach
                        @if($dividends->hasPages())
                            <div class="card-footer bg-light">
                                {{ $dividends->links('pagination::bootstrap-4') }}
                            </div>
                        @endif
                    @else
                        <div class="empty-state">
                            <i class="fas fa-gift"></i>
                            <p class="empty-state-text">No dividends received yet for this investment.</p>
                        </div>
                    @endif
                </div>
            </div>

            <form id="paymentForm" action="{{ route('user.plan.payment-receipt.generate-pdf') }}" method="POST">
                @csrf
                <input type="hidden" id="rivest_id" name="rivest_id" value="{{ $investment->id }}">
                <input type="hidden" id="check" name="check" value="1">

                <button type="submit" class="btn btn-sm btn-outline-success mt-2 w-100" data-bs-dismiss="modal">
                    Generate PDF Receipt 
                    <svg width="22px" height="22px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M3 15C3 17.8284 3 19.2426 3.87868 20.1213C4.75736 21 6.17157 21 9 21H15C17.8284 21 19.2426 21 20.1213 20.1213C21 19.2426 21 17.8284 21 15" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 3V16M12 16L16 11.625M12 16L8 11.625" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>

                </button>
            </form>
            <form id="paymentForm" action="{{ route('user.plan.payment-receipt.generate-pdf') }}" method="POST">
                @csrf
                <input type="hidden" id="rivest_id" name="rivest_id" value="{{ $investment->id }}">
                <input type="hidden" id="check" name="check" value="2">

                <button type="submit" class="btn btn-sm btn-outline-secondary mt-2 w-100" data-bs-dismiss="modal">Download Certificate 
                    <svg width="22px" height="22px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M3 15C3 17.8284 3 19.2426 3.87868 20.1213C4.75736 21 6.17157 21 9 21H15C17.8284 21 19.2426 21 20.1213 20.1213C21 19.2426 21 17.8284 21 15" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 3V16M12 16L16 11.625M12 16L8 11.625" stroke="#ffffff" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </form>
        </div>

        <!-- Right Column: Summary Cards -->
        <div class="col-lg-4">
            <!-- Total Dividends Large Card -->
            <div class="card border-0 shadow-sm mb-4 card-dividend-highlight">
                <div class="card-body text-center">
                    <i class="fas fa-gift dividend-highlight-icon"></i>
                    <p class="detail-label-large">Total Dividends Earned</p>
                    <h2 class="dividend-total-amount">{{ showAmount($totalDividends) }}</h2>
                    <small class="highlight-desc">From this investment</small>
                </div>
            </div>

            <!-- Performance Card -->
            <div class="card border-0 shadow-sm mb-4 card-detail-section">
                <div class="card-header border-bottom py-4">
                    <h5 class="card-title-custom">
                        <i class="fas fa-chart-pie me-2"></i>Performance
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="detail-label-row">
                            <span>Investment ROI</span>
                            <strong class="text-success">
                                @php
                                    $roi = (($totalDividends / $investment->five) * 100);
                                @endphp
                                {{ number_format($roi, 2) }}%
                            </strong>
                        </label>
                        <div class="progress-custom">
                            <div class="progress-bar-custom" style="width: {{ min($roi, 100) }}%"></div>
                        </div>
                    </div>

                    <div class="list-group list-group-flush">
                        <div class="detail-list-item">
                            <span>Total Invested</span>
                            <strong>{{ showAmount($investment->five) }}</strong>
                        </div>
                        <div class="detail-list-item">
                            <span>Dividends Earned</span>
                            <strong class="text-success">{{ showAmount($totalDividends) }}</strong>
                        </div>
                        <div class="detail-list-item">
                            <span>Total Return</span>
                            <strong class="text-dark">{{ showAmount($investment->five + $totalDividends) }}</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Dividends Card -->
            <div class="card border-0 shadow-sm card-detail-section">
                <div class="card-header border-bottom py-4">
                    <h5 class="card-title-custom">
                        <i class="fas fa-history me-2"></i>Recent Activity
                    </h5>
                </div>
                <div class="card-body">
                    @if($dividends->count() > 0)
                        <div class="list-group list-group-flush">
                            @foreach($dividends->slice(0, 5) as $dividend)
                                <div class="recent-dividend-item">
                                    <div>
                                        <strong class="recent-date">{{ $dividend->created_at->format('M d, Y') }}</strong>
                                        <small class="recent-ref">{{ $dividend->reference }}</small>
                                        <span class="badge badge-success-light">{{ showAmount($dividend->amount) }}</span>
                                    </div>
                                    
                                </div>
                            @endforeach
                        </div>
                        @if($dividends->count() > 5)
                            <div class="alert alert-more">
                                <i class="fas fa-ellipsis-h me-2"></i>
                                <small><strong>And {{ $dividends->count() - 5 }} more dividends...</strong></small>
                            </div>
                        @endif
                    @else
                        <p class="empty-state-small-text">
                            <i class="fas fa-inbox"></i>
                            No dividend history yet
                        </p>
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

    /* ===== BUTTONS ===== */
    .btn-back {
        background: transparent;
        color: #229e3b;
        border: 2px solid #229e3b;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        text-decoration: none;
        transition: all 0.3s;
    }

    .btn-back:hover {
        background: #229e3b;
        color: white;
    }

    /* ===== TITLES ===== */
    .detail-title {
        font-size: 2.2rem;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 0;
    }

    .detail-subtitle {
        color: #6c757d;
        font-size: 0.95rem;
        margin: 0;
    }

    /* ===== DETAIL SECTIONS ===== */
    .card-detail-section {
        overflow: hidden;
        border-radius: 12px !important;
        position: relative;
    }

    .card-detail-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #229e3b, #229e3b);
    }

    .detail-section-title {
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 1.5rem;
    }

    .detail-label {
        color: #000;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .detail-label i { color: #229e3b; }

    .detail-label-large {
        color: #6c757d;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .detail-label-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
        font-size: 0.9rem;
    }

    .detail-value {
        color: #1a1a2e;
        font-weight: 600;
        margin: 0;
    }

    .detail-value-primary {
        color: #229e3b;
        font-weight: 700;
    }

    .detail-divider {
        border-color: #e0e0e0;
        margin: 2rem 0;
    }

    /* ===== BADGES ===== */
    .badge-detail-units {
        background: linear-gradient(135deg, #229e3b, #764ba2);
        color: white;
        padding: 0.7rem 1rem;
        font-size: 1.1rem;
    }

    .badge-detail-active {
        background: linear-gradient(135deg, #229e3b, #229e3b);
        color: white;
        padding: 0.6rem 1rem;
        border-radius: 8px;
    }

    .badge-danger-gradient {
        background: linear-gradient(135deg, #229e3b, #229e3b);
        color: white;
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
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

    .badge-light {
        background-color: #f8f9fb !important;
        color: #1a1a2e;
        padding: 0.4rem 0.6rem;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-weight: 600;
    }

    /* ===== DIVIDEND HIGHLIGHT CARD ===== */
    .card-dividend-highlight {
        background: linear-gradient(135deg, #f5576c20, #f0937b20);
        border-radius: 12px !important;
    }

    .dividend-highlight-icon {
        font-size: 3rem;
        color: #f5576c;
        opacity: 0.2;
        display: block;
        margin-bottom: 1rem;
    }

    .dividend-total-amount {
        font-size: 2rem;
        font-weight: 700;
        color: #f5576c;
        margin-bottom: 0.5rem;
    }

    .highlight-desc {
        color: #6c757d;
    }

    /* ===== TABLES ===== */
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

    .reference-code {
        background-color: #f0f1ff;
        color: #229e3b;
        padding: 0.3rem 0.6rem;
        border-radius: 4px;
        font-size: 0.85rem;
    }

    .date-value { color: #1a1a2e; font-weight: 500; }
    .amount-success { color: #00d084; font-size: 1.05rem; }

    /* ===== PROGRESS BAR ===== */
    .progress-custom {
        height: 8px;
        border-radius: 10px;
        background-color: #e0e0e0;
        overflow: hidden;
    }

    .progress-bar-custom {
        background: linear-gradient(90deg, #4facfe, #00f2fe);
        height: 100%;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(79, 172, 254, 0.3);
    }

    /* ===== DETAIL LISTS ===== */
    .detail-list-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e0e0e0;
    }

    .detail-list-item:last-child { border-bottom: none; }
    .detail-list-item span { color: #6c757d; }

    /* ===== RECENT DIVIDENDS ===== */
    .recent-dividend-item {
        display: flex;
        justify-content: space-between;
        align-items: start;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e0e0e0;
    }

    .recent-dividend-item:last-child { border-bottom: none; }

    .recent-date {
        display: block;
        color: #1a1a2e;
        font-size: 0.95rem;
        margin-bottom: 0.25rem;
    }

    .recent-ref {
        color: #6c757d;
        display: block;
    }

    /* ===== ALERTS ===== */
    .alert-more {
        background-color: #f0f1ff;
        border: 1px solid #e0e0ff;
        border-radius: 8px;
        color: #229e3b;
        padding: 0.75rem 1rem;
        margin-top: 1rem;
        margin-bottom: 0;
    }

    /* ===== EMPTY STATES ===== */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        background-color: #f8f9fb;
    }

    .empty-state i {
        font-size: 3rem;
        color: #6c757d;
        opacity: 0.3;
        display: block;
        margin-bottom: 1rem;
    }

    .empty-state-text { color: #6c757d; }

    .empty-state-small-text {
        text-align: center;
        color: #6c757d;
        padding: 2rem 0;
        margin: 0;
    }

    .empty-state-small-text i {
        font-size: 1.5rem;
        opacity: 0.3;
        display: block;
        margin-bottom: 0.5rem;
    }

    /* ===== CARDS ===== */
    .card {
        border-radius: 12px !important;
        transition: all 0.3s ease;
    }

    .card:hover { box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important; }
    .card-header { padding: 1.5rem; background-color: white; }
    .card-body { padding: 1.5rem; }

    .card-title-custom {
        margin-bottom: 0.5rem;
        font-weight: 700;
        color: #1a1a2e;
    }

    .card-subtitle {
        color: #6c757d;
        display: block;
    }

    /* ===== TEXT UTILITIES ===== */
    .text-success { color: #00d084; }
    .text-primary { color: #229e3b; }
</style>
@endpush
@endsection

@extends($activeTemplate . 'layouts.master2')

@section('content')
<div class="container-fluid px-3 px-lg-5 py-4 shares-dashboard">
    <div class="mb-5 piggy-hero-card">
        <div class="piggy-hero-content">
            <h1 class="detail-title">Dream Savings in Shares</h1>
            <p class="detail-subtitle">A super-safe piggy dashboard for your investments</p>
        </div>
        <div class="piggy-hero-badge">
            <i class="fas fa-piggy-bank"></i>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm piggy-vest-card">
                <div class="card-body">
                    <p class="small mb-1">Current Balance</p>
                    <strong class="h4">{{ showAmount(auth()->user()->balance) }}</strong>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm piggy-vest-card">
                <div class="card-body">
                    <p class="small mb-1">Weekly Growth</p>
                    <strong class="h4">{{ isset($weeklyGrowth) ? showAmount($weeklyGrowth) : showAmount(0) }}</strong>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm piggy-vest-card">
                <div class="card-body">
                    <p class="small mb-1">Target</p>
                    <strong class="h4">{{ showAmount(50000) }}</strong>
                </div>
            </div>
        </div>
    </div>

    <div id="sharesAlert"></div>

    <div class="row g-4">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm piggy-vest-card shares-purchase-card">
                <div class="card-body">
                    <h5 class="detail-section-title">Buy Shares</h5>
                    <p class="text-muted">Minimum 5 units per purchase, piggyvest style.</p>
                    <form action="{{ route('user.shares.purchase') }}" method="POST" id="sharesPurchaseForm">
                        @csrf
                        <div class="mb-3 piggy-field-group">
                            <label class="form-label">Select Plan</label>
                            <div class="input-group piggy-input-group">
                                <span class="input-group-text"><i class="fas fa-list"></i></span>
                                <select class="form-select piggy-field" name="plan_id" id="planSelect" required>
                                    <option value="">Choose a plan</option>
                                    @foreach($plans as $plan)
                                        <option value="{{ $plan->id }}" data-price="{{ $plan->price }}">{{ $plan->name }} - {{ showAmount($plan->price) }} / unit</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3 piggy-field-group">
                            <label class="form-label">Units to Buy</label>
                            <div class="input-group piggy-input-group">
                                <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                <input type="number" min="5" name="qtys" id="qtysInput" class="form-control piggy-field" value="5" required>
                            </div>
                            <div class="form-text">Minimum 5 units. Total to pay calculated automatically.</div>
                        </div>

                        <div class="mb-3 piggy-field-group">
                            <label class="form-label">Total Payable</label>
                            <div class="p-3 total-payable-card" id="totalPayable">₦0.00</div>
                        </div>

                        <button type="submit" class="btn piggy-vest-btn w-100">Purchase Now</button>
                    </form>

                    <div class="piggy-vest-note mt-3">
                        <strong>Pro tip:</strong> Piggy vest users treat every investment as a step towards financial freedom. Keep your shares growing.
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm card-detail-section mt-4">
                <div class="card-body">
                    <h5 class="detail-section-title">Snapshot</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Units</span>
                        <strong id="totalUnitsText">{{ $totalUnits }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total Value</span>
                        <strong id="totalValueText">{{ showAmount($totalValue) }}</strong>
                    </div>

                    @php $goalTarget = 50000; $goalPercent = min(100, $totalValue / $goalTarget * 100); @endphp
                    <p class="mb-1">Progress to Target ({{ showAmount($goalTarget) }})</p>
                    <div class="progress mb-2" style="height: 16px; border-radius: 12px;">
                        <div id="goalProgressBar" class="progress-bar" role="progressbar" style="width: {{ $goalPercent }}%;" aria-valuenow="{{ $goalPercent }}" aria-valuemin="0" aria-valuemax="100">{{ number_format($goalPercent, 1) }}%</div>
                    </div>
                    <div class="progress-circle mb-3" id="goalProgressCircle" data-percent="{{ $goalPercent }}" aria-label="goal progress"></div>

                    <div class="history-graph mt-3">
                        <h6>Weekly Credit History</h6>
                        <div class="history-bars" id="historyBars">
                            @foreach($historyLabels as $idx => $label)
                                @php $value = $historyData[$idx] ?? 0; $max = max($historyData) ?: 1; $height = ($value / $max) * 100; @endphp
                                <div class="history-bar-item">
                                    <div class="history-bar" style="height: {{ $height }}%" title="{{ $label }}: {{ showAmount($value) }}"></div>
                                    <small class="d-block text-center mt-1">{{ $label }}</small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
    </div>
</div>

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const planSelect = document.getElementById('planSelect');
        const qtysInput = document.getElementById('qtysInput');
        const totalPayable = document.getElementById('totalPayable');
        const sharesForm = document.getElementById('sharesPurchaseForm');
        const sharesAlert = document.getElementById('sharesAlert');

        function formatNgn(value) {
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'NGN' }).format(value);
        }

        function updateTotal() {
            const option = planSelect.options[planSelect.selectedIndex];
            const price = parseFloat(option?.dataset?.price || 0);
            const qty = Number(qtysInput.value || 0);

            if (!price || !qty || qty < 5) {
                totalPayable.innerText = '₦0.00';
                return;
            }

            const total = price * qty;
            totalPayable.innerText = formatNgn(total);
        }

        function showAlert(type, message) {
            const color = type === 'success' ? 'alert-success' : 'alert-danger';
            sharesAlert.innerHTML = `<div class="alert ${color} alert-dismissible" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>`;
        }

        const totalUnitsText = document.getElementById('totalUnitsText');
        const totalValueText = document.getElementById('totalValueText');
        const activeCountBadge = document.getElementById('activeCountBadge');
        const goalProgressBar = document.getElementById('goalProgressBar');
        const historyBars = document.getElementById('historyBars');
        const portfolioTableBody = document.getElementById('portfolioTableBody');

        function updateGoalCircle(percent) {
            const gauge = document.getElementById('goalProgressCircle');
            if (!gauge) return;
            gauge.innerHTML = `
                <svg width="120" height="120" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="52" stroke="#e6f1ec" stroke-width="12" fill="none" />
                    <circle cx="60" cy="60" r="52" stroke="#26a65b" stroke-width="12" fill="none" stroke-linecap="round"
                        stroke-dasharray="${Math.PI * 2 * 52}" stroke-dashoffset="${Math.PI * 2 * 52 * (1 - percent / 100)}"
                        transform="rotate(-90 60 60)"></circle>
                    <text x="60" y="66" font-size="22" fill="#154924" text-anchor="middle" font-weight="700">${percent.toFixed(1)}%</text>
                </svg>`;
        }

        function updateProgressUI(percent) {
            goalProgressBar.style.width = `${percent}%`;
            goalProgressBar.setAttribute('aria-valuenow', percent);
            goalProgressBar.innerText = `${percent.toFixed(1)}%`;
            updateGoalCircle(percent);
        }

        function pushHistory(day, value) {
            // optional: append new update point, keep existing data static
            const max = Math.max(...Array.from(historyBars.querySelectorAll('.history-bar')).map(el => Number(el.dataset.value) || 0), value);
            const items = historyBars.querySelectorAll('.history-bar-item');
            if (!items.length || !day) return;

            const lastBar = items[items.length - 1].querySelector('.history-bar');
            if (lastBar) {
                lastBar.style.height = `${(value / max) * 100}%`;
                lastBar.dataset.value = value;
                lastBar.title = `${day}: ${value}`;
            }
        }

        planSelect.addEventListener('change', updateTotal);
        qtysInput.addEventListener('input', updateTotal);

        sharesForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(sharesForm);
            const submitBtn = sharesForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Processing...';

            fetch(sharesForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                },
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        showAlert('success', data.message);

                        // update header stats
                        totalUnitsText.innerText = data.totalUnits;
                        totalValueText.innerText = data.totalValue;
                        activeCountBadge.innerText = `${Number(activeCountBadge.innerText.split(' ')[0]) + 1} active`;

                        updateProgressUI(data.goalPercent ?? 0);

                        // append new portfolio row
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td class="py-4 ps-3">${data.newInvestment.plan_name}</td>
                            <td class="py-4 text-center"><span class="badge badge-primary-gradient">${data.newInvestment.units}</span></td>
                            <td class="py-4">${data.newInvestment.unit_cost}</td>
                            <td class="py-4 value-bold">${data.newInvestment.total}</td>
                            <td class="py-4"><span class="badge badge-success-light">${data.newInvestment.status}</span></td>
                            <td class="py-4 pe-3 text-end"><a href="${data.newInvestment.details_url}" class="btn btn-sm btn-action">Details</a></td>
                        `;
                        portfolioTableBody.insertAdjacentElement('afterbegin', row);

                        // adjust table if empty state
                        const emptyState = document.querySelector('.empty-state');
                        if (emptyState) emptyState.remove();

                        // update target progress, and optional history
                        if (data.updatedHistory) {
                            pushHistory(data.updatedHistory.day, data.updatedHistory.value);
                        }

                    } else {
                        showAlert('error', data.message || 'Could not complete purchase.');
                    }
                })
                .catch(err => {
                    showAlert('error', 'An error occurred. Please try again.');
                    console.error(err);
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Purchase Now';
                });
        });

        updateTotal();
        const initialGoalPercent = Number(goalProgressBar.getAttribute('aria-valuenow')) || 0;
        updateGoalCircle(initialGoalPercent);
    });
</script>
@endpush


@push('style')
<style>
    .shares-dashboard {
        background: linear-gradient(135deg, #e8f9f1, #fdfdfa);
        min-height: calc(100vh - 120px);
    }
    .piggy-hero-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: 28px;
        padding: 2rem;
        margin-bottom: 1.5rem;
        background: linear-gradient(140deg, #50bfa7, #2a9d6b);
        color: #ffffff;
        box-shadow: 0 25px 40px rgba(0, 0, 0, 0.12);
    }
    .piggy-hero-card .detail-title,
    .piggy-hero-card .detail-subtitle {
        color: #f5fdfb;
    }
    .piggy-hero-card .detail-title {
        font-size: 2.4rem;
        font-weight: 800;
    }
    .piggy-hero-card .piggy-hero-badge {
        width: 95px;
        height: 95px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.2);
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.2);
    }
    .piggy-hero-card .piggy-hero-badge i {
        font-size: 2.3rem;
    }
    .shares-dashboard .detail-title { font-size: 1.9rem; color: #0f4e3c; font-weight: 700; }
    .shares-dashboard .detail-subtitle { color: #336758; font-size: 1rem; }
    .card-title-custom { font-weight: 700; color: #17472f; }
    .card-subtitle { color: #6d7a70; }
    .detail-section-title { font-size: 1.1rem; font-weight: 700; color: #13472c; }
    .detail-label { color: #586b5f; font-weight: 600; }
    .detail-value { color: #0b3e20; font-weight: 600; }
    .detail-divider { border-top: 1px solid #e5f1e8; }
    .badge-primary-gradient { background: linear-gradient(135deg, #229e3b, #764ba2); }
    .badge-success-light { background: linear-gradient(135deg, #00d08430, #00d08460); color: #00783b; }
    .table-custom thead { background-color: #f3faf5; }
    .table-custom tbody tr:hover { background-color: #eef7ee !important; }
    .value-bold { font-weight: 700; color: #1a381f; }
</style>
@endpush
@endsection
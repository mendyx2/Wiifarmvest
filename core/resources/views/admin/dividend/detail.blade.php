@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-12">
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5>@lang('Batch #') {{ $batch->id }}</h5>
                        <p class="text-muted mb-1">@lang('Created by') {{ $batch->creator?->name ?? 'N/A' }}</p>
                        <p class="text-muted">{{ showDateTime($batch->created_at) }}</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="stat-item mb-3">
                            <p class="text-muted mb-1">@lang('Status')</p>
                            @php echo $batch->statusBadge @endphp
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <p class="text-muted small">@lang('Scope')</p>
                            <h6>{{ $batch->scope_display }}</h6>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <p class="text-muted small">@lang('Affected Users')</p>
                            <h6>{{ $batch->total_users }}</h6>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <p class="text-muted small">@lang('Total Units')</p>
                            <h6>{{ $batch->total_units }}</h6>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <p class="text-muted small">@lang('Total Amount')</p>
                            <h6 class="text-primary">₦{{ number_format($batch->total_amount, 2) }}</h6>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <p class="text-muted small">@lang('Processed')</p>
                            <h6 class="text-success">{{ $batch->processed_count }}</h6>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <p class="text-muted small">@lang('Failed')</p>
                            <h6 class="text-danger">{{ $batch->failed_count }}</h6>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <p class="text-muted small">@lang('Rate Per Unit')</p>
                            <h6>₦{{ number_format($batch->amount_per_unit, 2) }}</h6>
                        </div>
                    </div>
                    <div class="col-md-3">
                        @if($batch->canCancel())
                        <form action="{{ route('admin.dividend.cancel', $batch->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="la la-trash"></i> @lang('Cancel Batch')
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                @if($batch->notes)
                <hr>
                <div>
                    <p class="text-muted small mb-1">@lang('Notes')</p>
                    <p>{{ $batch->notes }}</p>
                </div>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="card-title mb-0">@lang('Transactions')</h5>
                    <div class="d-flex align-items-center gap-2">
                        <input id="transactionSearch" type="text" class="form-control form-control-sm flex-grow-1" placeholder="@lang('Search by username')" autocomplete="off">
                        <button id="transactionSearchBtn" class="btn btn-sm btn--primary flex-grow-1">@lang('Search')</button>
                        <button id="transactionSearchClear" class="btn btn-sm btn--warning flex-grow-1">@lang('Clear')</button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive--sm table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                            <tr>
                                <th>@lang('Reference')</th>
                                <th>@lang('User')</th>
                                <th>@lang('Investment')</th>
                                <th>@lang('Units')</th>
                                <th>@lang('Rate/Unit')</th>
                                <th>@lang('Amount')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Date')</th>
                            </tr>
                        </thead>
                        <tbody id="transactionTableBody">
                            @include('admin.dividend.partials.transaction-rows', ['transactions' => $transactions])
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($transactions->hasPages())
            <div class="card-footer py-4" id="transactionPaginationContainer">
                {{ paginateLinks($transactions) }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
<a href="{{ route('admin.dividend.index') }}" class="btn btn-sm btn-outline--primary    ">
    <i class="la la-arrow-left"></i> @lang('Back')
</a>
@endpush

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('transactionSearch');
        const searchBtn = document.getElementById('transactionSearchBtn');
        const clearBtn = document.getElementById('transactionSearchClear');
        const tableBody = document.getElementById('transactionTableBody');
        const paginationContainer = document.getElementById('transactionPaginationContainer');
        const batchId = "{{ $batch->id }}";

        function searchTransactions(page = 1) {
            const search = searchInput.value.trim();
            const url = `{{ route('admin.dividend.detail.search', $batch->id) }}?search=${encodeURIComponent(search)}&page=${page}`;

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                tableBody.innerHTML = data.html;
                if (paginationContainer && data.pagination) {
                    paginationContainer.innerHTML = data.pagination;
                    attachPaginationLinks();
                } else if (paginationContainer) {
                    paginationContainer.innerHTML = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                tableBody.innerHTML = '<tr><td class="text-danger text-center" colspan="8">@lang('Error loading transactions')</td></tr>';
            });
        }

        function attachPaginationLinks() {
            const paginationLinks = document.querySelectorAll('#transactionPaginationContainer a');
            paginationLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const url = new URL(this.href);
                    const page = url.searchParams.get('page');
                    searchTransactions(page);
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });
        }

        searchBtn.addEventListener('click', () => searchTransactions(1));

        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            searchTransactions(1);
        });

        // Allow Enter key to search
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchTransactions(1);
            }
        });

        // Attach pagination links on page load
        attachPaginationLinks();
    });
</script>
@endpush

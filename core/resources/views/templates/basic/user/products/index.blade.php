@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="shop-page">

    {{-- ── Page Header ── --}}
    <div class="shop-header-card">
        <div class="shop-header-left">
            <div class="shop-header-icon"><i class="las la-store"></i></div>
            <div>
                <h6 class="shop-header-title">Shop</h6>
                <p class="shop-header-sub" id="resultsCount">Showing {{ $products->count() }} product(s)</p>
            </div>
        </div>
        @if($selectedState)
        <div class="shop-state-pill" id="stateIndicator">
            <i class="las la-map-marker-alt"></i>
            <span>Prices for <strong>{{ $states->find($selectedState)->name }}</strong></span>
        </div>
        @else
        <div class="shop-state-pill" id="stateIndicator" style="display:none;">
            <i class="las la-map-marker-alt"></i>
            <span>Prices for <strong></strong></span>
        </div>
        @endif
    </div>

    {{-- ── Search & Filter Bar ── --}}
    <div class="shop-filter-bar">
        <div class="shop-search-wrap">
            <i class="las la-search"></i>
            <input type="text" id="searchInput" placeholder="Search products by name or description..." autocomplete="off">
            <div class="shop-search-spinner" id="searchSpinner" style="display:none;">
                <div class="shop-spinner-dot"></div>
            </div>
        </div>
        <select id="stateSelect" class="shop-state-select">
            <option value="">All States</option>
            @foreach($states as $state)
                <option value="{{ $state->id }}" {{ $selectedState == $state->id ? 'selected' : '' }}>
                    {{ $state->name }}
                </option>
            @endforeach
        </select>
        <button id="clearFilters" class="shop-clear-btn">
            <i class="las la-times"></i> Clear
        </button>
    </div>

    {{-- ── Results Title ── --}}
    <div class="shop-results-row">
        <h6 class="shop-results-title" id="resultsTitle">
            @if($selectedState)
                Products in {{ $states->find($selectedState)->name }}
            @else
                All Products
            @endif
        </h6>
    </div>

    {{-- ── Products Grid ── --}}
    <div id="productsGrid" class="shop-grid">
        @include($activeTemplate.'user.products.products_grid', ['products' => $products])
    </div>

    {{-- ── Loading ── --}}
    <div id="loadingSpinner" class="shop-loading" style="display:none;">
        <div class="shop-loading-ring"></div>
        <p>Finding products...</p>
    </div>

    {{-- ── Empty State ── --}}
    <div id="noProducts" class="shop-empty" style="display:none;">
        <div class="shop-empty-icon"><i class="las la-box-open"></i></div>
        <h6>No products found</h6>
        <p>Try a different search term or select another state</p>
        <button id="resetSearch" class="shop-reset-btn">
            <i class="las la-redo"></i> Show All Products
        </button>
    </div>

</div>
@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput    = document.getElementById('searchInput');
    const stateSelect    = document.getElementById('stateSelect');
    const productsGrid   = document.getElementById('productsGrid');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const searchSpinner  = document.getElementById('searchSpinner');
    const noProducts     = document.getElementById('noProducts');
    const resultsTitle   = document.getElementById('resultsTitle');
    const resultsCount   = document.getElementById('resultsCount');
    const stateIndicator = document.getElementById('stateIndicator');
    const clearFilters   = document.getElementById('clearFilters');
    const resetSearch    = document.getElementById('resetSearch');

    let searchTimeout;

    function performSearch() {
        const query   = searchInput.value;
        const stateId = stateSelect.value;

        showLoading();
        clearTimeout(searchTimeout);

        searchTimeout = setTimeout(() => {
            fetch('{{ route("user.products.search") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ query: query, state: stateId })
            })
            .then(r => r.json())
            .then(data => { updateUI(data); hideLoading(); })
            .catch(() => hideLoading());
        }, 450);
    }

    function updateUI(data) {
        productsGrid.innerHTML = data.products_html;

        if (data.products_count === 0) {
            productsGrid.style.display = 'none';
            noProducts.style.display = 'block';
        } else {
            productsGrid.style.display = 'grid';
            noProducts.style.display = 'none';
        }

        resultsCount.textContent = 'Showing ' + data.products_count + ' product(s)';

        if (data.selected_state_name) {
            resultsTitle.textContent = 'Products in ' + data.selected_state_name;
            stateIndicator.querySelector('strong').textContent = data.selected_state_name;
            stateIndicator.style.display = 'flex';
        } else {
            resultsTitle.textContent = 'All Products';
            stateIndicator.style.display = 'none';
        }
    }

    function showLoading() {
        loadingSpinner.style.display = 'flex';
        searchSpinner.style.display = 'block';
        productsGrid.style.opacity = '0.5';
    }

    function hideLoading() {
        loadingSpinner.style.display = 'none';
        searchSpinner.style.display = 'none';
        productsGrid.style.opacity = '1';
    }

    function clearAll() {
        searchInput.value = '';
        stateSelect.value = '';
        performSearch();
    }

    searchInput.addEventListener('input', performSearch);
    stateSelect.addEventListener('change', performSearch);
    clearFilters.addEventListener('click', clearAll);
    resetSearch.addEventListener('click', clearAll);
});
</script>
@endpush

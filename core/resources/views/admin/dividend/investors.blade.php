@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="card-title mb-0">@lang('Investors - Shares & Dividends')</h5>
                    <div class="d-flex align-items-center gap-2 ">
                        <input id="investorSearch" type="text" class="form-control form-control-sm flex-grow-1" placeholder="@lang('Search by username, email, name')" autocomplete="off">
                        <button id="investorSearchBtn" class="btn btn-sm btn--primary flex-grow-1">@lang('Search')</button>
                        <button id="investorSearchClear" class="btn btn-sm btn--warning flex-grow-1">@lang('Clear')</button>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive--sm table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                            <tr>
                                <th>@lang('S/N')</th>
                                <th>@lang('User')</th>
                                <th>@lang('Email')</th>
                                <th>@lang('Total Units')</th>
                                <th>@lang('Investments')</th>
                                <th>@lang('Share Credits')</th>
                                <th>@lang('Dividends Paid')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody id="investorTableBody">
                            @include('admin.dividend.partials.investor-rows', ['investors' => $investors])
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($investors->hasPages())
            <div class="card-footer py-4">
                {{ paginateLinks($investors) }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
<a href="{{ route('admin.dividend.index') }}" class="btn btn-sm btn-outline-primary">
    <i class="la la-arrow-left"></i> @lang('Back')
</a>
@endpush

@push('script')
<script>
    (function($){
        const $search = $('#investorSearch');
        const $tableBody = $('#investorTableBody');
        const $paginator = $('.card-footer.py-4');

        function refreshInvestors(search) {
            $.ajax({
                url: '{{ route('admin.dividend.investors') }}',
                data: { search: search },
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(response) {
                    $tableBody.html(response.html);
                    if (response.hasPages && response.pagination) {
                        $paginator.html(response.pagination);
                    } else {
                        $paginator.html('');
                    }
                },
                error: function() {
                    $tableBody.html('<tr><td colspan="100%" class="text-center text-danger">@lang("Unable to load investors. Please try again.")</td></tr>');
                    $paginator.html('');
                }
            });
        }

        let timeout = null;
        $search.on('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                refreshInvestors($search.val().trim());
            }, 300);
        });

        $('#investorSearchBtn').on('click', function(e) {
            e.preventDefault();
            clearTimeout(timeout);
            refreshInvestors($search.val().trim());
        });

        $('#investorSearchClear').on('click', function(e) {
            e.preventDefault();
            $search.val('');
            clearTimeout(timeout);
            refreshInvestors('');
        });
    })(jQuery);
</script>
@endpush

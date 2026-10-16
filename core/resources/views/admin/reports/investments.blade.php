@extends('admin.layouts.app')

@section('panel')
    <div class="row mb-3 g-3">
        <div class="col-md-6 col-xl-3">
            <x-widget style="2" value="{{ number_format($totalUnits) }}" title="Total Units Invested" icon="las la-layer-group" color="primary" :overlay_icon="1" />
        </div>
        <div class="col-md-6 col-xl-3">
            <x-widget style="2" value="{{ showAmount($totalAmount) }}" title="Total Amount Invested" icon="las la-coins" color="success" :overlay_icon="1" />
        </div>
        <div class="col-md-6 col-xl-3">
            <x-widget style="2" value="{{ number_format($investments->total()) }}" title="Total Investments" icon="las la-receipt" color="warning" :overlay_icon="1" />
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="show-filter mb-3 text-end">
                <button class="btn btn-outline--primary showFilterBtn btn-sm" type="button"><i class="las la-filter"></i> @lang('Filter')</button>
            </div>
            <div class="card responsive-filter-card mb-4">
                <div class="card-body">
                    <form>
                        <div class="d-flex flex-wrap gap-4">
                            <div class="flex-grow-1">
                                <label>@lang('TRX/Username/Plan')</label>
                                <input class="form-control" name="search" type="search" value="{{ request()->search }}" placeholder="@lang('Search')">
                            </div>
                            <div class="flex-grow-1">
                                <label>@lang('Date')</label>
                                <input class="datepicker-here form-control bg--white date-range pe-2" name="date" type="search"
                                    value="{{ request()->date }}" placeholder="@lang('Start Date - End Date')" autocomplete="off">
                            </div>
                            <div class="flex-grow-1 align-self-end">
                                <button class="btn btn--primary w-100 h-45"><i class="fas fa-filter"></i> @lang('Filter')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--lg table-responsive">
                        <table class="table--light style--two table">
                            <thead>
                                <tr>
                                    <th>@lang('User')</th>
                                    <th>@lang('Plan')</th>
                                    <th>@lang('Units')</th>
                                    <th>@lang('Unit Price')</th>
                                    <th>@lang('Total')</th>
                                    <th>@lang('TRX')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Date')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($investments as $inv)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $inv->user?->fullname }}</span>
                                            <br>
                                            <span class="small"> <a
                                                    href="{{ appendQuery('search', $inv->user?->username) }}"><span>@</span>{{ $inv->user?->username }}</a>
                                            </span>
                                        </td>

                                        <td>{{ $inv->plan?->name ?? '—' }}</td>

                                        <td>{{ number_format($inv->units) }}</td>

                                        <td class="budget">{{ showAmount($inv->unit_cost) }}</td>

                                        <td class="budget">
                                            <span class="fw-bold text--success">{{ showAmount($inv->five) }}</span>
                                        </td>

                                        <td><strong>{{ $inv->trx }}</strong></td>

                                        <td>{!! $inv->statusBadge !!}</td>

                                        <td>
                                            {{ showDateTime($inv->created_at) }}<br>{{ diffForHumans($inv->created_at) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __('No investments found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($investments->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($investments) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('script-lib')
    <script src="{{ asset('assets/admin/js/moment.min.js') }}"></script>
    <script src="{{ asset('assets/admin/js/daterangepicker.min.js') }}"></script>
@endpush

@push('style-lib')
    <link type="text/css" href="{{ asset('assets/admin/css/daterangepicker.css') }}" rel="stylesheet">
@endpush

@push('script')
    <script>
        (function($) {
            "use strict"

            const datePicker = $('.date-range').daterangepicker({
                autoUpdateInput: false,
                locale: {
                    cancelLabel: 'Clear'
                },
                showDropdowns: true,
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 15 Days': [moment().subtract(14, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(30, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'Last 6 Months': [moment().subtract(6, 'months').startOf('month'), moment().endOf('month')],
                    'This Year': [moment().startOf('year'), moment().endOf('year')],
                },
                maxDate: moment()
            });
            const changeDatePickerText = (event, startDate, endDate) => {
                $(event.target).val(startDate.format('MMMM DD, YYYY') + ' - ' + endDate.format('MMMM DD, YYYY'));
            }

            $('.date-range').on('apply.daterangepicker', (event, picker) => changeDatePickerText(event, picker.startDate, picker.endDate));

            if ($('.date-range').val()) {
                let dateRange = $('.date-range').val().split(' - ');
                $('.date-range').data('daterangepicker').setStartDate(new Date(dateRange[0]));
                $('.date-range').data('daterangepicker').setEndDate(new Date(dateRange[1]));
            }

        })(jQuery)
    </script>
@endpush

@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-12">
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5> <strong>{{ $user->fullname }}</strong></h5>
                        <p class="text-muted mb-1">@ {{ $user->username }}</p>
                        <p class="text-muted">{{ $user->email }}</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="stat-item">
                            <p class="text-muted mb-1">@lang('Total Dividends Received')</p>
                            <h4 class="text-success">₦{{ number_format($totalDividends, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">@lang('Dividend Transactions')</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive--sm table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                            <tr>
                                <th>@lang('Reference')</th>
                                <th>@lang('Investment')</th>
                                <th>@lang('Plan')</th>
                                <th>@lang('Units Held')</th>
                                <th>@lang('Rate/Unit')</th>
                                <th>@lang('Amount')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Date')</th>
                            </tr>
                        </thead>
                        <tbody> 
                            @forelse($dividends as $dividend)
                            <tr>
                                <td>
                                    <small>{{ $dividend->reference }}</small>
                                </td>
                                <td>
                                    #{{ $dividend->rinvestment_id }}
                                </td>
                                <td>
                                    {{ $dividend->rinvestment?->plan->name ?? 'N/A' }}
                                </td>
                                <td>
                                    {{ $dividend->units_held }}
                                </td>
                                <td>
                                    ₦{{ number_format($dividend->amount_per_unit, 2) }}
                                </td>
                                <td>
                                    <strong>₦{{ number_format($dividend->amount, 2) }}</strong>
                                </td>
                                <td>
                                    @php echo $dividend->statusBadge @endphp
                                </td>
                                <td>
                                    <small>{{ showDateTime($dividend->created_at) }}</small>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">@lang('No dividends for this user')</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($dividends->hasPages())
            <div class="card-footer py-4">
                {{ paginateLinks($dividends) }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
<a href="{{ route('admin.dividend.investors') }}" class="btn  btn-lg btn-outline--primary">
    <i class="la la-arrow-left"></i> @lang('Back to Investors')
</a>
@endpush

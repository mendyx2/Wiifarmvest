@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">@lang('Create New Dividend Payment')</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.dividend.store') }}" method="POST">
                    @csrf

                    <div class="form-group mb-3">
                        <label class="form-label">@lang('Payment Scope') <span class="text-danger">*</span></label>
                        <select name="scope" class="form-control" id="scopeSelect" required>
                            <option value="">@lang('Select Scope')</option>
                            <option value="all-time">@lang('All Time')</option>
                            <option value="date-range">@lang('Date Range')</option>
                        </select>
                        @error('scope')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div id="dateRangeContainer" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">@lang('Start Date') <span class="text-danger">*</span></label>
                                    <input type="date" name="start_date" class="form-control" id="startDate">
                                    @error('start_date')
                                    <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">@lang('End Date') <span class="text-danger">*</span></label>
                                    <input type="date" name="end_date" class="form-control" id="endDate">
                                    @error('end_date')
                                    <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">@lang('Plan (Optional)')</label>
                        <select name="plan_id" class="form-control">
                            <option value="">@lang('All Plans')</option>
                            @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                            @endforeach
                        </select>
                        @error('plan_id')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">@lang('Amount Per Unit') <span class="text-danger">*</span></label>
                        <input type="number" name="amount_per_unit" class="form-control" step="0.01" min="0" placeholder="Enter amount" required>
                        @error('amount_per_unit')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">@lang('Notes (Optional)')</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Add any notes about this dividend payment"></textarea>
                        @error('notes')
                        <span class="text-danger small">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="la la-save"></i> @lang('Create Dividend Payment')
                    </button>
                    <a href="{{ route('admin.dividend.index') }}" class="btn btn-secondary">
                        @lang('Cancel')
                    </a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">@lang('Affected Investors')</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <h6 class="mb-2">@lang('All Active Investors')</h6>
                    @foreach($investors as $investor)
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $investor->fullname }}</strong>
                            <br>
                            <small class="text-muted">@ {{ $investor->username }}</small>
                        </div>
                        <div class="text-end">
                            <small class="d-block"><strong>{{ $investor->rinvestment->sum('units') }}</strong> units</small>
                            <small class="text-muted">{{ $investor->rinvestment->count() }} investments</small>
                        </div>
                    </div>
                    @endforeach


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

<script>
document.getElementById('scopeSelect').addEventListener('change', function() {
    const dateContainer = document.getElementById('dateRangeContainer');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');

    if (this.value === 'date-range') {
        dateContainer.style.display = 'block';
        startDate.required = true;
        endDate.required = true;
    } else {
        dateContainer.style.display = 'none';
        startDate.required = false;
        endDate.required = false;
    }
});
</script>
@endsection

@push('breadcrumb-plugins')
<a href="{{ route('admin.dividend.index') }}" class="btn btn-sm btn-outline--secondary">
    <i class="la la-arrow-left"></i> @lang('Back')
</a>
@endpush

@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-12">
        <div class="row gy-4 mt-2">
        <div class="col-xxl-4 col-lg-4 col-md-6 col-sm-6">
            <x-widget value="{{ showAmount($totalUnits) }}" title="Total units" style="6"
                icon="fa fa-money-bill-wave-alt" bg="14" outline="true" />
        </div><!-- dashboard-w1 end -->

        <div class="col-xxl-4 col-lg-4 col-md-6 col-sm-6">
            <x-widget  value="{{ showAmount($totalDistributed) }}" title="Total amount distributed to shareholders" style="7"
                icon="fas la-cart-arrow-down" bg="1" />
        </div><!-- dashboard-w1 end -->
        <div class="col-xxl-4 col-lg-4 col-md-6 col-sm-6">
            <x-widget value="{{ $totalShareholders }}" title="Users" style="6" l
                icon="las la-user-check" bg="success" outline=false />
        </div>


    </div>

       
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">@lang('Pending Dividend Payments')</h5>
                <a href="{{ route('admin.dividend.create') }}" class="btn btn-sm btn-primary">
                    <i class="la la-plus"></i> @lang('New Dividend Payment')
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive--sm table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                            <tr>
                                <th>@lang('Batch ID')</th>
                                <th>@lang('Created By')</th>
                                <th>@lang('Scope')</th>
                                <th>@lang('Users')</th>
                                <th>@lang('Units')</th>
                                <th>@lang('Amount Per Unit')</th>
                                <th>@lang('Total Amount')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Created')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($batches as $batch)
                            <tr>
                                <td>
                                    <strong>#{{ $batch->id }}</strong>
                                </td>
                                <td>
                                    {{ $batch->creator?->name ?? 'N/A' }}
                                </td>
                                <td>
                                    @if($batch->scope === 'date-range')
                                        <small>{{ $batch->start_date->format('M d, Y') }} - {{ $batch->end_date->format('M d, Y') }}</small>
                                    @else
                                        <span class="badge badge-info">All Time</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $batch->total_users }}</strong>
                                </td>
                                <td>
                                    {{ $batch->total_units }}
                                </td>
                                <td>
                                    ₦{{ number_format($batch->amount_per_unit, 2) }}
                                </td>
                                <td>
                                    <strong>₦{{ number_format($batch->total_amount, 2) }}</strong>
                                </td>
                                <td>
                                    @php echo $batch->statusBadge @endphp
                                </td>
                                <td>
                                    {{ showDateTime($batch->created_at) }}
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline--primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="la la-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.dividend.detail', $batch->id) }}">
                                                    <i class="la la-eye"></i> @lang('View Details')
                                                </a>
                                            </li>
                                            <li>
                                                <form action="{{ route('admin.dividend.cancel', $batch->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure?')">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="la la-trash"></i> @lang('Cancel Batch')
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">@lang('No pending dividend payments')</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($batches->hasPages())
            <div class="card-footer py-4">
                {{ paginateLinks($batches) }}
            </div>
            @endif
        </div>

        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">@lang('Buy Shares')</h5>
                <span class="text-muted small">@lang('Select user, choose plan, and enter share quantity')</span>
            </div>
            <div class="card-body">
                <div id="buyShareAlert" class="alert d-none" role="alert"></div>
                <form id="buyShareForm">
                    @csrf
                    <div class="row gy-3">
                        <div class="col-lg-4">
                            <label class="form-label">@lang('Select User')</label>
                            <input id="shareUserSearch" type="text" class="form-control" list="shareUserList" placeholder="@lang('Type username or name')" autocomplete="off" required>
                            <datalist id="shareUserList">
                                @foreach($users as $user)
                                    <option value="{{ $user->username }} - {{ $user->firstname }} {{ $user->lastname }}">
                                @endforeach
                            </datalist>
                            <input id="shareUserId" type="hidden" name="user_id">
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">@lang('Select Plan')</label>
                            <select id="sharePlan" name="plan_id" class="form-select" required>
                                <option value="">@lang('Choose a share plan')</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" data-price="{{ $plan->price }}">
                                        {{ $plan->name }} - ₦{{ number_format($plan->price, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">@lang('Total Shares')</label>
                            <input id="shareUnits" name="units" type="number" min="1" value="1" class="form-control" required>
                        </div>

                        <div class="col-lg-4">
                            <label class="form-label">@lang('Unit Cost')</label>
                            <input id="shareUnitCost" type="text" class="form-control" readonly value="0.00">
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">@lang('Total Cost')</label>
                            <input id="shareTotalCost" type="text" class="form-control" readonly value="0.00">
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">@lang('User Balance')</label>
                            <input id="shareUserBalance" type="text" class="form-control" readonly value="N/A">
                        </div>

                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn--primary">@lang('Queue Purchase')</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmSharePurchaseModal" tabindex="-1" aria-labelledby="confirmSharePurchaseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmSharePurchaseLabel">@lang('Confirm Purchase')</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('Close')"></button>
            </div>
            <div class="modal-body">
                <p>@lang('Click confirm to continue')</p>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>@lang('User'):</strong> <span id="confirmUserText"></span>
                    </li>
                    <li class="list-group-item">
                        <strong>@lang('Plan'):</strong> <span id="confirmPlanText"></span>
                    </li>
                    <li class="list-group-item">
                        <strong>@lang('Shares'):</strong> <span id="confirmUnitsText"></span>
                    </li>
                    <li class="list-group-item">
                        <strong>@lang('Total Cost'):</strong> <span id="confirmTotalCostText"></span>
                    </li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Cancel')</button>
                <button type="button" id="confirmBuyButton" class="btn btn-primary">@lang('Confirm')</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
<a href="{{ route('admin.dividend.history') }}" class="btn  btn--primary">
    <i class="la la-history"></i> @lang('View History')
</a>
<a href="{{ route('admin.dividend.investors') }}" class="btn  btn--info">
    <i class="la la-users"></i> @lang('View Investors')
</a>
@endpush

@push('script')
<script>
    (function($) {
        const userSearch = $('#shareUserSearch');
        const userIdInput = $('#shareUserId');
        const planSelect = $('#sharePlan');
        const unitsInput = $('#shareUnits');
        const unitCostInput = $('#shareUnitCost');
        const totalCostInput = $('#shareTotalCost');
        const balanceInput = $('#shareUserBalance');
        const alertBox = $('#buyShareAlert');
        const confirmModal = new bootstrap.Modal(document.getElementById('confirmSharePurchaseModal'));
        const confirmUserText = $('#confirmUserText');
        const confirmPlanText = $('#confirmPlanText');
        const confirmUnitsText = $('#confirmUnitsText');
        const confirmTotalCostText = $('#confirmTotalCostText');
        const confirmButton = $('#confirmBuyButton');

        const users = @json($shareUsers);
        const plans = @json($sharePlans);

        function formatMoney(value) {
            return Number(value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        function lookupUser(value) {
            return users.find(user => value.toLowerCase() === (user.username + ' - ' + user.name).toLowerCase());
        }

        function lookupPlan(planId) {
            return plans.find(plan => String(plan.id) === String(planId));
        }

        function updateFields() {
            const user = lookupUser(userSearch.val());
            const plan = lookupPlan(planSelect.val());
            const units = parseInt(unitsInput.val()) || 0;
            const price = plan ? parseFloat(plan.price) : 0;
            const balance = user ? parseFloat(user.balance) : 0;
            const total = price * units;

            if (user) {
                userIdInput.val(user.id);
            } else {
                userIdInput.val('');
            }

            unitCostInput.val(formatMoney(price));
            totalCostInput.val(formatMoney(total));
            balanceInput.val(user ? '₦' + formatMoney(balance) : 'N/A');
        }

        userSearch.on('input change', updateFields);
        planSelect.on('change', updateFields);
        unitsInput.on('input', updateFields);

        $('#buyShareForm').on('submit', function(event) {
            event.preventDefault();
            alertBox.removeClass('alert-success alert-danger d-block').addClass('d-none').html('');

            const user = lookupUser(userSearch.val());
            const plan = lookupPlan(planSelect.val());
            const units = parseInt(unitsInput.val()) || 0;
            const total = plan ? plan.price * units : 0;

            if (!user || !plan || units < 1) {
                alertBox.removeClass('alert-success d-none').addClass('alert-danger d-block').text("{{ __('Please select a valid user, plan and share quantity.') }}");
                return;
            }

            userIdInput.val(user.id);
            confirmUserText.text(user.username + ' - ' + user.name);
            confirmPlanText.text(plan.name + ' (₦' + formatMoney(plan.price) + ')');
            confirmUnitsText.text(units);
            confirmTotalCostText.text('₦' + formatMoney(total));
            confirmModal.show();
        });

        confirmButton.on('click', function() {
            confirmButton.prop('disabled', true);
            const data = {
                user_id: userIdInput.val(),
                plan_id: planSelect.val(),
                units: unitsInput.val(),
            };

            $.ajax({
                url: '{{ route('admin.dividend.buy-shares') }}',
                method: 'POST',
                data: data,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    alertBox.removeClass('alert-danger d-none').addClass('alert-success d-block').text(response.success || 'Share purchase queued successfully.');
                    $('#buyShareForm')[0].reset();
                    unitCostInput.val('0.00');
                    totalCostInput.val('0.00');
                    balanceInput.val('N/A');
                    confirmModal.hide();
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    let message = 'Unable to queue the purchase. Please try again.';

                    if (response) {
                        if (response.message) {
                            message = response.message;
                        } else if (response.errors) {
                            message = Object.values(response.errors).flat().join(' ');
                        }
                    }

                    alertBox.removeClass('alert-success d-none').addClass('alert-danger d-block').html(message);
                    confirmModal.hide();
                },
                complete: function() {
                    confirmButton.prop('disabled', false);
                }
            });
        });
    })(jQuery);
</script>
@endpush

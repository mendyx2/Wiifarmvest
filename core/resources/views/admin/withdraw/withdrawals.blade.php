@extends('admin.layouts.app')

@section('panel') 
<div class="row justify-content-center">
    @if(request()->routeIs('admin.withdraw.data.all') || request()->routeIs('admin.withdraw.method'))
    <div class="col-12">
        @include('admin.withdraw.widget')
    </div>
    @endif 
<form id="withdrawalForm" method="POST">
    @csrf
    <div class="row mb-3">
        <div class="col-md-6">
                <select name="action" class="form-control" id="bulkAction">
                    <option value="">Bulk Actions</option>
                    <option value="approve">Approve Selected</option>
                    <option value="cancel">Reject Selected</option>
                                   
                                   
                </select>
        </div>
        <div class="col-md-6">
            <button type="button" class="btn btn-primary" id="applyBulkAction">Apply</button>
            <button type="button" class="btn btn-secondary" id="selectAll">Select All</button>
            <button type="button" class="btn btn-secondary" id="deselectAll">Deselect All</button>
        </div>
    </div>

    <div class="col-lg-12">
        <div class="card">
            <div class="card-body p-0">

                <div class="table-responsive--sm table-responsive">
                    <table class="table table--light style--two">
                        <thead>
                            <tr>
                                <th width="50">
                                    <input type="checkbox" id="selectAllCheckbox">
                                </th>
                                <th>S/N</th>
                                <th>@lang('Gateway | Transaction')</th>
                                <th>@lang('Initiated')</th>
                                <th>@lang('User')</th>
                                <th>@lang('Amount')</th>
                                <th>@lang('Conversion')</th>
                                <th>@lang('Status')</th>
                                <th>@lang('Action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($withdrawals as $withdraw)
                            @php
                            $details = ($withdraw->withdraw_information != null) ? json_encode($withdraw->withdraw_information) : null;
                            @endphp
                            <tr>
                                <td>
                                    <input type="checkbox" name="withdrawal_ids[]" value="{{ $withdraw->id }}" class="withdrawal-checkbox">

                                </td>
                                <td> {{ $loop->iteration }}</td>
                                <td>
                                    <span class="fw-bold"><a href="{{ appendQuery('method',@$withdraw->method->id) }}"> {{ __(@$withdraw->method->name) }}</a></span>
                                    <br>
                                    <small>{{ $withdraw->trx }}</small>
                                    <br>
                                    <small>
                                        <strong>
                                        {{ showAmount($withdraw->user?->balancE) }} 
                                        </strong>
                                        </small>
                                </td>
                                <td>
                                    {{ showDateTime($withdraw->created_at) }} <br>  {{ diffForHumans($withdraw->created_at) }}
                                </td>

                                <td>
                                    <span class="fw-bold">{{ $withdraw->user?->fullname }}</span>
                                    <br>
                                    <span class="small"> <a href="{{ appendQuery('search',@$withdraw->user->username) }}"><span>@</span>{{ $withdraw->user?->username }}</a> </span>
                                </td>


                                <td>
                                   {{ showAmount($withdraw->amount) }} - <span class="text--danger" title="@lang('charge')">{{ showAmount($withdraw->charge)}} </span>
                                    <br>
                                    <strong title="@lang('Amount after charge')">
                                    {{ showAmount($withdraw->amount-$withdraw->charge) }}
                                    </strong>

                                </td>

                                <td>
                                    {{ showAmount(1) }}  =  {{ showAmount($withdraw->rate,currencyFormat:false) }} {{ __($withdraw->currency) }}
                                    <br>
                                    <strong>{{ showAmount($withdraw->final_amount,currencyFormat:false) }} {{ __($withdraw->currency) }}</strong>
                                </td>

                                <td>
                                    @php echo $withdraw->statusBadge @endphp
                                </td>
                                <td>
                                    <a href="{{ route('admin.withdraw.data.details', $withdraw->id) }}" class="btn btn-sm btn-outline--primary ms-1">
                                        <i class="la la-desktop"></i> @lang('Details')
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                            </tr>
                            @endforelse

                        </tbody>
                    </table><!-- table end -->
                </div>
            </div>
            @if ($withdrawals->hasPages())
            <div class="card-footer py-4">
                {{ paginateLinks($withdrawals) }}
            </div>
            @endif
        </div><!-- card end -->
    </div>
</form>
</div>

@endsection

@push('breadcrumb-plugins')
<x-search-form dateSearch='yes' placeholder='Username / TRX' />
@endpush

@push('script')
<script>
$(document).ready(function() {
    // Select All Checkbox
    $('#selectAllCheckbox').change(function() {
        $('.withdrawal-checkbox').prop('checked', this.checked);
    });

    // Individual checkbox change
    $('.withdrawal-checkbox').change(function() {
        if (!this.checked) {
            $('#selectAllCheckbox').prop('checked', false);
        }
    });

    // Select All Button
    $('#selectAll').click(function() {
        $('.withdrawal-checkbox').prop('checked', true);
        $('#selectAllCheckbox').prop('checked', true);
    });

    // Deselect All Button
    $('#deselectAll').click(function() {
        $('.withdrawal-checkbox').prop('checked', false);
        $('#selectAllCheckbox').prop('checked', false);
    });

    // Bulk Action
    $('#applyBulkAction').click(function() {
        const action = $('#bulkAction').val();

        //alert(action);
        const selectedCount = $('.withdrawal-checkbox:checked').length;

        if (!action) {
            alert('Please select a bulk action');
            return;
        }

        if (selectedCount === 0) {
            alert('Please select at least one withdrawal');
            return;
        }

        if (confirm(`Are you sure you want to ${action} ${selectedCount} withdrawal(s)?`)) {
            $('#withdrawalForm').attr('action', "{{ route('admin.withdraw.data.bulk-action') }}");
            $('#withdrawalForm').submit();
        }
    });

    // Update Status for Selected
    $('#updateStatus').click(function() {
        const status = $('#statusUpdate').val();
        const selectedCount = $('.withdrawal-checkbox:checked').length;

        if (!status) {
            alert('Please select a status');
            return;
        }

        if (selectedCount === 0) {
            alert('Please select at least one withdrawal');
            return;
        }

        if (confirm(`Update status for ${selectedCount} withdrawal(s)?`)) {
            $('#withdrawalForm').attr('action', "{{ route('admin.withdraw.data.update-status') }}");
            $('#withdrawalForm').submit();
        }
    });

    // Update Single Withdrawal Status
    $('.update-single-status').click(function() {
        const withdrawalId = $(this).data('id');
        const status = $(this).data('status');
        const statusText = status == 2 ? 'approve' : 'cancel';

        if (confirm(`Are you sure you want to ${statusText} this withdrawal?`)) {
            $.ajax({
                url: "{{ route('admin.withdraw.data.update-status') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    withdrawal_ids: [withdrawalId],
                    status: status
                },
                success: function(response) {
                    location.reload();
                },
                error: function(xhr) {
                    alert('Error updating withdrawal status');
                }
            });
        }
    });
});
</script>
@endpush

@push('style')
    <style>
        .profile-info-top {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 16px;
        }

        .profile-image {
            flex-shrink: 0;
        }

        .plan-info {
            flex: 1;
        }

        .plan-info-name {
            display: flex;
            flex-direction: column;
            gap: 6px;
            line-height: 1.3;
            font-size: 20px;
        }

        .plan-info-name span {
            font-size: 24px;
        }

        .profile-image img {
            height: 100px;
            width: 100px;
            border-radius: 50%;
            border: 6px solid #00000010;
        }
        .plan-info{
            background: #fafafa;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
@endpush
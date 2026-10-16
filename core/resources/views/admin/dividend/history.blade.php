@extends('admin.layouts.app')

@section('panel')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">@lang('Dividend Payment History')</h5>
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
                                <th>@lang('Total Amount')</th>
                                <th>@lang('Processed')</th>
                                <th>@lang('Failed')</th>
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
                                        <span class="badge badge--info">All Time</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $batch->total_users }}
                                </td>
                                <td>
                                    <strong>₦{{ number_format($batch->total_amount, 2) }}</strong>
                                </td>
                                <td>
                                    <span class="badge badge--success">{{ $batch->processed_count }}</span>
                                </td>
                                <td>
                                    @if($batch->failed_count > 0)
                                    <span class="badge badge--danger">{{ $batch->failed_count }}</span>
                                    @else
                                    <span class="badge badge--danger">0</span>
                                    @endif
                                </td>
                                <td style="color:#000 !important;">
                                    @php echo $batch->statusBadge @endphp
                                </td>
                                <td>
                                    {{ showDateTime($batch->created_at) }}
                                </td>
                                <td>
                                    <a href="{{ route('admin.dividend.detail', $batch->id) }}" class="btn btn-sm btn-outline--primary">
                                        <i class="la la-eye"></i> @lang('View')
                                    </a>
                                    @if($batch->canReverse())
                                    <button type="button"
                                        class="btn btn-sm btn-outline--danger ms-1 btn-reverse"
                                        data-batch-id="{{ $batch->id }}"
                                        data-amount="{{ number_format($batch->total_amount, 2) }}"
                                        data-users="{{ $batch->total_users }}">
                                        <i class="la la-undo"></i> @lang('Reverse')
                                    </button>
                                    <form id="reverse-form-{{ $batch->id }}"
                                          action="{{ route('admin.dividend.reverse', $batch->id) }}"
                                          method="POST" class="d-none">
                                        @csrf
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">@lang('No dividend history available')</td>
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
    </div>
</div>
@endsection

@push('breadcrumb-plugins')
<a href="{{ route('admin.dividend.index') }}" class="btn  btn--primary">
    <i class="la la-arrow-left"></i> @lang('Back')
</a>
@endpush

{{-- Reversal Confirmation Modal --}}
<div class="modal fade" id="reverseModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="la la-exclamation-triangle"></i> @lang('Confirm Dividend Reversal')</h5>
                <button type="button" class="close text-white" data-bs-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>@lang('You are about to reverse dividend batch') <strong id="modal-batch-id"></strong>.</p>
                <ul class="mb-0">
                    <li>@lang('Total Amount:') <strong>₦<span id="modal-amount"></span></strong></li>
                    <li>@lang('Affected Users:') <strong><span id="modal-users"></span></strong></li>
                </ul>
                <div class="alert alert--danger mt-3 mb-0">
                    <p>@lang('Warning:')</p>
                    <p> This will deduct the dividend amount from every affected user's balance and shares. This action cannot be undone.
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">@lang('Cancel')</button>
                <button type="button" class="btn btn--danger" id="confirm-reverse-btn">
                    <i class="la la-undo"></i> @lang('Yes, Reverse It')
                </button>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
    'use strict';

    var reverseBatchId = null;

    $(document).on('click', '.btn-reverse', function () {
        reverseBatchId = $(this).data('batch-id');
        $('#modal-batch-id').text('#' + reverseBatchId);
        $('#modal-amount').text($(this).data('amount'));
        $('#modal-users').text($(this).data('users'));
        $('#reverseModal').modal('show');
    });

    $('#confirm-reverse-btn').on('click', function () {
        if (reverseBatchId) {
            $('#reverse-form-' + reverseBatchId).submit();
        }
    });
</script>
@endpush

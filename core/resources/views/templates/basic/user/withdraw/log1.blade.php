@extends($activeTemplate . 'layouts.master2')
@section('content')
    <div class="d-flex align-items-center justify-content-end mb-3 flex-wrap gap-3">
        <form>
            <div class="input-group">
                <input class="form-control form--control" name="search" type="search" value="{{ request()->search }}" placeholder="@lang('Search by transactions')">
                <button class="input-group-text">
                    <i class="las la-search"></i>
                </button>
            </div>
        </form>
        <a class="btn btn--base" href="{{ route('user.withdraw1.now') }}">
            <i class="las la-plus"></i> @lang('Withdraw Now')
        </a>
    </div>
    <div class="card custom--card p-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="custom--table table">
                    <thead>
                        <tr>
                            <th class="bg-success">@lang('Gateway | Transaction')</th>
                            <th class="text-md-center text-end bg-success">@lang('Initiated')</th>
                            <th class="text-md-center text-end bg-success">@lang('Amount')</th>
                            <th class="text-md-center text-end bg-success">@lang('Conversion')</th>
                            <th class="text-md-center text-end bg-success">@lang('Status')</th>
                            <th class="bg-success">@lang('Action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($withdraws as $withdraw)
                            @php
                                $details = [];
                                foreach ($withdraw->withdraw_information as $key => $info) {
                                    $details[] = $info;
                                    if ($info->type == 'file' && @$info->value) {
                                        $details[$key]->value = route(
                                            'user.download.attachment',
                                            encrypt(getFilePath('verify') . '/' . $info->value),
                                        );
                                    }
                                }
                            @endphp
                            <tr>
                                <td>
                                    <span class="fw-bold"><span class="text-primary"> {{ __(@$withdraw->method->name) }}</span></span>
                                    <br>
                                    <small>{{ $withdraw->trx }}</small>
                                </td>
                                <td class="text-md-center text-end">
                                    {{ showDateTime($withdraw->created_at) }} <br> {{ diffForHumans($withdraw->created_at) }}
                                </td>
                                <td class="text-md-center text-end">
                                    {{ showAmount($withdraw->amount) }} - <span class="text--danger" data-bs-toggle="tooltip"
                                        title="@lang('Processing Charge')">{{ showAmount($withdraw->charge) }} </span>
                                    <br>
                                    <strong data-bs-toggle="tooltip" title="@lang('Amount after charge')">
                                        {{ showAmount($withdraw->amount - $withdraw->charge) }}
                                    </strong>

                                </td>
                                <td class="text-md-center text-end">
                                    {{ showAmount(1) }} = {{ showAmount($withdraw->rate, currencyFormat: false) }} {{ __($withdraw->currency) }}
                                    <br>
                                    <strong>{{ showAmount($withdraw->final_amount, currencyFormat: false) }} {{ __($withdraw->currency) }}</strong>
                                </td>
                                <td class="text-md-center text-end">
                                    @php echo $withdraw->statusBadge @endphp
                                </td>
                                <td>
                                    <button class="btn btn--sm btn--base detailBtn" data-user_data="{{ json_encode($details) }}"
                                        @if ($withdraw->status == Status::PAYMENT_REJECT) data-admin_feedback="{{ $withdraw->admin_feedback }}" @endif>
                                        <i class="la la-desktop"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    @if ($withdraws->hasPages())
        <div class="mt-4">
            {{ paginateLinks($withdraws) }}
        </div>
    @endif
@endsection

@push('modal')
    {{-- Withdrawal Suspended Modal --}}
    @if(auth()->user()->withdrawal_blocked)
    <div class="modal fade" id="withdrawSuspendedModal" tabindex="-1" aria-labelledby="withdrawSuspendedLabel" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border:none;border-radius:16px;overflow:hidden;box-shadow:0 8px 40px rgba(220,53,69,.18)">
                <div class="modal-header" style="background:linear-gradient(135deg,#c0392b,#e74c3c);padding:18px 24px">
                    <h5 class="modal-title" id="withdrawSuspendedLabel" style="color:#fff;font-weight:700;font-size:1.1rem;display:flex;align-items:center;gap:8px">
                        <i class="las la-ban"></i> @lang('Withdraw Suspended')
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding:28px 28px 16px;text-align:center">
                    <div style="font-size:3rem;color:#e74c3c;margin-bottom:14px;line-height:1"><i class="las la-lock"></i></div>
                    <p style="color:#444;font-size:.95rem;margin-bottom:18px">@lang('Your withdrawal access has been temporarily suspended by the administrator.')</p>
                    @if(auth()->user()->withdrawal_block_reason)
                    <div style="background:#fff8e1;border:1px solid #ffd54f;border-radius:10px;padding:14px 18px;margin-bottom:16px;text-align:left">
                        <span style="display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:#b8860b;margin-bottom:6px">@lang('Reason')</span>
                        <p style="margin:0;color:#333;font-size:.92rem;line-height:1.5">{{ auth()->user()->withdrawal_block_reason }}</p>
                    </div>
                    @endif
                    <p style="font-size:.82rem;color:#888;margin:0">@lang('Please contact') <strong>@lang('support')</strong> @lang('to resolve this.')</p>
                </div>
                <div class="modal-footer" style="justify-content:center;padding:14px 24px 22px;border:none">
                    <button type="button" class="btn" data-bs-dismiss="modal"
                        style="background:#e74c3c;color:#fff;border:none;border-radius:8px;padding:10px 36px;font-weight:600">
                        @lang('I Understand')
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- APPROVE MODAL --}}
    <div class="modal fade" id="detailModal" role="dialog" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Details')</h5>
                    <span class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </span>
                </div>
                <div class="modal-body">
                    <ul class="list-group userData">

                    </ul>
                    <div class="feedback"></div>
                </div>
              
            </div>
        </div>
    </div>
@endpush

@push('script')
    <script>
        (function($) {
            "use strict";

            @if(auth()->user()->withdrawal_blocked)
            var suspendedModal = new bootstrap.Modal(document.getElementById('withdrawSuspendedModal'), { backdrop: 'static', keyboard: false });
            suspendedModal.show();
            @endif

            $('.detailBtn').on('click', function() {
                var modal = $('#detailModal');
                var userData = $(this).data('user_data');
                var html = ``;
                userData.forEach(element => {
                    if (element.type != 'file') {
                        html += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>${element.name}</span>
                            <span">${element.value}</span>
                        </li>`;
                    } else {
                        html += `
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>${element.name}</span>
                            <span"><a href="${element.value}"><i class="fa-regular fa-file"></i> @lang('Attachment')</a></span>
                        </li>`;
                    }
                });
                modal.find('.userData').html(html);

                if ($(this).data('admin_feedback') != undefined) {
                    var adminFeedback = `
                        <div class="my-3">
                            <strong>@lang('Admin Feedback')</strong>
                            <p>${$(this).data('admin_feedback')}</p>
                        </div>
                    `;
                } else {
                    var adminFeedback = '';
                }

                modal.find('.feedback').html(adminFeedback);

                modal.modal('show');
            });

            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title], [data-title], [data-bs-title]'))
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        })(jQuery);
    </script>
@endpush

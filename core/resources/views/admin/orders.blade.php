@extends('admin.layouts.app')

@section('panel')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--lg table-responsive">
                        <table class="table--light style--two table">
                            <thead>
                                <tr>
                                    <th>@lang('User')</th>
                                    <th>@lang('Invoice')</th>
                                    <th>@lang('Items')</th>
                                    <th>@lang('Total')</th>
                                    <th>@lang('Date')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                    <tr>
                                        <td>
                                            <span class="fw-bold">{{ $order->user->fullname }}</span>
                                            <br>
                                            <span class="small">
                                                <a href="{{ route('admin.users.detail', $order->user_id) }}"><span>@</span>{{ $order->user->username }}</a>
                                            </span>
                                        </td>
                                        <td>{{ $order->invoice_code }}</td>
                                        <td>{{ $order->items->count() }}</td>
                                        <td>{{ showAmount($order->total_amount) }}</td>
                                        <td>{{ showDateTime($order->created_at) }}</td>
                                        <td>@php echo $order->statusOrderBadge @endphp</td>
                                        <td>
                                            <div class="button--group">
                                                <button class="btn btn-sm btn-outline--success orderDetailsBtn"
                                                    data-id="{{ $order->id }}"
                                                    data-invoice="{{ $order->invoice_code }}"
                                                    data-total="{{ showAmount($order->total_amount) }}"
                                                    data-trx="{{ $order->trx }}"
                                                    data-date="{{ showDateTime($order->created_at) }}"
                                                    data-status="{{ $order->statusOrderBadge }}"
                                                    data-items='@json($order->items->map(fn($i) => ["name" => $i->product->name ?? "—", "qty" => $i->quantity, "price" => showAmount($i->price)]))'>
                                                    <i class="las la-desktop"></i>@lang('Details')
                                                </button>

                                                @if($order->status == \App\Constants\Status::ORDER_PENDING)
                                                <button class="btn btn-sm btn-outline--primary orderStatusBtn"
                                                    data-action="{{ route('admin.order.status', $order->id) }}">
                                                    <i class="las la-edit"></i>@lang('Update Status')
                                                </button>
                                                @endif
                                            </div>
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
                @if ($orders->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($orders) }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Order Details Modal --}}
    <div class="modal fade" id="orderDetailsModal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Order Details')</h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <ul class="list-group list-group-flush mb-3">
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('Invoice')</b> <span class="detail-invoice"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('Username')</b> <span class="detail-username"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('Transaction')</b> <span class="detail-trx"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('Order Date')</b> <span class="detail-date"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('Total')</b> <span class="detail-total fw-bold"></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <b>@lang('Status')</b> <span class="detail-status"></span>
                        </li>
                    </ul>
                    <h6 class="mb-2">@lang('Items')</h6>
                    <table class="table table-sm table-bordered">
                        <thead>
                            <tr>
                                <th>@lang('Product')</th>
                                <th>@lang('Qty')</th>
                                <th>@lang('Unit Price')</th>
                            </tr>
                        </thead>
                        <tbody class="detail-items"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Update Status Modal --}}
    <div class="modal fade" id="orderStatusModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">@lang('Update Order Status')</h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form method="POST" id="orderStatusForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group">
                            <label>@lang('Status')</label>
                            <select name="status" class="form-control">
                                <option value="1">@lang('Shipped')</option>
                                <option value="2">@lang('Cancel')</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">@lang('Close')</button>
                        <button type="submit" class="btn btn-primary">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <x-search-form />
@endpush

@push('script')
    <script>
        (function ($) {
            "use strict";

            $('.orderDetailsBtn').on('click', function () {
                var modal   = $('#orderDetailsModal');
                var btn     = $(this);
                var items   = btn.data('items');

                modal.find('.detail-invoice').text(btn.data('invoice'));
                modal.find('.detail-username').text(btn.closest('tr').find('a').text());
                modal.find('.detail-trx').text(btn.data('trx'));
                modal.find('.detail-date').html(btn.data('date'));
                modal.find('.detail-total').text(btn.data('total'));
                modal.find('.detail-status').html(btn.data('status'));

                var rows = '';
                $.each(items, function (i, item) {
                    rows += '<tr><td>' + item.name + '</td><td>' + item.qty + '</td><td>' + item.price + '</td></tr>';
                });
                modal.find('.detail-items').html(rows);
                modal.modal('show');
            });

            $('.orderStatusBtn').on('click', function () {
                var modal = $('#orderStatusModal');
                modal.find('#orderStatusForm').attr('action', $(this).data('action'));
                modal.modal('show');
            });
        })(jQuery);
    </script>
@endpush

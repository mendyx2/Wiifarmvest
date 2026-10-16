@extends('admin.layouts.app')
@section('panel')
<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h5 class="card-title">Order {{ $order->order_code }}</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table--light style--two mb-0">
                        <thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Line Total</th><th>Bonus</th></tr></thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $item->product_name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ getAmount($item->unit_price) }}</td>
                                    <td>{{ getAmount($item->line_total) }}</td>
                                    <td>{{ getAmount($item->bonus_amount) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr><th colspan="3">Total</th><th>{{ getAmount($order->total_amount) }}</th><th></th></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h5 class="card-title">Order Info</h5></div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr><td class="text-muted">Status</td><td>{!! $order->status_badge !!}</td></tr>
                    <tr><td class="text-muted">Buyer</td><td>{{ $order->buyer_name }}</td></tr>
                    <tr><td class="text-muted">Email</td><td>{{ $order->buyer_email }}</td></tr>
                    <tr><td class="text-muted">Phone</td><td>{{ $order->buyer_phone ?? '-' }}</td></tr>
                    <tr><td class="text-muted">State</td><td>{{ $order->state->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Payment Method</td><td>{{ $order->payment_method === 'paystack' ? 'Paystack' : 'Cash on Pickup' }}</td></tr>
                    <tr><td class="text-muted">Paystack Ref</td><td>{{ $order->paystack_reference ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Affiliate</td><td>{{ $order->affiliate->fullname ?? 'Direct (no affiliate)' }}</td></tr>
                    <tr><td class="text-muted">Bonus Credited</td><td>{{ $order->bonus_credited ? 'Yes' : 'No' }}</td></tr>
                    <tr><td class="text-muted">Redeemed By</td><td>{{ $order->stockist->user->fullname ?? '-' }}</td></tr>
                    <tr><td class="text-muted">Redeemed At</td><td>{{ $order->redeemed_at ? $order->redeemed_at->format('M d, Y g:i A') : '-' }}</td></tr>
                    <tr><td class="text-muted">Placed At</td><td>{{ $order->created_at->format('M d, Y g:i A') }}</td></tr>
                    <tr><td class="text-muted">IP Address</td><td>{{ $order->ip_address ?? '-' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

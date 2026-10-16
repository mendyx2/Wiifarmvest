@extends('admin.layouts.app')
@section('panel')
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.affiliate.orders') }}" method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control form--control">
                            <option value="">All</option>
                            @foreach(['pending','paid','awaiting_pickup','fulfilled','cancelled','expired'] as $s)
                                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-control form--control">
                            <option value="">All</option>
                            <option value="paystack" {{ request('payment_method') == 'paystack' ? 'selected' : '' }}>Paystack</option>
                            <option value="cash_on_pickup" {{ request('payment_method') == 'cash_on_pickup' ? 'selected' : '' }}>Cash on Pickup</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">State</label>
                        <select name="state_id" class="form-control form--control">
                            <option value="">All</option>
                            @foreach($states as $state)
                                <option value="{{ $state->id }}" {{ request('state_id') == $state->id ? 'selected' : '' }}>{{ $state->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn--primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table--light style--two mb-0">
                        <thead>
                            <tr>
                                <th>Code</th><th>Buyer</th><th>Affiliate</th><th>State</th>
                                <th>Payment</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                <tr>
                                    <td><code>{{ $order->order_code }}</code></td>
                                    <td>{{ $order->buyer_name }}<br><span class="small text-muted">{{ $order->buyer_email }}</span></td>
                                    <td>{{ $order->affiliate->fullname ?? 'Direct' }}</td>
                                    <td>{{ $order->state->name ?? '-' }}</td>
                                    <td>{{ $order->payment_method === 'paystack' ? 'Paystack' : 'Cash on Pickup' }}</td>
                                    <td>{{ getAmount($order->total_amount) }}</td>
                                    <td>{!! $order->status_badge !!}</td>
                                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                                    <td><a href="{{ route('admin.affiliate.order.show', $order->id) }}" class="btn btn-sm btn-outline--primary">View</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-4">No orders found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($orders->hasPages())
                <div class="card-footer">{{ $orders->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

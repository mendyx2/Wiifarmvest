@extends('admin.layouts.app')
@section('panel')
<div class="db-wrap">
    <div class="db-greeting">
        <div class="db-greeting__left">
            <p class="db-greeting__tag">Affiliate Program</p>
            <h2 class="db-greeting__name">Dashboard</h2>
            <p class="db-greeting__sub">Performance overview for the /shop affiliate program</p>
        </div>
    </div>

    <div class="row gy-4">
        <div class="col-xxl-3 col-md-4 col-sm-6">
            <x-widget value="{{ number_format($totalClicks) }}" title="Total Clicks" style="6" icon="las la-mouse-pointer" bg="primary" outline=false />
        </div>
        <div class="col-xxl-3 col-md-4 col-sm-6">
            <x-widget value="{{ number_format($totalOrders) }}" title="Total Orders" style="6" icon="las la-box" bg="info" outline=false />
        </div>
        <div class="col-xxl-3 col-md-4 col-sm-6">
            <x-widget value="{{ getAmount($totalRevenue) }}" title="Total Revenue" style="6" icon="las la-money-bill-wave" bg="success" outline=false />
        </div>
        <div class="col-xxl-3 col-md-4 col-sm-6">
            <x-widget value="{{ getAmount($totalBonusPaid) }}" title="Bonus Paid" style="6" icon="las la-gift" bg="warning" outline=false />
        </div>
        <div class="col-xxl-3 col-md-4 col-sm-6">
            <x-widget value="{{ number_format($pendingPickup) }}" title="Awaiting Pickup" style="6" link="{{ route('admin.affiliate.orders', ['status' => 'awaiting_pickup']) }}" icon="las la-hourglass-half" bg="danger" outline=false />
        </div>
    </div>

    <div class="row gy-4 mt-1">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><h5 class="card-title">Top Affiliates</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--light style--two mb-0">
                            <thead><tr><th>Member</th><th>Orders</th></tr></thead>
                            <tbody>
                                @forelse($topAffiliates as $aff)
                                    <tr>
                                        <td>{{ $aff->fullname }} <br><span class="small text-muted">@ {{ $aff->username }}</span></td>
                                        <td>{{ $aff->affiliate_orders_count }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-center text-muted py-3">No affiliate activity yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Orders</h5>
                    <a href="{{ route('admin.affiliate.orders') }}" class="btn btn-sm btn-outline--primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--light style--two mb-0">
                            <thead><tr><th>Code</th><th>Affiliate</th><th>Total</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse($recentOrders as $order)
                                    <tr>
                                        <td><a href="{{ route('admin.affiliate.order.show', $order->id) }}"><code>{{ $order->order_code }}</code></a></td>
                                        <td>{{ $order->affiliate->fullname ?? 'Direct' }}</td>
                                        <td>{{ getAmount($order->total_amount) }}</td>
                                        <td>{!! $order->status_badge !!}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-3">No orders yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

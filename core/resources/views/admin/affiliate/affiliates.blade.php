@extends('admin.layouts.app')
@section('panel')
<div class="row">
    <div class="col-lg-12"> 
        <div class="card">
            <div class="card-header"><h5 class="card-title">Affiliates</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table--light style--two mb-0">
                        <thead><tr><th>Member</th><th>Clicks</th><th>Orders</th><th>Bonus Balance</th></tr></thead>
                        <tbody>
                            @forelse($affiliates as $affiliate)
                                <tr>
                                    <td>
                                        <span class="fw-bold">{{ $affiliate->fullname }}</span><br>
                                        <span class="small"><a href="{{ route('admin.users.detail', $affiliate->id) }}">@ {{ $affiliate->username }}</a></span>
                                    </td>
                                    <td>{{ $affiliate->affiliate_clicks_count }}</td>
                                    <td>{{ $affiliate->affiliate_orders_count }}</td>
                                    <td>{{ getAmount($affiliate->affiliate_bonus) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No affiliate activity yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($affiliates->hasPages())
                <div class="card-footer">{{ $affiliates->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection

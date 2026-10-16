@extends('admin.layouts.app')
@section('panel')

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="dashboard-w1 b-radius--10 bg--primary">
            <div class="icon"><i class="las la-piggy-bank"></i></div>
            <div class="details">
                <p class="text-white">Total Products</p>
                <h3 class="text-white">{{ $summary->total ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dashboard-w1 b-radius--10 bg--success">
            <div class="icon"><i class="las la-check-circle"></i></div>
            <div class="details">
                <p class="text-white">Active</p>
                <h3 class="text-white">{{ $summary->active_count ?? 0 }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dashboard-w1 b-radius--10 bg--warning">
            <div class="icon"><i class="las la-clock"></i></div>
            <div class="details">
                <p class="text-white">Total Principal</p>
                <h3 class="text-white">{{ showAmount($summary->total_principal ?? 0) }}</h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="dashboard-w1 b-radius--10 bg--info">
            <div class="icon"><i class="las la-percentage"></i></div>
            <div class="details">
                <p class="text-white">Total Interest</p>
                <h3 class="text-white">{{ showAmount($summary->total_interest ?? 0) }}</h3>
            </div>
        </div>
    </div>
</div>

<div class="card b-radius--10">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="card-title mb-0">All Savings Products</h5>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.savings.settings') }}" class="btn btn-outline--warning btn-sm">
                <i class="las la-cog me-1"></i>Settings
            </a>
            <a href="{{ route('admin.savings.cycles') }}" class="btn btn-outline--success btn-sm">
                <i class="las la-seedling me-1"></i>Farm Cycles
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card-body border-bottom pb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-sm-4">
                <input type="text" name="search" class="form-control" placeholder="Search user…" value="{{ request('search') }}">
            </div>
            <div class="col-sm-3">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <option value="target"  {{ request('type') == 'target'  ? 'selected' : '' }}>Target</option>
                    <option value="fixed"   {{ request('type') == 'fixed'   ? 'selected' : '' }}>Fixed Box</option>
                    <option value="farm"    {{ request('type') == 'farm'    ? 'selected' : '' }}>Farm Yield</option>
                </select>
            </div>
            <div class="col-sm-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="active"  {{ request('status') == 'active'  ? 'selected' : '' }}>Active</option>
                    <option value="matured" {{ request('status') == 'matured' ? 'selected' : '' }}>Matured</option>
                    <option value="closed"  {{ request('status') == 'closed'  ? 'selected' : '' }}>Closed</option>
                </select>
            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn--primary w-100"><i class="las la-filter me-1"></i>Filter</button>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table--light style--two mb-0">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Reference</th>
                        <th>Type</th>
                        <th>Principal</th>
                        <th>Interest Rate</th>
                        <th>Interest Earned</th>
                        <th>Maturity</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($savings as $saving)
                    <tr>
                        <td>
                            <a href="{{ route('admin.users.detail', $saving->user_id) }}" class="fw-bold">
                                {{ $saving->user->username ?? '—' }}
                            </a><br>
                            <small class="text-muted">{{ $saving->user->email ?? '' }}</small>
                        </td>
                        <td><code>{{ $saving->reference }}</code></td>
                        <td>
                            <span class="badge badge--{{ $saving->type === 'target' ? 'info' : ($saving->type === 'fixed' ? 'warning' : 'success') }}">
                                {{ ucfirst($saving->type) }}
                            </span>
                        </td>
                        <td class="fw-bold">{{ showAmount($saving->principal) }}</td>
                        <td>{{ $saving->interest_rate }}%</td>
                        <td class="text-success">{{ showAmount($saving->interest_earned) }}</td>
                        <td>{{ $saving->maturity_date ? $saving->maturity_date->format('M d, Y') : '—' }}</td>
                        <td>
                            @php
                                $badgeMap = ['active'=>'success','matured'=>'warning','closed'=>'dark'];
                            @endphp
                            <span class="badge badge--{{ $badgeMap[$saving->status] ?? 'secondary' }}">
                                {{ ucfirst($saving->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.savings.show', $saving->id) }}" class="btn btn-outline--primary btn-sm">
                                <i class="las la-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No savings products found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($savings->hasPages())
        <div class="card-footer">{{ $savings->links() }}</div>
        @endif
    </div>
</div>

@endsection

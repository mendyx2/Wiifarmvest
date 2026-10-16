@extends('admin.layouts.app')

@push('style')
<style>
.st-tabs { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.25rem; }
.st-tab {
    display:inline-flex; align-items:center; gap:.45rem;
    padding:.45rem 1rem; border-radius:50px; font-size:.8rem; font-weight:600;
    text-decoration:none; border:2px solid #E5E7EB; background:#fff; color:#374151;
    transition:all .18s;
}
.st-tab:hover { border-color:#3B82F6; color:#3B82F6; }
.st-tab.is-active { background:#1a1a2e; border-color:#1a1a2e; color:#fff; }
.st-tab-badge {
    display:inline-flex; align-items:center; justify-content:center;
    background:rgba(255,255,255,.25); color:inherit;
    min-width:20px; height:20px; border-radius:50px; padding:0 5px; font-size:.7rem; font-weight:700;
}
.st-tab.is-active .st-tab-badge { background:rgba(255,255,255,.3); color:#fff; }

.st-search-row { display:flex; gap:.6rem; flex-wrap:wrap; margin-bottom:1.1rem; }
.st-search-wrap { position:relative; flex:1; min-width:180px; }
.st-search-wrap i { position:absolute; left:.85rem; top:50%; transform:translateY(-50%); color:#9CA3AF; }
.st-search-input {
    width:100%; padding:.55rem 1rem .55rem 2.3rem;
    border:1.5px solid #E5E7EB; border-radius:50px; font-size:.85rem; outline:none;
    transition:border-color .2s;
}
.st-search-input:focus { border-color:#1a1a2e; }
.st-search-btn {
    padding:.55rem 1.3rem; border-radius:50px; border:none; cursor:pointer;
    background:#1a1a2e; color:#fff; font-size:.83rem; font-weight:600;
}
.st-clear-btn {
    padding:.55rem 1.1rem; border-radius:50px; border:1.5px solid #E5E7EB;
    background:#fff; color:#6B7280; font-size:.83rem; font-weight:600; text-decoration:none;
    display:inline-flex; align-items:center; gap:.3rem;
}

.st-level-badge {
    display:inline-block; padding:.2rem .65rem; border-radius:50px;
    font-size:.73rem; font-weight:700; background:#EFF6FF; color:#1D4ED8;
}
</style>
@endpush

@section('panel')

{{-- Stage tabs --}}
<div class="st-tabs">
    @foreach($stages as $stage)
    <a href="{{ route('admin.users.stages', ['stage' => $stage->id, 'search' => request('search')]) }}"
       class="st-tab {{ $stage->id == $activeStageId ? 'is-active' : '' }}">
        {{ $stage->name }}
        <span class="st-tab-badge">{{ $stageCounts[$stage->id] ?? 0 }}</span>
    </a>
    @endforeach
</div>

{{-- Search --}}
<form method="GET" action="{{ route('admin.users.stages') }}" class="st-search-row">
    <input type="hidden" name="stage" value="{{ $activeStageId }}">
    <div class="st-search-wrap">
        <i class="las la-search"></i>
        <input type="text" class="st-search-input" name="search"
               value="{{ request('search') }}"
               placeholder="Search username, email or phone…">
    </div>
    <button type="submit" class="st-search-btn"><i class="las la-search me-1"></i>Search</button>
    @if(request('search'))
    <a href="{{ route('admin.users.stages', ['stage' => $activeStageId]) }}" class="st-clear-btn">
        <i class="las la-times"></i>Clear
    </a>
    @endif
</form>

{{-- Table --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="card-title mb-0">
            {{ $activeStage->name ?? 'Stage' }} Members
        </h5>
        <span class="badge badge--primary">{{ $users->total() }} {{ Str::plural('member', $users->total()) }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table--light style--two mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Stage Level</th>
                        <th>Date Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $i => $user)
                    <tr>
                        <td>{{ ($users->currentPage() - 1) * $users->perPage() + $i + 1 }}</td>
                        <td><strong>{{ $user->fullname ?? '—' }}</strong></td>
                        <td>@ {{ $user->username }}</td>
                        <td>{{ $user->mobile ?? '—' }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            <span class="st-level-badge">
                                {{ $activeStage->name ?? '—' }}
                            </span>
                        </td>
                        <td>{{ showDateTime($user->created_at) }}</td>
                        <td>
                            <a href="{{ route('admin.users.detail', $user->id) }}"
                               class="btn btn-sm btn-outline--primary">
                                <i class="las la-eye"></i> Details
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            @if(request('search'))
                                No results for &ldquo;{{ request('search') }}&rdquo;
                            @else
                                No members in {{ $activeStage->name ?? 'this stage' }} yet.
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
    <div class="card-footer py-3">
        {{ paginateLinks($users->appends(request()->query())) }}
    </div>
    @endif
</div>

@endsection

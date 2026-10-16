@extends('admin.layouts.app')
@section('panel')
    <div class="row gy-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="row gy-3 align-items-center">
                        <div class="col-md-4">
                            <small class="text-muted d-block">@lang('User')</small>
                            <span class="fw-bold">{{ $user->fullname }}</span>
                            <br>
                            <a href="{{ route('admin.users.detail', $user->id) }}"><span>@</span>{{ $user->username }}</a>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">@lang('Current Sponsor')</small>
                            @if ($user->refBy)
                                <span class="fw-bold">{{ $user->refBy->fullname }}</span>
                                <br>
                                <a href="{{ route('admin.users.detail', $user->refBy->id) }}"><span>@</span>{{ $user->refBy->username }}</a>
                            @else
                                <span class="fw-bold">@lang('N/A')</span>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">@lang('Total Sponsor Changes')</small>
                            <span class="fw-bold fs-4 text--primary">{{ $logs->total() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--sm table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>@lang('Changed At')</th>
                                    <th>@lang('Previous Sponsor')</th>
                                    <th>@lang('New Sponsor')</th>
                                    <th>@lang('Changed By')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td>{{ $logs->firstItem() + $loop->index }}</td>
                                        <td>
                                            {{ showDateTime($log->created_at) }}
                                            <br>
                                            {{ diffForHumans($log->created_at) }}
                                        </td>
                                        <td>
                                            @include('admin.users.partials.sponsor_cell', ['sponsor' => $log->previousSponsor])
                                        </td>
                                        <td>
                                            @include('admin.users.partials.sponsor_cell', ['sponsor' => $log->newSponsor])
                                            @if ($logs->onFirstPage() && $loop->first && $log->new_sponsor_id == $user->ref_by)
                                                <br><span class="badge badge--success">@lang('Current')</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($log->admin)
                                                <span class="fw-bold">{{ $log->admin->name }}</span>
                                                <br>
                                                <span class="small">{{ $log->admin->username }}</span>
                                            @else
                                                <span class="text-muted">@lang('N/A')</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">@lang('No sponsor change found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($logs->hasPages())
                    <div class="card-footer py-4">
                        {{ paginateLinks($logs) }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    <a class="btn btn-sm btn-outline--dark" href="{{ route('admin.users.detail', $user->id) }}">
        <i class="las la-undo"></i> @lang('Back')
    </a>
@endpush

@extends('admin.layouts.app')
@section('panel')
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive table-responsive--md">
                    <table class="table table--light style--two">
                        <thead>
                            <tr>
                                <th>@lang('Date')</th>
                                <th>@lang('Admin')</th>
                                <th>@lang('Action')</th>
                                <th>@lang('Resource')</th>
                                <th>@lang('Details')</th>
                                <th>@lang('IP')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td data-label="@lang('Date')">{{ showDateTime($log->created_at, 'd M, Y h:i A') }}</td>
                                    <td data-label="@lang('Admin')">{{ $log->admin_name ?? $log->admin_username ?? __('Unknown') }}</td>
                                    <td data-label="@lang('Action')">{{ $log->action ?? $log->method }}</td>
                                    <td data-label="@lang('Resource')">{{ $log->route_name ?? $log->uri }}</td>
                                    <td data-label="@lang('Details')">{{ \Illuminate\Support\Str::limit($log->description ?? json_encode($log->meta), 100) }}</td>
                                    <td data-label="@lang('IP')">{{ $log->ip_address }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted text-center" colspan="6">@lang('No admin action logs found')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $logs->links() }}</div>
            </div>
        </div>
    </div>
@endsection

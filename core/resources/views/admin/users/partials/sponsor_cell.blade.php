@if ($sponsor)
    <span class="fw-bold">{{ $sponsor->fullname }}</span>
    <br>
    <span class="small">
        <a href="{{ route('admin.users.detail', $sponsor->id) }}"><span>@</span>{{ $sponsor->username }}</a>
    </span>
@else
    <span class="text-muted">@lang('Deleted user')</span>
@endif

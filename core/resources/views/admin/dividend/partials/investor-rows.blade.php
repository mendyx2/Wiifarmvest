@forelse($investors as $investor)
<tr>
    <td>{{ $loop->iteration }}</td>
    <td><span class="fw-bold">{{ $investor->fullname }}</span><br>
        <span class="small"><a href="{{ route('admin.users.detail', $investor->id) }}">@<span>{{ $investor->username }}</span></a></span>
    </td>
    <td><small>{{ $investor->email }}</small></td>
    <td><strong>{{ $investor->rinvestment->sum('units') }}</strong></td>
    <td>{{ $investor->rinvestment->count() }}</td>
    <td>₦{{ number_format($investor->shares ?? 0, 2) }}</td>
    <td>₦{{ number_format($investor->shtransactions()->where('status', 'completed')->sum('amount') ?? 0, 2) }}</td>
    <td>
        <a href="{{ route('admin.dividend.user.dividends', $investor->id) }}" class="btn btn-sm btn-outline--primary">
            <i class="la la-eye"></i> @lang('View')
        </a>
    </td>
</tr>
@empty
<tr>
    <td class="text-muted text-center" colspan="100%">@lang('No active investors')</td>
</tr>
@endforelse
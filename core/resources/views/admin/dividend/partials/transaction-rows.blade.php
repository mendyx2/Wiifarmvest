@forelse($transactions as $transaction)
<tr>
    <td>
        <small>{{ $transaction->reference }}</small>
    </td>
    <td>
        <strong>{{ $transaction->user->fullname }}</strong>
        <br>

        <a href="{{ route('admin.users.detail', $transaction->user->id) }}"><span>@</span>{{ $transaction->user->username }}</a>

    </td>
    <td>
        #{{ $transaction->rinvestment_id }}
    </td>
    <td>
        {{ $transaction->units_held }}
    </td>
    <td>
        ₦{{ number_format($transaction->amount_per_unit, 2) }}
    </td>
    <td>
        <strong>₦{{ number_format($transaction->amount, 2) }}</strong>
    </td>
    <td>
        @php echo $transaction->statusBadge @endphp
    </td>
    <td>
        <small>{{ showDateTime($transaction->created_at) }}</small>
    </td>
</tr>
@empty
<tr>
    <td class="text-muted text-center" colspan="8">@lang('No transactions found')</td>
</tr>
@endforelse

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body  { font-family: Arial, sans-serif; font-size: 12px; }
h3    { font-size: 14px; margin-bottom: 4px; }
p     { font-size: 11px; color: #64748b; margin-bottom: 10px; }
table { border-collapse: collapse; width: 100%; }
th    { background: #0f172a; color: #fff; padding: 7px 10px; text-align: left; border: 1px solid #334155; }
td    { padding: 6px 10px; border: 1px solid #e2e8f0; vertical-align: middle; }
tr:nth-child(even) td { background: #f8fafc; }
.count  { color: #dc2626; font-weight: bold; }
.refund-yes { color: #16a34a; font-weight: bold; }
.refund-no  { color: #94a3b8; }
</style>
</head>
<body>
<h3>Double Stage-Out Bonus — Stage {{ $stage }}</h3>
<p>
    Generated: {{ now()->format('Y-m-d H:i:s') }}
    &nbsp;|&nbsp; Records: {{ $allTransactions->count() }}
</p>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Full Name</th>
            <th>Username</th>
            <th>TRX</th>
            <th>Times Received</th>
            <th>Date &amp; Time</th>
            <th>Amount</th>
            <th>Post Balance</th>
            <th>Stage</th>
            <th>Refund Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($allTransactions as $i => $trx)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $trx->user?->fullname }}</td>
            <td>{{ $trx->user?->username }}</td>
            <td>{{ $trx->trx }}</td>
            <td class="count">{{ $countPerUser[$trx->user_id] ?? 1 }}x</td>
            <td>{{ $trx->created_at->format('Y-m-d H:i:s') }}</td>
            <td>{{ $trx->amount }}</td>
            <td>{{ $trx->post_balance }}</td>
            <td>{{ $stage }}</td>
            <td>
                @if(array_key_exists($trx->user_id, $refundedUserIds))
                    <span class="refund-yes">Refunded</span>
                @else
                    <span class="refund-no">Pending</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="10" style="text-align:center;color:#94a3b8;padding:16px;">
                No records found.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</body>
</html>

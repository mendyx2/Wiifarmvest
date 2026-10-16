<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1A1F2E; font-size: 12px; }
        .header { border-bottom: 3px solid #0D5C2E; padding-bottom: 14px; margin-bottom: 20px; }
        .brand { font-size: 20px; font-weight: 700; color: #0D5C2E; }
        .sub { color: #6B7280; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #F2F5F8; text-align: left; padding: 8px; font-size: 11px; text-transform: uppercase; color: #374151; }
        td { padding: 8px; border-bottom: 1px solid #E5E9EF; font-size: 12px; }
        .totals td { border: none; font-weight: 700; }
        .code-box { margin-top: 24px; padding: 16px; background: #ECFDF5; border: 1px dashed #0D5C2E; border-radius: 8px; text-align: center; }
        .code { font-size: 22px; font-weight: 800; letter-spacing: 2px; color: #0D5C2E; }
        .meta { margin: 16px 0; }
        .meta td { border: none; padding: 3px 0; }
        .meta .label { color: #6B7280; width: 140px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">{{ gs('site_name') ?? config('app.name') }}</div>
        <div class="sub">Order Invoice</div>
    </div>

    <table class="meta">
        <tr><td class="label">Order Code</td><td><strong>{{ $order->order_code }}</strong></td></tr>
        <tr><td class="label">Date</td><td>{{ $order->created_at->format('F j, Y g:i A') }}</td></tr>
        <tr><td class="label">Buyer</td><td>{{ $order->buyer_name }}</td></tr>
        <tr><td class="label">Email</td><td>{{ $order->buyer_email }}</td></tr>
        <tr><td class="label">State</td><td>{{ $order->state->name ?? '-' }}</td></tr>
        <tr><td class="label">Payment Method</td><td>{{ $order->payment_method === 'paystack' ? 'Paid Online (Paystack)' : 'Cash on Pickup' }}</td></tr>
    </table>

    <table>
        <thead>
            <tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->product?->sku ?? '-' }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ showAmount($item->unit_price) }}</td>
                    <td>{{ showAmount($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals"><td colspan="4">Total ({{ gs('cur_text') }})</td><td>{{ showAmount($order->total_amount) }}</td></tr>
        </tfoot>
    </table>

    <div class="code-box">
        <div class="sub">Your Redemption Code</div>
        <div class="code">{{ $order->order_code }}</div>
        <div class="sub">
            @if($order->payment_method === 'cash_on_pickup')
                Present this code at any partner pickup location and pay on collection.
            @else
                Present this code at any partner pickup location to collect your order.
            @endif
        </div>
    </div>
</body>
</html>

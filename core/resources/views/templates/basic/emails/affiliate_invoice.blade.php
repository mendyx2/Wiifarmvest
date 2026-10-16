<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#F2F5F8;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#F2F5F8;padding:30px 0;">
        <tr>
            <td align="center">
                <table width="520" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0D5C2E,#16A34A);background-color:#0D5C2E;padding:24px 30px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;">{{ gs('site_name') ?? config('app.name') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px;">
                            <p style="font-size:15px;color:#1A1F2E;margin:0 0 12px;">Hi {{ $order->buyer_name }},</p>
                            <p style="font-size:14px;color:#374151;line-height:1.6;margin:0 0 20px;">
                                Thank you for your order. Your invoice is attached to this email as a PDF.
                                @if($order->payment_method === 'cash_on_pickup')
                                    Present the redemption code below at any partner pickup location and pay on collection.
                                @else
                                    Present the redemption code below at any partner pickup location to collect your order.
                                @endif
                            </p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#ECFDF5;border:1px dashed #0D5C2E;border-radius:10px;margin:0 0 20px;">
                                <tr>
                                    <td align="center" style="padding:18px;">
                                        <div style="font-size:11px;color:#6B7280;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Redemption Code</div>
                                        <div style="font-size:24px;font-weight:800;letter-spacing:3px;color:#0D5C2E;">{{ $order->order_code }}</div>
                                    </td>
                                </tr>
                            </table>

                            <table width="100%" cellpadding="6" cellspacing="0" style="font-size:13px;color:#374151;">
                                <tr><td style="color:#6B7280;">Order Total</td><td align="right"><strong>{{ getAmount($order->total_amount) }}</strong></td></tr>
                                <tr><td style="color:#6B7280;">Payment Method</td><td align="right">{{ $order->payment_method === 'paystack' ? 'Paid Online' : 'Cash on Pickup' }}</td></tr>
                                <tr><td style="color:#6B7280;">State</td><td align="right">{{ $order->state->name ?? '-' }}</td></tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:18px 30px;background:#FAFBFC;border-top:1px solid #E5E9EF;">
                            <p style="font-size:11px;color:#9CA3AF;margin:0;">If you did not place this order, please ignore this email.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>

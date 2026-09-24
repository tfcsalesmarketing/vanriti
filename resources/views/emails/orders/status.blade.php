<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $store }} — {{ $title }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <style>
        table, td { border-collapse: collapse !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    </style>
    <![endif]-->
    <style>
        body { margin: 0; padding: 0; width: 100%; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table { border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; line-height: 100%; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        a { color: #6F8736; }
        .btn a { display: inline-block; text-decoration: none; }
    </style>
</head>
<body style="margin:0; padding:0; width:100%; background-color:#F7F4EA; -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; font-family: Inter, 'Segoe UI', Arial, Helvetica, sans-serif;">
    <span style="display:none !important; visibility:hidden; mso-hide:all; font-size:1px; color:#F7F4EA; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
        {{ $title }} &mdash; order {{ $order->order_number }}. {{ $intro }}
    </span>

    <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#F7F4EA;">
        <tr>
            <td align="center" style="padding:32px 16px;">

                <!-- Masthead -->
                <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="max-width:600px; margin:0 auto;">
                    <tr>
                        <td align="center" style="padding-bottom:24px;">
                            <img src="{{ $logo }}" alt="{{ $store }}" width="160" height="auto" style="display:inline-block; width:160px; max-width:160px; height:auto; border:0; outline:none; text-decoration:none;">
                        </td>
                    </tr>
                </table>

                <!-- Main Card -->
                <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="max-width:600px; margin:0 auto; background-color:#FFFFFF; border-radius:18px; overflow:hidden; box-shadow:0 12px 40px rgba(38,61,37,0.08);">
                    <tr>
                        <td align="center" style="background-color:#263D25; padding:36px 40px 30px 40px;">
                            <div style="font-size:11px; letter-spacing:3px; color:#B58A3A; font-weight:600; text-transform:uppercase;">Pure By Nature</div>
                            <h1 style="margin:10px 0 0 0; font-size:26px; line-height:1.3; color:#F7F4EA; font-weight:700;">{{ $title }}</h1>
                            <div style="margin-top:8px; font-size:13px; color:#C7D3C2;">Order #{{ $order->order_number }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 40px 20px 40px;">

                            <p style="margin:0 0 16px 0; font-size:16px; line-height:1.6; color:#28322A;">Hi {{ $name }},</p>

                            <p style="margin:0 0 16px 0; font-size:15px; line-height:1.7; color:#28322A;">{{ $intro }}</p>

                            @if (!empty($note) || $status === 'payment_failed')
                                <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#EEF2E3; border-radius:14px; margin:0 0 24px 0;">
                                    <tr>
                                        <td style="padding:18px 22px; font-size:13px; line-height:1.6; color:#28322A;">
                                            <strong style="color:#263D25;">{{ $status === 'payment_failed' ? '&#128477; No amount was charged.' : '&#128203; Important note.' }}</strong><br>
                                            {{ $note ?? 'Your payment was not completed, so your card was not debited. You can place your order again whenever you are ready.' }}
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if ($shipment && in_array($status, ['shipped', 'out_for_delivery'], true))
                                <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#EEF2E3; border-radius:14px; margin:0 0 24px 0;">
                                    <tr>
                                        <td style="padding:18px 22px; font-size:13px; line-height:1.7; color:#28322A;">
                                            <strong style="color:#263D25;">&#128666; Your tracking details</strong>
                                            @if (!empty($shipment->courier))<br>Courier: <strong>{{ $shipment->courier }}</strong>@endif
                                            @if (!empty($shipment->awb_number) || !empty($shipment->tracking_number))<br>Tracking / AWB: <strong>{{ $shipment->awb_number ?: $shipment->tracking_number }}</strong>@endif
                                            @if (!empty($shipment->estimated_delivery))<br>Estimated delivery: <strong>{{ $shipment->estimated_delivery->format('d M Y') }}</strong>@endif
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <!-- Order summary -->
                            <div style="font-size:11px; letter-spacing:2px; text-transform:uppercase; color:#263D25; font-weight:700; margin:0 0 12px 0;">Order Summary</div>
                            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0">
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td style="padding:10px 0; border-bottom:1px solid #EEE9DC; font-size:14px; line-height:1.5; color:#28322A;">
                                            <strong>{{ $item->product_name }}</strong><br>
                                            <span style="font-size:12px; color:#6B736B;">Qty {{ $item->quantity }} &times; {{ format_price($item->unit_price) }}</span>
                                        </td>
                                        <td align="right" style="padding:10px 0; border-bottom:1px solid #EEE9DC; font-size:14px; color:#28322A; white-space:nowrap;">
                                            {{ format_price($item->total_price) }}
                                        </td>
                                    </tr>
                                @endforeach
                                <tr>
                                    <td style="padding:10px 0; font-size:13px; color:#28322A;">Subtotal</td>
                                    <td align="right" style="padding:10px 0; font-size:13px; color:#28322A; white-space:nowrap;">{{ format_price($order->subtotal) }}</td>
                                </tr>
                                @if (((float) $order->coupon_discount) > 0)
                                    <tr>
                                        <td style="padding:6px 0; font-size:13px; color:#28322A;">Coupon discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</td>
                                        <td align="right" style="padding:6px 0; font-size:13px; color:#6F8736; white-space:nowrap;">&minus; {{ format_price($order->coupon_discount) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="padding:6px 0; font-size:13px; color:#28322A;">Delivery</td>
                                    <td align="right" style="padding:6px 0; font-size:13px; color:#28322A; white-space:nowrap;">
                                        {{ ((float) ($order->shipping_charge ?? 0)) > 0 ? format_price($order->shipping_charge) : 'FREE' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; font-size:13px; color:#28322A;">GST</td>
                                    <td align="right" style="padding:6px 0; font-size:13px; color:#28322A; white-space:nowrap;">{{ format_price($order->tax_amount ?? 0) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 0 0 0; font-size:15px; font-weight:700; color:#263D25; border-top:2px solid #263D25;">Total</td>
                                    <td align="right" style="padding:14px 0 0 0; font-size:15px; font-weight:700; color:#263D25; border-top:2px solid #263D25; white-space:nowrap;">{{ format_price($order->grand_total) }}</td>
                                </tr>
                            </table>

                            @if ($status === 'payment_failed')
                                <p style="margin:24px 0 0 0; font-size:13px; line-height:1.7; color:#6B736B;">
                                    Need help? Our support team is one email away at
                                    <a href="mailto:{{ $supportEmail }}" style="color:#6F8736; font-weight:600; text-decoration:none;">{{ $supportEmail }}</a>.
                                </p>
                            @endif

                            <!-- Button -->
                            <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="margin:28px 0 4px 0;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="btn">
                                            <tr>
                                                <td align="center" style="border-radius:999px; background-color:#B58A3A;">
                                                    <a href="{{ $ctaUrl }}" target="_blank" rel="noopener"
                                                       style="display:inline-block; padding:15px 44px; border-radius:999px; background-color:#B58A3A; color:#FFFFFF !important; font-size:15px; font-weight:600; letter-spacing:0.4px; text-decoration:none;">
                                                        {{ $ctaText }}
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:16px 0 0 0; font-size:13px; line-height:1.7; color:#6B736B;">
                                You can always view the latest status under
                                <a href="{{ route('account.orders') }}" style="color:#6F8736; font-weight:600; text-decoration:underline;">your orders</a>,
                                or track any order on the <a href="{{ route('track') }}" style="color:#6F8736; font-weight:600; text-decoration:underline;">tracking page</a>.
                            </p>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td height="1" style="background-color:#E6E2D6;"><div style="height:1px; line-height:1px; font-size:1px;">&nbsp;</div></td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding:28px 40px 36px 40px;">
                            <div style="font-size:12px; letter-spacing:2px; text-transform:uppercase; color:#263D25; font-weight:700; margin-bottom:10px;">{{ strtoupper($store) }}</div>
                            <div style="font-size:13px; line-height:1.7; color:#6B736B;">
                                Pure by nature &middot; crafted with care for mind, body &amp; soul.<br>
                                Questions? Email us at
                                <a href="mailto:{{ $supportEmail }}" style="color:#6F8736; font-weight:600; text-decoration:none;">{{ $supportEmail }}</a>
                            </div>
                            <div style="font-size:12px; color:#6B736B; margin-top:14px;">&copy; {{ date('Y') }} {{ $store }}. All rights reserved.</div>
                        </td>
                    </tr>
                </table>

                <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="max-width:600px; margin:0 auto;">
                    <tr>
                        <td align="center" style="padding:20px 16px 0 16px; font-size:11px; line-height:1.6; color:#6B736B;">
                            You are receiving this email because you placed order {{ $order->order_number }} on {{ $store }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
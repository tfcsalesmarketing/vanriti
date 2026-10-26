@php
    $totalOrders = $orders->count();
    $totalCodAmount = 0.0;
    $codCount = 0;
    $prepaidCount = 0;

    foreach ($orders as $o) {
        if ($o->payment_method === 'cod') {
            $totalCodAmount += (float) $o->grand_total;
            $codCount++;
        } else {
            $prepaidCount++;
        }
    }

    $manifestRef = 'MNF-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Courier Handover Manifest - {{ $manifestRef }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Libre+Barcode+128&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            color: #0f172a;
            background: #f8fafc;
            padding: 24px;
            font-size: 11px;
            line-height: 1.4;
        }
        .manifest-actions {
            max-width: 900px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }
        .btn-primary { background: #263D25; color: #fff; }
        .btn-outline { background: #fff; color: #334155; border: 1px solid #cbd5e1; }

        .manifest-card {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 24px 28px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #263D25;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .brand-title { font-size: 18px; font-weight: 800; color: #263D25; text-transform: uppercase; }
        .doc-title { text-align: right; }
        .doc-title h1 { font-size: 16px; font-weight: 800; color: #0f172a; }
        .doc-title .meta { font-size: 10px; color: #64748b; font-weight: 600; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 16px;
        }
        .stat-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 8px 12px;
        }
        .stat-box span { font-size: 9px; text-transform: uppercase; color: #64748b; font-weight: 600; display: block; }
        .stat-box strong { font-size: 14px; font-weight: 800; color: #263D25; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #263D25;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            padding: 6px 8px;
            text-align: left;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
        }
        tr:nth-child(even) td { background-color: #fafbfd; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }

        .barcode-text {
            font-family: monospace;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #0f172a;
        }

        .sign-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #cbd5e1;
        }
        .sign-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
        }
        .sign-box h4 { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #475569; margin-bottom: 8px; }
        .sign-line {
            height: 35px;
            border-bottom: 1px dashed #94a3b8;
            margin-bottom: 8px;
        }
        .sign-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 9px; color: #475569; }

        @media print {
            body { background: #fff; padding: 0; }
            .manifest-actions { display: none !important; }
            .manifest-card { border: none; box-shadow: none; padding: 0; width: 100%; max-width: 100%; }
        }
    </style>
</head>
<body>

    <div class="manifest-actions">
        <a href="{{ route('admin.orders.index') }}" class="btn btn-outline">&larr; Back to Orders</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ Print Manifest</button>
    </div>

    <div class="manifest-card">
        <div class="header">
            <div>
                <div class="brand-title">{{ setting('company_name', store_name()) }}</div>
                <div style="font-size: 10px; color: #64748b;">Warehouse Dispatch Center &bull; {{ setting('store_city', 'Gurugram') }}, {{ setting('store_state', 'Haryana') }}</div>
            </div>
            <div class="doc-title">
                <h1>COURIER HANDOVER MANIFEST</h1>
                <div class="meta">{{ $manifestRef }} &bull; {{ now()->format('d M Y, h:i A') }}</div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-box">
                <span>Total Packages</span>
                <strong>{{ $totalOrders }}</strong>
            </div>
            <div class="stat-box">
                <span>Prepaid Orders</span>
                <strong>{{ $prepaidCount }}</strong>
            </div>
            <div class="stat-box">
                <span>COD Orders</span>
                <strong>{{ $codCount }}</strong>
            </div>
            <div class="stat-box">
                <span>Total COD to Collect</span>
                <strong>{{ format_price($totalCodAmount) }}</strong>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 25px;" class="text-center">#</th>
                    <th style="width: 100px;">Order No.</th>
                    <th style="width: 130px;">AWB / Tracking</th>
                    <th style="width: 100px;">Courier</th>
                    <th>Destination &amp; Customer</th>
                    <th style="width: 60px;" class="text-center">Items</th>
                    <th style="width: 90px;" class="text-end">Payment</th>
                    <th style="width: 40px;" class="text-center">Ack</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $idx => $order)
                    @php
                        $shipment = $order->shipments->first();
                        $awb = $shipment?->awb_number ?: ($shipment?->tracking_number ?: ($shipment?->shipmojo_order_id ?: '—'));
                        $courier = $shipment?->courier ?: 'ShipMojo Standard';
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td><strong>{{ $order->order_number }}</strong></td>
                        <td><span class="barcode-text">{{ $awb }}</span></td>
                        <td>{{ $courier }}</td>
                        <td>
                            <strong>{{ $order->shipping_name }}</strong>
                            <span style="color: #64748b; font-size: 9px; display: block;">{{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_pincode }}</span>
                        </td>
                        <td class="text-center">{{ $order->items->sum('quantity') }}</td>
                        <td class="text-end">
                            @if ($order->payment_method === 'cod')
                                <strong style="color: #b45309;">COD: {{ format_price($order->grand_total) }}</strong>
                            @else
                                <span style="color: #15803d; font-weight: 600;">Prepaid</span>
                            @endif
                        </td>
                        <td class="text-center">[ &check; ]</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No orders selected for manifest generation.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="sign-grid">
            <div class="sign-box">
                <h4>Vanriti Warehouse Dispatcher</h4>
                <div class="sign-line"></div>
                <div class="sign-meta">
                    <div>Name: <strong>{{ auth('admin')->user()?->name ?? 'Dispatch Staff' }}</strong></div>
                    <div>Date: <strong>{{ now()->format('d M Y') }}</strong></div>
                </div>
            </div>
            <div class="sign-box">
                <h4>Courier Pickup Executive (Driver / Rider)</h4>
                <div class="sign-line"></div>
                <div class="sign-meta">
                    <div>Rider Name: _________________</div>
                    <div>Signature: _________________</div>
                    <div>Phone: ______________________</div>
                    <div>Vehicle No: _________________</div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

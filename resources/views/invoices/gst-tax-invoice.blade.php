@php
    $items = $order->items;
    $isIntra = $order->isIntraState();
    $sellerName = setting('company_name', store_name());
    $sellerAddress = setting('store_address', 'Vanriti Luxury Wellness Pvt Ltd, Plot 42, Sector 18, Udyog Vihar');
    $sellerCity = setting('store_city', 'Gurugram');
    $sellerState = setting('store_state', 'Haryana');
    $sellerPincode = setting('store_pincode', '122015');
    $sellerGstin = setting('gstin', '06AAAAA0000A1Z5');
    $sellerPan = setting('company_pan', 'AAAAA0000A');
    $sellerPhone = setting('support_phone', '+91 98765 43210');
    $sellerEmail = setting('support_email', 'care@vanriti.com');

    // Amount in words helper
    $numberToWords = function ($number) {
        $no = floor($number);
        $point = round($number - $no, 2) * 100;
        $hundred = null;
        $digits_1 = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $digits_2 = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        $digits = ['', 'Hundred', 'Thousand', 'Lakh', 'Crore'];
        $str = [];
        $i = 0;
        while ($i < 5 && $no > 0) {
            $divider = ($i == 1 || $i == 0) ? 10 : 100;
            if ($i == 0) { $divider = 100; }
            elseif ($i == 1) { $divider = 10; }
            $number = floor($no % $divider);
            $no = floor($no / $divider);
            $i += ($divider == 10) ? 1 : 1;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str [] = ($number < 20) ? $digits_1[$number] . " " . ($digits[$counter] ?? '') . $plural . " " . $hundred
                    : $digits_2[floor($number / 10)] . " " . $digits_1[$number % 10] . " " . ($digits[$counter] ?? '') . $plural . " " . $hundred;
            } else $str[] = null;
        }
        $str = array_reverse($str);
        $result = implode('', $str);
        $points = ($point) ? "." . $digits_1[floor($point / 10)] . " " . $digits_1[$point = $point % 10] : '';
        return trim($result) ? trim($result) . ' Rupees Only' : 'Zero Rupees Only';
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GST Tax Invoice #INV-{{ $order->order_number }} - {{ store_name() }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            color: #1a202c;
            background: #f8fafc;
            padding: 24px;
            font-size: 12px;
            line-height: 1.5;
        }
        .invoice-actions {
            max-width: 800px;
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
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.9; }
        .btn-primary { background: #263D25; color: #fff; }
        .btn-outline { background: #fff; color: #334155; border: 1px solid #cbd5e1; }

        .invoice-card {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 32px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 20px;
            border-bottom: 2px solid #263D25;
            margin-bottom: 20px;
        }
        .brand-title {
            font-size: 22px;
            font-weight: 800;
            color: #263D25;
            letter-spacing: -0.5px;
            text-transform: uppercase;
        }
        .brand-tagline { font-size: 10px; color: #64748b; letter-spacing: 1px; text-transform: uppercase; }
        .tax-title {
            text-align: right;
        }
        .tax-title h1 {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        .tax-title .copy-tag {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        .meta-box {
            background: #f8fafc;
            border: 1px solid #edf2f7;
            border-radius: 6px;
            padding: 12px 14px;
        }
        .meta-box h3 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #263D25;
            font-weight: 700;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }
        .meta-box p { font-size: 11px; color: #334155; margin-bottom: 2px; }

        .details-bar {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            background: #f1f5f9;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 24px;
        }
        .details-col span { display: block; font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 600; }
        .details-col strong { font-size: 12px; color: #0f172a; font-weight: 700; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #263D25;
            color: #ffffff;
            font-size: 10px;
            text-transform: uppercase;
            font-weight: 600;
            padding: 8px 10px;
            text-align: left;
            letter-spacing: 0.3px;
        }
        th.text-end, td.text-end { text-align: right; }
        th.text-center, td.text-center { text-align: center; }
        td {
            padding: 9px 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            color: #334155;
        }
        tr:nth-child(even) td { background-color: #fafbfd; }

        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 24px;
        }
        .totals-table {
            width: 320px;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }
        .totals-table tr.grand-total td {
            font-size: 13px;
            font-weight: 800;
            color: #263D25;
            border-top: 2px solid #263D25;
            border-bottom: 2px solid #263D25;
            background: #f0fdf4;
        }

        .words-box {
            background: #f8fafc;
            border-left: 3px solid #263D25;
            padding: 8px 12px;
            margin-bottom: 24px;
            font-size: 11px;
        }
        .words-box strong { color: #0f172a; }

        .tax-split-table {
            margin-bottom: 24px;
            font-size: 10px;
        }
        .tax-split-table th { background: #475569; padding: 6px 8px; font-size: 9px; }
        .tax-split-table td { padding: 6px 8px; font-size: 10px; }

        .footer-note {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            align-items: flex-end;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }
        .terms h4 { font-size: 10px; font-weight: 700; text-transform: uppercase; color: #475569; margin-bottom: 4px; }
        .terms p { font-size: 9px; color: #64748b; line-height: 1.4; }
        .signatory {
            text-align: center;
        }
        .signature-box {
            height: 45px;
            border-bottom: 1px dashed #94a3b8;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #263D25;
            font-weight: 700;
            font-style: italic;
            font-size: 13px;
        }
        .signatory span { font-size: 10px; color: #64748b; display: block; font-weight: 600; }

        @media print {
            body { background: #fff; padding: 0; }
            .invoice-actions { display: none !important; }
            .invoice-card {
                border: none;
                box-shadow: none;
                padding: 0;
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    {{-- Top Action Toolbar --}}
    <div class="invoice-actions">
        <a href="javascript:history.back()" class="btn btn-outline">
            &larr; Back
        </a>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            🖨️ Print / Save as PDF
        </button>
    </div>

    <div class="invoice-card">
        {{-- Header --}}
        <div class="header">
            <div>
                <div class="brand-title">{{ $sellerName }}</div>
                <div class="brand-tagline">Pure Ayurvedic Luxury &bull; Crafted Elegance</div>
                <p style="font-size: 10px; color: #475569; margin-top: 4px;">
                    {{ $sellerAddress }}, {{ $sellerCity }}, {{ $sellerState }} - {{ $sellerPincode }}<br>
                    <strong>GSTIN:</strong> {{ $sellerGstin }} &bull; <strong>PAN:</strong> {{ $sellerPan }}<br>
                    <strong>Contact:</strong> {{ $sellerPhone }} | {{ $sellerEmail }}
                </p>
            </div>
            <div class="tax-title">
                <h1>TAX INVOICE</h1>
                <div class="copy-tag">Original for Recipient</div>
                <div style="font-size: 11px; font-weight: 700; color: #263D25; margin-top: 4px;">
                    INV-{{ $order->order_number }}
                </div>
            </div>
        </div>

        {{-- Meta 4-column summary bar --}}
        <div class="details-bar">
            <div class="details-col">
                <span>Invoice Date</span>
                <strong>{{ $order->created_at->format('d M Y') }}</strong>
            </div>
            <div class="details-col">
                <span>Order Reference</span>
                <strong>{{ $order->order_number }}</strong>
            </div>
            <div class="details-col">
                <span>Payment Mode</span>
                <strong>{{ strtoupper($order->payment_method === 'cod' ? 'Cash on Delivery' : ($order->payment_method ?: 'Prepaid')) }}</strong>
            </div>
            <div class="details-col">
                <span>Place of Supply</span>
                <strong>{{ $order->shipping_state ?: 'India' }} ({{ $order->isIntraState() ? 'Intra-State' : 'Inter-State' }})</strong>
            </div>
        </div>

        {{-- Billed To & Shipped To Grid --}}
        <div class="meta-grid">
            <div class="meta-box">
                <h3>Billed To (Customer)</h3>
                <p><strong>{{ $order->billing_name ?: $order->shipping_name }}</strong></p>
                <p>{{ $order->billing_address_line1 ?: $order->shipping_address_line1 }}</p>
                @if ($order->billing_address_line2 || $order->shipping_address_line2)
                    <p>{{ $order->billing_address_line2 ?: $order->shipping_address_line2 }}</p>
                @endif
                <p>{{ $order->billing_city ?: $order->shipping_city }}, {{ $order->billing_state ?: $order->shipping_state }} - {{ $order->billing_pincode ?: $order->shipping_pincode }}</p>
                <p><strong>Phone:</strong> {{ $order->billing_mobile ?: $order->shipping_mobile }}</p>
            </div>
            <div class="meta-box">
                <h3>Shipped To (Delivery Address)</h3>
                <p><strong>{{ $order->shipping_name }}</strong></p>
                <p>{{ $order->shipping_address_line1 }}</p>
                @if ($order->shipping_address_line2)
                    <p>{{ $order->shipping_address_line2 }}</p>
                @endif
                <p>{{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_pincode }}</p>
                <p><strong>Phone:</strong> {{ $order->shipping_mobile }}</p>
            </div>
        </div>

        {{-- Items Table --}}
        <table>
            <thead>
                <tr>
                    <th style="width: 30px;" class="text-center">#</th>
                    <th>Product Description</th>
                    <th style="width: 70px;" class="text-center">HSN</th>
                    <th style="width: 45px;" class="text-center">Qty</th>
                    <th style="width: 80px;" class="text-end">Unit Price</th>
                    <th style="width: 80px;" class="text-end">Taxable</th>
                    <th style="width: 60px;" class="text-center">GST %</th>
                    <th style="width: 80px;" class="text-end">Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $idx => $item)
                    @php
                        $rate = (float) ($item->gst_rate ?: 18);
                        $taxable = $item->taxable();
                        $lineTotal = (float) ($item->total_price ?: $item->subtotal());
                    @endphp
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $item->product_name }}</strong>
                            @if ($item->variant_title)
                                <span style="color: #64748b; font-size: 10px; display: block;">{{ $item->variant_title }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->product?->hsn_code ?: '3004' }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">{{ format_price($item->unit_price) }}</td>
                        <td class="text-end">{{ format_price($taxable) }}</td>
                        <td class="text-center">{{ $rate }}%</td>
                        <td class="text-end"><strong>{{ format_price($lineTotal) }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals Calculation Block --}}
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="text-muted">Total Taxable Value:</td>
                    <td class="text-end"><strong>{{ format_price($order->taxable_value) }}</strong></td>
                </tr>
                @if ($isIntra)
                    <tr>
                        <td class="text-muted">CGST (Central Tax):</td>
                        <td class="text-end">{{ format_price($order->cgst_amount) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">SGST (State Tax):</td>
                        <td class="text-end">{{ format_price($order->sgst_amount) }}</td>
                    </tr>
                @else
                    <tr>
                        <td class="text-muted">IGST (Integrated Tax):</td>
                        <td class="text-end">{{ format_price($order->igst_amount) }}</td>
                    </tr>
                @endif
                @if ((float) $order->discount_amount > 0)
                    <tr>
                        <td class="text-muted">Coupon Discount ({{ $order->coupon_code }}):</td>
                        <td class="text-end text-success">-{{ format_price($order->discount_amount) }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="text-muted">Delivery Charges:</td>
                    <td class="text-end">{{ (float) $order->shipping_charge > 0 ? format_price($order->shipping_charge) : 'FREE' }}</td>
                </tr>
                <tr class="grand-total">
                    <td>Invoice Total:</td>
                    <td class="text-end">{{ format_price($order->grand_total) }}</td>
                </tr>
            </table>
        </div>

        {{-- Amount in words --}}
        <div class="words-box">
            Amount in Words: <strong>{{ $numberToWords((float) $order->grand_total) }}</strong>
        </div>

        {{-- Bottom Footer with T&C and Signature block --}}
        <div class="footer-note">
            <div class="terms">
                <h4>Terms &amp; Conditions</h4>
                <p>
                    1. Goods once sold are covered under Vanriti's 7-day return policy as per store guidelines.<br>
                    2. This is a computer generated invoice issued in accordance with Rule 46 of CGST Rules, 2017.<br>
                    3. For questions regarding this invoice, contact our concierge at care@vanriti.com.
                </p>
            </div>
            <div class="signatory">
                <div class="signature-box">Vanriti Luxury Wellness</div>
                <span>Authorized Signatory</span>
            </div>
        </div>
    </div>

</body>
</html>

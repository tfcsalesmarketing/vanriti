@php
    $items = $order->items->loadMissing('product');
    $isIntra = $order->isIntraState();

    // Store & Company details from settings
    $storeName = setting('store_name', 'VANRITI');
    $companyName = setting('company_name', 'Eduse Marketing Private Limited');
    $storeAddress = setting('store_address', "12, 1st Floor, Ramnik Complex, Tikona Park,\nNIT-1, Faridabad, Haryana - 121001, India");
    $storePhone = setting('store_phone', '+91 7888673594');
    $storeEmail = setting('support_email', 'support@vanriti.com');
    $storeWebsite = setting('store_website', 'www.vanriti.com');
    $gstin = setting('gst_number', setting('gstin', '06AXYPG2957R1ZD'));

    // Logo image resolving
    $logoSetting = setting('store_logo');
    $logoSrc = null;
    if ($logoSetting && file_exists(public_path('storage/' . $logoSetting))) {
        $logoSrc = ($isPdf ?? false) ? public_path('storage/' . $logoSetting) : asset('storage/' . $logoSetting);
    } elseif (file_exists(public_path('images/logo.png'))) {
        $logoSrc = ($isPdf ?? false) ? public_path('images/logo.png') : asset('images/logo.png');
    } elseif (file_exists(public_path('images/logo.webp'))) {
        $logoSrc = ($isPdf ?? false) ? public_path('images/logo.webp') : asset('images/logo.webp');
    }

    // Totals calculations without delivery charges (as explicitly requested)
    $itemsTotalQuantity = $items->sum('quantity');
    $itemsSubtotal = 0.0;
    $totalTaxable = 0.0;
    $totalGst = 0.0;

    foreach ($items as $item) {
        $itemTotal = (float) ($item->total_price ?: ($item->unit_price * $item->quantity));
        $taxableVal = $item->taxable();
        $taxVal = $item->tax();
        
        $itemsSubtotal += $itemTotal;
        $totalTaxable += $taxableVal;
        $totalGst += $taxVal;
    }

    $discountAmount = (float) ($order->discount_amount ?? 0);
    // Invoice Total strictly removes delivery charges
    $invoiceGrandTotal = max(0, $itemsSubtotal - $discountAmount);

    // Number to words converter in Indian Currency
    $numberToWords = function ($number) {
        $no = (int) floor($number);
        $point = (int) round(($number - $no) * 100);
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
            $number = $no % $divider;
            $no = (int) floor($no / $divider);
            $i += ($divider == 10) ? 1 : 1;
            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
                $str [] = ($number < 20) ? $digits_1[$number] . " " . ($digits[$counter] ?? '') . $plural . " " . $hundred
                    : $digits_2[(int) floor($number / 10)] . " " . $digits_1[$number % 10] . " " . ($digits[$counter] ?? '') . $plural . " " . $hundred;
            } else $str[] = null;
        }
        $str = array_reverse($str);
        $result = implode('', array_filter($str));
        $points = ($point) ? "." . $digits_1[(int) floor($point / 10)] . " " . $digits_1[$point % 10] : '';
        return trim($result) ? trim($result) . ' Rupees Only' : 'Zero Rupees Only';
    };

    $isSameAddress = ($order->is_billing_same ?? false) || (
        trim(strtolower((string)$order->shipping_address_line1)) === trim(strtolower((string)($order->billing_address_line1 ?: $order->shipping_address_line1))) &&
        trim(strtolower((string)$order->shipping_city)) === trim(strtolower((string)($order->billing_city ?: $order->shipping_city)))
    );

    $customerEmail = $order->user?->email ?: ($order->guest_email ?: 'care@vanriti.com');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - INV-{{ $order->order_number }} - {{ $storeName }}</title>
    @unless($isPdf ?? false)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Cinzel:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @endunless

    <style>
        :root {
            --vr-forest: #14361E;
            --vr-forest-dark: #0D2614;
            --vr-sage-light: #EBF4EE;
            --vr-sage-border: #D1E4D6;
            --vr-sage-pill: #E1EFE5;
            --vr-accent-gold: #C2A267;
            --vr-text-main: #1C2826;
            --vr-text-muted: #5A6D63;
            --vr-border-light: #E5EBE6;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, 'DejaVu Sans', sans-serif;
            color: #1C2826;
            background: {{ ($isPdf ?? false) ? '#ffffff' : '#F4F6F4' }};
            padding: {{ ($isPdf ?? false) ? '0' : '20px 14px' }};
            font-size: 10px;
            line-height: 1.35;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Top Action Buttons (Hidden when printing or in PDF) */
        .invoice-actions {
            max-width: 800px;
            margin: 0 auto 14px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            font-size: 12.5px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-action-primary {
            background: #14361E;
            color: #ffffff;
            border: 1px solid #14361E;
            box-shadow: 0 2px 4px rgba(20, 54, 30, 0.2);
        }
        .btn-action-primary:hover {
            background: #0D2614;
            color: #ffffff;
        }
        .btn-action-outline {
            background: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-action-outline:hover {
            background: #f8fafc;
        }

        /* Invoice Container */
        .invoice-paper {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: {{ ($isPdf ?? false) ? '0' : '10px' }};
            box-shadow: {{ ($isPdf ?? false) ? 'none' : '0 4px 18px rgba(0, 0, 0, 0.06)' }};
            overflow: hidden;
            position: relative;
            border: {{ ($isPdf ?? false) ? 'none' : '1px solid #e5ebe7' }};
        }

        .invoice-content {
            padding: {{ ($isPdf ?? false) ? '12px 16px 10px 16px' : '22px 26px 18px 26px' }};
            position: relative;
        }

        /* Header Table */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-bottom: 12px;
        }
        .header-brand-cell {
            vertical-align: top;
            width: 68%;
        }
        .header-tagline-cell {
            vertical-align: top;
            width: 32%;
            text-align: right;
            padding-top: 4px;
        }
        .brand-logo-img {
            max-width: 140px;
            max-height: 65px;
            vertical-align: middle;
        }
        .company-brand-name {
            font-size: 17px;
            font-weight: 800;
            color: #14361E;
            line-height: 1.15;
            letter-spacing: 0.5px;
        }
        .company-legal-name {
            font-size: 10.5px;
            font-weight: 700;
            color: #2F3E34;
            margin-bottom: 3px;
        }
        .company-address {
            font-size: 9.5px;
            color: #556B5C;
            line-height: 1.35;
            margin-bottom: 4px;
        }
        .company-contacts {
            font-size: 9.5px;
            color: #3C4E43;
            line-height: 1.35;
        }
        .company-contacts strong {
            color: #14361E;
        }
        .pure-care-script {
            font-family: 'Caveat', cursive, 'DejaVu Sans', serif;
            font-size: 24px;
            color: #37533B;
            line-height: 1.05;
        }
        .pure-care-script span {
            font-size: 28px;
            color: #2B462F;
            display: block;
        }

        /* TAX INVOICE Banner Table */
        .tax-invoice-banner-table {
            width: 100%;
            border-collapse: collapse;
            background: #EBF4EE;
            border: 1px solid #D1E4D6;
            border-radius: 6px;
            margin-bottom: 12px;
        }
        .tax-invoice-banner-table td {
            padding: 8px 14px;
        }
        .tax-invoice-title-block h1 {
            font-family: 'Cinzel', serif, 'DejaVu Sans';
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 1px;
            color: #14361E;
            line-height: 1;
            margin-bottom: 2px;
        }
        .tax-invoice-title-block p {
            font-size: 9.5px;
            color: #4C6053;
            font-weight: 500;
        }
        .invoice-meta-table {
            border-collapse: collapse;
            margin-left: auto;
        }
        .invoice-meta-table td {
            padding: 1.5px 4px !important;
            font-size: 9.5px;
        }
        .invoice-meta-table td.label-col {
            font-weight: 600;
            color: #3D5044;
            white-space: nowrap;
        }
        .invoice-meta-table td.colon-col {
            font-weight: 600;
            color: #6B8072;
            padding: 1.5px 2px !important;
        }
        .invoice-meta-table td.val-col {
            font-weight: 700;
            color: #1A2E21;
            white-space: nowrap;
        }

        /* Two-Column Addresses Table */
        .addresses-layout-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-bottom: 12px;
        }
        .address-box {
            border: 1px solid #E5EBE6;
            border-radius: 6px;
            overflow: hidden;
            background: #ffffff;
        }
        .address-box-header {
            background: #E1EFE5;
            padding: 5px 10px;
            border-bottom: 1px solid #D1E4D6;
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #14361E;
            text-transform: uppercase;
        }
        .address-box-body {
            padding: 8px 10px;
            font-size: 9.5px;
            line-height: 1.35;
            color: #2F3E34;
        }
        .address-box-body .cust-name {
            font-size: 11px;
            font-weight: 800;
            color: #132418;
            margin-bottom: 2px;
        }
        .address-box-body .cust-lines {
            color: #495C50;
            margin-bottom: 4px;
        }

        /* Items Table */
        .items-table-wrap {
            margin-bottom: 12px;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #D7E2D9;
        }
        .invoice-items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        .invoice-items-table thead th {
            background: #14361E;
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 6px 6px;
            text-align: center;
            border: none;
            vertical-align: middle;
        }
        .invoice-items-table thead th.th-prod {
            text-align: left;
            padding-left: 10px;
        }
        .invoice-items-table tbody td {
            padding: 6px 6px;
            border-bottom: 1px solid #EAF0EC;
            text-align: center;
            color: #1F2E23;
            vertical-align: middle;
        }
        .invoice-items-table tbody td.td-idx {
            color: #6C8174;
            font-weight: 600;
            width: 24px;
        }
        .invoice-items-table tbody td.td-prod {
            text-align: left;
            padding-left: 10px;
        }
        .prod-title {
            font-weight: 700;
            color: #122416;
            font-size: 10px;
            line-height: 1.25;
        }
        .prod-sku {
            font-size: 8.5px;
            color: #687E71;
        }
        .prod-weight-tag {
            font-size: 8px;
            font-weight: 600;
            background: #EAF3EC;
            color: #184323;
            padding: 1px 4px;
            border-radius: 3px;
            display: inline-block;
        }
        .td-amount {
            font-weight: 700;
            color: #14361E;
            text-align: right !important;
            padding-right: 8px !important;
        }
        .text-end {
            text-align: right !important;
            padding-right: 8px !important;
        }

        /* Summary Layout Table (Left Terms vs Right Totals) */
        .summary-layout-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-bottom: 10px;
        }
        .summary-left-cell {
            width: 55%;
            vertical-align: top;
        }
        .summary-right-cell {
            width: 45%;
            vertical-align: top;
        }

        .summary-card-box {
            border: 1px solid #E5EBE6;
            border-radius: 6px;
            overflow: hidden;
            background: #ffffff;
            margin-bottom: 8px;
        }
        .summary-card-header {
            background: #F3F8F5;
            padding: 5px 10px;
            border-bottom: 1px solid #E5EBE6;
            font-size: 9px;
            font-weight: 800;
            color: #14361E;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }
        .summary-card-body {
            padding: 6px 10px;
            font-size: 9px;
            line-height: 1.35;
            color: #3B4E41;
        }
        .terms-conditions-list {
            margin: 0;
            padding-left: 14px;
            font-size: 8.5px;
            line-height: 1.35;
            color: #4C6053;
        }
        .terms-conditions-list li {
            margin-bottom: 2px;
        }
        .terms-highlight-computer {
            color: #14361E;
            font-weight: 700;
        }

        /* Totals Card */
        .totals-box {
            border: 1.5px solid #14361E;
            border-radius: 6px;
            overflow: hidden;
            background: #ffffff;
        }
        .summary-totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-totals-table td {
            padding: 4px 10px;
            font-size: 9.5px;
            color: #384C3E;
            border-bottom: 1px dashed #E5EDE7;
        }
        .summary-totals-table td.total-val {
            text-align: right;
            font-weight: 700;
            color: #112415;
            white-space: nowrap;
        }
        .grand-total-row {
            background: #14361E;
            color: #ffffff;
            padding: 8px 10px;
        }
        .grand-total-row table {
            width: 100%;
            border-collapse: collapse;
        }
        .grand-total-row td {
            color: #ffffff;
            font-weight: 800;
            font-size: 12px;
            border: none;
            padding: 0;
        }
        .grand-total-row td.gt-val {
            text-align: right;
            font-size: 14px;
            color: #C2A267;
        }

        /* Words strip */
        .words-strip {
            font-size: 9px;
            color: #3D5344;
            margin-bottom: 8px;
            background: #F8FCF9;
            border-left: 3px solid #14361E;
            padding: 4px 8px;
            border-radius: 4px;
        }

        /* Trust badges table */
        .trust-table {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-top: 4px;
            margin-bottom: 6px;
        }
        .trust-item-td {
            text-align: center;
            font-size: 8px;
            font-weight: 600;
            color: #3B5242;
            line-height: 1.2;
            padding: 2px 4px;
        }
        .thank-you-script-col {
            font-family: 'Caveat', cursive, 'DejaVu Sans', serif;
            font-size: 18px;
            color: #14361E;
            text-align: right;
            font-weight: 700;
        }

        /* Bottom Dark Bar */
        .invoice-bottom-bar {
            background: #14361E;
            color: #ffffff;
            padding: 6px 16px;
        }
        .invoice-bottom-bar table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-bottom-bar td {
            color: #D3E4D7;
            font-size: 8.5px;
            border: none;
            padding: 0;
        }
        .invoice-bottom-bar a {
            color: #D3E4D7;
            text-decoration: none;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .invoice-actions {
                display: none !important;
            }
            .invoice-paper {
                box-shadow: none !important;
                border: none !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }
            .invoice-content {
                padding: 10mm 8mm 6mm 8mm !important;
            }
            @page {
                size: A4 portrait;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    {{-- Screen Action Toolbar (hidden in PDF / print) --}}
    @unless($isPdf ?? false)
    <div class="invoice-actions">
        <a href="{{ auth('admin')->check() ? route('admin.orders.show', $order) : (auth()->check() ? route('account.orders') : url('/')) }}" class="btn-action btn-action-outline" id="btnBackInvoice">
            <i class="bi bi-arrow-left"></i> Back to Order
        </a>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="{{ auth('admin')->check() ? route('admin.orders.invoice', ['order' => $order->id, 'format' => 'pdf', 'download' => 1]) : route('account.order.invoice', ['order' => $order->id, 'format' => 'pdf', 'download' => 1]) }}" class="btn-action btn-action-outline" id="btnDownloadPdf" style="background:#ffffff; color:#14361E; border:1px solid #14361E;">
                <i class="bi bi-file-earmark-pdf"></i> Download PDF
            </a>
            <button type="button" class="btn-action btn-action-primary" id="btnPrintInvoice">
                <i class="bi bi-printer"></i> Print Invoice
            </button>
        </div>
    </div>
    @endunless

    {{-- Main Printable Invoice Card --}}
    <div class="invoice-paper">

        <div class="invoice-content">

            {{-- 1. Top Header (Table format for pixel-perfect A4 alignment) --}}
            <table class="header-table">
                <tr>
                    <td class="header-brand-cell">
                        <table style="border:none; border-collapse:collapse;">
                            <tr>
                                @if (!empty($logoSrc))
                                    <td style="vertical-align:middle; padding-right:16px;">
                                        <img src="{{ $logoSrc }}" alt="{{ $storeName }}" class="brand-logo-img">
                                    </td>
                                @endif
                                <td style="vertical-align:middle; border-left:1.5px solid #E2E8E2; padding-left:14px;">
                                    <div class="company-brand-name">{{ $storeName }}</div>
                                    <div class="company-legal-name">A brand of {{ $companyName }}</div>
                                    <div class="company-address">{!! nl2br(e($storeAddress)) !!}</div>
                                    <div class="company-contacts">
                                        <span>Ph: {{ $storePhone }} &bull; Email: {{ $storeEmail }} &bull; Web: {{ $storeWebsite }}</span><br>
                                        <span>GSTIN: <strong>{{ $gstin }}</strong></span>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td class="header-tagline-cell">
                        <div class="pure-care-script">
                            Pure<br>
                            <em>Care</em><br>
                            <span>Natural You <i>♡</i></span>
                        </div>
                    </td>
                </tr>
            </table>

            {{-- 2. TAX INVOICE Banner --}}
            <table class="tax-invoice-banner-table">
                <tr>
                    <td style="vertical-align:middle;" class="tax-invoice-title-block">
                        <h1>TAX INVOICE</h1>
                        <p>Thank you for choosing {{ $storeName }}. We appreciate your support.</p>
                    </td>
                    <td style="vertical-align:middle; text-align:right;">
                        <table class="invoice-meta-table">
                            <tr>
                                <td class="label-col">Invoice No.</td>
                                <td class="colon-col">:</td>
                                <td class="val-col">INV-{{ $order->order_number }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">Invoice Date</td>
                                <td class="colon-col">:</td>
                                <td class="val-col">{{ $order->created_at->format('d M Y') }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">Order No.</td>
                                <td class="colon-col">:</td>
                                <td class="val-col">#{{ $order->order_number }}</td>
                            </tr>
                            <tr>
                                <td class="label-col">Order Date</td>
                                <td class="colon-col">:</td>
                                <td class="val-col">{{ $order->created_at->format('d M Y') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- 3. Bill To & Shipping Address Two-Column Table --}}
            <table class="addresses-layout-table">
                <tr>
                    <td style="width:49%; vertical-align:top;">
                        <div class="address-box">
                            <div class="address-box-header">BILL TO</div>
                            <div class="address-box-body">
                                <div class="cust-name">{{ $order->billing_name ?: $order->shipping_name }}</div>
                                <div class="cust-lines">
                                    {{ $order->billing_address_line1 ?: $order->shipping_address_line1 }}<br>
                                    @if ($order->billing_address_line2 || $order->shipping_address_line2)
                                        {{ $order->billing_address_line2 ?: $order->shipping_address_line2 }}<br>
                                    @endif
                                    {{ $order->billing_city ?: $order->shipping_city }}, {{ $order->billing_state ?: $order->shipping_state }} - {{ $order->billing_pincode ?: $order->shipping_pincode }}<br>
                                    {{ $order->billing_country ?: ($order->shipping_country ?: 'India') }}
                                </div>
                                <div style="font-size:9px; color:#384A3E;">
                                    Ph: <strong>{{ $order->billing_mobile ?: $order->shipping_mobile }}</strong> &bull; Email: {{ $customerEmail }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="width:2%;"></td>
                    <td style="width:49%; vertical-align:top;">
                        <div class="address-box">
                            <div class="address-box-header">
                                SHIPPING ADDRESS
                                @if ($isSameAddress)
                                    <span style="float:right; font-size:8px; background:#CBE6D3; color:#174221; padding:1px 6px; border-radius:4px; text-transform:none;">Same as Billing</span>
                                @endif
                            </div>
                            <div class="address-box-body">
                                <div class="cust-name">{{ $order->shipping_name }}</div>
                                <div class="cust-lines">
                                    {{ $order->shipping_address_line1 }}<br>
                                    @if ($order->shipping_address_line2)
                                        {{ $order->shipping_address_line2 }}<br>
                                    @endif
                                    {{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_pincode }}<br>
                                    {{ $order->shipping_country ?: 'India' }}
                                </div>
                                <div style="font-size:9px; color:#384A3E;">
                                    Ph: <strong>{{ $order->shipping_mobile }}</strong> &bull; Email: {{ $customerEmail }}
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            {{-- 4. Products Table --}}
            <div class="items-table-wrap">
                <table class="invoice-items-table">
                    <thead>
                        <tr>
                            <th style="width:24px;">#</th>
                            <th class="th-prod">Product Details</th>
                            <th style="width:65px;">HSN</th>
                            <th style="width:75px;">Unit Price (₹)</th>
                            <th style="width:36px;">Qty</th>
                            <th style="width:65px;">Discount (₹)</th>
                            <th style="width:78px;">Taxable (₹)</th>
                            <th style="width:50px;">GST (%)</th>
                            <th style="width:75px;">GST Amount (₹)</th>
                            <th style="width:85px;" class="text-end">Total Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $idx => $item)
                            @php
                                $rate = (float) ($item->gst_rate ?: setting('default_gst_rate', 5));
                                $taxable = $item->taxable();
                                $taxAmt = $item->tax();
                                $lineTotal = (float) ($item->total_price ?: ($item->unit_price * $item->quantity));
                                $discount = (float) ($item->discount ?? 0);
                                $hsn = $item->product?->hsn_code ?: '330590';
                            @endphp
                            <tr>
                                <td class="td-idx">{{ $idx + 1 }}</td>
                                <td class="td-prod">
                                    <div class="prod-title">{{ $item->product_name }}</div>
                                    <div class="prod-sku">SKU: {{ $item->product?->sku ?: ($item->sku ?: 'VN-' . $item->product_id) }}
                                        @if ($item->product?->weight)
                                            &bull; <span class="prod-weight-tag">{{ $item->product->weight }} {{ $item->product->unit ?: 'gm' }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $hsn }}</td>
                                <td>{{ number_format((float) $item->unit_price, 2) }}</td>
                                <td style="font-weight:700;">{{ $item->quantity }}</td>
                                <td>{{ $discount > 0 ? number_format($discount, 2) : '0.00' }}</td>
                                <td>{{ number_format($taxable, 2) }}</td>
                                <td>{{ (int)$rate == $rate ? (int)$rate : $rate }}%</td>
                                <td>{{ number_format($taxAmt, 2) }}</td>
                                <td class="td-amount">{{ number_format($lineTotal, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- 5. Bottom Summary (Table layout for Payment/Terms vs Totals) --}}
            <table class="summary-layout-table">
                <tr>
                    <td class="summary-left-cell">
                        {{-- Payment Method Box --}}
                        <div class="summary-card-box">
                            <div class="summary-card-header">PAYMENT METHOD</div>
                            <div class="summary-card-body">
                                <table style="width:100%; border-collapse:collapse;">
                                    <tr>
                                        <td>
                                            <span style="color:#687E71; font-size:8.5px;">Paid via:</span>
                                            <div style="font-weight:700; color:#14361E;">
                                                @if ($order->payment_method === 'cod')
                                                    Cash on Delivery (COD)
                                                @elseif ($order->payment_method === 'razorpay')
                                                    Online Payment (Razorpay)
                                                @else
                                                    {{ ucwords(str_replace('_', ' ', $order->payment_method ?: 'Prepaid')) }}
                                                @endif
                                            </div>
                                        </td>
                                        <td style="text-align:right;">
                                            <span style="color:#687E71; font-size:8.5px;">Payment Status:</span>
                                            <div>
                                                @if (in_array(strtolower((string)$order->payment_status), ['paid', 'captured', 'success'], true))
                                                    <span style="background:#CDE8D4; color:#144420; padding:2px 7px; border-radius:4px; font-weight:700; font-size:8.5px;">Paid</span>
                                                @else
                                                    <span style="background:#FFF3CD; color:#856404; padding:2px 7px; border-radius:4px; font-weight:700; font-size:8.5px;">{{ ucfirst($order->payment_status ?: 'Pending') }}</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        {{-- Terms & Conditions Box --}}
                        <div class="summary-card-box">
                            <div class="summary-card-header">TERMS &amp; CONDITIONS</div>
                            <div class="summary-card-body">
                                <ul class="terms-conditions-list">
                                    <li><span class="terms-highlight-computer">This is a computer generated invoice and does not require a physical signature.</span></li>
                                    <li>Goods once sold will only be taken back as per our official Return &amp; Refund Policy.</li>
                                    <li>For order support, please reach out to {{ $storeEmail }} or {{ $storePhone }}.</li>
                                    <li>Issued by {{ $companyName }} ({{ $storeName }}). GSTIN: {{ $gstin }}.</li>
                                    <li>All disputes are subject to Faridabad, Haryana jurisdiction only.</li>
                                </ul>
                            </div>
                        </div>
                    </td>

                    <td style="width:3%;"></td>

                    <td class="summary-right-cell">
                        {{-- Totals Card (Delivery Charges Strictly Removed) --}}
                        <div class="totals-box">
                            <table class="summary-totals-table">
                                <tr>
                                    <td>Subtotal ({{ $itemsTotalQuantity }} {{ $itemsTotalQuantity > 1 ? 'items' : 'item' }})</td>
                                    <td class="total-val">₹ {{ number_format($itemsSubtotal, 2) }}</td>
                                </tr>
                                @if ($discountAmount > 0)
                                    <tr>
                                        <td style="color:#1B5628; font-weight:600;">Discount</td>
                                        <td class="total-val" style="color:#1B5628;">- ₹ {{ number_format($discountAmount, 2) }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td>Total Taxable Value</td>
                                    <td class="total-val">₹ {{ number_format($totalTaxable, 2) }}</td>
                                </tr>
                                @if ($isIntra)
                                    <tr>
                                        <td>CGST ({{ ((float)setting('default_gst_rate', 5)) / 2 }}%)</td>
                                        <td class="total-val">₹ {{ number_format($totalGst / 2, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>SGST ({{ ((float)setting('default_gst_rate', 5)) / 2 }}%)</td>
                                        <td class="total-val">₹ {{ number_format($totalGst / 2, 2) }}</td>
                                    </tr>
                                @else
                                    <tr>
                                        <td>IGST ({{ (float)setting('default_gst_rate', 5) }}%)</td>
                                        <td class="total-val">₹ {{ number_format($totalGst, 2) }}</td>
                                    </tr>
                                @endif
                            </table>

                            <div class="grand-total-row">
                                <table>
                                    <tr>
                                        <td>Grand Total</td>
                                        <td class="gt-val">₹ {{ number_format($invoiceGrandTotal, 2) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            {{-- 6. Amount in Words --}}
            <div class="words-strip">
                Amount in Words: <strong style="color:#142B1A;">{{ $numberToWords($invoiceGrandTotal) }}</strong>
            </div>

            {{-- 7. Trust Badges Row (Table layout) --}}
            <table class="trust-table">
                <tr>
                    <td class="trust-item-td" style="width:14%;">🌿<br>100% Natural</td>
                    <td class="trust-item-td" style="width:14%;">🛡️<br>No Chemicals</td>
                    <td class="trust-item-td" style="width:14%;">🔒<br>Secure Shopping</td>
                    <td class="trust-item-td" style="width:14%;">🚚<br>Fast Delivery</td>
                    <td class="trust-item-td" style="width:14%;">💚<br>Pure Care</td>
                    <td class="thank-you-script-col" style="width:30%;">
                        Thank you for choosing {{ $storeName }} ♡
                    </td>
                </tr>
            </table>

        </div>

        {{-- 8. Dark Forest Green Bottom Bar --}}
        <div class="invoice-bottom-bar">
            <table>
                <tr>
                    <td style="text-align:left;">
                        <a href="https://{{ $storeWebsite }}" target="_blank">{{ $storeWebsite }}</a> &bull;
                        <a href="mailto:{{ $storeEmail }}">{{ $storeEmail }}</a> &bull;
                        <span>{{ $storePhone }}</span>
                    </td>
                    <td style="text-align:right;">
                        <span>Faridabad, Haryana &bull; Certified Authentic Luxury</span>
                    </td>
                </tr>
            </table>
        </div>

    </div>

    {{-- Screen Interactive Scripts (Nonced for CSP Compliance) --}}
    @unless($isPdf ?? false)
    <script nonce="{{ csp_nonce() }}">
        document.addEventListener('DOMContentLoaded', function () {
            // Print Button Event Listener
            var btnPrint = document.getElementById('btnPrintInvoice');
            if (btnPrint) {
                btnPrint.addEventListener('click', function () {
                    window.print();
                });
            }

            // Back Button Event Listener
            var btnBack = document.getElementById('btnBackInvoice');
            if (btnBack) {
                btnBack.addEventListener('click', function (e) {
                    if (window.history.length > 1 && document.referrer && document.referrer !== window.location.href) {
                        e.preventDefault();
                        window.history.back();
                    }
                });
            }
        });
    </script>
    @endunless

</body>
</html>

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
    if ($logoSetting && file_exists(public_path('storage/' . $logoSetting))) {
        $logoSrc = asset('storage/' . $logoSetting);
    } elseif (file_exists(public_path('images/logo.png'))) {
        $logoSrc = asset('images/logo.png');
    } else {
        $logoSrc = asset('images/logo.webp');
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Cinzel:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--vr-text-main);
            background: #F4F6F4;
            padding: 24px 16px;
            font-size: 11px;
            line-height: 1.45;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Top Action Buttons (Hidden when printing) */
        .invoice-actions {
            max-width: 820px;
            margin: 0 auto 16px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-action-primary {
            background: var(--vr-forest);
            color: #ffffff;
            border: 1px solid var(--vr-forest);
            box-shadow: 0 2px 4px rgba(20, 54, 30, 0.2);
        }
        .btn-action-primary:hover {
            background: var(--vr-forest-dark);
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
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            position: relative;
            border: 1px solid #e5ebe7;
        }

        .invoice-content {
            padding: 28px 32px 24px 32px;
            position: relative;
        }

        /* Botanical Corner Accents */
        .botanical-bg-top {
            position: absolute;
            top: 0;
            right: 0;
            width: 220px;
            height: 140px;
            pointer-events: none;
            opacity: 0.85;
            z-index: 1;
        }
        .botanical-bg-bottom {
            position: absolute;
            bottom: 45px;
            left: 0;
            width: 130px;
            height: 100px;
            pointer-events: none;
            opacity: 0.8;
            z-index: 1;
        }

        /* Top Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            position: relative;
            z-index: 2;
            padding-bottom: 20px;
        }

        .header-brand-wrap {
            display: flex;
            align-items: center;
            gap: 22px;
            max-width: 580px;
        }

        .brand-logo-img {
            max-width: 155px;
            max-height: 85px;
            object-fit: contain;
        }

        .brand-company-info {
            border-left: 1.5px solid #E2E8E2;
            padding-left: 20px;
        }

        .brand-company-info .company-brand-name {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: var(--vr-forest);
            line-height: 1.2;
        }
        .brand-company-info .company-legal-name {
            font-size: 11px;
            font-weight: 700;
            color: #2F3E34;
            margin-bottom: 4px;
        }
        .brand-company-info .company-address {
            font-size: 10px;
            color: #556B5C;
            line-height: 1.4;
            margin-bottom: 6px;
            white-space: pre-line;
        }
        .brand-company-info .company-contacts {
            display: flex;
            flex-direction: column;
            gap: 2px;
            font-size: 10px;
            color: #3C4E43;
        }
        .brand-company-info .company-contacts i {
            color: var(--vr-forest);
            font-size: 10px;
            margin-right: 5px;
            display: inline-block;
        }
        .brand-company-info .company-contacts strong {
            color: var(--vr-forest);
            font-weight: 700;
        }

        .header-tagline-art {
            text-align: right;
            position: relative;
            z-index: 2;
            padding-top: 10px;
            padding-right: 10px;
        }
        .header-tagline-art .pure-care-script {
            font-family: 'Caveat', cursive;
            font-size: 27px;
            color: #37533B;
            line-height: 1.05;
            letter-spacing: 0.2px;
        }
        .header-tagline-art .pure-care-script span {
            font-size: 32px;
            color: #2B462F;
            display: block;
        }
        .header-tagline-art .pure-care-script em {
            font-style: normal;
            color: #4C6E51;
        }

        /* Green TAX INVOICE Banner */
        .tax-invoice-banner {
            background: linear-gradient(135deg, var(--vr-sage-light) 0%, #F3F8F4 100%);
            border: 1px solid var(--vr-sage-border);
            border-radius: 8px;
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
            position: relative;
            z-index: 2;
        }

        .tax-invoice-title-block h1 {
            font-family: 'Cinzel', serif;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--vr-forest);
            line-height: 1;
            margin-bottom: 4px;
        }
        .tax-invoice-title-block p {
            font-size: 10.5px;
            color: #4C6053;
            font-weight: 500;
        }

        .invoice-meta-table {
            border-collapse: collapse;
        }
        .invoice-meta-table td {
            padding: 2px 6px;
            font-size: 10.5px;
        }
        .invoice-meta-table td.label-col {
            font-weight: 600;
            color: #3D5044;
            white-space: nowrap;
        }
        .invoice-meta-table td.colon-col {
            font-weight: 600;
            color: #6B8072;
            padding: 2px 2px;
        }
        .invoice-meta-table td.val-col {
            font-weight: 700;
            color: #1A2E21;
            white-space: nowrap;
        }

        /* Two Column Addresses */
        .addresses-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
            position: relative;
            z-index: 2;
        }

        .address-card {
            border: 1px solid var(--vr-border-light);
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
        }

        .address-card-header {
            background: var(--vr-sage-pill);
            padding: 7px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid var(--vr-sage-border);
        }
        .address-card-header .title {
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: 0.6px;
            color: var(--vr-forest);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
        }
        .address-card-header .title i {
            font-size: 12px;
            color: var(--vr-forest);
        }
        .same-addr-badge {
            font-size: 9px;
            font-weight: 700;
            background: #CBE6D3;
            color: #174221;
            padding: 2px 8px;
            border-radius: 9999px;
        }

        .address-card-body {
            padding: 12px 14px;
            font-size: 10.5px;
            line-height: 1.45;
            color: #2F3E34;
        }
        .address-card-body .cust-name {
            font-size: 12px;
            font-weight: 800;
            color: #132418;
            margin-bottom: 3px;
        }
        .address-card-body .cust-lines {
            color: #495C50;
            margin-bottom: 6px;
        }
        .address-card-body .cust-meta {
            display: flex;
            flex-direction: column;
            gap: 2px;
            font-size: 10px;
            color: #384A3E;
        }
        .address-card-body .cust-meta i {
            color: var(--vr-forest);
            font-size: 10px;
            margin-right: 5px;
        }

        /* Items Table */
        .items-table-wrap {
            margin-bottom: 18px;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #D7E2D9;
            position: relative;
            z-index: 2;
        }

        .invoice-items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }

        .invoice-items-table thead th {
            background: var(--vr-forest);
            color: #ffffff;
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 9px 8px;
            text-align: center;
            border: none;
            vertical-align: middle;
        }
        .invoice-items-table thead th.th-prod {
            text-align: left;
            padding-left: 12px;
        }

        .invoice-items-table tbody td {
            padding: 10px 8px;
            border-bottom: 1px solid #E3ECE5;
            color: #223327;
            vertical-align: middle;
            text-align: center;
            background: #ffffff;
        }
        .invoice-items-table tbody tr:nth-child(even) td {
            background: #FAFCFA;
        }
        .invoice-items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .invoice-items-table td.td-idx {
            font-weight: 700;
            color: #556B5C;
            width: 28px;
        }
        .invoice-items-table td.td-prod {
            text-align: left;
            padding-left: 10px;
        }
        .prod-cell-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .prod-thumb-img {
            width: 44px;
            height: 44px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #E0EAE2;
            background: #f8faf8;
            flex-shrink: 0;
        }
        .prod-details-text .prod-title {
            font-size: 11px;
            font-weight: 700;
            color: #14281B;
            line-height: 1.3;
        }
        .prod-details-text .prod-sku {
            font-size: 9px;
            color: #728578;
            font-family: monospace;
            margin-top: 2px;
            text-transform: uppercase;
        }
        .prod-details-text .prod-weight-tag {
            font-size: 9px;
            color: var(--vr-forest);
            font-weight: 600;
            background: var(--vr-sage-pill);
            padding: 1px 5px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 2px;
        }

        .td-amount {
            font-weight: 700;
            color: #112216;
            text-align: right !important;
            padding-right: 12px !important;
        }

        /* Bottom Section: Payment + T&C vs Totals */
        .bottom-summary-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 16px;
            margin-bottom: 18px;
            position: relative;
            z-index: 2;
        }

        /* Left Info Cards */
        .left-info-cards {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .payment-method-card {
            border: 1px solid var(--vr-border-light);
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
        }
        .payment-method-header {
            background: var(--vr-sage-pill);
            padding: 6px 12px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.6px;
            color: var(--vr-forest);
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 6px;
            border-bottom: 1px solid var(--vr-sage-border);
        }
        .payment-method-body {
            padding: 9px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10.5px;
        }
        .payment-method-name {
            font-weight: 700;
            color: #1F3025;
        }
        .payment-status-badge {
            font-size: 9.5px;
            font-weight: 800;
            padding: 2px 10px;
            border-radius: 9999px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .status-paid {
            background: #D4ECD9;
            color: #0E481D;
        }
        .status-pending {
            background: #FEF3C7;
            color: #92400E;
        }

        .terms-conditions-card {
            border: 1px solid var(--vr-border-light);
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
            padding: 10px 12px;
        }
        .terms-conditions-header {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.6px;
            color: var(--vr-forest);
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 6px;
        }
        .terms-conditions-list {
            list-style: none;
            padding-left: 0;
            font-size: 9.5px;
            color: #556B5C;
            line-height: 1.45;
        }
        .terms-conditions-list li {
            position: relative;
            padding-left: 12px;
            margin-bottom: 3px;
        }
        .terms-conditions-list li::before {
            content: "•";
            position: absolute;
            left: 0;
            color: var(--vr-forest);
            font-weight: 700;
        }
        .terms-highlight-computer {
            font-weight: 700;
            color: var(--vr-forest);
        }

        /* Right Totals Card */
        .totals-card {
            border: 1px solid var(--vr-border-light);
            border-radius: 8px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.02);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .totals-table-wrap {
            padding: 10px 14px;
        }
        .summary-totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10.5px;
        }
        .summary-totals-table td {
            padding: 4px 0;
            color: #374A3D;
        }
        .summary-totals-table td.total-val {
            text-align: right;
            font-weight: 700;
            color: #1A2C20;
        }

        .grand-total-highlight-row {
            background: var(--vr-sage-light);
            border-top: 1.5px solid var(--vr-sage-border);
            padding: 10px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .grand-total-highlight-row .gt-label {
            font-size: 13px;
            font-weight: 800;
            color: var(--vr-forest);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .grand-total-highlight-row .gt-val {
            font-size: 16px;
            font-weight: 900;
            color: var(--vr-forest);
        }

        /* Digital Generated Verification Pill */
        .digital-verification-notice {
            background: #F2F8F4;
            border: 1px dashed #B8D6C0;
            border-radius: 6px;
            padding: 6px 10px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 9px;
            color: #2D4C35;
        }
        .digital-verification-notice i {
            font-size: 13px;
            color: #1B5628;
        }

        /* Trust Badges & Thank you Script Row */
        .trust-script-row {
            border-top: 1px solid #E5EBE6;
            padding: 14px 4px 6px 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .trust-badges-list {
            display: flex;
            align-items: center;
            gap: 18px;
            font-size: 9.5px;
            font-weight: 700;
            color: #384E3F;
        }
        .trust-badge-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .trust-badge-item i {
            font-size: 13px;
            color: var(--vr-forest);
        }

        .thank-you-script-col {
            font-family: 'Caveat', cursive;
            font-size: 22px;
            color: #2F4D33;
            font-weight: 700;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        /* Dark Bottom Footer Bar */
        .invoice-bottom-bar {
            background: var(--vr-forest);
            color: #ffffff;
            padding: 10px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
            font-weight: 500;
        }
        .footer-links-group {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .footer-links-group a {
            color: #ffffff;
            text-decoration: none;
        }
        .footer-social-icons {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 12px;
            color: #C8DEC9;
        }

        /* Print Media Styles */
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
                width: 100% !important;
                border-radius: 0 !important;
            }
            .invoice-content {
                padding: 20px 24px 16px 24px !important;
            }
            .invoice-bottom-bar {
                padding: 8px 24px !important;
            }
            @page {
                size: A4 portrait;
                margin: 8mm 6mm;
            }
        }
    </style>
</head>
<body>

    {{-- Screen Action Toolbar --}}
    <div class="invoice-actions">
        <a href="javascript:history.back()" class="btn-action btn-action-outline">
            <i class="bi bi-arrow-left"></i> Back to Order
        </a>
        <button type="button" class="btn-action btn-action-primary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print / Download Tax Invoice
        </button>
    </div>

    {{-- Main Printable Invoice Card --}}
    <div class="invoice-paper">

        {{-- Botanical Decorative Corner Elements --}}
        <svg class="botanical-bg-top" viewBox="0 0 240 160" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M220 10C180 30 160 80 170 130" stroke="#87A98F" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M190 25C175 10 150 15 155 35C160 55 185 45 190 25Z" fill="#C3D9C8" opacity="0.65"/>
            <path d="M175 60C155 50 140 65 150 82C160 99 180 80 175 60Z" fill="#A8C8AF" opacity="0.7"/>
            <path d="M185 95C170 90 160 110 175 125C190 140 200 115 185 95Z" fill="#91B699" opacity="0.6"/>
            <path d="M225 45C215 35 195 45 205 60C215 75 235 60 225 45Z" fill="#B3CDB7" opacity="0.7"/>
            <path d="M235 85C220 75 210 95 220 110C230 125 245 105 235 85Z" fill="#A2C3A8" opacity="0.5"/>
        </svg>

        <svg class="botanical-bg-bottom" viewBox="0 0 150 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M10 110C40 90 60 50 50 10" stroke="#9BBCA3" stroke-width="1.2" stroke-linecap="round"/>
            <path d="M30 85C45 95 65 85 55 70C45 55 25 70 30 85Z" fill="#C7DDCB" opacity="0.6"/>
            <path d="M48 50C60 60 78 50 70 35C62 20 42 35 48 50Z" fill="#A8C9AF" opacity="0.65"/>
        </svg>

        <div class="invoice-content">

            {{-- 1. Top Header --}}
            <div class="invoice-header">
                <div class="header-brand-wrap">
                    @if (!empty($logoSrc))
                        <img src="{{ $logoSrc }}" alt="{{ $storeName }}" class="brand-logo-img">
                    @else
                        <div style="font-family:'Cinzel', serif; font-size:24px; font-weight:800; color:var(--vr-forest);">{{ $storeName }}</div>
                    @endif

                    <div class="brand-company-info">
                        <div class="company-brand-name">{{ $storeName }}</div>
                        <div class="company-legal-name">A brand of {{ $companyName }}</div>
                        <div class="company-address">{!! nl2br(e($storeAddress)) !!}</div>
                        <div class="company-contacts">
                            <div><i class="bi bi-telephone-fill"></i> {{ $storePhone }}</div>
                            <div><i class="bi bi-envelope-fill"></i> {{ $storeEmail }}</div>
                            <div><i class="bi bi-globe"></i> {{ $storeWebsite }}</div>
                            <div style="margin-top: 3px; font-weight:700;"><i class="bi bi-card-text"></i> GSTIN: <strong>{{ $gstin }}</strong></div>
                        </div>
                    </div>
                </div>

                <div class="header-tagline-art">
                    <div class="pure-care-script">
                        Pure<br>
                        <em>Care</em><br>
                        <span>Natural You <i>♡</i></span>
                    </div>
                </div>
            </div>

            {{-- 2. TAX INVOICE Banner --}}
            <div class="tax-invoice-banner">
                <div class="tax-invoice-title-block">
                    <h1>TAX INVOICE</h1>
                    <p>Thank you for choosing {{ $storeName }}. We appreciate your support.</p>
                </div>

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
            </div>

            {{-- 3. Bill To & Shipping Address Two-Column Cards --}}
            <div class="addresses-grid">
                {{-- Bill To --}}
                <div class="address-card">
                    <div class="address-card-header">
                        <span class="title"><i class="bi bi-person-fill"></i> BILL TO</span>
                    </div>
                    <div class="address-card-body">
                        <div class="cust-name">{{ $order->billing_name ?: $order->shipping_name }}</div>
                        <div class="cust-lines">
                            {{ $order->billing_address_line1 ?: $order->shipping_address_line1 }}<br>
                            @if ($order->billing_address_line2 || $order->shipping_address_line2)
                                {{ $order->billing_address_line2 ?: $order->shipping_address_line2 }}<br>
                            @endif
                            {{ $order->billing_city ?: $order->shipping_city }}, {{ $order->billing_state ?: $order->shipping_state }} - {{ $order->billing_pincode ?: $order->shipping_pincode }}<br>
                            {{ $order->billing_country ?: ($order->shipping_country ?: 'India') }}
                        </div>
                        <div class="cust-meta">
                            <div><i class="bi bi-telephone-fill"></i> {{ $order->billing_mobile ?: $order->shipping_mobile }}</div>
                            <div><i class="bi bi-envelope-fill"></i> {{ $customerEmail }}</div>
                        </div>
                    </div>
                </div>

                {{-- Shipping Address --}}
                <div class="address-card">
                    <div class="address-card-header">
                        <span class="title"><i class="bi bi-geo-alt-fill"></i> SHIPPING ADDRESS</span>
                        @if ($isSameAddress)
                            <span class="same-addr-badge">Same as Billing Address</span>
                        @endif
                    </div>
                    <div class="address-card-body">
                        <div class="cust-name">{{ $order->shipping_name }}</div>
                        <div class="cust-lines">
                            {{ $order->shipping_address_line1 }}<br>
                            @if ($order->shipping_address_line2)
                                {{ $order->shipping_address_line2 }}<br>
                            @endif
                            {{ $order->shipping_city }}, {{ $order->shipping_state }} - {{ $order->shipping_pincode }}<br>
                            {{ $order->shipping_country ?: 'India' }}
                        </div>
                        <div class="cust-meta">
                            <div><i class="bi bi-telephone-fill"></i> {{ $order->shipping_mobile }}</div>
                            <div><i class="bi bi-envelope-fill"></i> {{ $customerEmail }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. Products Table --}}
            <div class="items-table-wrap">
                <table class="invoice-items-table">
                    <thead>
                        <tr>
                            <th style="width:28px;">#</th>
                            <th class="th-prod">Product Details</th>
                            <th style="width:68px;">HSN</th>
                            <th style="width:78px;">Unit Price (₹)</th>
                            <th style="width:42px;">Qty</th>
                            <th style="width:68px;">Discount (₹)</th>
                            <th style="width:82px;">Taxable Value (₹)</th>
                            <th style="width:52px;">GST (%)</th>
                            <th style="width:78px;">GST Amount (₹)</th>
                            <th style="width:88px;" class="text-end">Total Amount (₹)</th>
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

                                // Thumbnail resolution
                                $thumbSrc = null;
                                if ($item->product?->featured_image) {
                                    $thumbSrc = image_url($item->product->featured_image, 'placeholder.png');
                                } elseif ($item->image) {
                                    $thumbSrc = image_url($item->image, 'placeholder.png');
                                }
                                if (!$thumbSrc || !file_exists(public_path($thumbSrc))) {
                                    $thumbSrc = asset('images/placeholder.png');
                                }
                            @endphp
                            <tr>
                                <td class="td-idx">{{ $idx + 1 }}</td>
                                <td class="td-prod">
                                    <div class="prod-cell-content">
                                        <img src="{{ $thumbSrc }}" alt="{{ $item->product_name }}" class="prod-thumb-img" onerror="this.style.display='none'">
                                        <div class="prod-details-text">
                                            <div class="prod-title">{{ $item->product_name }}</div>
                                            <div class="prod-sku">SKU: {{ $item->product?->sku ?: ($item->sku ?: 'VN-' . $item->product_id) }}</div>
                                            @if ($item->product?->weight)
                                                <span class="prod-weight-tag">{{ $item->product->weight }} {{ $item->product->unit ?: 'gm' }}</span>
                                            @endif
                                        </div>
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

            {{-- 5. Bottom Summary: Payment/Terms vs Totals --}}
            <div class="bottom-summary-grid">
                {{-- Left: Payment Method + Terms --}}
                <div class="left-info-cards">
                    {{-- Payment Method Card --}}
                    <div class="payment-method-card">
                        <div class="payment-method-header">
                            <i class="bi bi-credit-card-2-front"></i> PAYMENT METHOD
                        </div>
                        <div class="payment-method-body">
                            <div>
                                <span class="text-muted" style="font-size:9.5px;">Paid via:</span>
                                <div class="payment-method-name">
                                    @if ($order->payment_method === 'cod')
                                        Cash on Delivery (COD)
                                    @elseif ($order->payment_method === 'razorpay')
                                        Online Payment (Razorpay)
                                    @else
                                        {{ ucwords(str_replace('_', ' ', $order->payment_method ?: 'Prepaid')) }}
                                    @endif
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span class="text-muted" style="font-size:9.5px;">Payment Status:</span>
                                <div>
                                    @if (in_array(strtolower((string)$order->payment_status), ['paid', 'captured', 'success'], true))
                                        <span class="payment-status-badge status-paid">Paid</span>
                                    @else
                                        <span class="payment-status-badge status-pending">{{ ucfirst($order->payment_status ?: 'Pending') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Terms & Conditions Card (with prominent computer generated notice, no signature needed) --}}
                    <div class="terms-conditions-card">
                        <div class="terms-conditions-header">
                            <i class="bi bi-file-text"></i> Terms &amp; Conditions
                        </div>
                        <ul class="terms-conditions-list">
                            <li><span class="terms-highlight-computer">This is a computer generated invoice and does not require a physical signature.</span></li>
                            <li>Goods once sold will only be taken back as per our official Return &amp; Refund Policy.</li>
                            <li>For any questions related to your order, please contact our support at {{ $storeEmail }} or {{ $storePhone }}.</li>
                            <li>This invoice is issued by {{ $companyName }} ({{ $storeName }}). GSTIN: {{ $gstin }}.</li>
                            <li>All disputes are subject to Faridabad, Haryana jurisdiction only.</li>
                        </ul>

                        <div class="digital-verification-notice">
                            <i class="bi bi-check-circle-fill"></i>
                            <div>
                                <strong>Digitally Verified &amp; Authorized</strong> &bull; Generated directly from store records.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Totals Card (Delivery Charges Removed) --}}
                <div class="totals-card">
                    <div class="totals-table-wrap">
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
                    </div>

                    <div class="grand-total-highlight-row">
                        <span class="gt-label">Grand Total</span>
                        <span class="gt-val">₹ {{ number_format($invoiceGrandTotal, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- 6. Amount in words strip --}}
            <div style="font-size:10px; color:#4E6354; margin-bottom:14px; background:#F8FCF9; border-left:3px solid var(--vr-forest); padding:6px 12px; border-radius:4px; position:relative; z-index:2;">
                Amount in Words: <strong style="color:#142B1A;">{{ $numberToWords($invoiceGrandTotal) }}</strong>
            </div>

            {{-- 7. Trust Badges & Script Row --}}
            <div class="trust-script-row">
                <div class="trust-badges-list">
                    <div class="trust-badge-item">
                        <i class="bi bi-flower1"></i>
                        <span>100% Natural<br>Ingredients</span>
                    </div>
                    <div class="trust-badge-item">
                        <i class="bi bi-shield-check"></i>
                        <span>No Harmful<br>Chemicals</span>
                    </div>
                    <div class="trust-badge-item">
                        <i class="bi bi-bag-check"></i>
                        <span>Safe &amp; Secure<br>Shopping</span>
                    </div>
                    <div class="trust-badge-item">
                        <i class="bi bi-truck"></i>
                        <span>Fast &amp; Reliable<br>Delivery</span>
                    </div>
                    <div class="trust-badge-item">
                        <i class="bi bi-heart-pulse"></i>
                        <span>Care for You<br>&amp; Planet</span>
                    </div>
                </div>

                <div class="thank-you-script-col">
                    Thank you for choosing {{ $storeName }} <i>♡</i>
                </div>
            </div>

        </div>

        {{-- 8. Dark Forest Green Bottom Bar --}}
        <div class="invoice-bottom-bar">
            <div class="footer-links-group">
                <a href="https://{{ $storeWebsite }}" target="_blank">{{ $storeWebsite }}</a>
                <span>|</span>
                <a href="mailto:{{ $storeEmail }}">{{ $storeEmail }}</a>
                <span>|</span>
                <span>{{ $storePhone }}</span>
            </div>

            <div class="footer-social-icons">
                <i class="bi bi-instagram"></i>
                <i class="bi bi-facebook"></i>
                <i class="bi bi-pinterest"></i>
                <i class="bi bi-youtube"></i>
            </div>
        </div>

    </div>

</body>
</html>

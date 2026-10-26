<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\Order::with('items.product')->find(21);
$invoiceService = app(\App\Services\InvoiceService::class);

echo "Generating and storing A4 PDF on S3 for Order {$order->order_number}...\n";
$url = $invoiceService->generateAndStorePdf($order, true);
$order->refresh();

echo "Stored invoice_number: " . $order->invoice_number . "\n";
echo "Dynamic A4 invoiceUrl: " . $order->invoiceUrl() . "\n";
echo "Generated at: " . $order->invoice_generated_at . "\n";

// Verify Canvas dimensions and page count
$dompdf = new \Dompdf\Dompdf();
$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('dpi', 96);

$dompdf->setOptions($options);
$dompdf->setPaper('A4', 'portrait');
$html = view('invoices.gst-tax-invoice', ['order' => $order, 'isPdf' => true])->render();
$dompdf->loadHtml($html);
$dompdf->render();

$canvas = $dompdf->getCanvas();
echo "Canvas Page count: " . $canvas->get_page_count() . " page(s)\n";
echo "Canvas Width: " . $canvas->get_width() . " pt (A4: 595.28 pt / 210mm)\n";
echo "Canvas Height: " . $canvas->get_height() . " pt (A4: 841.89 pt / 297mm)\n";

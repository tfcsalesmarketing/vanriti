<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\Order::with('items.product')->find(21);

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
$pageCount = $canvas->get_page_count();
$width = $canvas->get_width();
$height = $canvas->get_height();

echo "Page count: {$pageCount}\n";
echo "Page width (pt): {$width} (A4 standard: 595.28)\n";
echo "Page height (pt): {$height} (A4 standard: 841.89)\n";

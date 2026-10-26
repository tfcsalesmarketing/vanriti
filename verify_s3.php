<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\Order::with('items.product')->find(21);
$path = $order->invoice_pdf_path ?: "invoices/INV-{$order->order_number}.pdf";

$s3Content = \Illuminate\Support\Facades\Storage::disk('s3')->get($path);
file_put_contents('s3_downloaded.pdf', $s3Content);

echo "S3 Content length: " . strlen($s3Content) . " bytes\n";
echo "S3 Content md5: " . md5($s3Content) . "\n";

// Let's generate a fresh PDF right now and check its md5 and length
$dompdf = new \Dompdf\Dompdf();
$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('dpi', 150);

$dompdf->setOptions($options);
$dompdf->setPaper('A4', 'portrait');
$html = view('invoices.gst-tax-invoice', ['order' => $order, 'isPdf' => true])->render();
$dompdf->loadHtml($html);
$dompdf->render();
$freshPdf = $dompdf->output();

echo "Fresh Content length: " . strlen($freshPdf) . " bytes\n";
echo "Fresh Content md5: " . md5($freshPdf) . "\n";

// Upload fresh PDF to S3 right now with no-cache!
echo "Uploading fresh PDF to S3...\n";
\Illuminate\Support\Facades\Storage::disk('s3')->put($path, $freshPdf, [
    'visibility' => 'public',
    'CacheControl' => 'no-cache, no-store, must-revalidate, max-age=0',
]);
echo "Upload complete!\n";

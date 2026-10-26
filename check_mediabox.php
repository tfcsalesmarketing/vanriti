<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\Order::with('items.product')->find(21);
$path = $order->invoice_pdf_path ?: "invoices/INV-{$order->order_number}.pdf";
$content = \Illuminate\Support\Facades\Storage::disk('s3')->get($path);

preg_match_all('#/Type\s*/Page\b#', $content, $pageMatches);
echo "Total Page objects: " . count($pageMatches[0]) . "\n";

preg_match_all('#/Count\s+(\d+)#', $content, $countMatches);
print_r($countMatches[1]);

<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$req = Illuminate\Http\Request::create('http://tfcsalesmarketing.com/opportunity', 'GET');
$resp = $kernel->handle($req);
$content = $resp->getContent();

echo "Status: " . $resp->getStatusCode() . "\n";
echo "Has 'Activation Packages': " . (str_contains($content, 'Activation Packages') ? 'YES' : 'NO') . "\n";
echo "Has 'Direct Sponsor Incentive': " . (str_contains($content, 'Direct Sponsor Incentive') ? 'YES' : 'NO') . "\n";
echo "Has 'Product Credit': " . (str_contains($content, 'Product Credit') ? 'YES' : 'NO') . "\n";

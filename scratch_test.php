<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('https://tfcsalesmarketing.com/', 'GET');
$app->instance('request', $request);

echo "asset('css/tfc-style.css'): " . asset('css/tfc-style.css') . "\n";
echo "url('css/tfc-style.css'): " . url('css/tfc-style.css') . "\n";
echo "config('app.url'): " . config('app.url') . "\n";
echo "config('app.asset_url'): " . config('app.asset_url') . "\n";

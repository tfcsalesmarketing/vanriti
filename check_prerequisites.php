<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Product;

echo 'Blog with slug complete-guide-amla: ' . Blog::where('slug', 'complete-guide-amla')->count() . PHP_EOL;
echo 'Total blogs: ' . Blog::count() . PHP_EOL;
echo 'Blog categories: ' . BlogCategory::count() . PHP_EOL;
BlogCategory::all()->each(function ($c) {
    echo '  Cat: ' . $c->name . ' (' . $c->slug . ') status=' . $c->status . PHP_EOL;
});
echo 'Product VNRT091: ' . (Product::where('sku', 'VNRT091')->exists() ? 'EXISTS' : 'NOT FOUND') . PHP_EOL;
$p = Product::where('sku', 'VNRT091')->first();
if ($p) {
    echo '  Name: ' . $p->name . PHP_EOL;
    echo '  Slug: ' . $p->slug . PHP_EOL;
    echo '  Status: ' . $p->status . PHP_EOL;
    echo '  Description: ' . substr($p->description, 0, 200) . PHP_EOL;
    echo '  How to use: ' . substr($p->how_to_use, 0, 200) . PHP_EOL;
    echo '  Benefits: ' . substr($p->benefits, 0, 200) . PHP_EOL;
    echo '  Ingredients: ' . $p->ingredients . PHP_EOL;
    echo '  Net quantity: ' . $p->net_quantity . ' ' . $p->unit . PHP_EOL;
    echo '  Shelf life: ' . $p->shelf_life . PHP_EOL;
}

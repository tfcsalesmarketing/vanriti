<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class NormalizeProductSlugs extends Command
{
    protected $signature = 'products:normalize-slugs';

    protected $description = 'Rewrite product URL slugs from the product name only (no id or SKU prefix)';

    public function handle(): int
    {
        $updated = Product::rewriteAllSlugsFromNames();

        $this->info("Updated {$updated} product slug(s).");

        return self::SUCCESS;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $q = request('q');
        $lowOnly = request('low_only');

        $products = Product::with(['variants', 'images'])
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
                });
            })
            ->when($lowOnly, function ($query) {
                $query->where(function ($w) {
                    $w->where(function ($inner) {
                        $inner->doesntHave('variants')
                            ->whereColumn('stock', '<=', 'low_stock_threshold')
                            ->where('stock', '>', 0);
                    })->orWhereHas('variants', function ($v) {
                        $v->whereColumn('stock', '<=', 'low_stock_threshold')
                            ->where('stock', '>', 0);
                    });
                });
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $totalSkus = DB::table('products')
                ->whereNotNull('sku')->where('sku', '!=', '')->count()
            + DB::table('product_variants')
                ->whereNotNull('sku')->where('sku', '!=', '')->count();

        $outOfStock = DB::table('products')
                ->whereNotNull('sku')->where('sku', '!=', '')
                ->where(function ($q) {
                    $q->whereNull('stock')->orWhere('stock', '<=', 0);
                })->count()
            + DB::table('product_variants')
                ->whereNotNull('sku')->where('sku', '!=', '')
                ->where(function ($q) {
                    $q->whereNull('stock')->orWhere('stock', '<=', 0);
                })->count();

        return view('admin.inventory.index', compact('products', 'totalSkus', 'outOfStock'));
    }
}

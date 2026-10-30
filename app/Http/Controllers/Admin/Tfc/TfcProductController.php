<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TfcProductController extends Controller
{
    /**
     * Display live synced VANRITI products inside TFC Admin.
     */
    public function index(Request $request): View
    {
        $query = Product::with(['images', 'categories', 'variants']);

        if ($request->filled('search')) {
            $search = trim($request->query('search'));
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%");
        }

        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->query('category'));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $products = $query->latest()->paginate(20)->withQueryString();
        $categories = Category::active()->get();

        return view('admin.tfc.products.index', compact('products', 'categories'));
    }
}

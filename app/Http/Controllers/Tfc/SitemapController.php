<?php

namespace App\Http\Controllers\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tfc\TfcBlog;
use App\Models\Tfc\TfcPage;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Generate dynamic XML sitemap for TFC Sales & Marketing.
     */
    public function index(): Response
    {
        $products = Product::active()->latest()->get(['slug', 'updated_at']);
        $categories = Category::active()->get(['slug', 'updated_at']);
        $blogs = TfcBlog::published()->latest('published_at')->get(['slug', 'updated_at']);
        $pages = TfcPage::published()->get(['slug', 'updated_at']);

        $xml = view('tfc.sitemap', compact('products', 'categories', 'blogs', 'pages'))->render();

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }
}

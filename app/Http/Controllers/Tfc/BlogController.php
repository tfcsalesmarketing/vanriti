<?php

namespace App\Http\Controllers\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Tfc\TfcBlog;
use Illuminate\View\View;

class BlogController extends Controller
{
    /**
     * Display published TFC blogs & corporate updates.
     */
    public function index(): View
    {
        $blogs = TfcBlog::published()
            ->latest('published_at')
            ->paginate(9);

        return view('tfc.blogs.index', compact('blogs'));
    }

    /**
     * Display a single blog article.
     */
    public function show(string $slug): View
    {
        $blog = TfcBlog::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $recentBlogs = TfcBlog::published()
            ->where('id', '!=', $blog->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('tfc.blogs.show', compact('blog', 'recentBlogs'));
    }
}

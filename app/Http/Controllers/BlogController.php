<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogCategory;

class BlogController extends Controller
{
    public function index()
    {
        $blogs = Blog::published()->with(['category', 'author'])->latest('published_at')->paginate(9);
        $recent = Blog::published()->latest('published_at')->limit(4)->get();
        $categories = BlogCategory::whereHas('blogs', fn ($q) => $q->published())->get();
        $featured = $blogs->first();

        return view('storefront.blog.index', compact('blogs', 'recent', 'categories', 'featured'));
    }

    public function show(Blog $blog)
    {
        abort_if($blog->status !== 'published', 404);

        $blog->load(['category', 'author']);

        $related = Blog::published()
            ->where('id', '!=', $blog->id)
            ->where('blog_category_id', $blog->blog_category_id)
            ->with(['category', 'author'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('storefront.blog.show', compact('blog', 'related'));
    }
}

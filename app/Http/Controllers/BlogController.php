<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogCategory;

class BlogController extends Controller
{
    public function index()
    {
        $categorySlug = request('category');
        $search = trim((string) request('q'));

        $query = Blog::published()->with(['category', 'author'])->latest('published_at');

        $activeCategory = null;
        if ($categorySlug) {
            $activeCategory = BlogCategory::where('slug', $categorySlug)->first();
            if ($activeCategory) {
                $query->where('blog_category_id', $activeCategory->id);
            }
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $isFiltered = $categorySlug || $search !== '' || request('page', 1) > 1;

        $blogs = $query->paginate(12)->withQueryString();
        $categories = BlogCategory::whereHas('blogs', fn ($q) => $q->published())
            ->withCount(['blogs' => fn ($q) => $q->published()])
            ->get();
        $recent = Blog::published()->latest('published_at')->limit(5)->get();

        return view('storefront.blog.index', compact('blogs', 'recent', 'categories', 'activeCategory', 'search'));
    }

    public function show(Blog $blog)
    {
        abort_if($blog->status !== 'published', 404);

        $blog->load(['category', 'author']);

        // Recent blogs for the sidebar (5 latest excluding current post)
        $recent = Blog::published()
            ->where('id', '!=', $blog->id)
            ->with(['category', 'author'])
            ->latest('published_at')
            ->limit(5)
            ->get();

        // Popular blogs for the bottom of the article (4 blogs)
        $popular = Blog::published()
            ->where('id', '!=', $blog->id)
            ->when($blog->blog_category_id, fn ($q) => $q->where('blog_category_id', $blog->blog_category_id))
            ->with(['category', 'author'])
            ->latest('published_at')
            ->limit(4)
            ->get();

        if ($popular->count() < 4) {
            $needed = 4 - $popular->count();
            $fallback = Blog::published()
                ->where('id', '!=', $blog->id)
                ->whereNotIn('id', $popular->pluck('id'))
                ->with(['category', 'author'])
                ->latest('published_at')
                ->limit($needed)
                ->get();
            $popular = $popular->concat($fallback);
        }

        $categories = BlogCategory::whereHas('blogs', fn ($q) => $q->published())
            ->withCount(['blogs' => fn ($q) => $q->published()])
            ->get();

        return view('storefront.blog.show', compact('blog', 'recent', 'popular', 'categories'));
    }
}

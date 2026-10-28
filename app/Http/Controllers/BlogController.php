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

        $featured = null;
        if (! $isFiltered) {
            // Take the single newest article as featured banner
            $featured = Blog::published()->with(['category', 'author'])->latest('published_at')->first();
            if ($featured) {
                $query->where('id', '!=', $featured->id);
            }
        }

        $blogs = $query->paginate(9)->withQueryString();
        $categories = BlogCategory::whereHas('blogs', fn ($q) => $q->published())
            ->withCount(['blogs' => fn ($q) => $q->published()])
            ->get();
        $recent = Blog::published()->latest('published_at')->limit(4)->get();

        return view('storefront.blog.index', compact('blogs', 'recent', 'categories', 'featured', 'activeCategory', 'search'));
    }

    public function show(Blog $blog)
    {
        abort_if($blog->status !== 'published', 404);

        $blog->load(['category', 'author']);

        $related = Blog::published()
            ->where('id', '!=', $blog->id)
            ->when($blog->blog_category_id, fn ($q) => $q->where('blog_category_id', $blog->blog_category_id))
            ->with(['category', 'author'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        if ($related->count() < 3) {
            $needed = 3 - $related->count();
            $fallback = Blog::published()
                ->where('id', '!=', $blog->id)
                ->whereNotIn('id', $related->pluck('id'))
                ->with(['category', 'author'])
                ->latest('published_at')
                ->limit($needed)
                ->get();
            $related = $related->concat($fallback);
        }

        return view('storefront.blog.show', compact('blog', 'related'));
    }
}

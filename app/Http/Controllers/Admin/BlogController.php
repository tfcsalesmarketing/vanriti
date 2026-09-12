<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(): View
    {
        $blogs = Blog::query()
            ->with(['category', 'author'])
            ->when(request('q'), fn ($q, $search) => $q->where('title', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.blogs.index', compact('blogs'));
    }

    public function create(): View
    {
        $blogCategories = BlogCategory::where('status', 'active')->orderBy('name')->get();

        return view('admin.blogs.create', compact('blogCategories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:blogs,slug',
            'blog_category_id' => 'nullable|exists:blog_categories,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,webp|max:3072',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'seo_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('blogs', 's3');
        }

        $validated['admin_id'] = auth('admin')->id();

        if (($validated['status'] ?? null) === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        Blog::create($validated);

        return redirect()->route('admin.blogs.index')->with('success', 'Blog post created successfully.');
    }

    public function edit(Blog $blog): View
    {
        $blogCategories = BlogCategory::where('status', 'active')->orderBy('name')->get();

        return view('admin.blogs.edit', compact('blog', 'blogCategories'));
    }

    public function update(Request $request, Blog $blog): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:blogs,slug,' . $blog->id,
            'blog_category_id' => 'nullable|exists:blog_categories,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,webp|max:3072',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'seo_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('blogs', 's3');
        } else {
            unset($validated['featured_image']);
        }

        if (($validated['status'] ?? null) === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $blog->update($validated);

        return redirect()->route('admin.blogs.index')->with('success', 'Blog post updated successfully.');
    }

    public function destroy(Blog $blog): RedirectResponse
    {
        $blog->delete();

        return back()->with('success', 'Blog post deleted successfully.');
    }
}

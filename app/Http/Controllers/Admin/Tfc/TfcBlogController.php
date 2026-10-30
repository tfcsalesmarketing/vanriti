<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Tfc\TfcBlog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TfcBlogController extends Controller
{
    public function index(): View
    {
        $blogs = TfcBlog::latest()->paginate(15);
        return view('admin.tfc.blogs.index', compact('blogs'));
    }

    public function create(): View
    {
        return view('admin.tfc.blogs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:tfc_blogs,slug'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        TfcBlog::create($validated);

        return redirect()->route('admin.tfc.blogs.index')->with('success', 'Blog article created successfully.');
    }

    public function edit(TfcBlog $blog): View
    {
        return view('admin.tfc.blogs.edit', compact('blog'));
    }

    public function update(Request $request, TfcBlog $blog): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tfc_blogs,slug,' . $blog->id],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['slug'] = Str::slug($validated['slug']);
        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $blog->update($validated);

        return redirect()->route('admin.tfc.blogs.index')->with('success', 'Blog article updated successfully.');
    }

    public function destroy(TfcBlog $blog): RedirectResponse
    {
        $blog->delete();
        return redirect()->route('admin.tfc.blogs.index')->with('success', 'Blog article deleted successfully.');
    }
}

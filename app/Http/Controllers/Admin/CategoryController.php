<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::withCount('products')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.categories.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:categories,slug',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer',
            'parent_id' => 'nullable|exists:categories,id',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            try {
                $validated['image'] = $request->file('image')->store('categories', 's3');
            } catch (\Throwable $e) {
                report($e);
                $validated['image'] = $request->file('image')->store('categories', 'public');
            }
        }

        $validated['slug'] = !empty($validated['slug'])
            ? Category::uniqueSlugFromName($validated['slug'])
            : Category::uniqueSlugFromName($validated['name']);

        $validated['sort_order'] = !empty($validated['sort_order']) ? (int) $validated['sort_order'] : 0;
        $validated['parent_id'] = !empty($validated['parent_id']) ? (int) $validated['parent_id'] : null;

        Category::create($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(Category $category): View
    {
        $categories = Category::orderBy('name')->get();
        $excludeIds = $category->descendants();

        return view('admin.categories.edit', compact('category', 'categories', 'excludeIds'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:categories,slug,'.$category->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer',
            'parent_id' => 'nullable|exists:categories,id',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            try {
                $validated['image'] = $request->file('image')->store('categories', 's3');
            } catch (\Throwable $e) {
                report($e);
                $validated['image'] = $request->file('image')->store('categories', 'public');
            }
        }

        if (!empty($validated['slug'])) {
            $validated['slug'] = Category::uniqueSlugFromName($validated['slug'], $category->id);
        } elseif (!empty($category->slug)) {
            // Keep existing slug when slug field is omitted, preserving URLs/SEO
            $validated['slug'] = $category->slug;
        } else {
            $validated['slug'] = Category::uniqueSlugFromName($validated['name'], $category->id);
        }

        $validated['sort_order'] = !empty($validated['sort_order']) ? (int) $validated['sort_order'] : 0;
        $validated['parent_id'] = !empty($validated['parent_id']) ? (int) $validated['parent_id'] : null;

        if ($validated['parent_id'] !== null && $validated['parent_id'] === $category->id) {
            $validated['parent_id'] = null;
        }

        $category->update($validated);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->count() > 0) {
            return back()->with('error', 'Cannot delete a category that has child categories.');
        }

        $category->products()->detach();
        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }
}

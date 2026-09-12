<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MainCategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::whereNull('parent_id')
            ->withCount('children', 'products')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.main-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.main-categories.create');
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
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 's3');
        }

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['sort_order'] = $validated['sort_order'] ?? 0;
        $validated['parent_id'] = null;

        Category::create($validated);

        return redirect()->route('admin.main-categories.index')->with('success', 'Main category created successfully.');
    }

    public function edit(Category $main_category): View
    {
        return view('admin.main-categories.edit', ['category' => $main_category]);
    }

    public function update(Request $request, Category $main_category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:categories,slug,' . $main_category->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 's3');
        }

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $main_category->update($validated);

        return redirect()->route('admin.main-categories.index')->with('success', 'Main category updated successfully.');
    }

    public function destroy(Category $main_category): RedirectResponse
    {
        if ($main_category->children()->count() > 0) {
            return back()->with('error', 'Cannot delete this main category because it has sub-categories. Remove or reassign them first.');
        }

        $main_category->products()->detach();
        $main_category->delete();

        return back()->with('success', 'Main category deleted successfully.');
    }
}

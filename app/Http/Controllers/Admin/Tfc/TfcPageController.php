<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Tfc\TfcPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TfcPageController extends Controller
{
    public function index(): View
    {
        $pages = TfcPage::orderBy('sort_order')->latest()->paginate(15);
        return view('admin.tfc.pages.index', compact('pages'));
    }

    public function create(): View
    {
        return view('admin.tfc.pages.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:tfc_pages,slug'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published'],
            'sort_order' => ['nullable', 'integer'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['slug'] = $validated['slug'] ? Str::slug($validated['slug']) : Str::slug($validated['title']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        TfcPage::create($validated);

        return redirect()->route('admin.tfc.pages.index')->with('success', 'Page created successfully.');
    }

    public function edit(TfcPage $page): View
    {
        return view('admin.tfc.pages.edit', compact('page'));
    }

    public function update(Request $request, TfcPage $page): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tfc_pages,slug,' . $page->id],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'in:draft,published'],
            'sort_order' => ['nullable', 'integer'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['slug'] = Str::slug($validated['slug']);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $page->update($validated);

        return redirect()->route('admin.tfc.pages.index')->with('success', 'Page updated successfully.');
    }

    public function destroy(TfcPage $page): RedirectResponse
    {
        $page->delete();
        return redirect()->route('admin.tfc.pages.index')->with('success', 'Page deleted successfully.');
    }
}

<?php

namespace App\Http\Controllers\Admin\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Tfc\TfcBanner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TfcBannerController extends Controller
{
    public function index(): View
    {
        $banners = TfcBanner::orderBy('position')->orderBy('sort_order')->get();
        return view('admin.tfc.banners.index', compact('banners'));
    }

    public function create(): View
    {
        return view('admin.tfc.banners.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'image' => ['required', 'string', 'max:500'],
            'mobile_image' => ['nullable', 'string', 'max:500'],
            'link' => ['nullable', 'string', 'max:255'],
            'link_text' => ['nullable', 'string', 'max:100'],
            'position' => ['required', 'in:hero,opportunity,cta'],
            'sort_order' => ['nullable', 'integer'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        TfcBanner::create($validated);

        return redirect()->route('admin.tfc.banners.index')->with('success', 'Banner created successfully.');
    }

    public function edit(TfcBanner $banner): View
    {
        return view('admin.tfc.banners.edit', compact('banner'));
    }

    public function update(Request $request, TfcBanner $banner): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'image' => ['required', 'string', 'max:500'],
            'mobile_image' => ['nullable', 'string', 'max:500'],
            'link' => ['nullable', 'string', 'max:255'],
            'link_text' => ['nullable', 'string', 'max:100'],
            'position' => ['required', 'in:hero,opportunity,cta'],
            'sort_order' => ['nullable', 'integer'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $banner->update($validated);

        return redirect()->route('admin.tfc.banners.index')->with('success', 'Banner updated successfully.');
    }

    public function destroy(TfcBanner $banner): RedirectResponse
    {
        $banner->delete();
        return redirect()->route('admin.tfc.banners.index')->with('success', 'Banner deleted successfully.');
    }
}

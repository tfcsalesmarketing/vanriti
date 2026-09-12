<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View
    {
        $banners = Banner::query()->orderBy('sort_order')->paginate(20);

        return view('admin.banners.index', compact('banners'));
    }

    public function create(): View
    {
        return view('admin.banners.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,webp|max:3072',
            'mobile_image' => 'nullable|image|mimes:jpeg,png,webp|max:3072',
            'link' => 'required|url|max:500',
            'type' => 'required|in:hero,promotional,section',
            'position' => 'required|string|max:50',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('banners', 's3');
        } elseif ($request->input('image_url')) {
            $validated['image'] = $request->input('image_url');
        }

        if ($request->hasFile('mobile_image')) {
            $validated['mobile_image'] = $request->file('mobile_image')->store('banners', 's3');
        } elseif ($request->input('mobile_image_url')) {
            $validated['mobile_image'] = $request->input('mobile_image_url');
        }

        Banner::create($validated);

        return redirect()->route('admin.banners.index')->with('success', 'Banner created successfully.');
    }

    public function edit(Banner $banner): View
    {
        return view('admin.banners.edit', compact('banner'));
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $validated = $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,webp|max:3072',
            'mobile_image' => 'nullable|image|mimes:jpeg,png,webp|max:3072',
            'link' => 'required|url|max:500',
            'type' => 'required|in:hero,promotional,section',
            'position' => 'required|string|max:50',
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        if ($request->hasFile('image')) {
            if ($banner->image && ! str_starts_with($banner->image, 'http')) {
                Storage::disk('s3')->delete($banner->image);
            }
            $validated['image'] = $request->file('image')->store('banners', 's3');
        } elseif ($request->input('image_url')) {
            $validated['image'] = $request->input('image_url');
        } else {
            unset($validated['image']);
        }

        if ($request->hasFile('mobile_image')) {
            if ($banner->mobile_image && ! str_starts_with($banner->mobile_image, 'http')) {
                Storage::disk('s3')->delete($banner->mobile_image);
            }
            $validated['mobile_image'] = $request->file('mobile_image')->store('banners', 's3');
        } elseif ($request->input('mobile_image_url')) {
            $validated['mobile_image'] = $request->input('mobile_image_url');
        } else {
            unset($validated['mobile_image']);
        }

        $banner->update($validated);

        return redirect()->route('admin.banners.index')->with('success', 'Banner updated successfully.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        if ($banner->image && ! str_starts_with($banner->image, 'http')) {
            Storage::disk('s3')->delete($banner->image);
        }
        if ($banner->mobile_image && ! str_starts_with($banner->mobile_image, 'http')) {
            Storage::disk('s3')->delete($banner->mobile_image);
        }

        $banner->delete();

        return back()->with('success', 'Banner deleted successfully.');
    }
}

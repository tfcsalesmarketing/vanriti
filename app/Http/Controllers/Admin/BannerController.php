<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
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
            'title' => 'nullable|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,webp|max:3072',
            'mobile_image' => 'nullable|image|mimes:jpeg,png,webp|max:3072',
            'image_url' => ['nullable', 'url:https', 'max:500'],
            'mobile_image_url' => ['nullable', 'url:https', 'max:500'],
            'link' => 'required|url|max:500',
            'type' => 'required|in:hero,promotional,section',
            'position' => ['required', 'string', 'max:50', $this->positionRule($request)],
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        // Transport-only inputs: the banner stores the resolved path, not the URL field.
        unset($validated['image_url'], $validated['mobile_image_url']);

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
            'title' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,webp|max:3072',
            'mobile_image' => 'nullable|image|mimes:jpeg,png,webp|max:3072',
            'image_url' => ['nullable', 'url:https', 'max:500'],
            'mobile_image_url' => ['nullable', 'url:https', 'max:500'],
            'link' => 'required|url|max:500',
            'type' => 'required|in:hero,promotional,section',
            'position' => ['required', 'string', 'max:50', $this->positionRule($request)],
            'sort_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'remove_mobile_image' => 'nullable|boolean',
        ]);

        // Transport-only inputs: the banner stores the resolved path, not the URL field.
        unset($validated['image_url'], $validated['mobile_image_url'], $validated['remove_mobile_image']);

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

        // Replacing only the desktop image must never touch the mobile one, and
        // vice versa. An explicit "remove" box is the only way to clear mobile,
        // because an empty file input alone is indistinguishable from "unchanged".
        if ($request->boolean('remove_mobile_image')) {
            $validated['mobile_image'] = null;
        } elseif ($request->hasFile('mobile_image')) {
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

    /**
     * Constrain `position` to the placements that are actually valid for the
     * submitted type, so a hero banner can never be pointed at a homepage
     * section slot (or vice versa) and silently render nowhere.
     */
    protected function positionRule(Request $request): In
    {
        return Rule::in(array_keys(Banner::positionsForType((string) $request->input('type', ''))));
    }
}

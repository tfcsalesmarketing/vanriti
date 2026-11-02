<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InstagramReel;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class InstagramReelController extends Controller
{
    public function index(): View
    {
        $reels = InstagramReel::query()
            ->with(['product'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(15);

        $totalCount = InstagramReel::count();
        $activeCount = InstagramReel::where('is_active', true)->count();
        $inactiveCount = $totalCount - $activeCount;

        return view('admin.marketing.reels.index', compact('reels', 'totalCount', 'activeCount', 'inactiveCount'));
    }

    public function create(): View
    {
        $products = Product::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'selling_price']);

        return view('admin.marketing.reels.create', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:1000',
            'instagram_url' => 'nullable|url|max:500',
            'video_url' => 'nullable|url|max:500',
            'video_file' => 'nullable|file|mimes:mp4,mov,webm|max:51200', // 50MB
            'thumbnail_url' => 'nullable|url|max:500',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,webp,jpg|max:5120',
            'product_id' => 'nullable|exists:products,id',
            'views_count' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        if (! empty($validated['instagram_url'])) {
            $validated['instagram_shortcode'] = InstagramReel::extractShortcode($validated['instagram_url']);
        }

        // Handle uploaded video file
        if ($request->hasFile('video_file')) {
            $path = $request->file('video_file')->store('reels', 'public');
            $validated['video_url'] = 'storage/' . $path;
        }

        // Handle uploaded thumbnail cover image
        if ($request->hasFile('thumbnail_file')) {
            $path = $request->file('thumbnail_file')->store('reels/thumbnails', 'public');
            $validated['thumbnail_url'] = 'storage/' . $path;
        }

        InstagramReel::create($validated);

        return redirect()->route('admin.reels.index')->with('success', 'Instagram reel added successfully.');
    }

    public function edit(InstagramReel $reel): View
    {
        $products = Product::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'selling_price']);

        return view('admin.marketing.reels.edit', compact('reel', 'products'));
    }

    public function update(Request $request, InstagramReel $reel): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:1000',
            'instagram_url' => 'nullable|url|max:500',
            'video_url' => 'nullable|url|max:500',
            'video_file' => 'nullable|file|mimes:mp4,mov,webm|max:51200',
            'thumbnail_url' => 'nullable|url|max:500',
            'thumbnail_file' => 'nullable|image|mimes:jpeg,png,webp,jpg|max:5120',
            'product_id' => 'nullable|exists:products,id',
            'views_count' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        if (! empty($validated['instagram_url'])) {
            $validated['instagram_shortcode'] = InstagramReel::extractShortcode($validated['instagram_url']);
        } else {
            $validated['instagram_shortcode'] = null;
        }

        // Handle video replacement
        if ($request->hasFile('video_file')) {
            if ($reel->video_url && str_starts_with($reel->video_url, 'storage/reels/')) {
                Storage::disk('public')->delete(str_replace('storage/', '', $reel->video_url));
            }
            $path = $request->file('video_file')->store('reels', 'public');
            $validated['video_url'] = 'storage/' . $path;
        } elseif (! empty($validated['video_url'])) {
            // Updated to a direct URL
        }

        // Handle thumbnail replacement
        if ($request->hasFile('thumbnail_file')) {
            if ($reel->thumbnail_url && str_starts_with($reel->thumbnail_url, 'storage/reels/')) {
                Storage::disk('public')->delete(str_replace('storage/', '', $reel->thumbnail_url));
            }
            $path = $request->file('thumbnail_file')->store('reels/thumbnails', 'public');
            $validated['thumbnail_url'] = 'storage/' . $path;
        }

        $reel->update($validated);

        return redirect()->route('admin.reels.index')->with('success', 'Instagram reel updated successfully.');
    }

    public function destroy(InstagramReel $reel): RedirectResponse
    {
        if ($reel->video_url && str_starts_with($reel->video_url, 'storage/reels/')) {
            Storage::disk('public')->delete(str_replace('storage/', '', $reel->video_url));
        }

        if ($reel->thumbnail_url && str_starts_with($reel->thumbnail_url, 'storage/reels/')) {
            Storage::disk('public')->delete(str_replace('storage/', '', $reel->thumbnail_url));
        }

        $reel->delete();

        return redirect()->route('admin.reels.index')->with('success', 'Reel removed successfully.');
    }

    public function toggleActive(InstagramReel $reel): JsonResponse|RedirectResponse
    {
        $reel->update(['is_active' => ! $reel->is_active]);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => (bool) $reel->is_active,
                'message' => $reel->is_active ? 'Reel is now active on website.' : 'Reel is hidden from website.',
            ]);
        }

        return back()->with('success', $reel->is_active ? 'Reel published to website.' : 'Reel hidden from website.');
    }
}


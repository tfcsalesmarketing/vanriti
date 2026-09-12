<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $media = Media::query()
            ->with('admin')
            ->when($request->input('q'), fn ($q, $search) => $q->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")->orWhere('file_name', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.media.index', compact('media'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'image' => 'required|image|mimes:jpeg,png,webp,gif|max:5120',
        ]);

        try {
            $path = $request->file('image')->store('media', 's3');

            Media::create([
                'name' => $validated['name'],
                'file_name' => $request->file('image')->getClientOriginalName(),
                'path' => $path,
                'disk' => 's3',
                'mime_type' => $request->file('image')->getClientMimeType(),
                'size' => $request->file('image')->getSize(),
                'admin_id' => auth('admin')->id(),
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'Image upload failed: '.$e->getMessage());
        }

        return back()->with('success', 'Image uploaded successfully.');
    }

    public function destroy(Media $media): RedirectResponse
    {
        try {
            if ($media->path && ! str_starts_with($media->path, 'http')) {
                Storage::disk($media->disk ?? 's3')->delete($media->path);
            }

            $media->delete();
        } catch (\Throwable $e) {
            return back()->with('error', 'Delete failed: '.$e->getMessage());
        }

        return back()->with('success', 'Image deleted successfully.');
    }
}
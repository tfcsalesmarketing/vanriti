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
            'name' => 'nullable|string|max:150',
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['image', 'mimes:jpeg,png,webp,gif', 'max:5120'],
        ]);

        $files = $request->file('images', []);
        $single = count($files) === 1;
        $uploaded = 0;

        foreach ($files as $file) {
            try {
                $name = ($single && filled($validated['name'] ?? null))
                    ? $validated['name']
                    : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                $path = $file->store('media', 's3');

                Media::create([
                    'name' => $name,
                    'file_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'disk' => 's3',
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'admin_id' => auth('admin')->id(),
                ]);

                $uploaded++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($uploaded === 0) {
            return back()->with('error', 'No images could be uploaded. Please try again.');
        }

        $message = $uploaded === 1
            ? 'Image uploaded successfully.'
            : "{$uploaded} images uploaded successfully.";

        return back()->with('success', $message);
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

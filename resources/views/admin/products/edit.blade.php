@extends('admin.layouts.app')

@section('title', 'Edit Product')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Edit: {{ $product->name }}</h5>
    <a href="{{ route('admin.products.show', $product->slug) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye me-1"></i>View</a>
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold">Images</div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.products.images.store', $product->slug) }}" enctype="multipart/form-data" class="row g-2 mb-3">
            @csrf
            <div class="col-auto">
                <input type="file" name="image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp" required>
            </div>
            <div class="col-auto">
                <select name="type" class="form-select form-select-sm">
                    <option value="gallery">Gallery</option>
                    <option value="main">Main</option>
                    <option value="thumbnail">Thumbnail</option>
                </select>
            </div>
            <div class="col-auto">
                <input type="text" name="alt_text" class="form-control form-control-sm" placeholder="Alt text" style="width:150px;">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-upload me-1"></i>Upload</button>
            </div>
        </form>

        @php $images = $product->images()->orderBy('sort_order')->get(); @endphp
        @if ($images->count())
            <div class="row g-2">
                @foreach ($images as $img)
                    <div class="col-auto text-center">
                        <img src="{{ image_url($img->image_path) }}" alt="{{ $img->alt_text }}" class="rounded border mb-1" style="width:80px;height:80px;object-fit:cover;">
                        <div class="small text-muted" style="font-size:0.7rem;">
                            <span class="badge bg-{{ $img->type === 'main' ? 'primary' : ($img->type === 'thumbnail' ? 'info' : 'secondary') }}">{{ $img->type }}</span>
                        </div>
                        <div class="d-flex gap-1 justify-content-center mt-1">
                            <form method="POST" action="{{ route('admin.products.images.sort', $img->id) }}" class="d-inline">
                                @csrf
                                <input type="number" name="sort_order" value="{{ $img->sort_order }}" class="form-control form-control-sm text-center d-inline-block" style="width:50px;font-size:0.75rem;" onchange="this.form.submit()">
                            </form>
                            @if ($img->type !== 'main')
                                <form method="POST" action="{{ route('admin.products.images.store', $product->slug) }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="image" value="">
                                    <input type="hidden" name="type" value="main">
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.products.images.destroy', $img->id) }}" class="d-inline" onsubmit="return confirm('Delete image?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:0.7rem;"><i class="bi bi-x"></i></button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-muted small mb-0">No images yet.</p>
        @endif
    </div>
</div>

<form method="POST" action="{{ route('admin.products.update', $product->slug) }}">
    @csrf
    @method('PUT')
    @include('admin.products._form')
</form>
@endsection

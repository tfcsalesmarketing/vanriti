@extends('admin.layouts.app')

@section('title', 'Create Dadi Product Profile')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Create Dadi Product Profile</h5>
        <small class="text-muted">Author a draft of human-controlled product intelligence.</small>
    </div>
    <a href="{{ route('admin.dadi.product-profiles.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<form method="POST" action="{{ route('admin.dadi.product-profiles.store') }}">
    @csrf

    <div class="card mb-3">
        <div class="card-body">
            <label class="form-label fw-semibold" for="product_id">Product</label>
            <select name="product_id" id="product_id" class="form-select {{ $errors->has('content') ? 'is-invalid' : '' }}" required>
                <option value="">— Select product —</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                        {{ $product->name }} ({{ $product->sku }} — {{ ucfirst($product->status) }})
                    </option>
                @endforeach
            </select>
            @if ($errors->has('content'))
                <div class="text-danger small mt-1">{{ $errors->first('content') }}</div>
            @endif
        </div>
    </div>

    @include('admin.dadi-product-profiles._form', ['profile' => null])

    <button type="submit" class="btn btn-dark"><i class="bi bi-plus-lg me-1"></i>Create Draft</button>
</form>
@endsection
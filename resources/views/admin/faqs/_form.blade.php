@php
    $faq = $faq ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold">FAQ Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Question <span class="text-danger">*</span></label>
                        <input type="text" name="question" class="form-control form-control-sm" value="{{ old('question', $faq->question ?? '') }}" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Answer <span class="text-danger">*</span></label>
                        <textarea name="answer" class="form-control form-control-sm" rows="5" required>{{ old('answer', $faq->answer ?? '') }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Category</label>
                        <input list="faq-categories" name="category" class="form-control form-control-sm" value="{{ old('category', $faq->category ?? '') }}">
                        <datalist id="faq-categories">
                            @foreach ($categories as $category)
                                <option value="{{ $category }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Sort Order</label>
                        <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ old('sort_order', $faq->sort_order ?? 0) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select form-select-sm" required>
                            <option value="active" {{ old('status', $faq->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $faq->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-3">
    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Save FAQ</button>
    <a href="{{ route('admin.faqs.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
</div>
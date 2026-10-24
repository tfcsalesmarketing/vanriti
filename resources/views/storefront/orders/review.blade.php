@extends('storefront.layouts.app')
@section('title', 'Review Your Purchase — ' . store_name())
@section('robots', 'noindex, follow')

@section('content')
<div class="vr-section py-4 py-lg-5" style="background: linear-gradient(180deg, #fbfdf9 0%, #f4f7f2 100%); min-height: 80vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">

                {{-- Breadcrumb / Order badge --}}
                <div class="text-center mb-4">
                    <span class="badge bg-success-subtle text-success px-3 py-1.5 rounded-pill fw-semibold border border-success-subtle">
                        <i class="ri-checkbox-circle-fill me-1"></i> Verified Delivery &middot; {{ $order->order_number }}
                    </span>
                    <h2 class="h3 fw-bold mt-2 mb-1" style="color: #1C3C24; font-family: 'Playfair Display', serif;">Share Your Experience</h2>
                    <p class="text-muted small">Your feedback helps fellow botanical wellness seekers.</p>
                </div>

                {{-- Alert Messages --}}
                @if (session('success'))
                    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2 mb-4" style="border-radius: 12px;">
                        <i class="ri-checkbox-circle-line fs-5"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-4" style="border-radius: 12px;">
                        <i class="ri-error-warning-line fs-5"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                {{-- Product Selector (if multiple items in order) --}}
                @if ($order->items->count() > 1)
                    <div class="card border-0 shadow-xs mb-3" style="border-radius: 14px;">
                        <div class="card-body p-3">
                            <label class="small text-muted fw-semibold mb-2 d-block">Items in this delivery:</label>
                            <div class="d-flex gap-2 overflow-auto pb-1">
                                @foreach ($order->items as $item)
                                    @php
                                        $isCur = $selectedItem && $selectedItem->id === $item->id;
                                        $hasRev = (bool) $item->review;
                                    @endphp
                                    <a href="{{ request()->fullUrlWithQuery(['item' => $item->id]) }}"
                                       class="btn btn-sm d-flex align-items-center gap-2 text-start p-2 border {{ $isCur ? 'border-success bg-success-subtle' : 'bg-white' }}"
                                       style="border-radius: 10px; min-width: 170px; max-width: 220px; white-space: normal;">
                                        @if ($item->image)
                                            <img src="{{ image_url($item->image) }}" alt="{{ $item->product_name }}" width="36" height="36" class="rounded object-fit-cover flex-shrink-0">
                                        @endif
                                        <div class="overflow-hidden" style="font-size: 0.76rem; line-height: 1.2;">
                                            <div class="fw-semibold text-truncate text-dark">{{ $item->product_name }}</div>
                                            <div class="text-muted">{{ format_price($item->total_price) }} @if($hasRev)<span class="text-success">&check; Reviewed</span>@endif</div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                @if ($selectedItem)
                    {{-- Selected Item Showcase Card --}}
                    <div class="card border-0 shadow-sm mb-4" style="border-radius: 18px; overflow: hidden; background: #ffffff;">
                        <div class="p-3 d-flex align-items-center gap-3 border-bottom bg-light-subtle">
                            @if ($selectedItem->image)
                                <img src="{{ image_url($selectedItem->image) }}" alt="{{ $selectedItem->product_name }}"
                                     width="64" height="64" class="rounded-3 object-fit-cover shadow-xs border">
                            @endif
                            <div class="flex-grow-1">
                                <h5 class="fw-bold mb-0 text-dark" style="font-size: 1rem;">{{ $selectedItem->product_name }}</h5>
                                @if ($selectedItem->variant_name)
                                    <div class="text-muted small">{{ $selectedItem->variant_name }}</div>
                                @endif
                                <div class="text-success small fw-semibold mt-0.5">
                                    {{ format_price($selectedItem->total_price) }} &middot; Delivered
                                </div>
                            </div>
                        </div>

                        <div class="card-body p-4">
                            @if ($existingReview)
                                {{-- Already Reviewed State --}}
                                <div class="text-center py-3">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2"
                                         style="width: 52px; height: 52px; background: #ecfdf5; color: #059669;">
                                        <i class="ri-star-smile-fill fs-3"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-1">Your Review is Live!</h5>
                                    <p class="text-muted small mb-3">Thank you for sharing your genuine experience with this product.</p>

                                    <div class="p-3 bg-light rounded-3 text-start mb-3 border">
                                        <div class="text-warning mb-1 fs-5">
                                            @for ($s = 1; $s <= 5; $s++)
                                                <i class="bi bi-star{{ $s <= $existingReview->rating ? '-fill' : '' }}"></i>
                                            @endfor
                                        </div>
                                        @if ($existingReview->title)
                                            <div class="fw-bold text-dark">{{ $existingReview->title }}</div>
                                        @endif
                                        @if ($existingReview->comment)
                                            <div class="text-secondary small mt-1">{{ $existingReview->comment }}</div>
                                        @endif
                                        @if ($existingReview->images->isNotEmpty())
                                            <div class="d-flex gap-2 mt-3 flex-wrap">
                                                @foreach ($existingReview->images as $img)
                                                    <img src="{{ image_url($img->image_path) }}" alt="Review photo" width="56" height="56" class="rounded border object-fit-cover">
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <a href="{{ route('home') }}" class="btn btn-vr btn-sm px-4">Continue Shopping</a>
                                </div>
                            @else
                                {{-- Review Form --}}
                                <form method="POST" action="{{ route('order.review.submit', ['order' => $order->order_number, 'mobile' => request('mobile'), 'signature' => request('signature')]) }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="order_item_id" value="{{ $selectedItem->id }}">

                                    {{-- Star Rating --}}
                                    <div class="text-center mb-4">
                                        <label class="form-label d-block fw-semibold text-dark mb-2">Overall Rating</label>
                                        <div class="vr-star-rating d-inline-flex gap-2 fs-2 cursor-pointer" id="starContainer">
                                            @for ($r = 1; $r <= 5; $r++)
                                                <i class="bi bi-star text-warning star-icon" data-val="{{ $r }}"></i>
                                            @endfor
                                        </div>
                                        <input type="hidden" name="rating" id="ratingInput" value="{{ old('rating', 5) }}">
                                        <div class="small text-muted mt-1" id="starLabel">Tap stars to rate (5 of 5)</div>
                                        @error('rating')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Review Headline --}}
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-dark">Review Title</label>
                                        <input type="text" name="title" class="form-control" placeholder="e.g. Loved the fragrance & natural texture!" value="{{ old('title') }}">
                                        @error('title')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Review Comment --}}
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold text-dark">Your Detailed Review</label>
                                        <textarea name="comment" rows="4" class="form-control" placeholder="What did you like about this product? How did it feel on your skin/hair?">{{ old('comment') }}</textarea>
                                        @error('comment')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    {{-- Photos Upload --}}
                                    <div class="mb-4">
                                        <label class="form-label small fw-semibold text-dark d-flex align-items-center justify-content-between">
                                            <span>Add Photos (Optional)</span>
                                            <span class="text-muted fw-normal" style="font-size: 0.75rem;">Up to 5 images</span>
                                        </label>
                                        <input type="file" name="images[]" class="form-control form-control-sm" multiple accept="image/jpeg,image/jpg,image/png,image/webp">
                                        <div class="form-text" style="font-size: 0.72rem;">Supported formats: JPG, PNG, WebP (max 4 MB each).</div>
                                        @error('images')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                        @error('images.*')
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <button type="submit" class="btn btn-vr w-100 py-2.5 fw-bold shadow-sm" style="border-radius: 12px; font-size: 0.95rem;">
                                        <i class="ri-send-plane-fill me-1.5"></i> Submit Verified Review
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning text-center">No items found for this order.</div>
                @endif

            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const stars = document.querySelectorAll('.star-icon');
    const ratingInput = document.getElementById('ratingInput');
    const starLabel = document.getElementById('starLabel');
    const labels = {
        1: 'Poor (1 of 5)',
        2: 'Fair (2 of 5)',
        3: 'Good (3 of 5)',
        4: 'Very Good (4 of 5)',
        5: 'Excellent (5 of 5)'
    };

    function highlight(val) {
        stars.forEach(s => {
            const num = parseInt(s.dataset.val, 10);
            if (num <= val) {
                s.classList.remove('bi-star');
                s.classList.add('bi-star-fill');
            } else {
                s.classList.remove('bi-star-fill');
                s.classList.add('bi-star');
            }
        });
        if (starLabel && labels[val]) {
            starLabel.textContent = labels[val];
        }
    }

    if (ratingInput) {
        highlight(parseInt(ratingInput.value, 10) || 5);
        stars.forEach(s => {
            s.addEventListener('click', function () {
                const val = parseInt(this.dataset.val, 10);
                ratingInput.value = val;
                highlight(val);
            });
            s.addEventListener('mouseenter', function () {
                highlight(parseInt(this.dataset.val, 10));
            });
        });
        document.getElementById('starContainer')?.addEventListener('mouseleave', function () {
            highlight(parseInt(ratingInput.value, 10) || 5);
        });
    }
});
</script>
@endpush
@endsection

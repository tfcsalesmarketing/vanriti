<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-xs p-4 mb-4">
            <h6 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-instagram text-danger"></i> Reel Source &amp; Details
            </h6>

            <div class="mb-3">
                <label for="title" class="form-label small fw-semibold">Reel Title / Hook <span class="text-muted fw-normal">(Optional)</span></label>
                <input type="text"
                       name="title"
                       id="title"
                       class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title', $reel->title ?? '') }}"
                       placeholder="e.g. 5-Min Ayurvedic Hair Routine with Amla">
                <div class="form-text small">Short catchphrase shown at the bottom of the reel card.</div>
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="instagram_url" class="form-label small fw-semibold">Instagram Reel URL</label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-danger"><i class="bi bi-instagram"></i></span>
                    <input type="url"
                           name="instagram_url"
                           id="instagram_url"
                           class="form-control @error('instagram_url') is-invalid @enderror"
                           value="{{ old('instagram_url', $reel->instagram_url ?? '') }}"
                           placeholder="https://www.instagram.com/reel/C8XYZ123/">
                </div>
                <div class="form-text small">Paste the link to your Instagram Reel. Customers can click to view your original post on Instagram.</div>
                @error('instagram_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="caption" class="form-label small fw-semibold">Caption / Ritual Instructions <span class="text-muted fw-normal">(Optional)</span></label>
                <textarea name="caption"
                          id="caption"
                          rows="3"
                          class="form-control @error('caption') is-invalid @enderror"
                          placeholder="Share the routine steps or product benefits featured in this video...">{{ old('caption', $reel->caption ?? '') }}</textarea>
                @error('caption') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        {{-- Video & Media Source Card --}}
        <div class="card border-0 shadow-xs p-4 mb-4">
            <h6 class="fw-bold mb-2 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-play-btn text-primary"></i> Reel Video File (Auto-Playing MP4 for Krishna Ayurved Experience)
            </h6>
            
            <div class="alert alert-warning d-flex align-items-start gap-2.5 py-2.5 px-3 mb-3" style="font-size: 0.82rem; border-radius: 8px;">
                <i class="bi bi-exclamation-circle-fill fs-5 text-warning flex-shrink-0"></i>
                <div>
                    <strong class="d-block text-dark">Why upload an MP4 video instead of only pasting an Instagram link?</strong>
                    Instagram strictly blocks external iframe embeds with <em class="text-danger">"This content is blocked. Contact the site owner to fix the issue."</em> and prohibits multiple videos from autoplaying together.
                    Uploading the short vertical MP4 video file below gives your customers the exact <strong>Krishna Ayurved</strong> experience: <strong>all videos autoplay smoothly together without volume in the carousel</strong> and open with sound on click!
                    <div class="mt-1.5 text-muted">
                        💡 <em>Tip: You can quickly download any Instagram Reel as MP4 using free tools like <a href="https://snapinsta.app" target="_blank" class="fw-bold text-primary text-decoration-underline">SnapInsta.app</a> or <a href="https://fastdl.app" target="_blank" class="fw-bold text-primary text-decoration-underline">FastDl.app</a>, then upload it below!</em>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="video_file" class="form-label small fw-semibold">Upload Reel Video (MP4) <span class="text-muted fw-normal">(Max 100MB)</span></label>
                    <input type="file"
                           name="video_file"
                           id="video_file"
                           class="form-control @error('video_file') is-invalid @enderror"
                           accept="video/mp4,video/webm,video/quicktime,video/m4v">
                    @if (isset($reel) && $reel->video_url)
                        <div class="mt-2 small text-success d-flex align-items-center gap-1">
                            <i class="bi bi-check-circle-fill"></i> Video configured
                            <a href="{{ image_url($reel->video_url) }}" target="_blank" class="ms-1 text-primary">Preview</a>
                        </div>
                    @endif
                    @error('video_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="video_url" class="form-label small fw-semibold">Or Direct Video URL (CDN / Cloud Storage)</label>
                    <input type="url"
                           name="video_url"
                           id="video_url"
                           class="form-control @error('video_url') is-invalid @enderror"
                           value="{{ old('video_url', $reel->video_url ?? '') }}"
                           placeholder="https://cdn.example.com/videos/hair-routine.mp4">
                    @error('video_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        {{-- Thumbnail Cover Image --}}
        <div class="card border-0 shadow-xs p-4">
            <h6 class="fw-bold mb-2 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-image text-warning"></i> Thumbnail Cover / Poster
            </h6>
            <p class="small text-muted mb-3">
                Shown on the homepage before the user taps play. 9:16 vertical ratio recommended (e.g. 720x1280 or 1080x1920).
            </p>

            <div class="row g-3 align-items-start">
                <div class="col-md-6">
                    <label for="thumbnail_file" class="form-label small fw-semibold">Upload Cover Image</label>
                    <input type="file"
                           name="thumbnail_file"
                           id="thumbnail_file"
                           class="form-control @error('thumbnail_file') is-invalid @enderror"
                           accept="image/jpeg,image/png,image/webp">
                    @error('thumbnail_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="thumbnail_url" class="form-label small fw-semibold">Or Cover Image URL</label>
                    <input type="url"
                           name="thumbnail_url"
                           id="thumbnail_url"
                           class="form-control @error('thumbnail_url') is-invalid @enderror"
                           value="{{ old('thumbnail_url', $reel->thumbnail_url ?? '') }}"
                           placeholder="https://cdn.example.com/thumbnails/hair-mask-cover.jpg">
                    @error('thumbnail_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            @if (isset($reel) && $reel->thumbnail_url)
                <div class="mt-3">
                    <span class="small text-muted d-block mb-1">Current Cover Preview:</span>
                    <img src="{{ image_url($reel->thumbnail_url) }}" alt="Preview" class="rounded border" style="width: 90px; height: 160px; object-fit: cover;">
                </div>
            @endif
        </div>
    </div>

    {{-- Right Sidebar: Shoppable Product & Settings --}}
    <div class="col-lg-4">
        {{-- Tagged Product Card --}}
        <div class="card border-0 shadow-xs p-4 mb-4">
            <h6 class="fw-bold mb-2 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-bag-check text-success"></i> Tag Shoppable Product
            </h6>
            <p class="small text-muted mb-3">
                Select the product featured in this reel. An instant "Add to Cart" button will appear on the video player.
            </p>

            <div class="mb-3">
                <label for="product_id" class="form-label small fw-semibold">Featured Product</label>
                <select name="product_id" id="product_id" class="form-select @error('product_id') is-invalid @enderror">
                    <option value="">— None (Video only) —</option>
                    @foreach ($products as $prod)
                        <option value="{{ $prod->id }}" @selected(old('product_id', $reel->product_id ?? '') == $prod->id)>
                            {{ $prod->name }} ({{ format_price($prod->selling_price) }})
                        </option>
                    @endforeach
                </select>
                @error('product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        {{-- Display & Ordering Settings --}}
        <div class="card border-0 shadow-xs p-4 mb-4">
            <h6 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-sliders"></i> Display Settings
            </h6>

            <div class="mb-3">
                <label for="views_count" class="form-label small fw-semibold">View Count Badge <span class="text-muted fw-normal">(Optional)</span></label>
                <input type="text"
                       name="views_count"
                       id="views_count"
                       class="form-control @error('views_count') is-invalid @enderror"
                       value="{{ old('views_count', $reel->views_count ?? '') }}"
                       placeholder="e.g. 18.5K or 24,000+">
                <div class="form-text small">Shown as a social proof badge on the reel card.</div>
                @error('views_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label for="likes_count" class="form-label small fw-semibold">Likes Count</label>
                    <input type="number"
                           name="likes_count"
                           id="likes_count"
                           class="form-control @error('likes_count') is-invalid @enderror"
                           value="{{ old('likes_count', $reel->likes_count ?? 150) }}"
                           min="0">
                    @error('likes_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-6">
                    <label for="shares_count" class="form-label small fw-semibold">Shares Count</label>
                    <input type="number"
                           name="shares_count"
                           id="shares_count"
                           class="form-control @error('shares_count') is-invalid @enderror"
                           value="{{ old('shares_count', $reel->shares_count ?? 45) }}"
                           min="0">
                    @error('shares_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="sort_order" class="form-label small fw-semibold">Sort Order</label>
                <input type="number"
                       name="sort_order"
                       id="sort_order"
                       class="form-control @error('sort_order') is-invalid @enderror"
                       value="{{ old('sort_order', $reel->sort_order ?? 0) }}"
                       min="0"
                       step="1">
                <div class="form-text small">Lower numbers appear first on the homepage (0, 1, 2...).</div>
                @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="form-check form-switch pt-2">
                <input class="form-check-input"
                       type="checkbox"
                       role="switch"
                       name="is_active"
                       id="is_active"
                       value="1"
                       @checked(old('is_active', $reel->is_active ?? true))>
                <label class="form-check-label fw-semibold small" for="is_active">Publish to Homepage</label>
                <div class="form-text small">Uncheck to hide this reel without deleting it.</div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i> {{ isset($reel) ? 'Update Reel' : 'Publish Reel' }}
            </button>
            <a href="{{ route('admin.reels.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</div>


<div class="row g-4">
    <div class="col-lg-8">
        {{-- Video File Card (Primary) --}}
        <div class="card border-0 shadow-xs p-4 mb-4" id="vrVideoCard">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-play-circle-fill text-success"></i> Video File (Shoppable Reel)
                </h6>
                <span class="badge bg-light text-muted border">Vertical 9:16</span>
            </div>
            <p class="small text-muted mb-3">
                Upload your vertical video file (9:16). Videos will autoplay simultaneously on loop without volume on your homepage carousel, and unmute with product purchase controls when clicked.
            </p>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="video_file" class="form-label small fw-semibold">
                        Upload Video File <span class="text-muted fw-normal">(MP4, MOV, WebM — Max 100MB)</span>
                    </label>
                    <input type="file"
                           name="video_file"
                           id="video_file"
                           class="form-control @error('video_file') is-invalid @enderror"
                           accept="video/mp4,video/webm,video/quicktime,video/m4v">
                    <div class="form-text small">Recommended: Under 40MB for optimal mobile streaming speed.</div>
                    @error('video_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="video_url" class="form-label small fw-semibold">
                        Or Video Path / URL <span class="text-muted fw-normal">(Storage / CDN / Cloud)</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted"><i class="bi bi-link-45deg"></i></span>
                        <input type="text"
                               name="video_url"
                               id="video_url"
                               class="form-control @error('video_url') is-invalid @enderror"
                               value="{{ old('video_url', $reel->video_url ?? '') }}"
                               placeholder="storage/reels/... or https://cdn.example.com/video.mp4">
                    </div>
                    <div class="form-text small">Auto-filled when uploading, or paste an external video link.</div>
                    @error('video_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- Selected File Info Panel --}}
            <div id="vrFileInfoBox" class="mt-3 p-3 bg-light rounded border d-none">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 min-w-0">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                            <i class="bi bi-film fs-5"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="fw-semibold text-dark text-truncate" id="vrFileName" style="max-width: 280px;">video.mp4</div>
                            <div class="small text-muted" id="vrFileSize">0 MB</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-success text-white" id="vrUploadNowBtn">
                            <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload Video Now
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="vrClearFileBtn" title="Clear selection">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
                <div id="vrSizeWarning" class="alert alert-warning py-1 px-2 mt-2 mb-0 small d-none">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <span>This video is over 40MB. Large uploads may take longer or require compression.</span>
                </div>
            </div>

            {{-- Live Upload Progress & Loader Box --}}
            <div id="vrUploadProgressBox" class="mt-3 p-3 rounded border border-primary bg-light d-none">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-semibold small text-primary d-flex align-items-center gap-2" id="vrUploadStatus">
                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                        <span>Uploading video file... Please wait</span>
                    </span>
                    <span class="badge bg-primary fs-6 px-2.5 py-1" id="vrUploadPercent">0%</span>
                </div>
                <div class="progress" style="height: 12px; border-radius: 6px; background-color: rgba(38,61,37,0.12);">
                    <div id="vrUploadProgressBar"
                         class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                         role="progressbar"
                         style="width: 0%;"
                         aria-valuenow="0"
                         aria-valuemin="0"
                         aria-valuemax="100"></div>
                </div>
                <div class="d-flex align-items-center justify-content-between mt-2 small text-muted">
                    <span id="vrUploadBytes">0 MB of 0 MB uploaded</span>
                    <span id="vrUploadSpeed"><i class="bi bi-arrow-up text-primary"></i> Uploading...</span>
                </div>
            </div>

            {{-- Upload Success Confirmation Alert --}}
            <div id="vrUploadSuccessAlert" class="mt-3 alert alert-success d-flex align-items-start gap-2 mb-0 d-none" role="alert">
                <i class="bi bi-check-circle-fill fs-5 text-success flex-shrink-0 mt-0.5"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold" id="vrSuccessTitle">Video Uploaded Successfully!</div>
                    <div class="small" id="vrSuccessMessage">Your video has been saved on the server and is ready for publishing.</div>
                </div>
            </div>

            {{-- Upload Failed Error Alert --}}
            <div id="vrUploadErrorAlert" class="mt-3 alert alert-danger d-flex align-items-start gap-2 mb-0 d-none" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5 text-danger flex-shrink-0 mt-0.5"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold" id="vrErrorTitle">Upload Failed</div>
                    <div class="small" id="vrErrorMessage">An error occurred while uploading your video file. Please check the file and try again.</div>
                </div>
                <button type="button" class="btn-close btn-sm" aria-label="Close" onclick="this.closest('#vrUploadErrorAlert').classList.add('d-none')"></button>
            </div>

            {{-- Video Preview Player --}}
            <div id="vrPreviewContainer" class="mt-3 {{ (isset($reel) && $reel->video_source) ? '' : 'd-none' }}">
                <div class="small text-muted fw-semibold mb-2 d-flex align-items-center gap-1">
                    <i class="bi bi-play-btn-fill text-success"></i> Video Player Preview:
                </div>
                <div class="d-flex align-items-start gap-3 flex-wrap">
                    <div class="position-relative bg-dark rounded overflow-hidden" style="width: 175px; aspect-ratio: 9/16; box-shadow: 0 4px 16px rgba(0,0,0,0.25);">
                        <video id="vrPreviewPlayer"
                               src="{{ isset($reel) ? $reel->video_source : '' }}"
                               controls
                               playsinline
                               style="width: 100%; height: 100%; object-fit: cover;"></video>
                    </div>
                    <div class="small text-muted" style="max-width: 320px;">
                        <div class="fw-semibold text-dark mb-1">Instant Preview Check</div>
                        <p class="mb-2">Confirm your vertical framing (9:16) and audio. On the storefront, videos autoplay in silence on the carousel and unmute when opened.</p>
                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" id="vrPreviewBadge">
                            <i class="bi bi-check-circle me-1"></i> Ready
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Reel Details Card --}}
        <div class="card border-0 shadow-xs p-4 mb-4">
            <h6 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-card-text text-primary"></i> Reel Details &amp; Instagram Link
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
                <label for="instagram_url" class="form-label small fw-semibold">Instagram Post Link <span class="text-muted fw-normal">(Optional)</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-light text-danger"><i class="bi bi-instagram"></i></span>
                    <input type="url"
                           name="instagram_url"
                           id="instagram_url"
                           class="form-control @error('instagram_url') is-invalid @enderror"
                           value="{{ old('instagram_url', $reel->instagram_url ?? '') }}"
                           placeholder="https://www.instagram.com/reel/DfKecY2gY8K/">
                </div>
                <div class="form-text small">Optional link to your original Instagram post if visitors want to view your profile.</div>
                @error('instagram_url') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="caption" class="form-label small fw-semibold">Caption / Routine Notes <span class="text-muted fw-normal">(Optional)</span></label>
                <textarea name="caption"
                          id="caption"
                          rows="3"
                          class="form-control @error('caption') is-invalid @enderror"
                          placeholder="Share routine steps or product benefits featured in this video...">{{ old('caption', $reel->caption ?? '') }}</textarea>
                @error('caption') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
            <button type="submit" class="btn btn-primary btn-lg" id="vrSubmitBtn">
                <i class="bi bi-check-lg me-1"></i> {{ isset($reel) ? 'Update Reel' : 'Publish Reel' }}
            </button>
            <a href="{{ route('admin.reels.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('vrReelForm');
    var fileInput = document.getElementById('video_file');
    var urlInput = document.getElementById('video_url');
    var infoBox = document.getElementById('vrFileInfoBox');
    var fileName = document.getElementById('vrFileName');
    var fileSize = document.getElementById('vrFileSize');
    var sizeWarning = document.getElementById('vrSizeWarning');
    var uploadNowBtn = document.getElementById('vrUploadNowBtn');
    var clearFileBtn = document.getElementById('vrClearFileBtn');
    var progressBox = document.getElementById('vrUploadProgressBox');
    var progressBar = document.getElementById('vrUploadProgressBar');
    var uploadPercent = document.getElementById('vrUploadPercent');
    var uploadStatus = document.getElementById('vrUploadStatus');
    var uploadBytes = document.getElementById('vrUploadBytes');
    var uploadSpeed = document.getElementById('vrUploadSpeed');
    var successAlert = document.getElementById('vrUploadSuccessAlert');
    var successTitle = document.getElementById('vrSuccessTitle');
    var successMessage = document.getElementById('vrSuccessMessage');
    var errorAlert = document.getElementById('vrUploadErrorAlert');
    var errorTitle = document.getElementById('vrErrorTitle');
    var errorMessage = document.getElementById('vrErrorMessage');
    var previewContainer = document.getElementById('vrPreviewContainer');
    var previewPlayer = document.getElementById('vrPreviewPlayer');
    var submitBtn = document.getElementById('vrSubmitBtn');

    var uploadUrl = "{{ route('admin.reels.upload-video') }}";
    var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '{{ csrf_token() }}';
    var originalSubmitHtml = submitBtn ? submitBtn.innerHTML : 'Publish Reel';
    var isUploading = false;

    function formatBytes(bytes) {
        if (!bytes || bytes === 0) return '0 MB';
        var mb = bytes / (1024 * 1024);
        return mb.toFixed(1) + ' MB';
    }

    function hideAlerts() {
        if (successAlert) successAlert.classList.add('d-none');
        if (errorAlert) errorAlert.classList.add('d-none');
    }

    function showError(title, msg) {
        hideAlerts();
        if (errorTitle) errorTitle.textContent = title || 'Upload Failed';
        if (errorMessage) errorMessage.textContent = msg || 'An error occurred during upload.';
        if (errorAlert) {
            errorAlert.classList.remove('d-none');
            errorAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function showSuccess(title, msg) {
        hideAlerts();
        if (successTitle) successTitle.textContent = title || 'Upload Successful!';
        if (successMessage) successMessage.textContent = msg || 'Your video file has been uploaded.';
        if (successAlert) {
            successAlert.classList.remove('d-none');
            successAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    // 1. File Input Selection Change
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            hideAlerts();
            if (progressBox) progressBox.classList.add('d-none');

            var file = fileInput.files && fileInput.files[0];
            if (!file) {
                if (infoBox) infoBox.classList.add('d-none');
                return;
            }

            // Check format
            var validTypes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/m4v', 'video/ogg'];
            var isVideo = validTypes.indexOf(file.type) !== -1 || /\.(mp4|webm|mov|m4v|ogg)$/i.test(file.name);
            if (!isVideo) {
                showError('Invalid File Format', 'Please select a valid video file (.mp4, .webm, .mov, or .m4v).');
                fileInput.value = '';
                if (infoBox) infoBox.classList.add('d-none');
                return;
            }

            // Update Info Box
            if (fileName) fileName.textContent = file.name;
            if (fileSize) fileSize.textContent = formatBytes(file.size);
            if (infoBox) infoBox.classList.remove('d-none');

            // Size Warning (over 40MB)
            if (sizeWarning) {
                if (file.size > 40 * 1024 * 1024) {
                    sizeWarning.classList.remove('d-none');
                } else {
                    sizeWarning.classList.add('d-none');
                }
            }

            // Instant Local Video Preview
            try {
                var blobUrl = URL.createObjectURL(file);
                if (previewPlayer) {
                    previewPlayer.src = blobUrl;
                    previewPlayer.load();
                }
                if (previewContainer) previewContainer.classList.remove('d-none');
            } catch (err) {}
        });
    }

    // 2. Clear File Selection Button
    if (clearFileBtn) {
        clearFileBtn.addEventListener('click', function () {
            if (fileInput) fileInput.value = '';
            if (infoBox) infoBox.classList.add('d-none');
            hideAlerts();
            if (progressBox) progressBox.classList.add('d-none');
        });
    }

    // 3. Standalone "Upload Video Now" Button
    if (uploadNowBtn) {
        uploadNowBtn.addEventListener('click', function () {
            var file = fileInput && fileInput.files && fileInput.files[0];
            if (!file) {
                showError('No File Selected', 'Please choose a video file first before clicking upload.');
                return;
            }
            performVideoOnlyUpload(file);
        });
    }

    function performVideoOnlyUpload(file) {
        hideAlerts();
        isUploading = true;
        uploadNowBtn.disabled = true;
        uploadNowBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Uploading...';
        if (submitBtn) submitBtn.disabled = true;

        if (progressBox) progressBox.classList.remove('d-none');
        updateProgress(0, file.size, 0);

        var formData = new FormData();
        formData.append('video_file', file);
        formData.append('_token', csrfToken);

        var startTime = Date.now();
        var xhr = new XMLHttpRequest();

        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) {
                var percent = Math.round((e.loaded / e.total) * 100);
                var elapsedSec = (Date.now() - startTime) / 1000;
                var speedBps = elapsedSec > 0 ? (e.loaded / elapsedSec) : 0;
                var speedText = formatBytes(speedBps) + '/s';
                updateProgress(percent, e.total, e.loaded, speedText);
            }
        };

        xhr.onload = function () {
            isUploading = false;
            uploadNowBtn.disabled = false;
            uploadNowBtn.innerHTML = '<i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload Video Now';
            if (submitBtn) submitBtn.disabled = false;

            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res.success && res.video_url) {
                        if (urlInput) urlInput.value = res.video_url;
                        if (fileInput) fileInput.value = '';
                        if (infoBox) infoBox.classList.add('d-none');
                        if (progressBox) progressBox.classList.add('d-none');
                        showSuccess('Video Uploaded Successfully!', 'Saved at "' + res.video_url + '". Ready for publishing.');
                        if (previewPlayer && res.full_url) {
                            previewPlayer.src = res.full_url;
                            previewPlayer.load();
                        }
                    } else {
                        showError('Upload Error', res.message || 'Unexpected response from server.');
                    }
                } catch (e) {
                    showError('Upload Error', 'Could not parse server response.');
                }
            } else {
                handleXhrError(xhr);
            }
        };

        xhr.onerror = function () {
            isUploading = false;
            uploadNowBtn.disabled = false;
            uploadNowBtn.innerHTML = '<i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload Video Now';
            if (submitBtn) submitBtn.disabled = false;
            if (progressBox) progressBox.classList.add('d-none');
            showError('Network Error', 'Failed to communicate with server. Please check your internet connection.');
        };

        xhr.open('POST', uploadUrl, true);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.send(formData);
    }

    function updateProgress(percent, totalBytes, loadedBytes, speedText) {
        if (progressBar) progressBar.style.width = percent + '%';
        if (uploadPercent) uploadPercent.textContent = percent + '%';
        if (uploadBytes) {
            uploadBytes.textContent = formatBytes(loadedBytes) + ' of ' + formatBytes(totalBytes) + ' uploaded';
        }
        if (uploadSpeed && speedText) {
            uploadSpeed.innerHTML = '<i class="bi bi-arrow-up text-primary"></i> ' + speedText;
        }
        if (uploadStatus) {
            if (percent >= 100) {
                uploadStatus.innerHTML = '<span class="spinner-border spinner-border-sm text-success" role="status"></span> <span class="text-success fw-bold">Upload complete! Saving video on server...</span>';
            } else {
                uploadStatus.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" role="status"></span> <span>Uploading video (' + percent + '%)... Please wait</span>';
            }
        }
    }

    function handleXhrError(xhr) {
        if (progressBox) progressBox.classList.add('d-none');
        var errorMsg = 'An error occurred while uploading.';
        try {
            var data = JSON.parse(xhr.responseText);
            if (data.errors && data.errors.video_file) {
                errorMsg = data.errors.video_file.join(', ');
            } else if (data.message) {
                errorMsg = data.message;
            }
        } catch (e) {
            if (xhr.status === 413) {
                errorMsg = 'File is too large for the server. Maximum allowed size is 40MB.';
            } else if (xhr.status === 500) {
                errorMsg = 'Internal Server Error (500). Please check your server storage configuration.';
            } else if (xhr.status === 419) {
                errorMsg = 'Page session expired (CSRF token mismatch). Please refresh the page and try again.';
            }
        }
        showError('Upload Failed (' + xhr.status + ')', errorMsg);
    }

    // 4. Form Submit with AJAX & Progress if Video File is attached
    if (form) {
        form.addEventListener('submit', function (e) {
            var file = fileInput && fileInput.files && fileInput.files[0];
            if (!file) {
                // No new file attached, allow normal form submission or quick submit
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Saving Reel...';
                }
                return;
            }

            e.preventDefault();
            hideAlerts();

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Uploading Video &amp; Saving Reel...';
            }
            if (progressBox) progressBox.classList.remove('d-none');
            updateProgress(0, file.size, 0);

            var formData = new FormData(form);
            var startTime = Date.now();
            var xhr = new XMLHttpRequest();

            xhr.upload.onprogress = function (ev) {
                if (ev.lengthComputable) {
                    var pct = Math.round((ev.loaded / ev.total) * 100);
                    var elapsedSec = (Date.now() - startTime) / 1000;
                    var speedBps = elapsedSec > 0 ? (ev.loaded / elapsedSec) : 0;
                    var speedText = formatBytes(speedBps) + '/s';
                    updateProgress(pct, ev.total, ev.loaded, speedText);
                }
            };

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var res = JSON.parse(xhr.responseText);
                        updateProgress(100, file.size, file.size);
                        showSuccess('Video Reel Published Successfully!', 'All details and video file have been saved. Redirecting to reels dashboard...');
                        if (submitBtn) {
                            submitBtn.className = 'btn btn-success btn-lg';
                            submitBtn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Published!';
                        }
                        setTimeout(function () {
                            window.location.href = res.redirect_url || "{{ route('admin.reels.index') }}";
                        }, 800);
                    } catch (err) {
                        window.location.href = "{{ route('admin.reels.index') }}";
                    }
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalSubmitHtml;
                    }
                    handleXhrError(xhr);
                }
            };

            xhr.onerror = function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalSubmitHtml;
                }
                if (progressBox) progressBox.classList.add('d-none');
                showError('Upload Interrupted', 'A network error occurred during upload. Please check your connection and try again.');
            };

            xhr.open(form.method || 'POST', form.action, true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(formData);
        });
    }
});
</script>
@endpush


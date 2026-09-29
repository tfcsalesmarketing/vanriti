@php
    $shareUrl = url()->current();
    $shareTitle = (string) $product->name;
    $shareText = $shareTitle.' — '.store_name();
    $enc = fn (string $value) => rawurlencode($value);

    $shareLinks = [
        [
            'key' => 'whatsapp',
            'icon' => 'bi-whatsapp',
            'label' => 'Share on WhatsApp',
            'href' => 'https://wa.me/?text='.$enc($shareText.' '.$shareUrl),
        ],
        [
            'key' => 'facebook',
            'icon' => 'bi-facebook',
            'label' => 'Share on Facebook',
            'href' => 'https://www.facebook.com/sharer/sharer.php?u='.$enc($shareUrl),
        ],
        [
            'key' => 'x',
            'icon' => 'bi-twitter-x',
            'label' => 'Share on X',
            'href' => 'https://twitter.com/intent/tweet?url='.$enc($shareUrl).'&text='.$enc($shareText),
        ],
        [
            'key' => 'linkedin',
            'icon' => 'bi-linkedin',
            'label' => 'Share on LinkedIn',
            'href' => 'https://www.linkedin.com/sharing/share-offsite/?url='.$enc($shareUrl),
        ],
        [
            'key' => 'telegram',
            'icon' => 'bi-telegram',
            'label' => 'Share on Telegram',
            'href' => 'https://t.me/share/url?url='.$enc($shareUrl).'&text='.$enc($shareText),
        ],
        [
            'key' => 'pinterest',
            'icon' => 'bi-pinterest',
            'label' => 'Save on Pinterest',
            'href' => 'https://pinterest.com/pin/create/button/?url='.$enc($shareUrl).'&description='.$enc($shareText),
        ],
        [
            'key' => 'email',
            'icon' => 'bi-envelope-fill',
            'label' => 'Share by email',
            'href' => 'mailto:?subject='.$enc($shareText).'&body='.$enc($shareUrl),
        ],
        [
            'key' => 'instagram',
            'icon' => 'bi-instagram',
            'label' => 'Open in Instagram (copies the link)',
            'href' => 'https://www.instagram.com/',
            'app' => true,
        ],
        [
            'key' => 'threads',
            'icon' => 'bi-threads',
            'label' => 'Open in Threads (copies the link)',
            'href' => 'https://www.threads.net/',
            'app' => true,
        ],
        [
            'key' => 'messenger',
            'icon' => 'bi-messenger',
            'label' => 'Open in Messenger (copies the link)',
            'href' => 'https://www.messenger.com/',
            'app' => true,
        ],
    ];
@endphp

<div class="vr-share"
     id="vrProductShare"
     data-share-url="{{ $shareUrl }}"
     data-share-title="{{ $shareTitle }}"
     data-share-text="{{ $shareText }}">
    <span class="vr-share-label">Share</span>

    <div class="vr-share-list">
        @foreach ($shareLinks as $link)
            <a class="vr-share-btn"
               href="{{ $link['href'] }}"
               target="_blank"
               rel="noopener noreferrer"
               aria-label="{{ $link['label'] }}"
               title="{{ $link['label'] }}"
               data-share-network="{{ $link['key'] }}"
               @if (! empty($link['app'])) data-share-app @endif
               @if (! empty($link['app'])) data-share-href="{{ $link['href'] }}" @endif>
                <i class="bi {{ $link['icon'] }}" aria-hidden="true"></i>
            </a>
        @endforeach

        <button type="button" class="vr-share-btn" data-share-copy aria-label="Copy product link" title="Copy product link">
            <i class="bi bi-link-45deg" aria-hidden="true"></i>
        </button>

        <button type="button" class="vr-share-btn" data-share-native hidden aria-label="Share product" title="Share product">
            <i class="bi bi-share" aria-hidden="true"></i>
        </button>
    </div>

    <span class="vr-share-status" data-share-status role="status" aria-live="polite"></span>
</div>

@once
    @push('scripts')
        <script nonce="{{ $cspNonce }}">
            (function () {
                var root = document.getElementById('vrProductShare');

                if (!root) {
                    return;
                }

                var shareUrl = root.getAttribute('data-share-url') || window.location.href;
                var shareTitle = root.getAttribute('data-share-title') || document.title;
                var shareText = root.getAttribute('data-share-text') || shareTitle;
                var statusEl = root.querySelector('[data-share-status]');
                var nativeBtn = root.querySelector('[data-share-native]');
                var resetTimer = null;

                function announce(message) {
                    if (statusEl) {
                        statusEl.textContent = message;
                    }

                    window.clearTimeout(resetTimer);
                    resetTimer = window.setTimeout(function () {
                        if (statusEl) {
                            statusEl.textContent = '';
                        }
                    }, 2200);
                }

                function flashCopied(btn) {
                    if (!btn) {
                        return;
                    }

                    var icon = btn.querySelector('i');
                    var original = icon ? icon.className : '';

                    if (icon) {
                        icon.className = 'bi bi-check-lg';
                    }

                    btn.classList.add('is-copied');

                    window.setTimeout(function () {
                        if (icon) {
                            icon.className = original;
                        }

                        btn.classList.remove('is-copied');
                    }, 2200);
                }

                function legacyCopy(value) {
                    var area = document.createElement('textarea');

                    area.value = value;
                    area.setAttribute('readonly', 'readonly');
                    area.style.position = 'fixed';
                    area.style.top = '-1000px';
                    area.style.opacity = '0';
                    document.body.appendChild(area);
                    area.select();

                    var copied = false;

                    try {
                        copied = document.execCommand('copy');
                    } catch (e) {
                        copied = false;
                    }

                    document.body.removeChild(area);

                    return copied;
                }

                function confirmCopy(btn) {
                    announce('Link copied');
                    flashCopied(btn);
                }

                function copyLink(btn) {
                    if (navigator.clipboard && window.isSecureContext) {
                        navigator.clipboard.writeText(shareUrl).then(function () {
                            confirmCopy(btn);
                        }, function () {
                            if (legacyCopy(shareUrl)) {
                                confirmCopy(btn);
                            } else {
                                announce('Could not copy — long-press the address bar instead.');
                            }
                        });

                        return;
                    }

                    if (legacyCopy(shareUrl)) {
                        confirmCopy(btn);
                    } else {
                        announce('Could not copy — long-press the address bar instead.');
                    }
                }

                root.addEventListener('click', function (event) {
                    var btn = event.target.closest ? event.target.closest('.vr-share-btn') : null;

                    if (!btn) {
                        return;
                    }

                    if (btn.hasAttribute('data-share-app')) {
                        event.preventDefault();
                        window.open(btn.getAttribute('data-share-href'), '_blank', 'noopener');
                        copyLink(btn);

                        return;
                    }

                    if (btn.hasAttribute('data-share-copy')) {
                        event.preventDefault();
                        copyLink(btn);

                        return;
                    }

                    if (btn.hasAttribute('data-share-native')) {
                        event.preventDefault();

                        if (navigator.share) {
                            navigator.share({ title: shareTitle, text: shareText, url: shareUrl }).catch(function () {});
                        } else {
                            copyLink(btn);
                        }
                    }
                });

                if (nativeBtn && navigator.share) {
                    nativeBtn.removeAttribute('hidden');
                }
            })();
        </script>
    @endpush
@endonce

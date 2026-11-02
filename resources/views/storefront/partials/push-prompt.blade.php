{{-- Modern Ayurvedic Web Push Notification Soft-Prompt --}}
<div id="vrPushPromptCard" class="vr-push-prompt-card d-none" aria-live="polite">
    <div class="d-flex align-items-start gap-3">
        <div class="vr-push-icon-box flex-shrink-0">
            <i class="bi bi-bell-fill"></i>
            <span class="vr-push-pulse"></span>
        </div>
        <div class="flex-grow-1 min-w-0">
            <div class="fw-bold text-dark small mb-0.5" style="letter-spacing: 0.2px;">Get Secret Flash Sales &amp; Price Drops</div>
            <div class="text-muted font-2xs mb-2" style="font-size: 0.76rem; line-height: 1.35;">
                Stay updated on fresh Ayurvedic stock, flash discounts &amp; order alerts directly on your device.
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" id="vrPushAllowBtn" class="btn btn-sm btn-success fw-semibold px-3 py-1 text-white rounded-pill shadow-xs" style="font-size: 0.78rem;">
                    Allow Alerts
                </button>
                <button type="button" id="vrPushDismissBtn" class="btn btn-sm btn-link text-muted text-decoration-none px-2 py-1 font-2xs" style="font-size: 0.75rem;">
                    Maybe Later
                </button>
            </div>
        </div>
        <button type="button" class="btn-close ms-auto font-2xs p-1" id="vrPushCloseBtn" aria-label="Close"></button>
    </div>
</div>

<style>
.vr-push-prompt-card {
    position: fixed;
    bottom: 24px;
    left: 24px;
    max-width: 380px;
    width: calc(100% - 48px);
    background: #ffffff;
    border-radius: 14px;
    padding: 14px 16px;
    box-shadow: 0 12px 35px rgba(10, 29, 55, 0.18), 0 2px 8px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(212, 155, 36, 0.3);
    border-left: 4px solid #15803d;
    z-index: 1045;
    animation: vrPushSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    transition: opacity 0.2s ease, transform 0.2s ease;
}
@media (max-width: 575.98px) {
    .vr-push-prompt-card {
        left: 12px;
        right: 12px;
        bottom: 74px; /* Above mobile bottom bar */
        width: auto;
        padding: 12px 14px;
    }
}
@keyframes vrPushSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.vr-push-icon-box {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(21, 128, 61, 0.1);
    color: #15803d;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    font-size: 1rem;
}
.vr-push-pulse {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 8px;
    height: 8px;
    background: #e11d48;
    border-radius: 50%;
    animation: vrPulseAnim 1.8s infinite;
}
@keyframes vrPulseAnim {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(225, 29, 72, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(225, 29, 72, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(225, 29, 72, 0); }
}
</style>

<script nonce="{{ $cspNonce ?? csp_nonce() }}">
(function () {
    // Only supported in secure contexts with Service Worker & PushManager
    if (!window.isSecureContext || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        return;
    }

    var promptCard = document.getElementById('vrPushPromptCard');
    var allowBtn = document.getElementById('vrPushAllowBtn');
    var dismissBtn = document.getElementById('vrPushDismissBtn');
    var closeBtn = document.getElementById('vrPushCloseBtn');

    function urlB64ToUint8Array(base64String) {
        base64String = (base64String || '').trim();
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        var rawData = window.atob(base64);
        var outputArray = new Uint8Array(rawData.length);

        for (var i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function dismissPrompt() {
        if (!promptCard) return;
        promptCard.style.opacity = '0';
        promptCard.style.transform = 'translateY(15px)';
        setTimeout(function () {
            promptCard.classList.add('d-none');
        }, 250);
        localStorage.setItem('vanriti_push_dismissed_at', Date.now().toString());
    }

    if (dismissBtn) dismissBtn.addEventListener('click', dismissPrompt);
    if (closeBtn) closeBtn.addEventListener('click', dismissPrompt);

    // Core subscription worker routine
    function performSubscription() {
        return fetch('/push/public-key', {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (res) {
            if (!res.ok) throw new Error('Failed to retrieve VAPID key');
            return res.json();
        })
        .then(function (keyData) {
            if (!keyData || !keyData.publicKey) {
                throw new Error('VAPID public key unavailable');
            }

            // Register service worker with root scope and wait until ready
            return navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then(function () {
                    return navigator.serviceWorker.ready;
                })
                .then(function (registration) {
                    return registration.pushManager.getSubscription().then(function (existingSub) {
                        if (existingSub) {
                            return existingSub;
                        }
                        return registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: urlB64ToUint8Array(keyData.publicKey)
                        });
                    });
                });
        })
        .then(function (subscription) {
            if (!subscription) throw new Error('No push subscription object');

            var subJson = subscription.toJSON();
            var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            return fetch('/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify(subJson)
            });
        })
        .then(function (res) {
            return res.json();
        });
    }

    // 1. If user already granted permission previously, silently sync in the background
    if (Notification.permission === 'granted') {
        performSubscription().catch(function (err) {
            console.warn('[VANRITI WebPush] Background sync failed:', err);
        });
        return;
    }

    // 2. If user hasn't decided yet, display the gentle soft opt-in prompt
    if (Notification.permission === 'default' && promptCard) {
        var forceShow = window.location.search.indexOf('push_prompt=1') !== -1;
        var dismissedAt = localStorage.getItem('vanriti_push_dismissed_at');
        var wasRecentlyDismissed = dismissedAt && (Date.now() - parseInt(dismissedAt, 10)) < (3 * 24 * 60 * 60 * 1000);

        if (forceShow || !wasRecentlyDismissed) {
            setTimeout(function () {
                promptCard.classList.remove('d-none');
            }, 2500);
        }

        if (allowBtn) {
            allowBtn.addEventListener('click', function () {
                allowBtn.disabled = true;
                allowBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Subscribing...';

                Notification.requestPermission().then(function (permission) {
                    if (permission !== 'granted') {
                        dismissPrompt();
                        return;
                    }

                    performSubscription()
                        .then(function () {
                            promptCard.innerHTML = '<div class="d-flex align-items-center gap-2 text-success py-1"><i class="bi bi-check-circle-fill fs-5"></i><span class="small fw-semibold">Notifications enabled! Thank you.</span></div>';
                            setTimeout(function () {
                                promptCard.classList.add('d-none');
                            }, 3000);
                        })
                        .catch(function (err) {
                            console.error('[VANRITI WebPush] Subscription error:', err);
                            dismissPrompt();
                        });
                });
            });
        }
    }
})();
</script>


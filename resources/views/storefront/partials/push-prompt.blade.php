{{-- Modern Ayurvedic Web Push Notification Soft-Prompt (Centered Modal for Mobile & Desktop) --}}
<div id="vrPushPromptBackdrop" class="vr-push-backdrop d-none" aria-modal="true" role="dialog" aria-labelledby="vrPushTitle">
    <div id="vrPushPromptCard" class="vr-push-modal-card shadow-lg">
        <button type="button" class="btn-close position-absolute top-0 end-0 m-3 font-2xs" id="vrPushCloseBtn" aria-label="Close notification prompt"></button>
        <div class="text-center pt-2 pb-1">
            <div class="vr-push-icon-box mx-auto mb-3">
                <i class="bi bi-bell-fill fs-3"></i>
                <span class="vr-push-pulse"></span>
            </div>
            <h5 class="fw-bold text-dark mb-2" id="vrPushTitle" style="letter-spacing: -0.2px;">Don't Miss Secret Offers &amp; Updates</h5>
            <p class="text-muted small mb-4 px-2" style="line-height: 1.5; font-size: 0.85rem;">
                Enable instant alerts for fresh Ayurvedic batches, VIP flash sales, restock updates &amp; live order tracking directly on your device.
            </p>
            <div class="d-flex flex-column gap-2">
                <button type="button" id="vrPushAllowBtn" class="btn btn-success fw-bold py-2.5 px-4 text-white rounded-pill shadow-sm w-100" style="font-size: 0.9rem; background: #15803d; border-color: #15803d;">
                    <i class="bi bi-bell me-1.5"></i>Allow Notifications
                </button>
                <button type="button" id="vrPushDismissBtn" class="btn btn-link text-muted text-decoration-none py-1.5 font-2xs" style="font-size: 0.8rem;">
                    Maybe Later
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.vr-push-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(10, 29, 55, 0.48);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    z-index: 1060;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    animation: vrPushFadeIn 0.25s ease forwards;
}
.vr-push-modal-card {
    position: relative;
    background: #ffffff;
    border-radius: 20px;
    padding: 28px 24px 22px;
    max-width: 400px;
    width: 100%;
    margin: auto;
    box-shadow: 0 20px 50px rgba(10, 29, 55, 0.25), 0 4px 14px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(212, 155, 36, 0.35);
    animation: vrPushZoomIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@media (max-width: 575.98px) {
    .vr-push-modal-card {
        max-width: calc(100vw - 32px);
        padding: 24px 20px 18px;
    }
}
@keyframes vrPushFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes vrPushZoomIn {
    from {
        opacity: 0;
        transform: scale(0.92);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
.vr-push-icon-box {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(21, 128, 61, 0.12);
    color: #15803d;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
.vr-push-pulse {
    position: absolute;
    top: 3px;
    right: 3px;
    width: 13px;
    height: 13px;
    background: #e11d48;
    border: 2px solid #ffffff;
    border-radius: 50%;
    animation: vrPulseAnim 1.8s infinite;
}
@keyframes vrPulseAnim {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(225, 29, 72, 0.7); }
    70% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(225, 29, 72, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(225, 29, 72, 0); }
}
</style>

<script nonce="{{ $cspNonce ?? csp_nonce() }}">
(function () {
    // Check for Secure Context & Browser Capability
    if (!window.isSecureContext || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        return;
    }

    var VR_PUSH_CONFIG = {
        publicKeyUrl: "{{ route('push.public-key') }}",
        subscribeUrl: "{{ route('push.subscribe') }}",
        swUrl: "{{ url('sw.js') }}",
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || "{{ csrf_token() }}"
    };

    var backdrop = document.getElementById('vrPushPromptBackdrop');
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
        if (!backdrop) return;
        backdrop.style.opacity = '0';
        backdrop.style.transition = 'opacity 0.2s ease';
        setTimeout(function () {
            backdrop.classList.add('d-none');
            backdrop.style.opacity = '';
        }, 220);
        localStorage.setItem('vanriti_push_dismissed_at', Date.now().toString());
    }

    if (dismissBtn) dismissBtn.addEventListener('click', dismissPrompt);
    if (closeBtn) closeBtn.addEventListener('click', dismissPrompt);
    if (backdrop) {
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) dismissPrompt();
        });
    }

    // Core subscription routine
    function performSubscription() {
        return fetch(VR_PUSH_CONFIG.publicKeyUrl, {
            headers: { 'Accept': 'application/json' }
        })
        .then(function (res) {
            if (!res.ok) throw new Error('Failed to retrieve VAPID key (HTTP ' + res.status + ')');
            return res.json();
        })
        .then(function (keyData) {
            if (!keyData || !keyData.publicKey) {
                throw new Error('VAPID public key unavailable');
            }

            return navigator.serviceWorker.register(VR_PUSH_CONFIG.swUrl, { scope: '{{ url("/") }}/' })
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
            try {
                var cartMatch = document.cookie.match(/(^|;)\s*vanriti_cart\s*=\s*([^;]+)/);
                if (cartMatch) {
                    subJson.cart_token = decodeURIComponent(cartMatch[2]);
                }
            } catch (e) {}

            return fetch(VR_PUSH_CONFIG.subscribeUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': VR_PUSH_CONFIG.csrfToken
                },
                body: JSON.stringify(subJson)
            });
        })
        .then(function (res) {
            return res.json();
        });
    }

    // 1. If user already granted permission, silently sync subscription in background
    if (Notification.permission === 'granted') {
        performSubscription().catch(function (err) {
            console.warn('[VANRITI WebPush] Background sync failed:', err);
        });
        return;
    }

    // 2. If user hasn't decided yet, show the centered modal prompt
    if (Notification.permission === 'default' && backdrop) {
        var forceShow = window.location.search.indexOf('push_prompt=1') !== -1;
        var dismissedAt = localStorage.getItem('vanriti_push_dismissed_at');
        var wasRecentlyDismissed = dismissedAt && (Date.now() - parseInt(dismissedAt, 10)) < (3 * 24 * 60 * 60 * 1000);

        if (forceShow || !wasRecentlyDismissed) {
            setTimeout(function () {
                backdrop.classList.remove('d-none');
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
                            promptCard.innerHTML = '<div class="text-center py-3"><div class="text-success mb-2"><i class="bi bi-check-circle-fill fs-1"></i></div><h6 class="fw-bold text-dark mb-1">Notifications Enabled!</h6><p class="text-muted small mb-0">Thank you for staying connected with Vanriti.</p></div>';
                            setTimeout(function () {
                                dismissPrompt();
                            }, 2000);
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

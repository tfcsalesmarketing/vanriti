// Vanriti Herbal & Ayurvedic Care - Web Push Service Worker
self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

// Handle incoming background push notification
self.addEventListener('push', function (event) {
    var payload = {};
    if (event.data) {
        try {
            payload = event.data.json();
        } catch (e) {
            payload = {
                title: 'Vanriti Ayurvedic Care',
                body: event.data.text()
            };
        }
    } else {
        payload = {
            title: 'Vanriti Herbal & Ayurvedic Care',
            body: 'Special Ayurvedic wellness offer waiting for you!'
        };
    }

    var baseScope = self.registration.scope || (self.location.origin + '/');
    var defaultIcon = new URL('images/push-icon-192.png', baseScope).href;
    var defaultBadge = new URL('images/push-badge-96.png', baseScope).href;

    var title = payload.title || 'Vanriti Herbal & Ayurvedic Care';
    var options = {
        body: payload.body || 'Discover pure Ayurvedic wellness on Vanriti.',
        icon: payload.icon || defaultIcon,
        image: payload.image || undefined,
        badge: payload.badge || defaultBadge,
        tag: payload.tag || ('vr-push-' + (payload.data?.campaign_id || Date.now())),
        renotify: true,
        data: payload.data || { url: baseScope },
        actions: [
            { action: 'open', title: 'Shop Now' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Handle notification click event
self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    var baseScope = self.registration.scope || (self.location.origin + '/');
    var data = event.notification.data || {};
    var rawUrl = data.url || baseScope;
    var campaignId = data.campaign_id;

    // Resolve destination URL against service worker base scope
    var targetUrl = baseScope;
    try {
        targetUrl = new URL(rawUrl, baseScope).href;
    } catch (e) {
        targetUrl = baseScope;
    }

    // Ping click tracking endpoint asynchronously
    if (campaignId) {
        try {
            var trackUrl = new URL('push/track-click/' + encodeURIComponent(campaignId), baseScope).href;
            fetch(trackUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                keepalive: true
            }).catch(function () { });
        } catch (e) { }
    }

    // Open destination URL or focus existing open tab
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if ('focus' in client && client.url.indexOf(self.location.origin) !== -1) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })
    );
});

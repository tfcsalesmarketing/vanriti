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

    var title = payload.title || 'Vanriti Herbal & Ayurvedic Care';
    var options = {
        body: payload.body || 'Discover pure Ayurvedic wellness on Vanriti.',
        icon: payload.icon || '/images/logo.png',
        image: payload.image || undefined,
        badge: payload.badge || '/images/logo.png',
        tag: payload.tag || ('vr-push-' + (payload.data?.campaign_id || Date.now())),
        renotify: true,
        data: payload.data || { url: '/' },
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

    var data = event.notification.data || {};
    var rawUrl = data.url || '/';
    var campaignId = data.campaign_id;

    // Resolve destination URL against current origin
    var targetUrl = '/';
    try {
        targetUrl = new URL(rawUrl, self.location.origin).href;
    } catch (e) {
        targetUrl = self.location.origin + '/';
    }

    // Ping click tracking endpoint asynchronously
    if (campaignId) {
        try {
            fetch('/push/track-click/' + encodeURIComponent(campaignId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                keepalive: true
            }).catch(function () {});
        } catch (e) {}
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


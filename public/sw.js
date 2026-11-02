// Vanriti Herbal & Ayurvedic Care - Web Push Service Worker
self.addEventListener('install', function (event) {
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(self.clients.claim());
});

// Handle incoming background push notification
self.addEventListener('push', function (event) {
    if (!event.data) {
        return;
    }

    var payload = {};
    try {
        payload = event.data.json();
    } catch (e) {
        payload = {
            title: 'Vanriti Ayurvedic Care',
            body: event.data.text()
        };
    }

    var title = payload.title || 'Vanriti Herbal & Ayurvedic Care';
    var options = {
        body: payload.body || 'Discover fresh Ayurvedic wellness offers on Vanriti.',
        icon: payload.icon || '/images/logo.png',
        image: payload.image || undefined,
        badge: payload.badge || '/images/logo.png',
        vibrate: [100, 50, 100],
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
    var targetUrl = data.url || '/';
    var campaignId = data.campaign_id;

    // Track click event asynchronously if campaign ID is present
    if (campaignId) {
        try {
            fetch('/push/track-click/' + encodeURIComponent(campaignId), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                keepalive: true
            }).catch(function () {});
        } catch (e) {}
    }

    // Open target URL or focus existing window
    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if ('focus' in client) {
                    if (client.url.indexOf(self.location.origin) !== -1) {
                        client.navigate(targetUrl);
                        return client.focus();
                    }
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })
    );
});


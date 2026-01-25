// Service Worker for Push Notifications
self.addEventListener('push', function (event) {
    if (!event.data) {
        return;
    }

    const data = event.data.json();

    const options = {
        body: data.body || '',
        icon: data.icon || '/capicon.png',
        badge: '/capicon.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.data?.url || '/',
        },
        actions: data.actions || [],
    };

    event.waitUntil(self.registration.showNotification(data.title || 'Capylendar', options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const urlToOpen = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (clientList) {
            // If a window is already open, focus it
            for (const client of clientList) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(urlToOpen);
                    return client.focus();
                }
            }
            // Otherwise, open a new window
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        }),
    );
});

self.addEventListener('notificationclose', function (event) {
    // Optional: track notification dismissals
});

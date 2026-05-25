// Service Worker for Partial Checkout Offline Support
const CACHE_NAME = 'partial-checkout-v1';
const SAVE_URL = '/partial-checkout/save';

// Install event
self.addEventListener('install', event => {
    console.log('Service Worker installing.');
});

// Activate event
self.addEventListener('activate', event => {
    console.log('Service Worker activating.');
});

// Background Sync for queued saves
self.addEventListener('sync', event => {
    if (event.tag === 'partial-checkout-sync') {
        event.waitUntil(syncPartialCheckout());
    }
});

async function syncPartialCheckout() {
    const queue = JSON.parse(localStorage.getItem('partial_queue_v1') || '[]');
    for (const payload of queue) {
        try {
            const response = await fetch(SAVE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(), // You'll need to implement this
                },
                body: JSON.stringify(payload),
            });
            if (response.ok) {
                queue.shift();
            }
        } catch (error) {
            console.error('Sync failed:', error);
        }
    }
    localStorage.setItem('partial_queue_v1', JSON.stringify(queue));
}

// Fetch event for caching
self.addEventListener('fetch', event => {
    // Implement caching if needed
});

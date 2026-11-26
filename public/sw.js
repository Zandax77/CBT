// CBT ExamBro Service Worker — PWA & Offline Resilience
const CACHE_NAME = 'cbt-exambro-v2';

const PRECACHE_ASSETS = [
    '/offline.html',
    '/manifest.json',
    '/css/liquid-glass.css',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'
];

// Install: precache critical assets
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => cache.addAll(PRECACHE_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// Activate: clean up older caches
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch: Strategy depending on request type
self.addEventListener('fetch', event => {
    // Only handle GET requests
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);

    // Bypass real-time, exam submissions, violations, monitoring, and CSRF sensitive endpoints
    const nonCachedPaths = [
        '/jawaban',
        '/selesai',
        '/pelanggaran',
        '/monitoring',
        '/rotate-token',
        '/logout'
    ];
    if (nonCachedPaths.some(p => url.pathname.includes(p))) {
        return;
    }

    // 1. Navigation requests (HTML pages): Network First, fallback to cache, then offline.html
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    if (response && response.status === 200) {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, copy));
                    }
                    return response;
                })
                .catch(() => {
                    return caches.match(event.request).then(cached => {
                        return cached || caches.match('/offline.html');
                    });
                })
        );
        return;
    }

    // 2. Static assets (CSS, JS, Fonts, Images): Cache First with background update
    if (
        url.pathname.match(/\.(css|js|png|jpg|jpeg|svg|webp|ico|woff|woff2|ttf)$/i) ||
        url.hostname.includes('cdn.jsdelivr.net') ||
        url.hostname.includes('fonts.googleapis.com') ||
        url.hostname.includes('fonts.gstatic.com')
    ) {
        event.respondWith(
            caches.match(event.request).then(cachedResponse => {
                const fetchPromise = fetch(event.request).then(networkResponse => {
                    if (networkResponse && networkResponse.status === 200) {
                        const copy = networkResponse.clone();
                        caches.open(CACHE_NAME).then(cache => cache.put(event.request, copy));
                    }
                    return networkResponse;
                }).catch(() => cachedResponse);

                return cachedResponse || fetchPromise;
            })
        );
        return;
    }

    // Default: Network with cache fallback
    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});

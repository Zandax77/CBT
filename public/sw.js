// ExamBro Service Worker — cache halaman login & ujian untuk offline resilience
const CACHE = 'exambro-v1';
const PRECACHE = [
    '/',
    '/login',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
];

self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE).then(c => c.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', e => {
    // Jangan cache request API / AJAX
    if (e.request.method !== 'GET') return;
    const url = new URL(e.request.url);
    if (url.pathname.includes('/jawaban') || url.pathname.includes('/selesai') ||
        url.pathname.includes('/pelanggaran')) return;

    e.respondWith(
        caches.match(e.request).then(cached => cached || fetch(e.request).then(res => {
            if (res && res.status === 200 && res.type === 'basic') {
                const clone = res.clone();
                caches.open(CACHE).then(c => c.put(e.request, clone));
            }
            return res;
        }))
    );
});

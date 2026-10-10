/*
 * Hezar Rial service worker. It does exactly one thing: when the network is down and the user
 * opens a page, show a friendly offline page instead of the browser's error.
 *
 * It never stores pages or data. Anything under /app is financial data and must always come from
 * the server; the browser's normal HTTP cache already keeps the hashed /build files for a year.
 * Bump VERSION when OFFLINE_PAGE changes.
 */
const VERSION = 'v1';
const CACHE = `hezarrial-offline-${VERSION}`;
const OFFLINE_PAGE = '/offline';

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.add(new Request(OFFLINE_PAGE, { cache: 'reload' }))).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key.startsWith('hezarrial-') && key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;

    // Only page navigations. Data requests (Inertia, API, forms) are never touched.
    if (request.mode !== 'navigate' || request.method !== 'GET') {
        return;
    }

    event.respondWith(
        fetch(request).catch(async () => (await caches.match(OFFLINE_PAGE)) ?? new Response('Offline', { status: 503, headers: { 'Content-Type': 'text/plain' } })),
    );
});

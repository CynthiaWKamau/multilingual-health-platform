// Samburu Health service worker
const VERSION      = 'v1';
const STATIC_CACHE = 'static-' + VERSION;
const PAGE_CACHE   = 'pages-' + VERSION;
const BASE         = new URL('./', self.location).pathname;

// Files saved on install
const PRECACHE = ['offline.html', 'manifest.json', 'pwa.js', 'assets/reports.css', 'icons/icon-192.png', 'icons/icon-512.png']
    .map(p => BASE + p);

// Pages available offline
const OFFLINE_PAGES = [
    'dashboard/index.php', 'dashboard/my_activity.php',
    'health/index.php', 'myth/index.php', 'symptom/index.php',
    'chw/index.php', 'chw/report.php'
];

// Pages saved in the background
const WARM_PAGES = ['dashboard/index.php', 'health/index.php', 'myth/index.php', 'symptom/index.php'];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then(cache => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys()
            .then(keys => Promise.all(keys.filter(k => k !== STATIC_CACHE && k !== PAGE_CACHE).map(k => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

// Path inside the app
function appPath(url) {
    const path = new URL(url).pathname;
    return path.startsWith(BASE) ? path.slice(BASE.length) : null;
}

function isOfflinePage(url) {
    return OFFLINE_PAGES.includes(appPath(url));
}

// Network first, saved copy when offline
async function pageResponse(request) {
    try {
        const response = await fetch(request);
        if (response.ok && isOfflinePage(response.url)) {
            const cache = await caches.open(PAGE_CACHE);
            await cache.put(response.url, response.clone());
        }
        return response;
    } catch (err) {
        const cache = await caches.open(PAGE_CACHE);
        return (await cache.match(request))
            || (await cache.match(request, { ignoreSearch: true }))
            || (await offlinePage());
    }
}

// Offline page with app base address
async function offlinePage() {
    const cached = await caches.match(BASE + 'offline.html');
    if (!cached) return new Response('Offline', { status: 503 });
    const html = (await cached.text()).replace('<head>', '<head><base href="' + BASE + '">');
    return new Response(html, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
}

// Cache first for static files
async function staticResponse(request) {
    const cached = await caches.match(request);
    if (cached) return cached;
    try {
        const response = await fetch(request);
        if (response.ok || response.type === 'opaque') {
            const cache = await caches.open(STATIC_CACHE);
            await cache.put(request, response.clone());
        }
        return response;
    } catch (err) {
        return new Response('', { status: 504 });
    }
}

self.addEventListener('fetch', event => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url  = new URL(request.url);
    const path = appPath(request.url);

    // Logout clears saved pages
    if (path === 'auth/logout.php') {
        event.waitUntil(caches.delete(PAGE_CACHE));
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(pageResponse(request));
        return;
    }

    const isFont   = url.hostname === 'fonts.googleapis.com' || url.hostname === 'fonts.gstatic.com';
    const isStatic = path !== null && /\.(css|js|png|json)$/.test(path);
    if (isFont || isStatic) {
        event.respondWith(staticResponse(request));
    }
});

// Background saving of key pages
async function warmPages() {
    const cache = await caches.open(PAGE_CACHE);
    for (const page of WARM_PAGES) {
        try {
            const response = await fetch(BASE + page, { credentials: 'same-origin' });
            if (response.ok && isOfflinePage(response.url)) {
                await cache.put(response.url, response);
            }
        } catch (err) {
            // Skipped while offline
        }
    }
}

self.addEventListener('message', event => {
    if (event.data && event.data.type === 'warm') {
        event.waitUntil(warmPages());
    }
});

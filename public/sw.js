/* Cantine de Vatel — service worker : application installable, menu et QR code consultables hors ligne. */
const CACHE = 'cantine-vatel-v1';
const ASSETS = ['/css/app.css', '/css/extra.css', '/js/app.js', '/vendor/qrcode.min.js', '/img/vatel_logo.png', '/manifest.webmanifest', '/offline.html'];
// Pages mises en cache à chaque visite (réseau d'abord, cache en secours)
const OFFLINE_PAGES = [/^\/menu/, /^\/reservations\/\d+$/, /^\/reservations$/, /^\/accueil$/, /^\/parent/, /^\/menu-public/];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== location.origin) return;

  if (ASSETS.includes(url.pathname) || url.pathname.startsWith('/vendor/') || url.pathname.startsWith('/storage/')) {
    e.respondWith(caches.match(req).then((hit) => hit || fetch(req).then((res) => { const copy = res.clone(); caches.open(CACHE).then((c) => c.put(req, copy)); return res; })));
    return;
  }
  if (req.mode === 'navigate') {
    const cacheable = OFFLINE_PAGES.some((r) => r.test(url.pathname));
    e.respondWith(
      fetch(req).then((res) => {
        if (cacheable && res.ok) { const copy = res.clone(); caches.open(CACHE).then((c) => c.put(req, copy)); }
        return res;
      }).catch(() => caches.match(req).then((hit) => hit || caches.match('/offline.html')))
    );
  }
});

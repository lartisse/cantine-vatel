/* Cantine de Vatel — service worker : application installable, menu et QR code consultables hors ligne. */
const CACHE = 'cantine-vatel-v2';
const ASSETS = ['/css/app.css', '/css/extra.css', '/js/app.js', '/vendor/qrcode.min.js', '/img/vatel_logo.png', '/img/icon-192.png', '/manifest.webmanifest', '/offline.html'];
// Pages mises en cache à chaque visite (réseau d'abord, cache en secours) : menu, réservations et QR, accueil, espace parent
const OFFLINE_PAGES = [/^\/menu(\/|$)/, /^\/plats\/\d+$/, /^\/reservations\/\d+$/, /^\/reservations$/, /^\/accueil$/, /^\/portefeuille$/, /^\/parent(\/|$)/, /^\/menu-public$/];

self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(CACHE).then((c) => c.addAll(ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});

const isStatic = (p) => ASSETS.includes(p) || p.startsWith('/vendor/') || p.startsWith('/css/') || p.startsWith('/js/') || p.startsWith('/img/') || p.startsWith('/storage/');

// Déconnexion : la page envoie « clear » pour effacer les pages personnelles mises en cache (on garde CSS, JS, images)
self.addEventListener('message', (e) => {
  if (e.data !== 'clear') return;
  e.waitUntil(caches.open(CACHE).then((c) => c.keys().then((reqs) =>
    Promise.all(reqs.filter((r) => !isStatic(new URL(r.url).pathname)).map((r) => c.delete(r))))));
});

self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== location.origin || url.pathname.startsWith('/api/')) return;

  // Fichiers statiques : cache d'abord (chaque version porte ?v=… dans l'URL)
  if (isStatic(url.pathname)) {
    e.respondWith(caches.match(req).then((hit) => hit || fetch(req).then((res) => {
      if (res.ok) { const copy = res.clone(); caches.open(CACHE).then((c) => c.put(req, copy)); }
      return res;
    })));
    return;
  }

  // Pages : réseau d'abord ; hors ligne, dernière version consultée, sinon la page « Vous êtes hors ligne »
  if (req.mode === 'navigate') {
    const cacheable = OFFLINE_PAGES.some((r) => r.test(url.pathname));
    e.respondWith(
      fetch(req).then((res) => {
        // jamais une redirection (ex. session expirée → connexion) à la place de la page demandée
        if (cacheable && res.ok && !res.redirected && res.type === 'basic') {
          const copy = res.clone();
          caches.open(CACHE).then((c) => c.put(req, copy));
        }
        return res;
      }).catch(() => caches.match(req)
        .then((hit) => hit || (url.pathname.startsWith('/menu') ? caches.match(req, { ignoreSearch: true }) : null))
        .then((hit) => hit || caches.match('/offline.html')))
    );
  }
});

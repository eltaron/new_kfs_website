/*!
 * KFS Portal Service Worker — بوابة محافظة كفر الشيخ
 * ---------------------------------------------------------------
 * - Versioned caches: CACHE_VERSION comes from the ?v= query used by
 *   the layout (filemtime of sw.js), so every deploy of this file
 *   produces new cache names and old ones are dropped on activate.
 * - Static (same-origin + whitelisted CDNs css/js/fonts): cache-first.
 * - Images: stale-while-revalidate, capped entries.
 * - Public GET HTML pages: network-first with /offline.html fallback.
 *   Pages are cached ONLY for guests, only for successful,
 *   non-redirected, same-origin responses, never for no-store,
 *   and only when the server explicitly marked the page public via
 *   <meta name="kfs-cache" content="public"> (Laravel sends
 *   "Cache-Control: private" by default, so the marker is the
 *   server-side opt-in that this page contains no personal data).
 * - Panels, auth, payments, API, livewire... are never intercepted.
 * - Design system v2 (2026-10): file touched so CACHE_VERSION (filemtime) rolls
 *   and clients drop the old static caches (kfs-ds / index / motion / sections / dashboard css).
 * - Dark/light theme (2026-10): kfs-theme.css added to the precache; file touched again so
 *   CACHE_VERSION rolls and clients pick up the themed kfs-ds / a11y / voice assets.
 */
'use strict';

const SW_URL = new URL(self.location.href);
const CACHE_VERSION = SW_URL.searchParams.get('v') || '1';
const PREFIX = 'kfs-';
const STATIC_CACHE = PREFIX + 'static-v' + CACHE_VERSION;
const IMAGE_CACHE = PREFIX + 'images-v' + CACHE_VERSION;
const PAGE_CACHE = PREFIX + 'pages-v' + CACHE_VERSION;
const META_CACHE = PREFIX + 'meta';
const CURRENT_CACHES = [STATIC_CACHE, IMAGE_CACHE, PAGE_CACHE, META_CACHE];

const IMAGE_MAX_ENTRIES = 80;
const PAGE_MAX_ENTRIES = 40;

// Base path of the app (works when the site is served from a sub-folder).
const BASE = new URL('./', self.location.href).pathname; // e.g. "/"
const OFFLINE_URL = BASE + 'offline.html';

const PRECACHE_URLS = [
  OFFLINE_URL,
  BASE + 'css/kfs-ds.css',
  BASE + 'css/style.css',
  BASE + 'css/index.css',
  BASE + 'css/kfs-motion.css',
  BASE + 'css/kfs-sections.css',
  BASE + 'css/kfs-hero.css',
  BASE + 'css/kfs-portal.css',
  BASE + 'css/kfs-theme.css',
  BASE + 'js/kfs-a11y.js',
  BASE + 'js/kfs-voice.js',
  BASE + 'js/pwa.js',
  BASE + 'js/script.js',
  BASE + 'js/kfs-motion.js',
  BASE + 'js/kfs-portal.js',
  BASE + 'images/places/i3.png',
  BASE + 'images/pwa/icon-192.png',
  BASE + 'images/pwa/icon-512.png',
  BASE + 'images/kfs/kfs-wave.svg',
  BASE + 'images/kfs/kfs-pattern.svg',
  BASE + 'images/kfs/kfs-heritage-line.svg',
  BASE + 'manifest.webmanifest',
];

const CDN_HOSTS = [
  'cdnjs.cloudflare.com',
  'cdn.jsdelivr.net',
  'unpkg.com',
  'fonts.googleapis.com',
  'fonts.gstatic.com',
];

// Paths (relative to BASE, no leading slash) the SW must never touch.
// WILDCARD entries match any path starting with them ("/admin*", "/gis*" ...).
const BYPASS_WILDCARD = [
  'admin', 'gis', 'estidama_panal', 'livewire', 'filament', 'dashboard', 'register',
  'password', 'two-factor', 'payments', 'efinance', 'receipts', 'api',
  // Site validity service: /site-validity (Filament panel) + /site-validity-request/* (citizen pages, private files)
  'site-validity',
];
// SEGMENT entries match the exact path or anything below it ("/login", "/store/cart/...").
const BYPASS_SEGMENT = [
  'login', 'logout', 'forgot-password', 'reset-password', 'account', 'citizen',
  'secure-files', 'erp-governorate', 'broadcasting', 'sanctum', 'store/cart',
  'store/checkout', 'store/orders', 'store/wishlist', 'store/sell', 'syndicate', 'sw.js',
  // Module panels (Filament): /council, /vendor, /store-admin
  'council', 'vendor', 'store-admin', 'two-factor-challenge', 'store/vendors',
  // Admin tooling and signed private-disk downloads
  'log-viewer', 'pulse', 'storage', 'dashboard-monitor',
  'gis-portal/application-success', 'gis-portal/track-request',
  // Private LMS pages and media (range requests for video seeking must reach the server)
  'estidama/my-learning', 'estidama/learn', 'estidama/attempts', 'estidama/lessons',
  'estidama/enrollments', 'estidama/apply',
];

let authState = null; // null = unknown, true = logged in, false = guest

/* ------------------------------------------------------------ helpers */
function relPath(url) {
  let p = url.pathname;
  if (p.indexOf(BASE) === 0) p = p.slice(BASE.length);
  return p.replace(/^\/+/, '');
}

function isBypassed(url) {
  const p = relPath(url).toLowerCase();
  if (BYPASS_WILDCARD.some(function (w) { return p.indexOf(w) === 0; })) return true;
  return BYPASS_SEGMENT.some(function (seg) { return p === seg || p.indexOf(seg + '/') === 0; });
}

function hasNoStore(response) {
  const cc = (response.headers.get('Cache-Control') || '').toLowerCase();
  return cc.indexOf('no-store') !== -1;
}

function isPrivate(response) {
  const cc = (response.headers.get('Cache-Control') || '').toLowerCase();
  return cc.indexOf('private') !== -1;
}

function isStaticAsset(url, request) {
  const dest = request.destination;
  if (dest === 'style' || dest === 'script' || dest === 'font' || dest === 'manifest') return true;
  return /\.(css|js|mjs|woff2?|ttf|otf|eot|webmanifest)$/i.test(url.pathname);
}

function isImage(url, request) {
  return request.destination === 'image' || /\.(png|jpe?g|gif|webp|avif|svg|ico)$/i.test(url.pathname);
}

async function trimCache(name, max) {
  const cache = await caches.open(name);
  const keys = await cache.keys();
  for (let i = 0; i < keys.length - max; i++) {
    await cache.delete(keys[i]);
  }
}

async function clearPages() {
  const names = await caches.keys();
  await Promise.all(names.filter(function (n) { return n.indexOf(PREFIX + 'pages-') === 0; })
    .map(function (n) { return caches.delete(n); }));
}

async function saveAuthState(value) {
  authState = value;
  try {
    const cache = await caches.open(META_CACHE);
    await cache.put(BASE + '__kfs_auth_state', new Response(JSON.stringify({ auth: value })));
  } catch (e) { /* ignore */ }
}

async function loadAuthState() {
  if (authState !== null) return authState;
  try {
    const cache = await caches.open(META_CACHE);
    const res = await cache.match(BASE + '__kfs_auth_state');
    if (res) authState = !!(await res.json()).auth;
  } catch (e) { /* ignore */ }
  return authState;
}

/* ------------------------------------------------------------ lifecycle */
self.addEventListener('install', function (event) {
  event.waitUntil((async function () {
    const cache = await caches.open(STATIC_CACHE);
    // offline page is mandatory; the rest is best-effort.
    await cache.add(new Request(OFFLINE_URL, { cache: 'reload' }));
    await Promise.allSettled(PRECACHE_URLS.filter(function (u) { return u !== OFFLINE_URL; })
      .map(function (u) { return cache.add(new Request(u, { cache: 'reload' })); }));
  })());
  // No automatic skipWaiting: the page asks the user first (update toast).
});

self.addEventListener('activate', function (event) {
  event.waitUntil((async function () {
    const names = await caches.keys();
    await Promise.all(names
      .filter(function (n) { return n.indexOf(PREFIX) === 0 && CURRENT_CACHES.indexOf(n) === -1; })
      .map(function (n) { return caches.delete(n); }));
    await self.clients.claim();
  })());
});

self.addEventListener('message', function (event) {
  const data = event.data || {};
  if (data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  } else if (data.type === 'AUTH_STATE') {
    const next = !!data.auth;
    event.waitUntil((async function () {
      const prev = await loadAuthState();
      if (next || data.changed || (prev !== null && prev !== next)) await clearPages();
      await saveAuthState(next);
    })());
  } else if (data.type === 'CLEAR_PAGES') {
    event.waitUntil(clearPages());
  }
});

/* ------------------------------------------------------------ fetch */
self.addEventListener('fetch', function (event) {
  const request = event.request;
  let url;
  try { url = new URL(request.url); } catch (e) { return; }
  if (url.protocol !== 'http:' && url.protocol !== 'https:') return;

  const sameOrigin = url.origin === self.location.origin;

  if (request.method !== 'GET') {
    // Login / logout / register submissions change who is browsing:
    // drop any cached HTML (never respondWith — the network handles it).
    if (sameOrigin && /^(login|logout|register|two-factor)/i.test(relPath(url))) {
      event.waitUntil(clearPages());
    }
    return;
  }
  if (request.headers.has('Authorization')) return;
  if (request.headers.has('Range')) return;
  if (sameOrigin && isBypassed(url)) return;
  if (!sameOrigin && CDN_HOSTS.indexOf(url.hostname) === -1) return;

  if (request.mode === 'navigate' || (request.headers.get('Accept') || '').indexOf('text/html') !== -1) {
    if (!sameOrigin) return;
    event.respondWith(networkFirstPage(event, request));
    return;
  }

  if (isStaticAsset(url, request)) {
    event.respondWith(cacheFirst(request, STATIC_CACHE));
    return;
  }

  if (isImage(url, request)) {
    event.respondWith(staleWhileRevalidate(event, request, IMAGE_CACHE, IMAGE_MAX_ENTRIES));
  }
});

async function cacheFirst(request, cacheName) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);
  if (cached) return cached;
  const response = await fetch(request);
  if (response && response.ok && !hasNoStore(response) && (response.type === 'basic' || response.type === 'cors')) {
    cache.put(request, response.clone()).catch(function () {});
  }
  return response;
}

async function staleWhileRevalidate(event, request, cacheName, maxEntries) {
  const cache = await caches.open(cacheName);
  const cached = await cache.match(request);
  const network = fetch(request).then(function (response) {
    if (response && response.ok && !hasNoStore(response) && !isPrivate(response) &&
        (response.type === 'basic' || response.type === 'cors')) {
      return cache.put(request, response.clone())
        .then(function () { return trimCache(cacheName, maxEntries); })
        .then(function () { return response; });
    }
    return response;
  }).catch(function () { return cached; });
  if (cached) {
    event.waitUntil(network.catch(function () {}));
    return cached;
  }
  return network.then(function (r) { return r || Response.error(); });
}

async function networkFirstPage(event, request) {
  try {
    const response = await fetch(request);
    event.waitUntil(maybeCachePage(request, response.clone()));
    return response;
  } catch (err) {
    const isAuth = await loadAuthState();
    if (!isAuth) {
      const cache = await caches.open(PAGE_CACHE);
      const cached = await cache.match(request, { ignoreVary: true });
      if (cached) return cached;
    }
    const offline = await caches.match(OFFLINE_URL);
    return offline || new Response('<h1 dir="rtl">لا يوجد اتصال بالإنترنت</h1>', {
      status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' },
    });
  }
}

async function maybeCachePage(request, response) {
  try {
    if (!response || !response.ok || response.redirected || response.type !== 'basic') return;
    if (hasNoStore(response)) return;
    if ((response.headers.get('Content-Type') || '').indexOf('text/html') === -1) return;
    if (await loadAuthState()) return; // never cache HTML for authenticated users
    const html = await response.clone().text();
    // Server-side opt-in marker (guests on public pages only).
    if (html.indexOf('name="kfs-cache" content="public"') === -1) return;
    // Defence in depth: an authenticated page must never be cached.
    if (html.indexOf('name="kfs-auth" content="1"') !== -1) {
      await saveAuthState(true);
      await clearPages();
      return;
    }
    const cache = await caches.open(PAGE_CACHE);
    await cache.put(request, response);
    await trimCache(PAGE_CACHE, PAGE_MAX_ENTRIES);
  } catch (e) { /* ignore */ }
}

/* ------------------------------------------------------------ push */
self.addEventListener('push', function (event) {
  let data = {};
  if (event.data) {
    try { data = event.data.json(); } catch (e) { data = { body: event.data.text() }; }
  }
  const title = data.title || 'بوابة محافظة كفر الشيخ';
  const options = {
    body: data.body || '',
    icon: data.icon || BASE + 'images/pwa/icon-192.png',
    badge: BASE + 'images/pwa/badge-96.png',
    dir: 'rtl',
    lang: 'ar',
    tag: data.tag || undefined,
    data: { url: data.url || data.action_url || BASE },
  };
  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  let target;
  try {
    target = new URL((event.notification.data && event.notification.data.url) || BASE, self.location.origin);
  } catch (e) {
    target = new URL(BASE, self.location.origin);
  }
  // Only open same-origin URLs from notifications.
  if (target.origin !== self.location.origin) target = new URL(BASE, self.location.origin);
  event.waitUntil((async function () {
    const all = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    for (const client of all) {
      if (client.url === target.href && 'focus' in client) return client.focus();
    }
    if (self.clients.openWindow) return self.clients.openWindow(target.href);
  })());
});

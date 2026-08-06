/**
 * Nymagro — minimal service worker.
 *
 * Amaç: tarayıcının "Ana ekrana ekle / Uygulamayı yükle" istemini
 * tetikleyebilmesi (Chrome/Android bunun için kayıtlı bir service worker
 * ister). Bilerek agresif önbellekleme YAPMIYOR — sayfa HTML'i her zaman
 * ağdan taze çekilir, aksi hâlde deploy sonrası "site değişmedi" sorunu
 * burada da tekrar eder. Yalnızca resim/ikon gibi nadiren değişen statik
 * dosyalar, sürüm etiketli küçük bir önbellekte tutulur.
 */
const CACHE_VERSION = 'nymagro-static-v1';
const CACHEABLE_EXT = /\.(png|jpg|jpeg|webp|svg|gif|ico)$/i;

self.addEventListener('install', (event) => {
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((names) =>
      Promise.all(
        names
          .filter((name) => name.startsWith('nymagro-') && name !== CACHE_VERSION)
          .map((name) => caches.delete(name))
      )
    ).then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const req = event.request;

  // Sayfa (HTML/navigasyon) istekleri ve GET olmayanlar: her zaman ağdan.
  if (req.mode === 'navigate' || req.method !== 'GET') {
    return;
  }

  const url = new URL(req.url);
  if (url.origin !== self.location.origin || !CACHEABLE_EXT.test(url.pathname)) {
    return;
  }

  event.respondWith(
    caches.open(CACHE_VERSION).then((cache) =>
      cache.match(req).then((cached) => {
        const network = fetch(req)
          .then((res) => {
            if (res && res.ok) cache.put(req, res.clone());
            return res;
          })
          .catch(() => cached);
        return cached || network;
      })
    )
  );
});

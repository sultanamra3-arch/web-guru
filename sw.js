/* SiGuru: simpan berkas tampilan di HP; halaman (data) selalu diambil dari server. */
const CACHE = 'siguru-v1.43';
const V = CACHE.replace('siguru-v', '');
const ASET = [
  'aset/gaya.css?v=' + V, 'aset/guru.css?v=' + V, 'aset/guru.js?v=' + V,
  'aset/huruf/quicksand-500.woff2', 'aset/huruf/quicksand-600.woff2', 'aset/huruf/quicksand-700.woff2',
  'aset/logo.png', 'aset/favicon.png?v=3', 'offline.html'
];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(ASET)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', e => {
  e.waitUntil((async () => {
    const k = await caches.keys();
    await Promise.all(k.filter(n => n !== CACHE).map(n => caches.delete(n)));
    // Muat halaman sambil service worker "bangun" (tidak menunggu bergantian)
    if (self.registration.navigationPreload) await self.registration.navigationPreload.enable();
    await self.clients.claim();
  })());
});

self.addEventListener('fetch', e => {
  const r = e.request;
  if (r.method !== 'GET') return;
  const url = new URL(r.url);
  if (url.origin !== location.origin) return;

  // Berkas tampilan (CSS, JS, huruf, gambar aplikasi): ambil dari HP dulu, simpan bila belum ada
  if (url.pathname.includes('/aset/')) {
    e.respondWith(caches.open(CACHE).then(c => c.match(r).then(ada => ada || fetch(r).then(res => {
      if (res.ok && res.type === 'basic') c.put(r, res.clone());
      return res;
    }).catch(() => c.match(r, { ignoreSearch: true }).then(lama => lama || Response.error())))));
    return;
  }

  // Halaman: selalu dari server (data terbaru), memakai hasil navigation preload bila ada
  if (r.mode === 'navigate') {
    e.respondWith((async () => {
      try {
        const awal = await e.preloadResponse;
        if (awal) return awal;
        return await fetch(r);
      } catch (x) {
        return (await caches.match('offline.html')) || Response.error();
      }
    })());
  }
});

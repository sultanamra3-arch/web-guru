/* SiGuru: simpan berkas tampilan di HP; halaman diambil dari server (data terbaru).
   v1.68: halaman guru yang pernah dibuka disimpan di HP → tetap bisa dilihat tanpa sinyal;
          layar pembuka (logo → ikon Riau) saat aplikasi baru dibuka. */
const CACHE = 'siguru-v1.76';
const HALAMAN = 'siguru-halaman';
const V = CACHE.replace('siguru-v', '');
const ASET = [
  'aset/gaya.css?v=' + V, 'aset/guru.css?v=' + V, 'aset/guru.js?v=' + V, 'aset/gerak.js?v=' + V, 'aset/tuah.js?v=' + V, 'aset/dialog.js?v=' + V,
  'aset/huruf/quicksand-500.woff2', 'aset/huruf/quicksand-600.woff2', 'aset/huruf/quicksand-700.woff2',
  'aset/logo.png', 'aset/favicon.png?v=3', 'offline.html'
];
// halaman siswa & tamu: tidak disimpan, tanpa layar pembuka
const HAL_SISWA = ['u', 'u_kerja', 'gambar', 't', 'tugas_foto', 'k', 'resi'];
// halaman yang tidak disimpan di HP
const JANGAN_SIMPAN = /^(api_|keluar$|masuk$|beralih$|apk$|ke_pustaka$|ke_slims$|sso$|gambar$|tugas_foto$|presentasi$|.*_cetak$)/;
const MAKS_HALAMAN = 80;
// v1.75: gambar TUAH (maskot) untuk layar pembuka & layar tanpa sinyal
try { importScripts('aset/tuah.js?v=' + V); } catch (x) { /* tanpa TUAH */ }
const TUAH = n => (self.sgTuah ? self.sgTuah(n) : '');
if (self.sgTuahBerkas) ASET.push(...self.sgTuahBerkas); // gambar TUAH ikut disimpan untuk luring

self.addEventListener('install', e => {
  // v1.71: satu berkas gagal diambil (mis. diblokir hosting) tidak boleh menggagalkan seluruh pemasangan
  e.waitUntil(caches.open(CACHE)
    .then(c => Promise.all(ASET.map(u => c.add(new Request(u, { cache: 'reload' })).catch(() => null))))
    .then(() => self.skipWaiting()));
});

self.addEventListener('activate', e => {
  e.waitUntil((async () => {
    const k = await caches.keys();
    await Promise.all(k.filter(n => n !== CACHE && n !== HALAMAN).map(n => caches.delete(n)));
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

  if (r.mode !== 'navigate') return;
  const p = url.searchParams.get('p') || (/\/(u|t|k|r)\/?$/.test(url.pathname) ? 'siswa' : 'beranda');
  const siswa = p === 'siswa' || HAL_SISWA.includes(p);
  e.respondWith((async () => {
   try {
    // keluar / masuk / beralih akun: hapus halaman yang tersimpan (milik akun sebelumnya)
    if (p === 'keluar' || p === 'masuk' || p === 'beralih') await caches.delete(HALAMAN);
    const jalan = ambilHalaman(e, url, p, siswa);
    if (!siswa && await bukaPertama(e)) {
      // jawaban layar pembuka dikirim lebih dulu: tetap tunggu halaman asli (jangan sampai pemuatan awal dibatalkan)
      e.waitUntil(jalan.catch(() => {}));
      return denganPembuka(jalan);
    }
    return await jalan;
   } catch (x) {
    return halamanOffline();
   }
  })());
});

/** Layar "Tanpa sinyal" milik SiGuru; tidak bergantung pada berkas tersimpan (v1.72). */
async function halamanOffline() {
  const ada = await caches.match('offline.html').catch(() => null);
  if (ada) return ada;
  return new Response('<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">' +
    '<title>SiGuru · Tanpa sinyal</title><link rel="stylesheet" href="aset/guru.css?v=' + V + '"><style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#F6F8FC;' +
    'font-family:Quicksand,system-ui,sans-serif;color:#1A1A2E;overscroll-behavior:none}.k{background:#fff;border:1px solid #E3E8F2;border-radius:16px;' +
    'padding:24px 20px;margin:16px;max-width:360px;text-align:center}h1{color:#2A2C84;font-size:1.3rem}.tuah{width:150px;margin:0 auto}p{color:#5B6382;line-height:1.5}' +
    'button{font:inherit;font-weight:700;background:#2A2C84;color:#fff;border:0;border-radius:12px;padding:12px 24px;margin:4px}' +
    'button.b{background:#fff;color:#2A2C84;border:1px solid #E3E8F2}</style></head><body><div class="k"><div class="tuah">' + TUAH('payung') + '</div><h1>Sinyal sedang hujan</h1>' +
    '<p>Halaman ini belum tersimpan di HP. Halaman yang pernah dibuka saat ada sinyal tetap bisa dilihat.</p>' +
    '<button onclick="location.reload()">Coba lagi</button><button class="b" onclick="history.back()">‹ Kembali</button></div>' +
    '<script>addEventListener("online",function(){location.reload()})</script></body></html>',
    { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
}

/** Aplikasi baru dibuka (belum ada jendela SiGuru lain) → tampilkan layar pembuka. */
async function bukaPertama(e) {
  try {
    const semua = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    return !semua.some(c => c.id !== e.resultingClientId);
  } catch (x) { return false; }
}

/** Halaman: dari server dulu (simpan salinannya); tanpa sinyal → salinan terakhir di HP. */
async function ambilHalaman(e, url, p, siswa) {
  const simpanan = siswa ? null : await cariSimpanan(url, p);
  try {
    const tunggu = Promise.resolve(e.preloadResponse).then(awal => awal || fetch(e.request));
    // ada salinan: jangan menunggu server terlalu lama (sinyal sangat lemah)
    const res = simpanan ? await balapan(tunggu, 9000) : await tunggu;
    if (!siswa && res.ok && res.type === 'basic' && !res.redirected && !JANGAN_SIMPAN.test(p) &&
        !/[?&](unduh|word|cetak)=/.test(url.search) && (res.headers.get('content-type') || '').includes('text/html')) {
      const salin = res.clone();
      salin.text().then(t => simpanHalaman(url.href, t)).catch(() => {});
    }
    return res;
  } catch (x) {
    if (simpanan) return simpanan;
    return halamanOffline();
  }
}

function balapan(janji, ms) {
  return new Promise((ok, gagal) => {
    const t = setTimeout(() => gagal(new Error('lambat')), ms);
    janji.then(r => { clearTimeout(t); ok(r); }, x => { clearTimeout(t); gagal(x); });
  });
}

async function simpanHalaman(alamat, teks) {
  const c = await caches.open(HALAMAN);
  await c.put(alamat, new Response(teks, { headers: { 'Content-Type': 'text/html; charset=utf-8', 'X-SG-Waktu': String(Date.now()) } }));
  const kunci = await c.keys();
  if (kunci.length > MAKS_HALAMAN) await Promise.all(kunci.slice(0, kunci.length - MAKS_HALAMAN).map(k => c.delete(k)));
}

/** Salinan halaman ini; untuk absen boleh salinan kelas yang sama dari tanggal lain (tanggal disesuaikan di HP). */
async function cariSimpanan(url, p) {
  const c = await caches.open(HALAMAN);
  let res = await c.match(url.href), ganti = false;
  if (!res && p === 'absensi') {
    const kelas = url.searchParams.get('kelas');
    for (const k of await c.keys()) {
      const u = new URL(k.url);
      if (u.searchParams.get('p') === 'absensi' && kelas && u.searchParams.get('kelas') === kelas) { res = await c.match(k); ganti = true; break; }
    }
  }
  if (!res) return null;
  const waktu = res.headers.get('X-SG-Waktu') || '0';
  // pesan sekali tampil (mis. "tersimpan") dan pita Batalkan jangan ikut muncul lagi dari salinan
  const teks = (await res.text()).replace(/<div class="pesan [^"]*">[\s\S]*?<\/div>/g, '').replace(/<form[^>]*class="pita-batal[\s\S]*?<\/form>/g, '').replace(/<head>/i, '<head><meta name="sg-tersimpan" content="' + waktu + '">' + (ganti ? '<meta name="sg-pengganti" content="1">' : ''));
  return new Response(teks, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
}

/* ---------- layar pembuka: dikirim lebih dulu, lalu halaman asli menyusul di dokumen yang sama ---------- */
function jalur(d, emas) { return '<path pathLength="1" class="' + (emas ? 'e' : 'n') + '" d="' + d + '"/>'; }
const RIAU = [
  ['Selembayung', jalur('M50 40 C45 24 34 16 24 19 M50 40 C55 24 66 16 76 19', 1) + jalur('M14 68 L50 40 L86 68 M24 68 V88 H76 V68 M44 88 V74 H56 V88')],
  ['Lancang Kuning', jalur('M50 64 V18 L74 54 H50') + jalur('M8 58 Q14 64 20 64 H80 Q86 64 92 58 Q84 82 50 82 Q16 82 8 58 Z', 1) + jalur('M10 92 Q20 88 30 92 T50 92 T70 92 T90 92')],
  ['Tanjak', jalur('M20 74 Q50 86 80 74 L72 46 L59 54 L50 18 L41 54 L28 46 Z') + jalur('M26 66 Q50 76 74 66', 1)],
  ['Tepak Sirih', jalur('M22 52 L32 38 H68 L78 52 H82 V84 H18 V52 Z') + jalur('M30 68 H70 M42 30 Q50 22 58 30', 1)],
  ['Pucuk Rebung', jalur('M10 84 L28 30 L46 84 M54 84 L72 30 L90 84') + jalur('M28 46 L36 70 H20 Z M72 46 L80 70 H64 Z M8 88 H92', 1)]
];
const PEMBUKA = '<!DOCTYPE html><html><head><meta charset="utf-8">' +
  '<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><meta name="theme-color" content="#2A2C84">' +
  '<style>' +
  '#sg-pembuka{position:fixed;inset:0;z-index:2147483000;background:#2A2C84;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:20px;' +
  'font-family:Quicksand,system-ui,sans-serif;transition:opacity .45s,visibility .45s;overscroll-behavior:none}' +
  '#sg-pembuka.hilang{opacity:0;visibility:hidden}' +
  '#sg-pembuka{padding-bottom:min(140px,22vh);box-sizing:border-box}' +
  '#sg-pembuka .lk{position:relative;width:150px;height:150px}' +
  '#sg-pembuka .lk>*{position:absolute}' +
  '#sg-pembuka .cc{inset:0;width:100%;height:100%;animation:sgp-putar 2.4s linear infinite}' +
  '#sg-pembuka .cc circle{fill:none;stroke-width:3}' +
  '#sg-pembuka .cc .j{stroke:#3A3D9A}#sg-pembuka .cc .k{stroke:#F2C230;stroke-linecap:round;animation:sgp-ekor 2.4s cubic-bezier(.65,0,.35,1) infinite}' +
  '#sg-pembuka .logo{left:33px;top:33px;width:84px;height:84px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;opacity:0;animation:sgp-logo 7.2s cubic-bezier(.65,0,.35,1) infinite}' +
  '#sg-pembuka .logo img{width:62px;height:62px;object-fit:contain}' +
  '#sg-pembuka .ik{left:25px;top:25px;width:100px;height:100px}' +
  '#sg-pembuka .ik path{fill:none;stroke-width:3.5;stroke-linecap:round;stroke-linejoin:round;stroke-dasharray:1 2;stroke-dashoffset:1.01;' +
  'animation:sgp-lukis 7.2s cubic-bezier(.65,0,.35,1) infinite;animation-delay:inherit}' +
  '#sg-pembuka .n{stroke:#fff}#sg-pembuka .e{stroke:#F2C230}' +
  '#sg-pembuka .nm{position:relative;height:20px;width:100%;text-align:center;color:#F2C230;font-size:13px;font-weight:700}' +
  '#sg-pembuka .nm span{position:absolute;left:0;right:0;opacity:0;animation:sgp-nama 7.2s cubic-bezier(.65,0,.35,1) infinite}' +
  '#sg-pembuka .mrk{position:absolute;bottom:calc(36px + env(safe-area-inset-bottom));left:0;right:0;text-align:center;color:#fff;font-size:21px;font-weight:700;letter-spacing:1px}' +
  '#sg-pembuka .mrk i{display:flex;width:70px;height:3px;margin:8px auto 0;background:linear-gradient(90deg,#F2C230 0 70%,#2E9E48 70%);transform-origin:left;animation:sgp-garis 1.2s cubic-bezier(.65,0,.35,1)}' +
  '#sg-pembuka .tuah-pembuka{position:absolute;bottom:calc(92px + env(safe-area-inset-bottom));left:50%;width:min(150px,30vh);transform:translateX(-50%);opacity:0;animation:sgp-muncul .6s 1.1s forwards}' +
  '#sg-pembuka .tuah-pembuka span,#sg-pembuka .tuah-pembuka img{display:block;width:100%;height:auto}' +
  '@keyframes sgp-muncul{from{opacity:0;transform:translate(-50%,16px)}to{opacity:1;transform:translateX(-50%)}}' +
  '@keyframes sgp-putar{to{transform:rotate(360deg)}}' +
  '@keyframes sgp-ekor{0%,100%{stroke-dasharray:.05 1}50%{stroke-dasharray:.45 1}}' +
  '@keyframes sgp-lukis{0%{stroke-dashoffset:1.01}8%{stroke-dashoffset:0}13.5%{stroke-dashoffset:0}16.6%{stroke-dashoffset:-1.01}100%{stroke-dashoffset:-1.01}}' +
  '@keyframes sgp-logo{0%{opacity:0;transform:scale(.85)}4%,13%{opacity:1;transform:none}16.6%,100%{opacity:0;transform:scale(.85)}}' +
  '@keyframes sgp-nama{0%{opacity:0;letter-spacing:6px;transform:translateY(6px)}3%{opacity:0}8%,13.5%{opacity:1;letter-spacing:.5px;transform:none}16.6%,100%{opacity:0;transform:translateY(-6px)}}' +
  '@keyframes sgp-garis{from{transform:scaleX(0)}to{transform:none}}' +
  '@media (prefers-reduced-motion:reduce){#sg-pembuka *{animation:none!important}#sg-pembuka .logo{opacity:1}}' +
  '</style></head><body><div id="sg-pembuka" role="status" aria-label="Membuka SiGuru">' +
  '<div class="lk"><svg class="cc" viewBox="0 0 150 150" aria-hidden="true"><circle class="j" cx="75" cy="75" r="70"/><circle class="k" cx="75" cy="75" r="70" pathLength="1"/></svg>' +
  '<div class="logo"><img src="aset/logo.png" alt=""></div>' +
  RIAU.map((x, i) => '<svg class="ik" viewBox="0 0 100 100" aria-hidden="true" style="animation-delay:' + ((i + 1) * 1.2) + 's">' + x[1] + '</svg>').join('') +
  '</div><div class="nm"><span>SMPN 6 Keritang</span>' +
  RIAU.map((x, i) => '<span style="animation-delay:' + ((i + 1) * 1.2) + 's">' + x[0] + '</span>').join('') +
  '</div><div class="tuah-pembuka">' + TUAH('pembuka') + '</div><div class="mrk">SiGuru<i></i></div></div>' +
  // tutup pembuka setelah halaman siap (paling cepat 2,4 detik: logo lalu ikon Riau pertama sempat terlihat)
  '<script>(function(){var p=document.getElementById("sg-pembuka"),t0=Date.now();function tutup(){setTimeout(function(){p.classList.add("hilang");' +
  'setTimeout(function(){p.remove()},500)},Math.max(0,2400-(Date.now()-t0)))}' +
  'window.addEventListener("load",tutup);setTimeout(function(){if(document.body.contains(p))tutup()},15000)})();</script>';

function denganPembuka(jalan) {
  const enc = new TextEncoder();
  const aliran = new ReadableStream({
    async start(c) {
      c.enqueue(enc.encode(PEMBUKA));
      let res;
      try { res = await jalan; } catch (x) { res = null; }
      if (!res || res.type === 'opaqueredirect' || res.redirected || !(res.headers.get('content-type') || '').includes('text/html') || !res.body) {
        // halaman ini dialihkan (mis. ke halaman masuk) atau bukan HTML: muat ulang biasa
        c.enqueue(enc.encode('<script>location.replace(location.href)</script>'));
        c.close();
        return;
      }
      const baca = res.body.getReader();
      try {
        for (;;) {
          const { done, value } = await baca.read();
          if (done) break;
          c.enqueue(value);
        }
      } catch (x) { /* sambungan putus di tengah */ }
      c.close();
    }
  });
  return new Response(aliran, { headers: { 'Content-Type': 'text/html; charset=utf-8' } });
}

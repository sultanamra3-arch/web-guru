/* SiGuru v1.68 · animasi (loading, garis khas sekolah, menu bola emas, pindah aplikasi)
   dan mode tanpa sinyal (status Online/Offline, pita oranye, absen antre dikirim otomatis). */
(function () {
  'use strict';
  var D = document, H = D.documentElement;
  var REDUKSI = !!(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches);
  function $(s, r) { return (r || D).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || D).querySelectorAll(s)); }
  function sesiAmbil(k) { try { return JSON.parse(sessionStorage.getItem(k) || 'null'); } catch (e) { return null; } }
  function sesiSimpan(k, v) { try { if (v == null) sessionStorage.removeItem(k); else sessionStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* abaikan */ } }
  function jam(ts) { var d = new Date(+ts); return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2); }
  function tombolBiasa(e) { return !(e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey); }

  /* ---------- ikon garis (dilukis dengan stroke) ---------- */
  function jalur(d, emas) { return '<path pathLength="1" class="' + (emas ? 'e' : 'n') + '" d="' + d + '"/>'; }
  var RIAU = [ // Selembayung, Lancang Kuning, Tanjak, Tepak Sirih, Pucuk Rebung
    jalur('M50 40 C45 24 34 16 24 19 M50 40 C55 24 66 16 76 19', 1) + jalur('M14 68 L50 40 L86 68 M24 68 V88 H76 V68 M44 88 V74 H56 V88'),
    jalur('M50 64 V18 L74 54 H50') + jalur('M8 58 Q14 64 20 64 H80 Q86 64 92 58 Q84 82 50 82 Q16 82 8 58 Z', 1) + jalur('M10 92 Q20 88 30 92 T50 92 T70 92 T90 92'),
    jalur('M20 74 Q50 86 80 74 L72 46 L59 54 L50 18 L41 54 L28 46 Z') + jalur('M26 66 Q50 76 74 66', 1),
    jalur('M22 52 L32 38 H68 L78 52 H82 V84 H18 V52 Z') + jalur('M30 68 H70 M42 30 Q50 22 58 30', 1),
    jalur('M10 84 L28 30 L46 84 M54 84 L72 30 L90 84') + jalur('M28 46 L36 70 H20 Z M72 46 L80 70 H64 Z M8 88 H92', 1)
  ];
  var APLIKASI = {
    siguru: jalur('M10 40 L50 22 L90 40 L50 58 Z') + jalur('M26 48 V68 Q50 82 74 68 V48') + jalur('M90 40 V62 M86 66 H94', 1),
    sipustaka: jalur('M50 30 Q34 20 14 24 V78 Q34 74 50 84 Q66 74 86 78 V24 Q66 20 50 30 Z M50 30 V84') + jalur('M24 40 Q34 38 42 42 M24 52 Q34 50 42 54 M58 42 Q66 38 76 40 M58 54 Q66 50 76 52', 1),
    slims: jalur('M14 86 H86 M18 86 V20 H82 V86 M18 52 H82') + jalur('M28 50 V30 M38 50 V26 M48 50 V32 M60 84 V60 M70 84 V56 M30 84 L36 62', 1),
    kartu: jalur('M12 26 H88 V76 H12 Z') + jalur('M22 38 H44 V60 H22 Z M54 42 H78 M54 52 H74 M54 62 H68', 1),
    bukutamu: jalur('M22 14 H70 V86 H22 Z M30 14 V86') + jalur('M40 34 H60 M40 46 H60 M58 80 L84 54 L90 60 L64 86 L56 88 Z', 1),
    katalog: jalur('M42 16 A26 26 0 1 0 42.1 16 Z M61 61 L86 86') + jalur('M30 34 H54 M30 44 H50 M30 54 H46', 1)
  };
  function cincin(kelas) {
    return '<svg class="' + kelas + '" viewBox="0 0 100 100" aria-hidden="true"><circle class="jalur" cx="50" cy="50" r="46"/>' +
      '<circle class="ekor" cx="50" cy="50" r="46" pathLength="1"/></svg>';
  }
  function ikonRiau() {
    return '<svg class="sg-riau" viewBox="0 0 100 100" aria-hidden="true">' +
      RIAU.map(function (p, i) { return '<g style="--d:' + (i * 1.2) + 's">' + p + '</g>'; }).join('') + '</svg>';
  }

  /* ---------- 0. Tanpa sinyal: jangan tinggalkan halaman bila tujuan belum tersimpan di HP (v1.72) ----------
     Mencegah layar "Tidak tersambung" milik APK. Tautan ke halaman yang sudah tersimpan tetap dibuka. */
  D.addEventListener('click', function (e) {
    if (navigator.onLine !== false || !tombolBiasa(e)) return;
    var a = e.target.closest && e.target.closest('a[href]');
    if (!a || a.target || a.hasAttribute('download')) return;
    var h = a.getAttribute('href') || '';
    if (h.charAt(0) === '#' || /^(javascript|mailto|tel|whatsapp|intent|blob|data):/i.test(h)) return;
    var u;
    try { u = new URL(a.href, location.href); } catch (x) { return; }
    e.preventDefault();
    e.stopImmediatePropagation();
    var pesan = function () {
      (window.sgPesan || alert)(u.origin !== location.origin
        ? 'Tanpa sinyal.\nAplikasi ini perlu internet. Coba lagi setelah tersambung.'
        : 'Tanpa sinyal.\nHalaman ini belum tersimpan di HP. Buka sekali saat ada sinyal agar bisa dilihat tanpa sinyal.');
    };
    if (u.origin !== location.origin || !window.caches || !navigator.serviceWorker || !navigator.serviceWorker.controller) { pesan(); return; }
    var q = u.searchParams, cari = caches.open('siguru-halaman').then(function (c) {
      return c.match(u.href).then(function (r) {
        if (r || q.get('p') !== 'absensi') return r;
        // absen: salinan kelas yang sama dari tanggal lain juga boleh (tanggal disesuaikan di HP)
        return c.keys().then(function (k) {
          return k.some(function (x) { var v = new URL(x.url).searchParams; return v.get('p') === 'absensi' && q.get('kelas') && v.get('kelas') === q.get('kelas'); });
        });
      });
    });
    cari.then(function (ada) { if (ada) location.href = u.href; else pesan(); }, pesan);
  }, true);

  /* ---------- 1. Loading: garis khas sekolah hidup + lingkaran kecil bila > 0,3 detik ---------- */
  var LABEL = { beranda: 'beranda', kelas: 'kelas', kelas_lihat: 'kelas', absensi: 'absen', absensi_rekap: 'rekap absen',
    ujian: 'ujian', ujian_lihat: 'ujian', ujian_hasil: 'hasil ujian', ujian_form: 'ujian', tugas: 'tugas', tugas_lihat: 'tugas',
    tugas_form: 'tugas', nilai: 'nilai', nilai_rekap: 'nilai', penilaian: 'nilai', perangkat: 'perangkat', soal: 'soal', paket: 'paket soal',
    lainnya: 'menu', profil: 'profil', jadwal: 'jadwal', siswa: 'siswa', kuis: 'kuis', erapor: 'e-Rapor', cari: 'pencarian',
    u: 'ujian', u_kerja: 'soal ujian', t: 'tugas', k: 'kuis', resi: 'resi' };
  var Muat = (function () {
    var tunda, batas, kotak = null, jalan = false;
    function buat() {
      kotak = D.createElement('div');
      kotak.className = 'sg-muat tak-cetak';
      kotak.setAttribute('role', 'status');
      kotak.innerHTML = '<div class="sg-muat-isi"><div class="sg-lingkar">' + cincin('sg-cincin') + ikonRiau() + '</div>' +
        '<div class="sg-muat-teks"><span></span><i></i><i></i><i></i></div></div>';
      D.body.appendChild(kotak);
    }
    function mulai(teks) {
      if (jalan) return;
      jalan = true;
      H.classList.add('sg-memuat');
      clearTimeout(tunda); clearTimeout(batas);
      tunda = setTimeout(function () {
        if (!kotak) buat();
        kotak.querySelector('.sg-muat-teks span').textContent = teks || 'Memuat';
        kotak.classList.add('tampil');
      }, 300);
      batas = setTimeout(henti, 20000);
    }
    function henti() {
      jalan = false;
      clearTimeout(tunda); clearTimeout(batas);
      H.classList.remove('sg-memuat');
      if (kotak) kotak.classList.remove('tampil');
    }
    return { mulai: mulai, henti: henti };
  })();
  window.sgMuat = Muat;

  function labelDari(url) {
    var p = '';
    try { p = new URL(url, location.href).searchParams.get('p') || 'beranda'; } catch (e) { /* abaikan */ }
    return 'Memuat ' + (LABEL[p] || 'halaman');
  }
  D.addEventListener('click', function (e) {
    if (!tombolBiasa(e)) return;
    var a = e.target.closest && e.target.closest('a[href]');
    if (!a || a.target || a.hasAttribute('download') || a.hasAttribute('data-tanpa-muat') || a.hasAttribute('data-ke-aplikasi')) return;
    var h = a.getAttribute('href') || '';
    if (h.charAt(0) === '#' || /^(javascript|mailto|tel|whatsapp|intent|blob|data):/i.test(h)) return;
    try { var u = new URL(a.href, location.href); if (u.origin !== location.origin) return; if (/[?&](unduh|word|cetak)=/.test(u.search)) return; if (u.pathname === location.pathname && u.search === location.search && u.hash) return; } catch (x) { return; }
    setTimeout(function () { if (!e.defaultPrevented) Muat.mulai(labelDari(a.href)); }, 0);
  });
  D.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.target || f.hasAttribute('data-ajax') || /(unduh|cetak)/.test(f.getAttribute('action') || '')) return;
    setTimeout(function () { if (!e.defaultPrevented) Muat.mulai((f.method || '').toLowerCase() === 'post' ? (D.body.classList.contains('polos') ? 'Membuka' : 'Menyimpan') : labelDari(f.action || location.href)); }, 0);
  });
  window.addEventListener('pageshow', function (e) {
    Muat.henti();
    if (e.persisted) { $$('.sg-pindah').forEach(function (x) { x.remove(); }); sedangPindah = false; }
  });
  window.addEventListener('pagehide', function () { setTimeout(Muat.henti, 0); });

  /* ---------- 2. Menu bawah: bola emas ---------- */
  (function () {
    var nav = $('nav.menu');
    if (!nav) return;
    var POS = [10, 30, 50, 70, 90];
    var kini = nav.hasAttribute('data-aktif') ? +nav.getAttribute('data-aktif') : -1;
    var jejak = sesiAmbil('sg-bola');
    sesiSimpan('sg-bola', null);
    function letak(i, langsung) {
      if (langsung) nav.classList.add('tanpa-gerak');
      nav.style.setProperty('--bx', POS[i] + '%');
      nav.classList.add('ada-bola');
      if (langsung) { void nav.offsetWidth; nav.classList.remove('tanpa-gerak'); }
    }
    if (jejak && kini >= 0 && jejak.ke === kini && jejak.dari !== kini && Date.now() - jejak.t < 4000) {
      // isi halaman masuk dari arah tab yang ditekan
      var m = $('main');
      if (m && !REDUKSI) {
        m.classList.add(jejak.dari < 0 || jejak.ke > jejak.dari ? 'sg-dari-kanan' : 'sg-dari-kiri');
        setTimeout(function () { m.classList.remove('sg-dari-kanan', 'sg-dari-kiri'); }, 600);
      }
      // halaman tampil sangat cepat (sudah disiapkan): bola belum sempat bergerak di halaman lama → gerakkan di sini
      if (jejak.dari >= 0 && Date.now() - jejak.t < 260 && !REDUKSI) {
        letak(jejak.dari, true);
        requestAnimationFrame(function () { letak(kini); });
      }
    }
    $$('a[data-i]', nav).forEach(function (a) {
      a.addEventListener('click', function (e) {
        if (!tombolBiasa(e)) return;
        var i = +a.getAttribute('data-i');
        if (i === kini) return;
        sesiSimpan('sg-bola', { dari: kini, ke: i, t: Date.now() });
        letak(i, kini < 0);
        $$('a[data-i]', nav).forEach(function (x) { x.classList.toggle('aktif', x === a); });
      });
    });
  })();

  /* ---------- 3. Pindah aplikasi (SiPustaka, SLiMS, Kartu, Buku tamu, Katalog) ----------
     v1.69: aplikasi tujuan baru dibuka saat layar navy mulai menutup (2,2 detik),
     agar animasi tidak terpotong / terasa terburu-buru. */
  var sedangPindah = false;
  D.addEventListener('click', function (e) {
    if (!tombolBiasa(e)) return;
    var a = e.target.closest && e.target.closest('a[data-ke-aplikasi]');
    if (!a || REDUKSI) return;
    e.preventDefault();
    if (sedangPindah) return;
    sedangPindah = true;
    var tujuan = a.href;
    setTimeout(function () { location.href = tujuan; }, 2200);
    setTimeout(function () { sedangPindah = false; }, 5000);
    setTimeout(function () {
      var tuju = APLIKASI[a.getAttribute('data-ke-aplikasi')] || APLIKASI.sipustaka, nama = a.getAttribute('data-nama') || 'aplikasi';
      var o = D.createElement('div');
      o.className = 'sg-pindah tak-cetak';
      o.setAttribute('role', 'status');
      o.innerHTML = '<div class="sg-pindah-lingkar">' + cincin('sg-cincin') +
        '<svg class="asal" viewBox="0 0 100 100" aria-hidden="true">' + APLIKASI.siguru + '</svg>' +
        '<svg class="tuju" viewBox="0 0 100 100" aria-hidden="true">' + tuju + '</svg></div>' +
        '<div class="sg-pindah-nama"><span class="a">SiGuru</span><span class="b"></span></div>' +
        '<div class="sg-pindah-tirai"></div><div class="sg-pindah-akhir"><b></b><small>sedang dibuka<i></i><i></i><i></i></small></div>';
      o.querySelector('.b').textContent = 'Membuka ' + nama;
      o.querySelector('.sg-pindah-akhir b').textContent = nama;
      D.body.appendChild(o);
    }, 0);
  });

  /* ---------- 4. Status Online / Offline ---------- */
  var tersimpan = $('meta[name="sg-tersimpan"]'), pengganti = !!$('meta[name="sg-pengganti"]');
  var waktuData = tersimpan ? +tersimpan.getAttribute('content') : 0;
  var akun = ($('meta[name="sg-akun"]') || { getAttribute: function () { return ''; } }).getAttribute('content') || '';
  var atas = $('header.atas');
  var pita = null, toastTimer;

  function toast(teks) {
    var t = $('.sg-toast');
    if (!t) { t = D.createElement('div'); t.className = 'sg-toast tak-cetak'; t.setAttribute('role', 'status'); D.body.appendChild(t); }
    t.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path pathLength="1" d="M5 12.5 L10 17.5 L19 7.5"/></svg><span></span>';
    t.querySelector('span').textContent = teks;
    t.classList.remove('tampil'); void t.offsetWidth; t.classList.add('tampil');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { t.classList.remove('tampil'); }, 3200);
  }
  function aturPita(teks, tombol) {
    if (!atas) return;
    if (!pita) {
      pita = D.createElement('div');
      pita.className = 'sg-pita tak-cetak';
      pita.setAttribute('role', 'status');
      atas.parentNode.insertBefore(pita, atas.nextSibling);
    }
    if (!teks) { pita.classList.remove('tampil'); return; }
    pita.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 8.8a15 15 0 0 1 20 0M5 12.5a10 10 0 0 1 14 0M8.5 16a5 5 0 0 1 7 0M12 20h.01M3 3l18 18"/></svg><span></span>';
    pita.querySelector('span').innerHTML = teks;
    if (tombol) {
      var b = D.createElement('button'); b.type = 'button'; b.className = 'kecil'; b.textContent = tombol;
      b.onclick = function () { location.reload(); };
      pita.appendChild(b);
      pita.classList.add('segar');
    } else pita.classList.remove('segar');
    void pita.offsetWidth;
    pita.classList.add('tampil');
  }
  function aturStatus() {
    var on = navigator.onLine !== false;
    H.classList.toggle('sg-offline', !on);
    $$('.status-jaringan').forEach(function (s) {
      s.classList.toggle('off', !on);
      var b = s.querySelector('b'); if (b) b.textContent = on ? 'Online' : 'Offline';
      s.title = on ? 'Tersambung ke server' : 'Tanpa sinyal';
    });
    if (!on) aturPita('<b>Tanpa sinyal</b>' + (waktuData ? ' · data terakhir ' + jam(waktuData) : '') + '. Absen tetap bisa diisi dan dikirim otomatis nanti.');
    else if (waktuData) aturPita('<b>Server belum terjangkau</b> · data terakhir ' + jam(waktuData) + '.', 'Muat ulang');
    else aturPita('');
  }
  window.addEventListener('offline', aturStatus);
  window.addEventListener('online', function () {
    aturStatus();
    kirimAntre().then(function (n) {
      toast(n ? 'Tersambung lagi · absen ' + n + ' siswa terkirim' : 'Tersambung lagi');
      if (waktuData) aturPita('<b>Tersambung lagi</b> · data di layar dari pukul ' + jam(waktuData) + '.', 'Perbarui');
    });
  });

  // Saat tanpa sinyal: formulir biasa jangan dikirim (akan gagal); absen punya jalannya sendiri
  D.addEventListener('submit', function (e) {
    var f = e.target;
    if (navigator.onLine !== false || (f.method || '').toLowerCase() !== 'post' || f.hasAttribute('data-antre') || f.hasAttribute('data-ajax')) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    (window.sgPesan || alert)('Tanpa sinyal.\nYang ini baru bisa disimpan setelah tersambung lagi. Absen tetap bisa diisi tanpa sinyal.');
  }, true);

  /* ---------- 5. Absen antre: tersimpan di HP, terkirim otomatis saat ada sinyal ---------- */
  var KA = 'sg-antre';
  function antre() { try { return JSON.parse(localStorage.getItem(KA) || '[]') || []; } catch (e) { return []; } }
  function simpanAntre(a) { try { if (a.length) localStorage.setItem(KA, JSON.stringify(a)); else localStorage.removeItem(KA); } catch (e) { /* abaikan */ } }
  function jumlahSiswa(x) { return x.isi.filter(function (p) { return /^status\[/.test(p[0]); }).length; }
  function ambilToken() {
    return fetch('?p=beranda', { credentials: 'same-origin', cache: 'no-store' }).then(function (r) {
      if (/[?&]p=masuk/.test(r.url)) return null;
      return r.text().then(function (t) { var m = t.match(/name="_t" value="([^"]+)"/); return m ? m[1] : null; });
    }).catch(function () { return null; });
  }
  // hasil: 'ok' | 'tolak' (server menolak isinya, misal mapel kosong) | 'gagal' (sinyal/sesi, coba lagi nanti)
  function kirimSatu(x, sudahCoba) {
    var body = new URLSearchParams();
    x.isi.forEach(function (p) { body.append(p[0], p[1]); });
    var ab = window.AbortController ? new AbortController() : null, w = ab ? setTimeout(function () { ab.abort(); }, 20000) : 0;
    return fetch(x.url, { method: 'POST', body: body, credentials: 'same-origin', redirect: 'follow', signal: ab ? ab.signal : undefined })
      .then(function (r) {
        clearTimeout(w);
        if (r.status === 400 && !sudahCoba) {
          return ambilToken().then(function (t) {
            if (!t) return 'gagal';
            x.isi = x.isi.map(function (p) { return p[0] === '_t' ? ['_t', t] : p; });
            return kirimSatu(x, true);
          });
        }
        if (!r.ok || /[?&]p=masuk/.test(r.url) || r.url.indexOf('p=absensi') < 0) return 'gagal';
        return r.text().then(function (t) {
          var m = t.match(/class="pesan galat[^"]*">([\s\S]*?)<\/div>/);
          if (m) { x.galat = m[1].replace(/<[^>]+>/g, '').trim(); return 'tolak'; }
          return 'ok';
        });
      })
      .catch(function () { clearTimeout(w); return 'gagal'; });
  }
  var sedangKirim = null;
  function kirimAntre() {
    if (sedangKirim) return sedangKirim;
    var daftar = antre().filter(function (x) { return x.akun === akun; });
    if (!daftar.length || navigator.onLine === false || !akun) return Promise.resolve(0);
    var terkirim = 0;
    sedangKirim = daftar.reduce(function (p, x) {
      return p.then(function () {
        return kirimSatu(x).then(function (h) {
          if (h === 'gagal') return;
          simpanAntre(antre().filter(function (y) { return y.id !== x.id || y.waktu !== x.waktu; }));
          if (h === 'ok') terkirim += jumlahSiswa(x);
          else (window.sgPesan || alert)('Absen ' + x.judul + ' tidak bisa disimpan server:\n' + (x.galat || 'periksa lagi isinya.'));
          tandaiAbsen();
        });
      });
    }, Promise.resolve()).then(function () { sedangKirim = null; return terkirim; }, function () { sedangKirim = null; return terkirim; });
    return sedangKirim;
  }

  var fAbsen = $('#form-absen');
  function nilai(f, n) { var i = f.querySelector('[name="' + n + '"]'); return i ? i.value : ''; }
  function idAbsen(f) { return 'absen|' + nilai(f, 'kelas') + '|' + nilai(f, 'tanggal') + '|' + nilai(f, 'mapel'); }
  function tandaiAbsen() {
    if (!fAbsen) return;
    var x = antre().filter(function (y) { return y.id === idAbsen(fAbsen) && y.akun === akun; })[0];
    var ada = {};
    if (x) x.isi.forEach(function (p) { var m = p[0].match(/^status\[(.+)\]$/); if (m) ada[m[1]] = 1; });
    $$('li', fAbsen).forEach(function (li) {
      var r = li.querySelector('input[type=radio]');
      var mid = r ? (r.name.match(/^status\[(.+)\]$/) || [])[1] : null;
      li.classList.toggle('antre', !!(mid && ada[mid]));
    });
    var info = $('#sg-info-antre');
    if (!info) { info = D.createElement('p'); info.id = 'sg-info-antre'; info.className = 'sg-info-antre kecil'; fAbsen.insertBefore(info, fAbsen.querySelector('ul.absen')); }
    if (x) { info.textContent = '⏳ ' + jumlahSiswa(x) + ' siswa disimpan di HP pukul ' + jam(x.waktu) + ', menunggu sinyal. Terkirim otomatis.'; info.hidden = false; }
    else info.hidden = true;
  }
  function simpanAbsenKeAntre() {
    var isi = [];
    new FormData(fAbsen).forEach(function (v, k) { if (typeof v === 'string') isi.push([k, v]); });
    var id = idAbsen(fAbsen);
    var a = antre().filter(function (y) { return !(y.id === id && y.akun === akun); });
    a.push({ id: id, akun: akun, url: fAbsen.getAttribute('action') || location.href, isi: isi, waktu: Date.now(),
      judul: nilai(fAbsen, 'kelas') + ' · ' + nilai(fAbsen, 'tanggal') });
    simpanAntre(a);
    tandaiAbsen();
  }
  if (fAbsen) {
    // halaman dari simpanan HP: sesuaikan tanggal dengan yang diminta (atau hari ini)
    if (waktuData) {
      var q = new URLSearchParams(location.search), d = new Date();
      var hariIni = d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2);
      var minta = /^\d{4}-\d{2}-\d{2}$/.test(q.get('tanggal') || '') ? q.get('tanggal') : hariIni;
      var inpT = fAbsen.querySelector('[name="tanggal"]');
      if (inpT && inpT.value !== minta) {
        inpT.value = minta;
        $$('input[type=radio]', fAbsen).forEach(function (r) { r.checked = false; });
        $$('input.ket', fAbsen).forEach(function (r) { r.value = ''; });
        var BLN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'], p = minta.split('-');
        var ring = $('.ringkas-absen > span', fAbsen);
        if (ring) ring.innerHTML = '<b></b> · ' + (+p[2]) + ' ' + BLN[+p[1] - 1] + ' ' + p[0] + ' · <span class="lencana abu">tanpa sinyal</span>';
        if (ring) ring.querySelector('b').textContent = nilai(fAbsen, 'kelas');
      }
    }
    // isi dari antrean (bila absen ini sudah pernah diisi tanpa sinyal)
    var xa = antre().filter(function (y) { return y.id === idAbsen(fAbsen) && y.akun === akun; })[0];
    if (xa) xa.isi.forEach(function (p) {
      if (/^status\[/.test(p[0])) { var r = fAbsen.querySelector('input[name="' + p[0].replace(/"/g, '') + '"][value="' + p[1] + '"]'); if (r) r.checked = true; }
      else if (/^ket\[/.test(p[0])) { var k = fAbsen.querySelector('[name="' + p[0].replace(/"/g, '') + '"]'); if (k) k.value = p[1]; }
    });
    tandaiAbsen();
    var tundaSimpan;
    fAbsen.addEventListener('change', function () {
      if (navigator.onLine !== false) return;
      clearTimeout(tundaSimpan);
      tundaSimpan = setTimeout(simpanAbsenKeAntre, 250);
    });
    fAbsen.addEventListener('click', function (e) {
      if (navigator.onLine === false && e.target.id === 'semua-hadir') setTimeout(simpanAbsenKeAntre, 50);
    });
    fAbsen.addEventListener('submit', function (e) {
      e.preventDefault();
      if (fAbsen.dataset.mengirim) return;
      if (navigator.onLine === false) {
        simpanAbsenKeAntre();
        (window.sgPesan || alert)('Absen disimpan di HP.\nAkan terkirim otomatis begitu ada sinyal. Aplikasi boleh ditutup.');
        return;
      }
      fAbsen.dataset.mengirim = '1';
      Muat.mulai('Menyimpan absen');
      var isi = [];
      new FormData(fAbsen).forEach(function (v, k) { if (typeof v === 'string') isi.push([k, v]); });
      var x = { url: fAbsen.getAttribute('action') || location.href, isi: isi };
      kirimSatu(x).then(function (h) {
        delete fAbsen.dataset.mengirim;
        Muat.henti();
        if (h === 'ok') {
          simpanAntre(antre().filter(function (y) { return !(y.id === idAbsen(fAbsen) && y.akun === akun); }));
          sesiSimpan('sg-toast', 'Absensi tersimpan (' + jumlahSiswa(x) + ' siswa)');
          location.reload();
        } else if (h === 'tolak') {
          (window.sgPesan || alert)(x.galat || 'Absen tidak bisa disimpan. Periksa lagi isinya.');
        } else {
          simpanAbsenKeAntre();
          (window.sgPesan || alert)('Sinyal lemah, absen disimpan di HP.\nAkan terkirim otomatis begitu tersambung.');
        }
      });
    });
  }

  /* ---------- 6. Keterangan penyimpanan offline di kaki halaman (v1.70, untuk memeriksa di HP) ---------- */
  (function () {
    var f = $('footer.bawah');
    if (!f) return;
    var s = D.createElement('span');
    s.className = 'sg-diag';
    f.appendChild(s);
    function tulis(t) { s.textContent = ' · offline: ' + t; }
    var galat = '';
    try { galat = localStorage.getItem('sg-sw-galat') || ''; } catch (e) { /* abaikan */ }
    if (!('serviceWorker' in navigator)) { tulis('tidak didukung HP ini'); return; }
    navigator.serviceWorker.getRegistration().then(function (r) {
      if (!r) { tulis('belum terpasang' + (galat ? ' (' + galat + ')' : '')); return; }
      var aktif = navigator.serviceWorker.controller ? 'aktif' : (r.active ? 'terpasang, buka ulang aplikasi' : r.installing ? 'sedang dipasang' : r.waiting ? 'menunggu' : 'gagal dipasang');
      if (!window.caches) { tulis(aktif + ' · tanpa simpanan'); return; }
      caches.open('siguru-halaman').then(function (c) { return c.keys(); })
        .then(function (k) { tulis(aktif + ' · ' + k.length + ' halaman tersimpan'); })
        .catch(function (e) { tulis(aktif + ' · simpanan galat: ' + ((e && e.message) || e)); });
    }).catch(function (e) { tulis('galat: ' + ((e && e.message) || e)); });
  })();

  /* ---------- mulai ---------- */
  aturStatus();
  var tt = sesiAmbil('sg-toast');
  if (tt) { sesiSimpan('sg-toast', null); setTimeout(function () { toast(tt); }, 300); }
  if (navigator.onLine !== false) setTimeout(function () {
    kirimAntre().then(function (n) { if (n) toast('Absen tertunda terkirim (' + n + ' siswa)'); });
  }, 800);
  if (pengganti && fAbsen) aturPita('<b>Tanpa sinyal</b> · daftar siswa dari simpanan HP. Absen tetap bisa diisi dan dikirim otomatis nanti.');
})();

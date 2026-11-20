/* SiGuru v1.75 · TUAH, maskot SiGuru (Harimau Sumatera bertanjak, berseragam sekolah, bersamping songket).
   Gambar: aset/tuah/*.webp (dari desain Pak Sultan, dipotong per bagian); gerak di guru.css (bagian "v1.75 TUAH").
   - <div class="tuah" data-tuah="sapa|tunjuk|jempol|baca|tulis|nilai|selesai|tidur|payung|tertib|pembuka|jalan|pikir"></div> → diisi otomatis.
   - sgTuah(pose) → teks HTML; sgTuahDayung() → TUAH untuk layar memuat.
   - sgAI.mulai({gaya:'baca'|'tulis'|'nilai', judul, langkah:[3 teks], detik}) → kartu "TUAH sedang bekerja" untuk proses AI.
   - <form data-ai="tulis" data-ai-judul="…" data-ai-langkah="a|b|c" data-ai-detik="40"> → kartu muncul saat formulir dikirim. */
(function () {
  'use strict';
  var D = typeof document !== 'undefined' ? document : null;
  var G = typeof window !== 'undefined' ? window : self;
  var V = '177';
  /* w,h = ukuran gambar dasar; p = bagian yang bergerak (posisi % dari gambar dasar, ox/oy = titik putar %) */
  var DATA = {"sapa":{"w":201,"h":307,"p":[{"n":"lengan","x":0.0,"y":34.55,"w":30.56,"ox":90.9,"oy":85.2}]},"kosong":{"w":236,"h":308,"p":[{"n":"lengan","x":67.11,"y":38.03,"w":20.0,"ox":0.0,"oy":78.8},{"n":"b1","x":79.74,"y":8.65,"w":9.47,"ox":50.0,"oy":50.0},{"n":"b2","x":73.16,"y":17.1,"w":6.84,"ox":50.0,"oy":50.0},{"n":"b3","x":91.84,"y":46.08,"w":8.16,"ox":50.0,"oy":50.0}]},"jempol":{"w":271,"h":308,"p":[{"n":"lengan","x":1.83,"y":39.31,"w":18.99,"ox":69.9,"oy":87.0},{"n":"b1","x":0.92,"y":27.42,"w":10.76,"ox":83.0,"oy":93.9}]},"selesai":{"w":197,"h":291,"p":[{"n":"b1","x":3.14,"y":22.81,"w":13.21,"ox":50.0,"oy":50.0},{"n":"b2","x":12.58,"y":32.84,"w":8.81,"ox":50.0,"oy":50.0},{"n":"b3","x":91.19,"y":32.84,"w":8.81,"ox":50.0,"oy":50.0},{"n":"b4","x":83.65,"y":42.86,"w":11.32,"ox":50.0,"oy":50.0}]},"ai":{"w":285,"h":305,"p":[]},"tertib":{"w":296,"h":306,"p":[]},"payung":{"w":296,"h":317,"p":[]},"pembuka":{"w":311,"h":299,"p":[]},"jalan":{"w":319,"h":434,"p":[{"n":"lengan","x":76.85,"y":45.0,"w":23.15,"ox":5.9,"oy":80.5},{"n":"lengan2","x":19.46,"y":53.14,"w":16.93,"ox":75.9,"oy":17.0}]},"nilai":{"w":342,"h":435,"p":[{"n":"kertas","x":66.12,"y":32.05,"w":33.88,"ox":7.0,"oy":74.6}]},"tidur":{"w":309,"h":379,"p":[]},"baca":{"w":262,"h":427,"p":[]},"tulis":{"w":275,"h":424,"p":[]},"pikir":{"w":221,"h":435,"p":[]}};
  /* nama pose → gambar */
  var ALIAS = { sapa: 'sapa', tunjuk: 'kosong', kosong: 'kosong', jempol: 'jempol', ai: 'ai', baca: 'baca', tulis: 'tulis', nilai: 'nilai',
    selesai: 'selesai', tidur: 'tidur', payung: 'payung', tertib: 'tertib', pembuka: 'pembuka', jalan: 'jalan', pikir: 'pikir' };
  var TAMBAH = {
    ai: '<i class="tg-pindai"></i>',
    payung: '<i class="tg-hujan"><b></b><b></b><b></b><b></b><b></b><b></b><b></b></i>',
    tidur: '<i class="tg-zzz">z<b>z</b><b>z</b></i>',
    baca: '<i class="tg-huruf"><b>a</b><b>b</b><b>c</b><b>1</b><b>2</b></i>',
    tulis: '<i class="tg-gores"><b></b><b></b><b></b></i>',
    pikir: '<i class="tg-tanya">?</i>'
  };
  function src(n) { return 'aset/tuah/' + n + '.webp?v=' + V; }
  function sgTuah(pose, kelas) {
    var n = ALIAS[pose] || 'sapa', d = DATA[n];
    var h = '<span class="tuah-gb tg-' + n + (kelas ? ' ' + kelas : '') + '" style="aspect-ratio:' + d.w + '/' + d.h + '" role="img" aria-label="TUAH, maskot SiGuru">' +
      '<img class="tg-dasar" src="' + src(n) + '" alt="" draggable="false" decoding="async">';
    d.p.forEach(function (p) {
      h += '<img class="tg-p tg-' + p.n + '" src="' + src(n + '-' + p.n) + '" alt="" draggable="false" decoding="async" style="left:' + p.x + '%;top:' + p.y + '%;width:' + p.w + '%;transform-origin:' + p.ox + '% ' + p.oy + '%">';
    });
    return h + (TAMBAH[n] || '') + '</span>';
  }
  /* daftar berkas gambar (disimpan service worker agar tetap tampil tanpa sinyal) */
  var BERKAS = [];
  Object.keys(DATA).forEach(function (n) { BERKAS.push(src(n)); DATA[n].p.forEach(function (p) { BERKAS.push(src(n + '-' + p.n)); }); });
  function sgTuahDayung() { return sgTuah('jalan', 'tuah-dayung'); }
  G.sgTuahBerkas = BERKAS;
  G.sgTuah = sgTuah;
  G.sgTuahDayung = sgTuahDayung;
  if (!D) return; // di service worker hanya butuh gambarnya

  function isiSemua(akar) {
    Array.prototype.forEach.call((akar || D).querySelectorAll('.tuah[data-tuah]:not([data-terisi])'), function (el) {
      el.innerHTML = sgTuah(el.getAttribute('data-tuah'));
      el.setAttribute('data-terisi', '1');
    });
  }
  window.sgTuahIsi = isiSemua;

  /* ---------- Kartu "TUAH sedang bekerja" untuk proses AI ---------- */
  var JUDUL = { baca: 'TUAH sedang membaca…', tulis: 'TUAH sedang menulis…', nilai: 'TUAH sedang menilai…' };
  var LANGKAH = {
    baca: ['Membaca foto', 'Mengenali isinya', 'Merapikan hasil'],
    tulis: ['Memahami permintaan', 'Menyusun isi', 'Memeriksa ulang'],
    nilai: ['Membaca jawaban siswa', 'Mencocokkan dengan soal', 'Menyusun nilai & komentar']
  };
  var IK = {
    tunggu: '<svg viewBox="0 0 18 18" aria-hidden="true"><circle cx="9" cy="9" r="6" fill="none" stroke="#C9D2E3" stroke-width="2"/></svg>',
    jalan: '<svg viewBox="0 0 18 18" aria-hidden="true"><circle class="sg-ai-putar" cx="9" cy="9" r="7" fill="none" stroke="#2A2C84" stroke-width="2.4" stroke-dasharray="11 40"/></svg>',
    ok: '<svg viewBox="0 0 18 18" aria-hidden="true"><path class="sg-ai-ok" pathLength="1" d="M4 9.5 L7.5 13 L14 5.5" fill="none" stroke="#2E9E48" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>'
  };
  function istirahat(p) { return /istirahat|limit|kuota|quota|429|rate/i.test(String(p || '')); }

  var sgAI = {
    mulai: function (o) {
      o = o || {};
      var gaya = LANGKAH[o.gaya] ? o.gaya : 'tulis';
      var langkah = (o.langkah && o.langkah.length ? o.langkah : LANGKAH[gaya]).slice(0, 4);
      var detik = Math.max(8, +o.detik || 30), t0 = Date.now(), teksSendiri = null, maju = null, selesaiSudah = false;
      var latar = D.createElement('div');
      latar.className = 'sg-ai-latar tak-cetak';
      latar.innerHTML = '<div class="sg-ai-kartu" role="status" aria-live="polite"><div class="sg-ai-tuah">' + sgTuah(gaya) + '</div>' +
        '<b class="sg-ai-judul"></b><ul class="sg-ai-langkah"></ul><div class="sg-ai-bar"><i></i></div><small class="sg-ai-sisa"></small>' +
        '<p class="sg-ai-pesan" hidden></p><button type="button" class="sg-ai-tutup" hidden>Tutup</button></div>';
      latar.querySelector('.sg-ai-judul').textContent = o.judul || JUDUL[gaya];
      var ul = latar.querySelector('.sg-ai-langkah');
      langkah.forEach(function (t) { var li = D.createElement('li'); li.innerHTML = IK.tunggu + '<span></span>'; li.querySelector('span').textContent = t; ul.appendChild(li); });
      D.body.appendChild(latar);
      requestAnimationFrame(function () { latar.classList.add('tampil'); });
      var bar = latar.querySelector('.sg-ai-bar i'), sisa = latar.querySelector('.sg-ai-sisa');
      var aktif = -1;
      function langkahKe(i) {
        if (i === aktif) return;
        Array.prototype.forEach.call(ul.children, function (li, k) {
          var st = k < i ? 'ok' : k === i ? 'jalan' : 'tunggu';
          if (li.getAttribute('data-st') === st) return;
          li.setAttribute('data-st', st);
          li.className = st;
          li.firstChild.outerHTML = IK[st];
        });
        aktif = i;
      }
      function detak() {
        if (selesaiSudah) return;
        var e = (Date.now() - t0) / 1000;
        var f = maju != null ? maju : Math.min(0.92, e / detik * 0.9);
        bar.style.width = Math.round(f * 100) + '%';
        langkahKe(maju != null ? Math.min(langkah.length - 1, Math.floor(maju * langkah.length)) : Math.min(langkah.length - 1, Math.floor(e / (detik / langkah.length))));
        sisa.textContent = teksSendiri || (e < detik ? '± ' + Math.max(1, Math.ceil(detik - e)) + ' detik lagi' : 'Hampir selesai, TUAH masih bekerja…');
      }
      detak();
      var jam = setInterval(detak, 500);
      function tutup() {
        clearInterval(jam);
        latar.classList.remove('tampil');
        setTimeout(function () { latar.remove(); }, 300);
      }
      var k = {
        teks: function (t) { teksSendiri = t; detak(); return k; },
        kemajuan: function (f) { maju = Math.max(0, Math.min(1, f)); detak(); return k; },
        selesai: function (judul) {
          return new Promise(function (ok) {
            if (selesaiSudah) { ok(); return; }
            selesaiSudah = true; clearInterval(jam);
            langkahKe(langkah.length);
            bar.style.width = '100%';
            latar.querySelector('.sg-ai-tuah').innerHTML = sgTuah('selesai');
            latar.querySelector('.sg-ai-judul').textContent = judul || 'Selesai!';
            sisa.textContent = '';
            setTimeout(function () { tutup(); ok(); }, 1300);
          });
        },
        gagal: function (pesan) {
          return new Promise(function (ok) {
            if (selesaiSudah) { ok(); return; }
            selesaiSudah = true; clearInterval(jam);
            if (!istirahat(pesan)) { tutup(); ok(); return; }
            latar.querySelector('.sg-ai-tuah').innerHTML = sgTuah('tidur');
            latar.querySelector('.sg-ai-judul').textContent = 'TUAH sedang istirahat';
            ul.hidden = true; latar.querySelector('.sg-ai-bar').hidden = true; sisa.textContent = '';
            var p = latar.querySelector('.sg-ai-pesan'); p.textContent = pesan; p.hidden = false;
            var b = latar.querySelector('.sg-ai-tutup'); b.hidden = false;
            b.onclick = function () { tutup(); ok(); };
            b.focus();
          });
        },
        tutup: function () { selesaiSudah = true; tutup(); }
      };
      return k;
    },
    /** dipakai di rantai fetch: .then(sgAI.akhiri(kartu)) — kartu ditutup dengan pose sesuai hasil, lalu hasil diteruskan */
    akhiri: function (k) {
      return function (r) {
        if (!k) return r;
        return (r && r.ok ? k.selesai() : k.gagal(r && (r.galat || r.teks))).then(function () { return r; });
      };
    }
  };
  window.sgAI = sgAI;

  // formulir biasa (halaman berganti saat AI selesai): <form data-ai="tulis" data-ai-judul data-ai-langkah="a|b|c" data-ai-detik>
  D.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f.hasAttribute || !f.hasAttribute('data-ai')) return;
    setTimeout(function () {
      if (e.defaultPrevented || navigator.onLine === false) return;
      sgAI.mulai({ gaya: f.getAttribute('data-ai'), judul: f.getAttribute('data-ai-judul'),
        langkah: (f.getAttribute('data-ai-langkah') || '').split('|').filter(Boolean), detik: +f.getAttribute('data-ai-detik') || 40 });
      if (window.sgMuat) window.sgMuat.henti();
    }, 0);
  });
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) Array.prototype.forEach.call(D.querySelectorAll('.sg-ai-latar'), function (x) { x.remove(); });
  });

  // TUAH menyapa di Beranda: sekali sehari; sesudahnya sapaan biasa
  function sapaSekali() {
    var el = D.querySelector('.tuah-balon[data-sekali]');
    if (!el) return;
    var d = new Date(), hari = d.getFullYear() + '-' + (d.getMonth() + 1) + '-' + d.getDate(), k = 'sg-sekali-' + el.getAttribute('data-sekali');
    var sudah = '';
    try { sudah = localStorage.getItem(k) || ''; } catch (e) { /* abaikan */ }
    if (sudah === hari) { el.remove(); return; }
    try { localStorage.setItem(k, hari); } catch (e) { /* abaikan */ }
    el.hidden = false;
    var h1 = D.querySelector('.sapa-biasa'); if (h1) h1.hidden = true;
    var b = el.querySelector('.tutup-sapa');
    if (b) b.onclick = function () { el.remove(); if (h1) h1.hidden = false; };
  }
  function mulai() { isiSemua(); sapaSekali(); }
  if (D.readyState === 'loading') D.addEventListener('DOMContentLoaded', mulai);
  else mulai();
})();

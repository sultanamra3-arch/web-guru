(function () {
  'use strict';
  if ('serviceWorker' in navigator) window.addEventListener('load', function () { navigator.serviceWorker.register('sw.js').catch(function () {}); });

  function el(tag, kelas, teks) { var e = document.createElement(tag); if (kelas) e.className = kelas; if (teks != null) e.textContent = teks; return e; }
  function $(s, r) { return (r || document).querySelector(s); }
  function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }

  // Cegah klik ganda pada formulir biasa
  $$('form[method="post"]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (f.dataset.terkirim) { e.preventDefault(); return; }
      // tandai terkirim setelah semua pemeriksaan lain selesai; bila dibatalkan, jangan dikunci
      setTimeout(function () {
        if (e.defaultPrevented) return;
        f.dataset.terkirim = '1';
        setTimeout(function () { delete f.dataset.terkirim; }, 15000);
      }, 0);
    });
  });

  /* ---------- Foto ---------- */
  function kecilkanFoto(berkas, maks) {
    maks = maks || 1600;
    return new Promise(function (ok, gagal) {
      var url = URL.createObjectURL(berkas), img = new Image();
      img.onload = function () {
        var s = Math.min(1, maks / Math.max(img.width, img.height)), c = document.createElement('canvas');
        c.width = Math.round(img.width * s); c.height = Math.round(img.height * s);
        c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url); ok(c.toDataURL('image/jpeg', 0.82));
      };
      img.onerror = function () { URL.revokeObjectURL(url); gagal(new Error('Foto tidak bisa dibuka')); };
      img.src = url;
    });
  }
  /* Gambar soal & gambar pilihan (formulir soal) */
  $$('.unggah-gambar').forEach(function (k) {
    var inp = $('input[type=file]', k), sembunyi = $('input[type=hidden]', k), prat = $('.pratinjau-gambar', k);
    inp.addEventListener('change', function () {
      var f = inp.files[0]; inp.value = '';
      if (!f) return;
      kecilkanFoto(f, k.classList.contains('kecil-gambar') ? 700 : 1200).then(function (d) {
        sembunyi.value = d;
        prat.innerHTML = ''; var im = document.createElement('img'); im.src = d; im.className = k.classList.contains('kecil-gambar') ? 'gambar-opsi' : 'gambar-soal'; prat.append(im);
        var h = $('input[type=checkbox]', k); if (h) h.checked = false;
      }).catch(function (e) { alert(e.message); });
    });
  });

  function pasangFoto(form) {
    var foto = [], prat = $('.pratinjau-foto', form);
    function gambar() {
      if (!prat) return;
      prat.innerHTML = '';
      foto.forEach(function (f, i) {
        var w = el('span', 'mini'), im = document.createElement('img'), x = el('button', null, '×');
        im.src = f; x.type = 'button'; x.onclick = function () { foto.splice(i, 1); gambar(); };
        w.append(im, x); prat.append(w);
      });
    }
    $$('.ambil-foto', form).forEach(function (inp) {
      inp.addEventListener('change', function () {
        var b = Array.prototype.slice.call(inp.files, 0, 3 - foto.length); inp.value = '';
        Promise.all(b.map(function (x) { return kecilkanFoto(x); })).then(function (f) { foto = foto.concat(f); gambar(); });
      });
    });
    return function () { return foto; };
  }

  function kirimAI(token, data) {
    var fd = new FormData();
    fd.append('_t', token);
    Object.keys(data).forEach(function (k) {
      var v = data[k];
      if (Array.isArray(v)) v.forEach(function (x) { fd.append(k + '[]', x); }); else fd.append(k, v);
    });
    return fetch('?p=api_ai', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.text(); })
      .then(function (t) { try { return JSON.parse(t); } catch (e) { return { ok: false, galat: t.indexOf('Sesi formulir') >= 0 ? 'Sesi kedaluwarsa, muat ulang halaman.' : 'Server sibuk atau waktu habis. Coba lagi (jumlah dikurangi bila perlu).' }; } })
      .catch(function () { return { ok: false, galat: 'Tidak tersambung ke server.' }; });
  }
  function dataForm(form) {
    var d = {};
    $$('input[name],select[name],textarea[name]', form).forEach(function (i) { if (i.type !== 'file') d[i.name] = i.value; });
    return d;
  }
  function tampilGalat(st, r) {
    st.innerHTML = '';
    st.append(el('div', 'pesan galat', r.galat || r.teks || 'Gagal.'));
    (r.rincian || []).forEach(function (x) { st.append(el('div', 'redup kecil', x)); });
  }
  function jamTunggu(st, teks) {
    var mulai = Date.now();
    st.textContent = teks;
    return setInterval(function () { st.textContent = teks + ' (' + Math.round((Date.now() - mulai) / 1000) + ' detik)'; }, 1000);
  }

  /* ---------- Generator soal ---------- */
  var fs = $('#form-soal-ai');
  if (fs) {
    var hitungTotal = function () {
      var n = 0; ['jml_pg', 'jml_bs', 'jml_isian', 'jml_esai'].forEach(function (k) { n += Math.max(0, +fs.elements[k].value || 0); });
      var t = $('#total-soal'); if (t) t.textContent = n;
      return n;
    };
    if (fs.elements.jml_pg) { fs.addEventListener('input', hitungTotal); hitungTotal(); }
    var ambilFotoSoal = pasangFoto(fs), hasil = $('#hasil-soal'), daftar = $('#daftar-hasil'), st = $('#status-ai'), btn = $('#tombol-buat-soal');
    var TIPE = { pg: 'Pilihan ganda', bs: 'Benar/Salah', isian: 'Isian', esai: 'Uraian' };
    fs.addEventListener('submit', function (e) {
      e.preventDefault();
      if (fs.elements.jml_pg && !hitungTotal()) { st.textContent = 'Isi jumlah soal minimal 1.'; return; }
      if (fs.elements.jml_pg && hitungTotal() > 60) { st.textContent = 'Maksimal 60 soal sekali buat.'; return; }
      var d = dataForm(fs); d.aksi = 'buat_soal'; d.foto = ambilFotoSoal();
      btn.disabled = true;
      var t = jamTunggu(st, '⏳ AI sedang menyusun soal…');
      kirimAI(fs.dataset.token, d).then(function (r) {
        clearInterval(t); btn.disabled = false;
        if (!r.ok) { tampilGalat(st, r); return; }
        var hit = {}; r.soal.forEach(function (s) { hit[s.tipe] = (hit[s.tipe] || 0) + 1; });
        st.textContent = '✅ ' + r.soal.length + ' soal (' + Object.keys(TIPE).filter(function (k) { return hit[k]; }).map(function (k) { return TIPE[k] + ' ' + hit[k]; }).join(', ') + ') dibuat oleh ' + r.oleh + '. Periksa, hapus centang yang tidak dipakai, lalu simpan.'
          + (r.kurang && r.kurang.length ? ' ⚠ Kurang: ' + r.kurang.join(', ') + ' — buat lagi untuk menambah.' : '');
        hasil.hidden = false; daftar.innerHTML = '';
        hasil.elements.data.value = JSON.stringify(r.soal);
        hasil.elements.mapel.value = d.mapel; hasil.elements.kelas_angka.value = d.kelas; hasil.elements.topik.value = d.topik;
        r.soal.forEach(function (s, i) {
          var li = el('li'), lab = el('label', 'soal-baris'), cb = document.createElement('input');
          cb.type = 'checkbox'; cb.name = 'pilih[]'; cb.value = i; cb.checked = true;
          var isi = el('span', 'soal-isi');
          isi.append(el('small', 'meta', (TIPE[s.tipe] || s.tipe) + ' · ' + s.tingkat + (s.level ? ' · ' + s.level : '')));
          isi.append(el('span', 'tanya', (i + 1) + '. ' + s.pertanyaan));
          var keys = Object.keys(s.opsi || {});
          if (keys.length) {
            var ol = el('ol', 'opsi'); ol.type = 'A';
            keys.forEach(function (k) { var o = el('li', k === String(s.kunci).trim().toUpperCase() ? 'benar' : '', s.opsi[k]); ol.append(o); });
            isi.append(ol);
          } else if (s.kunci) isi.append(el('small', 'kunci', 'Kunci: ' + s.kunci));
          if (s.pembahasan) isi.append(el('small', 'pembahasan', 'Pembahasan: ' + s.pembahasan));
          lab.append(cb, isi); li.append(lab); daftar.append(li);
        });
        hasil.scrollIntoView({ behavior: 'smooth' });
      });
    });
    $('#pilih-semua-hasil').addEventListener('change', function () { var c = this.checked; $$('input[name="pilih[]"]', hasil).forEach(function (x) { x.checked = c; }); });
  }

  /* ---------- Modul / LKPD / Presentasi ---------- */
  var fd = $('#form-dokumen-ai');
  if (fd) {
    var ambilFotoDok = pasangFoto(fd), std = $('#status-ai'), bd = $('#tombol-buat-dokumen');
    fd.addEventListener('submit', function (e) {
      e.preventDefault();
      var d = dataForm(fd); d.aksi = 'buat_dokumen'; d.jenis = fd.dataset.jenis; d.foto = ambilFotoDok();
      bd.disabled = true;
      var t = jamTunggu(std, '⏳ AI sedang menyusun (biasanya 20–90 detik, jangan tutup halaman)…');
      kirimAI(fd.dataset.token, d).then(function (r) {
        clearInterval(t);
        if (!r.ok) { bd.disabled = false; tampilGalat(std, r); return; }
        std.textContent = '✅ Selesai (' + r.oleh + '). Membuka…';
        location.href = r.url;
      });
    });
  }
  var fr = $('#form-revisi');
  if (fr) {
    fr.addEventListener('submit', function (e) {
      e.preventDefault();
      var st2 = $('#status-ai', fr), b = $('button', fr);
      if (!fr.elements.perintah.value.trim()) return;
      b.disabled = true;
      var t = jamTunggu(st2, '⏳ Merevisi…');
      kirimAI(fr.dataset.token, { aksi: 'revisi', id: fr.dataset.id, perintah: fr.elements.perintah.value }).then(function (r) {
        clearInterval(t); b.disabled = false;
        if (!r.ok) { tampilGalat(st2, r); return; }
        location.reload();
      });
    });
  }

  /* ---------- Presentasi: tayang & PowerPoint ---------- */
  var deck = $('#deck');
  if (deck) {
    var slides = $$('.slide', deck), idx = 0;
    function tampil(i) { idx = Math.max(0, Math.min(slides.length - 1, i)); slides.forEach(function (s, j) { s.classList.toggle('kini', j === idx); }); }
    $('#mulai-tayang').addEventListener('click', function () {
      deck.classList.add('tayang'); tampil(0);
      if (deck.requestFullscreen) deck.requestFullscreen().catch(function () {});
    });
    function keluar() { deck.classList.remove('tayang'); if (document.fullscreenElement) document.exitFullscreen(); }
    document.addEventListener('fullscreenchange', function () { if (!document.fullscreenElement) deck.classList.remove('tayang'); });
    document.addEventListener('keydown', function (e) {
      if (!deck.classList.contains('tayang')) return;
      if (['ArrowRight', 'PageDown', ' ', 'Enter'].indexOf(e.key) >= 0) { e.preventDefault(); tampil(idx + 1); }
      if (['ArrowLeft', 'PageUp', 'Backspace'].indexOf(e.key) >= 0) { e.preventDefault(); tampil(idx - 1); }
      if (e.key === 'Escape') keluar();
    });
    deck.addEventListener('click', function (e) {
      if (!deck.classList.contains('tayang')) return;
      tampil(e.clientX > window.innerWidth / 3 ? idx + 1 : idx - 1);
    });
    var x0 = null;
    deck.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
    deck.addEventListener('touchend', function (e) {
      if (x0 === null || !deck.classList.contains('tayang')) return;
      var dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 40) tampil(dx < 0 ? idx + 1 : idx - 1); x0 = null;
    });

    $('#unduh-pptx').addEventListener('click', function () {
      if (typeof PptxGenJS === 'undefined') { alert('Pustaka PowerPoint belum termuat, tunggu sebentar lalu coba lagi.'); return; }
      var data = JSON.parse($('#data-presentasi').textContent), p = new PptxGenJS();
      p.layout = 'LAYOUT_WIDE'; p.title = data.judul;
      var NAVY = '2A2C84', EMAS = 'F2C230', logo = new URL('aset/logo.png', location.href).href;
      var s0 = p.addSlide(); s0.background = { color: NAVY };
      s0.addImage({ path: logo, x: 5.9, y: 0.5, w: 1.5, h: 1.58 });
      s0.addText(data.judul, { x: 0.6, y: 2.4, w: 12.1, h: 1.4, fontSize: 40, bold: true, color: 'FFFFFF', align: 'center', fontFace: 'Calibri' });
      s0.addText(data.subjudul || '', { x: 0.6, y: 3.8, w: 12.1, h: 0.7, fontSize: 22, color: EMAS, align: 'center' });
      s0.addText(deck.dataset.guru + ' · ' + deck.dataset.sekolah, { x: 0.6, y: 6.3, w: 12.1, h: 0.5, fontSize: 14, color: 'DDDDDD', align: 'center' });
      data.slide.forEach(function (s, i) {
        var sl = p.addSlide(); sl.background = { color: 'FFFFFF' };
        sl.addShape(p.ShapeType.rect, { x: 0, y: 0, w: 13.33, h: 1.1, fill: { color: NAVY } });
        sl.addShape(p.ShapeType.rect, { x: 0, y: 1.1, w: 13.33, h: 0.08, fill: { color: EMAS } });
        sl.addText(s.judul, { x: 0.5, y: 0.12, w: 11.2, h: 0.86, fontSize: 28, bold: true, color: 'FFFFFF', fontFace: 'Calibri' });
        sl.addImage({ path: logo, x: 12.3, y: 0.15, w: 0.76, h: 0.8 });
        sl.addText(s.poin.map(function (t) { return { text: t, options: { bullet: true, breakLine: true } }; }),
          { x: 0.7, y: 1.5, w: 11.9, h: 5.4, fontSize: 22, color: '1A1A2E', valign: 'top', paraSpaceAfter: 10 });
        sl.addText(String(i + 2), { x: 12.4, y: 7.0, w: 0.7, h: 0.4, fontSize: 12, color: '888888', align: 'right' });
        if (s.catatan) sl.addNotes(s.catatan);
      });
      p.writeFile({ fileName: (data.judul || 'presentasi').replace(/[^\w\- ]+/g, '').slice(0, 60) + '.pptx' });
    });
  }

  /* ---------- Form ujian: jenis & pengaman ---------- */
  var fm = $('.pilih-mode');
  if (fm) {
    var f = fm.closest('form');
    function aturMode() {
      var lat = ($('input[name=mode]:checked', f) || {}).value === 'latihan';
      $$('.hanya-ujian', f).forEach(function (e) { e.hidden = lat; });
      var pg = $('#pengaman', f);
      if (pg) { $('.pengaman-isi', f).hidden = !pg.checked; $('.pengaman-mati', f).hidden = pg.checked; }
    }
    f.addEventListener('change', aturMode); aturMode();
  }

  /* ---------- Absensi ---------- */
  var fa = $('#form-absen');
  if (fa) {
    var baris = $$('.absen li', fa);
    function belum() { return baris.filter(function (li) { return !$('input[type=radio]:checked', li); }); }
    function hitung() {
      var h = { H: 0, S: 0, I: 0, A: 0 };
      $$('input[type=radio]:checked', fa).forEach(function (r) { h[r.value]++; });
      var b = belum().length;
      $('#hitung-absen').innerHTML = 'H ' + h.H + ' · S ' + h.S + ' · I ' + h.I + ' · A ' + h.A + (b ? ' · <b>belum dipilih ' + b + '</b>' : '');
      baris.forEach(function (li) { li.classList.toggle('belum-pilih', !$('input[type=radio]:checked', li)); });
    }
    function sisanyaHadir() { belum().forEach(function (li) { var r = $('input[type=radio][value=H]', li); if (r) r.checked = true; }); hitung(); }
    fa.addEventListener('change', hitung);
    $('#semua-hadir').addEventListener('click', sisanyaHadir);
    fa.addEventListener('submit', function (e) {
      var b = belum();
      if (!b.length) return;
      if (b.length === baris.length) { e.preventDefault(); alert('Belum ada siswa yang dipilih. Pilih S/I/A untuk yang tidak hadir, atau tekan "Sisanya hadir".'); return; }
      if (confirm(b.length + ' siswa belum dipilih. Tandai mereka HADIR?\n\nOK = tandai hadir lalu simpan\nBatal = periksa lagi')) { sisanyaHadir(); return; }
      e.preventDefault(); b[0].scrollIntoView({ block: 'center' });
    });
    hitung();
  }

  /* ---------- Nilai: tandai di bawah KKTP ---------- */
  var tn = $('.tabel-nilai');
  if (tn) {
    var kktp = parseFloat(tn.dataset.kktp) || 0;
    function warnai(i) { var v = parseFloat(String(i.value).replace(',', '.')); i.classList.toggle('kurang', !isNaN(v) && v < kktp); }
    $$('.isi-nilai', tn).forEach(function (i, n, semua) {
      warnai(i);
      i.addEventListener('input', function () { warnai(i); });
      i.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); var b = semua[n + 1]; if (b) b.focus(); } });
    });
  }

  /* ---------- Bank soal ---------- */
  var ps = $('#pilih-semua-soal');
  if (ps) {
    var cbs = $$('#form-bank input[name="pilih[]"]'), jml = $('#jml-soal');
    function n() { jml.textContent = cbs.filter(function (c) { return c.checked; }).length; }
    ps.addEventListener('change', function () { cbs.forEach(function (c) { c.checked = ps.checked; }); n(); });
    cbs.forEach(function (c) { c.addEventListener('change', n); });
  }
  var ts = $('#tipe-soal');
  if (ts) {
    function o() { $('#bagian-opsi').hidden = ['pg', 'bs'].indexOf(ts.value) < 0; }
    ts.addEventListener('change', o); o();
  }

  /* ---------- Sertifikat: pilih kelas (tanpa memuat ulang halaman) ---------- */
  var pk = $('#pilih-kelas-sertifikat');
  if (pk) {
    function tampilKelas() {
      $$('.blok-kelas').forEach(function (b) {
        var on = b.dataset.kelas === pk.value;
        b.hidden = !on;
        $$('input', b).forEach(function (i) { i.disabled = !on; });
      });
    }
    pk.addEventListener('change', tampilKelas); tampilKelas();
    $$('.centang-semua-kelas').forEach(function (cs) {
      cs.addEventListener('change', function () { $$('input[name="siswa[]"]', cs.closest('.blok-kelas')).forEach(function (c) { c.checked = cs.checked; }); });
    });
  }

  /* ---------- Isian jam 24 jam (tanpa AM/PM): ketik 0715 → 07:15 ---------- */
  document.addEventListener('input', function (e) {
    var i = e.target;
    if (!i.classList || !i.classList.contains('jam24')) return;
    var d = i.value.replace(/[^0-9]/g, '').slice(0, 4);
    i.value = d.length > 2 ? d.slice(0, 2) + ':' + d.slice(2) : d;
  });
  document.addEventListener('blur', function (e) {
    var i = e.target;
    if (!i.classList || !i.classList.contains('jam24') || !i.value) return;
    var d = i.value.replace(/[^0-9]/g, '');
    if (d.length === 3) d = '0' + d;
    if (d.length === 1 || d.length === 2) d = ('0' + d).slice(-2) + '00';
    if (d.length === 4 && +d.slice(0, 2) < 24 && +d.slice(2) < 60) i.value = d.slice(0, 2) + ':' + d.slice(2);
  }, true);
})();

/* Soal manual: pastikan setiap PG yang diisi punya kunci */
(function () {
  var f = document.getElementById('form-manual');
  if (!f) return;
  f.addEventListener('submit', function (e) {
    var kurang = [];
    Array.prototype.forEach.call(f.querySelectorAll('.soal-manual'), function (k) {
      var t = k.querySelector('textarea[name$="[pertanyaan]"]'), r = k.querySelectorAll('input[type=radio]');
      if (t && t.value.trim() && r.length && !k.querySelector('input[type=radio]:checked')) { kurang.push(k); k.classList.add('perlu-kunci'); }
      else k.classList.remove('perlu-kunci');
    });
    if (kurang.length) {
      e.preventDefault();
      kurang[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
      alert(kurang.length + ' soal pilihan ganda belum ditandai kuncinya (bulatan di depan pilihan).');
    }
  });
})();

/* Pilihan mapel: "Lainnya…" → kotak isian yang dikirim sebagai mapel */
(function () {
  function atur(s) {
    var lain = s.parentNode.querySelector('.mapel-lain'); if (!lain) return;
    var pakaiLain = s.value === '__lain';
    lain.hidden = !pakaiLain;
    if (pakaiLain) { s.removeAttribute('name'); lain.name = 'mapel'; lain.required = s.required || s.dataset.wajib === '1'; }
    else { s.name = 'mapel'; lain.removeAttribute('name'); lain.required = false; }
  }
  document.querySelectorAll('select.pilih-mapel').forEach(function (s) {
    if (s.required) s.dataset.wajib = '1';
    atur(s);
    s.addEventListener('change', function () { atur(s); if (s.value === '__lain') s.parentNode.querySelector('.mapel-lain').focus(); });
  });
})();

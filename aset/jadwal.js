/* SiGuru · isi jadwal mingguan cepat: tab hari, pilih kelas per jam, salin hari, baca dari foto jadwal sekolah */
(function () {
  'use strict';
  var form = document.getElementById('form-jadwal');
  if (!form) return;
  function el(tag, kelas, teks) { var e = document.createElement(tag); if (kelas) e.className = kelas; if (teks != null) e.textContent = teks; return e; }
  function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
  var HARI = { senin: 1, selasa: 2, rabu: 3, kamis: 4, jumat: 5, "jum'at": 5, sabtu: 6 };

  function hitung() {
    $$('.tab-hari button', form).forEach(function (b) {
      var n = $$('section[data-hari="' + b.dataset.hari + '"] .pilih-kelas', form).filter(function (s) { return s.value && s.value !== '__ketik'; }).length;
      b.querySelector('.jml-hari').textContent = n || '';
    });
  }
  function buka(h) {
    $$('.tab-hari button', form).forEach(function (b) { b.classList.toggle('aktif', b.dataset.hari === String(h)); });
    $$('section.hari-jadwal', form).forEach(function (s) { s.hidden = s.dataset.hari !== String(h); });
  }
  $$('.tab-hari button', form).forEach(function (b) { b.addEventListener('click', function () { buka(b.dataset.hari); }); });

  // "✎ ketik…" → tambah pilihan baru ke semua daftar kelas
  function tambahKelas(nama) {
    $$('.pilih-kelas', form).forEach(function (s) {
      if ($$('option', s).some(function (o) { return o.value === nama; })) return;
      var o = el('option', null, nama); o.value = nama; s.insertBefore(o, s.lastElementChild);
    });
  }
  function aturKelas(sel, nilai) {
    if (nilai && !$$('option', sel).some(function (o) { return o.value === nilai; })) tambahKelas(nilai);
    sel.value = nilai || '';
    sel.closest('.baris-jam').classList.toggle('terisi', !!sel.value);
  }
  $$('.pilih-kelas', form).forEach(function (sel) {
    var lama = sel.value;
    sel.addEventListener('focus', function () { lama = sel.value; });
    sel.addEventListener('change', function () {
      if (sel.value === '__ketik') {
        var inp = el('input', 'ketik-kelas'); inp.placeholder = 'nama kelas, mis. VII A';
        sel.hidden = true; sel.parentNode.insertBefore(inp, sel.nextSibling); inp.focus();
        var selesai = function () {
          var v = inp.value.trim().replace(/\s+/g, ' ');
          inp.remove(); sel.hidden = false;
          if (v) aturKelas(sel, v); else sel.value = lama;
          hitung();
        };
        inp.addEventListener('blur', selesai);
        inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); inp.blur(); } });
        return;
      }
      sel.closest('.baris-jam').classList.toggle('terisi', !!sel.value);
      hitung();
    });
  });

  $$('.salin-hari', form).forEach(function (b) {
    b.addEventListener('click', function () {
      var h = +b.dataset.hari, asal = h - 1;
      if (asal < 1) { asal = 6; }
      var dari = $$('section[data-hari="' + asal + '"] .baris-jam', form), ke = $$('section[data-hari="' + h + '"] .baris-jam', form);
      ke.forEach(function (r, i) {
        if (!dari[i]) return;
        aturKelas(r.querySelector('.pilih-kelas'), dari[i].querySelector('.pilih-kelas').value);
        r.querySelector('.mapel-jam').value = dari[i].querySelector('.mapel-jam').value;
      });
      hitung();
    });
  });

  /* ---------- dari foto jadwal sekolah ---------- */
  var foto = [], pf = document.getElementById('foto-jadwal'), st = document.getElementById('status-jadwal');
  function kecilkan(berkas) {
    return new Promise(function (ok, gagal) {
      var url = URL.createObjectURL(berkas), img = new Image();
      img.onload = function () {
        var s = Math.min(1, 2200 / Math.max(img.width, img.height)), c = document.createElement('canvas');
        c.width = Math.round(img.width * s); c.height = Math.round(img.height * s);
        var g = c.getContext('2d'); g.fillStyle = '#fff'; g.fillRect(0, 0, c.width, c.height); g.drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url); ok(c.toDataURL('image/jpeg', 0.86));
      };
      img.onerror = function () { URL.revokeObjectURL(url); gagal(new Error('Foto tidak bisa dibuka')); };
      img.src = url;
    });
  }
  function gambarFoto() {
    pf.innerHTML = '';
    foto.forEach(function (f, i) {
      var w = el('span', 'mini'), im = document.createElement('img'), x = el('button', null, '×');
      im.src = f; x.type = 'button'; x.onclick = function () { foto.splice(i, 1); gambarFoto(); }; w.append(im, x); pf.append(w);
    });
  }
  $$('.ambil-foto-jadwal', form).forEach(function (inp) {
    inp.addEventListener('change', function () {
      var b = Array.prototype.slice.call(inp.files, 0, 4 - foto.length); inp.value = '';
      Promise.all(b.map(kecilkan)).then(function (f) { foto = foto.concat(f); gambarFoto(); });
    });
  });
  document.getElementById('tombol-baca-jadwal').addEventListener('click', function () {
    if (!foto.length) { st.textContent = 'Ambil foto jadwal dulu.'; return; }
    var btn = this, mulai = Date.now(); btn.disabled = true;
    var jam = setInterval(function () { st.textContent = '⏳ AI sedang membaca jadwal… (' + Math.round((Date.now() - mulai) / 1000) + ' detik)'; }, 1000);
    var fd = new FormData(); fd.append('_t', form.dataset.token); fd.append('aksi', 'baca_jadwal'); fd.append('guru', document.getElementById('kode-guru').value);
    foto.forEach(function (f) { fd.append('foto[]', f); });
    fetch('?p=api_ai', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).catch(function () { return { ok: false, galat: 'Tidak tersambung ke server.' }; })
      .then(function (r) {
        clearInterval(jam); btn.disabled = false;
        if (!r.ok) { st.textContent = '⚠ ' + (r.galat || 'Gagal membaca.'); return; }
        var n = 0;
        (r.jadwal || []).forEach(function (j) {
          var h = HARI[String(j.hari).toLowerCase().trim()] || +j.hari;
          var sel = form.querySelector('select[name="kelas[' + h + '][' + (+j.jam_ke) + ']"]');
          if (!sel || !j.kelas) return;
          aturKelas(sel, j.kelas);
          var m = form.querySelector('input[name="mapel[' + h + '][' + (+j.jam_ke) + ']"]'), u = form.elements.mapel_utama.value.trim().toLowerCase();
          if (m) m.value = j.mapel && j.mapel.toLowerCase().indexOf(u || '\u0000') < 0 && u.indexOf(j.mapel.toLowerCase()) < 0 ? j.mapel : '';
          n++;
        });
        hitung();
        st.textContent = n ? '✅ ' + n + ' jam pelajaran terisi (' + r.oleh + '). Periksa tiap hari, lalu tekan Simpan jadwal.' : 'AI tidak menemukan jam untuk guru itu. Periksa nama/kode guru, atau isi manual.';
      });
  });
})();

/* Jam pelajaran saya: per hari, tambah/kurangi baris, salin dari hari sebelumnya */
(function () {
  'use strict';
  var form = document.getElementById('form-jam');
  if (!form) return;
  function tambahMenit(w, m) { var p = String(w || '07:30').split(':'), t = (+p[0]) * 60 + (+p[1]) + m; return ('0' + Math.floor(t / 60) % 24).slice(-2) + ':' + ('0' + t % 60).slice(-2); }
  var JAM24 = 'class="jam24" inputmode="numeric" maxlength="5" placeholder="07:30" autocomplete="off"';
  function bagian(h) { return form.querySelector('.daftar-jam[data-hari="' + h + '"]'); }
  function baris(fs) { return fs.querySelectorAll('.baris-waktu'); }
  function hitung() {
    Array.prototype.forEach.call(form.querySelectorAll('.tab-jam button'), function (b) { b.querySelector('.jml-jam').textContent = baris(bagian(b.dataset.hari)).length || 'libur'; });
  }
  function barisBaru(fs, mulai, selesai) {
    var h = fs.dataset.hari, n = baris(fs).length + 1, d = document.createElement('div');
    d.className = 'baris-waktu';
    d.innerHTML = '<b>' + n + '</b><input type="text" ' + JAM24 + ' name="mulai[' + h + '][]" required><span>–</span><input type="text" ' + JAM24 + ' name="selesai[' + h + '][]" required>';
    d.children[1].value = mulai; d.children[3].value = selesai;
    fs.insertBefore(d, fs.querySelector('.dua-tombol'));
  }
  Array.prototype.forEach.call(form.querySelectorAll('.tab-jam button'), function (b) {
    b.addEventListener('click', function () {
      Array.prototype.forEach.call(form.querySelectorAll('.tab-jam button'), function (x) { x.classList.toggle('aktif', x === b); });
      Array.prototype.forEach.call(form.querySelectorAll('.daftar-jam'), function (f) { f.hidden = f.dataset.hari !== b.dataset.hari; });
    });
  });
  Array.prototype.forEach.call(form.querySelectorAll('.daftar-jam'), function (fs) {
    fs.querySelector('.tambah-jam').addEventListener('click', function () {
      var r = baris(fs), akhir = r[r.length - 1], mulai = akhir ? akhir.querySelector('[name^=selesai]').value : '07:30';
      barisBaru(fs, mulai, tambahMenit(mulai, 40)); hitung();
    });
    fs.querySelector('.kurang-jam').addEventListener('click', function () {
      var r = baris(fs); if (r.length) r[r.length - 1].remove(); hitung();
    });
    var salin = fs.querySelector('.salin-jam');
    if (salin) salin.addEventListener('click', function () {
      var asal = bagian(+fs.dataset.hari - 1);
      Array.prototype.forEach.call(baris(fs), function (r) { r.remove(); });
      Array.prototype.forEach.call(baris(asal), function (r) { barisBaru(fs, r.children[1].value, r.children[3].value); });
      hitung();
    });
  });
  hitung();
})();

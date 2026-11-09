/* SiGuru · tambah siswa: foto daftar (AI), tempel nama, impor Excel/CSV → pratinjau → simpan */
(function () {
  'use strict';
  var form = document.getElementById('form-siswa');
  if (!form) return;
  function el(tag, kelas, teks) { var e = document.createElement(tag); if (kelas) e.className = kelas; if (teks != null) e.textContent = teks; return e; }
  function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
  var token = form.dataset.token, st = document.getElementById('status-siswa');
  var pilih = document.getElementById('kelas-tujuan'), kotakBaru = document.getElementById('kotak-kelas-baru'), kelasBaru = document.getElementById('kelas-baru');
  var prat = document.getElementById('pratinjau-siswa'), tbody = prat.querySelector('tbody');
  var foto = [];

  pilih.addEventListener('change', function () { kotakBaru.hidden = pilih.value !== '__baru'; if (!kotakBaru.hidden) kelasBaru.focus(); });
  function kelasTujuan() { return pilih.value === '__baru' ? kelasBaru.value.trim() : pilih.value; }

  $$('.tab-isi button', form).forEach(function (b) {
    b.addEventListener('click', function () {
      $$('.tab-isi button', form).forEach(function (x) { x.classList.toggle('aktif', x === b); });
      $$('section[data-isi]', form).forEach(function (s) { s.hidden = s.dataset.isi !== b.dataset.tab; });
    });
  });

  function rapikanKelas(k) {
    k = String(k || '').trim().replace(/\s+/g, ' ');
    var m = k.match(/^(?:kelas\s*)?(7|8|9|vii|viii|ix)\s*[-.\s]?\s*([a-z])?\s*$/i);
    if (!m) return k;
    var r = { '7': 'VII', '8': 'VIII', '9': 'IX' }[m[1]] || m[1].toUpperCase();
    return r + (m[2] ? ' ' + m[2].toUpperCase() : '');
  }
  function kirim(data) {
    var fd = new FormData(); fd.append('_t', token);
    Object.keys(data).forEach(function (k) { if (Array.isArray(data[k])) data[k].forEach(function (x) { fd.append(k + '[]', x); }); else fd.append(k, data[k]); });
    return fetch('?p=api_ai', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.text(); })
      .then(function (t) { try { return JSON.parse(t); } catch (e) { return { ok: false, galat: 'Server sibuk atau waktu habis. Coba lagi.' }; } })
      .catch(function () { return { ok: false, galat: 'Tidak tersambung ke server.' }; });
  }
  function galat(el0, r) { el0.innerHTML = ''; el0.append(el('div', 'pesan galat', r.galat || 'Gagal.')); (r.rincian || []).forEach(function (x) { el0.append(el('div', 'redup kecil', x)); }); }

  /* ---------- pratinjau ---------- */
  function tampilkan(baris, kelasDariSumber) {
    if (!baris.length) { galat(st, { galat: 'Tidak ada nama yang terbaca.' }); return; }
    tbody.innerHTML = '';
    var tujuan = kelasTujuan();
    baris.forEach(function (b, i) {
      var tr = el('tr', b.ragu ? 'ragu' : ''), cb = document.createElement('input');
      cb.type = 'checkbox'; cb.checked = true; cb.className = 'pakai';
      function inp(nama, nilai, lebar) { var x = document.createElement('input'); x.name = nama; x.value = nilai || ''; if (lebar) x.style.width = lebar; return x; }
      var jk = document.createElement('select'); jk.name = 'jk';
      ['', 'L', 'P'].forEach(function (v) { var o = el('option', null, v || '–'); o.value = v; if ((b.jk || '').toUpperCase() === v) o.selected = true; jk.append(o); });
      var td = function (x) { var t = el('td'); t.append(x); return t; };
      tr.append(td(cb), td(inp('absen', b.absen || String(i + 1), '2.6rem')), td(inp('nama', b.nama)), td(jk), td(inp('nisn', b.nisn, '7.5rem')),
        td(inp('kelas', (kelasDariSumber && b.kelas) ? b.kelas : tujuan, '5rem')));
      tbody.append(tr);
    });
    prat.hidden = false;
    st.textContent = '✅ ' + baris.length + ' nama terbaca. Periksa di bawah lalu simpan.';
    prat.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  /* ---------- tempel ---------- */
  function uraiBaris(t) {
    var b = { nama: '', nisn: '', jk: '', absen: '', kelas: '' };
    t = t.replace(/ /g, ' ').trim();
    var m = t.match(/^(\d{1,3})[.)\s]\s*/); if (m) { b.absen = m[1]; t = t.slice(m[0].length); }
    var bagian = t.split(/\t|;|,|\|/).map(function (x) { return x.trim(); }).filter(Boolean);
    var JUDUL = /^(no\.?|nomor|nama( siswa| lengkap| peserta didik)?|l\/p|jk|jenis kelamin|nisn|nis|kelas|rombel|ket(erangan)?)$/i;
    if (bagian.length && bagian.every(function (x) { return x.split(/\s+/).every(function (y) { return JUDUL.test(y) || /^(siswa|lengkap)$/i.test(y); }); })) return b;
    var nama = [];
    bagian.forEach(function (x) {
      if (/^\d{8,12}$/.test(x)) b.nisn = x;
      else if (/^(l|p|laki-laki|perempuan)$/i.test(x)) b.jk = x.charAt(0).toUpperCase();
      else if (/^(kelas\s*)?(vii|viii|ix|7|8|9)\s*-?\s*[a-z]?$/i.test(x) && nama.length) b.kelas = x;
      else nama.push(x);
    });
    // "Ahmad Pratama L" → jk di akhir
    var n = nama.join(' ').replace(/\s+/g, ' ').trim(), j = n.match(/\s+(L|P)$/);
    if (j && !b.jk) { b.jk = j[1]; n = n.slice(0, -2).trim(); }
    var nisn2 = n.match(/\s+(\d{8,12})$/); if (nisn2 && !b.nisn) { b.nisn = nisn2[1]; n = n.slice(0, -nisn2[0].length).trim(); }
    b.nama = n;
    return b;
  }
  document.getElementById('tombol-tempel').addEventListener('click', function () {
    var baris = document.getElementById('teks-siswa').value.split(/\r?\n/).map(uraiBaris)
      .filter(function (b) { return b.nama.length > 1 && !/^(no|nama|nama siswa|nama lengkap)$/i.test(b.nama); });
    tampilkan(baris, true);
  });

  /* ---------- foto ---------- */
  var pf = document.getElementById('foto-siswa');
  function kecilkan(berkas) {
    return new Promise(function (ok, gagal) {
      var url = URL.createObjectURL(berkas), img = new Image();
      img.onload = function () {
        var s = Math.min(1, 2000 / Math.max(img.width, img.height)), c = document.createElement('canvas');
        c.width = Math.round(img.width * s); c.height = Math.round(img.height * s);
        var g = c.getContext('2d'); g.fillStyle = '#fff'; g.fillRect(0, 0, c.width, c.height); g.drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url); ok(c.toDataURL('image/jpeg', 0.85));
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
  $$('.ambil-foto-siswa', form).forEach(function (inp) {
    inp.addEventListener('change', function () {
      var b = Array.prototype.slice.call(inp.files, 0, 4 - foto.length); inp.value = '';
      Promise.all(b.map(kecilkan)).then(function (f) { foto = foto.concat(f); gambarFoto(); });
    });
  });
  document.getElementById('tombol-baca-foto').addEventListener('click', function () {
    if (!foto.length) { galat(st, { galat: 'Ambil foto daftar siswa dulu.' }); return; }
    var btn = this, mulai = Date.now(); btn.disabled = true;
    var jam = setInterval(function () { st.textContent = '⏳ AI sedang membaca daftar nama… (' + Math.round((Date.now() - mulai) / 1000) + ' detik)'; }, 1000);
    kirim({ aksi: 'baca_daftar', foto: foto }).then(function (r) {
      clearInterval(jam); btn.disabled = false;
      if (!r.ok) { galat(st, r); return; }
      if (r.kelas && pilih.value === '__baru' && !kelasBaru.value) kelasBaru.value = r.kelas;
      tampilkan(r.siswa, false);
    });
  });

  /* ---------- berkas ---------- */
  document.getElementById('berkas-siswa').addEventListener('change', function () {
    var f = this.files[0]; this.value = '';
    if (!f) return;
    st.textContent = '⏳ Membaca ' + f.name + '…';
    var fd = new FormData(); fd.append('_t', token); fd.append('aksi', 'baca_berkas'); fd.append('berkas', f);
    fetch('?p=api_ai', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
      if (!r.ok) { galat(st, r); return; }
      tampilkan(r.siswa, document.getElementById('pakai-kolom-kelas').checked);
    }).catch(function () { galat(st, { galat: 'Berkas gagal dikirim.' }); });
  });

  /* ---------- simpan ---------- */
  document.getElementById('tombol-simpan-siswa').addEventListener('click', function () {
    var ss = document.getElementById('status-simpan-siswa'), rapikan = document.getElementById('rapikan-kelas').checked, baris = [];
    var cadangan = kelasTujuan();
    $$('tr', tbody).forEach(function (tr) {
      if (!tr.querySelector('.pakai').checked) return;
      var g = function (n) { return tr.querySelector('[name="' + n + '"]').value.trim(); };
      var k = g('kelas') || cadangan;
      baris.push({ nama: g('nama'), jk: g('jk'), nisn: g('nisn'), absen: g('absen'), kelas: rapikan ? rapikanKelas(k) : k });
    });
    if (!baris.length) { galat(ss, { galat: 'Tidak ada baris yang dicentang.' }); return; }
    if (baris.some(function (b) { return !b.kelas; })) { galat(ss, { galat: 'Isi kelas tujuan dulu (di atas) atau di kolom Kelas.' }); return; }
    var btn = this; btn.disabled = true; ss.textContent = '⏳ Menyimpan…';
    kirim({ aksi: 'simpan_siswa', data: JSON.stringify(baris) }).then(function (r) {
      btn.disabled = false;
      if (!r.ok) { galat(ss, r); return; }
      ss.innerHTML = '';
      ss.append(el('div', 'pesan ok', '✅ ' + r.baru + ' siswa baru, ' + r.diperbarui + ' diperbarui' + (r.dilewati ? ', ' + r.dilewati + ' dilewati (sudah ada/kosong)' : '') + '.'));
      var a = el('a', 'tombol utama lebar', 'Lihat kelas ' + r.kelas); a.href = '?p=siswa&kelas=' + encodeURIComponent(r.kelas); ss.append(a);
    });
  });
})();

/* SiGuru · Nilai otomatis dari foto lembar jawaban */
(function () {
  'use strict';
  var form = document.getElementById('form-koreksi');
  if (!form) return;
  function el(tag, kelas, teks) { var e = document.createElement(tag); if (kelas) e.className = kelas; if (teks != null) e.textContent = teks; return e; }
  function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }

  var token = form.dataset.token, pid = form.dataset.id;
  var pilihSiswa = document.getElementById('pilih-siswa');
  var tombol = document.getElementById('tombol-koreksi');
  var st = document.getElementById('status-koreksi');
  var kotak = document.getElementById('hasil-koreksi');
  var prat = document.getElementById('foto-lj');
  var siswa = [];
  try { siswa = JSON.parse(document.getElementById('data-siswa').textContent); } catch (e) { /* kosong */ }
  var foto = [], hasil = null;

  /* ---------- foto (resolusi lebih tinggi agar tanda tipis terbaca) ---------- */
  function kecilkan(berkas) {
    return new Promise(function (ok, gagal) {
      var url = URL.createObjectURL(berkas), img = new Image();
      img.onload = function () {
        var s = Math.min(1, 2000 / Math.max(img.width, img.height)), c = document.createElement('canvas');
        c.width = Math.round(img.width * s); c.height = Math.round(img.height * s);
        var g = c.getContext('2d'); g.fillStyle = '#fff'; g.fillRect(0, 0, c.width, c.height); g.drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url); ok(c.toDataURL('image/jpeg', 0.86));
      };
      img.onerror = function () { URL.revokeObjectURL(url); gagal(new Error('Foto tidak bisa dibuka')); };
      img.src = url;
    });
  }
  function gambarFoto() {
    prat.innerHTML = '';
    foto.forEach(function (f, i) {
      var w = el('span', 'mini'), im = document.createElement('img'), x = el('button', null, '×');
      im.src = f; x.type = 'button'; x.title = 'Hapus foto'; x.onclick = function () { foto.splice(i, 1); gambarFoto(); };
      w.append(im, x); prat.append(w);
    });
    if (foto.length) prat.append(el('small', 'redup', foto.length + ' halaman'));
  }
  $$('.ambil-foto-lj', form).forEach(function (inp) {
    inp.addEventListener('change', function () {
      var b = Array.prototype.slice.call(inp.files, 0, 4 - foto.length); inp.value = '';
      Promise.all(b.map(kecilkan)).then(function (f) { foto = foto.concat(f); gambarFoto(); });
    });
  });

  function kirim(data) {
    var fd = new FormData();
    fd.append('_t', token);
    Object.keys(data).forEach(function (k) {
      if (Array.isArray(data[k])) data[k].forEach(function (x) { fd.append(k + '[]', x); }); else fd.append(k, data[k]);
    });
    return fetch('?p=api_ai', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) { return r.text(); })
      .then(function (t) { try { return JSON.parse(t); } catch (e) { return { ok: false, galat: t.indexOf('Sesi formulir') >= 0 ? 'Sesi kedaluwarsa, muat ulang halaman.' : 'Server sibuk atau waktu habis. Coba lagi.' }; } })
      .catch(function () { return { ok: false, galat: 'Tidak tersambung ke server.' }; });
  }
  function galat(r) {
    st.innerHTML = '';
    st.append(el('div', 'pesan galat', r.galat || 'Gagal.'));
    (r.rincian || []).forEach(function (x) { st.append(el('div', 'redup kecil', x)); });
  }

  /* ---------- kirim untuk dinilai ---------- */
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (!foto.length) { galat({ galat: 'Ambil foto lembar jawaban dulu.' }); return; }
    tombol.disabled = true; kotak.hidden = true;
    var dua = form.elements.dua_kali.checked, mulai = Date.now();
    var teks = dua ? '⏳ Dua AI sedang membaca lembar jawaban…' : '⏳ AI sedang membaca lembar jawaban…';
    st.textContent = teks;
    var jam = setInterval(function () { st.textContent = teks + ' (' + Math.round((Date.now() - mulai) / 1000) + ' detik)'; }, 1000);
    kirim({ aksi: 'koreksi', id: pid, member_id: pilihSiswa.value, dua_kali: dua ? '1' : '0', foto: foto }).then(function (r) {
      clearInterval(jam); tombol.disabled = false;
      if (!r.ok) { galat(r); return; }
      st.textContent = '';
      if (!pilihSiswa.value && r.saran_siswa) pilihSiswa.value = r.saran_siswa;
      hasil = r;
      tampil();
    });
  });

  /* ---------- tampilkan hasil ---------- */
  var adaE = false;
  function hurufPilihan(jenis) { return jenis === 'bs' ? ['B', 'S'] : (adaE ? ['A', 'B', 'C', 'D', 'E'] : ['A', 'B', 'C', 'D']); }
  function skorBaris(s) {
    if (s.jenis === 'pg' || s.jenis === 'bs') return s.jawaban && s.jawaban === s.kunci ? Number(s.maks) : 0;
    return Number(s.skor) || 0;
  }
  function hitung() {
    var skor = 0, maks = 0;
    hasil.soal.forEach(function (s) { skor += skorBaris(s); maks += Number(s.maks) || 0; });
    return { skor: skor, maks: maks, nilai: maks ? Math.round(skor / maks * 100) : 0 };
  }
  var elTotal, elSimpan, elRagu, hanyaRagu = false, peringatan = false;

  function perbaruiTotal() {
    var t = hitung(), sisa = hasil.soal.filter(function (s) { return s.ragu && !s.diperiksa; }).length;
    elTotal.innerHTML = '';
    elTotal.append(el('b', 'nilai-besar', String(t.nilai)), el('span', null, ' skor ' + (Math.round(t.skor * 10) / 10) + '/' + t.maks));
    elRagu.textContent = sisa ? '⚠ ' + sisa + ' nomor kuning perlu diperiksa' : '✓ Semua nomor sudah jelas';
    elRagu.className = sisa ? 'ragu-info' : 'ragu-info beres';
    elSimpan.textContent = '💾 Simpan nilai ' + t.nilai;
    peringatan = false;
  }

  function barisPG(s) {
    var tr = el('tr', 'baris-k' + (s.ragu ? ' ragu' : ''));
    var tdNo = el('td', 'no', s.no), tdJ = el('td'), tdK = el('td', 'kunci-k', s.kunci), tdH = el('td', 'tanda-k');
    var grup = el('div', 'pilih-huruf');
    function tandai() {
      $$('button', grup).forEach(function (b) { b.classList.toggle('aktif', b.dataset.h === (s.jawaban || '-')); });
      var benar = s.jawaban && s.jawaban === s.kunci;
      tdH.textContent = benar ? '✓' : '✗';
      tr.classList.toggle('benar', !!benar); tr.classList.toggle('salah', !benar);
    }
    hurufPilihan(s.jenis).concat(['-']).forEach(function (h) {
      var b = el('button', null, h === '-' ? '–' : h); b.type = 'button'; b.dataset.h = h;
      b.title = h === '-' ? 'Tidak dijawab' : 'Jawaban ' + h;
      b.onclick = function () {
        s.jawaban = h === '-' ? '' : h; s.diperiksa = true; tr.classList.remove('ragu'); tr.classList.add('diperiksa');
        tandai(); perbaruiTotal(); terapkanSaring();
      };
      grup.append(b);
    });
    if (s.jawaban && s.jawaban.indexOf(',') >= 0) grup.append(el('small', 'ganda', 'ditandai: ' + s.jawaban));
    tdJ.append(grup);
    if (s.catatan) tdJ.append(el('small', 'catatan-k', s.catatan));
    if (s.ragu) {
      var ok = el('button', 'kecil ok-k', 'Sudah benar'); ok.type = 'button';
      ok.onclick = function () { s.diperiksa = true; tr.classList.remove('ragu'); tr.classList.add('diperiksa'); ok.remove(); perbaruiTotal(); terapkanSaring(); };
      tdJ.append(ok);
    }
    tandai();
    tr.append(tdNo, tdJ, tdK, tdH);
    return tr;
  }

  function barisUraian(s) {
    var tr = el('tr', 'baris-k uraian' + (s.ragu ? ' ragu' : ''));
    var tdJ = el('td'); tdJ.colSpan = 3;
    tdJ.append(el('small', 'redup', (s.jenis === 'isian' ? 'Isian' : s.jenis === 'pendapat' ? 'Pendapat' : 'Uraian') + ' · jawaban siswa:'));
    tdJ.append(el('div', 'jawaban-uraian', s.jawaban || '(kosong)'));
    if (s.catatan) tdJ.append(el('small', 'catatan-k', s.catatan));
    var tdS = el('div', 'skor-k'), inp = document.createElement('input');
    inp.type = 'number'; inp.min = 0; inp.max = s.maks; inp.step = 0.5; inp.value = s.skor || 0; inp.inputMode = 'decimal';
    inp.oninput = function () {
      var v = Math.max(0, Math.min(Number(s.maks), Number(inp.value.replace(',', '.')) || 0));
      s.skor = v; s.diperiksa = true; tr.classList.remove('ragu'); tr.classList.add('diperiksa'); perbaruiTotal();
    };
    tdS.append(el('span', null, 'Skor '), inp, el('small', null, ' / ' + s.maks));
    tdJ.append(tdS);
    tr.append(el('td', 'no', s.no), tdJ);
    return tr;
  }

  function terapkanSaring() {
    $$('tr.baris-k', kotak).forEach(function (tr) { tr.hidden = hanyaRagu && !tr.classList.contains('ragu'); });
  }

  function tampil() {
    kotak.innerHTML = ''; kotak.hidden = false; hanyaRagu = false;
    adaE = hasil.soal.some(function (s) { return s.jenis === 'pg' && (s.kunci === 'E' || String(s.jawaban).indexOf('E') >= 0); });
    var nm = siswa.filter(function (x) { return x.id === pilihSiswa.value; })[0];
    var kepala = el('div', 'kepala-k');
    var judul = el('h3', null, nm ? nm.nama : 'Siswa belum dipilih');
    kepala.append(judul);
    if (hasil.nama) kepala.append(el('small', 'redup', 'Nama di lembar: "' + hasil.nama + '"' + (hasil.saran_siswa && pilihSiswa.value === hasil.saran_siswa ? ' → dicocokkan otomatis, periksa.' : '')));
    if (!nm) kepala.append(el('div', 'pesan galat', 'Pilih nama siswa di kolom "Siswa" di atas sebelum menyimpan.'));
    kepala.append(el('small', 'redup', 'Dibaca oleh ' + hasil.oleh + (hasil.dua_kali ? ' (dua kali)' : '')));
    if (hasil.info) kepala.append(el('div', 'pesan', hasil.info));
    kotak.append(kepala);

    elTotal = el('div', 'total-k'); elRagu = el('div', 'ragu-info');
    var saring = el('button', 'kecil', 'Tampilkan hanya yang kuning'); saring.type = 'button';
    saring.onclick = function () { hanyaRagu = !hanyaRagu; saring.textContent = hanyaRagu ? 'Tampilkan semua' : 'Tampilkan hanya yang kuning'; terapkanSaring(); };
    var ring = el('div', 'ringkas-k'); ring.append(elTotal, el('div', null), elRagu, saring);
    kotak.append(ring);

    var tb = el('table', 'tabel-koreksi isi-k'), th = el('tr');
    ['No', 'Jawaban siswa', 'Kunci', ''].forEach(function (x) { th.append(el('th', null, x)); });
    tb.append(th);
    hasil.soal.forEach(function (s) { tb.append(s.jenis === 'pg' || s.jenis === 'bs' ? barisPG(s) : barisUraian(s)); });
    kotak.append(tb);

    var lk = el('label', null, 'Komentar untuk siswa'), ta = document.createElement('textarea');
    ta.rows = 2; ta.value = hasil.komentar || ''; ta.oninput = function () { hasil.komentar = ta.value; };
    lk.append(ta); kotak.append(lk);

    elSimpan = el('button', 'utama lebar'); elSimpan.type = 'button';
    elSimpan.onclick = simpan;
    var stS = el('div', 'status-ai'); stS.id = 'status-simpan';
    kotak.append(elSimpan, stS);
    perbaruiTotal();
    kotak.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function simpan() {
    var stS = document.getElementById('status-simpan');
    if (!pilihSiswa.value) { stS.innerHTML = ''; stS.append(el('div', 'pesan galat', 'Pilih nama siswa dulu (kolom "Siswa" di atas).')); pilihSiswa.focus(); return; }
    var sisa = hasil.soal.filter(function (s) { return s.ragu && !s.diperiksa; }).length;
    if (sisa && !peringatan) {
      peringatan = true;
      stS.innerHTML = ''; stS.append(el('div', 'pesan', 'Masih ada ' + sisa + ' nomor kuning belum diperiksa. Tekan Simpan sekali lagi untuk tetap menyimpan.'));
      return;
    }
    elSimpan.disabled = true; stS.textContent = '⏳ Menyimpan…';
    var data = { soal: hasil.soal.map(function (s) { return { no: s.no, jawaban: s.jawaban, skor: s.skor, skor_ai: s.skor_ai, ragu: s.ragu && !s.diperiksa, catatan: s.catatan }; }), komentar: hasil.komentar || '', oleh: hasil.oleh };
    kirim({ aksi: 'koreksi_simpan', id: pid, member_id: pilihSiswa.value, data: JSON.stringify(data) }).then(function (r) {
      elSimpan.disabled = false;
      if (!r.ok) { stS.innerHTML = ''; stS.append(el('div', 'pesan galat', r.galat || 'Gagal menyimpan.')); return; }
      var opt = pilihSiswa.options[pilihSiswa.selectedIndex], nama = opt.textContent.replace(/ ✓ .*$/, '');
      opt.textContent = nama + ' ✓ ' + r.nilai; opt.dataset.sudah = '1';
      st.innerHTML = ''; st.append(el('div', 'pesan ok', '✅ Nilai ' + nama.replace(/^\d+\.\s*/, '') + ' tersimpan: ' + r.nilai + '. Lanjut siswa berikutnya.'));
      // siapkan siswa berikutnya
      foto = []; gambarFoto(); kotak.hidden = true; kotak.innerHTML = ''; hasil = null;
      var i = pilihSiswa.selectedIndex, n = pilihSiswa.options.length, j;
      for (j = 1; j < n; j++) { var o = pilihSiswa.options[(i + j) % n]; if (o.value && !o.dataset.sudah) { pilihSiswa.value = o.value; break; } }
      form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }
})();

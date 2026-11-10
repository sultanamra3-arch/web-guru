/* SiGuru · lembar ujian siswa: simpan otomatis, tahan sinyal putus, waktu, navigasi, catat keluar layar */
(function () {
  'use strict';
  var dataEl = document.getElementById('data-ujian');
  if (!dataEl) return;
  var D = JSON.parse(dataEl.textContent);
  function $(id) { return document.getElementById(id); }
  function el(tag, kelas, teks) { var e = document.createElement(tag); if (kelas) e.className = kelas; if (teks != null) e.textContent = teks; return e; }

  var soal = D.soal, jw = D.jawaban || {}, rg = D.ragu || {};
  var pos = Math.max(0, Math.min(soal.length - 1, soal.findIndex(function (s) { return true; }) + 0));
  var KUNCI = 'sgu-tunda-' + D.id + '-' + D.nama;
  var tunda = { jawab: {}, ragu: {} }, mengirim = false, gagal = 0, sisa = D.sisa, selesaiDikirim = false, putus = false;
  // kunci lanjut: dipakai menyambung lagi bila sesi di HP hilang (juga dibaca halaman pilih nama)
  var KUNCI_LANJUT = 'sgu-kunci-' + D.id;
  try { if (D.kunci) localStorage.setItem(KUNCI_LANJUT, D.kunci); } catch (e) { /* abaikan */ }
  var HURUF = ['A', 'B', 'C', 'D', 'E'];

  // posisi awal = nomor terakhir yang dibuka (disimpan server)
  if (typeof D.nomor === 'number') pos = Math.max(0, Math.min(soal.length - 1, D.nomor));

  // pulihkan jawaban yang belum terkirim sebelum HP mati/halaman ditutup
  try {
    var lama = JSON.parse(localStorage.getItem(KUNCI) || 'null');
    if (lama && lama.jawab) {
      Object.keys(lama.jawab).forEach(function (i) { jw[i] = lama.jawab[i]; tunda.jawab[i] = lama.jawab[i]; });
      Object.keys(lama.ragu || {}).forEach(function (i) { if (lama.ragu[i]) rg[i] = 1; else delete rg[i]; tunda.ragu[i] = lama.ragu[i]; });
    }
  } catch (e) { /* penyimpanan tidak tersedia */ }
  function simpanLokal() { try { localStorage.setItem(KUNCI, JSON.stringify(tunda)); } catch (e) { /* abaikan */ } }

  var stSimpan = $('status-simpan');
  function status(teks, jenis) { stSimpan.textContent = teks; stSimpan.className = 'ujian-status ' + (jenis || ''); }

  /* ---------- kirim ke server ---------- */
  var timerKirim = null;
  function jadwalKirim(jeda) { clearTimeout(timerKirim); timerKirim = setTimeout(kirim, jeda == null ? 350 : jeda); }
  function kirim(aksi) {
    if (mengirim) {
      if (!aksi) { jadwalKirim(600); return Promise.resolve(null); }
      // aksi (keluar/selesai/detak) jangan sampai hilang: coba lagi setelah kiriman berjalan selesai
      return new Promise(function (ok) { setTimeout(function () { ok(kirim(aksi)); }, 700); });
    }
    var paket = { jawab: tunda.jawab, ragu: tunda.ragu };
    var adaIsi = Object.keys(paket.jawab).length || Object.keys(paket.ragu).length;
    if (!adaIsi && !aksi) return Promise.resolve(null);
    tunda = { jawab: {}, ragu: {} };
    mengirim = true;
    if (adaIsi) status('⏳ Menyimpan…', 'proses');
    var fd = new FormData();
    fd.append('_t', D.token); fd.append('id', D.id); fd.append('nomor', pos); fd.append('kunci', D.kunci || '');
    fd.append('jawab', JSON.stringify(paket.jawab)); fd.append('ragu', JSON.stringify(paket.ragu));
    if (aksi) fd.append('aksi', aksi);
    return fetch('?p=api_u', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (r) {
        // 400 = sesi di server sudah hilang → bukan masalah sinyal
        if (r.status === 400) return { ok: false, keluar: true, galat: 'Sesi berakhir. Pilih namamu lagi untuk melanjutkan — jawabanmu tetap tersimpan.' };
        return r.json();
      })
      .then(function (r) {
        mengirim = false; gagal = 0;
        if (r.token) D.token = r.token; // tersambung lagi memakai kunci lanjut
        if (r.ok && typeof r.sisa === 'number') sisa = r.sisa;
        if (r.selesai) { bersihLokal(); location.reload(); return r; }
        if (r.keluar) {
          // jawaban yang belum terkirim tetap disimpan di HP, dikirim lagi setelah masuk ulang
          Object.keys(paket.jawab).forEach(function (i) { if (!(i in tunda.jawab)) tunda.jawab[i] = paket.jawab[i]; });
          Object.keys(paket.ragu).forEach(function (i) { if (!(i in tunda.ragu)) tunda.ragu[i] = paket.ragu[i]; });
          simpanLokal(); putus = true;
          status('⚠ ' + (r.galat || 'Sesi berakhir.'), 'galat');
          setTimeout(masukLagi, 3500);
          return r;
        }
        if (!r.ok) throw new Error('gagal');
        if (!Object.keys(tunda.jawab).length && !Object.keys(tunda.ragu).length) { bersihLokal(); status('✓ Semua jawaban tersimpan', 'ok'); }
        else jadwalKirim(200);
        return r;
      })
      .catch(function () {
        mengirim = false; gagal++;
        // kembalikan paket ke antrean tanpa menimpa perubahan yang lebih baru
        Object.keys(paket.jawab).forEach(function (i) { if (!(i in tunda.jawab)) tunda.jawab[i] = paket.jawab[i]; });
        Object.keys(paket.ragu).forEach(function (i) { if (!(i in tunda.ragu)) tunda.ragu[i] = paket.ragu[i]; });
        simpanLokal();
        status('⚠ Belum tersimpan (sinyal?). Jawaban aman di HP, dikirim otomatis saat tersambung.', 'galat');
        jadwalKirim(Math.min(15000, 2000 * gagal));
        return null;
      });
  }
  function bersihLokal() { try { localStorage.removeItem(KUNCI); } catch (e) { /* abaikan */ } }
  function masukLagi() { simpanLokal(); location.href = '?p=u' + (D.kode_ujian ? '&k=' + encodeURIComponent(D.kode_ujian) : ''); }

  function catatJawab(i, v) {
    if (v === '' || v == null) delete jw[i]; else jw[i] = v;
    tunda.jawab[i] = v == null ? '' : v;
    simpanLokal(); gambarNomor();
  }

  /* ---------- tampilan ---------- */
  var elNomor = $('nomor'), elSoal = $('soal'), tSebelum = $('sebelum'), tSesudah = $('sesudah'), tRagu = $('ragu');
  function terjawab(s) { return jw[s.i] != null && String(jw[s.i]).trim() !== ''; }
  function gambarNomor() {
    elNomor.innerHTML = '';
    soal.forEach(function (s, n) {
      var b = el('button', (terjawab(s) ? 'dijawab ' : '') + (rg[s.i] ? 'ragu ' : '') + (n === pos ? 'kini' : ''), String(n + 1));
      b.type = 'button'; b.onclick = function () { ke(n); };
      elNomor.append(b);
    });
  }
  function teksBaris(induk, teks) {
    String(teks).split(/\n/).forEach(function (b, k) { if (k) induk.append(document.createElement('br')); induk.append(document.createTextNode(b)); });
  }
  function gambarSoal() {
    var s = soal[pos];
    elSoal.innerHTML = '';
    elSoal.append(el('div', 'ujian-no', 'Soal ' + (pos + 1) + ' dari ' + soal.length));
    var t = el('div', 'ujian-teks'); teksBaris(t, s.teks); elSoal.append(t);
    if (s.g) { var gs = document.createElement('img'); gs.src = s.g; gs.className = 'ujian-gambar'; gs.alt = ''; gs.onclick = function () { perbesar(s.g); }; elSoal.append(gs); }
    if (s.tipe === 'pg' || s.tipe === 'bs') {
      var ol = el('div', 'ujian-opsi');
      s.opsi.forEach(function (o, n) {
        var b = el('button', String(jw[s.i]) === o.k ? 'pilih' : ''); b.type = 'button';
        b.append(el('span', 'huruf', HURUF[n] || ''));
        var tt = el('span', 'isi'); teksBaris(tt, o.t);
        if (o.g) { var go = document.createElement('img'); go.src = o.g; go.className = 'ujian-gambar-opsi'; go.alt = ''; tt.append(go); }
        b.append(tt);
        b.onclick = function () { catatJawab(s.i, String(jw[s.i]) === o.k ? '' : o.k); gambarSoal(); jadwalKirim(); };
        ol.append(b);
      });
      elSoal.append(ol);
    } else {
      var inp = document.createElement(s.tipe === 'isian' ? 'input' : 'textarea');
      inp.className = 'ujian-isian'; inp.value = jw[s.i] || ''; inp.placeholder = 'Tulis jawabanmu di sini…';
      if (s.tipe !== 'isian') inp.rows = 8;
      inp.addEventListener('input', function () { catatJawab(s.i, inp.value); jadwalKirim(1200); });
      inp.addEventListener('blur', function () { jadwalKirim(0); });
      elSoal.append(inp);
    }
    tRagu.classList.toggle('aktif', !!rg[s.i]);
    tSebelum.disabled = pos === 0;
    tSesudah.textContent = pos === soal.length - 1 ? 'Selesai ✓' : 'Lanjut ›';
    gambarNomor();
    var kini = elNomor.querySelector('.kini'); if (kini && kini.scrollIntoView) kini.scrollIntoView({ block: 'nearest', inline: 'center' });
  }
  function perbesar(src) {
    var t = el('div', 'ujian-tirai gambar-besar'), im = document.createElement('img'); im.src = src; im.alt = '';
    t.append(im, el('small', null, 'ketuk untuk menutup')); t.onclick = function () { t.remove(); };
    document.body.append(t);
  }
  function ke(n) { pos = Math.max(0, Math.min(soal.length - 1, n)); gambarSoal(); window.scrollTo(0, 0); jadwalKirim(1500); }
  tSebelum.onclick = function () { ke(pos - 1); };
  tSesudah.onclick = function () { if (pos === soal.length - 1) tanyaSelesai(); else ke(pos + 1); };
  tRagu.onclick = function () {
    var i = soal[pos].i;
    if (rg[i]) delete rg[i]; else rg[i] = 1;
    tunda.ragu[i] = rg[i] ? 1 : 0; simpanLokal(); gambarSoal(); jadwalKirim();
  };

  /* ---------- selesai ---------- */
  function tanyaSelesai() {
    var j = soal.filter(terjawab).length, r = soal.filter(function (s) { return rg[s.i]; }).length;
    $('ringkas-selesai').textContent = 'Terjawab ' + j + ' dari ' + soal.length + ' soal' + (soal.length - j ? ', ' + (soal.length - j) + ' belum dijawab' : '') + (r ? ', ' + r + ' ragu-ragu' : '') + '. Setelah dikumpulkan jawaban tidak bisa diubah.';
    $('tirai-selesai').hidden = false;
  }
  $('batal-selesai').onclick = function () { $('tirai-selesai').hidden = true; };
  $('ya-selesai').onclick = function () { kumpulkan(); };
  function kumpulkan() {
    var b = $('ya-selesai');
    // sesi tidak bisa disambung lagi: jangan diam, arahkan masuk lagi (jawaban yang belum terkirim ikut dikirim)
    if (putus) { b.disabled = true; b.textContent = 'Masuk lagi untuk mengumpulkan…'; masukLagi(); return; }
    if (selesaiDikirim) return;
    selesaiDikirim = true;
    b.disabled = true; b.textContent = '⏳ Mengirim…';
    (function coba() {
      kirim('selesai').then(function (r) {
        if (r && (r.selesai || r.ok)) { bersihLokal(); try { localStorage.removeItem(KUNCI_LANJUT); } catch (e) { /* abaikan */ } location.reload(); return; }
        if (r && r.keluar) { b.textContent = 'Masuk lagi untuk mengumpulkan…'; return; }
        b.textContent = '⚠ Belum tersambung, mencoba lagi…';
        setTimeout(coba, 3000);
      });
    })();
  }

  /* ---------- waktu ---------- */
  var elWaktu = $('waktu'), peringatan5 = sisa <= 300;
  function detik(kurangi) {
    if (kurangi) sisa = Math.max(0, sisa - 1);
    var j = Math.floor(sisa / 3600), m = Math.floor(sisa % 3600 / 60), d = sisa % 60;
    elWaktu.textContent = (j ? j + ':' + ('0' + m).slice(-2) : m) + ':' + ('0' + d).slice(-2);
    elWaktu.classList.toggle('mepet', sisa <= 300);
    if (sisa <= 300 && sisa > 0 && !peringatan5 && !selesaiDikirim) {
      peringatan5 = true;
      var w = document.createElement('div'); w.className = 'peringatan-waktu'; w.setAttribute('role', 'alert');
      w.innerHTML = '<span><b>⏰ Waktu tinggal 5 menit lagi.</b><br>Periksa jawabanmu. Jawaban dikumpulkan otomatis saat waktu habis.</span>';
      var x = document.createElement('button'); x.type = 'button'; x.textContent = 'Oke'; x.onclick = function () { w.remove(); };
      w.append(x); document.body.append(w);
      if (navigator.vibrate) try { navigator.vibrate([200, 100, 200]); } catch (e) {}
      setTimeout(function () { w.remove(); }, 15000);
    }
    if (sisa === 0 && !selesaiDikirim && !putus) { $('tirai-selesai').hidden = true; status('⏰ Waktu habis. Jawaban dikumpulkan…', 'galat'); kumpulkan(); }
  }
  detik(false);
  setInterval(function () { detik(true); }, 1000);
  // detak: perbarui status online & sisa waktu
  setInterval(function () { if (!mengirim) kirim('detak'); }, 30000);

  /* ---------- keluar layar ---------- */
  var pernahSembunyi = false;
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') { pernahSembunyi = true; return; }
    if (pernahSembunyi && !selesaiDikirim && !putus) {
      pernahSembunyi = false;
      D.keluar = (D.keluar || 0) + 1;
      if (D.pengaman) {
        var sisaK = D.batas_keluar - D.keluar;
        $('judul-keluar').textContent = sisaK > 0 ? '⚠ Peringatan ' + D.keluar + ' dari ' + D.batas_keluar : '🔒 Batas keluar tercapai';
        $('teks-keluar').textContent = sisaK > 0
          ? 'Kamu keluar dari halaman ujian. Kalau keluar ' + sisaK + ' kali lagi, jawabanmu dikumpulkan otomatis.'
          : 'Jawabanmu sedang dikumpulkan otomatis…';
      }
      $('tirai-keluar').hidden = false;
      // jawaban yang belum terkirim ikut dikirim bersama catatan keluar
      kirim('keluar').then(function (r) { if (r && typeof r.keluar === 'number') D.keluar = r.keluar; });
    }
  });
  $('tutup-tirai').onclick = function () { $('tirai-keluar').hidden = true; };
  window.addEventListener('online', function () { jadwalKirim(0); });
  window.addEventListener('beforeunload', function (e) { if (Object.keys(tunda.jawab).length) { simpanLokal(); e.preventDefault(); e.returnValue = ''; } });

  gambarSoal();
  if (Object.keys(tunda.jawab).length || Object.keys(tunda.ragu).length) jadwalKirim(0);
  else status('✓ Semua jawaban tersimpan', 'ok');
})();

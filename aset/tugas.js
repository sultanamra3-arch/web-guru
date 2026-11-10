/* SiGuru · siswa mengirim tugas: foto dikecilkan di HP, pratinjau, kirim dengan coba ulang bila sinyal putus */
(function () {
  'use strict';
  function $(id) { return document.getElementById(id); }
  // hitung mundur batas
  var sisa = document.querySelector('.sisa-tugas');
  if (sisa && !sisa.classList.contains('lewat')) {
    var batas = new Date(sisa.dataset.batas).getTime(), lbl = sisa.querySelector('span');
    (function jalan() {
      var d = Math.floor((batas - Date.now()) / 1000);
      if (d <= 0) { lbl.textContent = 'Batas sudah lewat'; sisa.classList.add('lewat'); return; }
      var h = Math.floor(d / 86400), j = Math.floor(d % 86400 / 3600), m = Math.floor(d % 3600 / 60);
      lbl.textContent = 'Sisa waktu: ' + (h ? h + ' hari ' : '') + (h || j ? j + ' jam ' : '') + m + ' menit';
      setTimeout(jalan, 30000);
    })();
  }
  var K = $('kirim-tugas');
  if (!K) return;
  var maks0 = +K.dataset.maks, maks = maks0, foto = [], wadah = $('foto-pilih'), ambil = $('ambil-foto'), st = $('status-kirim'), tombol = $('tombol-kirim');
  function kecilkan(berkas) {
    return new Promise(function (ok, gagal) {
      var url = URL.createObjectURL(berkas), img = new Image();
      img.onload = function () {
        var s = Math.min(1, 1400 / Math.max(img.width, img.height)), c = document.createElement('canvas');
        c.width = Math.round(img.width * s); c.height = Math.round(img.height * s);
        var x = c.getContext('2d'); x.fillStyle = '#fff'; x.fillRect(0, 0, c.width, c.height); x.drawImage(img, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url); ok(c.toDataURL('image/jpeg', 0.75));
      };
      img.onerror = function () { URL.revokeObjectURL(url); gagal(new Error('Foto tidak bisa dibuka')); };
      img.src = url;
    });
  }
  // tugas kelompok: foto lama yang tetap dipakai mengurangi jatah foto baru
  var lama = Array.prototype.slice.call(document.querySelectorAll('.pakai-lama'));
  function lamaDipakai() { return lama.filter(function (c) { return c.checked; }).map(function (c) { return c.value; }); }
  function aturMaks() {
    maks = Math.max(0, maks0 - lamaDipakai().length);
    if (foto.length > maks) foto = foto.slice(0, maks);
    gambar();
  }
  lama.forEach(function (c) { c.addEventListener('change', function () { c.parentNode.classList.toggle('dibuang', !c.checked); aturMaks(); }); });
  function gambar() {
    Array.prototype.slice.call(wadah.querySelectorAll('.foto-mini')).forEach(function (e) { e.remove(); });
    foto.forEach(function (f, i) {
      var w = document.createElement('div'); w.className = 'foto-mini';
      var im = document.createElement('img'); im.src = f; im.alt = 'foto ' + (i + 1);
      var x = document.createElement('button'); x.type = 'button'; x.textContent = '✕'; x.setAttribute('aria-label', 'hapus foto ' + (i + 1));
      x.onclick = function () { foto.splice(i, 1); gambar(); };
      w.append(im, x); wadah.insertBefore(w, wadah.lastElementChild);
    });
    wadah.lastElementChild.hidden = foto.length >= maks;
  }
  ambil.addEventListener('change', function () {
    var b = Array.prototype.slice.call(ambil.files, 0, maks - foto.length); ambil.value = '';
    st.textContent = b.length ? '⏳ Menyiapkan foto…' : '';
    Promise.all(b.map(kecilkan)).then(function (f) { foto = foto.concat(f); gambar(); st.textContent = ''; }).catch(function (e) { st.textContent = '⚠ ' + e.message; });
  });
  var token = (document.querySelector('input[name=_t]') || {}).value || K.dataset.t;
  tombol.addEventListener('click', function () {
    var teks = $('teks-tugas').value.trim();
    if (!foto.length && !teks && !lamaDipakai().length) { st.textContent = '⚠ Tambahkan foto atau tulis jawaban dulu.'; return; }
    tombol.disabled = true;
    var coba = 0;
    (function kirim() {
      coba++;
      st.textContent = '⏳ Mengirim' + (coba > 1 ? ' (percobaan ' + coba + ')' : '') + '… jangan tutup halaman ini.';
      var fd = new FormData(); fd.append('_t', token); fd.append('id', K.dataset.id); fd.append('foto', JSON.stringify(foto)); fd.append('teks', teks); fd.append('lama', JSON.stringify(lamaDipakai()));
      fetch('?p=api_t', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
        if (!r.ok) { st.textContent = '⚠ ' + (r.galat || 'Gagal mengirim.'); tombol.disabled = false; return; }
        st.textContent = '✅ Terkirim pukul ' + r.jam + (r.telat ? ' (terlambat)' : '') + '.';
        setTimeout(function () { location.href = '?p=t&k=' + K.dataset.kode; }, 900);
      }).catch(function () {
        if (coba < 6) { st.textContent = '⚠ Sinyal bermasalah, mencoba lagi…'; setTimeout(kirim, Math.min(15000, 2500 * coba)); }
        else { st.textContent = '⚠ Belum terkirim. Periksa sinyal lalu tekan Kirim lagi (foto masih tersimpan di halaman ini).'; tombol.disabled = false; }
      });
    })();
  });
  if (lama.length) aturMaks();
})();

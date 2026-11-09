/* SiGuru · pantau ujian online (guru) */
(function () {
  'use strict';
  var kotak = document.getElementById('layar-token');
  if (!kotak) return;
  function el(tag, kelas, teks) { var e = document.createElement(tag); if (kelas) e.className = kelas; if (teks != null) e.textContent = teks; return e; }
  var id = kotak.dataset.id, url = kotak.dataset.url;
  var elToken = document.getElementById('token'), elSisa = document.getElementById('token-sisa');
  var daftar = document.getElementById('pantau'), ringkas = document.getElementById('pantau-ringkas'), waktu = document.getElementById('pantau-waktu');
  var tpl = document.getElementById('tpl-aksi');
  var sisaToken = 0, terbuka = {};

  // QR untuk link siswa
  try {
    var qr = qrcode(0, 'M'); qr.addData(url); qr.make();
    document.getElementById('qr').innerHTML = qr.createSvgTag({ cellSize: 6, margin: 2, scalable: true });
  } catch (e) { /* abaikan */ }

  document.getElementById('tombol-layar-penuh').addEventListener('click', function () {
    kotak.classList.toggle('proyektor');
    if (kotak.classList.contains('proyektor') && kotak.requestFullscreen) kotak.requestFullscreen().catch(function () {});
    else if (document.fullscreenElement) document.exitFullscreen();
  });
  document.addEventListener('fullscreenchange', function () { if (!document.fullscreenElement) kotak.classList.remove('proyektor'); });

  function mmss(d) { d = Math.max(0, d | 0); return Math.floor(d / 60) + ':' + ('0' + d % 60).slice(-2); }

  function gambar(r) {
    elToken.textContent = r.status === 'buka' ? r.token : '—';
    sisaToken = r.token_sisa;
    var n = { belum: 0, jalan: 0, selesai: 0, keluar: 0 };
    daftar.innerHTML = '';
    r.siswa.forEach(function (s) {
      n[s.st]++;
      if (s.keluar) n.keluar++;
      var li = el('li', 'pantau-' + s.st);
      var atas = el('div', 'pantau-atas');
      var nm = el('b', null, s.nama);
      if (s.st === 'jalan') nm.prepend(el('span', s.online ? 'titik hidup' : 'titik mati', '●'));
      atas.append(nm);
      var ket = el('small');
      if (s.st === 'belum') ket.textContent = 'belum masuk';
      else if (s.st === 'jalan') ket.textContent = 'mengerjakan · terjawab ' + s.terjawab + '/' + s.total + ' · no. ' + s.nomor + ' · sisa ' + mmss(s.sisa) + (s.online ? '' : ' · tidak tersambung');
      else ket.textContent = 'selesai · nilai ' + (s.nilai == null ? '-' : s.nilai) + (s.perlu ? ' (uraian belum dinilai)' : '');
      atas.append(ket);
      if (s.keluar) atas.append(el('small', 'peringatan-keluar', '⚠ keluar dari layar ujian ' + s.keluar + '×'));
      li.append(atas);
      if (s.st !== 'belum' && tpl) {
        var tb = el('button', 'kecil tombol-aksi', '⋯'); tb.type = 'button';
        var f = tpl.content.firstElementChild.cloneNode(true);
        f.querySelector('[name=kode]').value = s.kode;
        if (s.st === 'selesai') { f.querySelector('[value=tambah_waktu]').remove(); f.querySelector('[value=akhiri]').remove(); }
        f.hidden = !terbuka[s.kode];
        tb.onclick = function () { terbuka[s.kode] = f.hidden; f.hidden = !f.hidden; };
        li.append(tb, f);
      }
      daftar.append(li);
    });
    ringkas.innerHTML = '';
    [['belum', 'belum masuk'], ['jalan', 'mengerjakan'], ['selesai', 'selesai'], ['keluar', 'keluar layar']].forEach(function (x) {
      var s = el('span'); s.append(el('b', null, String(n[x[0]])), document.createTextNode(x[1])); ringkas.append(s);
    });
    waktu.textContent = '· diperbarui ' + r.jam;
  }

  function muat() {
    fetch('?p=api_ujian&aksi=pantau&id=' + id, { credentials: 'same-origin' }).then(function (r) { return r.json(); })
      .then(function (r) { if (r.ok) gambar(r); }).catch(function () { waktu.textContent = '· gagal memuat, mencoba lagi…'; });
  }
  muat();
  setInterval(muat, 8000);
  setInterval(function () {
    if (sisaToken > 0) { sisaToken--; elSisa.textContent = 'berganti dalam ' + mmss(sisaToken); if (sisaToken === 0) muat(); }
    else elSisa.textContent = '';
  }, 1000);
})();

/* Hasil ujian: nilai uraian semua siswa dengan AI, satu per satu */
(function () {
  'use strict';
  var kotak = document.getElementById('kotak-ai-uraian');
  if (!kotak) return;
  var btn = document.getElementById('tombol-ai-uraian'), st = document.getElementById('status-ai-uraian');
  var kode = JSON.parse(kotak.dataset.kode || '[]'), id = kotak.dataset.id;
  var t = document.querySelector('input[name=_t]').value;
  btn.addEventListener('click', function () {
    btn.disabled = true;
    var i = 0, gagal = 0;
    (function lanjut() {
      if (i >= kode.length) { st.textContent = '✅ Selesai' + (gagal ? ' (' + gagal + ' gagal, coba lagi nanti)' : '') + '. Memuat ulang…'; setTimeout(function () { location.reload(); }, 1200); return; }
      st.textContent = '⏳ Menilai ' + (i + 1) + ' dari ' + kode.length + '…';
      var fd = new FormData(); fd.append('_t', t); fd.append('aksi', 'nilai_ai'); fd.append('id', id); fd.append('kode', kode[i]);
      fetch('?p=api_ujian', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); })
        .then(function (r) { if (!r.ok) gagal++; }).catch(function () { gagal++; })
        .then(function () { i++; lanjut(); });
    })();
  });
})();

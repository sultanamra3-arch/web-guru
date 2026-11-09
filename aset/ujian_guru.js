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
    if (bagi) bagi.tokenBaru(r.token, r.token_sisa);
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
      else ket.textContent = (s.alasan === 'pengaman' ? '🔒 dikumpulkan otomatis (pengaman)' : 'selesai') + ' · nilai ' + (s.nilai == null ? '-' : s.nilai) + (s.perlu ? ' (uraian belum dinilai)' : '')
        + (s.percobaan > 1 ? ' · percobaan ke-' + s.percobaan + (s.terbaik != null ? ', terbaik ' + Math.max(s.terbaik, s.nilai || 0) : '') : '');
      if (s.alasan === 'pengaman') li.classList.add('pantau-dikunci');
      atas.append(ket);
      if (s.keluar) atas.append(el('small', 'peringatan-keluar', '⚠ keluar dari layar ujian ' + s.keluar + '×'));
      if (s.dikunci) atas.append(el('small', 'peringatan-keluar', '🔒 pernah dikunci pengaman ' + s.dikunci + '× lalu dibuka ulang'));
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

  /* ---------- Bagikan: tulisan + gambar kartu ujian ---------- */
  var bagi = (function () {
    var P = document.getElementById('panel-bagikan');
    if (!P) return null;
    var d = P.dataset, latihan = d.latihan === '1';
    var cb = document.getElementById('sertakan-token'), pilihToken = P.querySelector('.pilih-token');
    var cv = document.getElementById('kartu-bagikan'), prat = document.getElementById('pratinjau-pesan'), st = document.getElementById('status-bagikan');
    var tok = '', tokSisa = 0, menit = +d.tokenMenit, gambarSiap = null;
    var tokenEl = document.querySelector('input[name=_t]');
    function jenis() { var r = P.querySelector('input[name=jenis_token]:checked'); return r ? r.value : 'tetap'; }
    function sertakan() { return !latihan && cb && cb.checked; }
    function jamBerlaku() {
      var t = new Date(Date.now() + (tokSisa + menit * 60) * 1000);
      return ('0' + t.getHours()).slice(-2) + '.' + ('0' + t.getMinutes()).slice(-2);
    }
    function teks() {
      var b = ['📝 *' + d.judul + '*', 'Kelas ' + d.kelas + (d.mapel ? ' · ' + d.mapel : '') + ' · ' + d.soal + ' soal · ' + d.durasi + ' menit'];
      if (d.mulai) b.push('🗓 Mulai: ' + d.mulai);
      if (d.batas) b.push('⏰ Batas: ' + d.batas);
      b.push('', 'Buka: ' + d.url, (latihan ? 'Kode latihan: *' : 'Kode ujian: *') + d.kode + '*');
      if (sertakan() && tok) b.push('Token: *' + tok + '*' + (menit > 0 ? ' (berlaku s.d. ' + jamBerlaku() + ')' : ''));
      if (latihan) b.push('Latihan: tanpa token, boleh diulang 💪');
      b.push('', d.tertib === '1' ? 'Baca tata tertib dulu, kerjakan dengan jujur ya 🙏' : 'Kerjakan dengan jujur ya 🙏');
      return b.join('\n');
    }
    function muatGambar(src) { return new Promise(function (ok) { var i = new Image(); i.onload = function () { ok(i); }; i.onerror = function () { ok(null); }; i.src = src; }); }
    function bungkus(c, t, maks) {
      var kata = String(t).split(' '), baris = [], kini = '';
      kata.forEach(function (k) { var c2 = kini ? kini + ' ' + k : k; if (c.measureText(c2).width > maks && kini) { baris.push(kini); kini = k; } else kini = c2; });
      if (kini) baris.push(kini); return baris;
    }
    function kotakBulat(c, x, y, w, h, r) { c.beginPath(); c.moveTo(x + r, y); c.arcTo(x + w, y, x + w, y + h, r); c.arcTo(x + w, y + h, x, y + h, r); c.arcTo(x, y + h, x, y, r); c.arcTo(x, y, x + w, y, r); c.closePath(); }
    function gambarKartu() {
      var huruf = getComputedStyle(document.body).fontFamily || 'sans-serif';
      var qrSrc = '';
      try { var q = qrcode(0, 'M'); q.addData(d.url); q.make(); qrSrc = q.createDataURL(12, 1); } catch (e) { /* abaikan */ }
      return Promise.all([muatGambar('aset/logo.png'), muatGambar(qrSrc), document.fonts ? document.fonts.ready : null]).then(function (g) {
        var c = cv.getContext('2d'), W = 1080, H = 1200;
        var gr = c.createLinearGradient(0, 0, W * .4, H); gr.addColorStop(0, '#26317d'); gr.addColorStop(1, '#1a1f5e');
        c.fillStyle = gr; c.fillRect(0, 0, W, H);
        c.fillStyle = '#F2C230'; c.fillRect(0, H - 20, W * .6, 20); c.fillStyle = '#2E9E48'; c.fillRect(W * .6, H - 20, W * .4, 20);
        if (g[0]) c.drawImage(g[0], 72, 64, 112, 112 * g[0].height / g[0].width);
        c.textBaseline = 'alphabetic';
        c.fillStyle = '#F2C230'; c.font = '700 30px ' + huruf; c.fillText(String(d.sekolah).toUpperCase(), 210, 110);
        c.fillStyle = '#fff'; c.font = '700 40px ' + huruf; c.fillText(latihan ? 'Latihan Soal · SiGuru' : 'Ujian Online · SiGuru', 210, 160);
        c.font = '700 64px ' + huruf;
        var y = 290; bungkus(c, d.judul, W - 144).slice(0, 2).forEach(function (b) { c.fillText(b, 72, y); y += 76; });
        c.fillStyle = 'rgba(255,255,255,.85)'; c.font = '500 32px ' + huruf;
        c.fillText('Kelas ' + d.kelas + (d.mapel ? ' · ' + d.mapel : '') + ' · ' + d.durasi + ' menit', 72, y); y += 46;
        if (d.mulai) { c.fillText('Mulai ' + d.mulai, 72, y); y += 46; }
        y += 20;
        var bh = 470; c.fillStyle = '#fff'; kotakBulat(c, 72, y, W - 144, bh, 40); c.fill();
        if (g[1]) c.drawImage(g[1], 112, y + 40, 390, 390);
        var x = 550;
        c.fillStyle = '#667'; c.font = '600 28px ' + huruf; c.fillText(latihan ? 'KODE LATIHAN' : 'KODE UJIAN', x, y + 120);
        c.fillStyle = '#1b2160'; c.font = '800 92px ' + huruf; c.fillText(d.kode.split('').join(String.fromCharCode(8202)), x, y + 220);
        if (sertakan() && tok) {
          c.fillStyle = '#667'; c.font = '600 28px ' + huruf; c.fillText('TOKEN', x, y + 300);
          c.fillStyle = '#1b2160'; c.font = '800 66px ' + huruf; c.fillText(tok, x, y + 375);
          if (menit > 0) { c.fillStyle = '#8a6400'; c.font = '600 24px ' + huruf; c.fillText('berlaku s.d. ' + jamBerlaku(), x, y + 415); }
        } else if (!latihan) {
          c.fillStyle = '#667'; c.font = '500 26px ' + huruf; bungkus(c, 'Token diberikan guru di kelas', 400).forEach(function (b, i) { c.fillText(b, x, y + 300 + i * 34); });
        } else {
          c.fillStyle = '#1d6b33'; c.font = '600 28px ' + huruf; c.fillText('Tanpa token ·', x, y + 300); c.fillText('boleh diulang', x, y + 340);
        }
        y += bh + 70;
        c.fillStyle = '#fff'; c.font = '500 32px ' + huruf;
        c.fillText('📱 Pindai QR dengan kamera HP, atau buka', 72, y);
        c.font = '700 32px ' + huruf; c.fillText(d.url.replace(/^https?:\/\//, '').replace(/\?k=.*$/, ''), 72, y + 48);
        c.font = '500 32px ' + huruf; c.fillText('lalu masukkan kode di atas.', 72, y + 96);
      });
    }
    function segarkan() {
      prat.textContent = teks();
      document.getElementById('bagikan-wa').href = 'https://wa.me/?text=' + encodeURIComponent(teks());
      gambarSiap = gambarKartu();
    }
    function blobKartu() { return gambarSiap.then(function () { return new Promise(function (ok) { cv.toBlob(ok, 'image/png'); }); }); }
    function unduh(blob) {
      var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'kartu-' + (latihan ? 'latihan' : 'ujian') + '-' + d.kode + '.png';
      document.body.append(a); a.click(); setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 1500);
    }
    function pesan(t) { st.textContent = t; }
    function jadikanTetap() {
      if (!sertakan() || menit === 0 || jenis() !== 'tetap') return Promise.resolve();
      var fd = new FormData(); fd.append('_t', tokenEl.value); fd.append('aksi', 'token_tetap'); fd.append('id', id);
      return fetch('?p=api_ujian', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
        if (r.ok) { tok = r.token; menit = 0; elToken.textContent = r.token; sisaToken = 0; pesan('Token ujian ini sekarang tetap: ' + r.token + '.'); if (pilihToken) pilihToken.innerHTML = '<p class="kecil redup" style="margin:.2rem 0">Token ujian ini sudah tetap (tidak berganti).</p>'; segarkan(); }
      });
    }
    if (cb) cb.addEventListener('change', function () { if (pilihToken) pilihToken.hidden = !cb.checked; jadikanTetap().then(segarkan); });
    P.addEventListener('change', function (e) { if (e.target.name === 'jenis_token') jadikanTetap().then(segarkan); });
    document.getElementById('tombol-bagikan').addEventListener('click', function () { P.hidden = false; segarkan(); P.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
    document.getElementById('tutup-bagikan').addEventListener('click', function () { P.hidden = true; });
    document.getElementById('salin-teks').addEventListener('click', function () {
      (navigator.clipboard ? navigator.clipboard.writeText(teks()) : Promise.reject()).then(function () { pesan('✓ Teks disalin. Tempel di WA/Telegram.'); })
        .catch(function () { var t = document.createElement('textarea'); t.value = teks(); document.body.append(t); t.select(); document.execCommand('copy'); t.remove(); pesan('✓ Teks disalin.'); });
    });
    document.getElementById('unduh-gambar').addEventListener('click', function () { blobKartu().then(unduh); });
    document.getElementById('bagikan-sekarang').addEventListener('click', function () {
      var t = teks();
      blobKartu().then(function (blob) {
        var f = new File([blob], 'kartu-' + d.kode + '.png', { type: 'image/png' });
        if (navigator.canShare && navigator.canShare({ files: [f] })) return navigator.share({ files: [f], text: t, title: d.judul }).then(function () { pesan('✓ Dibagikan.'); });
        // perangkat yang belum bisa kirim gambar+teks sekaligus: buka WA dengan teks, gambar diunduh untuk dilampirkan
        unduh(blob);
        window.open('https://wa.me/?text=' + encodeURIComponent(t), '_blank');
        pesan('Teks dibuka di WhatsApp; gambar kartu sudah diunduh — lampirkan gambarnya di chat.');
      }).catch(function (e) { if (e && e.name !== 'AbortError') pesan('Gagal membagikan: ' + e.message); });
    });
    return { tokenBaru: function (t, s) { tok = t; tokSisa = s; if (!P.hidden && sertakan()) segarkan(); } };
  })();

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

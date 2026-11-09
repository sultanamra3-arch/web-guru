/* SiGuru · bagikan ujian/latihan/tugas ke WA: tulisan + gambar kartu (QR, kode, token/batas) */
(function () {
  'use strict';
  var P = document.getElementById('panel-bagikan');
  if (!P) return;
  var d = P.dataset, jenis = d.jenis || 'ujian', latihan = jenis === 'latihan', tugas = jenis === 'tugas', kuis = jenis === 'kuis';
  var cb = document.getElementById('sertakan-token'), pilihToken = P.querySelector('.pilih-token');
  var cv = document.getElementById('kartu-bagikan'), prat = document.getElementById('pratinjau-pesan'), st = document.getElementById('status-bagikan');
  var tok = '', tokSisa = 0, menit = +(d.tokenMenit || 0), gambarSiap = null;
  var tokenEl = document.querySelector('input[name=_t]');
  var label = kuis ? 'Kuis Cepat' : (tugas ? 'Tugas Online' : (latihan ? 'Latihan Soal' : 'Ujian Online'));
  var labelKode = kuis ? 'KODE KUIS' : (tugas ? 'KODE TUGAS' : (latihan ? 'KODE LATIHAN' : 'KODE UJIAN'));

  document.addEventListener('siguru:token', function (e) { tok = e.detail.token; tokSisa = e.detail.sisa; if (!P.hidden && sertakan()) segarkan(); });

  function jenisToken() { var r = P.querySelector('input[name=jenis_token]:checked'); return r ? r.value : 'tetap'; }
  function sertakan() { return !latihan && !tugas && !kuis && cb && cb.checked; }
  function jamBerlaku() {
    var t = new Date(Date.now() + (tokSisa + menit * 60) * 1000);
    return ('0' + t.getHours()).slice(-2) + '.' + ('0' + t.getMinutes()).slice(-2);
  }
  function teks() {
    var b = [(kuis ? '⚡ *Kuis: ' : '📝 *' + (tugas ? 'Tugas: ' : '')) + d.judul + '*'];
    b.push('📚 ' + (d.mapel ? d.mapel + ' · ' : '') + 'Kelas ' + d.kelas);
    if (d.guru) b.push('👤 Guru: ' + d.guru);
    if (!tugas) b.push('🧮 ' + d.soal + ' soal · ' + (kuis ? d.durasi + ' detik per soal' : d.durasi + ' menit'));
    if (d.mulai) b.push('🗓 Mulai: ' + d.mulai);
    if (d.batas) b.push('⏰ Batas: ' + d.batas);
    b.push('', 'Buka: ' + d.url, labelKode.charAt(0) + labelKode.slice(1).toLowerCase() + ': *' + d.kode + '*');
    if (sertakan() && tok) b.push('Token: *' + tok + '*' + (menit > 0 ? ' (berlaku s.d. ' + jamBerlaku() + ')' : ''));
    if (latihan) b.push('Latihan: tanpa token, boleh diulang 💪');
    if (tugas) b.push('Foto tugasmu lalu kirim lewat link di atas ya 📷');
    if (kuis) { b.push('', 'Siapkan HP, masuk sekarang, lalu lihat layar guru. Jawab cepat & tepat untuk poin terbanyak! 🏆'); return b.join('\n'); }
    b.push('', d.tertib === '1' ? 'Baca tata tertib dulu, kerjakan dengan jujur ya 🙏' : 'Kerjakan dengan jujur ya 🙏');
    return b.join('\n');
  }
  function muatGambar(src) { return new Promise(function (ok) { if (!src) return ok(null); var i = new Image(); i.onload = function () { ok(i); }; i.onerror = function () { ok(null); }; i.src = src; }); }
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
      var c = cv.getContext('2d'), W = 1080;
      c.font = '700 64px ' + huruf;
      var nJudul = Math.min(2, bungkus(c, d.judul, W - 144).length);
      var nInfo = 1 + (d.guru ? 1 : 0) + (d.mulai ? 1 : 0);
      var H = 290 + nJudul * 76 + nInfo * 46 + 20 + 470 + 70 + 96 + 60;
      cv.height = H; c = cv.getContext('2d');
      var gr = c.createLinearGradient(0, 0, W * .4, H); gr.addColorStop(0, '#26317d'); gr.addColorStop(1, '#1a1f5e');
      c.fillStyle = gr; c.fillRect(0, 0, W, H);
      c.fillStyle = '#F2C230'; c.fillRect(0, H - 20, W * .6, 20); c.fillStyle = '#2E9E48'; c.fillRect(W * .6, H - 20, W * .4, 20);
      if (g[0]) c.drawImage(g[0], 72, 64, 112, 112 * g[0].height / g[0].width);
      c.textBaseline = 'alphabetic';
      c.fillStyle = '#F2C230'; c.font = '700 30px ' + huruf; c.fillText(String(d.sekolah).toUpperCase(), 210, 110);
      c.fillStyle = '#fff'; c.font = '700 40px ' + huruf; c.fillText(label + ' · SiGuru', 210, 160);
      c.font = '700 64px ' + huruf;
      var y = 290; bungkus(c, d.judul, W - 144).slice(0, 2).forEach(function (b) { c.fillText(b, 72, y); y += 76; });
      c.fillStyle = 'rgba(255,255,255,.85)'; c.font = '500 32px ' + huruf;
      c.fillText((d.mapel ? d.mapel + ' · ' : '') + 'Kelas ' + d.kelas + (tugas ? '' : (kuis ? ' · ' + d.soal + ' soal' : ' · ' + d.durasi + ' menit')), 72, y); y += 46;
      if (d.guru) { c.fillText('Guru: ' + d.guru, 72, y); y += 46; }
      if (d.mulai) { c.fillText('Mulai ' + d.mulai, 72, y); y += 46; }
      y += 20;
      var bh = 470; c.fillStyle = '#fff'; kotakBulat(c, 72, y, W - 144, bh, 40); c.fill();
      if (g[1]) c.drawImage(g[1], 112, y + 40, 390, 390);
      var x = 550;
      c.fillStyle = '#667'; c.font = '600 28px ' + huruf; c.fillText(labelKode, x, y + 120);
      c.fillStyle = '#1b2160'; c.font = '800 ' + (d.kode.length > 6 ? 80 : 92) + 'px ' + huruf; c.fillText(kuis ? d.kode.replace(/(\d{3})(\d{3})/, '$1 $2') : d.kode.split('').join(String.fromCharCode(8202)), x, y + 220);
      if (kuis) {
        c.fillStyle = '#1d6b33'; c.font = '700 30px ' + huruf; c.fillText('Masuk sekarang,', x, y + 310); c.fillText('lihat layar guru ⚡', x, y + 352);
      } else if (tugas) {
        c.fillStyle = '#667'; c.font = '600 28px ' + huruf; c.fillText('BATAS', x, y + 300);
        c.fillStyle = '#b3261e'; c.font = '700 32px ' + huruf;
        bungkus(c, d.batas || 'tanpa batas', 410).slice(0, 3).forEach(function (b, i) { c.fillText(b, x, y + 345 + i * 40); });
      } else if (sertakan() && tok) {
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
    var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'kartu-' + jenis + '-' + d.kode + '.png';
    document.body.append(a); a.click(); setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 1500);
  }
  function pesan(t) { st.textContent = t; }
  function jadikanTetap() {
    if (!sertakan() || menit === 0 || jenisToken() !== 'tetap') return Promise.resolve();
    var fd = new FormData(); fd.append('_t', tokenEl.value); fd.append('aksi', 'token_tetap'); fd.append('id', d.id);
    return fetch('?p=api_ujian', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
      if (!r.ok) return;
      tok = r.token; menit = 0; tokSisa = 0;
      var te = document.getElementById('token'); if (te) te.textContent = r.token;
      pesan('Token ujian ini sekarang tetap: ' + r.token + '.');
      if (pilihToken) pilihToken.innerHTML = '<p class="kecil redup" style="margin:.2rem 0">Token ujian ini sudah tetap (tidak berganti).</p>';
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
})();

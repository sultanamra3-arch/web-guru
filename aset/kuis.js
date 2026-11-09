/* SiGuru · kuis cepat: layar guru (proyektor) & HP siswa. Kuis berjalan sendiri sampai akhir;
   jawaban benar, poin, dan peringkat baru dibuka setelah soal terakhir. Sinkron lewat polling ringan. */
(function () {
  'use strict';
  var WARNA = { A: '#e21b3c', B: '#1368ce', C: '#c98f00', D: '#26890c', E: '#864cbf' };
  var BENTUK = { A: '▲', B: '◆', C: '●', D: '■', E: '★' };
  function el(tag, kelas, teks) { var e = document.createElement(tag); if (kelas) e.className = kelas; if (teks != null) e.textContent = teks; return e; }
  function baris(induk, teks) { String(teks || '').split(/\n/).forEach(function (b, i) { if (i) induk.append(document.createElement('br')); induk.append(document.createTextNode(b)); }); }
  function kirim(url, data) {
    var fd = new FormData(); Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }
  function hitungMundur(ms) { return Math.max(1, Math.ceil(ms / 1000)); }

  /* ================= Layar guru ================= */
  var G = document.getElementById('panggung');
  if (G) (function () {
    var id = G.dataset.id, token = document.querySelector('input[name=_t]').value;
    var isi = document.getElementById('pg-isi'), tombol = document.getElementById('pg-tombol'), info = document.getElementById('pg-info');
    var S = null, kunciTampil = '', tAkhir = 0, tMulai = 0, sibuk = false;
    document.getElementById('pg-penuh').onclick = function () { if (!document.fullscreenElement) G.requestFullscreen && G.requestFullscreen().catch(function () {}); else document.exitFullscreen(); };
    function aksi(a, x) {
      if (sibuk) return; sibuk = true;
      kirim('?p=api_kuis', Object.assign({ _t: token, id: id, aksi: a }, x || {})).then(function (r) {
        sibuk = false; if (r && r.pesan) { alert(r.pesan); if (r.tautan) location.href = r.tautan; return; } muat();
      }).catch(function () { sibuk = false; });
    }
    function btn(teks, kelas, f) { var b = el('button', kelas, teks); b.type = 'button'; b.onclick = f; tombol.append(b); return b; }
    function qr(target, url, ukuran) { try { var q = qrcode(0, 'M'); q.addData(url); q.make(); target.innerHTML = q.createSvgTag({ cellSize: ukuran || 6, margin: 1, scalable: true }); } catch (e) { /* abaikan */ } }
    function papan(peserta) {
      var ol = el('ol', 'pg-peringkat');
      peserta.forEach(function (p) { var li = el('li'); li.append(el('b', null, p.nama), el('span', null, p.poin + ' poin · ' + p.benar + ' benar')); ol.append(li); });
      return ol;
    }
    var tTotal = 0;
    function mmss(ms) { var d = Math.max(0, Math.ceil(ms / 1000)); return Math.floor(d / 60) + ':' + ('0' + d % 60).slice(-2); }
    function gambarBalap(r) {
      info.textContent = r.peserta.length + ' peserta · ' + r.total + ' soal';
      tMulai = Date.now() + r.mulai_dalam; tTotal = Date.now() + r.sisa_total_ms;
      if (r.mulai_dalam > 0) {
        if (kunciTampil === 'siap') return;
        kunciTampil = 'siap'; isi.innerHTML = ''; tombol.innerHTML = ''; G.className = 'panggung st-soal';
        var c = el('div', 'pg-siap'); c.append(el('div', 'pg-no', 'Siap-siap… semua mulai bersamaan'), el('div', 'pg-angka', '')); c.lastChild.id = 'pg-angka'; isi.append(c);
        return;
      }
      if (kunciTampil !== 'balap') {
        kunciTampil = 'balap'; isi.innerHTML = ''; tombol.innerHTML = ''; G.className = 'panggung st-jalan';
        var atas = el('div', 'pg-balap-atas'), w = el('span', 'pg-waktu', ''); w.id = 'pg-total';
        var ring = el('span', 'pg-ringkas', ''); ring.id = 'pg-ringkas';
        atas.append(el('span', 'pg-no', '🏁 Balapan kuis'), ring, w); isi.append(atas);
        var daftar = el('div', 'pg-balap'); daftar.id = 'pg-balap'; isi.append(daftar);
        btn('■ Akhiri kuis sekarang', '', function () { if (confirm('Akhiri kuis sekarang? Siswa yang belum selesai berhenti di soal terakhir yang dijawabnya.')) aksi('selesai'); });
      }
      var n = { selesai: 0, jalan: 0, belum: 0 };
      var bl = document.getElementById('pg-balap'); bl.innerHTML = '';
      r.peserta.forEach(function (p) {
        var st = p.selesai_ke ? 'selesai' : (p.pos > 0 ? 'jalan' : 'belum'); n[st]++;
        var row = el('div', 'pg-baris ' + st);
        row.append(el('span', 'pg-baris-nama', p.nama));
        var trek = el('span', 'pg-trek'), isiTrek = el('span'); isiTrek.style.width = (p.pos / r.total * 100) + '%'; trek.append(isiTrek); row.append(trek);
        row.append(el('span', 'pg-baris-ket', p.selesai_ke ? '🏁 ke-' + p.selesai_ke : p.pos + '/' + r.total));
        bl.append(row);
      });
      document.getElementById('pg-ringkas').textContent = n.selesai + ' selesai · ' + n.jalan + ' mengerjakan' + (n.belum ? ' · ' + n.belum + ' belum mulai' : '');
    }
    function gambar(r) {
      if (r.mode === 'mandiri' && r.status === 'jalan') { gambarBalap(r); return; }
      var siap = r.status === 'soal' && r.mulai_dalam > 0;
      info.textContent = r.peserta.length + ' peserta' + (r.status === 'soal' ? ' · soal ' + (r.nomor + 1) + '/' + r.total : '');
      if (r.status === 'soal') {
        tMulai = Date.now() + r.mulai_dalam; tAkhir = tMulai + r.sisa_ms + (r.mulai_dalam > 0 ? 0 : 0);
        if (!siap) tAkhir = Date.now() + r.sisa_ms;
        var dj = document.getElementById('pg-dijawab'); if (dj) dj.textContent = r.dijawab + '/' + r.peserta.length + ' menjawab';
      }
      var kunciBaru = r.status + ':' + r.nomor + ':' + (siap ? 'siap' : '') + ':' + (r.status === 'lobi' ? r.peserta.map(function (p) { return p.kode; }).join(',') : '');
      if (kunciBaru === kunciTampil) return;
      kunciTampil = kunciBaru; isi.innerHTML = ''; tombol.innerHTML = '';
      G.className = 'panggung st-' + r.status;
      if (r.status === 'lobi') {
        var w = el('div', 'pg-lobi'), kiri = el('div', 'pg-lobi-kiri'), q = el('div', 'pg-qr');
        kiri.append(el('div', 'pg-kecil', 'Buka di HP:'), el('div', 'pg-url', G.dataset.url.replace(/^https?:\/\//, '').replace(/\?k=.*$/, '')),
          el('div', 'pg-kecil', 'Kode kuis'), el('div', 'pg-kode', G.dataset.kode.replace(/(\d{3})(\d{3})/, '$1 $2')));
        qr(q, G.dataset.url, 8); w.append(kiri, q); isi.append(w);
        var nm = el('div', 'pg-nama');
        if (!r.peserta.length) nm.append(el('span', 'redup', 'Menunggu siswa masuk…'));
        r.peserta.forEach(function (p) { var c = el('button', 'chip', p.nama); c.type = 'button'; c.title = 'Ketuk untuk mengeluarkan'; c.onclick = function () { if (confirm('Keluarkan ' + p.nama + ' dari kuis?')) aksi('keluarkan', { kode: p.kode }); }; nm.append(c); });
        isi.append(el('h3', null, r.peserta.length + ' siswa sudah masuk'), nm);
        btn('▶ Mulai kuis', 'utama besar', function () { if (!r.peserta.length && !confirm('Belum ada siswa yang masuk. Tetap mulai?')) return; aksi('mulai'); });
      } else if (siap) {
        var c = el('div', 'pg-siap'); c.append(el('div', 'pg-no', 'Soal ' + (r.nomor + 1) + ' dari ' + r.total), el('div', 'pg-angka', ''));
        c.lastChild.id = 'pg-angka'; isi.append(c);
        btn('■ Akhiri kuis', '', function () { if (confirm('Akhiri kuis sekarang dan tampilkan hasil?')) aksi('selesai'); });
      } else if (r.status === 'soal') {
        var s = r.soal || { teks: '', opsi: [] };
        var top = el('div', 'pg-soal-atas'); top.append(el('span', 'pg-no', 'Soal ' + (r.nomor + 1) + ' dari ' + r.total));
        var dj2 = el('span', 'pg-dijawab', r.dijawab + '/' + r.peserta.length + ' menjawab'); dj2.id = 'pg-dijawab'; top.append(dj2);
        var wk = el('span', 'pg-waktu', ''); wk.id = 'pg-waktu'; top.append(wk);
        isi.append(top);
        var t = el('div', 'pg-teks'); baris(t, s.teks); isi.append(t);
        if (s.g) { var im = el('img', 'pg-gambar'); im.src = s.g; isi.append(im); }
        var bar = el('div', 'pg-bar'); bar.append(el('div')); bar.id = 'pg-bar'; isi.append(bar);
        var grid = el('div', 'pg-opsi n' + s.opsi.length);
        s.opsi.forEach(function (o) {
          var d = el('div', 'pg-pil'); d.style.background = WARNA[o.k] || '#555';
          d.append(el('span', 'pg-bentuk', BENTUK[o.k] || o.k));
          var tx = el('span', 'pg-pil-teks'); baris(tx, o.t); if (o.g) { var ig = el('img'); ig.src = o.g; tx.append(ig); } d.append(tx);
          grid.append(d);
        });
        isi.append(grid);
        btn('Lewati soal ›', '', function () { aksi('berikut'); });
        btn('■ Akhiri kuis', '', function () { if (confirm('Akhiri kuis sekarang dan tampilkan hasil?')) aksi('selesai'); });
      } else if (r.status === 'selesai') {
        isi.append(el('h2', 'pg-judul-peringkat', '🏁 Hasil akhir'));
        var pod = el('div', 'pg-podium');
        [1, 0, 2].forEach(function (i) { var p = r.peserta[i]; if (!p) return; var c2 = el('div', 'pod pod-' + (i + 1)); c2.append(el('div', 'pod-medali', ['🥇', '🥈', '🥉'][i]), el('b', null, p.nama), el('span', null, p.poin + ' poin'), el('div', 'pod-tiang', String(i + 1))); pod.append(c2); });
        isi.append(pod, papan(r.peserta));
        // pembahasan semua soal (jawaban baru dibuka sekarang)
        var det = el('details', 'pg-rekap'); det.append(el('summary', null, '📖 Jawaban & pembahasan semua soal (' + (r.rekap || []).length + ')'));
        (r.rekap || []).forEach(function (x) {
          var k = el('div', 'pg-rekap-soal');
          var h = el('div', 'pg-rekap-kepala'); h.append(el('b', null, 'Soal ' + x.no), el('span', null, x.benar + '/' + r.peserta.length + ' benar')); k.append(h);
          var tt = el('div', 'pg-rekap-teks'); baris(tt, x.teks); k.append(tt);
          if (x.g) { var gi = el('img', 'pg-gambar'); gi.src = x.g; k.append(gi); }
          var maks = 1; x.opsi.forEach(function (o) { maks = Math.max(maks, o.n); });
          x.opsi.forEach(function (o) {
            var d = el('div', 'pg-rekap-pil' + (o.k === x.kunci ? ' kunci' : ''));
            d.style.setProperty('--isi', (o.n / maks * 100) + '%'); d.style.setProperty('--warna', WARNA[o.k] || '#555');
            d.append(el('span', 'pg-bentuk', BENTUK[o.k] || o.k), el('span', 'pg-pil-teks', o.t), el('span', 'pg-jml', o.n + (o.k === x.kunci ? ' ✓' : '')));
            k.append(d);
          });
          if (x.pembahasan) { var pb = el('div', 'pg-pembahasan'); pb.append(el('b', null, 'Pembahasan: ')); baris(pb, x.pembahasan); k.append(pb); }
          det.append(k);
        });
        isi.append(det);
        btn('💾 Simpan nilai ke Penilaian', 'utama', function () { if (confirm('Simpan nilai (persentase benar) semua peserta ke Penilaian sebagai Asesmen Formatif?')) aksi('simpan_nilai'); });
        btn('↻ Main lagi', '', function () { if (confirm('Ulang kuis dari awal? Poin semua peserta direset.')) aksi('ulang'); });
      }
    }
    function muat() {
      fetch('?p=api_kuis&id=' + id, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) { if (r.ok) { S = r; gambar(r); } }).catch(function () {});
    }
    setInterval(function () {
      if (S && S.mode === 'mandiri' && S.status === 'jalan') {
        var a2 = document.getElementById('pg-angka'); if (a2) a2.textContent = String(hitungMundur(tMulai - Date.now()));
        var tt = document.getElementById('pg-total'); if (tt) tt.textContent = '⏱ ' + mmss(tTotal - Date.now());
        return;
      }
      if (!S || S.status !== 'soal') return;
      var a = document.getElementById('pg-angka'); if (a) a.textContent = String(hitungMundur(tMulai - Date.now()));
      var ms = Math.max(0, tAkhir - Date.now()), w = document.getElementById('pg-waktu'), b = document.querySelector('#pg-bar div');
      if (w) w.textContent = Math.ceil(ms / 1000) + ' dtk';
      if (b) b.style.width = Math.min(100, ms / (S.detik * 1000) * 100) + '%';
    }, 200);
    muat(); setInterval(muat, 1000);
  })();

  /* ================= HP siswa ================= */
  var K = document.getElementById('kuis-siswa');
  if (K) (function () {
    var id = K.dataset.id, token = K.dataset.t, isi = document.getElementById('ks-isi'), poinEl = document.getElementById('ks-poin');
    var kunciTampil = '', tAkhir = 0, tMulai = 0, S = null, mengirim = false;
    var HURUF = ['A', 'B', 'C', 'D', 'E'], minta = false;
    function tampil(r) {
      if (r.mode === 'mandiri' && r.status === 'jalan') r = Object.assign({}, r, { status: r.selesai_saya ? 'tunggu' : 'soal' });
      poinEl.textContent = r.status === 'selesai' ? r.poin + ' poin' : (r.status === 'soal' ? 'Soal ' + Math.min(r.total, r.nomor + 1) + '/' + r.total : r.total + ' soal');
      var siap = r.status === 'soal' && r.mulai_dalam > 0;
      if (r.status === 'soal') { tMulai = Date.now() + r.mulai_dalam; tAkhir = siap ? tMulai + r.detik * 1000 : Date.now() + r.sisa_ms; }
      var habis = r.status === 'soal' && !siap && r.sisa_ms <= 0 && r.mode !== 'mandiri';
      var kunci = r.status + ':' + r.nomor + ':' + (r.jawabku ? 1 : 0) + ':' + (siap ? 'siap' : '') + ':' + (habis ? 'habis' : '') + ':' + (r.status === 'lobi' ? r.jumlah : '') + ':' + (r.soal ? 1 : 0);
      if (kunci === kunciTampil) return;
      kunciTampil = kunci; isi.innerHTML = '';
      K.className = 'kuis-siswa st-' + r.status;
      if (r.status === 'lobi') {
        isi.append(el('div', 'ks-besar', '🎉'), el('h2', null, 'Kamu sudah masuk!'), el('p', null, 'Lihat layar guru. Kuis dimulai sebentar lagi.'), el('p', 'redup', r.jumlah + ' peserta'));
      } else if (r.status === 'tunggu') {
        isi.append(el('div', 'ks-besar', '🏁'), el('h2', null, 'Kamu selesai' + (r.urut_selesai ? ' ke-' + r.urut_selesai : '') + '!'),
          el('p', null, 'Hebat! Tunggu teman-temanmu. Nilai, peringkat, dan pembahasan muncul setelah kuis berakhir.'));
      } else if (siap) {
        var a = el('div', 'ks-besar', ''); a.id = 'ks-angka';
        isi.append(el('h2', null, r.mode === 'mandiri' ? 'Kuis dimulai!' : 'Soal ' + (r.nomor + 1) + ' dari ' + r.total), a, el('p', null, 'Siap-siap!'));
      } else if (r.status === 'soal' && r.mode === 'mandiri' && !r.soal) {
        isi.append(el('p', null, 'Memuat soal berikutnya…'));
      } else if (r.status === 'soal' && r.jawabku) {
        var c = el('div', 'ks-terkirim'); c.style.background = WARNA[r.jawabku.p] || '#555';
        c.append(el('div', 'ks-besar', BENTUK[r.jawabku.p] || r.jawabku.p), el('b', null, 'Jawaban terkirim'), el('span', null, 'Tunggu soal berikutnya…'));
        isi.append(c);
      } else if (habis) {
        isi.append(el('div', 'ks-besar', '⏰'), el('h2', null, 'Waktu habis'), el('p', null, 'Tunggu soal berikutnya…'));
      } else if (r.status === 'soal' && r.soal) {
        var atas = el('div', 'ks-atas'); atas.append(el('span', null, 'Soal ' + (r.nomor + 1) + '/' + r.total), el('span', 'ks-waktu', '')); isi.append(atas);
        var t = el('div', 'ks-teks'); baris(t, r.soal.teks); isi.append(t);
        var g = el('div', 'ks-opsi n' + r.soal.opsi.length);
        if (r.soal.g) { var gi = el('img', 'ks-gambar'); gi.src = r.soal.g; isi.append(gi); }
        r.soal.opsi.forEach(function (o, idx) {
          var hf = HURUF[idx] || o.k;   // warna & bentuk mengikuti posisi tombol (pilihan bisa diacak)
          var b = el('button', 'ks-pil'); b.type = 'button'; b.style.background = WARNA[hf] || '#555';
          b.append(el('span', 'ks-bentuk', BENTUK[hf] || hf)); var tx = el('span', 'ks-pil-teks'); baris(tx, o.t); b.append(tx);
          b.onclick = function () {
            if (mengirim) return; mengirim = true;
            Array.prototype.forEach.call(g.children, function (x) { x.disabled = true; x.style.opacity = x === b ? 1 : .35; });
            kirim('?p=api_k', { _t: token, id: id, aksi: 'jawab', nomor: r.nomor, pilihan: o.k }).then(function (x) { mengirim = false; if (x.ok) { S = x; tampil(x); } })
              .catch(function () { mengirim = false; Array.prototype.forEach.call(g.children, function (x) { x.disabled = false; x.style.opacity = 1; }); });
          };
          g.append(b);
        });
        isi.append(g);
      } else if (r.status === 'selesai') {
        isi.append(el('div', 'ks-besar', r.rank && r.rank <= 3 ? ['🥇', '🥈', '🥉'][r.rank - 1] : '🏁'), el('h2', null, 'Kuis selesai!'),
          el('p', 'ks-rank', 'Peringkat ke-' + r.rank + ' dari ' + r.jumlah), el('p', null, r.poin + ' poin · ' + r.benar + ' dari ' + r.total + ' benar'));
        var ul = el('ol', 'ks-rekap');
        (r.rekap || []).forEach(function (x) {
          var ok = x.jawabku && x.jawabku === x.kunci, li = el('li', ok ? 'benar' : 'salah');
          var h = el('div', 'ks-rekap-kepala'); h.append(el('b', null, 'Soal ' + x.no), el('span', null, ok ? '✓ +' + x.poin : (x.jawabku ? '✗' : '– tidak menjawab'))); li.append(h);
          var tt = el('div'); baris(tt, x.teks); li.append(tt);
          li.append(el('div', 'ks-rekap-jawab', (x.jawabku ? 'Jawabanmu: ' + (x.jawab_teks || x.jawabku) + ' · ' : '') + 'Kunci: ' + x.kunci_teks));
          if (x.pembahasan) li.append(el('div', 'ks-rekap-bahas', x.pembahasan));
          ul.append(li);
        });
        isi.append(el('h3', null, 'Jawaban & pembahasan'), ul);
      }
    }
    function muat() {
      if (mengirim) return;
      fetch('?p=api_k&id=' + id, { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
        if (r.keluar) { isi.innerHTML = ''; isi.append(el('h2', null, r.galat || 'Sesi berakhir.')); setTimeout(function () { location.href = '?p=k&k=' + K.dataset.kode; }, 2500); return; }
        if (r.ok) { S = r; tampil(r); }
      }).catch(function () {});
    }
    setInterval(function () {
      if (!S || (S.status !== 'soal' && S.status !== 'jalan')) return;
      var a = document.getElementById('ks-angka'); if (a) a.textContent = String(hitungMundur(tMulai - Date.now()));
      var w = document.querySelector('.ks-waktu');
      if (w) {
        var dd = Math.max(0, Math.ceil((tAkhir - Date.now()) / 1000)); w.textContent = dd + ' dtk';
        // mode mandiri: waktu soal habis → minta soal berikutnya
        if (S.mode === 'mandiri' && dd === 0 && !minta && !mengirim) { minta = true; setTimeout(function () { minta = false; muat(); }, 1100); }
      }
    }, 250);
    muat(); setInterval(muat, 1500);
  })();
})();

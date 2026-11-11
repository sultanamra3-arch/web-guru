/* SiGuru: jendela konfirmasi & pesan sendiri (pengganti confirm/alert bawaan browser & APK).
 * - sgKonfirmasi(pesan, {ok, batal, bahaya}) → Promise<boolean>
 * - sgPesan(pesan) → Promise   (alert() otomatis memakai ini)
 * - sgSalin(teks)              (kotak teks untuk disalin manual)
 * - onclick/onsubmit="return confirm('...')" yang sudah ada di halaman otomatis memakai jendela ini.
 */
(function () {
  var confirmAsli = window.confirm ? window.confirm.bind(window) : function () { return true; };
  var lolos = false;

  function buat(pesan, o) {
    o = o || {};
    return new Promise(function (selesai) {
      var lama = document.activeElement;
      var latar = document.createElement('div'); latar.className = 'sg-dialog-latar';
      var kotak = document.createElement('div'); kotak.className = 'sg-dialog'; kotak.setAttribute('role', o.alert ? 'alertdialog' : 'dialog'); kotak.setAttribute('aria-modal', 'true');
      var baris = String(pesan == null ? '' : pesan).replace(/\r/g, '').split('\n');
      var judul = document.createElement('p'); judul.className = 'sg-dialog-judul'; judul.textContent = baris.shift();
      kotak.appendChild(judul);
      var sisa = baris.join('\n').trim();
      if (sisa) { var sub = document.createElement('p'); sub.className = 'sg-dialog-isi'; sub.textContent = sisa; kotak.appendChild(sub); }
      if (o.teks != null) { var ta = document.createElement('textarea'); ta.className = 'sg-dialog-teks'; ta.readOnly = true; ta.value = o.teks; ta.rows = 5; kotak.appendChild(ta); setTimeout(function () { ta.focus(); ta.select(); }, 30); }
      var tb = document.createElement('div'); tb.className = 'sg-dialog-tombol';
      var bahaya = o.bahaya != null ? o.bahaya : /\b(hapus|keluarkan|ulang|akhiri|kosongkan|reset)/i.test(judul.textContent);
      var ok = document.createElement('button'); ok.type = 'button'; ok.className = 'sg-dialog-ok' + (bahaya && !o.alert ? ' bahaya' : ' utama');
      ok.textContent = o.ok || (o.alert ? 'Oke' : (bahaya ? (judul.textContent.match(/^(Hapus|Keluarkan|Ulang|Akhiri|Kosongkan)/i) || ['Ya'])[0] : 'Ya, lanjutkan'));
      if (!o.alert) { var bt = document.createElement('button'); bt.type = 'button'; bt.className = 'sg-dialog-batal'; bt.textContent = o.batal || 'Batal'; tb.appendChild(bt); bt.onclick = function () { tutup(false); }; }
      tb.appendChild(ok); kotak.appendChild(tb); latar.appendChild(kotak);
      function tutup(hasil) {
        document.removeEventListener('keydown', tombol, true);
        latar.classList.add('keluar');
        setTimeout(function () { latar.remove(); }, 120);
        document.documentElement.classList.remove('sg-dialog-buka');
        if (lama && lama.focus) try { lama.focus({ preventScroll: true }); } catch (e) {}
        selesai(hasil);
      }
      function tombol(e) {
        if (e.key === 'Escape') { e.preventDefault(); tutup(o.alert ? true : false); }
        else if (e.key === 'Enter' && document.activeElement !== (kotak.querySelector('textarea'))) { e.preventDefault(); tutup(true); }
      }
      ok.onclick = function () { tutup(true); };
      latar.addEventListener('click', function (e) { if (e.target === latar) tutup(o.alert ? true : false); });
      document.addEventListener('keydown', tombol, true);
      document.documentElement.classList.add('sg-dialog-buka');
      (document.body || document.documentElement).appendChild(latar);
      if (o.teks == null) setTimeout(function () { ok.focus(); }, 30);
    });
  }

  window.sgKonfirmasi = function (pesan, o) { return buat(pesan, o); };
  window.sgPesan = function (pesan, o) { return buat(pesan, Object.assign({ alert: true }, o || {})); };
  window.sgSalin = function (teks) { return buat('Salin teks ini', { alert: true, teks: teks, ok: 'Tutup' }); };
  window.alert = function (m) { window.sgPesan(m); };
  // confirm() biasa hanya dipakai saat tombol yang sudah disetujui dijalankan ulang
  window.confirm = function (m) { return lolos ? true : confirmAsli(m); };

  /** Ambil teks dari atribut  return confirm('...')  */
  function pesanDari(attr) {
    var m = /^\s*return\s+confirm\(([\s\S]*)\)\s*;?\s*$/.exec(attr || '');
    if (!m) return null;
    var s = m[1].trim();
    try { return JSON.parse(s); } catch (e) {}
    if (/^'[\s\S]*'$/.test(s)) return s.slice(1, -1).replace(/\\n/g, '\n').replace(/\\'/g, "'").replace(/\\"/g, '"').replace(/\\\\/g, '\\');
    return null;
  }

  document.addEventListener('click', function (e) {
    var el = e.target.closest && e.target.closest('[onclick]');
    if (!el || el.__sgOk) return;
    var p = pesanDari(el.getAttribute('onclick'));
    if (p === null) return;
    e.preventDefault(); e.stopImmediatePropagation();
    buat(p).then(function (ya) {
      if (!ya) return;
      el.__sgOk = true; lolos = true;
      try { el.click(); } finally { lolos = false; el.__sgOk = false; }
    });
  }, true);

  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f || f.__sgOk) return;
    var p = pesanDari(f.getAttribute('onsubmit'));
    if (p === null) return;
    e.preventDefault(); e.stopImmediatePropagation();
    var oleh = e.submitter;
    buat(p).then(function (ya) {
      if (!ya) return;
      f.__sgOk = true; lolos = true;
      try {
        if (f.requestSubmit) f.requestSubmit(oleh && oleh.form === f ? oleh : undefined);
        else { if (oleh && oleh.name) { var h = document.createElement('input'); h.type = 'hidden'; h.name = oleh.name; h.value = oleh.value; f.appendChild(h); } f.submit(); }
      } finally { lolos = false; f.__sgOk = false; }
    });
  }, true);
})();

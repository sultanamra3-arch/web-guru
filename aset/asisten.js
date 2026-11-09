/* SiGuru · Asisten AI admin: chat + kartu usulan (simpan setelah dikonfirmasi) */
(function () {
  'use strict';
  var chat = document.getElementById('chat');
  if (!chat) return;
  function el(tag, kelas, teks) { var e = document.createElement(tag); if (kelas) e.className = kelas; if (teks != null) e.textContent = teks; return e; }
  var token = chat.dataset.token, form = document.getElementById('chat-form'), teks = document.getElementById('chat-teks'), tombol = document.getElementById('chat-kirim');
  var pf = document.getElementById('chat-foto-pratinjau'), foto = [];

  function esc(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function md(t) {
    return esc(t).replace(/\*\*(.+?)\*\*/g, '<b>$1</b>').replace(/`([^`]+)`/g, '<code>$1</code>')
      .replace(/^\s*[-*•] (.*)$/gm, '• $1').replace(/\n/g, '<br>');
  }
  Array.prototype.forEach.call(chat.querySelectorAll('[data-md]'), function (g) { g.innerHTML = md(g.textContent); });
  function gulir() { window.scrollTo(0, document.body.scrollHeight); }
  gulir();

  function kirim(data) {
    var fd = new FormData(); fd.append('_t', token);
    Object.keys(data).forEach(function (k) { if (Array.isArray(data[k])) data[k].forEach(function (x) { fd.append(k + '[]', x); }); else fd.append(k, data[k]); });
    return fetch('?p=api_ai', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.text(); })
      .then(function (t) { try { return JSON.parse(t); } catch (e) { return { ok: false, teks: 'Server sibuk atau waktu habis. Coba lagi.' }; } })
      .catch(function () { return { ok: false, teks: 'Tidak tersambung ke server.' }; });
  }

  function kartu(u) {
    var k = el('div', 'usulan');
    k.append(el('div', 'usulan-judul', '📝 ' + u.judul));
    if (u.baris && u.baris.length) {
      var dl = el('dl');
      u.baris.forEach(function (b) { dl.append(el('dt', null, b[0]), el('dd', null, b[1])); });
      k.append(dl);
    }
    var aksi = el('div', 'usulan-aksi'), simpan = el('button', 'utama', '💾 Simpan'), batal = el('button', null, 'Batal'), hasil = el('div', 'usulan-hasil');
    simpan.type = batal.type = 'button';
    simpan.onclick = function () {
      simpan.disabled = batal.disabled = true; simpan.textContent = '⏳ Menyimpan…';
      kirim({ aksi: 'simpan_usulan', id: u.id }).then(function (r) {
        if (r.ok) {
          aksi.remove(); hasil.className = 'usulan-hasil ok'; hasil.textContent = '✅ ' + r.teks + ' ';
          if (r.tautan) { var a = el('a', null, 'Buka ›'); a.href = r.tautan; hasil.append(a); }
        } else { simpan.disabled = batal.disabled = false; simpan.textContent = '💾 Simpan'; hasil.className = 'usulan-hasil galat'; hasil.textContent = r.teks || 'Gagal.'; }
      });
    };
    batal.onclick = function () { kirim({ aksi: 'batal_usulan', id: u.id }); aksi.remove(); hasil.textContent = 'Dibatalkan.'; };
    aksi.append(simpan, batal); k.append(aksi, hasil);
    return k;
  }

  function kecilkan(berkas) {
    return new Promise(function (ok, gagal) {
      var url = URL.createObjectURL(berkas), img = new Image();
      img.onload = function () {
        var s = Math.min(1, 1800 / Math.max(img.width, img.height)), c = document.createElement('canvas');
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
    foto.forEach(function (f, i) { var w = el('span', 'mini'), im = document.createElement('img'), x = el('button', null, '×'); im.src = f; x.type = 'button'; x.onclick = function () { foto.splice(i, 1); gambarFoto(); }; w.append(im, x); pf.append(w); });
  }
  Array.prototype.forEach.call(document.querySelectorAll('.chat-foto'), function (inp) {
    inp.addEventListener('change', function () {
      var b = Array.prototype.slice.call(inp.files, 0, 3 - foto.length); inp.value = '';
      Promise.all(b.map(kecilkan)).then(function (f) { foto = foto.concat(f); gambarFoto(); });
    });
  });

  teks.addEventListener('input', function () { teks.style.height = 'auto'; teks.style.height = Math.min(160, teks.scrollHeight) + 'px'; });
  teks.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey && window.innerWidth > 700) { e.preventDefault(); form.requestSubmit(); } });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var isi = teks.value.trim();
    if (!isi && !foto.length) return;
    var g = el('div', 'gelembung saya'); g.textContent = isi;
    foto.forEach(function (f) { var im = document.createElement('img'); im.src = f; im.className = 'foto-chat'; g.append(im); });
    chat.append(g);
    var tunggu = el('div', 'gelembung ai menunggu', 'Sedang memeriksa data…'); chat.append(tunggu); gulir();
    var kirimFoto = foto; foto = []; gambarFoto(); teks.value = ''; teks.style.height = 'auto'; tombol.disabled = true;
    kirim({ aksi: 'chat', pesan: isi, foto: kirimFoto }).then(function (r) {
      tunggu.remove(); tombol.disabled = false;
      var b = el('div', 'gelembung ai' + (r.ok ? '' : ' galat')); b.innerHTML = md(r.teks || r.galat || '');
      if (r.oleh) b.append(el('small', 'oleh', r.oleh));
      chat.append(b);
      (r.usulan || []).forEach(function (u) { chat.append(kartu(u)); });
      gulir(); teks.focus();
    });
  });
  document.getElementById('chat-baru').addEventListener('click', function () {
    kirim({ aksi: 'chat_baru' }).then(function () { location.reload(); });
  });
})();

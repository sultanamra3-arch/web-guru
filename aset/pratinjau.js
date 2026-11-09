/* Pratinjau statis SiGuru (GitHub Pages): tanpa server, data contoh. */
(function () {
  var CODESPACES = 'https://codespaces.new/sultanamra3-arch/web-guru?quickstart=1';
  function namaFile(q) {
    var u = new URLSearchParams(q), p = u.get('p') || 'beranda', ks = [];
    u.forEach(function (v, k) { if (k !== 'p' && k !== '_t' && v !== '') ks.push([k, v]); });
    ks.sort(function (a, b) { return a[0] < b[0] ? -1 : a[0] > b[0] ? 1 : 0; });
    var n = p + ks.map(function (x) { return '__' + x[0] + '-' + x[1]; }).join('');
    return n.replace(/[^A-Za-z0-9_-]+/g, '-') + '.html';
  }
  function kabar(t) {
    var d = document.getElementById('demo-kabar');
    if (!d) { d = document.createElement('div'); d.id = 'demo-kabar'; document.body.appendChild(d); }
    d.textContent = t; d.className = 'tampil';
    clearTimeout(kabar.w); kabar.w = setTimeout(function () { d.className = ''; }, 4000);
  }
  var asliFetch = window.fetch;
  window.fetch = function (url, o) {
    var mp = String(url).match(/api_ujian&aksi=pantau&id=(\d+)/);
    if (mp) return asliFetch.call(this, 'pantau-' + mp[1] + '.json');
    if (String(url).indexOf('api_ai') >= 0 || String(url).indexOf('api_ujian') >= 0) {
      return new Promise(function (ok) { setTimeout(function () {
        ok(new Response(JSON.stringify({ ok: false, galat: 'Ini pratinjau: AI tidak aktif. Coba di Codespaces atau setelah SiGuru dipasang di hosting.' }), { headers: { 'Content-Type': 'application/json' } }));
      }, 600); });
    }
    return asliFetch.apply(this, arguments);
  };
  var AI = ['form-soal-ai', 'form-dokumen-ai', 'form-revisi', 'form-koreksi'];
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (AI.indexOf(f.id) >= 0) return;
    e.preventDefault(); e.stopPropagation();
    if ((f.getAttribute('method') || 'get').toLowerCase() === 'get') {
      var q = new URLSearchParams(new FormData(f)).toString(), tujuan = namaFile(q);
      fetch(tujuan, { method: 'HEAD' }).then(function (r) {
        if (r.ok) location.href = tujuan; else kabar('Di pratinjau hanya contoh tertentu yang tersedia.');
      });
      return;
    }
    kabar('Ini pratinjau: data tidak disimpan.');
  }, true);
  document.addEventListener('DOMContentLoaded', function () {
    var b = document.createElement('div');
    b.id = 'demo-pita';
    b.innerHTML = '<b>Pratinjau SiGuru</b> · data contoh, tidak bisa menyimpan, AI nonaktif · <a href="' + CODESPACES + '" target="_blank" rel="noopener">Coba versi lengkap</a>';
    document.body.insertBefore(b, document.body.firstChild);
    var s = document.createElement('style');
    s.textContent = '#demo-pita{background:#F2C230;color:#1B1D5E;font:600 13px/1.4 Quicksand,sans-serif;padding:6px 12px;text-align:center}#demo-pita a{color:#1B1D5E}' +
      '#demo-kabar{position:fixed;left:50%;bottom:90px;transform:translateX(-50%);background:#1B1D5E;color:#fff;padding:10px 16px;border-radius:12px;font:600 14px Quicksand,sans-serif;opacity:0;transition:opacity .2s;pointer-events:none;z-index:99;max-width:90vw;text-align:center}#demo-kabar.tampil{opacity:1}' +
      '@media print{#demo-pita{display:none}}';
    document.head.appendChild(s);
  });
})();

/* SiGuru · isi semua elemen [data-qr="alamat"] dengan gambar QR (perlu aset/lib/qrcode.js dimuat lebih dulu) */
(function () {
  'use strict';
  Array.prototype.forEach.call(document.querySelectorAll('[data-qr]'), function (el) {
    try {
      var q = qrcode(0, 'M'); q.addData(el.dataset.qr); q.make();
      el.innerHTML = q.createSvgTag({ cellSize: 4, margin: 2, scalable: true });
    } catch (e) {}
  });
  // tombol "Tunjukkan QR": buka/tutup panel QR besar
  Array.prototype.forEach.call(document.querySelectorAll('[data-buka-qr]'), function (b) {
    var p = document.getElementById(b.dataset.bukaQr);
    if (!p) return;
    b.addEventListener('click', function () { p.hidden = !p.hidden; b.setAttribute('aria-expanded', String(!p.hidden)); });
  });
})();

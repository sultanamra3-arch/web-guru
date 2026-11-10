<?php
/* Aplikasi Android "SiGuru Admin" (khusus admin): petunjuk pasang + unduh APK. */
declare(strict_types=1);

if (!adalah_admin()) ke('?p=lainnya');
$f = __DIR__ . '/../apk/SiGuru-Admin.apk';
if (get('unduh') && is_file($f)) {
    header('Content-Type: application/vnd.android.package-archive');
    header('Content-Disposition: attachment; filename="SiGuru-Admin.apk"');
    header('Content-Length: ' . filesize($f));
    header('Cache-Control: no-store');
    readfile($f);
    exit;
}
kepala('Aplikasi Android', 'lainnya');
judul_baris('Aplikasi Android', '<a class="tombol" href="?p=lainnya">‹ Lainnya</a>');
?>
<div class="kartu">
  <p style="margin-top:0"><b>SiGuru Admin</b> untuk HP Android (7.0 ke atas). Semua aplikasi sekolah (SiGuru, SiPustaka, SLiMS, Kartu, Buku tamu, Katalog)
    terbuka <b>layar penuh tanpa bilah alamat web</b>, dan tombol kembali HP berfungsi seperti aplikasi biasa. Isinya selalu terbaru — APK tidak perlu dipasang ulang saat SiGuru diperbarui.</p>
  <?php if (is_file($f)): ?>
  <a class="tombol utama lebar" href="?p=apk&amp;unduh=1" data-tanpa-siap><?= ikon('unduh') ?> Unduh APK (<?= round(filesize($f) / 1048576, 1) ?> MB)</a>
  <?php else: ?><div class="pesan galat">Berkas APK belum ada di server.</div><?php endif; ?>
</div>
<div class="kartu">
  <h2 class="judul-kecil">Cara memasang</h2>
  <ol class="langkah-pasang">
    <li>Buka halaman ini <b>di HP Android</b>, tekan <b>Unduh APK</b>.</li>
    <li>Buka berkas <b>SiGuru-Admin.apk</b> dari notifikasi unduhan.</li>
    <li>Bila muncul peringatan, pilih <b>Setelan</b> → izinkan <b>Instal aplikasi tak dikenal</b> untuk Chrome, lalu kembali dan tekan <b>Instal</b>.</li>
    <li>Buka <b>SiGuru Admin</b> dari layar HP dan masuk dengan akun SiGuru.</li>
    <li>Boleh menghapus ikon SiGuru/SiPustaka lama di layar HP.</li>
  </ol>
  <p class="kecil redup">Hanya untuk admin. Bila suatu aplikasi masih menampilkan bilah alamat, berkas pengenal (<code>.well-known/assetlinks.json</code>) di folder aplikasi itu belum diunggah.</p>
</div>
<?php
kaki();

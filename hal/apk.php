<?php
/* Aplikasi Android "SiGuru" (semua guru): petunjuk pasang + unduh APK. */
declare(strict_types=1);

$f = __DIR__ . '/../apk/SiGuru.apk';
if (get('unduh') && is_file($f)) {
    header('Content-Type: application/vnd.android.package-archive');
    header('Content-Disposition: attachment; filename="SiGuru.apk"');
    header('Content-Length: ' . filesize($f));
    header('Cache-Control: no-store');
    readfile($f);
    exit;
}
kepala('Aplikasi Android', 'lainnya');
judul_baris('Aplikasi Android', '<a class="tombol" href="?p=lainnya">‹ Lainnya</a>');
?>
<div class="kartu">
  <p style="margin-top:0"><b>SiGuru</b> untuk HP Android (7.0 ke atas). SiGuru terbuka <b>layar penuh tanpa bilah alamat web</b>,
    tombol kembali HP berfungsi seperti aplikasi biasa, dan tombol cetak bisa langsung <b>Simpan sebagai PDF</b>.
    Isinya selalu terbaru — APK tidak perlu dipasang ulang saat SiGuru diperbarui.</p>
  <?php if (is_file($f)): ?>
  <a class="tombol utama lebar" href="?p=apk&amp;unduh=1" data-tanpa-siap><?= ikon('unduh') ?> Unduh APK (<?= round(filesize($f) / 1048576, 1) ?> MB)</a>
  <?php else: ?><div class="pesan galat">Berkas APK belum ada di server.</div><?php endif; ?>
</div>
<div class="kartu">
  <h2 class="judul-kecil">Cara memasang</h2>
  <ol class="langkah-pasang">
    <li>Buka halaman ini <b>di HP Android</b>, tekan <b>Unduh APK</b>.</li>
    <li>Buka berkas <b>SiGuru.apk</b> dari notifikasi unduhan.</li>
    <li>Bila muncul peringatan, pilih <b>Setelan</b> → izinkan <b>Instal aplikasi tak dikenal</b>, lalu kembali dan tekan <b>Instal</b>.</li>
    <li>Buka <b>SiGuru</b> dari layar HP dan masuk dengan akun SiGuru.</li>
    <li>Saat memakai kamera pertama kali, pilih <b>Izinkan</b>.</li>
  </ol>
  <p class="kecil redup">Sudah memasang versi sebelumnya? Cukup unduh lagi lalu tekan <b>Perbarui</b>. Bila gagal, hapus aplikasi lama dulu lalu pasang lagi.</p>
</div>
<?php
kaki();

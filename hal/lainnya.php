<?php
declare(strict_types=1);

kepala('Lainnya', 'lainnya');
?>
<h1>Lainnya</h1>
<ul class="daftar kartu menu-lainnya">
  <li><a href="?p=jadwal"><b>🗓 Jadwal pelajaran</b><small>Atur jadwal mengajar per hari</small></a></li>
  <li><a href="?p=sertifikat"><b>🏅 Sertifikat</b><small>Buat & cetak sertifikat untuk siswa atau peserta</small></a></li>
  <li><a href="?p=absensi_rekap"><b>📋 Rekap absensi</b><small>Rekap per bulan, unduh Excel</small></a></li>
  <li><a href="?p=profil"><b>👤 Profil & kata sandi</b><small><?= h(pengguna()['nama']) ?> (<?= h(pengguna()['username']) ?>)</small></a></li>
  <?php if (adalah_admin()): ?>
  <li><a href="?p=asisten"><b>✨ Asisten AI (admin)</b><small>Ubah datamu lewat chat: siswa, nilai, absensi, jadwal, soal… setiap perubahan dikonfirmasi</small></a></li>
  <li><a href="?p=akun"><b>👥 Akun guru</b><small>Tambah, ubah, nonaktifkan akun guru (khusus admin)</small></a></li>
  <li><a href="?p=pengaturan_ai"><b>⚙ Pengaturan AI</b><small>Kunci & model AI (dipakai bersama SiPustaka)</small></a></li>
  <?php endif; ?>
</ul>
<form method="post" action="?p=keluar"><?= input_token() ?><button class="lebar">Keluar</button></form>
<?php
kaki();

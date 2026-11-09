<?php
declare(strict_types=1);

kepala('Lainnya', 'lainnya');
?>
<h1>Lainnya</h1>
<ul class="daftar kartu menu-lainnya">
  <li><a href="?p=jadwal"><span class="ikon-kotak"><?= ikon('jadwal') ?></span><b>Jadwal pelajaran</b><small>Atur jadwal mengajar per hari</small></a></li>
  <li><a href="?p=sertifikat"><span class="ikon-kotak"><?= ikon('sertifikat') ?></span><b>Sertifikat</b><small>Buat & cetak sertifikat untuk siswa atau peserta</small></a></li>
  <li><a href="?p=absensi_rekap"><span class="ikon-kotak"><?= ikon('rekap') ?></span><b>Rekap absensi</b><small>Rekap per bulan, unduh Excel</small></a></li>
  <li><a href="?p=erapor"><span class="ikon-kotak"><?= ikon('nilai') ?></span><b>Nilai untuk e-Rapor</b><small>Nilai akhir + kalimat capaian, unduh Excel</small></a>
  <li><a href="?p=cadangan"><span class="ikon-kotak"><?= ikon('unduh') ?></span><b>Cadangan data</b><small>Unduh semua datamu (ZIP)</small></a>
  <li><a href="?p=profil"><span class="ikon-kotak"><?= ikon('profil') ?></span><b>Profil & kata sandi</b><small><?= h(pengguna()['nama']) ?> (<?= h(pengguna()['username']) ?>)</small></a></li>
  <?php if (adalah_admin()): ?>
  <li><a href="?p=asisten"><span class="ikon-kotak"><?= ikon('asisten') ?></span><b>Asisten AI (admin)</b><small>Ubah datamu lewat chat: siswa, nilai, absensi, jadwal, soal… setiap perubahan dikonfirmasi</small></a></li>
  <li><a href="?p=akun"><span class="ikon-kotak"><?= ikon('akun') ?></span><b>Akun guru</b><small>Tambah, ubah, nonaktifkan akun guru (khusus admin)</small></a></li>
  <li><a href="?p=pengaturan_ai"><span class="ikon-kotak"><?= ikon('pengaturan') ?></span><b>Pengaturan AI</b><small>Kunci & model AI sekolah</small></a></li>
  <?php endif; ?>
</ul>
<form method="post" action="?p=keluar"><?= input_token() ?><button class="lebar">Keluar</button></form>
<?php
kaki();

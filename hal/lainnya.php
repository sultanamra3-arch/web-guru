<?php
declare(strict_types=1);

kepala('Lainnya', 'lainnya');
?>
<h1>Lainnya</h1>
<?php
$grup = [
    'Mengajar' => [['jadwal', 'jadwal', 'Jadwal pelajaran', 'Atur jadwal mengajar per hari'], ['sertifikat', 'sertifikat', 'Sertifikat', 'Buat & cetak sertifikat untuk siswa atau peserta'],
        ['ujian', 'ujian', 'Ujian online', 'Semua ujian & latihan'], ['tugas', 'tugas', 'Tugas online', 'Semua tugas'], ['kuis', 'kuis', 'Kuis cepat', 'Semua kuis']],
    'Laporan' => [['absensi_rekap', 'rekap', 'Rekap absensi', 'Rekap per bulan, unduh Excel'], ['erapor', 'nilai', 'Nilai untuk e-Rapor', 'Nilai akhir + kalimat capaian, unduh Excel']],
    'Akun dan data' => [['profil', 'profil', 'Profil, kata sandi, ukuran huruf', pengguna()['nama'] . ' (' . pengguna()['username'] . ')'], ['cadangan', 'unduh', 'Cadangan data', 'Unduh semua datamu (ZIP)'],
        ['apk', 'unduh', 'Aplikasi Android (APK)', 'SiGuru di HP: layar penuh, tanpa bilah alamat']],
];
if (adalah_admin()) $grup['Khusus admin'] = [['akun', 'akun', 'Akun guru', 'Tambah, ubah, nonaktifkan akun guru'], ['asisten', 'asisten', 'Asisten AI', 'Ubah datamu lewat chat, setiap perubahan dikonfirmasi'],
    ['pengaturan_ai', 'pengaturan', 'Pengaturan AI', 'Kunci & model AI sekolah']];
foreach ($grup as $judul => $isi): ?>
<h2 class="grup-judul"><?= h($judul) ?></h2>
<ul class="daftar kartu menu-lainnya">
  <?php foreach ($isi as [$p, $ik, $t, $k]): ?><li><a href="?p=<?= $p ?>"><span class="ikon-kotak"><?= ikon($ik) ?></span><b><?= h($t) ?></b><small><?= h($k) ?></small></a></li><?php endforeach; ?>
  <?php if ($judul === 'Akun dan data'): ?>
  <li><form method="post" action="?p=keluar" class="keluar-baris"><?= input_token() ?><button><span class="ikon-kotak"><?= ikon('keluar') ?></span><b>Keluar</b></button></form></li>
  <?php endif; ?>
</ul>
<?php endforeach; ?>
<?php
kaki();

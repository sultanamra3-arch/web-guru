<?php
declare(strict_types=1);

$kelas = daftar_kelas();
$jumlah = [];
foreach (semua("SELECT inst_name, COUNT(*) n, SUM(gender = 1) l FROM member WHERE inst_name <> '' GROUP BY inst_name") as $r) $jumlah[$r['inst_name']] = $r;
$tanpa = (int)nilai("SELECT COUNT(*) FROM member m JOIN mst_member_type t ON t.member_type_id = m.member_type_id
    WHERE (m.inst_name IS NULL OR m.inst_name = '') AND t.member_type_name NOT LIKE '%guru%'");
$saya = q('SELECT DISTINCT kelas FROM guru_jadwal WHERE guru_id = ?', [uid()])->fetchAll(PDO::FETCH_COLUMN);

kepala('Kelas', 'kelas');
judul_baris('Kelas & siswa');
?>
<div class="tombol-besar">
  <a class="utama" href="?p=absensi">✅ Isi absensi</a>
  <a href="?p=penilaian">📊 Penilaian</a>
  <a href="?p=absensi_rekap">📋 Rekap absensi</a>
  <a href="?p=jadwal">🗓 Jadwal</a>
</div>
<?php if ($tanpa > 0): ?>
<div class="pesan info"><?= $tanpa ?> siswa belum punya kelas. Data siswa diambil dari anggota perpustakaan; isi kelasnya di
  <a href="https://kelola.otomasismpn6keritang.my.id/?p=anggota_kelas" target="_blank" rel="noopener">SiPustaka → Isi kelas sekaligus</a>.</div>
<?php endif; ?>
<?php if (!$kelas): ?>
  <div class="pesan peringatan">Belum ada kelas. Isi kelas siswa terlebih dahulu di SiPustaka.</div>
<?php endif; ?>
<ul class="daftar kartu">
<?php foreach ($kelas as $k): $j = $jumlah[$k] ?? ['n' => 0, 'l' => 0]; ?>
  <li>
    <div class="eks-atas"><b>Kelas <?= h($k) ?></b> <?= in_array($k, $saya, true) ? '<span class="lencana">kelas saya</span>' : '' ?>
      <small><?= (int)$j['n'] ?> siswa · L <?= (int)$j['l'] ?> · P <?= (int)$j['n'] - (int)$j['l'] ?></small></div>
    <div class="eks-aksi">
      <a class="tombol kecil" href="?p=absensi&amp;kelas=<?= h(rawurlencode($k)) ?>">Absensi</a>
      <a class="tombol kecil" href="?p=nilai_rekap&amp;kelas=<?= h(rawurlencode($k)) ?>">Nilai</a>
      <a class="tombol kecil" href="?p=absensi_rekap&amp;kelas=<?= h(rawurlencode($k)) ?>">Rekap</a>
    </div>
  </li>
<?php endforeach; ?>
</ul>
<?php
kaki();

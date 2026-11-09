<?php
declare(strict_types=1);

$hari = (int)date('N');
$jadwal = $hari <= 6 ? semua('SELECT * FROM guru_jadwal WHERE guru_id = ? AND hari = ? ORDER BY jam_ke, mulai', [uid(), $hari]) : [];
$st = satu('SELECT
    (SELECT COUNT(*) FROM guru_soal WHERE guru_id = ?) soal,
    (SELECT COUNT(*) FROM guru_dokumen WHERE guru_id = ?) dokumen,
    (SELECT COUNT(*) FROM guru_penilaian WHERE guru_id = ?) penilaian,
    (SELECT COUNT(DISTINCT kelas, tanggal) FROM guru_absensi WHERE guru_id = ?) absen', [uid(), uid(), uid(), uid()]);
$dok = semua('SELECT id, jenis, judul, diubah FROM guru_dokumen WHERE guru_id = ? ORDER BY diubah DESC LIMIT 4', [uid()]);

kepala('Beranda', 'beranda');
?>
<h1>Halo, <?= h(pengguna()['nama']) ?></h1>
<p class="redup" style="margin-top:-.5rem"><?= HARI[$hari] ?? 'Minggu' ?>, <?= tgl_panjang(hari_ini()) ?></p>

<div class="tombol-besar">
  <a class="utama" href="?p=soal_ai"><?= ikon('ai') ?><span>Buat soal</span></a>
  <a href="?p=dokumen_buat&amp;jenis=modul"><?= ikon('modul') ?><span>Modul ajar</span></a>
  <a href="?p=kisi"><?= ikon('kisi') ?><span>Kisi-kisi</span></a>
  <a href="?p=dokumen_buat&amp;jenis=presentasi"><?= ikon('presentasi') ?><span>Presentasi</span></a>
  <a href="?p=absensi"><?= ikon('absensi') ?><span>Absensi</span></a>
  <a href="?p=penilaian"><?= ikon('nilai') ?><span>Nilai</span></a>
</div>

<div class="kartu">
  <h2>Jadwal hari ini</h2>
  <?php if (!$jadwal): ?>
    <p class="redup">Tidak ada jadwal hari ini. <a href="?p=jadwal">Atur jadwal pelajaran →</a></p>
  <?php else: ?>
  <ul class="daftar">
    <?php foreach ($jadwal as $j): ?>
      <li><b>Jam ke-<?= (int)$j['jam_ke'] ?><?= $j['mulai'] ? ' · ' . substr($j['mulai'], 0, 5) . '–' . substr((string)$j['selesai'], 0, 5) : '' ?></b>
        <small><?= h($j['mapel']) ?> · Kelas <?= h($j['kelas']) ?><?= $j['ruang'] ? ' · ' . h($j['ruang']) : '' ?>
          · <a href="?p=absensi&amp;kelas=<?= h(rawurlencode($j['kelas'])) ?>&amp;mapel=<?= h(rawurlencode($j['mapel'])) ?>">Absen</a></small></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</div>

<div class="statistik">
  <a href="?p=soal"><b><?= (int)$st['soal'] ?></b>soal</a>
  <a href="?p=perangkat"><b><?= (int)$st['dokumen'] ?></b>perangkat</a>
  <a href="?p=penilaian"><b><?= (int)$st['penilaian'] ?></b>penilaian</a>
  <a href="?p=absensi_rekap"><b><?= (int)$st['absen'] ?></b>absensi</a>
</div>

<?php if ($dok): ?>
<div class="kartu">
  <h2>Perangkat terakhir</h2>
  <ul class="daftar">
  <?php foreach ($dok as $d): ?>
    <li><a href="?p=<?= $d['jenis'] === 'presentasi' ? 'presentasi' : 'dokumen_lihat' ?>&amp;id=<?= (int)$d['id'] ?>"><b><?= h($d['judul']) ?></b>
      <small><?= h(JENIS_DOKUMEN[$d['jenis']] ?? $d['jenis']) ?> · <?= tgl($d['diubah']) ?></small></a></li>
  <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
<?php if (!ai_siap()): ?>
  <div class="pesan peringatan">Fitur AI belum aktif: kunci AI belum ditemukan. <?= adalah_admin() ? '<a href="?p=pengaturan_ai">Atur kunci AI →</a>' : 'Hubungi admin.' ?></div>
<?php endif; ?>
<?php
kaki();

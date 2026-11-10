<?php
/* Halaman satu kelas (v1.44): ringkasan, tindakan cepat, ujian & tugas kelas ini, siswa yang perlu perhatian. */
declare(strict_types=1);

$kelas = (string)get('kelas');
if (!kelas_valid($kelas)) { pesan('Kelas tidak ditemukan.', 'galat'); ke('?p=kelas'); }
$q = h(rawurlencode($kelas));
$siswa = siswa_kelas($kelas);
$nama = array_column($siswa, 'member_name', 'member_id');
$bulan = date('Y-m-01');

// kehadiran bulan ini (hanya hari yang sudah diisi)
$abs = satu("SELECT COUNT(*) n, SUM(status = 'H') h FROM guru_absensi WHERE guru_id = ? AND kelas = ? AND tanggal >= ?", [uid(), $kelas, $bulan]);
$hadir = (int)$abs['n'] ? round((int)$abs['h'] / (int)$abs['n'] * 100) : null;
// rata-rata nilai semua penilaian kelas ini
$rata = nilai('SELECT ROUND(AVG(n.nilai), 1) FROM guru_nilai n JOIN guru_penilaian p ON p.id = n.penilaian_id WHERE p.guru_id = ? AND p.kelas = ? AND n.nilai IS NOT NULL', [uid(), $kelas]);

// ujian & tugas kelas ini (terbaru)
$kegiatan = [];
foreach (semua("SELECT id, judul, status, mode, tindak, dibuat FROM guru_ujian WHERE guru_id = ? AND kelas = ? ORDER BY id DESC LIMIT 6", [uid(), $kelas]) as $u) {
    $kegiatan[] = ['w' => (string)$u['dibuat'], 'judul' => $u['judul'], 'jenis' => ($u['tindak'] ?: ($u['mode'] === 'latihan' ? 'Latihan' : 'Ujian')),
        'status' => $u['status'], 'url' => '?p=ujian_lihat&id=' . (int)$u['id']];
}
foreach (semua("SELECT id, judul, status, kelompok, batas, dibuat FROM guru_tugas WHERE guru_id = ? AND kelas = ? ORDER BY id DESC LIMIT 6", [uid(), $kelas]) as $t) {
    $kegiatan[] = ['w' => (string)$t['dibuat'], 'judul' => $t['judul'], 'jenis' => $t['kelompok'] ? 'Tugas kelompok' : 'Tugas',
        'status' => $t['status'], 'url' => '?p=tugas_lihat&id=' . (int)$t['id'], 'batas' => $t['batas']];
}
usort($kegiatan, fn($a, $b) => strcmp($b['w'], $a['w']));
$kegiatan = array_slice($kegiatan, 0, 6);

// perlu perhatian: alpa bulan ini & tugas individu (30 hari terakhir) yang tidak dikirim
$catatan = [];
foreach (semua("SELECT member_id, COUNT(*) n FROM guru_absensi WHERE guru_id = ? AND kelas = ? AND tanggal >= ? AND status = 'A' GROUP BY member_id", [uid(), $kelas, $bulan]) as $r) {
    if (isset($nama[$r['member_id']])) $catatan[$r['member_id']][] = 'alpa ' . (int)$r['n'] . '× bulan ini';
}
$tugas = semua("SELECT id FROM guru_tugas WHERE guru_id = ? AND kelas = ? AND kelompok = 0 AND dibuat >= ? AND (status = 'tutup' OR (batas IS NOT NULL AND batas < ?))",
    [uid(), $kelas, date('Y-m-d H:i:s', time() - 30 * 86400), sekarang()]);
if ($tugas) {
    $ids = implode(',', array_map(fn($t) => (int)$t['id'], $tugas));
    $kirim = [];
    foreach (semua("SELECT kode, COUNT(*) n FROM guru_tugas_kirim WHERE tugas_id IN ($ids) AND dikirim IS NOT NULL GROUP BY kode") as $r) $kirim[$r['kode']] = (int)$r['n'];
    foreach ($nama as $kd => $_) {
        $belum = count($tugas) - ($kirim[$kd] ?? 0);
        if ($belum > 0) $catatan[$kd][] = $belum . ' tugas tidak dikirim';
    }
}
uksort($catatan, fn($a, $b) => count($catatan[$b]) <=> count($catatan[$a]));

kepala('Kelas ' . $kelas, 'kelas');
judul_baris('Kelas ' . h($kelas), '<a class="tombol" href="?p=kelas">‹ Kelas</a>');
?>
<div class="kartu">
  <div class="statistik tiga">
    <span><b><?= count($siswa) ?></b>siswa</span>
    <span><b><?= $hadir === null ? '-' : $hadir . '%' ?></b>hadir bulan ini</span>
    <span><b><?= $rata !== null && $rata !== false ? h(str_replace('.', ',', (string)$rata)) : '-' ?></b>rata-rata nilai</span>
  </div>
</div>

<div class="kartu kartu-kini">
  <small class="kini-label">Tindakan cepat</small>
  <div class="kini-tombol" style="margin-top:.4rem">
    <a class="utama" href="?p=absensi&amp;kelas=<?= $q ?>"><?= ikon('absensi') ?>Absen</a>
    <a href="?p=tugas_form&amp;kelas=<?= $q ?>"><?= ikon('tugas') ?>Tugas</a>
    <a href="?p=kuis&amp;kelas=<?= $q ?>"><?= ikon('kuis') ?>Kuis</a>
    <a href="?p=ujian&amp;kelas=<?= $q ?>"><?= ikon('ujian') ?>Ujian</a>
  </div>
</div>

<div class="kartu">
  <h2 class="judul-kecil">Ujian dan tugas kelas ini</h2>
  <?php if (!$kegiatan): ?><p class="redup kecil">Belum ada ujian atau tugas untuk kelas ini.</p><?php else: ?>
  <ul class="daftar">
  <?php foreach ($kegiatan as $g): ?>
    <li><a href="<?= h($g['url']) ?>"><b><?= h($g['judul']) ?></b>
      <small><?= h(ucfirst((string)$g['jenis'])) ?><?= !empty($g['batas']) ? ' · batas ' . h(ujian_waktu_teks((string)$g['batas'])) : '' ?>
        · <span class="lencana st-<?= h($g['status']) ?>"><?= ['buka' => 'berjalan', 'tutup' => 'selesai', 'draf' => 'draf'][$g['status']] ?? h($g['status']) ?></span></small></a></li>
  <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</div>

<?php if ($catatan): ?>
<div class="kartu">
  <h2 class="judul-kecil">Perlu perhatian</h2>
  <ul class="baris-tindak">
  <?php foreach (array_slice($catatan, 0, 6, true) as $kd => $c): ?>
    <li><span class="bt-ikon"><?= ikon('peringatan') ?></span><span class="bt-isi"><b><?= h($nama[$kd] ?? (string)$kd) ?></b><small><?= h(implode(' · ', $c)) ?></small></span></li>
  <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="kartu">
  <ul class="daftar menu-tautan">
    <li><a href="?p=siswa&amp;kelas=<?= $q ?>"><b>Daftar siswa</b><small>Tambah, ubah, nomor absen</small></a></li>
    <li><a href="?p=absensi_rekap&amp;kelas=<?= $q ?>"><b>Rekap absensi</b><small>Per bulan, unduh Excel</small></a></li>
    <li><a href="?p=nilai_rekap&amp;kelas=<?= $q ?>"><b>Rekap nilai</b><small>Semua penilaian kelas ini</small></a></li>
    <li><a href="?p=kelompok&amp;kelas=<?= $q ?>"><b>Kelompok belajar</b><small>Bagi acak atau atur sendiri</small></a></li>
  </ul>
</div>
<?php
kaki();

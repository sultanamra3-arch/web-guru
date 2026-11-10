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

// ---- yang sedang berjalan: kuis, ujian/latihan yang dibuka, tugas yang dibuka / masih perlu dinilai ----
$berjalan = [];
foreach (semua("SELECT id, judul, kelas, status, (SELECT COUNT(*) FROM guru_kuis_peserta p WHERE p.kuis_id = k.id) n
                FROM guru_kuis k WHERE guru_id = ? AND status <> 'selesai' AND dibuat >= ? ORDER BY dibuat DESC", [uid(), date('Y-m-d H:i:s', time() - 86400)]) as $k) {
    $berjalan[] = ['ikon' => 'kuis', 'judul' => 'Kuis: ' . $k['judul'], 'kelas' => $k['kelas'],
        'ket' => ($k['status'] === 'lobi' ? 'Ruang tunggu' : 'Sedang berjalan') . ' · ' . (int)$k['n'] . ' peserta', 'url' => '?p=kuis_main&id=' . (int)$k['id'], 'tombol' => 'Buka'];
}
foreach (semua("SELECT u.*, (SELECT COUNT(*) FROM guru_ujian_peserta p WHERE p.ujian_id = u.id AND p.selesai IS NOT NULL) n_selesai,
                (SELECT COUNT(*) FROM guru_ujian_peserta p WHERE p.ujian_id = u.id AND p.mulai IS NOT NULL AND p.selesai IS NULL) n_jalan
                FROM guru_ujian u WHERE guru_id = ? AND status = 'buka' ORDER BY id DESC", [uid()]) as $u) {
    $n = count(ujian_siswa($u));
    $berjalan[] = ['ikon' => 'ujian', 'judul' => (ujian_latihan($u) && empty($u['tindak']) ? 'Latihan: ' : '') . $u['judul'], 'kelas' => $u['kelas'],
        'ket' => (int)$u['n_selesai'] . ($n ? '/' . $n : '') . ' mengumpulkan' . ((int)$u['n_jalan'] ? ' · ' . (int)$u['n_jalan'] . ' sedang mengerjakan' : ''),
        'url' => '?p=ujian_lihat&id=' . (int)$u['id'], 'tombol' => 'Pantau'];
}
foreach (semua("SELECT t.*, (SELECT COUNT(*) FROM guru_tugas_kirim k WHERE k.tugas_id = t.id AND k.dikirim IS NOT NULL) n_kirim,
                (SELECT COUNT(*) FROM guru_tugas_kirim k WHERE k.tugas_id = t.id AND k.dikirim IS NOT NULL AND k.status = 'kirim') n_nilai
                FROM guru_tugas t WHERE guru_id = ? ORDER BY (t.batas IS NULL), t.batas, t.id DESC", [uid()]) as $t) {
    if ($t['status'] !== 'buka' && !(int)$t['n_nilai']) continue;
    $n = tugas_kelompok($t) ? count(tugas_daftar_kelompok($t)) : count(siswa_kelas($t['kelas'], $t['tahun'] ?: null));
    $ket = (int)$t['n_kirim'] . ($n ? '/' . $n : '') . (tugas_kelompok($t) ? ' kelompok' : '') . ' terkumpul';
    if ((int)$t['n_nilai']) $ket .= ' · <b>' . (int)$t['n_nilai'] . ' perlu dinilai</b>';
    if ($t['status'] !== 'buka') $ket .= ' · ditutup';
    elseif ($t['batas']) {
        $hari = (int)floor((strtotime(substr($t['batas'], 0, 10)) - strtotime(hari_ini())) / 86400);
        $ket .= strtotime($t['batas']) < time() ? ' · <span class="teks-merah">lewat batas</span>'
            : ' · batas ' . ($hari <= 0 ? 'hari ini ' . substr($t['batas'], 11, 5) : ($hari === 1 ? 'besok' : $hari . ' hari lagi'));
    }
    $berjalan[] = ['ikon' => 'tugas', 'judul' => $t['judul'], 'kelas' => $t['kelas'], 'ket' => $ket, 'html' => true,
        'url' => '?p=tugas_lihat&id=' . (int)$t['id'], 'tombol' => (int)$t['n_nilai'] ? 'Nilai' : 'Lihat'];
}

kepala('Beranda', 'beranda');
?>
<h1>Selamat datang, <?= h(pengguna()['nama']) ?></h1>
<p class="redup" style="margin-top:-.5rem"><?= HARI[$hari] ?? 'Minggu' ?>, <?= tgl_panjang(hari_ini()) ?></p>

<div class="tombol-besar">
  <a class="utama" href="?p=soal_ai"><?= ikon('ai') ?><span>Buat soal</span></a>
  <a href="?p=dokumen_buat&amp;jenis=modul"><?= ikon('modul') ?><span>Modul ajar</span></a>
  <a href="?p=kisi"><?= ikon('kisi') ?><span>Kisi-kisi</span></a>
  <a href="?p=dokumen_buat&amp;jenis=presentasi"><?= ikon('presentasi') ?><span>Presentasi</span></a>
  <a href="?p=absensi"><?= ikon('absensi') ?><span>Absensi</span></a>
  <a href="?p=penilaian"><?= ikon('nilai') ?><span>Nilai</span></a>
</div>

<?php if ($berjalan): ?>
<div class="kartu berjalan">
  <h2>Sedang berjalan <span class="lencana"><?= count($berjalan) ?></span></h2>
  <ul class="daftar">
    <?php foreach (array_slice($berjalan, 0, 5) as $b): ?>
    <li><span class="berjalan-ikon"><?= ikon($b['ikon']) ?></span>
      <span class="berjalan-isi"><b><?= h($b['judul']) ?></b><small>Kelas <?= h($b['kelas']) ?> · <?= !empty($b['html']) ? $b['ket'] : h($b['ket']) ?></small></span>
      <a class="tombol kecil" href="<?= h($b['url']) ?>"><?= h($b['tombol']) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <?php if (count($berjalan) > 5): ?><p class="kecil" style="margin:.4rem 0 0">Lihat semua: <a href="?p=kuis">Kuis</a> · <a href="?p=ujian">Ujian</a> · <a href="?p=tugas">Tugas</a></p><?php endif; ?>
</div>
<?php endif; ?>

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

<?php
/* Lembar ujian siswa. */
declare(strict_types=1);

$id = (int)get('id');
$u = ujian_ambil($id, false);
$kode = $_SESSION['ujian'][$id] ?? null;
if (!$u || !$kode) ke('?p=u' . ($u ? '&k=' . $u['kode'] : ''));
$p = satu('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ?', [$id, $kode]);
$alat = hash('sha256', (string)($_COOKIE['sgu_alat'] ?? ''));
if (!$p || !$p['mulai'] || !$p['perangkat'] || !hash_equals((string)$p['perangkat'], $alat)) {
    unset($_SESSION['ujian'][$id]);
    ke('?p=u&k=' . $u['kode']);
}
// waktu habis / ujian ditutup → kumpulkan
if (!$p['selesai'] && ($u['status'] === 'tutup' || strtotime((string)$p['batas']) < time() - 20)) {
    q('UPDATE guru_ujian_peserta SET selesai = ? WHERE ujian_id = ? AND kode = ?', [$u['status'] === 'tutup' ? sekarang() : $p['batas'], $id, $kode]);
    ujian_simpan_hitung($u, $p);
    $p = satu('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ?', [$id, $kode]);
}
$nama = nama_siswa($kode);

kepala($u['judul'], '', true);
if ($p['selesai']):
    $h = ujian_hitung($u, $p);
    $jw = array_filter(json_decode((string)$p['jawaban'], true) ?: [], fn($v) => (string)$v !== '');
?>
<div class="siswa-wadah">
  <header class="siswa-kop"><img src="aset/logo.png" alt=""><div><small><?= h((string)k('sekolah', '')) ?></small><b>Ujian Online</b></div></header>
  <div class="kartu selesai-ujian">
    <div class="ikon-selesai">✅</div>
    <h2>Jawaban terkumpul</h2>
    <p><b><?= h($nama) ?></b><br><?= h($u['judul']) ?></p>
    <p class="redup"><?= count($jw) ?> dari <?= count(ujian_soal($u)) ?> soal dijawab · dikumpulkan pukul <?= h(substr((string)$p['selesai'], 11, 5)) ?></p>
    <?php if ($u['tampil_nilai']): ?>
      <?php if ($h['perlu']): ?><p class="pesan">Nilai pilihan ganda sudah dihitung. Soal uraian sedang diperiksa guru, nilai akhir diumumkan kemudian.</p>
      <?php else: ?><div class="nilai-siswa"><?= h((string)$h['nilai']) ?></div><p class="redup">nilai kamu</p><?php endif; ?>
    <?php else: ?><p>Nilai akan diumumkan oleh guru.</p><?php endif; ?>
    <p class="kecil redup">Halaman ini boleh ditutup.</p>
  </div>
</div>
<?php
    kaki(true);
    return;
endif;

$data = [
    'id' => $id, 'nama' => $nama, 'judul' => $u['judul'],
    'soal' => ujian_data_siswa($u, $p),
    'jawaban' => (object)(json_decode((string)$p['jawaban'], true) ?: []),
    'ragu' => (object)(json_decode((string)$p['ragu'], true) ?: []),
    'nomor' => (int)$p['nomor'],
    'sisa' => max(0, strtotime((string)$p['batas']) - time()),
    'token' => token(),
];
?>
<div id="ujian" class="ujian-wadah">
  <header class="ujian-kop">
    <div class="ujian-nama"><b><?= h($nama) ?></b><small><?= h($u['judul']) ?></small></div>
    <div class="ujian-waktu" id="waktu">--:--</div>
  </header>
  <div class="ujian-status" id="status-simpan"></div>
  <nav class="ujian-nomor" id="nomor"></nav>
  <div class="ujian-soal" id="soal"></div>
  <footer class="ujian-bawah">
    <button type="button" id="sebelum">‹ Kembali</button>
    <button type="button" id="ragu" class="tombol-ragu">Ragu-ragu</button>
    <button type="button" id="sesudah" class="utama">Lanjut ›</button>
  </footer>
</div>
<div class="ujian-tirai" id="tirai-keluar" hidden>
  <div class="kartu"><h3>⚠ Kamu keluar dari halaman ujian</h3><p>Kejadian ini tercatat dan terlihat oleh guru. Tetap di halaman ini sampai selesai.</p>
    <button type="button" class="utama lebar" id="tutup-tirai">Lanjut mengerjakan</button></div>
</div>
<div class="ujian-tirai" id="tirai-selesai" hidden>
  <div class="kartu"><h3>Kumpulkan jawaban?</h3><p id="ringkas-selesai"></p>
    <button type="button" class="utama lebar" id="ya-selesai">Ya, kumpulkan</button>
    <button type="button" class="lebar" id="batal-selesai" style="margin-top:.5rem">Periksa lagi</button></div>
</div>
<script type="application/json" id="data-ujian"><?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php
$GLOBALS['skrip_tambahan'][] = 'aset/ujian.js';
kaki(true);

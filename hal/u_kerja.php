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
// latihan: ulangi dari awal
if ($p['selesai'] && ujian_latihan($u) && $_SERVER['REQUEST_METHOD'] === 'POST' && post('aksi') === 'ulang' && $u['status'] === 'buka' && !ujian_bisa_masuk_kerja($u)) {
    ujian_ulang_latihan($u, $p);
    ke('?p=u_kerja&id=' . $id);
}

kepala($u['judul'], '', true);
if ($p['selesai']):
    $h = ujian_hitung($u, $p);
    $soal_semua = ujian_soal($u);
    $jw = json_decode((string)$p['jawaban'], true) ?: [];
    $n_jawab = count(array_filter($jw, fn($v) => (string)$v !== ''));
    $terbuka = ujian_hasil_terbuka($u);
    $lihat_nilai = $terbuka || $u['tampil_nilai'];
    $nu = json_decode((string)$p['nilai_uraian'], true) ?: [];
?>
<div class="siswa-wadah">
  <header class="siswa-kop tak-cetak"><img src="aset/logo.png" alt=""><div><small><?= h((string)k('sekolah', '')) ?></small><b><?= ujian_latihan($u) ? 'Latihan Soal' : 'Ujian Online' ?></b></div></header>
  <div class="kartu selesai-ujian tak-cetak">
    <div class="ikon-selesai"><?= $p['alasan'] === 'pengaman' ? '🔒' : '✅' ?></div>
    <h2><?= $p['alasan'] === 'pengaman' ? 'Jawaban dikumpulkan otomatis' : 'Jawaban terkumpul' ?></h2>
    <p><b><?= h($nama) ?></b><br><?= h($u['judul']) ?></p>
    <?php if ($p['alasan'] === 'pengaman'): ?><div class="pesan galat" style="text-align:left">Kamu keluar dari halaman ujian <?= (int)$p['keluar'] ?> kali, jadi jawabanmu dikumpulkan otomatis. Kalau ini tidak sengaja (HP mati, telepon masuk), minta guru menekan <b>Buka ulang</b>.</div><?php endif; ?>
    <p class="redup"><?= $n_jawab ?> dari <?= count($soal_semua) ?> soal dijawab · dikumpulkan pukul <?= h(substr((string)$p['selesai'], 11, 5)) ?><?= ujian_latihan($u) && (int)$p['percobaan'] > 1 ? ' · percobaan ke-' . (int)$p['percobaan'] : '' ?></p>
    <?php if ($lihat_nilai): ?>
      <?php if ($h['perlu'] && ujian_latihan($u)): ?><div class="nilai-siswa"><?= h((string)$h['nilai']) ?></div><p class="redup">nilai sementara — soal isian/uraian yang belum cocok dengan kunci menunggu dinilai guru<?= $p['nilai_terbaik'] !== null ? ' · terbaik sebelumnya ' . h((string)(float)$p['nilai_terbaik']) : '' ?></p>
      <?php elseif ($h['perlu']): ?><p class="pesan">Nilai pilihan ganda sudah dihitung. Soal uraian sedang diperiksa guru, nilai akhir diumumkan kemudian.</p>
      <?php else: ?><div class="nilai-siswa"><?= h((string)$h['nilai']) ?></div><p class="redup">nilai kamu<?= ujian_latihan($u) && $p['nilai_terbaik'] !== null ? ' · terbaik sebelumnya ' . h((string)(float)$p['nilai_terbaik']) : '' ?></p><?php endif; ?>
    <?php else: ?><p>Nilai akan diumumkan oleh guru.</p><?php endif; ?>
    <?php if (!$terbuka && $u['tampil_hasil']): ?><p class="kecil">Setelah guru menutup ujian, buka lagi link ujian ini di HP ini untuk melihat <b>nilai &amp; pembahasan</b>.</p><?php endif; ?>
    <?php if (ujian_latihan($u) && $u['status'] === 'buka' && !ujian_bisa_masuk_kerja($u)): ?>
      <form method="post"><?= input_token() ?><input type="hidden" name="aksi" value="ulang"><button class="utama lebar">↻ Ulangi latihan</button></form>
    <?php endif; ?>
    <?php if (!$terbuka): ?><p class="kecil redup">Halaman ini boleh ditutup.</p><?php endif; ?>
  </div>
  <?php if ($terbuka): ?>
  <div class="kartu tak-cetak simpan-pdf">
    <button type="button" class="utama lebar" onclick="document.title=<?= h(json_encode('Pembahasan - ' . $u['judul'] . ' - ' . $nama, JSON_UNESCAPED_UNICODE)) ?>;window.print()">📄 Simpan sebagai PDF</button>
    <p class="kecil redup" style="margin:.4rem 0 0">Di jendela yang muncul, pilih <b>Simpan sebagai PDF</b> (atau <b>Simpan PDF</b>), lalu tekan Simpan/Unduh. Simpan untuk belajar lagi nanti.</p>
  </div>
  <div class="hanya-cetak kop-pdf">
    <b><?= h((string)k('sekolah', '')) ?></b> · Pembahasan <?= ujian_latihan($u) ? 'latihan' : 'ujian' ?><br>
    <?= h($u['judul']) ?> · Kelas <?= h($u['kelas']) ?><?= $u['mapel'] ? ' · ' . h($u['mapel']) : '' ?><br>
    Nama: <b><?= h($nama) ?></b><?= $lihat_nilai && !$h['perlu'] ? ' · Nilai: <b>' . h((string)$h['nilai']) . '</b>' : '' ?> · <?= h(tgl_panjang(substr((string)$p['selesai'], 0, 10))) ?>
  </div>
  <h3 style="margin:1.2rem 0 .5rem">Pembahasan</h3>
  <ol class="ulasan-siswa">
  <?php foreach ($soal_semua as $i => $x): $a = (string)($jw[$i] ?? ''); $r = $h['rinci'][$i]; $pg = in_array($x['tipe'], ['pg', 'bs'], true);
      $kelas_r = $r['status'] === 'perlu' ? 'tunggu' : ($r['skor'] >= $r['maks'] && $r['maks'] > 0 ? 'benar' : ($r['skor'] > 0 ? 'sebagian' : 'salah')); ?>
    <li class="kartu <?= $kelas_r ?>">
      <div class="ulasan-kepala"><b>Soal <?= $i + 1 ?></b><span><?= ['benar' => '✓ Benar', 'salah' => '✗ Salah', 'sebagian' => '◐ Sebagian', 'tunggu' => '⏳ Menunggu guru'][$kelas_r] ?></span></div>
      <div class="ujian-teks" style="font-size:1rem;margin-bottom:.4rem"><?= nl2br(h($x['teks'])) ?></div>
      <?= gambar_html($x['gambar'] ?? null, 'gambar-soal kecil') ?>
      <?php if ($pg): ?>
        <ul class="ulasan-opsi">
        <?php foreach ($x['opsi'] as $k => $t): $kls = ($k === $x['kunci'] ? 'kunci ' : '') . ($k === strtoupper($a) ? 'dipilih' : ''); ?>
          <li class="<?= trim($kls) ?>"><span class="huruf"><?= h($k) ?></span><span><?= h($t) ?><?= gambar_html($x['gambar_opsi'][$k] ?? null, 'gambar-opsi') ?></span>
            <?= $k === strtoupper($a) ? '<small>jawabanmu</small>' : '' ?><?= $k === $x['kunci'] ? '<small class="ok">kunci</small>' : '' ?></li>
        <?php endforeach; ?>
        </ul>
        <?php if ($a === ''): ?><p class="kecil redup" style="margin:.3rem 0 0">Tidak dijawab.</p><?php endif; ?>
      <?php else: ?>
        <div class="kecil redup">Jawabanmu:</div><div class="jawaban-uraian"><?= $a === '' ? '(kosong)' : nl2br(h($a)) ?></div>
        <?php if ($x['tipe'] === 'isian' && $x['kunci'] !== ''): ?><div class="kecil" style="margin-top:.3rem">Kunci: <b><?= h($x['kunci']) ?></b></div><?php endif; ?>
        <?php if ($r['status'] !== 'perlu' && $x['tipe'] === 'esai'): ?><div class="kecil" style="margin-top:.3rem">Skor: <b><?= h((string)$r['skor']) ?></b> / <?= h((string)$r['maks']) ?></div><?php endif; ?>
        <?php if (!empty($nu[$i]['catatan'])): ?><div class="catatan-k"><?= h($nu[$i]['catatan']) ?></div><?php endif; ?>
      <?php endif; ?>
      <?php if (trim((string)$x['pembahasan']) !== ''): ?><div class="pembahasan-siswa"><b>Pembahasan:</b> <?= nl2br(h($x['pembahasan'])) ?></div>
      <?php elseif ($x['tipe'] === 'esai' && trim((string)$x['kunci']) !== ''): ?><div class="pembahasan-siswa"><b>Pedoman jawaban:</b> <?= nl2br(h($x['kunci'])) ?></div><?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ol>
  <?php endif; ?>
</div>
<?php
    kaki(true);
    return;
endif;

$data = [
    'id' => $id, 'kode_ujian' => $u['kode'], 'nama' => $nama, 'judul' => $u['judul'],
    'soal' => ujian_data_siswa($u, $p),
    'jawaban' => (object)(json_decode((string)$p['jawaban'], true) ?: []),
    'ragu' => (object)(json_decode((string)$p['ragu'], true) ?: []),
    'nomor' => (int)$p['nomor'],
    'sisa' => max(0, strtotime((string)$p['batas']) - time()),
    'pengaman' => !ujian_latihan($u) && (int)$u['pengaman'] === 1, 'batas_keluar' => (int)$u['batas_keluar'], 'keluar' => (int)$p['keluar'],
    'token' => token(),
    'kunci' => ujian_kunci_baru($id, (string)$kode),
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
  <div class="kartu"><h3 id="judul-keluar">⚠ Kamu keluar dari halaman ujian</h3><p id="teks-keluar">Kejadian ini tercatat dan terlihat oleh guru. Tetap di halaman ini sampai selesai.</p>
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

<?php
/* Halaman tugas online untuk siswa (tanpa akun): pilih nama → tata tertib → foto & kirim. */
declare(strict_types=1);

$kode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($_REQUEST['k'] ?? '')));
$t = $kode !== '' ? satu('SELECT * FROM guru_tugas WHERE kode = ?', [$kode]) : null;
$alat = hash('sha256', alat_perangkat());
$galat = '';
$tid = $t ? (int)$t['id'] : 0;
$siswa = $t ? siswa_kelas((string)$t['kelas'], (string)($t['tahun'] ?: tahun_aktif((int)$t['guru_id'])), (int)$t['guru_id']) : [];
$nama_ada = array_column($siswa, 'member_name', 'member_id');

if ($t && get('ganti')) { unset($_SESSION['tugas'][$tid], $_SESSION['tugas_ok'][$tid]); ke('?p=t&k=' . $t['kode']); }
if ($t && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('aksi') === 'nama') {
        $s = (string)post('siswa');
        if (!isset($nama_ada[$s])) $galat = 'Pilih namamu dari daftar.';
        else { session_regenerate_id(true); $_SESSION['tugas'][$tid] = $s; ke('?p=t&k=' . $t['kode']); }
    } elseif (post('aksi') === 'setuju' && isset($_SESSION['tugas'][$tid])) {
        if (!post('setuju')) $galat = 'Centang dulu “Saya sudah membaca”.';
        else { $_SESSION['tugas_ok'][$tid] = 1; ke('?p=t&k=' . $t['kode']); }
    }
}
$saya = $t ? ($_SESSION['tugas'][$tid] ?? null) : null;
if ($saya && !isset($nama_ada[$saya])) { unset($_SESSION['tugas'][$tid]); $saya = null; }
$k = $saya ? satu('SELECT * FROM guru_tugas_kirim WHERE tugas_id = ? AND kode = ?', [$tid, $saya]) : null;
$hp_sama = $k && $k['perangkat'] && hash_equals((string)$k['perangkat'], $alat);
$guru = $t ? (string)nilai('SELECT nama FROM guru_akun WHERE id = ?', [$t['guru_id']]) : '';

kepala($t ? $t['judul'] : 'Tugas online', '', true);
?>
<div class="siswa-wadah">
  <header class="siswa-kop"><img src="aset/logo.png" alt=""><div><small><?= h((string)k('sekolah', '')) ?></small><b>Tugas Online</b></div></header>
  <?php if (!$t): ?>
  <form method="get" class="kartu formulir">
    <input type="hidden" name="p" value="t">
    <?php if ($kode !== ''): ?><div class="pesan galat">Kode tugas tidak ditemukan.</div><?php endif; ?>
    <label>Kode tugas<input name="k" class="isian-kode" maxlength="10" autocomplete="off" autocapitalize="characters" required placeholder="mis. T7M2QX"></label>
    <button class="utama lebar">Lanjut</button>
  </form>
  <?php else: ?>
  <div class="kartu">
    <small class="redup"><?= $t['mapel'] ? h($t['mapel']) . ' · ' : '' ?>Kelas <?= h($t['kelas']) ?><?= $guru ? ' · ' . h($guru) : '' ?></small>
    <h2 style="margin:.2rem 0 .3rem"><?= h($t['judul']) ?></h2>
    <?php if ($t['petunjuk']): ?><p style="margin:.2rem 0 .5rem;white-space:pre-line"><?= h($t['petunjuk']) ?></p><?php endif; ?>
    <?= gambar_html($t['lampiran'], 'gambar-soal') ?>
    <?php if ($t['batas']): ?><div class="sisa-tugas<?= tugas_lewat($t) ? ' lewat' : '' ?>" data-batas="<?= h(date('c', strtotime((string)$t['batas']))) ?>">⏰ <span><?= tugas_lewat($t) ? 'Batas sudah lewat' : 'Batas' ?></span> (<?= h(ujian_waktu_teks($t['batas'])) ?>)</div><?php endif; ?>
  </div>
  <?php if ($galat): ?><div class="pesan galat"><?= h($galat) ?></div><?php endif; ?>

  <?php if (!$saya): ?>
  <form method="post" class="kartu formulir">
    <?= input_token() ?><input type="hidden" name="k" value="<?= h($t['kode']) ?>"><input type="hidden" name="aksi" value="nama">
    <label>Nama kamu
      <select name="siswa" required><option value="">— pilih nama —</option>
        <?php foreach ($siswa as $i => $s): ?><option value="<?= h($s['member_id']) ?>"><?= ($s['absen'] ?: $i + 1) . '. ' . h($s['member_name']) ?></option><?php endforeach; ?>
      </select></label>
    <button class="utama lebar">Lanjut</button>
  </form>

  <?php elseif ($t['tampil_tertib'] && empty($_SESSION['tugas_ok'][$tid]) && !($k && $k['dikirim'])): ?>
  <form method="post" class="kartu tata-tertib">
    <?= input_token() ?><input type="hidden" name="k" value="<?= h($t['kode']) ?>"><input type="hidden" name="aksi" value="setuju">
    <p style="margin:0 0 .4rem">Halo, <b><?= h($nama_ada[$saya]) ?></b> 👋</p>
    <h3 style="margin:0 0 .2rem">📋 Tata tertib tugas</h3>
    <ol class="poin-tertib"><?php foreach (tugas_tata_tertib($t) as $x): ?><li><?= $x ?></li><?php endforeach; ?></ol>
    <label class="setuju-tertib"><input type="checkbox" name="setuju" value="1" required> <span>Saya sudah membaca dan <b>mengerjakan sendiri</b> tugas ini.</span></label>
    <button class="utama lebar" style="margin-top:.7rem">Lanjut</button>
    <p class="kecil redup" style="text-align:center;margin:.4rem 0 0"><a href="?p=t&amp;k=<?= h($t['kode']) ?>&amp;ganti=1">bukan saya</a></p>
  </form>

  <?php else: $bisa = tugas_bisa_kirim($t, $k); $foto_lama = $hp_sama ? tugas_foto_daftar($k) : []; ?>
  <?php if ($k && $k['dikirim']): ?>
    <?php if ($hp_sama): ?>
      <?php if ($k['status'] === 'dinilai'): ?>
        <div class="kartu nilai-kartu"><div class="kecil redup">Nilai tugasmu</div><div class="nilai-siswa"><?= h(rtrim(rtrim((string)$k['nilai'], '0'), '.')) ?></div>
          <?php if (trim((string)$k['komentar']) !== ''): ?><div class="pembahasan-siswa" style="text-align:left"><b>Komentar guru:</b> <?= nl2br(h($k['komentar'])) ?></div><?php endif; ?></div>
      <?php elseif ($k['status'] === 'perbaiki'): ?>
        <div class="pesan galat"><b>↩ Guru meminta kamu memperbaiki tugas.</b><?= trim((string)$k['komentar']) !== '' ? '<br>' . nl2br(h($k['komentar'])) : '' ?><br>Kirim ulang di bawah.</div>
      <?php endif; ?>
      <div class="pesan" style="background:#dff3e4;color:#1d6b33">✅ Terkirim <?= h(ujian_waktu_teks($k['dikirim'])) ?> · <?= count($foto_lama) ?> foto<?= $k['telat'] ? ' · <b>terlambat</b>' : '' ?><?= $k['status'] === 'kirim' ? '. Menunggu dinilai guru.' : '' ?></div>
      <?php if ($foto_lama): ?><div class="foto-tugas kecil-foto"><?php foreach ($foto_lama as $f): ?><img src="<?= h(tugas_foto_url($tid, $f)) ?>" alt="" loading="lazy"><?php endforeach; ?></div><?php endif; ?>
    <?php else: ?>
      <div class="pesan">Tugas atas nama <b><?= h($nama_ada[$saya]) ?></b> sudah dikirim dari HP lain (<?= h(ujian_waktu_teks($k['dikirim'])) ?>). Mengirim lagi akan <b>menggantikan</b> kiriman itu.</div>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($bisa !== ''): ?>
    <?php if (!($k && $k['status'] === 'dinilai')): ?><div class="pesan"><?= h($bisa) ?></div><?php endif; ?>
  <?php else: ?>
  <div class="kartu" id="kirim-tugas" data-t="<?= h(token()) ?>" data-id="<?= $tid ?>" data-maks="<?= (int)$t['maks_foto'] ?>" data-kode="<?= h($t['kode']) ?>">
    <small class="redup">Mengirim sebagai</small>
    <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem"><b style="font-size:1.1rem"><?= h($nama_ada[$saya]) ?></b>
      <a class="kecil" href="?p=t&amp;k=<?= h($t['kode']) ?>&amp;ganti=1">kirim untuk nama lain</a></div>
    <div class="foto-pilih" id="foto-pilih">
      <label class="tambah-foto"><input type="file" accept="image/*" multiple hidden id="ambil-foto"><b>＋</b><span>Foto</span></label>
    </div>
    <small class="redup">📷 Kamera atau galeri · maks. <?= (int)$t['maks_foto'] ?> foto · otomatis dikecilkan</small>
    <label class="formulir" style="display:block;margin-top:.6rem">Jawaban / catatan (opsional)<textarea id="teks-tugas" rows="3" placeholder="Tulis jawaban atau catatan untuk guru"></textarea></label>
    <button type="button" class="utama lebar" id="tombol-kirim" style="margin-top:.6rem">📤 <?= $k && $k['dikirim'] ? 'Kirim ulang' : 'Kirim tugas' ?></button>
    <p class="kecil redup" id="status-kirim" style="margin:.4rem 0 0"></p>
  </div>
  <?php endif; ?>
  <?php endif; ?>
  <?php endif; ?>
</div>
<?php
if ($t && $saya) $GLOBALS['skrip_tambahan'][] = 'aset/tugas.js';
kaki(true);

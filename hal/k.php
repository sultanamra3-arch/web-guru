<?php
/* Kuis cepat untuk siswa: masukkan kode → pilih nama → jawab dari HP. */
declare(strict_types=1);

$kode = preg_replace('/[^0-9]/', '', (string)($_REQUEST['k'] ?? ''));
$k = $kode !== '' ? satu('SELECT * FROM guru_kuis WHERE kode = ?', [$kode]) : null;
$kid = $k ? (int)$k['id'] : 0;
$galat = '';
$alat = hash('sha256', alat_perangkat());
$siswa = $k ? siswa_kelas((string)$k['kelas'], (string)($k['tahun'] ?: tahun_aktif((int)$k['guru_id'])), (int)$k['guru_id']) : [];
$nama_ada = array_column($siswa, 'member_name', 'member_id');

if ($k && get('ganti')) { unset($_SESSION['kuis'][$kid]); ke('?p=k&k=' . $k['kode']); }
if ($k && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $s = (string)post('siswa');
    $p = isset($nama_ada[$s]) ? satu('SELECT * FROM guru_kuis_peserta WHERE kuis_id = ? AND kode = ?', [$kid, $s]) : null;
    if (!isset($nama_ada[$s])) $galat = 'Pilih namamu dari daftar.';
    elseif ($k['status'] === 'selesai') $galat = 'Kuis sudah selesai.';
    elseif ($p && $p['perangkat'] && !hash_equals((string)$p['perangkat'], $alat)) $galat = 'Nama ini sudah dipakai di HP lain. Minta guru mengeluarkan nama itu bila salah.';
    else {
        q('INSERT INTO guru_kuis_peserta (kuis_id, kode, perangkat, gabung, terakhir) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE perangkat = VALUES(perangkat), terakhir = VALUES(terakhir)',
            [$kid, $s, $alat, sekarang(), sekarang()]);
        session_regenerate_id(true);
        $_SESSION['kuis'][$kid] = $s;
        ke('?p=k&k=' . $k['kode']);
    }
}
$saya = $k ? ($_SESSION['kuis'][$kid] ?? null) : null;
if ($saya && !nilai('SELECT 1 FROM guru_kuis_peserta WHERE kuis_id = ? AND kode = ?', [$kid, $saya])) { unset($_SESSION['kuis'][$kid]); $saya = null; }

kepala($k ? $k['judul'] : 'Kuis', '', true);
if ($k && $saya): ?>
<div id="kuis-siswa" class="kuis-siswa" data-id="<?= $kid ?>" data-t="<?= h(token()) ?>" data-kode="<?= h($k['kode']) ?>">
  <header class="kuis-kop"><b><?= h($nama_ada[$saya] ?? nama_siswa($saya)) ?></b><span id="ks-poin">0 poin</span></header>
  <div id="ks-isi" class="kuis-isi"><p>Memuat…</p></div>
  <p class="kecil" style="text-align:center;margin:1rem 0"><a href="?p=k&amp;k=<?= h($k['kode']) ?>&amp;ganti=1">bukan saya</a></p>
</div>
<?php
    $GLOBALS['skrip_tambahan'][] = 'aset/kuis.js';
    kaki(true);
    return;
endif; ?>
<div class="siswa-wadah">
  <header class="siswa-kop"><img src="aset/logo.png" alt=""><div><small><?= h((string)k('sekolah', '')) ?></small><b>Kuis Cepat</b></div></header>
  <?php if ($galat): ?><div class="pesan galat"><?= h($galat) ?></div><?php endif; ?>
  <?php if (!$k): ?>
  <form method="get" class="kartu formulir">
    <input type="hidden" name="p" value="k">
    <?php if ($kode !== ''): ?><div class="pesan galat">Kode kuis tidak ditemukan.</div><?php endif; ?>
    <label>Kode kuis (6 angka)<input name="k" class="isian-kode" inputmode="numeric" maxlength="7" autocomplete="off" required placeholder="mis. 482 913"></label>
    <button class="utama lebar">Masuk</button>
  </form>
  <?php elseif ($k['status'] === 'selesai'): ?>
  <div class="pesan">Kuis “<?= h($k['judul']) ?>” sudah selesai.</div>
  <?php else: ?>
  <form method="post" class="kartu formulir">
    <?= input_token() ?><input type="hidden" name="k" value="<?= h($k['kode']) ?>">
    <h2 style="margin:0 0 .3rem"><?= h($k['judul']) ?></h2>
    <p class="redup" style="margin:0 0 .5rem">Kelas <?= h($k['kelas']) ?> · <?= count(kuis_soal($k)) ?> soal</p>
    <label>Nama kamu<select name="siswa" required><option value="">— pilih nama —</option>
      <?php foreach ($siswa as $i => $s): ?><option value="<?= h($s['member_id']) ?>"><?= ($s['absen'] ?: $i + 1) . '. ' . h($s['member_name']) ?></option><?php endforeach; ?></select></label>
    <button class="utama lebar">Masuk kuis</button>
  </form>
  <?php endif; ?>
</div>
<?php
kaki(true);

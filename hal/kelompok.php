<?php
declare(strict_types=1);

// Kelompok belajar per kelas (milik guru yang login, per tahun ajaran aktif).
$kelas_list = kelas_saya();
$kelas = (string)get('kelas', $kelas_list[0] ?? '');
if ($kelas !== '' && !kelas_valid($kelas)) { pesan('Kelas tidak ditemukan.', 'galat'); ke('?p=kelas'); }
$tahun = tahun_aktif();
$siswa = $kelas !== '' ? siswa_kelas($kelas) : [];
$balik = (string)get('balik');
$url = '?p=kelompok&kelas=' . rawurlencode($kelas) . ($balik !== '' ? '&balik=' . rawurlencode($balik) : '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $kelas !== '') {
    $aksi = (string)post('aksi');
    $baru = [];
    if ($aksi === 'acak') {
        $n = max(2, min(15, (int)post('jumlah', 5)));
        $n = min($n, max(1, count($siswa)));
        $urut = [];
        if (post('campur')) {
            // L dan P diacak terpisah lalu dibagi bergiliran agar tiap kelompok campur.
            $l = array_values(array_filter($siswa, fn($s) => $s['jk'] === 'L'));
            $pr = array_values(array_filter($siswa, fn($s) => $s['jk'] !== 'L'));
            shuffle($l); shuffle($pr);
            $urut = array_merge($l, $pr);
        } else {
            $urut = $siswa;
            shuffle($urut);
        }
        foreach ($urut as $i => $s) $baru[$s['member_id']] = $i % $n + 1;
    } elseif ($aksi === 'simpan') {
        $pilih = (array)($_POST['kel'] ?? []);
        foreach ($siswa as $s) {
            $v = (int)($pilih[$s['member_id']] ?? 0);
            if ($v >= 1 && $v <= 15) $baru[$s['member_id']] = $v;
        }
    } elseif ($aksi !== 'hapus') {
        ke($url);
    }
    db()->beginTransaction();
    q('DELETE FROM guru_kelompok WHERE guru_id = ? AND tahun = ? AND kelas = ?', [uid(), $tahun, $kelas]);
    foreach ($baru as $kode => $k) {
        q('INSERT INTO guru_kelompok (guru_id, tahun, kelas, kode, kelompok) VALUES (?,?,?,?,?)', [uid(), $tahun, $kelas, (string)$kode, $k]);
    }
    db()->commit();
    pesan($aksi === 'hapus' ? 'Pembagian kelompok dihapus.' : 'Kelompok tersimpan.');
    ke($url);
}

$kel = $kelas !== '' ? kelompok_kelas($kelas) : [];
$grup = [];
$tanpa = [];
foreach ($siswa as $s) {
    $k = $kel[$s['member_id']] ?? 0;
    if ($k) $grup[$k][] = $s; else $tanpa[] = $s;
}
ksort($grup);
$maks = max(6, $grup ? max(array_keys($grup)) + 1 : 0);
$maks = min(15, $maks);

kepala('Kelompok', 'kelas');
judul_baris('Kelompok belajar', $balik !== '' && preg_match('/^\d+$/', $balik)
    ? '<a class="tombol" href="?p=nilai&amp;id=' . (int)$balik . '">‹ Kembali ke nilai</a>'
    : '<a class="tombol" href="?p=kelas">‹ Kelas</a>');
?>
<form class="cari" method="get"><input type="hidden" name="p" value="kelompok">
  <?php if ($balik !== ''): ?><input type="hidden" name="balik" value="<?= h($balik) ?>"><?php endif; ?>
  <select name="kelas" onchange="this.form.submit()"><?= pilihan(array_combine($kelas_list, $kelas_list) ?: [], $kelas) ?></select></form>

<?php if ($kelas === ''): ?>
  <p class="pesan">Belum ada kelas. <a href="?p=siswa_tambah">Tambahkan kelas &amp; siswamu dulu →</a></p>
<?php elseif (!$siswa): ?>
  <p class="pesan">Kelas <?= h($kelas) ?> belum punya siswa. <a href="?p=siswa_tambah">Tambah siswa →</a></p>
<?php else: ?>

<p class="redup kecil">Kelas <b><?= h($kelas) ?></b> · <?= count($siswa) ?> siswa · tahun ajaran <?= h($tahun) ?>.
  Kelompok dipakai untuk mengisi nilai tugas kelompok sekaligus (menu Penilaian).</p>

<details class="kartu" <?= $grup ? '' : 'open' ?>>
  <summary><b><?= $grup ? 'Bagi ulang secara acak' : 'Bagi kelompok secara acak' ?></b></summary>
  <form method="post" class="formulir" style="margin-top:.6rem" <?= $grup ? 'onsubmit="return confirm(\'Kelompok lama akan diganti. Lanjutkan?\')"' : '' ?>>
    <?= input_token() ?><input type="hidden" name="aksi" value="acak">
    <label>Jumlah kelompok<input type="number" name="jumlah" min="2" max="15" value="<?= $grup ? count($grup) : min(6, max(2, (int)round(count($siswa) / 5))) ?>" required></label>
    <label class="centang"><input type="checkbox" name="campur" value="1" checked> Laki-laki dan perempuan dicampur rata</label>
    <button class="utama lebar">Acak kelompok</button>
  </form>
</details>

<?php if ($grup): ?>
<div class="kelompok-grid">
  <?php foreach ($grup as $k => $anggota): ?>
  <div class="kartu kelompok-kartu">
    <h3>Kelompok <?= $k ?> <small class="redup">· <?= count($anggota) ?> siswa</small></h3>
    <ol><?php foreach ($anggota as $s): ?><li><?= h($s['member_name']) ?> <small class="redup"><?= h($s['jk']) ?></small></li><?php endforeach; ?></ol>
  </div>
  <?php endforeach; ?>
</div>
<?php if ($tanpa): ?><p class="pesan">Belum masuk kelompok: <?= h(implode(', ', array_column($tanpa, 'member_name'))) ?>.</p><?php endif; ?>
<?php endif; ?>

<details class="kartu">
  <summary><b>Atur sendiri / pindahkan siswa</b></summary>
  <form method="post" style="margin-top:.6rem">
    <?= input_token() ?><input type="hidden" name="aksi" value="simpan">
    <ul class="daftar">
    <?php foreach ($siswa as $i => $s): $v = $kel[$s['member_id']] ?? 0; ?>
      <li class="baris-kelompok"><span class="redup"><?= $i + 1 ?></span> <b><?= h($s['member_name']) ?></b>
        <select name="kel[<?= h($s['member_id']) ?>]" aria-label="Kelompok <?= h($s['member_name']) ?>">
          <option value="0">–</option>
          <?php for ($n = 1; $n <= $maks; $n++): ?><option value="<?= $n ?>" <?= $v === $n ? 'selected' : '' ?>>Kelompok <?= $n ?></option><?php endfor; ?>
        </select></li>
    <?php endforeach; ?>
    </ul>
    <button class="utama lebar">Simpan kelompok</button>
  </form>
</details>

<?php if ($grup): ?>
<form method="post" style="margin-top:1rem" onsubmit="return confirm('Hapus pembagian kelompok kelas ini? Nilai yang sudah disimpan tidak ikut terhapus.')">
  <?= input_token() ?><input type="hidden" name="aksi" value="hapus"><button class="kecil bahaya">Hapus pembagian kelompok</button>
</form>
<?php endif; ?>
<?php endif; ?>
<?php
kaki();

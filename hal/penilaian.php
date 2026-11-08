<?php
declare(strict_types=1);

const JENIS_PENILAIAN = ['Tugas', 'Ulangan Harian', 'Asesmen Formatif', 'Sumatif Lingkup Materi', 'Sumatif Tengah Semester', 'Sumatif Akhir Semester', 'Praktik', 'Proyek', 'Lainnya'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kelas = (string)post('kelas');
    $judul = trim((string)post('judul'));
    $mapel = trim((string)post('mapel'));
    if (!kelas_valid($kelas) || $judul === '' || $mapel === '') {
        pesan('Kelas, mapel, dan judul wajib diisi.', 'galat');
        ke('?p=penilaian');
    }
    q('INSERT INTO guru_penilaian (guru_id, kelas, mapel, judul, jenis, tanggal, kktp, dibuat) VALUES (?,?,?,?,?,?,?,?)', [
        uid(), $kelas, $mapel, mb_substr($judul, 0, 200), in_array(post('jenis'), JENIS_PENILAIAN, true) ? post('jenis') : 'Tugas',
        preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)post('tanggal')) ? post('tanggal') : hari_ini(),
        max(0, min(100, (float)post('kktp', 75))), sekarang()]);
    ke('?p=nilai&id=' . db()->lastInsertId());
}

$kelas_list = kelas_saya();
$f = (string)get('kelas');
$par = [uid()];
$w = '';
if ($f !== '') { $w = ' AND p.kelas = ?'; $par[] = $f; }
$daftar = semua("SELECT p.*, (SELECT COUNT(nilai) FROM guru_nilai n WHERE n.penilaian_id = p.id) terisi,
    (SELECT ROUND(AVG(nilai), 1) FROM guru_nilai n WHERE n.penilaian_id = p.id) rata
    FROM guru_penilaian p WHERE p.guru_id = ? $w ORDER BY p.tanggal DESC, p.id DESC LIMIT 200", $par);

kepala('Penilaian', 'kelas');
judul_baris('Penilaian', '<a class="tombol" href="?p=nilai_rekap' . ($f ? '&amp;kelas=' . h(rawurlencode($f)) : '') . '">Rekap nilai</a>');
?>
<details class="kartu" <?= $daftar ? '' : 'open' ?>>
  <summary><b>＋ Penilaian baru</b></summary>
  <form method="post" class="formulir" style="margin-top:.8rem">
    <?= input_token() ?>
    <label>Judul<input name="judul" required placeholder="mis. Ulangan Bab 2 Sistem Pencernaan"></label>
    <div class="baris2">
      <label>Kelas<select name="kelas" required><?= pilihan(array_combine($kelas_list, $kelas_list) ?: [], $f) ?></select></label>
      <label>Mapel<input name="mapel" list="dl-mapel" required value="<?= h(mapel_saya()[0] ?? '') ?>"></label>
    </div>
    <div class="baris3">
      <label>Jenis<select name="jenis"><?= pilihan(array_combine(JENIS_PENILAIAN, JENIS_PENILAIAN), 'Tugas') ?></select></label>
      <label>Tanggal<input type="date" name="tanggal" value="<?= hari_ini() ?>"></label>
      <label>KKTP<input type="number" name="kktp" min="0" max="100" value="75"></label>
    </div>
    <button class="utama lebar">Buat & isi nilai</button>
  </form>
</details>
<datalist id="dl-mapel"><?php foreach (mapel_saya() as $m): ?><option value="<?= h($m) ?>"><?php endforeach; ?></datalist>

<form class="cari" method="get"><input type="hidden" name="p" value="penilaian">
  <select name="kelas" onchange="this.form.submit()"><option value="">Semua kelas</option><?= pilihan(array_combine($kelas_list, $kelas_list) ?: [], $f) ?></select></form>
<ul class="daftar kartu">
  <?php if (!$daftar): ?><li class="redup">Belum ada penilaian.</li><?php endif; ?>
  <?php foreach ($daftar as $d): ?>
    <li><a href="?p=nilai&amp;id=<?= (int)$d['id'] ?>"><b><?= h($d['judul']) ?></b>
      <small><?= h($d['jenis']) ?> · Kelas <?= h($d['kelas']) ?> · <?= h($d['mapel']) ?> · <?= tgl($d['tanggal']) ?></small>
      <small class="kode"><?= (int)$d['terisi'] ?> nilai terisi<?= $d['rata'] !== null ? ' · rata-rata ' . h($d['rata']) : '' ?></small></a></li>
  <?php endforeach; ?>
</ul>
<?php
kaki();

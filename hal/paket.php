<?php
declare(strict_types=1);

$ids = array_map('intval', (array)($_SESSION['paket_pilih'] ?? []));
if (!$ids) { pesan('Pilih soal di bank soal dulu.', 'galat'); ke('?p=soal'); }
$soal = semua('SELECT * FROM guru_soal WHERE guru_id = ? AND id IN (' . implode(',', $ids) . ')', [uid()]);
// urutkan: PG/BS dulu, lalu isian, lalu esai
$urut = ['pg' => 1, 'bs' => 2, 'isian' => 3, 'esai' => 4];
usort($soal, fn($a, $b) => [$urut[$a['tipe']] ?? 9, $a['id']] <=> [$urut[$b['tipe']] ?? 9, $b['id']]);

$lama = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (trim((string)post('judul')) === '' || trim((string)post('jenis_ujian')) === '')) {
    // judul & jenis ujian wajib diisi guru sendiri
    pesan('Judul paket dan Jenis ujian wajib diisi.', 'galat');
    $lama = $_POST;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isi = [
        'ids' => array_map('intval', array_column($soal, 'id')),
        'mapel' => trim((string)post('mapel')), 'kelas' => trim((string)post('kelas')), 'waktu' => trim((string)post('waktu')),
        'jenis_ujian' => trim((string)post('jenis_ujian')), 'petunjuk' => trim((string)post('petunjuk')), 'acak' => post('acak') ? 1 : 0,
    ];
    if (post('acak')) shuffle($isi['ids']);
    q("INSERT INTO guru_dokumen (guru_id, jenis, judul, mapel, kelas, isi, dibuat, diubah) VALUES (?, 'paket', ?, ?, ?, ?, ?, ?)",
        [uid(), mb_substr(trim((string)post('judul')), 0, 250), $isi['mapel'], mb_substr($isi['kelas'], 0, 20), json_encode($isi, JSON_UNESCAPED_UNICODE), sekarang(), sekarang()]);
    unset($_SESSION['paket_pilih']);
    ke('?p=paket_cetak&id=' . db()->lastInsertId());
}
$mapel = $soal[0]['mapel'] ?? '';
$kelas = $soal[0]['kelas'] ?? '';

kepala('Paket soal', 'soal');
judul_baris('Buat paket soal', '<a class="tombol" href="?p=soal">‹ Bank soal</a>');
?>
<p><?= count($soal) ?> soal dipilih: <?php $hit = array_count_values(array_column($soal, 'tipe')); foreach ($hit as $t => $n) echo h(TIPE_SOAL[$t] ?? $t) . ' ' . $n . '; '; ?></p>
<form method="post" class="formulir">
  <?= input_token() ?>
  <fieldset><legend>Kop lembar soal</legend>
    <label>Judul paket <span class="wajib">*</span><input name="judul" required value="<?= h((string)($lama['judul'] ?? '')) ?>" placeholder="<?= h('mis. Soal ' . ($mapel ?: 'Informatika') . ' Kelas ' . ($kelas ?: '7')) ?>"></label>
    <label>Jenis ujian <span class="wajib">*</span><input name="jenis_ujian" required list="daftar-jenis-ujian" value="<?= h((string)($lama['jenis_ujian'] ?? '')) ?>" placeholder="ketik atau pilih, mis. Sumatif Tengah Semester"></label>
    <datalist id="daftar-jenis-ujian">
      <option value="Asesmen Sumatif Lingkup Materi"><option value="Asesmen Sumatif Tengah Semester Ganjil"><option value="Asesmen Sumatif Akhir Semester Ganjil">
      <option value="Asesmen Sumatif Tengah Semester Genap"><option value="Asesmen Sumatif Akhir Tahun"><option value="Asesmen Formatif"><option value="Ulangan Harian">
    </datalist>
    <div class="baris3">
      <label>Mapel<?= pilih_mapel((string)($lama['mapel'] ?? $mapel)) ?></label>
      <label>Kelas<input name="kelas" value="<?= h((string)($lama['kelas'] ?? $kelas)) ?>"></label>
      <label>Waktu<input name="waktu" value="<?= h((string)($lama['waktu'] ?? '60 menit')) ?>"></label>
    </div>
    <label>Petunjuk<textarea name="petunjuk" rows="3"><?= h((string)($lama['petunjuk'] ?? "Berdoalah sebelum mengerjakan.\nTulis nama dan kelas pada lembar jawaban.\nPilih satu jawaban yang paling tepat untuk soal pilihan ganda.")) ?></textarea></label>
    <label class="centang"><input type="checkbox" name="acak" value="1"<?= !empty($lama['acak']) ? ' checked' : '' ?>> Acak urutan soal</label>
  </fieldset>
  <button class="utama lebar">Buat paket & lihat lembar soal</button>
</form>
<?php
kaki();

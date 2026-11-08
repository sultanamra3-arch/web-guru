<?php
declare(strict_types=1);

$id = (int)get('id');
$s = $id ? satu('SELECT * FROM guru_soal WHERE id = ? AND guru_id = ?', [$id, uid()]) : null;
if ($id && !$s) ke('?p=soal');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $opsi = [];
    foreach (['A', 'B', 'C', 'D', 'E'] as $h) { $v = trim((string)($_POST['opsi'][$h] ?? '')); if ($v !== '') $opsi[$h] = $v; }
    $tipe = isset(TIPE_SOAL[post('tipe')]) ? post('tipe') : 'pg';
    if (!in_array($tipe, ['pg', 'bs'], true)) $opsi = [];
    $d = [
        'mapel' => trim((string)post('mapel')), 'kelas' => trim((string)post('kelas')), 'topik' => trim((string)post('topik')),
        'tipe' => $tipe, 'tingkat' => isset(TINGKAT_SOAL[post('tingkat')]) ? post('tingkat') : 'sedang',
        'level' => in_array(post('level'), LEVEL_KOGNITIF, true) ? post('level') : null,
        'pertanyaan' => trim((string)post('pertanyaan')), 'opsi' => $opsi ? json_encode($opsi, JSON_UNESCAPED_UNICODE) : null,
        'kunci' => trim((string)post('kunci')), 'pembahasan' => trim((string)post('pembahasan')), 'berbagi' => post('berbagi') ? 1 : 0,
    ];
    if ($d['mapel'] === '' || $d['pertanyaan'] === '') {
        pesan('Mapel dan pertanyaan wajib diisi.', 'galat');
    } else {
        if ($s) {
            q('UPDATE guru_soal SET ' . implode(', ', array_map(fn($c) => "$c = ?", array_keys($d))) . ' WHERE id = ?', [...array_values($d), $id]);
        } else {
            $d['guru_id'] = uid(); $d['dibuat'] = sekarang();
            q('INSERT INTO guru_soal (' . implode(',', array_keys($d)) . ') VALUES (' . implode(',', array_fill(0, count($d), '?')) . ')', array_values($d));
        }
        pesan('Soal tersimpan.');
        ke(post('lagi') ? '?p=soal_form' : '?p=soal');
    }
}
$opsi = $s ? (json_decode((string)$s['opsi'], true) ?: []) : [];

kepala('Soal', 'soal');
judul_baris($s ? 'Ubah soal' : 'Tambah soal manual', '<a class="tombol" href="?p=soal">‹ Bank soal</a>');
?>
<form method="post" class="formulir">
  <?= input_token() ?>
  <fieldset><legend>Identitas</legend>
    <div class="baris3">
      <label>Mapel<input name="mapel" list="dl-mapel" required value="<?= h($s['mapel'] ?? (mapel_saya()[0] ?? '')) ?>"></label>
      <label>Kelas<select name="kelas"><?= pilihan(['' => '-', '7' => '7', '8' => '8', '9' => '9'], $s['kelas'] ?? '') ?></select></label>
      <label>Topik<input name="topik" value="<?= h($s['topik'] ?? '') ?>"></label>
    </div>
    <div class="baris3">
      <label>Bentuk<select name="tipe" id="tipe-soal"><?= pilihan(TIPE_SOAL, $s['tipe'] ?? 'pg') ?></select></label>
      <label>Tingkat<select name="tingkat"><?= pilihan(TINGKAT_SOAL, $s['tingkat'] ?? 'sedang') ?></select></label>
      <label>Level<select name="level"><option value="">-</option><?= pilihan(array_combine(LEVEL_KOGNITIF, LEVEL_KOGNITIF), $s['level'] ?? '') ?></select></label>
    </div>
  </fieldset>
  <fieldset><legend>Soal</legend>
    <label>Pertanyaan<textarea name="pertanyaan" rows="4" required><?= h($s['pertanyaan'] ?? '') ?></textarea></label>
    <div id="bagian-opsi">
      <?php foreach (['A', 'B', 'C', 'D', 'E'] as $h): ?>
        <label>Opsi <?= $h ?><input name="opsi[<?= $h ?>]" value="<?= h($opsi[$h] ?? '') ?>"></label>
      <?php endforeach; ?>
    </div>
    <label>Kunci jawaban <small>(huruf untuk pilihan ganda; jawaban/pedoman untuk isian & uraian)</small><textarea name="kunci" rows="2"><?= h($s['kunci'] ?? '') ?></textarea></label>
    <label>Pembahasan<textarea name="pembahasan" rows="3"><?= h($s['pembahasan'] ?? '') ?></textarea></label>
    <label class="centang"><input type="checkbox" name="berbagi" value="1" <?= !empty($s['berbagi']) ? 'checked' : '' ?>> Bagikan ke guru lain (mereka bisa memakai, tidak bisa mengubah)</label>
  </fieldset>
  <div class="dua-tombol">
    <button class="utama">Simpan</button>
    <?php if (!$s): ?><button name="lagi" value="1">Simpan & tambah lagi</button><?php endif; ?>
  </div>
</form>
<datalist id="dl-mapel"><?php foreach (mapel_saya() as $m): ?><option value="<?= h($m) ?>"><?php endforeach; ?></datalist>
<?php
kaki();

<?php
declare(strict_types=1);

$id = (int)get('id');
$s = $id ? satu('SELECT * FROM guru_soal WHERE id = ? AND guru_id = ?', [$id, uid()]) : null;
if ($id && !$s) ke('?p=soal');
// datang dari halaman paket: kembali ke paket, soal baru langsung masuk paket itu
$pk_id = (int)get('paket');
$pk = $pk_id ? satu("SELECT * FROM guru_dokumen WHERE id = ? AND guru_id = ? AND jenis = 'paket'", [$pk_id, uid()]) : null;
$kembali = $pk ? '?p=paket_cetak&id=' . $pk_id . '&ubah=1#ubah-soal' : '?p=soal';

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
        'kunci' => trim((string)post('kunci')), 'pembahasan' => trim((string)post('pembahasan')),
    ];
    // gambar soal & gambar pilihan (dikecilkan di HP sebelum dikirim)
    $g = $s['gambar'] ?? null;
    if (post('hapus_gambar')) $g = null;
    if ((string)post('gambar_data') !== '') $g = gambar_simpan((string)post('gambar_data')) ?? $g;
    $d['gambar'] = $g;
    $go = gambar_opsi($s['gambar_opsi'] ?? null);
    foreach (['A', 'B', 'C', 'D', 'E'] as $h) {
        if (!empty($_POST['hapus_gambar_opsi'][$h])) unset($go[$h]);
        $x = (string)($_POST['gambar_opsi_data'][$h] ?? '');
        if ($x !== '' && ($f = gambar_simpan($x))) $go[$h] = $f;
    }
    if (!in_array($tipe, ['pg', 'bs'], true)) $go = [];
    $d['gambar_opsi'] = $go ? json_encode($go) : null;
    // pilihan yang hanya berupa gambar tetap dihitung sebagai pilihan
    if (in_array($tipe, ['pg', 'bs'], true)) {
        foreach ($go as $h => $_) if (!isset($opsi[$h])) $opsi[$h] = '';
        ksort($opsi);
        $d['opsi'] = $opsi ? json_encode($opsi, JSON_UNESCAPED_UNICODE) : null;
    }
    if ($d['mapel'] === '' && $d['pertanyaan'] === '') {
        pesan('Mapel dan pertanyaan wajib diisi.', 'galat');
    } elseif ($d['mapel'] === '' || ($d['pertanyaan'] === '' && !$g)) {
        pesan('Mapel dan pertanyaan wajib diisi.', 'galat');
    } else {
        if ($s) {
            q('UPDATE guru_soal SET ' . implode(', ', array_map(fn($c) => "$c = ?", array_keys($d))) . ' WHERE id = ?', [...array_values($d), $id]);
        } else {
            $d['guru_id'] = uid(); $d['dibuat'] = sekarang();
            q('INSERT INTO guru_soal (' . implode(',', array_keys($d)) . ') VALUES (' . implode(',', array_fill(0, count($d), '?')) . ')', array_values($d));
            if ($pk) {
                $isi = json_decode((string)$pk['isi'], true) ?: [];
                $isi['ids'][] = (int)db()->lastInsertId();
                q('UPDATE guru_dokumen SET isi = ?, diubah = ? WHERE id = ?', [json_encode($isi, JSON_UNESCAPED_UNICODE), sekarang(), $pk_id]);
            }
        }
        pesan('Soal tersimpan' . ($pk ? ' di paket "' . h($pk['judul']) . '".' : '.'));
        ke(post('lagi') ? '?p=soal_form' . ($pk ? '&paket=' . $pk_id : '') : $kembali);
    }
}
$opsi = $s ? (json_decode((string)$s['opsi'], true) ?: []) : [];
$g_opsi = gambar_opsi($s['gambar_opsi'] ?? null);

kepala('Soal', 'soal');
judul_baris($s ? 'Ubah soal' : ($pk ? 'Tambah soal ke paket' : 'Tambah soal manual'), '<a class="tombol" href="' . h($kembali) . '">‹ ' . ($pk ? 'Kembali ke paket' : 'Bank soal') . '</a>');
if ($pk): ?><p class="kecil redup">Paket: <b><?= h($pk['judul']) ?></b><?= $s ? '. Perubahan langsung terlihat di lembar soal paket ini (ujian online yang sudah dibuat tidak ikut berubah).' : '' ?></p><?php endif;
?>
<form method="post" class="formulir" id="form-soal">
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
    <label>Pertanyaan<textarea name="pertanyaan" rows="4"><?= h($s['pertanyaan'] ?? '') ?></textarea></label>
    <div class="unggah-gambar" data-nama="gambar_data">
      <div class="pratinjau-gambar"><?= gambar_html($s['gambar'] ?? null, 'gambar-soal') ?></div>
      <label class="tombol kecil pilih-gambar"><?= ikon('galeri') ?> <span><?= !empty($s['gambar']) ? 'Ganti gambar soal' : 'Tambah gambar soal' ?></span><input type="file" accept="image/*" hidden></label>
      <?php if (!empty($s['gambar'])): ?><label class="centang kecil"><input type="checkbox" name="hapus_gambar" value="1"> Hapus gambar</label><?php endif; ?>
      <input type="hidden" name="gambar_data">
    </div>
    <div id="bagian-opsi">
      <?php foreach (['A', 'B', 'C', 'D', 'E'] as $h): ?>
        <div class="opsi-dengan-gambar">
          <label>Opsi <?= $h ?><input name="opsi[<?= $h ?>]" value="<?= h($opsi[$h] ?? '') ?>"></label>
          <div class="unggah-gambar kecil-gambar" data-nama="gambar_opsi_data[<?= $h ?>]">
            <div class="pratinjau-gambar"><?= gambar_html($g_opsi[$h] ?? null, 'gambar-opsi') ?></div>
            <label class="tombol kecil pilih-gambar" title="Gambar untuk opsi <?= $h ?>"><?= ikon('galeri') ?><span class="sr"><?= isset($g_opsi[$h]) ? 'Ganti' : 'Gambar' ?></span><input type="file" accept="image/*" hidden></label>
            <?php if (isset($g_opsi[$h])): ?><label class="centang kecil"><input type="checkbox" name="hapus_gambar_opsi[<?= $h ?>]" value="1"> hapus</label><?php endif; ?>
            <input type="hidden" name="gambar_opsi_data[<?= $h ?>]">
          </div>
        </div>
      <?php endforeach; ?>
      <p class="kecil redup">Pilihan boleh berupa gambar saja (isi teks opsi dengan keterangan singkat, mis. "Gambar 1").</p>
    </div>
    <label>Kunci jawaban <small>(huruf untuk pilihan ganda; jawaban/pedoman untuk isian & uraian)</small><textarea name="kunci" rows="2"><?= h($s['kunci'] ?? '') ?></textarea></label>
    <label>Pembahasan<textarea name="pembahasan" rows="3"><?= h($s['pembahasan'] ?? '') ?></textarea></label>
  </fieldset>
  <div class="dua-tombol">
    <button class="utama">Simpan</button>
    <?php if (!$s): ?><button name="lagi" value="1">Simpan & tambah lagi</button><?php endif; ?>
  </div>
</form>
<datalist id="dl-mapel"><?php foreach (mapel_saya() as $m): ?><option value="<?= h($m) ?>"><?php endforeach; ?></datalist>
<?php
kaki();

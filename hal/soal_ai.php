<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simpan soal hasil AI yang dicentang
    $data = json_decode((string)post('data'), true) ?: [];
    $pilih = array_map('intval', (array)($_POST['pilih'] ?? []));
    $n = 0;
    $ids = [];
    foreach ($pilih as $i) {
        $s = $data[$i] ?? null;
        if (!$s || trim((string)($s['pertanyaan'] ?? '')) === '') continue;
        q('INSERT INTO guru_soal (guru_id, mapel, kelas, topik, tipe, tingkat, level, pertanyaan, opsi, kunci, pembahasan, berbagi, dibuat) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)', [
            uid(), mb_substr(trim((string)post('mapel')), 0, 120), mb_substr(trim((string)post('kelas_angka')), 0, 20), mb_substr(trim((string)post('topik')), 0, 200),
            isset(TIPE_SOAL[$s['tipe'] ?? '']) ? $s['tipe'] : 'pg', isset(TINGKAT_SOAL[$s['tingkat'] ?? '']) ? $s['tingkat'] : 'sedang',
            in_array($s['level'] ?? '', LEVEL_KOGNITIF, true) ? $s['level'] : null, (string)$s['pertanyaan'],
            !empty($s['opsi']) ? json_encode($s['opsi'], JSON_UNESCAPED_UNICODE) : null, (string)($s['kunci'] ?? ''), (string)($s['pembahasan'] ?? ''),
            0, sekarang()]);
        $ids[] = (int)db()->lastInsertId();
        $n++;
    }
    pesan($n . ' soal disimpan ke bank soal.');
    if (post('jadikan_paket') && $ids) { $_SESSION['paket_pilih'] = $ids; ke('?p=paket'); }
    ke('?p=soal&mapel=' . rawurlencode((string)post('mapel')));
}

kepala('Buat soal AI', 'soal');
judul_baris('✨ Buat soal dengan AI', '<a class="tombol" href="?p=soal">‹ Bank soal</a>');
?>
<form class="formulir" id="form-soal-ai" data-token="<?= h(token()) ?>">
  <fieldset><legend>Permintaan</legend>
    <div class="baris2">
      <label>Mata pelajaran<?= pilih_mapel('') ?></label>
      <label>Kelas<select name="kelas"><?= pilihan(['7' => 'Kelas 7', '8' => 'Kelas 8', '9' => 'Kelas 9'], '8') ?></select></label>
    </div>
    <label>Materi / topik<input name="topik" required placeholder="mis. Sistem pencernaan manusia"></label>
    <div class="jumlah-bentuk">
      <span class="kecil redup">Jumlah soal per bentuk</span>
      <label>Pilihan ganda<input type="number" name="jml_pg" min="0" max="50" value="10" inputmode="numeric"></label>
      <label>Benar/Salah<input type="number" name="jml_bs" min="0" max="50" value="0" inputmode="numeric"></label>
      <label>Isian singkat<input type="number" name="jml_isian" min="0" max="50" value="0" inputmode="numeric"></label>
      <label>Uraian/esai<input type="number" name="jml_esai" min="0" max="50" value="0" inputmode="numeric"></label>
      <b class="total-soal">Total: <span id="total-soal">10</span> soal</b>
    </div>
    <label>Opsi pilihan ganda<select name="jumlah_opsi"><?= pilihan(['4' => 'A–D (4 pilihan)', '5' => 'A–E (5 pilihan)'], '4') ?></select></label>
    <label>Tingkat<select name="tingkat"><?= pilihan(['campuran (LOTS, MOTS, HOTS seimbang)' => 'Campuran (disarankan)', 'mudah (LOTS)' => 'Mudah', 'sedang (MOTS)' => 'Sedang', 'sulit (HOTS, berbasis kasus/stimulus)' => 'Sulit / HOTS'], 'campuran (LOTS, MOTS, HOTS seimbang)') ?></select></label>
    <details><summary class="kecil">Tambahan (opsional): bahan materi, foto buku, catatan</summary>
      <label>Tempel teks materi <small>(soal dibuat dari teks ini)</small><textarea name="bahan" rows="4"></textarea></label>
      <div class="dua-tombol">
        <label class="tombol">📷 Kamera<input type="file" class="ambil-foto" accept="image/*" capture="environment" hidden></label>
        <label class="tombol">🖼 Galeri<input type="file" class="ambil-foto" accept="image/*" multiple hidden></label>
      </div>
      <div class="pratinjau-foto kiri"></div>
      <label>Catatan untuk AI<input name="catatan" placeholder="mis. gunakan konteks perkebunan kelapa"></label>
    </details>
  </fieldset>
  <button class="utama lebar" id="tombol-buat-soal">✨ Buat soal</button>
  <div id="status-ai" class="kecil"></div>
</form>

<form method="post" id="hasil-soal" hidden>
  <?= input_token() ?>
  <input type="hidden" name="data"><input type="hidden" name="mapel"><input type="hidden" name="kelas_angka"><input type="hidden" name="topik">
  <div class="kartu lengket aksi-bank">
    <label class="centang"><input type="checkbox" id="pilih-semua-hasil" checked> Pilih semua</label>
    <span>
      <label class="centang kecil"><input type="checkbox" name="jadikan_paket" value="1" checked> Langsung jadikan paket soal</label>
      <button class="utama kecil">💾 Simpan</button></span>
  </div>
  <ol class="daftar kartu bank-soal" id="daftar-hasil"></ol>
</form>
<?php
kaki();

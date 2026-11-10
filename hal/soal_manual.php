<?php
/* Buat banyak soal manual sekaligus: tentukan jumlah per bentuk, isi lembarnya, simpan semua. */
declare(strict_types=1);

$NAMA = ['pg' => 'Pilihan ganda', 'bs' => 'Benar/Salah', 'isian' => 'Isian singkat', 'esai' => 'Uraian/esai'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mapel = mb_substr(trim((string)post('mapel')), 0, 120);
    $kelas = mb_substr(trim((string)post('kelas')), 0, 20);
    $topik = mb_substr(trim((string)post('topik')), 0, 200);
    $tingkat = isset(TINGKAT_SOAL[post('tingkat')]) ? (string)post('tingkat') : 'sedang';
    if ($mapel === '') { pesan('Isi mata pelajaran.', 'galat'); ke($_SERVER['REQUEST_URI']); }
    $ids = [];
    db()->beginTransaction();
    foreach ((array)($_POST['soal'] ?? []) as $s) {
        $tipe = isset($NAMA[$s['tipe'] ?? '']) ? $s['tipe'] : 'pg';
        $tanya = trim((string)($s['pertanyaan'] ?? ''));
        if ($tanya === '') continue;
        $opsi = null;
        $kunci = trim((string)($s['kunci'] ?? ''));
        if ($tipe === 'pg') {
            $o = [];
            foreach ((array)($s['opsi'] ?? []) as $k => $v) if (in_array($k, HURUF_OPSI, true) && trim((string)$v) !== '') $o[$k] = trim((string)$v);
            $opsi = json_encode($o, JSON_UNESCAPED_UNICODE);
            $kunci = strtoupper(substr($kunci, 0, 1));
        } elseif ($tipe === 'bs') {
            $opsi = json_encode(['A' => 'Benar', 'B' => 'Salah'], JSON_UNESCAPED_UNICODE);
            $kunci = $kunci === 'B' ? 'B' : 'A';
        }
        q('INSERT INTO guru_soal (guru_id, mapel, kelas, topik, tipe, tingkat, level, pertanyaan, opsi, kunci, pembahasan, berbagi, dibuat) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [uid(), $mapel, $kelas, $topik, $tipe, $tingkat, null, $tanya, $opsi, mb_substr($kunci, 0, 5000), mb_substr(trim((string)($s['pembahasan'] ?? '')), 0, 5000), 0, sekarang()]);
        $ids[] = (int)db()->lastInsertId();
    }
    db()->commit();
    if (!$ids) { pesan('Belum ada soal yang diisi.', 'galat'); ke($_SERVER['REQUEST_URI']); }
    pesan(count($ids) . ' soal disimpan ke bank soal.');
    if (post('jadikan_paket')) { $_SESSION['paket_pilih'] = $ids; ke('?p=paket'); }
    ke('?p=soal&mapel=' . rawurlencode($mapel));
}

$jml = [];
foreach (array_keys($NAMA) as $t) $jml[$t] = max(0, min(60, (int)get('jml_' . $t, $t === 'pg' ? '10' : '0')));
$siap = get('mulai') && array_sum($jml) > 0;
$opsi_n = (int)get('opsi', '4') === 5 ? 5 : 4;
$mapel = (string)get('mapel', '');

kepala('Buat soal manual', 'soal');
judul_baris('Buat soal manual', '<a class="tombol" href="?p=soal">‹ Bank soal</a>');
?>
<?php if (!$siap): ?>
<form method="get" class="kartu formulir">
  <input type="hidden" name="p" value="soal_manual"><input type="hidden" name="mulai" value="1">
  <div class="baris2">
    <label>Mata pelajaran<?= pilih_mapel($mapel) ?></label>
    <label>Kelas<select name="kelas"><?= pilihan(['7' => 'Kelas 7', '8' => 'Kelas 8', '9' => 'Kelas 9'], (string)get('kelas', '8')) ?></select></label>
  </div>
  <label>Materi / topik<input name="topik" value="<?= h((string)get('topik')) ?>" placeholder="mis. Algoritma"></label>
  <div class="jumlah-bentuk">
    <span class="kecil redup">Jumlah soal per bentuk</span>
    <?php foreach ($NAMA as $t => $n): ?><label><?= $n ?><input type="number" name="jml_<?= $t ?>" min="0" max="60" value="<?= $jml[$t] ?>" inputmode="numeric"></label><?php endforeach; ?>
  </div>
  <label>Opsi pilihan ganda<select name="opsi"><?= pilihan(['4' => 'A–D (4 pilihan)', '5' => 'A–E (5 pilihan)'], (string)$opsi_n) ?></select></label>
  <button class="utama lebar">Buat lembar isian soal</button>
  <p class="kecil redup">Ingin soal satu per satu dengan pengaturan lengkap? <a href="?p=soal_form">Formulir satu soal</a>.</p>
</form>
<?php else: ?>
<form method="post" class="formulir" id="form-manual">
  <?= input_token() ?>
  <input type="hidden" name="mapel" value="<?= h($mapel) ?>"><input type="hidden" name="kelas" value="<?= h((string)get('kelas')) ?>"><input type="hidden" name="topik" value="<?= h((string)get('topik')) ?>">
  <div class="kartu">
    <b><?= h($mapel) ?></b> · Kelas <?= h((string)get('kelas')) ?><?= get('topik') ? ' · ' . h((string)get('topik')) : '' ?>
    <div class="kecil redup"><?= implode(' · ', array_filter(array_map(fn($t, $n) => $jml[$t] ? $n . ' ' . $jml[$t] : '', array_keys($NAMA), $NAMA))) ?> ·
      <a href="?<?= h(http_build_query(array_merge($_GET, ['mulai' => '']))) ?>">ubah jumlah</a></div>
    <label style="margin-top:.5rem">Tingkat<select name="tingkat"><?= pilihan(TINGKAT_SOAL, 'sedang') ?></select></label>
  </div>
  <?php $no = 0; foreach ($NAMA as $t => $judul): if (!$jml[$t]) continue; ?>
  <h3 class="judul-bagian"><?= $judul ?> <small class="redup">(<?= $jml[$t] ?> soal)</small></h3>
  <?php for ($i = 0; $i < $jml[$t]; $i++, $no++): $nm = "soal[$no]"; ?>
  <div class="kartu soal-manual">
    <input type="hidden" name="<?= $nm ?>[tipe]" value="<?= $t ?>">
    <label><b><?= $no + 1 ?>.</b> Pertanyaan<textarea name="<?= $nm ?>[pertanyaan]" rows="2"></textarea></label>
    <?php if ($t === 'pg'): ?>
      <div class="opsi-manual">
        <?php foreach (array_slice(HURUF_OPSI, 0, $opsi_n) as $h): ?>
        <label class="opsi-baris"><input type="radio" name="<?= $nm ?>[kunci]" value="<?= $h ?>" title="Tandai sebagai kunci"><b><?= $h ?></b><input name="<?= $nm ?>[opsi][<?= $h ?>]" placeholder="Pilihan <?= $h ?>"></label>
        <?php endforeach; ?>
        <small class="redup">Bulatan = kunci jawaban</small>
      </div>
    <?php elseif ($t === 'bs'): ?>
      <label>Kunci<select name="<?= $nm ?>[kunci]"><option value="A">Benar</option><option value="B">Salah</option></select></label>
    <?php elseif ($t === 'isian'): ?>
      <label>Kunci jawaban<input name="<?= $nm ?>[kunci]" placeholder="mis. fotosintesis (pisahkan dengan / bila ada beberapa)"></label>
    <?php else: ?>
      <label>Pedoman / jawaban yang diharapkan<textarea name="<?= $nm ?>[kunci]" rows="2" placeholder="poin-poin jawaban & skor"></textarea></label>
    <?php endif; ?>
    <details><summary class="kecil">Pembahasan (opsional)</summary><textarea name="<?= $nm ?>[pembahasan]" rows="2"></textarea></details>
  </div>
  <?php endfor; endforeach; ?>
  <div class="kartu lengket-bawah">
    <label class="centang"><input type="checkbox" name="jadikan_paket" value="1" checked> Langsung jadikan paket soal</label>
    <button class="utama lebar">💾 Simpan semua soal</button>
    <p class="kecil redup">Soal yang pertanyaannya kosong dilewati.</p>
  </div>
</form>
<?php endif; ?>
<?php
kaki();

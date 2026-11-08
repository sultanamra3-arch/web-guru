<?php
declare(strict_types=1);

$id = (int)get('id');
$d = satu("SELECT * FROM guru_dokumen WHERE id = ? AND guru_id = ? AND jenis = 'presentasi'", [$id, uid()]);
if (!$d) ke('?p=perangkat');
$data = json_decode((string)$d['isi'], true) ?: ['judul' => $d['judul'], 'subjudul' => '', 'slide' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('aksi') === 'hapus') {
        q('DELETE FROM guru_dokumen WHERE id = ?', [$id]);
        pesan('Presentasi dihapus.');
        ke('?p=perangkat');
    }
    $slide = [];
    foreach ((array)($_POST['s'] ?? []) as $s) {
        $j = trim((string)($s['judul'] ?? ''));
        $p = array_values(array_filter(array_map('trim', preg_split('/\r\n|\n/', (string)($s['poin'] ?? ''))), 'strlen'));
        if ($j === '' && !$p) continue;
        $slide[] = ['judul' => $j, 'poin' => $p, 'catatan' => trim((string)($s['catatan'] ?? ''))];
    }
    $data = ['judul' => trim((string)post('judul')) ?: $d['judul'], 'subjudul' => trim((string)post('subjudul')), 'slide' => $slide];
    q('UPDATE guru_dokumen SET judul = ?, isi = ?, diubah = ? WHERE id = ?', [mb_substr($data['judul'], 0, 250), json_encode($data, JSON_UNESCAPED_UNICODE), sekarang(), $id]);
    pesan('Presentasi tersimpan.');
    ke('?p=presentasi&id=' . $id);
}

kepala($d['judul'], 'perangkat');
?>
<div class="judul-baris"><h1><?= h($d['judul']) ?></h1><a class="tombol" href="?p=perangkat">‹ Perangkat</a></div>
<div class="kartu alat-dokumen">
  <button class="utama" id="mulai-tayang">▶ Tayangkan</button>
  <button id="unduh-pptx">⬇ PowerPoint (.pptx)</button>
  <button type="button" onclick="document.getElementById('sunting').toggleAttribute('hidden')">✏️ Ubah slide</button>
  <form method="post" class="sebaris" onsubmit="return confirm('Hapus presentasi ini?')"><?= input_token() ?><input type="hidden" name="aksi" value="hapus"><button class="kecil bahaya">Hapus</button></form>
</div>

<div id="deck" class="deck" data-sekolah="<?= h((string)k('sekolah')) ?>" data-guru="<?= h(pengguna()['nama']) ?>">
  <section class="slide judul-slide"><img src="aset/logo.png" alt=""><h2><?= h($data['judul']) ?></h2><p><?= h($data['subjudul']) ?></p><small><?= h(pengguna()['nama']) ?> · <?= h((string)k('sekolah')) ?></small></section>
  <?php foreach ($data['slide'] as $i => $s): ?>
  <section class="slide"><h2><?= h($s['judul']) ?></h2><ul><?php foreach ($s['poin'] as $p): ?><li><?= h($p) ?></li><?php endforeach; ?></ul>
    <?php if ($s['catatan']): ?><aside class="catatan"><?= h($s['catatan']) ?></aside><?php endif; ?><span class="nomor"><?= $i + 2 ?></span></section>
  <?php endforeach; ?>
</div>
<script type="application/json" id="data-presentasi"><?= json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>

<form method="post" id="sunting" class="formulir" hidden>
  <?= input_token() ?>
  <fieldset><legend>Judul</legend>
    <label>Judul<input name="judul" value="<?= h($data['judul']) ?>"></label>
    <label>Subjudul<input name="subjudul" value="<?= h($data['subjudul']) ?>"></label>
  </fieldset>
  <?php foreach (array_merge($data['slide'], [['judul' => '', 'poin' => [], 'catatan' => '']]) as $i => $s): ?>
  <fieldset><legend>Slide <?= $i + 2 ?><?= $s['judul'] === '' ? ' (baru, opsional)' : '' ?></legend>
    <label>Judul<input name="s[<?= $i ?>][judul]" value="<?= h($s['judul']) ?>"></label>
    <label>Poin <small>(satu per baris; kosongkan judul & poin untuk menghapus slide)</small><textarea name="s[<?= $i ?>][poin]" rows="4"><?= h(implode("\n", $s['poin'])) ?></textarea></label>
    <label>Catatan pembicara<textarea name="s[<?= $i ?>][catatan]" rows="2"><?= h($s['catatan']) ?></textarea></label>
  </fieldset>
  <?php endforeach; ?>
  <button class="utama lebar">Simpan slide</button>
</form>
<script src="aset/lib/pptxgen.bundle.js?v=3.12.0" defer></script>
<?php
kaki();

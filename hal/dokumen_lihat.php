<?php
declare(strict_types=1);

$id = (int)get('id');
$d = satu("SELECT * FROM guru_dokumen WHERE id = ? AND guru_id = ? AND jenis IN ('modul','lkpd')", [$id, uid()]);
if (!$d) ke('?p=perangkat');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('aksi') === 'hapus') {
        q('DELETE FROM guru_dokumen WHERE id = ?', [$id]);
        pesan('Dokumen dihapus.');
        ke('?p=perangkat');
    }
    $isi = (string)($_POST['isi'] ?? '');
    $judul = trim((string)post('judul')) ?: $d['judul'];
    q('UPDATE guru_dokumen SET judul = ?, isi = ?, diubah = ? WHERE id = ?', [mb_substr($judul, 0, 250), $isi, sekarang(), $id]);
    pesan('Perubahan tersimpan.');
    ke('?p=dokumen_lihat&id=' . $id);
}

$html = md((string)$d['isi']);
if (get('word')) {
    header('Content-Type: application/msword; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $d['judul']) . '.doc"');
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word"><head><meta charset="utf-8"><title>' . h($d['judul']) . '</title>'
        . '<style>body{font-family:"Times New Roman";font-size:12pt;line-height:1.4}h1{font-size:16pt;text-align:center}h2{font-size:13pt;margin-top:14pt}h3{font-size:12pt}'
        . 'table{border-collapse:collapse;width:100%;margin:6pt 0}td,th{border:1px solid #444;padding:4pt;vertical-align:top}th{background:#e8eaf6}</style></head><body>'
        . $html . '</body></html>';
    exit;
}

kepala($d['judul'], 'perangkat');
?>
<div class="judul-baris tak-cetak"><h1><?= h($d['judul']) ?></h1><a class="tombol" href="?p=perangkat">‹ Perangkat</a></div>
<div class="kartu tak-cetak alat-dokumen">
  <button class="utama" onclick="window.print()">🖨 Cetak / PDF</button>
  <a class="tombol" href="?p=dokumen_lihat&amp;id=<?= $id ?>&amp;word=1">⬇ Word</a>
  <button type="button" onclick="document.getElementById('sunting').toggleAttribute('hidden')">✏️ Ubah teks</button>
  <form method="post" class="sebaris" onsubmit="return confirm('Hapus dokumen ini?')"><?= input_token() ?><input type="hidden" name="aksi" value="hapus"><button class="kecil bahaya">Hapus</button></form>
</div>
<form class="kartu tak-cetak" id="form-revisi" data-token="<?= h(token()) ?>" data-id="<?= $id ?>">
  <label><b>✨ Minta AI merevisi</b>
    <input name="perintah" placeholder="mis. tambahkan diferensiasi untuk siswa cepat; ubah jadi 2 pertemuan; tambah 5 soal HOTS"></label>
  <button class="kecil utama">Revisi</button> <span id="status-ai" class="kecil"></span>
</form>
<form method="post" id="sunting" class="kartu tak-cetak formulir" hidden>
  <?= input_token() ?>
  <label>Judul<input name="judul" value="<?= h($d['judul']) ?>"></label>
  <label>Isi <small>(format Markdown: # judul, ## subjudul, **tebal**, - poin, tabel | a | b |)</small><textarea name="isi" rows="22" class="kode-md"><?= h($d['isi']) ?></textarea></label>
  <button class="utama">Simpan perubahan</button>
</form>
<article class="kertas dokumen-md"><?= $html ?></article>
<?php
kaki();

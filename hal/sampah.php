<?php
/* Sampah: data yang dihapus, bisa dikembalikan selama 30 hari. */
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string)post('aksi');
    $id = (int)post('id');
    if ($aksi === 'kembalikan') {
        $r = sampah_kembalikan($id);
        if ($r['ok']) {
            pesan('"' . h($r['judul']) . '" dikembalikan. <a href="' . h(sampah_alamat($r['jenis'], $r['data'])) . '">Lihat</a>');
            if (post('dari') === 'batal') ke(sampah_alamat($r['jenis'], $r['data']));
        } else pesan(h($r['galat']), 'galat');
    } elseif ($aksi === 'hapus') {
        $s = satu('SELECT * FROM guru_sampah WHERE id = ? AND guru_id = ?', [$id, uid()]);
        if ($s) { sampah_hapus_permanen($s); pesan('"' . h($s['judul']) . '" dihapus permanen.'); }
    } elseif ($aksi === 'kosongkan') {
        $n = 0;
        foreach (semua('SELECT * FROM guru_sampah WHERE guru_id = ?', [uid()]) as $s) { sampah_hapus_permanen($s); $n++; }
        pesan($n . ' isi sampah dihapus permanen.');
    }
    ke('?p=sampah');
}

$daftar = semua('SELECT id, jenis, judul, dihapus, LENGTH(data) ukuran FROM guru_sampah WHERE guru_id = ? ORDER BY dihapus DESC', [uid()]);
kepala('Sampah', 'lainnya');
judul_baris('Sampah', '<a class="tombol" href="?p=lainnya">‹ Lainnya</a>');
?>
<p class="redup kecil" style="margin-top:0">Data yang dihapus disimpan di sini selama <b><?= SAMPAH_HARI ?> hari</b>, lalu terhapus otomatis. Tekan <b>Kembalikan</b> untuk memulihkan persis seperti semula (termasuk jawaban, nilai, dan foto siswa).</p>
<?php if (!$daftar): ?>
  <div class="kartu kosong-sampah"><?= ikon('sampah') ?><p>Sampah kosong.</p></div>
<?php else: ?>
<ul class="daftar kartu daftar-sampah">
  <?php foreach ($daftar as $s):
      $sisa = SAMPAH_HARI - (int)floor((time() - strtotime($s['dihapus'])) / 86400); ?>
  <li>
    <div class="sampah-isi">
      <small class="lencana"><?= h(SAMPAH_JENIS[$s['jenis']] ?? $s['jenis']) ?></small>
      <b><?= h($s['judul']) ?></b>
      <small class="redup">Dihapus <?= h(tgl(substr($s['dihapus'], 0, 10))) ?> <?= h(substr($s['dihapus'], 11, 5)) ?> · <?= $sisa <= 3 ? '<span class="merah">' : '' ?>sisa <?= max(0, $sisa) ?> hari<?= $sisa <= 3 ? '</span>' : '' ?></small>
    </div>
    <div class="sampah-aksi">
      <form method="post"><?= input_token() ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button name="aksi" value="kembalikan" class="kecil utama">Kembalikan</button></form>
      <form method="post" onsubmit="return confirm('Hapus permanen?\nData ini tidak bisa dikembalikan lagi.')"><?= input_token() ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button name="aksi" value="hapus" class="kecil bahaya">Hapus permanen</button></form>
    </div>
  </li>
  <?php endforeach; ?>
</ul>
<form method="post" onsubmit="return confirm('Kosongkan sampah?\nSemua isi sampah (<?= count($daftar) ?>) dihapus permanen dan tidak bisa dikembalikan.')"><?= input_token() ?><input type="hidden" name="aksi" value="kosongkan"><button class="lebar bahaya">Kosongkan sampah</button></form>
<?php endif;
kaki();

<?php
declare(strict_types=1);

$jenis = (string)get('jenis');
$par = [uid()];
$w = '';
if (isset(JENIS_DOKUMEN[$jenis])) { $w = ' AND jenis = ?'; $par[] = $jenis; }
$daftar = semua("SELECT id, jenis, judul, mapel, kelas, diubah FROM guru_dokumen WHERE guru_id = ? $w ORDER BY diubah DESC LIMIT 300", $par);

kepala('Perangkat ajar', 'perangkat');
$buat = ['modul' => ['＋ Modul ajar', '?p=dokumen_buat&amp;jenis=modul'], 'presentasi' => ['＋ Presentasi', '?p=dokumen_buat&amp;jenis=presentasi'],
    'kisi' => ['＋ Kisi-kisi', '?p=kisi'], 'paket' => ['＋ Paket dari bank soal', '?p=soal']];
judul_baris('Perangkat ajar');
tab_perangkat(isset(JENIS_DOKUMEN[$jenis]) ? $jenis : '');
?>
<div class="baris-buat">
  <?php foreach ($jenis !== '' && isset($buat[$jenis]) ? [$buat[$jenis]] : array_slice($buat, 0, 3) as [$t, $u]): ?><a class="tombol kecil" href="<?= $u ?>"><?= $t ?></a><?php endforeach; ?>
  <a class="tombol kecil utama" href="?p=soal_ai"><?= ikon('ai') ?> Soal AI</a>
</div>
<ul class="daftar kartu">
  <?php if (!$daftar): ?><li class="redup">Belum ada. Tekan salah satu tombol ＋ di atas.</li><?php endif; ?>
  <?php foreach ($daftar as $d): $hal = ['presentasi' => 'presentasi', 'paket' => 'paket_cetak', 'kisi' => 'kisi'][$d['jenis']] ?? 'dokumen_lihat'; ?>
    <li><a href="?p=<?= $hal ?>&amp;id=<?= (int)$d['id'] ?>"><b><?= h($d['judul']) ?></b>
      <small><?= h(JENIS_DOKUMEN[$d['jenis']] ?? $d['jenis']) ?><?= $d['mapel'] ? ' · ' . h($d['mapel']) : '' ?><?= $d['kelas'] ? ' · Kelas ' . h($d['kelas']) : '' ?> · <?= tgl($d['diubah']) ?></small></a></li>
  <?php endforeach; ?>
</ul>
<?php
kaki();

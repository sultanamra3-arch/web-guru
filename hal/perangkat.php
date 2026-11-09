<?php
declare(strict_types=1);

$jenis = (string)get('jenis');
$par = [uid()];
$w = '';
if (isset(JENIS_DOKUMEN[$jenis])) { $w = ' AND jenis = ?'; $par[] = $jenis; }
$daftar = semua("SELECT id, jenis, judul, mapel, kelas, diubah FROM guru_dokumen WHERE guru_id = ? $w ORDER BY diubah DESC LIMIT 300", $par);

kepala('Perangkat ajar', 'perangkat');
judul_baris('Perangkat ajar');
?>
<div class="tombol-besar">
  <a class="utama" href="?p=dokumen_buat&amp;jenis=modul"><?= ikon('modul') ?><span>Modul ajar</span></a>
  <a href="?p=kisi"><?= ikon('kisi') ?><span>Kisi-kisi</span></a>
  <a href="?p=dokumen_buat&amp;jenis=presentasi"><?= ikon('presentasi') ?><span>Presentasi</span></a>
  <a href="?p=soal_ai"><?= ikon('ai') ?><span>Soal</span></a>
</div>
<div class="tab">
  <a href="?p=perangkat" class="<?= $jenis === '' ? 'aktif' : '' ?>">Semua</a>
  <?php $ada_lkpd = (bool)nilai("SELECT 1 FROM guru_dokumen WHERE guru_id = ? AND jenis = 'lkpd' LIMIT 1", [uid()]);
  foreach (JENIS_DOKUMEN as $k => $n): if ($k === 'lkpd' && !$ada_lkpd) continue; ?><a href="?p=perangkat&amp;jenis=<?= $k ?>" class="<?= $jenis === $k ? 'aktif' : '' ?>"><?= $n ?></a><?php endforeach; ?>
</div>
<ul class="daftar kartu">
  <?php if (!$daftar): ?><li class="redup">Belum ada. Tekan salah satu tombol ✨ di atas.</li><?php endif; ?>
  <?php foreach ($daftar as $d): $hal = ['presentasi' => 'presentasi', 'paket' => 'paket_cetak', 'kisi' => 'kisi'][$d['jenis']] ?? 'dokumen_lihat'; ?>
    <li><a href="?p=<?= $hal ?>&amp;id=<?= (int)$d['id'] ?>"><b><?= h($d['judul']) ?></b>
      <small><?= h(JENIS_DOKUMEN[$d['jenis']] ?? $d['jenis']) ?><?= $d['mapel'] ? ' · ' . h($d['mapel']) : '' ?><?= $d['kelas'] ? ' · Kelas ' . h($d['kelas']) : '' ?> · <?= tgl($d['diubah']) ?></small></a></li>
  <?php endforeach; ?>
</ul>
<?php
kaki();

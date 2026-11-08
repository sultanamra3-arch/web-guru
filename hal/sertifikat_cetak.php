<?php
declare(strict_types=1);

$id = (int)get('id');
$s = satu('SELECT * FROM guru_sertifikat WHERE id = ? AND guru_id = ?', [$id, uid()]);
if (!$s) ke('?p=sertifikat');
$penerima = semua('SELECT * FROM guru_sertifikat_penerima WHERE sertifikat_id = ? ORDER BY urut', [$id]);
$warna = ['navy' => ['#2A2C84', '#1B1D5E'], 'hijau' => ['#1f6f3f', '#123f24'], 'merah' => ['#7a1f2b', '#4d1018'], 'hitam' => ['#222', '#000']][$s['warna']] ?? ['#2A2C84', '#1B1D5E'];

kepala('Cetak sertifikat', '', true);
?>
<style>
@page { size: A4 landscape; margin: 0; }
body.polos { background: #e9ecf3; padding: 0; }
.alat-cetak { position: sticky; top: 0; z-index: 5; display: flex; gap: .5rem; justify-content: center; padding: .6rem; background: #fff; border-bottom: 1px solid #ddd; }
.lembar { width: 297mm; height: 210mm; margin: 12px auto; background: #fff; position: relative; overflow: hidden; page-break-after: always; box-shadow: 0 4px 20px rgba(0,0,0,.12);
  --u: <?= $warna[0] ?>; --g: <?= $warna[1] ?>; --e: #c9a227; font-family: 'Quicksand', sans-serif; color: #222; }
.lembar:last-child { page-break-after: auto; }
.bingkai { position: absolute; inset: 9mm; border: 2.2mm solid var(--u); outline: .8mm solid var(--e); outline-offset: -4.2mm; }
.sudut { position: absolute; width: 46mm; height: 46mm; background: linear-gradient(135deg, var(--u) 0 50%, transparent 50%); }
.sudut.a { top: 0; left: 0; } .sudut.b { top: 0; right: 0; transform: scaleX(-1); } .sudut.c { bottom: 0; left: 0; transform: scaleY(-1); } .sudut.d { bottom: 0; right: 0; transform: scale(-1); }
.sudut::after { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, transparent 0 52%, var(--e) 52% 55%, transparent 55%); }
.isi { position: absolute; inset: 20mm 26mm 18mm; display: flex; flex-direction: column; align-items: center; text-align: center; }
.isi img { width: 24mm; height: 24mm; object-fit: contain; }
.sekolah { font-weight: 700; letter-spacing: .14em; color: var(--g); font-size: 11pt; margin-top: 2mm; text-transform: uppercase; }
.judul { font-family: Georgia, 'Times New Roman', serif; font-size: 36pt; letter-spacing: .18em; color: var(--u); margin: 3mm 0 0; font-weight: 700; }
.nomor { font-size: 10pt; color: #555; margin-bottom: 5mm; }
.sebutan { font-size: 12pt; color: #444; }
.nama { font-family: Georgia, 'Times New Roman', serif; font-size: 30pt; font-weight: 700; color: #111; margin: 3mm 0 1mm; padding: 0 10mm 1.5mm; border-bottom: .6mm solid var(--e); }
.ket-orang { font-weight: 700; color: var(--g); font-size: 13pt; margin-top: 1mm; }
.teks { font-size: 12.5pt; max-width: 210mm; margin-top: 4mm; line-height: 1.5; }
.ttd { position: absolute; left: 0; right: 0; bottom: 0; display: flex; justify-content: space-around; font-size: 11pt; }
.ttd div { min-width: 70mm; }
.ttd .garis { height: 20mm; }
.ttd b { text-decoration: underline; }
@media print { .alat-cetak { display: none; } body.polos { background: #fff; } .lembar { margin: 0; box-shadow: none; } }
</style>
<div class="alat-cetak">
  <button class="utama" onclick="window.print()">🖨 Cetak / Simpan PDF</button>
  <a class="tombol" href="?p=sertifikat">‹ Kembali</a>
  <span class="kecil redup" style="align-self:center">Pilih kertas A4 mendatar (landscape), margin "Tidak ada", centang "Grafik latar".</span>
</div>
<?php foreach ($penerima as $i => $pn):
    $nomor = str_replace('{n}', str_pad((string)($i + 1), 3, '0', STR_PAD_LEFT), (string)$s['format_nomor']); ?>
<section class="lembar">
  <div class="sudut a"></div><div class="sudut b"></div><div class="sudut c"></div><div class="sudut d"></div>
  <div class="bingkai"></div>
  <div class="isi">
    <img src="aset/logo.png" alt="">
    <div class="sekolah"><?= h((string)k('sekolah')) ?></div>
    <div class="judul"><?= h($s['judul']) ?></div>
    <?php if ($nomor !== ''): ?><div class="nomor">Nomor: <?= h($nomor) ?></div><?php endif; ?>
    <div class="sebutan"><?= h($s['sebutan']) ?></div>
    <div class="nama"><?= h($pn['nama']) ?></div>
    <?php if ($pn['keterangan']): ?><div class="ket-orang"><?= h($pn['keterangan']) ?></div><?php endif; ?>
    <div class="teks"><?= nl2br(h($s['isi'])) ?></div>
    <div class="ttd">
      <div><?= $s['ttd2_nama'] ? '<br>' : h($s['kota']) . ', ' . tgl_panjang($s['tanggal']) . '<br>' ?><?= h($s['ttd1_jabatan']) ?><div class="garis"></div><b><?= h($s['ttd1_nama'] ?: '..............................') ?></b><br><?= $s['ttd1_nip'] ? 'NIP ' . h($s['ttd1_nip']) : '' ?></div>
      <?php if ($s['ttd2_nama']): ?>
      <div><?= h($s['kota']) ?>, <?= tgl_panjang($s['tanggal']) ?><br><?= h($s['ttd2_jabatan']) ?><div class="garis"></div><b><?= h($s['ttd2_nama']) ?></b><br><?= $s['ttd2_nip'] ? 'NIP ' . h($s['ttd2_nip']) : '' ?></div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endforeach;
kaki(true);

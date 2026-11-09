<?php
declare(strict_types=1);

$id = (int)get('id');
$p = satu('SELECT * FROM guru_penilaian WHERE id = ? AND guru_id = ?', [$id, uid()]);
$kk = $p ? satu('SELECT * FROM guru_koreksi_kunci WHERE penilaian_id = ?', [$id]) : null;
if (!$p || !$kk) ke('?p=penilaian');
$soal = koreksi_urai_kunci((string)$kk['teks']);
$pilih = array_values(array_filter($soal, fn($s) => in_array($s['jenis'], ['pg', 'bs'], true)));
$uraian = array_values(array_filter($soal, fn($s) => !in_array($s['jenis'], ['pg', 'bs'], true)));
$ada_bs = (bool)array_filter($pilih, fn($s) => $s['jenis'] === 'bs');
$per_kolom = count($pilih) > 40 ? 15 : 10;
$kolom = array_chunk($pilih, $per_kolom);

kepala('Lembar jawaban · ' . $p['judul'], '', true);
?>
<div class="alat-dokumen tak-cetak">
  <a class="tombol" href="?p=koreksi&amp;id=<?= $id ?>">‹ Kembali</a>
  <button class="utama" onclick="window.print()">🖨 Cetak</button>
  <span class="kecil redup">Kertas A4. Cetak sebanyak jumlah siswa.</span>
</div>
<div class="lj">
  <table class="lj-kop"><tr>
    <td style="width:62px"><img src="aset/logo.png" alt="" style="width:54px"></td>
    <td><b><?= h((string)k('sekolah')) ?></b><br>LEMBAR JAWABAN — <?= h($p['judul']) ?><br><?= h($p['mapel']) ?> · Kelas <?= h($p['kelas']) ?></td>
  </tr></table>
  <table class="lj-id">
    <colgroup><col style="width:11%"><col style="width:52%"><col style="width:15%"><col style="width:22%"></colgroup>
    <tr><td>Nama</td><td class="isi"></td><td>No. absen</td><td class="isi"></td></tr>
    <tr><td>Kelas</td><td class="isi"></td><td>Tanggal</td><td class="isi"></td></tr>
  </table>
  <div class="lj-petunjuk">
    <b>Cara mengisi:</b> tulis <b>SATU huruf kapital</b> (A, B, C, D, atau E<?= $ada_bs ? '; benar/salah: B atau S' : '' ?>) di dalam kotak, tebal dan jelas.
    Untuk <b>mengganti</b>: coret huruf lama dengan dua garis (=), lalu tulis huruf baru di <b>samping kanan</b> kotak. Jangan menulis di luar kotak selain itu.
    <div class="lj-contoh">Contoh: <span class="kotak">B</span> benar &nbsp;·&nbsp; <span class="kotak"><s style="text-decoration-style:double">A</s></span> C &nbsp;= jawaban C</div>
  </div>
  <?php if ($pilih): ?>
  <div class="lj-kolom">
    <?php foreach ($kolom as $kol): ?>
    <div class="lj-kol">
      <?php foreach ($kol as $s): ?>
      <div class="lj-baris"><span class="lj-no"><?= h($s['no']) ?></span><span class="kotak"></span><span class="lj-ganti"></span><?= $s['jenis'] === 'bs' ? '<small>B/S</small>' : '' ?></div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php foreach ($uraian as $s): ?>
  <div class="lj-uraian">
    <b><?= h($s['no']) ?>.</b> <span class="kecil">(<?= h(JENIS_KOREKSI[$s['jenis']] ?? $s['jenis']) ?>)</span>
    <?php for ($i = 0; $i < ($s['jenis'] === 'isian' ? 1 : 6); $i++): ?><div class="garis"></div><?php endfor; ?>
  </div>
  <?php endforeach; ?>
</div>
<style>
.lj{background:#fff;color:#000;max-width:190mm;margin:0 auto;padding:6mm;font-family:Arial,Helvetica,sans-serif;font-size:11pt}
.lj-kop{width:100%;border-bottom:2px solid #000;margin-bottom:3mm}.lj-kop td{vertical-align:middle}
.lj-id{width:100%;border-collapse:collapse;margin-bottom:3mm;table-layout:fixed}.lj-id td{border:0}.lj-id td{padding:2.5mm 1.5mm;font-size:10.5pt}.lj-id td.isi{border-bottom:1px solid #000}
.lj-petunjuk{border:1px solid #000;padding:2mm 3mm;font-size:9.5pt;margin-bottom:4mm;line-height:1.45}
.lj-contoh{margin-top:1mm}.lj-contoh .kotak{width:7mm;height:7mm;vertical-align:middle;font-weight:700;display:inline-flex;align-items:center;justify-content:center}
.lj-kolom{display:flex;gap:6mm;justify-content:space-between}.lj-kol{flex:1}
.lj-baris{display:flex;align-items:center;gap:2mm;height:11mm;border-bottom:1px dotted #bbb}
.lj-no{width:9mm;text-align:right;font-weight:700;font-size:12pt}
.kotak{display:inline-block;width:10mm;height:9mm;border:1.6px solid #000;border-radius:1mm}
.lj-ganti{flex:1;max-width:14mm;height:9mm}.lj-baris small{font-size:8pt;color:#444}
.lj-uraian{margin-top:4mm;break-inside:avoid}.lj-uraian .garis{height:8mm;border-bottom:1px solid #777}
@media print{body{background:#fff}.lj{padding:0;max-width:none}@page{size:A4;margin:12mm}}
</style>
<?php
kaki(true);

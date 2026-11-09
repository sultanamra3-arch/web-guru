<?php
declare(strict_types=1);

$id = (int)get('id');
$d = satu("SELECT * FROM guru_dokumen WHERE id = ? AND guru_id = ? AND jenis = 'paket'", [$id, uid()]);
if (!$d) ke('?p=soal');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('aksi') === 'hapus') {
    q('DELETE FROM guru_dokumen WHERE id = ?', [$id]);
    pesan('Paket soal dihapus (soal di bank soal tetap ada).');
    ke('?p=perangkat&jenis=paket');
}
$isi = json_decode((string)$d['isi'], true) ?: [];
$ids = array_map('intval', $isi['ids'] ?? []);
$peta = [];
if ($ids) foreach (semua('SELECT * FROM guru_soal WHERE id IN (' . implode(',', $ids) . ')') as $s) $peta[$s['id']] = $s;
$soal = array_values(array_filter(array_map(fn($i) => $peta[$i] ?? null, $ids)));

function lembar_soal(array $d, array $isi, array $soal, bool $kunci, bool $word = false): string
{
    ob_start(); ?>
<div class="lembar-soal">
  <table class="kop-soal"><tr>
    <?php if (!$word): ?><td style="width:70px"><img src="aset/logo.png" alt="" style="width:60px"></td><?php endif; ?>
    <td><b style="font-size:14pt"><?= h((string)k('sekolah')) ?></b><br><?= h($isi['jenis_ujian'] ?? '') ?><br>Tahun Ajaran <?= h((string)k('tahun_ajaran')) ?></td>
  </tr></table>
  <table class="identitas-soal">
    <tr><td>Mata pelajaran</td><td>: <?= h($isi['mapel'] ?? '') ?></td><td>Nama</td><td>: ..............................</td></tr>
    <tr><td>Kelas</td><td>: <?= h($isi['kelas'] ?? '') ?></td><td>No. absen</td><td>: ..........</td></tr>
    <tr><td>Waktu</td><td>: <?= h($isi['waktu'] ?? '') ?></td><td>Tanggal</td><td>: ..............................</td></tr>
  </table>
  <?php if (!empty($isi['petunjuk'])): ?><p><b>Petunjuk:</b></p><ol class="petunjuk"><?php foreach (preg_split('/\r\n|\n/', $isi['petunjuk']) as $p) if (trim($p) !== '') echo '<li>' . h(trim($p)) . '</li>'; ?></ol><?php endif; ?>
  <?php
  $bagian = ['pg' => 'Pilihan Ganda', 'bs' => 'Benar atau Salah', 'isian' => 'Isian Singkat', 'esai' => 'Uraian'];
  $no = 0; $bag_kini = null; $romawi = ['I', 'II', 'III', 'IV'];  $bi = 0;
  foreach ($soal as $s):
      if ($s['tipe'] !== $bag_kini) { $bag_kini = $s['tipe']; echo '<h3 class="bagian-soal">' . $romawi[$bi++ % 4] . '. ' . h($bagian[$bag_kini] ?? $bag_kini) . '</h3>'; }
      $no++; $opsi = json_decode((string)$s['opsi'], true) ?: []; ?>
    <div class="soal-cetak">
      <div class="no"><?= $no ?>.</div>
      <div class="badan"><?= nl2br(h($s['pertanyaan'])) ?>
        <?php if ($opsi): ?><table class="opsi-cetak"><?php foreach ($opsi as $hk => $ov): ?><tr><td><?= h($hk) ?>.</td><td><?= h($ov) ?></td></tr><?php endforeach; ?></table>
        <?php elseif ($s['tipe'] === 'esai' && !$kunci): ?><div class="ruang-jawab"></div>
        <?php elseif ($s['tipe'] === 'isian' && !$kunci): ?><div>Jawab: ........................................................</div><?php endif; ?>
        <?php if ($kunci): ?><div class="kunci-cetak"><b>Kunci:</b> <?= nl2br(h((string)$s['kunci'])) ?><?= $s['pembahasan'] ? '<br><b>Pembahasan:</b> ' . nl2br(h($s['pembahasan'])) : '' ?><?= $s['level'] ? ' <i>(' . h($s['level']) . ')</i>' : '' ?></div><?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
</div>
<?php
    return (string)ob_get_clean();
}

if (get('word')) {
    $kunci = (bool)get('kunci');
    header('Content-Type: application/msword; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $d['judul'] . ($kunci ? '_kunci' : '')) . '.doc"');
    echo '<html><head><meta charset="utf-8"><style>body{font-family:"Times New Roman";font-size:12pt}table{border-collapse:collapse}td{vertical-align:top;padding:2px 6px}.soal-cetak{margin:6px 0}.no{float:left;width:28px}.badan{margin-left:28px}.ruang-jawab{height:90px}.kunci-cetak{color:#1d6b33}.bagian-soal{font-size:12pt;margin:14px 0 4px}</style></head><body>';
    echo lembar_soal($d, $isi, $soal, $kunci, true);
    echo '</body></html>';
    exit;
}

kepala($d['judul'], 'soal');
?>
<div class="judul-baris tak-cetak"><h1><?= h($d['judul']) ?></h1><a class="tombol" href="?p=soal">‹ Bank soal</a></div>
<div class="kartu tak-cetak alat-dokumen">
  <button class="utama" onclick="document.body.classList.remove('tampil-kunci');window.print()">🖨 Cetak soal</button>
  <button onclick="document.body.classList.add('tampil-kunci');window.print();document.body.classList.remove('tampil-kunci')">🖨 Cetak kunci</button>
  <a class="tombol utama" href="?p=ujian_form&amp;paket=<?= $id ?>">💻 Jadikan ujian online</a>
  <a class="tombol" href="?p=paket_cetak&amp;id=<?= $id ?>&amp;word=1">⬇ Word (soal)</a>
  <a class="tombol" href="?p=paket_cetak&amp;id=<?= $id ?>&amp;word=1&amp;kunci=1">⬇ Word (kunci)</a>
  <label class="centang"><input type="checkbox" onchange="document.body.classList.toggle('tampil-kunci', this.checked)"> Tampilkan kunci</label>
  <form method="post" class="sebaris" onsubmit="return confirm('Hapus paket ini?')"><?= input_token() ?><input type="hidden" name="aksi" value="hapus"><button class="kecil bahaya">Hapus paket</button></form>
</div>
<div class="kertas versi-soal"><?= lembar_soal($d, $isi, $soal, false) ?></div>
<div class="kertas versi-kunci"><?= lembar_soal($d, $isi, $soal, true) ?></div>
<?php
kaki();

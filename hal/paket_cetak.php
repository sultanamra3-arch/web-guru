<?php
declare(strict_types=1);

$id = (int)get('id');
$d = satu("SELECT * FROM guru_dokumen WHERE id = ? AND guru_id = ? AND jenis = 'paket'", [$id, uid()]);
if (!$d) ke('?p=soal');
$isi = json_decode((string)$d['isi'], true) ?: [];
$ids = array_map('intval', $isi['ids'] ?? []);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string)post('aksi');
    if ($aksi === 'hapus') {
        $lain = soal_di_paket($id);
        $sendiri = array_values(array_diff($ids, $lain));
        sampah_simpan('paket', $d['judul'], [sampah_baris('guru_dokumen', 'id = ?', [$id]),
            post('dengan_soal') && $sendiri ? sampah_baris('guru_soal', 'guru_id = ? AND id IN (' . implode(',', $sendiri) . ')', [uid()]) : ['baris' => []]]);
        if (post('dengan_soal') && $sendiri) q('DELETE FROM guru_soal WHERE guru_id = ? AND id IN (' . implode(',', $sendiri) . ')', [uid()]);
        q('DELETE FROM guru_dokumen WHERE id = ?', [$id]);
        pesan(post('dengan_soal') ? 'Paket soal dan soal-soalnya dipindah ke Sampah.' : 'Paket soal dipindah ke Sampah. Soal-soalnya kembali tampil di bank soal.');
        ke('?p=soal');
    }
    if ($aksi === 'kop') {
        foreach (['mapel', 'kelas', 'waktu', 'jenis_ujian', 'petunjuk'] as $k) $isi[$k] = trim((string)post($k));
        $isi = poin_paket_dari_post($isi);
        $judul = mb_substr(trim((string)post('judul')) ?: $d['judul'], 0, 250);
        q('UPDATE guru_dokumen SET judul = ?, mapel = ?, kelas = ?, isi = ?, diubah = ? WHERE id = ?',
            [$judul, $isi['mapel'], mb_substr($isi['kelas'], 0, 20), json_encode($isi, JSON_UNESCAPED_UNICODE), sekarang(), $id]);
        pesan('Kop paket tersimpan.');
    }
    if ($aksi === 'keluarkan' || $aksi === 'hapus_soal' || $aksi === 'naik' || $aksi === 'turun') {
        $sid = (int)post('soal');
        $pos = array_search($sid, $ids, true);
        if ($pos !== false) {
            if ($aksi === 'naik' && $pos > 0) [$ids[$pos - 1], $ids[$pos]] = [$ids[$pos], $ids[$pos - 1]];
            elseif ($aksi === 'turun' && $pos < count($ids) - 1) [$ids[$pos + 1], $ids[$pos]] = [$ids[$pos], $ids[$pos + 1]];
            elseif ($aksi === 'keluarkan' || $aksi === 'hapus_soal') {
                array_splice($ids, $pos, 1);
                if ($aksi === 'hapus_soal' && !in_array($sid, soal_di_paket($id), true)) {
                    $sb = sampah_baris('guru_soal', 'id = ? AND guru_id = ?', [$sid, uid()]);
                    sampah_simpan('soal', mb_substr((string)($sb['baris'][0]['pertanyaan'] ?? 'Soal'), 0, 80) . ' (dari paket ' . $d['judul'] . ')', [$sb], ['ke_paket' => [$id, $pos]]);
                }
                if ($aksi === 'hapus_soal' && !in_array($sid, soal_di_paket($id), true)) q('DELETE FROM guru_soal WHERE id = ? AND guru_id = ?', [$sid, uid()]);
            }
            $isi['ids'] = array_values($ids);
            q('UPDATE guru_dokumen SET isi = ?, diubah = ? WHERE id = ?', [json_encode($isi, JSON_UNESCAPED_UNICODE), sekarang(), $id]);
            pesan(['keluarkan' => 'Soal dikeluarkan dari paket dan kembali ke bank soal.', 'hapus_soal' => 'Soal dipindah ke Sampah.'][$aksi] ?? 'Urutan soal diubah.');
        }
    }
    ke('?p=paket_cetak&id=' . $id . (in_array($aksi, ['keluarkan', 'hapus_soal', 'naik', 'turun'], true) ? '&ubah=1#ubah-soal' : ''));
}
$peta = [];
if ($ids) foreach (semua('SELECT * FROM guru_soal WHERE guru_id = ? AND id IN (' . implode(',', $ids) . ')', [uid()]) as $s) $peta[$s['id']] = $s;
$soal = array_values(array_filter(array_map(fn($i) => $peta[$i] ?? null, $ids)));

/** Bagian "Pedoman penskoran" di lembar kunci: poin per jenis, skor maksimal, rubrik uraian. */
function pedoman_skor_html(array $isi, array $soal): string
{
    $pp = paket_poin($isi);
    $n_ur = count(array_filter($soal, fn($s) => $s['tipe'] === 'esai'));
    $n_obj = count($soal) - $n_ur;
    $maks = $n_obj * $pp['pg'] + $n_ur * $pp['uraian'];
    $f = fn($x) => str_replace('.', ',', (string)($x + 0));
    $o = '<h3 class="bagian-soal">Pedoman Penskoran</h3><ul class="pedoman-skor">';
    if ($n_obj) $o .= '<li>Pilihan ganda / benar-salah / isian: setiap jawaban benar = <b>' . $f($pp['pg']) . '</b>, salah/kosong = 0. Skor maksimal = ' . $n_obj . ' × ' . $f($pp['pg']) . ' = ' . $f($n_obj * $pp['pg']) . '.</li>';
    if ($n_ur) $o .= '<li>Uraian: skor 0–<b>' . $f($pp['uraian']) . '</b> per soal. Skor maksimal = ' . $n_ur . ' × ' . $f($pp['uraian']) . ' = ' . $f($n_ur * $pp['uraian']) . '.</li>';
    $o .= '<li>Nilai = ' . (abs($maks - 100) < 0.01 ? 'jumlah seluruh skor (maksimal 100)' : 'jumlah skor ÷ ' . $f($maks) . ' × 100') . '.</li></ul>';
    if ($n_ur && $pp['rubrik'] !== '') $o .= '<p><b>Rubrik penskoran uraian (per soal):</b></p><div class="rubrik-cetak">' . nl2br(h($pp['rubrik'])) . '</div>';
    return $o;
}

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
      $no++; $opsi = json_decode((string)$s['opsi'], true) ?: []; $go = gambar_opsi($s['gambar_opsi'] ?? null); ?>
    <div class="soal-cetak">
      <div class="no"><?= $no ?>.</div>
      <div class="badan"><?= nl2br(h($s['pertanyaan'])) ?>
        <?php if (!empty($s['gambar'])): ?><div class="gambar-cetak"><img src="<?= h(gambar_url($s['gambar'], $word)) ?>" alt=""<?= $word ? ' width="300"' : '' ?>></div><?php endif; ?>
        <?php if ($opsi): ?><table class="opsi-cetak"><?php foreach ($opsi as $hk => $ov): ?><tr><td><?= h($hk) ?>.</td><td><?= h($ov) ?><?php if (isset($go[$hk])): ?><br><img class="gambar-opsi-cetak" src="<?= h(gambar_url($go[$hk], $word)) ?>" alt=""<?= $word ? ' width="140"' : '' ?>><?php endif; ?></td></tr><?php endforeach; ?></table>
        <?php elseif ($s['tipe'] === 'esai' && !$kunci): ?><div class="ruang-jawab"></div>
        <?php elseif ($s['tipe'] === 'isian' && !$kunci): ?><div>Jawab: ........................................................</div><?php endif; ?>
        <?php if ($kunci): ?><div class="kunci-cetak"><b>Kunci:</b> <?= nl2br(h((string)$s['kunci'])) ?><?= $s['pembahasan'] ? '<br><b>Pembahasan:</b> ' . nl2br(h($s['pembahasan'])) : '' ?><?= $s['level'] ? ' <i>(' . h($s['level']) . ')</i>' : '' ?></div><?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if ($kunci): echo pedoman_skor_html($isi, $soal); endif; ?>
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
<p class="kecil redup tak-cetak"><?= count($soal) ?> soal<?php $hit = array_count_values(array_column($soal, 'tipe')); foreach ($hit as $t => $n) echo ' · ' . h(TIPE_SOAL[$t] ?? $t) . ' ' . $n; ?></p>
<div class="kartu tak-cetak alat-dokumen">
  <button class="utama" onclick="document.body.classList.remove('tampil-kunci');window.print()">🖨 Cetak soal</button>
  <button onclick="document.body.classList.add('tampil-kunci');window.print();document.body.classList.remove('tampil-kunci')">🖨 Cetak kunci</button>
  <a class="tombol utama" href="?p=ujian_form&amp;paket=<?= $id ?>">💻 Jadikan ujian online</a>
  <a class="tombol" href="?p=kisi&amp;paket=<?= $id ?>"><?= ikon('kisi') ?> Kisi-kisi</a>
  <a class="tombol" href="?p=paket_cetak&amp;id=<?= $id ?>&amp;word=1">⬇ Word (soal)</a>
  <a class="tombol" href="?p=paket_cetak&amp;id=<?= $id ?>&amp;word=1&amp;kunci=1">⬇ Word (kunci)</a>
  <label class="centang"><input type="checkbox" onchange="document.body.classList.toggle('tampil-kunci', this.checked)"> Tampilkan kunci</label>
</div>

<details class="kartu tak-cetak ubah-paket" id="ubah-soal"<?= get('ubah') ? ' open' : '' ?>>
  <summary><b>✎ Ubah soal di paket ini</b> <span class="kecil redup">(<?= count($soal) ?> soal)</span></summary>
  <p class="kecil redup">Perubahan langsung terlihat di lembar soal. Ujian online yang sudah dibuat dari paket ini tidak ikut berubah.</p>
  <ol class="daftar paket-soal">
  <?php foreach ($soal as $i => $s): ?>
    <li>
      <div class="ps-isi"><small class="meta"><b>No. <?= $i + 1 ?></b> · <?= h(TIPE_SOAL[$s['tipe']] ?? $s['tipe']) ?><?= $s['tipe'] === 'pg' || $s['tipe'] === 'bs' ? ' · kunci ' . h((string)$s['kunci']) : '' ?></small>
        <span><?= !empty($s['gambar']) ? '🖼 ' : '' ?><?= h(mb_strimwidth((string)$s['pertanyaan'], 0, 140, '…')) ?></span></div>
      <div class="ps-aksi">
        <a class="tombol kecil" href="?p=soal_form&amp;id=<?= (int)$s['id'] ?>&amp;paket=<?= $id ?>">Ubah</a>
        <form method="post" class="sebaris"><?= input_token() ?><input type="hidden" name="soal" value="<?= (int)$s['id'] ?>">
          <button name="aksi" value="naik" class="kecil" title="Naikkan" aria-label="Naikkan"<?= $i === 0 ? ' disabled' : '' ?>>↑</button><button name="aksi" value="turun" class="kecil" title="Turunkan" aria-label="Turunkan"<?= $i === count($soal) - 1 ? ' disabled' : '' ?>>↓</button>
          <button name="aksi" value="hapus_soal" class="kecil bahaya" onclick="return confirm('Hapus soal ini dari paket?\nBisa dikembalikan dari Sampah (Lainnya → Sampah) selama 30 hari.')">Hapus</button>
        </form>
      </div>
    </li>
  <?php endforeach; ?>
  </ol>
  <a class="tombol" href="?p=soal_form&amp;paket=<?= $id ?>">＋ Tambah soal ke paket ini</a>
</details>

<details class="kartu tak-cetak">
  <summary><b>✎ Ubah kop paket</b> <span class="kecil redup">(judul, mapel, kelas, waktu, petunjuk, poin & rubrik)</span></summary>
  <form method="post" class="formulir" style="margin-top:.6rem">
    <?= input_token() ?><input type="hidden" name="aksi" value="kop">
    <label>Judul paket<input name="judul" required value="<?= h($d['judul']) ?>"></label>
    <label>Jenis ujian<input name="jenis_ujian" value="<?= h($isi['jenis_ujian'] ?? '') ?>"></label>
    <div class="baris3">
      <label>Mapel<?= pilih_mapel((string)($isi['mapel'] ?? '')) ?></label>
      <label>Kelas<input name="kelas" value="<?= h($isi['kelas'] ?? '') ?>"></label>
      <label>Waktu<input name="waktu" value="<?= h($isi['waktu'] ?? '') ?>"></label>
    </div>
    <label>Petunjuk<textarea name="petunjuk" rows="3"><?= h($isi['petunjuk'] ?? '') ?></textarea></label>
    <?php $n_ur = count(array_filter($soal, fn($s) => $s['tipe'] === 'esai')); echo form_poin_paket($isi, count($soal) - $n_ur, $n_ur); ?>
    <button class="utama">Simpan kop</button>
  </form>
</details>

<details class="kartu tak-cetak">
  <summary><b class="merah">Hapus paket</b></summary>
  <form method="post" style="margin-top:.6rem" onsubmit="return confirm('Hapus paket ini?\nBisa dikembalikan dari Sampah (Lainnya → Sampah) selama 30 hari.')"><?= input_token() ?><input type="hidden" name="aksi" value="hapus">
    <label class="centang"><input type="checkbox" name="dengan_soal" value="1" checked> Hapus juga soal-soalnya (kalau tidak dicentang, soalnya kembali ke bank soal)</label>
    <button class="kecil bahaya">Hapus paket</button>
  </form>
</details>
<div class="kertas versi-soal"><?= lembar_soal($d, $isi, $soal, false) ?></div>
<div class="kertas versi-kunci"><?= lembar_soal($d, $isi, $soal, true) ?></div>
<?php
kaki();

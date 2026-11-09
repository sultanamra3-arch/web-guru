<?php
/* Ekspor nilai untuk e-Rapor: nilai akhir per siswa + kalimat capaian kompetensi, siap disalin ke e-Rapor. */
declare(strict_types=1);

const GRUP_SUMATIF = ['Sumatif Lingkup Materi', 'Ulangan Harian', 'Sumatif Tengah Semester'];
const GRUP_SAS = ['Sumatif Akhir Semester'];

$kelas_list = kelas_saya();
$kelas = (string)get('kelas', $kelas_list[0] ?? '');
$mapel_list = q('SELECT DISTINCT mapel FROM guru_penilaian WHERE guru_id = ? AND kelas = ? ORDER BY mapel', [uid(), $kelas])->fetchAll(PDO::FETCH_COLUMN);
$mapel = (string)get('mapel', $mapel_list[0] ?? '');
$tahun = tahun_aktif();
[$th1, $th2] = array_map('intval', explode('/', $tahun . '/')) + [0, 0];
$semester = get('semester') === 'Genap' || (!get('semester') && (int)date('n') <= 6) ? 'Genap' : 'Ganjil';
[$awal, $akhir] = $semester === 'Ganjil' ? ["$th1-07-01", "$th1-12-31"] : [($th2 ?: $th1 + 1) . '-01-01', ($th2 ?: $th1 + 1) . '-06-30'];
$pen = semua('SELECT * FROM guru_penilaian WHERE guru_id = ? AND kelas = ? AND mapel = ? AND tanggal BETWEEN ? AND ? ORDER BY tanggal, id', [uid(), $kelas, $mapel, $awal, $akhir]);

// penilaian yang dihitung: bawaan = semua sumatif (+ SAS); guru bisa mencentang sendiri
$dipilih = isset($_GET['pakai']) ? array_map('intval', (array)$_GET['pakai'])
    : array_map(fn($p) => (int)$p['id'], array_filter($pen, fn($p) => in_array($p['jenis'], array_merge(GRUP_SUMATIF, GRUP_SAS), true)));
if (!$dipilih && !isset($_GET['pakai'])) $dipilih = array_map(fn($p) => (int)$p['id'], $pen); // belum ada sumatif: pakai semua
$bobot_sas = max(0, min(100, (int)get('bobot_sas', 50)));
$kktp = $pen ? (float)$pen[0]['kktp'] : 75;

$siswa = kelas_valid($kelas) ? siswa_kelas($kelas) : [];
$nilai = [];
if ($pen) foreach (semua('SELECT penilaian_id, member_id, nilai FROM guru_nilai WHERE penilaian_id IN (' . implode(',', array_map('intval', array_column($pen, 'id'))) . ')') as $r) {
    if ($r['nilai'] !== null) $nilai[$r['member_id']][(int)$r['penilaian_id']] = (float)$r['nilai'];
}
$pakai = array_values(array_filter($pen, fn($p) => in_array((int)$p['id'], $dipilih, true)));
$ada_sas = (bool)array_filter($pakai, fn($p) => in_array($p['jenis'], GRUP_SAS, true));

function materi_singkat(string $judul): string
{
    // "Sumatif Lingkup Materi Budaya Melayu Riau" → "Budaya Melayu Riau"
    $j = preg_replace('/^(asesmen\s+)?(sumatif|formatif|ulangan harian|tugas|praktik|proyek|uh|slm|sts|sas)\b[\s:\-–]*(lingkup materi|tengah semester|akhir semester)?[\s:\-–]*(\d+[\s:\-–]*)?/iu', '', trim($judul));
    $j = trim(preg_replace('/\s*\((online|tugas online)\)\s*$/i', '', (string)$j));
    return $j !== '' ? $j : trim($judul);
}

$baris = [];
foreach ($siswa as $i => $s) {
    $v = $nilai[$s['member_id']] ?? [];
    $harian = [];
    $sas = [];
    $per = [];
    foreach ($pakai as $p) {
        if (!isset($v[(int)$p['id']])) continue;
        $x = $v[(int)$p['id']];
        if (in_array($p['jenis'], GRUP_SAS, true)) $sas[] = $x; else $harian[] = $x;
        if (!in_array($p['jenis'], GRUP_SAS, true) && !in_array($p['jenis'], ['Sumatif Tengah Semester'], true)) $per[] = [$x, materi_singkat($p['judul'])];
    }
    $rh = $harian ? array_sum($harian) / count($harian) : null;
    $rs = $sas ? array_sum($sas) / count($sas) : null;
    $na = $rh !== null && $rs !== null ? $rh * (100 - $bobot_sas) / 100 + $rs * $bobot_sas / 100 : ($rh ?? $rs);
    $tinggi = $rendah = '';
    if ($per) {
        usort($per, fn($a, $b) => $b[0] <=> $a[0]);
        $tinggi = $per[0][0] >= $kktp
            ? 'Menunjukkan penguasaan yang ' . ($per[0][0] >= 90 ? 'sangat baik' : 'baik') . ' dalam ' . $per[0][1] . '.'
            : 'Mulai menunjukkan pemahaman dalam ' . $per[0][1] . '.';
        $low = end($per);
        if (count($per) > 1 && $low[1] !== $per[0][1]) $rendah = ($low[0] < $kktp ? 'Perlu bantuan dalam ' : 'Perlu peningkatan dalam ') . $low[1] . '.';
        elseif ($per[0][0] < $kktp) $rendah = 'Perlu bantuan untuk mencapai ketuntasan dalam ' . $per[0][1] . '.';
    }
    $baris[] = ['no' => $i + 1, 'nisn' => (string)($s['nisn'] ?? ''), 'nama' => $s['member_name'], 'jk' => (string)($s['jk'] ?? ''),
        'rh' => $rh, 'rs' => $rs, 'na' => $na === null ? null : (int)round($na), 'tinggi' => $tinggi, 'rendah' => $rendah];
}
$fmt = fn($x) => $x === null ? '' : str_replace('.', ',', (string)round($x, 1));

if (get('unduh')) {
    $out = [['Nilai e-Rapor ' . $mapel . ' - Kelas ' . $kelas . ' - Semester ' . $semester . ' ' . $tahun],
        ['No', 'NISN', 'Nama', 'L/P', 'Rata-rata sumatif', 'Sumatif akhir semester', 'Nilai akhir', 'Capaian kompetensi (tertinggi)', 'Capaian kompetensi (perlu ditingkatkan)']];
    foreach ($baris as $b) $out[] = [$b['no'], $b['nisn'] !== '' ? '="' . $b['nisn'] . '"' : '', $b['nama'], $b['jk'], $fmt($b['rh']), $fmt($b['rs']), $b['na'] ?? '', $b['tinggi'], $b['rendah']];
    kirim_csv('erapor_' . $kelas . '_' . $mapel . '_' . $semester . '.csv', $out);
}

kepala('Nilai e-Rapor', 'kelas');
judul_baris('Nilai untuk e-Rapor', '<a class="tombol" href="?p=nilai_rekap&amp;kelas=' . h(rawurlencode($kelas)) . '">‹ Rekap nilai</a>');
?>
<form class="kartu formulir tak-cetak" method="get" id="form-erapor">
  <input type="hidden" name="p" value="erapor">
  <div class="baris3">
    <label>Kelas<select name="kelas" onchange="this.form.querySelectorAll('[name=\'pakai[]\']').forEach(function(e){e.remove()});this.form.submit()"><?= pilihan(array_combine($kelas_list, $kelas_list) ?: [], $kelas) ?></select></label>
    <label>Mapel<select name="mapel" onchange="this.form.querySelectorAll('[name=\'pakai[]\']').forEach(function(e){e.remove()});this.form.submit()"><?= pilihan(array_combine($mapel_list, $mapel_list) ?: [], $mapel) ?></select></label>
    <label>Semester<select name="semester" onchange="this.form.querySelectorAll('[name=\'pakai[]\']').forEach(function(e){e.remove()});this.form.submit()"><?= pilihan(['Ganjil' => 'Ganjil', 'Genap' => 'Genap'], $semester) ?></select></label>
  </div>
  <?php if ($pen): ?>
  <fieldset><legend>Penilaian yang dihitung (<?= h(tgl($awal)) ?> – <?= h(tgl($akhir)) ?>)</legend>
    <?php foreach ($pen as $p): ?><label class="centang kecil"><input type="checkbox" name="pakai[]" value="<?= (int)$p['id'] ?>" <?= in_array((int)$p['id'], $dipilih, true) ? 'checked' : '' ?>> <span><?= h($p['judul']) ?> <small class="redup">· <?= h($p['jenis']) ?> · <?= h(tgl($p['tanggal'])) ?></small></span></label><?php endforeach; ?>
    <p class="kecil redup">Bawaan: semua sumatif (Ulangan Harian, Sumatif Lingkup Materi, Sumatif Tengah Semester) dan Sumatif Akhir Semester. Tugas/formatif boleh ikut dicentang.</p>
  </fieldset>
  <label>Bobot Sumatif Akhir Semester (%) <small>(sisanya dari rata-rata sumatif lain)</small><input type="number" name="bobot_sas" min="0" max="100" value="<?= $bobot_sas ?>"></label>
  <button class="utama lebar">Hitung ulang</button>
  <?php endif; ?>
</form>
<?php if (!$pen): ?>
  <div class="pesan">Belum ada penilaian <?= h($mapel) ?> kelas <?= h($kelas) ?> pada semester <?= h($semester) ?> <?= h($tahun) ?>.</div>
<?php else: ?>
<div class="kartu">
  <p class="kecil redup" style="margin-top:0">Nilai akhir = <?= $ada_sas ? (100 - $bobot_sas) . '% rata-rata sumatif + ' . $bobot_sas . '% sumatif akhir semester' : 'rata-rata penilaian yang dicentang (belum ada sumatif akhir semester)' ?>, dibulatkan. Kalimat capaian diambil dari judul penilaian dengan nilai tertinggi & terendah tiap siswa — periksa dan sesuaikan bila perlu.</p>
  <a class="tombol utama" href="?<?= h(http_build_query(array_merge($_GET, ['unduh' => 1]))) ?>">⬇ Unduh Excel untuk e-Rapor</a>
  <div class="tabel-gulir" style="margin-top:.6rem">
  <table class="tabel rapat tabel-erapor">
    <thead><tr><th>No</th><th>Nama</th><th>Rata sumatif</th><th>SAS</th><th>Nilai akhir</th><th>Capaian kompetensi</th></tr></thead>
    <tbody>
    <?php foreach ($baris as $b): ?>
      <tr class="<?= $b['na'] !== null && $b['na'] < $kktp ? 'kurang' : '' ?>"><td><?= $b['no'] ?></td><td class="nowrap"><?= h($b['nama']) ?></td><td><?= $fmt($b['rh']) ?></td><td><?= $fmt($b['rs']) ?></td><td><b><?= $b['na'] ?? '–' ?></b></td>
        <td class="kecil"><?= h($b['tinggi']) ?><?= $b['rendah'] ? '<br>' . h($b['rendah']) : '' ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>
<?php
kaki();

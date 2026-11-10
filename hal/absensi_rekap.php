<?php
declare(strict_types=1);

$kelas_list = kelas_saya();
$kelas = (string)get('kelas', $kelas_list[0] ?? '');
$bulan = preg_match('/^\d{4}-\d{2}$/', (string)get('bulan')) ? (string)get('bulan') : date('Y-m');
$mapel = trim((string)get('mapel'));
$awal = $bulan . '-01';
$akhir = date('Y-m-t', strtotime($awal));
// rentang tanggal (atau satu tanggal) menggantikan bulan
$pola = '/^\d{4}-\d{2}-\d{2}$/';
$rentang = preg_match($pola, (string)get('dari')) && preg_match($pola, (string)get('sampai'));
if ($rentang) {
    [$awal, $akhir] = [(string)get('dari'), (string)get('sampai')];
    if ($awal > $akhir) [$awal, $akhir] = [$akhir, $awal];
    if ((strtotime($akhir) - strtotime($awal)) > 400 * 86400) $akhir = date('Y-m-d', strtotime($awal . ' +400 days'));
}
$harian = $rentang && $awal === $akhir;
$periode = !$rentang ? BULAN[(int)substr($bulan, 5)] . ' ' . substr($bulan, 0, 4)
    : ($harian ? tgl_panjang($awal) : tgl($awal) . ' s.d. ' . tgl($akhir));
$nama_file = $rentang ? ($harian ? $awal : $awal . '_sd_' . $akhir) : $bulan;

$siswa = kelas_valid($kelas) ? siswa_kelas($kelas) : [];
$w = 'guru_id = ? AND kelas = ? AND tanggal BETWEEN ? AND ?';
$par = [uid(), $kelas, $awal, $akhir];
if ($mapel !== '') { $w .= ' AND mapel = ?'; $par[] = $mapel; }
$data = semua("SELECT member_id, tanggal, status, ket FROM guru_absensi WHERE $w ORDER BY tanggal", $par);
$tgl_ada = array_values(array_unique(array_column($data, 'tanggal')));
$peta = [];
$ket = [];
foreach ($data as $r) {
    $sebelum = $peta[$r['member_id']][$r['tanggal']] ?? '';
    // bila ada beberapa mapel di hari yang sama, ambil status terburuk (A > S/I > H)
    $urut = ['' => 0, 'H' => 1, 'I' => 2, 'S' => 2, 'A' => 3];
    if ($urut[$r['status']] >= $urut[$sebelum]) $peta[$r['member_id']][$r['tanggal']] = $r['status'];
    if (trim((string)$r['ket']) !== '') $ket[$r['member_id']][] = trim((string)$r['ket']);
}
$mapel_list = q('SELECT DISTINCT mapel FROM guru_absensi WHERE guru_id = ? AND kelas = ? ORDER BY mapel', [uid(), $kelas])->fetchAll(PDO::FETCH_COLUMN);

$baris = [];
foreach ($siswa as $i => $s) {
    $h = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];
    $sel = [];
    foreach ($tgl_ada as $t) { $v = $peta[$s['member_id']][$t] ?? ''; $sel[] = $v; if ($v) $h[$v]++; }
    $tot = array_sum($h);
    $baris[] = ['no' => $i + 1, 'kode' => $s['member_id'], 'nama' => $s['member_name'], 'sel' => $sel, 'h' => $h, 'persen' => $tot ? round($h['H'] / $tot * 100) : null];
}

const NAMA_STATUS = ['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa'];
if (get('unduh') && $harian) {
    $out = [['Absensi Kelas ' . $kelas . ' - ' . $periode . ($mapel ? ' - ' . $mapel : '')], ['No', 'Nama', 'Status', 'Keterangan']];
    foreach ($baris as $b) $out[] = [$b['no'], $b['nama'], NAMA_STATUS[$b['sel'][0] ?? ''] ?? 'Belum diisi', implode('; ', array_unique($ket[$b['kode']] ?? []))];
    $tot = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];
    foreach ($baris as $b) foreach ($b['h'] as $x => $n) $tot[$x] += $n;
    $out[] = [];
    foreach ($tot as $x => $n) $out[] = ['', NAMA_STATUS[$x], $n];
    kirim_csv('absensi_' . $kelas . '_' . $nama_file . '.csv', $out);
}
if (get('unduh')) {
    $out = [['Rekap Absensi Kelas ' . $kelas . ' - ' . $periode . ($mapel ? ' - ' . $mapel : '')],
        array_merge(['No', 'Nama'], array_map(fn($t) => $rentang && substr($awal, 0, 7) !== substr($akhir, 0, 7) ? date('d/m', strtotime($t)) : date('d', strtotime($t)), $tgl_ada), ['H', 'S', 'I', 'A', '% Hadir'])];
    foreach ($baris as $b) $out[] = array_merge([$b['no'], $b['nama']], $b['sel'], array_values($b['h']), [$b['persen'] ?? '']);
    kirim_csv('absensi_' . $kelas . '_' . $nama_file . '.csv', $out);
}

kepala('Rekap absensi', 'kelas');
judul_baris('Rekap absensi', '<button type="button" class="tombol" onclick="window.print()">🖨 Cetak</button>');
?>
<form class="cari tak-cetak" method="get">
  <input type="hidden" name="p" value="absensi_rekap">
  <select name="kelas"><?= pilihan(array_combine($kelas_list, array_map(fn($k) => 'Kelas ' . $k, $kelas_list)) ?: [], $kelas) ?></select>
  <select name="mapel"><option value="">Semua mapel</option><?= pilihan(array_combine($mapel_list, $mapel_list) ?: [], $mapel) ?></select>
  <select id="pilih-periode" aria-label="Periode"><option value="bulan"<?= $rentang ? '' : ' selected' ?>>Per bulan</option><option value="tanggal"<?= $harian ? ' selected' : '' ?>>Satu tanggal</option><option value="rentang"<?= $rentang && !$harian ? ' selected' : '' ?>>Rentang tanggal</option></select>
  <span class="per per-bulan"><input type="month" name="bulan" value="<?= h($bulan) ?>"></span>
  <span class="per per-tanggal per-rentang"><input type="date" name="dari" value="<?= h($rentang ? $awal : hari_ini()) ?>" aria-label="Tanggal"></span>
  <span class="per per-rentang">s.d. <input type="date" name="sampai" value="<?= h($rentang ? $akhir : hari_ini()) ?>" aria-label="Sampai tanggal"></span>
  <button>Tampilkan</button>
  <a class="tombol" href="?<?= h(http_build_query(array_merge(['p' => 'absensi_rekap', 'kelas' => $kelas, 'mapel' => $mapel, 'unduh' => 1], $rentang ? ['dari' => $awal, 'sampai' => $akhir] : ['bulan' => $bulan]))) ?>">⬇ Excel</a>
</form>
<script>
(function () {
  var s = document.getElementById('pilih-periode'), f = s.form;
  function atur() {
    f.querySelectorAll('.per').forEach(function (e) { var on = e.classList.contains('per-' + s.value); e.hidden = !on; e.querySelectorAll('input').forEach(function (i) { i.disabled = !on; }); });
    if (s.value === 'tanggal') { f.sampai.disabled = false; f.sampai.closest('.per').hidden = true; }
  }
  s.addEventListener('change', atur); atur();
  f.addEventListener('submit', function () { if (s.value === 'tanggal') f.sampai.value = f.dari.value; });
  <?php if (get('cetak')): ?>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });<?php endif; ?>
})();
</script>
<div class="kartu cetak-penuh">
  <div class="kop-cetak hanya-cetak"><img src="aset/logo.png" alt=""><div><b><?= h((string)k('sekolah')) ?></b><br><?= $harian ? 'Daftar Hadir Siswa' : 'Rekap Absensi Siswa' ?></div></div>
  <h2>Kelas <?= h($kelas) ?> · <?= h($periode) ?><?= $mapel ? ' · ' . h($mapel) : '' ?></h2>
  <?php if (!$tgl_ada): ?><p class="redup">Belum ada data absensi pada <?= $harian ? 'tanggal' : ($rentang ? 'rentang tanggal' : 'bulan') ?> ini.</p>
  <?php elseif ($harian): $tot = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0]; foreach ($baris as $b) foreach ($b['h'] as $x => $n) $tot[$x] += $n; ?>
  <p class="kecil">Hadir <b><?= $tot['H'] ?></b> · Sakit <b><?= $tot['S'] ?></b> · Izin <b><?= $tot['I'] ?></b> · Alpa <b><?= $tot['A'] ?></b></p>
  <div class="tabel-gulir">
  <table class="tabel rapat">
    <thead><tr><th>No</th><th>Nama</th><th>Status</th><th>Keterangan</th></tr></thead>
    <tbody>
    <?php foreach ($baris as $b): $v = $b['sel'][0] ?? ''; ?>
      <tr><td><?= $b['no'] ?></td><td class="nowrap"><?= h($b['nama']) ?></td><td class="st st-<?= $v ?: 'x' ?>"><?= NAMA_STATUS[$v] ?? '·' ?></td><td><?= h(implode('; ', array_unique($ket[$b['kode']] ?? []))) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php else: ?>
  <p class="kecil redup tak-cetak"><?= count($tgl_ada) ?> pertemuan diisi. Hanya tanggal yang diisi yang dihitung; hari tanpa absensi tidak dianggap hadir. Tanda · = siswa itu tidak diberi status.</p>
  <div class="tabel-gulir">
  <table class="tabel rapat">
    <thead><tr><th>No</th><th>Nama</th><?php foreach ($tgl_ada as $t): ?><th><?= date('d', strtotime($t)) ?></th><?php endforeach; ?><th>H</th><th>S</th><th>I</th><th>A</th><th>%</th></tr></thead>
    <tbody>
    <?php foreach ($baris as $b): ?>
      <tr><td><?= $b['no'] ?></td><td class="nowrap"><?= h($b['nama']) ?></td>
        <?php foreach ($b['sel'] as $v): ?><td class="st st-<?= $v ?: 'x' ?>"><?= $v ?: '·' ?></td><?php endforeach; ?>
        <td><?= $b['h']['H'] ?></td><td><?= $b['h']['S'] ?></td><td><?= $b['h']['I'] ?></td><td><?= $b['h']['A'] ?></td><td><?= $b['persen'] ?? '-' ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
  <div class="ttd-cetak hanya-cetak"><p><?= h((string)k('kota')) ?>, <?= tgl_panjang(hari_ini()) ?><br>Guru Mata Pelajaran<br><br><br><br><b><?= h(pengguna()['nama']) ?></b><br>NIP <?= h(pengguna()['nip'] ?: '-') ?></p></div>
</div>
<?php
kaki();

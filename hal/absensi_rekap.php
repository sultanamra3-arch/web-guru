<?php
declare(strict_types=1);

$kelas_list = kelas_saya();
$kelas = (string)get('kelas', $kelas_list[0] ?? '');
$bulan = preg_match('/^\d{4}-\d{2}$/', (string)get('bulan')) ? (string)get('bulan') : date('Y-m');
$mapel = trim((string)get('mapel'));
$awal = $bulan . '-01';
$akhir = date('Y-m-t', strtotime($awal));

$siswa = kelas_valid($kelas) ? siswa_kelas($kelas) : [];
$w = 'guru_id = ? AND kelas = ? AND tanggal BETWEEN ? AND ?';
$par = [uid(), $kelas, $awal, $akhir];
if ($mapel !== '') { $w .= ' AND mapel = ?'; $par[] = $mapel; }
$data = semua("SELECT member_id, tanggal, status FROM guru_absensi WHERE $w ORDER BY tanggal", $par);
$tgl_ada = array_values(array_unique(array_column($data, 'tanggal')));
$peta = [];
foreach ($data as $r) {
    $sebelum = $peta[$r['member_id']][$r['tanggal']] ?? '';
    // bila ada beberapa mapel di hari yang sama, ambil status terburuk (A > S/I > H)
    $urut = ['' => 0, 'H' => 1, 'I' => 2, 'S' => 2, 'A' => 3];
    if ($urut[$r['status']] >= $urut[$sebelum]) $peta[$r['member_id']][$r['tanggal']] = $r['status'];
}
$mapel_list = q('SELECT DISTINCT mapel FROM guru_absensi WHERE guru_id = ? AND kelas = ? ORDER BY mapel', [uid(), $kelas])->fetchAll(PDO::FETCH_COLUMN);

$baris = [];
foreach ($siswa as $i => $s) {
    $h = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];
    $sel = [];
    foreach ($tgl_ada as $t) { $v = $peta[$s['member_id']][$t] ?? ''; $sel[] = $v; if ($v) $h[$v]++; }
    $tot = array_sum($h);
    $baris[] = ['no' => $i + 1, 'nama' => $s['member_name'], 'sel' => $sel, 'h' => $h, 'persen' => $tot ? round($h['H'] / $tot * 100) : null];
}

if (get('unduh')) {
    $out = [['Rekap Absensi Kelas ' . $kelas . ' - ' . BULAN[(int)substr($bulan, 5)] . ' ' . substr($bulan, 0, 4) . ($mapel ? ' - ' . $mapel : '')],
        array_merge(['No', 'Nama'], array_map(fn($t) => date('d', strtotime($t)), $tgl_ada), ['H', 'S', 'I', 'A', '% Hadir'])];
    foreach ($baris as $b) $out[] = array_merge([$b['no'], $b['nama']], $b['sel'], array_values($b['h']), [$b['persen'] ?? '']);
    kirim_csv('absensi_' . $kelas . '_' . $bulan . '.csv', $out);
}

kepala('Rekap absensi', 'kelas');
judul_baris('Rekap absensi', '<button type="button" class="tombol" onclick="window.print()">🖨 Cetak</button>');
?>
<form class="cari tak-cetak" method="get">
  <input type="hidden" name="p" value="absensi_rekap">
  <select name="kelas"><?= pilihan(array_combine($kelas_list, array_map(fn($k) => 'Kelas ' . $k, $kelas_list)) ?: [], $kelas) ?></select>
  <input type="month" name="bulan" value="<?= h($bulan) ?>">
  <select name="mapel"><option value="">Semua mapel</option><?= pilihan(array_combine($mapel_list, $mapel_list) ?: [], $mapel) ?></select>
  <button>Tampilkan</button>
  <a class="tombol" href="?<?= h(http_build_query(['p' => 'absensi_rekap', 'kelas' => $kelas, 'bulan' => $bulan, 'mapel' => $mapel, 'unduh' => 1])) ?>">⬇ Excel</a>
</form>
<div class="kartu cetak-penuh">
  <div class="kop-cetak hanya-cetak"><img src="aset/logo.png" alt=""><div><b><?= h((string)k('sekolah')) ?></b><br>Rekap Absensi Siswa</div></div>
  <h2>Kelas <?= h($kelas) ?> · <?= BULAN[(int)substr($bulan, 5)] ?> <?= substr($bulan, 0, 4) ?><?= $mapel ? ' · ' . h($mapel) : '' ?></h2>
  <?php if (!$tgl_ada): ?><p class="redup">Belum ada data absensi pada bulan ini.</p><?php else: ?>
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

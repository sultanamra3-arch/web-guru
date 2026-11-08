<?php
declare(strict_types=1);

$kelas_list = kelas_saya();
$kelas = (string)get('kelas', $kelas_list[0] ?? '');
$mapel_list = q('SELECT DISTINCT mapel FROM guru_penilaian WHERE guru_id = ? AND kelas = ? ORDER BY mapel', [uid(), $kelas])->fetchAll(PDO::FETCH_COLUMN);
$mapel = (string)get('mapel', $mapel_list[0] ?? '');
$pen = semua('SELECT * FROM guru_penilaian WHERE guru_id = ? AND kelas = ? AND mapel = ? ORDER BY tanggal, id', [uid(), $kelas, $mapel]);
$siswa = kelas_valid($kelas) ? siswa_kelas($kelas) : [];
$nilai = [];
if ($pen) {
    $ids = array_column($pen, 'id');
    foreach (semua('SELECT penilaian_id, member_id, nilai FROM guru_nilai WHERE penilaian_id IN (' . implode(',', array_map('intval', $ids)) . ')') as $r) {
        $nilai[$r['member_id']][$r['penilaian_id']] = $r['nilai'];
    }
}
$kktp = $pen ? (float)$pen[0]['kktp'] : 75;
$baris = [];
foreach ($siswa as $i => $s) {
    $v = [];
    foreach ($pen as $p) $v[] = $nilai[$s['member_id']][$p['id']] ?? null;
    $isi = array_filter($v, fn($x) => $x !== null);
    $baris[] = ['no' => $i + 1, 'nama' => $s['member_name'], 'v' => $v, 'rata' => $isi ? round(array_sum($isi) / count($isi), 1) : null];
}
$f = fn($x) => $x === null ? '' : rtrim(rtrim((string)$x, '0'), '.');

if (get('unduh')) {
    $out = [['Rekap Nilai ' . $mapel . ' - Kelas ' . $kelas], array_merge(['No', 'Nama'], array_map(fn($p) => $p['judul'], $pen), ['Rata-rata', 'Keterangan'])];
    foreach ($baris as $b) $out[] = array_merge([$b['no'], $b['nama']], array_map(fn($x) => str_replace('.', ',', $f($x)), $b['v']),
        [$b['rata'] === null ? '' : str_replace('.', ',', (string)$b['rata']), $b['rata'] === null ? '' : ($b['rata'] >= $kktp ? 'Tuntas' : 'Belum tuntas')]);
    kirim_csv('rekap_nilai_' . $kelas . '_' . $mapel . '.csv', $out);
}

kepala('Rekap nilai', 'kelas');
judul_baris('Rekap nilai', '<button type="button" class="tombol" onclick="window.print()">🖨 Cetak</button>');
?>
<form class="cari tak-cetak" method="get">
  <input type="hidden" name="p" value="nilai_rekap">
  <select name="kelas" onchange="this.form.submit()"><?= pilihan(array_combine($kelas_list, array_map(fn($k) => 'Kelas ' . $k, $kelas_list)) ?: [], $kelas) ?></select>
  <select name="mapel" onchange="this.form.submit()"><?= pilihan(array_combine($mapel_list, $mapel_list) ?: [], $mapel) ?></select>
  <a class="tombol" href="?<?= h(http_build_query(['p' => 'nilai_rekap', 'kelas' => $kelas, 'mapel' => $mapel, 'unduh' => 1])) ?>">⬇ Excel</a>
</form>
<div class="kartu cetak-penuh">
  <div class="kop-cetak hanya-cetak"><img src="aset/logo.png" alt=""><div><b><?= h((string)k('sekolah')) ?></b><br>Rekap Nilai Peserta Didik</div></div>
  <h2><?= h($mapel ?: '-') ?> · Kelas <?= h($kelas) ?></h2>
  <?php if (!$pen): ?><p class="redup">Belum ada penilaian untuk kelas & mapel ini. <a href="?p=penilaian">Buat penilaian →</a></p><?php else: ?>
  <div class="tabel-gulir">
  <table class="tabel rapat">
    <thead><tr><th>No</th><th>Nama</th><?php foreach ($pen as $i => $p): ?><th title="<?= h($p['judul']) ?>"><a href="?p=nilai&amp;id=<?= (int)$p['id'] ?>">P<?= $i + 1 ?></a></th><?php endforeach; ?><th>Rata</th></tr></thead>
    <tbody>
    <?php foreach ($baris as $b): ?>
      <tr><td><?= $b['no'] ?></td><td class="nowrap"><?= h($b['nama']) ?></td>
        <?php foreach ($b['v'] as $x): ?><td class="<?= $x !== null && (float)$x < $kktp ? 'kurang' : '' ?>"><?= h($f($x)) ?></td><?php endforeach; ?>
        <td><b class="<?= $b['rata'] !== null && $b['rata'] < $kktp ? 'kurang' : '' ?>"><?= $b['rata'] ?? '-' ?></b></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <p class="kecil"><?php foreach ($pen as $i => $p): ?>P<?= $i + 1 ?> = <?= h($p['judul']) ?> (<?= tgl($p['tanggal']) ?>)<?= $i < count($pen) - 1 ? '; ' : '' ?><?php endforeach; ?></p>
  <?php endif; ?>
  <div class="ttd-cetak hanya-cetak"><p><?= h((string)k('kota')) ?>, <?= tgl_panjang(hari_ini()) ?><br>Guru Mata Pelajaran<br><br><br><br><b><?= h(pengguna()['nama']) ?></b><br>NIP <?= h(pengguna()['nip'] ?: '-') ?></p></div>
</div>
<?php
kaki();

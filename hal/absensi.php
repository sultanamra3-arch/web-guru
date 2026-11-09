<?php
declare(strict_types=1);

$kelas_list = kelas_saya();
$kelas = (string)get('kelas', $kelas_list[0] ?? '');
$tanggal = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)get('tanggal')) ? (string)get('tanggal') : hari_ini();
$mapel = trim((string)get('mapel', mapel_saya()[0] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kelas = (string)post('kelas');
    $tanggal = (string)post('tanggal');
    $mapel = trim((string)post('mapel'));
    if (!kelas_valid($kelas) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        pesan('Kelas atau tanggal tidak valid.', 'galat');
    } else {
        $siswa = array_column(siswa_kelas($kelas), 'member_id');
        if (post('aksi') === 'hapus') {
            $n = q('DELETE FROM guru_absensi WHERE guru_id = ? AND kelas = ? AND tanggal = ? AND mapel = ?', [uid(), $kelas, $tanggal, $mapel])->rowCount();
            pesan('Absensi kelas ' . h($kelas) . ' tanggal ' . h(tgl($tanggal)) . ' dihapus (' . $n . ' siswa). Tanggal ini kembali "belum diisi".');
            ke('?' . http_build_query(['p' => 'absensi', 'kelas' => $kelas, 'tanggal' => $tanggal, 'mapel' => $mapel]));
        }
        $st = (array)($_POST['status'] ?? []);
        $ket = (array)($_POST['ket'] ?? []);
        db()->beginTransaction();
        $n = 0;
        foreach ($siswa as $mid) {
            // siswa yang tidak dipilih statusnya TIDAK disimpan (tidak dianggap hadir)
            $s = strtoupper((string)($st[$mid] ?? ''));
            if (!in_array($s, ['H', 'S', 'I', 'A'], true)) continue;
            q('INSERT INTO guru_absensi (guru_id, kelas, tanggal, mapel, member_id, status, ket, diubah) VALUES (?,?,?,?,?,?,?,?)
               ON DUPLICATE KEY UPDATE status = VALUES(status), ket = VALUES(ket), diubah = VALUES(diubah)',
                [uid(), $kelas, $tanggal, $mapel, $mid, $s, mb_substr(trim((string)($ket[$mid] ?? '')), 0, 200), sekarang()]);
            $n++;
        }
        db()->commit();
        if ($n === 0) {
            pesan('Belum ada siswa yang dipilih statusnya, jadi tidak ada yang disimpan.', 'galat');
            ke('?' . http_build_query(['p' => 'absensi', 'kelas' => $kelas, 'tanggal' => $tanggal, 'mapel' => $mapel]));
        }
        pesan('Absensi kelas ' . h($kelas) . ' tanggal ' . h(tgl($tanggal)) . ' tersimpan (' . $n . ' siswa).');
    }
    ke('?' . http_build_query(['p' => 'absensi', 'kelas' => $kelas, 'tanggal' => $tanggal, 'mapel' => $mapel]));
}

$siswa = kelas_valid($kelas) ? siswa_kelas($kelas) : [];
$ada = [];
foreach (semua('SELECT member_id, status, ket FROM guru_absensi WHERE guru_id = ? AND kelas = ? AND tanggal = ? AND mapel = ?', [uid(), $kelas, $tanggal, $mapel]) as $r) $ada[$r['member_id']] = $r;
$hitung = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0, '' => 0];
foreach ($siswa as $s) $hitung[$ada[$s['member_id']]['status'] ?? '']++;

kepala('Absensi', 'kelas');
judul_baris('Absensi siswa', '<a class="tombol" href="?p=absensi_rekap&amp;kelas=' . h(rawurlencode($kelas)) . '">Rekap</a>');
?>
<form class="cari" method="get">
  <input type="hidden" name="p" value="absensi">
  <select name="kelas" onchange="this.form.submit()"><?= pilihan(array_combine($kelas_list, array_map(fn($k) => 'Kelas ' . $k, $kelas_list)) ?: [], $kelas) ?></select>
  <input type="date" name="tanggal" value="<?= h($tanggal) ?>" onchange="this.form.submit()">
  <input name="mapel" list="dl-mapel" value="<?= h($mapel) ?>" placeholder="Mapel" style="flex:1 1 140px">
  <button>Buka</button>
</form>
<datalist id="dl-mapel"><?php foreach (mapel_saya() as $m): ?><option value="<?= h($m) ?>"><?php endforeach; ?></datalist>

<?php if (!$siswa): ?>
  <?php if (daftar_kelas()): ?><div class="pesan info">Pilih kelas.</div><?php else: ?><div class="pesan info">Belum ada kelas. <a href="?p=siswa_tambah">Tambahkan kelas &amp; siswamu dulu →</a></div><?php endif; ?>
<?php else: ?>
<form method="post" id="form-absen">
  <?= input_token() ?>
  <input type="hidden" name="kelas" value="<?= h($kelas) ?>"><input type="hidden" name="tanggal" value="<?= h($tanggal) ?>"><input type="hidden" name="mapel" value="<?= h($mapel) ?>">
  <div class="kartu lengket ringkas-absen">
    <span><b><?= h($kelas) ?></b> · <?= h(tgl($tanggal)) ?><?= $ada ? ' · <span class="lencana">sudah diisi</span>' : ' · <span class="lencana abu">belum diisi</span>' ?></span>
    <span id="hitung-absen" class="kecil">H <?= $hitung['H'] ?> · S <?= $hitung['S'] ?> · I <?= $hitung['I'] ?> · A <?= $hitung['A'] ?><?= $hitung[''] ? ' · <b>belum dipilih ' . $hitung[''] . '</b>' : '' ?></span>
    <button type="button" class="kecil" id="semua-hadir">Sisanya hadir</button>
  </div>
  <?php if (!$ada): ?><p class="kecil redup">Absensi tanggal ini <b>belum diisi</b>. Cukup pilih siswa yang <b>S / I / A</b>, lalu Simpan — sisanya bisa ditandai hadir sekaligus. Hari yang tidak diisi tidak dihitung di rekap.</p><?php endif; ?>
  <ul class="daftar kartu absen">
  <?php foreach ($siswa as $i => $s): $cur = $ada[$s['member_id']]['status'] ?? ''; ?>
    <li>
      <div class="absen-nama"><span class="no"><?= $i + 1 ?></span> <b><?= h($s['member_name']) ?></b> <small><?= (int)$s['gender'] === 1 ? 'L' : 'P' ?></small></div>
      <div class="absen-pilih">
        <?php foreach (['H' => 'Hadir', 'S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa'] as $kode => $nm): ?>
          <label class="pil pil-<?= $kode ?>" title="<?= $nm ?>"><input type="radio" name="status[<?= h($s['member_id']) ?>]" value="<?= $kode ?>" <?= $cur === $kode ? 'checked' : '' ?>><span><?= $kode ?></span></label>
        <?php endforeach; ?>
        <input class="ket" name="ket[<?= h($s['member_id']) ?>]" value="<?= h($ada[$s['member_id']]['ket'] ?? '') ?>" placeholder="ket.">
      </div>
    </li>
  <?php endforeach; ?>
  </ul>
  <button class="utama lebar">Simpan absensi (<?= count($siswa) ?> siswa)</button>
</form>
<?php if ($ada): ?>
<form method="post" class="hapus-absen" onsubmit="return confirm('Hapus absensi kelas <?= h($kelas) ?> tanggal <?= h(tgl($tanggal)) ?>? Tanggal ini akan kembali \'belum diisi\' dan tidak dihitung di rekap.')">
  <?= input_token() ?><input type="hidden" name="aksi" value="hapus">
  <input type="hidden" name="kelas" value="<?= h($kelas) ?>"><input type="hidden" name="tanggal" value="<?= h($tanggal) ?>"><input type="hidden" name="mapel" value="<?= h($mapel) ?>">
  <button class="kecil bahaya">Hapus absensi tanggal ini (kalau tidak sengaja tersimpan)</button>
</form>
<?php endif; ?>
<?php endif; ?>
<?php
kaki();

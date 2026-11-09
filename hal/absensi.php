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
        $st = (array)($_POST['status'] ?? []);
        $ket = (array)($_POST['ket'] ?? []);
        db()->beginTransaction();
        $n = 0;
        foreach ($siswa as $mid) {
            $s = strtoupper((string)($st[$mid] ?? 'H'));
            if (!in_array($s, ['H', 'S', 'I', 'A'], true)) $s = 'H';
            q('INSERT INTO guru_absensi (guru_id, kelas, tanggal, mapel, member_id, status, ket, diubah) VALUES (?,?,?,?,?,?,?,?)
               ON DUPLICATE KEY UPDATE status = VALUES(status), ket = VALUES(ket), diubah = VALUES(diubah)',
                [uid(), $kelas, $tanggal, $mapel, $mid, $s, mb_substr(trim((string)($ket[$mid] ?? '')), 0, 200), sekarang()]);
            $n++;
        }
        db()->commit();
        pesan('Absensi kelas ' . h($kelas) . ' tanggal ' . h(tgl($tanggal)) . ' tersimpan (' . $n . ' siswa).');
    }
    ke('?' . http_build_query(['p' => 'absensi', 'kelas' => $kelas, 'tanggal' => $tanggal, 'mapel' => $mapel]));
}

$siswa = kelas_valid($kelas) ? siswa_kelas($kelas) : [];
$ada = [];
foreach (semua('SELECT member_id, status, ket FROM guru_absensi WHERE guru_id = ? AND kelas = ? AND tanggal = ? AND mapel = ?', [uid(), $kelas, $tanggal, $mapel]) as $r) $ada[$r['member_id']] = $r;
$hitung = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];
foreach ($siswa as $s) $hitung[$ada[$s['member_id']]['status'] ?? 'H']++;

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
    <span><b><?= h($kelas) ?></b> · <?= h(tgl($tanggal)) ?><?= $ada ? ' · <span class="lencana">sudah diisi</span>' : '' ?></span>
    <span id="hitung-absen" class="kecil">H <?= $hitung['H'] ?> · S <?= $hitung['S'] ?> · I <?= $hitung['I'] ?> · A <?= $hitung['A'] ?></span>
    <button type="button" class="kecil" id="semua-hadir">Semua hadir</button>
  </div>
  <ul class="daftar kartu absen">
  <?php foreach ($siswa as $i => $s): $cur = $ada[$s['member_id']]['status'] ?? 'H'; ?>
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
<?php endif; ?>
<?php
kaki();

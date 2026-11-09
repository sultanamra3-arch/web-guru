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

// Kalender: tanggal mana saja yang sudah diisi untuk kelas ini
$bln = preg_match('/^\d{4}-\d{2}$/', (string)get('bln')) ? (string)get('bln') : substr($tanggal, 0, 7);
$k_awal = $bln . '-01';
$k_akhir = date('Y-m-t', strtotime($k_awal));
$terisi = [];
if (kelas_valid($kelas)) {
    foreach (semua("SELECT tanggal, mapel, SUM(status='H') h, SUM(status='S') s, SUM(status='I') i, SUM(status='A') a
                    FROM guru_absensi WHERE guru_id = ? AND kelas = ? AND tanggal BETWEEN ? AND ?
                    GROUP BY tanggal, mapel ORDER BY tanggal", [uid(), $kelas, $k_awal, $k_akhir]) as $r) {
        $t = (string)$r['tanggal'];
        if (!isset($terisi[$t])) $terisi[$t] = ['mapel' => $r['mapel'], 'h' => 0, 's' => 0, 'i' => 0, 'a' => 0, 'n' => 0];
        foreach (['h', 's', 'i', 'a'] as $x) $terisi[$t][$x] += (int)$r[$x];
        $terisi[$t]['n']++;
        if ($r['mapel'] === $mapel) $terisi[$t]['mapel'] = $mapel;
    }
}
$hari_jadwal = kelas_valid($kelas) ? array_map('intval', q('SELECT DISTINCT hari FROM guru_jadwal WHERE guru_id = ? AND kelas = ?', [uid(), $kelas])->fetchAll(PDO::FETCH_COLUMN)) : [];
$bln_lalu = date('Y-m', strtotime($k_awal . ' -1 month'));
$bln_depan = date('Y-m', strtotime($k_awal . ' +1 month'));
$url_abs = fn(array $x) => '?' . http_build_query(array_merge(['p' => 'absensi', 'kelas' => $kelas, 'tanggal' => $tanggal, 'mapel' => $mapel], $x));

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
<details class="kartu kalender-absen" open>
  <summary><?= ikon('jadwal') ?> <b>Tanggal yang sudah diisi</b> <span class="kecil redup">· <?= count($terisi) ?> pertemuan di <?= BULAN[(int)substr($bln, 5)] ?></span></summary>
  <div class="kal-nav">
    <a class="tombol kecil" href="<?= h($url_abs(['bln' => $bln_lalu])) ?>" aria-label="Bulan sebelumnya">‹</a>
    <b><?= BULAN[(int)substr($bln, 5)] ?> <?= substr($bln, 0, 4) ?></b>
    <a class="tombol kecil" href="<?= h($url_abs(['bln' => $bln_depan])) ?>" aria-label="Bulan berikutnya">›</a>
  </div>
  <div class="kal">
    <?php foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $nm): ?><span class="kal-h"><?= $nm ?></span><?php endforeach; ?>
    <?php for ($i = 1; $i < (int)date('N', strtotime($k_awal)); $i++): ?><span></span><?php endfor; ?>
    <?php for ($d = 1; $d <= (int)date('t', strtotime($k_awal)); $d++):
        $t = sprintf('%s-%02d', $bln, $d); $n = (int)date('N', strtotime($t)); $x = $terisi[$t] ?? null;
        $kls = 'kal-d' . ($x ? ' isi' : '') . (in_array($n, $hari_jadwal, true) ? ' jadwal' : '') . ($t === $tanggal ? ' pilih' : '') . ($t === hari_ini() ? ' kini' : '') . ($n === 7 ? ' minggu' : '');
        $judul = $x ? 'Sudah diisi: H ' . $x['h'] . ', S ' . $x['s'] . ', I ' . $x['i'] . ', A ' . $x['a'] : (in_array($n, $hari_jadwal, true) ? 'Ada jadwal, belum diisi' : 'Belum diisi'); ?>
      <a class="<?= $kls ?>" title="<?= h($judul) ?>" href="<?= h($url_abs(['tanggal' => $t, 'bln' => null, 'mapel' => $x['mapel'] ?? $mapel])) ?>"><?= $d ?><?php if ($x): ?><?php $tdk = $x['s'] + $x['i'] + $x['a']; ?><small<?= $tdk ? ' class="tdk"' : '' ?>><?= $tdk ? $tdk . ' tdk' : '✓' ?></small><?php endif; ?></a>
    <?php endfor; ?>
  </div>
  <p class="kecil redup kal-ket"><span class="kal-d isi contoh">✓</span> sudah diisi, semua hadir · <b class="tdk">2 tdk</b> = 2 siswa S/I/A<?php if ($hari_jadwal): ?> · <span class="kal-d jadwal contoh"></span> hari jadwalmu di kelas ini<?php endif; ?> · tekan tanggal untuk membuka</p>
</details>

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

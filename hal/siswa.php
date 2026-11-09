<?php
declare(strict_types=1);

$tahun = tahun_aktif();
$kelas = (string)get('kelas');
if (!kelas_valid($kelas)) ke('?p=kelas');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string)post('aksi');
    if ($aksi === 'absen') {
        // nomor absen urut nama (abjad) atau simpan nomor yang diketik
        if (post('urut') === 'nama') {
            $i = 0;
            foreach (semua("SELECT ks.kode FROM guru_kelas_siswa ks JOIN guru_siswa s ON s.kode = ks.kode WHERE ks.tahun = ? AND ks.kelas = ? AND s.status = 'aktif' AND s.guru_id = ? ORDER BY s.nama", [$tahun, $kelas, uid()]) as $r)
                q('UPDATE guru_kelas_siswa SET absen = ? WHERE tahun = ? AND kode = ?', [++$i, $tahun, $r['kode']]);
        } else {
            foreach ((array)($_POST['absen'] ?? []) as $kode => $v) {
                $v = trim((string)$v);
                if (!siswa_milik_saya((string)$kode)) continue;
                q('UPDATE guru_kelas_siswa SET absen = ? WHERE tahun = ? AND kode = ? AND kelas = ?', [$v === '' ? null : max(1, min(999, (int)$v)), $tahun, (string)$kode, $kelas]);
            }
        }
        pesan('Nomor absen disimpan.');
    } elseif ($aksi === 'ganti_nama') {
        $baru = rapikan_kelas(mb_substr(trim((string)post('nama')), 0, 50));
        if ($baru !== '' && $baru !== $kelas && !kelas_valid($baru)) {
            db()->beginTransaction();
            q('UPDATE guru_kelas SET nama = ? WHERE tahun = ? AND nama = ? AND guru_id = ?', [$baru, $tahun, $kelas, uid()]);
            q('UPDATE guru_kelas_siswa ks JOIN guru_siswa s ON s.kode = ks.kode SET ks.kelas = ? WHERE ks.tahun = ? AND ks.kelas = ? AND s.guru_id = ?', [$baru, $tahun, $kelas, uid()]);
            foreach (['guru_jadwal', 'guru_absensi', 'guru_penilaian'] as $t) q("UPDATE $t SET kelas = ? WHERE kelas = ? AND guru_id = ?", [$baru, $kelas, uid()]);
            q('UPDATE guru_ujian SET kelas = ? WHERE kelas = ? AND tahun = ? AND guru_id = ?', [$baru, $kelas, $tahun, uid()]);
            db()->commit();
            pesan('Nama kelas diganti menjadi ' . h($baru) . '.');
            ke('?p=siswa&kelas=' . rawurlencode($baru));
        }
        pesan('Nama kelas tidak valid atau sudah dipakai.', 'galat');
    } elseif ($aksi === 'hapus_kelas') {
        if (!siswa_kelas($kelas)) {
            q('DELETE FROM guru_kelas WHERE tahun = ? AND nama = ? AND guru_id = ?', [$tahun, $kelas, uid()]);
            q('DELETE ks FROM guru_kelas_siswa ks JOIN guru_siswa s ON s.kode = ks.kode WHERE ks.tahun = ? AND ks.kelas = ? AND s.guru_id = ?', [$tahun, $kelas, uid()]);
            pesan('Kelas dihapus.');
            ke('?p=kelas');
        }
        pesan('Kelas masih berisi siswa aktif.', 'galat');
    }
    ke('?p=siswa&kelas=' . rawurlencode($kelas));
}

$siswa = siswa_kelas($kelas);
$nonaktif = semua("SELECT s.kode, s.nama, s.status FROM guru_kelas_siswa ks JOIN guru_siswa s ON s.kode = ks.kode
    WHERE ks.tahun = ? AND ks.kelas = ? AND s.status <> 'aktif' AND s.guru_id = ? ORDER BY s.nama", [$tahun, $kelas, uid()]);
$l = count(array_filter($siswa, fn($s) => $s['jk'] === 'L'));
$pr = count(array_filter($siswa, fn($s) => $s['jk'] === 'P'));

kepala('Kelas ' . $kelas, 'kelas');
judul_baris('Kelas ' . h($kelas), '<a class="tombol" href="?p=kelas">‹ Kelas</a>');
?>
<p class="redup"><?= count($siswa) ?> siswa · L <?= $l ?> · P <?= $pr ?> · <?= h($tahun) ?></p>
<div class="dua-tombol">
  <a class="tombol utama" href="?p=siswa_tambah&amp;kelas=<?= h(rawurlencode($kelas)) ?>">＋ Tambah siswa</a>
  <a class="tombol" href="?p=absensi&amp;kelas=<?= h(rawurlencode($kelas)) ?>">✅ Absensi</a>
</div>
<form method="post" class="kartu">
  <?= input_token() ?><input type="hidden" name="aksi" value="absen">
  <?php if (!$siswa): ?><p class="redup">Belum ada siswa di kelas ini.</p><?php endif; ?>
  <ul class="daftar daftar-siswa">
  <?php foreach ($siswa as $i => $s): ?>
    <li>
      <input class="no-absen" name="absen[<?= h($s['member_id']) ?>]" value="<?= h((string)($s['absen'] ?? '')) ?>" inputmode="numeric" placeholder="<?= $i + 1 ?>" aria-label="No. absen">
      <a href="?p=siswa_ubah&amp;kode=<?= h($s['member_id']) ?>"><b><?= h($s['member_name']) ?></b>
        <small><?= $s['jk'] ? h($s['jk']) : '–' ?><?= $s['nisn'] ? ' · NISN ' . h($s['nisn']) : '' ?></small></a>
    </li>
  <?php endforeach; ?>
  </ul>
  <?php if ($siswa): ?>
  <div class="dua-tombol">
    <button>Simpan no. absen</button>
    <button name="urut" value="nama">Urutkan absen sesuai abjad</button>
  </div>
  <?php endif; ?>
</form>
<?php if ($nonaktif): ?>
<details class="kartu"><summary><?= count($nonaktif) ?> siswa tidak aktif</summary>
  <ul class="daftar"><?php foreach ($nonaktif as $s): ?><li><a href="?p=siswa_ubah&amp;kode=<?= h($s['kode']) ?>"><?= h($s['nama']) ?> <small><?= h($s['status']) ?></small></a></li><?php endforeach; ?></ul>
</details>
<?php endif; ?>
<details class="kartu"><summary><b>Ganti nama / hapus kelas</b></summary>
  <form method="post" class="formulir" style="margin-top:.6rem">
    <?= input_token() ?><input type="hidden" name="aksi" value="ganti_nama">
    <label>Ganti nama kelas<input name="nama" value="<?= h($kelas) ?>" required></label>
    <button>Ganti nama</button>
    <p class="kecil redup">Jadwal, absensi, penilaian, dan ujianmu untuk kelas ini ikut diganti.</p>
  </form>
  <?php if (!$siswa): ?>
  <form method="post" style="margin-top:.8rem" onsubmit="return confirm('Hapus kelas kosong ini?')">
    <?= input_token() ?><input type="hidden" name="aksi" value="hapus_kelas"><button class="kecil bahaya">Hapus kelas</button>
  </form>
  <?php else: ?><p class="kecil redup" style="margin-top:.8rem">Kelas bisa dihapus bila sudah tidak berisi siswa aktif.</p><?php endif; ?>
</details>
<?php
kaki();

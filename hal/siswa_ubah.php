<?php
declare(strict_types=1);

$tahun = tahun_aktif();
$kode = (string)get('kode');
$s = siswa_milik_saya($kode);
if (!$s) ke('?p=kelas');
$ks = satu('SELECT * FROM guru_kelas_siswa WHERE tahun = ? AND kode = ?', [$tahun, $kode]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('aksi') === 'hapus') {
        $dipakai = nilai('SELECT 1 FROM guru_absensi WHERE member_id = ? UNION SELECT 1 FROM guru_nilai WHERE member_id = ? UNION SELECT 1 FROM guru_ujian_peserta WHERE kode = ? LIMIT 1', [$kode, $kode, $kode]);
        if ($dipakai) {
            q("UPDATE guru_siswa SET status = 'keluar', diubah = ? WHERE kode = ?", [sekarang(), $kode]);
            pesan('Siswa sudah punya absensi/nilai, jadi ditandai "keluar" (riwayat tetap tersimpan).');
        } else {
            q('DELETE FROM guru_kelas_siswa WHERE kode = ?', [$kode]);
            q('DELETE FROM guru_kelompok WHERE kode = ?', [$kode]);
            q('DELETE FROM guru_siswa WHERE kode = ?', [$kode]);
            pesan('Siswa dihapus.');
        }
        ke('?p=siswa&kelas=' . rawurlencode((string)($ks['kelas'] ?? '')));
    }
    $nama = rapikan_nama((string)post('nama'));
    $nisn = preg_replace('/\D/', '', (string)post('nisn'));
    $jk = in_array(post('jk'), ['L', 'P'], true) ? post('jk') : null;
    $kelas = (string)post('kelas');
    $status = in_array(post('status'), ['aktif', 'keluar', 'lulus'], true) ? post('status') : 'aktif';
    if ($nama === '' || !kelas_valid($kelas)) { pesan('Nama dan kelas wajib diisi.', 'galat'); ke('?p=siswa_ubah&kode=' . rawurlencode($kode)); }
    if ($nisn !== '' && nilai('SELECT 1 FROM guru_siswa WHERE nisn = ? AND kode <> ? AND guru_id = ?', [$nisn, $kode, uid()])) {
        pesan('NISN ' . h($nisn) . ' sudah dipakai siswa lain.', 'galat');
        ke('?p=siswa_ubah&kode=' . rawurlencode($kode));
    }
    $absen = trim((string)post('absen'));
    q('UPDATE guru_siswa SET nama = ?, nisn = ?, jk = ?, status = ?, diubah = ? WHERE kode = ?', [$nama, $nisn ?: null, $jk, $status, sekarang(), $kode]);
    q('INSERT INTO guru_kelas_siswa (tahun, kode, kelas, absen) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE kelas = VALUES(kelas), absen = VALUES(absen)',
        [$tahun, $kode, $kelas, $absen === '' ? null : max(1, (int)$absen)]);
    pesan('Data ' . h($nama) . ' disimpan.');
    ke('?p=siswa&kelas=' . rawurlencode($kelas));
}

kepala('Ubah siswa', 'kelas');
judul_baris('Ubah siswa', '<a class="tombol" href="?p=siswa&amp;kelas=' . h(rawurlencode((string)($ks['kelas'] ?? ''))) . '">‹ Kembali</a>');
?>
<form method="post" class="kartu formulir">
  <?= input_token() ?>
  <label>Nama lengkap<input name="nama" value="<?= h($s['nama']) ?>" required></label>
  <div class="baris2">
    <label>NISN<input name="nisn" value="<?= h((string)$s['nisn']) ?>" inputmode="numeric" maxlength="20"></label>
    <label>Jenis kelamin<select name="jk"><?= pilihan(['' => '—', 'L' => 'Laki-laki', 'P' => 'Perempuan'], (string)$s['jk']) ?></select></label>
  </div>
  <div class="baris2">
    <label>Kelas (<?= h($tahun) ?>)<select name="kelas"><?php $kl = daftar_kelas(); echo pilihan(array_combine($kl, $kl) ?: [], $ks['kelas'] ?? ''); ?></select></label>
    <label>No. absen<input name="absen" value="<?= h((string)($ks['absen'] ?? '')) ?>" inputmode="numeric"></label>
  </div>
  <label>Status<select name="status"><?= pilihan(['aktif' => 'Aktif', 'keluar' => 'Keluar / pindah', 'lulus' => 'Lulus'], $s['status']) ?></select></label>
  <button class="utama lebar">Simpan</button>
</form>
<form method="post" onsubmit="return confirm('Hapus siswa ini? Bila sudah punya nilai/absensi, siswa hanya ditandai keluar.')">
  <?= input_token() ?><input type="hidden" name="aksi" value="hapus"><button class="kecil bahaya">Hapus siswa</button>
</form>
<?php
kaki();

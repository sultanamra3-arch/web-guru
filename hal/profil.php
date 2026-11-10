<?php
declare(strict_types=1);

$a = satu('SELECT * FROM guru_akun WHERE id = ?', [uid()]);
$galat = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('aksi') === 'huruf') {
    setelan_simpan('huruf:' . uid(), post('huruf') === 'besar' ? 'besar' : 'normal');
    pesan('Ukuran huruf disimpan.');
    ke('?p=profil');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('aksi') === 'slims' && adalah_admin()) {
    setelan_simpan('slims_user:' . uid(), mb_substr(trim((string)post('slims_user')), 0, 50));
    pesan('Akun SLiMS untuk SiPustaka disimpan.');
    ke('?p=profil');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim((string)post('nama'));
    $baru = (string)($_POST['sandi_baru'] ?? '');
    if ($nama === '') $galat[] = 'Nama wajib diisi.';
    if ($baru !== '') {
        if (!password_verify((string)($_POST['sandi_lama'] ?? ''), $a['sandi'])) $galat[] = 'Kata sandi lama salah.';
        if (strlen($baru) < 6) $galat[] = 'Kata sandi baru minimal 6 karakter.';
    }
    if (!$galat) {
        q('UPDATE guru_akun SET nama = ?, nip = ?, mapel = ? WHERE id = ?', [$nama, trim((string)post('nip')), trim((string)post('mapel')), uid()]);
        if ($baru !== '') q('UPDATE guru_akun SET sandi = ? WHERE id = ?', [password_hash($baru, PASSWORD_DEFAULT), uid()]);
        $_SESSION['pengguna']['nama'] = $nama;
        $_SESSION['pengguna']['nip'] = trim((string)post('nip'));
        $_SESSION['pengguna']['mapel'] = trim((string)post('mapel'));
        pesan('Profil tersimpan' . ($baru !== '' ? ' dan kata sandi diganti' : '') . '.');
        ke('?p=profil');
    }
}
kepala('Profil', 'lainnya');
judul_baris('Profil saya', '<a class="tombol" href="?p=lainnya">‹ Lainnya</a>');
foreach ($galat as $g) echo '<div class="pesan galat">' . h($g) . '</div>';
?>
<form method="post" class="kartu formulir">
  <?= input_token() ?><input type="hidden" name="aksi" value="huruf">
  <b>Ukuran huruf</b>
  <div class="seg-status pilih-huruf" style="margin:.5rem 0 0">
    <?php $hf = setelan('huruf:' . uid()) === 'besar' ? 'besar' : 'normal'; foreach (['normal' => 'Normal', 'besar' => 'Besar'] as $k => $n): ?>
    <button name="huruf" value="<?= $k ?>" class="<?= $hf === $k ? 'aktif' : '' ?>"><?= $n ?></button><?php endforeach; ?>
  </div>
  <small class="redup">Huruf besar lebih nyaman dibaca di HP atau saat layar ditampilkan di proyektor.</small>
</form>
<?php if (adalah_admin()): ?>
<form method="post" class="kartu formulir">
  <?= input_token() ?><input type="hidden" name="aksi" value="slims">
  <label>Akun SLiMS / SiPustaka <small>(nama pengguna untuk masuk SiPustaka tanpa login ulang)</small>
    <input name="slims_user" value="<?= h(sso_akun_slims()) ?>" placeholder="mis. admin" autocomplete="off"></label>
  <button class="kecil">Simpan</button>
</form>
<?php endif; ?>
<form method="post" class="formulir">
  <?= input_token() ?>
  <fieldset><legend>Data guru</legend>
    <label>Nama lengkap (dengan gelar)<input name="nama" required value="<?= h($a['nama']) ?>"></label>
    <label>NIP<input name="nip" value="<?= h($a['nip']) ?>"></label>
    <label>Mata pelajaran <small>(pisahkan koma)</small><input name="mapel" value="<?= h($a['mapel']) ?>"></label>
    <p class="kecil redup">Nama pengguna: <b><?= h($a['username']) ?></b></p>
  </fieldset>
  <fieldset><legend>Ganti kata sandi <small class="redup">(opsional)</small></legend>
    <label>Kata sandi lama<input type="password" name="sandi_lama" autocomplete="current-password"></label>
    <label>Kata sandi baru<input type="password" name="sandi_baru" autocomplete="new-password"></label>
  </fieldset>
  <button class="utama lebar">Simpan</button>
</form>
<?php
kaki();

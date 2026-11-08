<?php
declare(strict_types=1);

if (pengguna()) ke('?p=beranda');
$pertama = (int)nilai('SELECT COUNT(*) FROM guru_akun') === 0;
$galat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cek_token();
    if ($pertama) {
        // Pemasangan pertama: buat akun admin
        $u = strtolower(trim((string)post('username')));
        $n = trim((string)post('nama'));
        $s = (string)($_POST['password'] ?? '');
        if (!preg_match('/^[a-z0-9._-]{3,50}$/', $u)) $galat = 'Nama pengguna 3–50 huruf kecil/angka.';
        elseif ($n === '') $galat = 'Nama lengkap wajib diisi.';
        elseif (strlen($s) < 6) $galat = 'Kata sandi minimal 6 karakter.';
        else {
            q("INSERT INTO guru_akun (username, nama, sandi, peran, aktif, dibuat) VALUES (?, ?, ?, 'admin', 1, ?)",
                [$u, $n, password_hash($s, PASSWORD_DEFAULT), sekarang()]);
            pesan('Akun admin dibuat. Silakan masuk.');
            ke('?p=masuk');
        }
    } else {
        $sisa = login_terkunci();
        if ($sisa > 0) {
            $galat = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . ceil($sisa / 60) . ' menit.';
        } else {
            $u = satu('SELECT * FROM guru_akun WHERE username = ? LIMIT 1', [strtolower(trim((string)post('username')))]);
            if ($u && $u['aktif'] && password_verify((string)($_POST['password'] ?? ''), $u['sandi'])) {
                hapus_gagal_login();
                session_regenerate_id(true);
                $_SESSION['pengguna'] = ['id' => (int)$u['id'], 'username' => $u['username'], 'nama' => $u['nama'],
                    'peran' => $u['peran'], 'mapel' => (string)$u['mapel'], 'nip' => (string)$u['nip']];
                q('UPDATE guru_akun SET masuk_terakhir = ? WHERE id = ?', [sekarang(), $u['id']]);
                ke('?p=beranda');
            }
            catat_gagal_login();
            usleep(400000);
            $galat = 'Nama pengguna atau kata sandi salah' . ($u && !$u['aktif'] ? ' (akun dinonaktifkan)' : '') . '.';
        }
    }
}

kepala('Masuk');
?>
<div class="kartu masuk">
  <img class="logo-masuk" src="aset/logo.png" alt="Logo">
  <h1>SiGuru</h1>
  <p class="redup" style="margin-top:0"><b>Asisten kerja guru <?= h((string)k('sekolah')) ?></b></p>
  <?php if ($galat): ?><div class="pesan galat"><?= h($galat) ?></div><?php endif; ?>
  <form method="post" action="?p=masuk">
    <?= input_token() ?>
    <?php if ($pertama): ?>
      <div class="pesan info">Pemasangan pertama: buat akun <b>admin</b> (biasanya operator/kepala sekolah). Akun guru dibuat setelah ini.</div>
      <label>Nama lengkap<input name="nama" required value="<?= h(post('nama')) ?>"></label>
    <?php endif; ?>
    <label>Nama pengguna<input name="username" autocomplete="username" autocapitalize="none" required value="<?= h(post('username')) ?>"></label>
    <label>Kata sandi<input type="password" name="password" autocomplete="<?= $pertama ? 'new-password' : 'current-password' ?>" required></label>
    <button class="utama lebar"><?= $pertama ? 'Buat akun admin' : 'Masuk' ?></button>
  </form>
</div>
<?php
kaki();

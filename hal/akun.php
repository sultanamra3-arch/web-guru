<?php
declare(strict_types=1);

wajib_admin();
$id = (int)get('id', 0);
$galat = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = post('aksi');
    if ($aksi === 'simpan') {
        $u = strtolower(trim((string)post('username')));
        $d = ['nama' => trim((string)post('nama')), 'nip' => trim((string)post('nip')), 'mapel' => trim((string)post('mapel')),
            'peran' => post('peran') === 'admin' ? 'admin' : 'guru'];
        $sandi = (string)($_POST['sandi'] ?? '');
        if (!preg_match('/^[a-z0-9._-]{3,50}$/', $u)) $galat[] = 'Nama pengguna 3–50 huruf kecil/angka (boleh titik, garis bawah, strip).';
        if ($d['nama'] === '') $galat[] = 'Nama wajib diisi.';
        if (!$id && strlen($sandi) < 6) $galat[] = 'Kata sandi awal minimal 6 karakter.';
        if ($id && $sandi !== '' && strlen($sandi) < 6) $galat[] = 'Kata sandi minimal 6 karakter.';
        if (nilai('SELECT 1 FROM guru_akun WHERE username = ? AND id <> ?', [$u, $id])) $galat[] = 'Nama pengguna sudah dipakai.';
        if ($id === uid() && $d['peran'] !== 'admin') $galat[] = 'Anda tidak bisa mencabut peran admin dari akun sendiri.';
        if (!$galat) {
            if ($id) {
                q('UPDATE guru_akun SET username = ?, nama = ?, nip = ?, mapel = ?, peran = ? WHERE id = ?', [$u, $d['nama'], $d['nip'], $d['mapel'], $d['peran'], $id]);
                if ($sandi !== '') q('UPDATE guru_akun SET sandi = ? WHERE id = ?', [password_hash($sandi, PASSWORD_DEFAULT), $id]);
                pesan('Akun ' . h($d['nama']) . ' diperbarui.');
            } else {
                q('INSERT INTO guru_akun (username, nama, nip, mapel, sandi, peran, aktif, dibuat) VALUES (?, ?, ?, ?, ?, ?, 1, ?)',
                    [$u, $d['nama'], $d['nip'], $d['mapel'], password_hash($sandi, PASSWORD_DEFAULT), $d['peran'], sekarang()]);
                pesan('Akun <b>' . h($d['nama']) . '</b> dibuat. Login: <b>' . h($u) . '</b> / sandi yang Anda isi. Minta guru menggantinya di Profil.');
            }
            ke('?p=akun');
        }
    } elseif ($aksi === 'aktif') {
        $t = (int)post('id');
        if ($t !== uid()) {
            q('UPDATE guru_akun SET aktif = 1 - aktif WHERE id = ?', [$t]);
            pesan('Status akun diubah.');
        }
        ke('?p=akun');
    }
}

$daftar = semua('SELECT id, username, nama, nip, mapel, peran, aktif, masuk_terakhir FROM guru_akun ORDER BY peran, nama');
$e = $id ? satu('SELECT * FROM guru_akun WHERE id = ?', [$id]) : null;

kepala('Akun guru', 'lainnya');
judul_baris('Akun guru', '<a class="tombol" href="?p=lainnya">‹ Lainnya</a>');
foreach ($galat as $g) echo '<div class="pesan galat">' . h($g) . '</div>';
?>
<form method="post" class="formulir" autocomplete="off">
  <?= input_token() ?><input type="hidden" name="aksi" value="simpan">
  <fieldset>
    <legend><?= $e ? 'Ubah akun: ' . h($e['nama']) : 'Tambah akun guru' ?></legend>
    <div class="baris2">
      <label>Nama lengkap<input name="nama" required value="<?= h(post('nama', $e['nama'] ?? '')) ?>"></label>
      <label>NIP <small>(opsional)</small><input name="nip" value="<?= h(post('nip', $e['nip'] ?? '')) ?>"></label>
    </div>
    <label>Mata pelajaran <small>(pisahkan koma bila lebih dari satu)</small><input name="mapel" value="<?= h(post('mapel', $e['mapel'] ?? '')) ?>" placeholder="mis. Informatika, Matematika"></label>
    <div class="baris2">
      <label>Nama pengguna<input name="username" required autocapitalize="none" value="<?= h(post('username', $e['username'] ?? '')) ?>"></label>
      <label><?= $e ? 'Kata sandi baru <small>(kosongkan bila tetap)</small>' : 'Kata sandi awal' ?><input name="sandi" type="text" autocomplete="new-password" <?= $e ? '' : 'required' ?>></label>
    </div>
    <label>Peran<select name="peran"><?= pilihan(['guru' => 'Guru', 'admin' => 'Admin (bisa mengelola akun & AI)'], $e['peran'] ?? 'guru') ?></select></label>
  </fieldset>
  <button class="utama lebar"><?= $e ? 'Simpan perubahan' : 'Tambah akun' ?></button>
  <?php if ($e): ?><p><a href="?p=akun">Batal</a></p><?php endif; ?>
</form>

<div class="kartu" style="margin-top:1rem">
  <h2>Daftar akun (<?= count($daftar) ?>)</h2>
  <ul class="daftar">
  <?php foreach ($daftar as $a): ?>
    <li class="<?= $a['aktif'] ? '' : 'nonaktif' ?>">
      <div class="eks-atas"><b><?= h($a['nama']) ?></b> <?= $a['peran'] === 'admin' ? '<span class="lencana">admin</span>' : '' ?> <?= $a['aktif'] ? '' : '<span class="lencana merah">nonaktif</span>' ?>
        <small><?= h($a['username']) ?><?= $a['mapel'] ? ' · ' . h($a['mapel']) : '' ?> · terakhir masuk <?= tgl($a['masuk_terakhir']) ?></small></div>
      <div class="eks-aksi">
        <a class="tombol kecil" href="?p=akun&amp;id=<?= (int)$a['id'] ?>">Ubah</a>
        <?php if ((int)$a['id'] !== uid()): ?>
        <form method="post" class="sebaris"><?= input_token() ?><input type="hidden" name="aksi" value="aktif"><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
          <button class="kecil <?= $a['aktif'] ? 'bahaya' : '' ?>"><?= $a['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
        <?php endif; ?>
      </div>
    </li>
  <?php endforeach; ?>
  </ul>
</div>
<?php
kaki();

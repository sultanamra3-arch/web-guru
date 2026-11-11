<?php
/* Admin masuk sebagai guru lain (tanpa sandi), lalu kembali ke akun admin. */
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') ke('?p=beranda');

/** Bersihkan simpanan sesi milik akun sebelumnya (angka beranda, pilihan paket, tempel, asisten, dll.). */
function beralih_bersihkan_sesi(): void
{
    foreach (['angka_beranda', 'paket_pilih', 'tempel', 'tempel_isian', 'asisten_guru', 'sampah_batal', 'ujian_siap'] as $k) unset($_SESSION[$k]);
}

if (post('aksi') === 'kembali') {
    if (!empty($_SESSION['admin_asli'])) {
        $_SESSION['pengguna'] = $_SESSION['admin_asli'];
        unset($_SESSION['admin_asli']);
        beralih_bersihkan_sesi();
        session_regenerate_id(true);
        pesan('Kembali ke akun admin.');
    }
    ke('?p=akun');
}

if (post('aksi') === 'masuk') {
    if (!adalah_admin() || !empty($_SESSION['admin_asli'])) ke('?p=beranda');
    $g = satu('SELECT * FROM guru_akun WHERE id = ?', [(int)post('id')]);
    if (!$g || (int)$g['id'] === uid()) { pesan('Akun tidak ditemukan.', 'galat'); ke('?p=akun'); }
    $_SESSION['admin_asli'] = pengguna();
    $_SESSION['pengguna'] = ['id' => (int)$g['id'], 'username' => $g['username'], 'nama' => $g['nama'],
        'peran' => $g['peran'], 'mapel' => (string)$g['mapel'], 'nip' => (string)$g['nip']];
    beralih_bersihkan_sesi();
    session_regenerate_id(true);
    ke('?p=beranda');
}
ke('?p=beranda');

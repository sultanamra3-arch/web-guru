<?php
/* Kotak "Sekolah" khusus admin (v1.46): ringkasan perpustakaan (SLiMS, HANYA DIBACA) + pintu ke aplikasi sekolah lain,
 * dan masuk SiPustaka tanpa login ulang (tanda tangan HMAC dengan kunci bersama di folder data SiPustaka). */
declare(strict_types=1);

const APLIKASI_SEKOLAH = [
    // [judul, keterangan, ikon, kunci config, alamat bawaan]
    ['SiPustaka', 'buku, anggota', 'buku', 'url_sipustaka', 'https://kelola.otomasismpn6keritang.my.id/'],
    ['SLiMS', 'sirkulasi', 'perpus', 'url_slims', 'https://otomasismpn6keritang.my.id/admin/'],
    ['Kartu', 'cetak kartu', 'kartu', 'url_kartu', 'https://kartu.otomasismpn6keritang.my.id/'],
    ['Buku tamu', 'layar kios', 'tamu', 'url_bukutamu', 'https://bukutamu.otomasismpn6keritang.my.id/?p=layar'],
    ['Katalog', 'cari buku', 'cari', 'url_katalog', 'https://katalog.otomasismpn6keritang.my.id/'],
];

/** Ringkasan perpustakaan (tabel SLiMS, hanya SELECT). Disimpan di sesi 2 menit. null bila tabel SLiMS tidak ada. */
function sekolah_ringkas(): ?array
{
    $c = $_SESSION['sekolah_ringkas'] ?? null;
    if ($c && $c[0] > time() - 120) return $c[1];
    try {
        $r = satu("SELECT (SELECT COUNT(*) FROM loan WHERE is_lent = 1 AND is_return = 0) aktif,
            (SELECT COUNT(*) FROM loan WHERE is_lent = 1 AND is_return = 0 AND due_date < CURDATE()) terlambat,
            (SELECT COUNT(*) FROM visitor_count WHERE checkin_date >= CURDATE()) tamu,
            (SELECT COUNT(*) FROM item) eksemplar,
            (SELECT COUNT(*) FROM member WHERE expire_date >= CURDATE()) anggota") ?: null;
    } catch (Throwable $e) {
        $r = null;   // SiGuru dipasang tanpa SLiMS (mis. pratinjau)
    }
    $_SESSION['sekolah_ringkas'] = [time(), $r];
    return $r;
}

function sekolah_url(string $kunci, string $bawaan): string
{
    return (string)k($kunci, $bawaan);
}

/** Lokasi kunci bersama SSO: di folder data SiPustaka (sama dengan kunci AI). Dibuat bila belum ada. */
function sso_kunci(): ?string
{
    $f = (string)k('sso_kunci', '');
    if ($f === '') {
        $ai = (string)k('ai_kunci', '');
        $f = ($ai !== '' ? dirname($ai) : '/home/bzydmpxm/kelola.otomasismpn6keritang.my.id/data') . '/sso-kunci.php';
    }
    if (!is_file($f)) {
        if (!is_dir(dirname($f)) || !is_writable(dirname($f))) return null;
        $k = bin2hex(random_bytes(32));
        @file_put_contents($f, "<?php return '" . $k . "';\n", LOCK_EX);
        @chmod($f, 0640);
    }
    $k = @include $f;
    return is_string($k) && strlen($k) >= 32 ? $k : null;
}

/** Nama pengguna SLiMS/SiPustaka untuk admin ini (diatur di Profil; bawaan: nama pengguna SiGuru bila ada di SLiMS). */
function sso_akun_slims(): string
{
    $u = (string)setelan('slims_user:' . uid());
    if ($u !== '') return $u;
    try {
        return nilai('SELECT 1 FROM user WHERE username = ?', [pengguna()['username']]) ? (string)pengguna()['username'] : '';
    } catch (Throwable $e) {
        return '';
    }
}

/** Alamat SiPustaka yang langsung masuk (berlaku 60 detik, sekali pakai). Kosong bila belum bisa → buka halaman masuk biasa. */
function sso_alamat_sipustaka(): string
{
    $dasar = rtrim(sekolah_url('url_sipustaka', APLIKASI_SEKOLAH[0][4]), '/') . '/';
    $kunci = sso_kunci();
    $akun = sso_akun_slims();
    if (!adalah_admin() || !$kunci || $akun === '') return $dasar;
    $isi = ['u' => $akun, 't' => (string)time(), 'n' => bin2hex(random_bytes(8))];
    $isi['s'] = hash_hmac('sha256', $isi['u'] . '|' . $isi['t'] . '|' . $isi['n'], $kunci);
    return $dasar . '?p=sso&' . http_build_query($isi);
}

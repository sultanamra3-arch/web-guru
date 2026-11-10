<?php
/* Admin: buka SiPustaka tanpa login ulang (alamat bertanda tangan, berlaku 60 detik, sekali pakai). */
declare(strict_types=1);

if (!adalah_admin()) ke('?p=beranda');
$url = sso_alamat_sipustaka();
$ke = (string)get('ke');
if (in_array($ke, ['pinjaman', 'buku', 'anggota'], true)) {
    // sudah lewat SSO: SiPustaka membuka halaman ini setelah masuk; tanpa SSO: langsung ke halamannya
    $url .= str_contains($url, 'p=sso') ? '&ke=' . $ke : '?p=' . $ke;
}
header('Cache-Control: no-store');
ke($url);

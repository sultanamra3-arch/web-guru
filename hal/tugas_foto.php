<?php
/* Foto kiriman tugas: hanya untuk guru pemilik tugas atau siswa pengirimnya (tugas kelompok: anggota kelompoknya). */
declare(strict_types=1);

$tid = (int)get('t');
$f = (string)get('f');
$path = tugas_folder() . '/' . $f;
$boleh = false;
if (preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $f) && is_file($path)) {
    $t = tugas_ambil($tid, false);
    $kode_siswa = $_SESSION['tugas'][$tid] ?? null;
    if ($t && pengguna() && (int)$t['guru_id'] === uid()) {
        $boleh = (bool)nilai('SELECT 1 FROM guru_tugas_kirim WHERE tugas_id = ? AND foto LIKE ?', [$tid, '%' . $f . '%']);
    } elseif ($t && preg_match('/^T[A-Z0-9]{9}$/', (string)get('r'))) {
        // dibuka dari halaman resi: kode resi = kunci akses kiriman itu
        $boleh = (bool)nilai('SELECT 1 FROM guru_tugas_kirim WHERE tugas_id = ? AND resi = ? AND foto LIKE ?', [$tid, (string)get('r'), '%' . $f . '%']);
    } elseif ($t && $kode_siswa && ($kode_kirim = tugas_kode_kirim($t, (string)$kode_siswa)) !== null) {
        // tugas kelompok: semua anggota kelompok boleh melihat foto kelompoknya
        $boleh = (bool)nilai('SELECT 1 FROM guru_tugas_kirim WHERE tugas_id = ? AND kode = ? AND foto LIKE ?', [$tid, $kode_kirim, '%' . $f . '%']);
    }
}
if (!$boleh) { http_response_code(404); exit; }
header('Content-Type: ' . (str_ends_with($f, '.png') ? 'image/png' : (str_ends_with($f, '.webp') ? 'image/webp' : 'image/jpeg')));
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=86400');
readfile($path);
exit;

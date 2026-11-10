<?php
declare(strict_types=1);

require __DIR__ . '/inc/inti.php';
require __DIR__ . '/inc/ai.php';
require __DIR__ . '/inc/skema.php';
require __DIR__ . '/inc/siswa.php';
require __DIR__ . '/inc/ikon.php';
require __DIR__ . '/inc/tampilan.php';
require __DIR__ . '/inc/guru_ai.php';
require __DIR__ . '/inc/koreksi.php';
require __DIR__ . '/inc/gambar.php';
require __DIR__ . '/inc/ujian.php';
require __DIR__ . '/inc/tugas.php';
require __DIR__ . '/inc/kuis.php';
require __DIR__ . '/inc/asisten.php';

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

mulai_sesi();

$halaman = [
    'masuk', 'keluar', 'beranda', 'lainnya', 'akun', 'profil', 'pengaturan_ai',
    'kelas', 'erapor', 'cadangan', 'siswa', 'siswa_tambah', 'siswa_ubah', 'siswa_naik', 'jadwal', 'absensi', 'absensi_rekap', 'penilaian', 'nilai', 'nilai_rekap', 'kelompok', 'koreksi', 'koreksi_lj', 'koreksi_lihat',
    'soal', 'soal_form', 'soal_manual', 'soal_ai', 'paket', 'paket_cetak', 'kisi',
    'perangkat', 'dokumen', 'dokumen_buat', 'dokumen_lihat', 'presentasi',
    'sertifikat', 'sertifikat_form', 'sertifikat_cetak',
    'ujian', 'ujian_form', 'ujian_lihat', 'ujian_hasil', 'ujian_jawaban', 'u', 'u_kerja', 'gambar',
    'tugas', 'tugas_form', 'tugas_lihat', 'tugas_nilai', 'tugas_foto', 'api_tugas', 't', 'api_t',
    'kuis', 'kuis_main', 'api_kuis', 'k', 'api_k',
    'asisten', 'api_ai', 'api_ujian', 'api_u',
];
const HALAMAN_UMUM = ['masuk', 'u', 'u_kerja', 'api_u', 'gambar', 't', 'api_t', 'tugas_foto', 'k', 'api_k'];
$p = (string)($_GET['p'] ?? '');
if (!in_array($p, $halaman, true)) $p = pengguna() ? 'beranda' : 'masuk';

try {
    db();
    pastikan_skema();
} catch (Throwable $e) {
    http_response_code(500);
    kepala('Gagal tersambung');
    echo '<div class="kartu"><h2>Database belum tersambung</h2><p>' . h($e->getMessage())
        . '</p><p>Periksa <code>config.php</code> (salin dari <code>config.contoh.php</code>).</p></div>';
    kaki();
    exit;
}

if (!in_array($p, HALAMAN_UMUM, true)) wajib_login();
// Akun dinonaktifkan admin → keluar
if (pengguna() && !nilai('SELECT aktif FROM guru_akun WHERE id = ?', [uid()])) {
    $_SESSION = [];
    ke('?p=masuk');
}
// api_u memeriksa tokennya sendiri (bisa menyambung ulang dengan kunci lanjut bila sesi di HP siswa hilang)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $p !== 'api_u') cek_token();

try {
    require __DIR__ . '/hal/' . $p . '.php';
} catch (Throwable $e) {
    if (db()->inTransaction()) db()->rollBack();
    error_log('[siguru] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (in_array($p, ['api_ai', 'api_ujian', 'api_u', 'api_tugas', 'api_t', 'api_kuis', 'api_k'], true)) {
        echo json_encode(['ok' => false, 'galat' => 'Kesalahan server: ' . $e->getMessage()]);
        exit;
    }
    http_response_code(500);
    if (!headers_sent()) kepala('Terjadi kesalahan');
    echo '<div class="pesan galat">Terjadi kesalahan: ' . h($e->getMessage()) . '</div>';
    kaki();
}

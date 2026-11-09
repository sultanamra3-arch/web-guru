<?php
/* Siswa mengirim tugas (foto dikecilkan di HP, dikirim sebagai data URL). */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function balas_t(array $d): never { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') balas_t(['ok' => false]);
$tid = (int)post('id');
$kode = $_SESSION['tugas'][$tid] ?? null;
$alat = hash('sha256', alat_perangkat());
session_write_close();
$t = tugas_ambil($tid, false);
if (!$t || !$kode) balas_t(['ok' => false, 'galat' => 'Sesi berakhir. Buka lagi link tugasnya.']);
$k = satu('SELECT * FROM guru_tugas_kirim WHERE tugas_id = ? AND kode = ?', [$tid, $kode]);
if (($m = tugas_bisa_kirim($t, $k)) !== '') balas_t(['ok' => false, 'galat' => $m]);

$foto = array_slice((array)json_decode((string)post('foto', '[]'), true), 0, (int)$t['maks_foto']);
$teks = mb_substr(trim((string)post('teks')), 0, 5000);
if (!$foto && $teks === '') balas_t(['ok' => false, 'galat' => 'Tambahkan foto atau tulis jawaban dulu.']);
$simpan = [];
foreach ($foto as $f) {
    $n = tugas_foto_simpan((string)$f);
    if (!$n) { tugas_hapus_foto($simpan); balas_t(['ok' => false, 'galat' => 'Ada foto yang tidak bisa dibaca. Coba ambil ulang.']); }
    $simpan[] = $n;
}
if ($k) tugas_hapus_foto(tugas_foto_daftar($k));
q("INSERT INTO guru_tugas_kirim (tugas_id, kode, perangkat, foto, teks, dikirim, telat, kali, status) VALUES (?,?,?,?,?,?,?,1,'kirim')
   ON DUPLICATE KEY UPDATE perangkat = VALUES(perangkat), foto = VALUES(foto), teks = VALUES(teks), dikirim = VALUES(dikirim), telat = VALUES(telat),
   kali = kali + 1, status = 'kirim', saran_ai = NULL",
    [$tid, $kode, $alat, json_encode($simpan), $teks, sekarang(), tugas_lewat($t) ? 1 : 0]);
balas_t(['ok' => true, 'jam' => date('H.i'), 'telat' => tugas_lewat($t)]);

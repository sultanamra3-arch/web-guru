<?php
/* API guru untuk tugas online. */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$t = tugas_ambil((int)post('id'));
if (!$t || $_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok' => false, 'galat' => 'Tugas tidak ditemukan.']); exit; }
if (post('aksi') === 'status') {
    echo json_encode(['ok' => true, 'per' => ujian_waktu_teks(sekarang()), 'tutup' => $t['status'] !== 'buka'] + tugas_status_kumpul($t), JSON_UNESCAPED_UNICODE);
    exit;
}
if (post('aksi') === 'saran') {
    $k = satu('SELECT * FROM guru_tugas_kirim WHERE tugas_id = ? AND kode = ?', [$t['id'], (string)post('kode')]);
    if (!$k) { echo json_encode(['ok' => false, 'galat' => 'Kiriman tidak ditemukan.']); exit; }
    session_write_close();
    set_time_limit(180);
    $r = ai_nilai_tugas($t, $k, trim((string)post('pesan')));
    if ($r['ok']) q('UPDATE guru_tugas_kirim SET saran_ai = ? WHERE tugas_id = ? AND kode = ?', [json_encode($r, JSON_UNESCAPED_UNICODE), $t['id'], $k['kode']]);
    echo json_encode($r, JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode(['ok' => false, 'galat' => 'Aksi tidak dikenal']);

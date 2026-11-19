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
if (post('aksi') === 'unggah') {
    // v1.67: guru mengunggahkan foto tugas atas nama siswa/kelompok (mis. buku dikumpulkan langsung)
    $balas = function (array $d): never { echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; };
    $kode = (string)post('kode');
    $daftar = tugas_daftar_unggah($t);
    if (!isset($daftar[$kode])) $balas(['ok' => false, 'galat' => 'Siswa tidak ada di kelas ini.']);
    $k = satu('SELECT * FROM guru_tugas_kirim WHERE tugas_id = ? AND kode = ?', [$t['id'], $kode]);
    if ($k && $k['status'] === 'dinilai') $balas(['ok' => false, 'galat' => 'Tugas ini sudah dinilai.']);
    session_write_close();
    $foto = array_slice((array)json_decode((string)post('foto', '[]'), true), 0, (int)$t['maks_foto']);
    $teks = mb_substr(trim((string)post('teks')), 0, 5000);
    if (!$foto) $balas(['ok' => false, 'galat' => 'Tambahkan minimal 1 foto.']);
    $simpan = [];
    foreach ($foto as $f) {
        $n = tugas_foto_simpan((string)$f);
        if (!$n) { tugas_hapus_foto($simpan); $balas(['ok' => false, 'galat' => 'Ada foto yang tidak bisa dibaca. Coba ambil ulang.']); }
        $simpan[] = $n;
    }
    if ($k) tugas_hapus_foto(tugas_foto_daftar($k));
    $telat = post('telat') ? 1 : 0;
    q("INSERT INTO guru_tugas_kirim (tugas_id, kode, perangkat, foto, teks, dikirim, telat, kali, status, oleh) VALUES (?,?,'guru',?,?,?,?,1,'kirim',NULL)
       ON DUPLICATE KEY UPDATE perangkat = 'guru', foto = VALUES(foto), teks = VALUES(teks), dikirim = VALUES(dikirim), telat = VALUES(telat),
       kali = kali + 1, status = 'kirim', saran_ai = NULL, oleh = NULL",
        [$t['id'], $kode, json_encode($simpan), $teks, sekarang(), $telat]);
    resi_tugas((int)$t['id'], $kode);
    $balas(['ok' => true, 'jam' => date('H.i'), 'telat' => (bool)$telat]);
}
echo json_encode(['ok' => false, 'galat' => 'Aksi tidak dikenal']);

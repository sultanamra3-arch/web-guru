<?php
/* API guru untuk ujian online (perlu login guru). */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function jawab_json(array $d): never
{
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

$u = ujian_ambil((int)($_REQUEST['id'] ?? 0));
if (!$u) jawab_json(['ok' => false, 'galat' => 'Ujian tidak ditemukan.']);
$aksi = (string)($_REQUEST['aksi'] ?? '');

if ($aksi === 'pantau') {
    session_write_close();
    ujian_akhiri_kedaluwarsa($u);
    $n_soal = count(ujian_soal($u));
    $peserta = [];
    foreach (semua('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ?', [$u['id']]) as $p) $peserta[$p['kode']] = $p;
    $out = [];
    $sekarang = time();
    foreach (siswa_kelas((string)$u['kelas'], (string)($u['tahun'] ?: tahun_aktif())) as $s) {
        $p = $peserta[$s['member_id']] ?? null;
        unset($peserta[$s['member_id']]);
        $out[] = ujian_baris_pantau($s['member_id'], $s['member_name'], $p, $n_soal, $sekarang);
    }
    foreach ($peserta as $k => $p) $out[] = ujian_baris_pantau((string)$k, nama_siswa((string)$k), $p, $n_soal, $sekarang);
    jawab_json(['ok' => true, 'status' => $u['status'], 'token' => $u['status'] === 'buka' ? ujian_token($u) : '', 'token_sisa' => ujian_token_sisa($u),
        'siswa' => $out, 'jam' => date('H:i:s')]);
}

function ujian_baris_pantau(string $kode, string $nama, ?array $p, int $n_soal, int $sekarang): array
{
    if (!$p || !$p['mulai']) return ['kode' => $kode, 'nama' => $nama, 'st' => 'belum'];
    $jw = array_filter(json_decode((string)$p['jawaban'], true) ?: [], fn($v) => (string)$v !== '');
    return [
        'kode' => $kode, 'nama' => $nama,
        'st' => $p['selesai'] ? 'selesai' : 'jalan',
        'terjawab' => count($jw), 'total' => $n_soal, 'nomor' => (int)$p['nomor'] + 1,
        'keluar' => (int)$p['keluar'],
        'online' => $p['terakhir'] && $sekarang - strtotime((string)$p['terakhir']) < 50,
        'sisa' => $p['selesai'] ? 0 : max(0, strtotime((string)$p['batas']) - $sekarang),
        'nilai' => $p['nilai'] !== null ? (float)$p['nilai'] : null,
        'perlu' => (bool)$p['perlu_koreksi'],
        'perangkat' => $p['perangkat'] !== null,
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jawab_json(['ok' => false, 'galat' => 'Metode tidak didukung']);

if ($aksi === 'nilai_ai') {
    $p = satu('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ? AND selesai IS NOT NULL', [$u['id'], (string)post('kode')]);
    if (!$p) jawab_json(['ok' => false, 'galat' => 'Peserta belum selesai.']);
    session_write_close();
    set_time_limit(180);
    jawab_json(ujian_nilai_ai($u, $p));
}

jawab_json(['ok' => false, 'galat' => 'Aksi tidak dikenal']);

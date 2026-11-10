<?php
/* API siswa saat mengerjakan ujian. */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function balas_u(array $d): never
{
    if (!empty($GLOBALS['token_baru'])) $d['token'] = $GLOBALS['token_baru'];
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') balas_u(['ok' => false]);
$id = (int)post('id');
$kode = $_SESSION['ujian'][$id] ?? null;
$token_ok = !empty($_SESSION['csrf']) && hash_equals((string)$_SESSION['csrf'], (string)post('_t'));
// Sesi di HP hilang (cookie terhapus / browser ditutup sistem): sambung lagi memakai kunci lanjut dari HP
$pk = null;
if ((!$kode || !$token_ok) && (string)post('kunci') !== '') {
    $pk = ujian_kunci_cocok($id, (string)post('kunci'));
    if ($pk) {
        session_regenerate_id(true);
        $_SESSION['ujian'][$id] = $kode = (string)$pk['kode'];
        $token_ok = true;
        $GLOBALS['token_baru'] = token();
        alat_siswa();
    }
}
session_write_close();
$u = ujian_ambil($id, false);
if (!$u || !$kode || !$token_ok) balas_u(['ok' => false, 'keluar' => true, 'galat' => 'Sesi berakhir. Pilih namamu lagi untuk melanjutkan — jawabanmu tetap tersimpan.']);

db()->beginTransaction();
$p = satu('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ? FOR UPDATE', [$id, $kode]);
$alat = hash('sha256', (string)($_COOKIE['sgu_alat'] ?? ''));
// cookie penanda HP hilang tetapi kunci lanjut cocok → ini HP yang sama: perbarui penandanya
if ($p && !$p['selesai'] && (!$p['perangkat'] || !hash_equals((string)$p['perangkat'], $alat))) {
    $pk = $pk ?: ujian_kunci_cocok($id, (string)post('kunci'));
    if ($pk && $pk['kode'] === $kode) {
        $alat = hash('sha256', alat_siswa());
        q('UPDATE guru_ujian_peserta SET perangkat = ? WHERE ujian_id = ? AND kode = ?', [$alat, $id, $kode]);
        $p['perangkat'] = $alat;
    }
}
if (!$p || !$p['perangkat'] || !hash_equals((string)$p['perangkat'], $alat)) {
    db()->rollBack();
    balas_u(['ok' => false, 'keluar' => true, 'galat' => ujian_latihan($u) ? 'Latihan ini sekarang dibuka di HP/browser lain.' : 'Nama ini dipakai di HP/browser lain. Minta guru menekan "Buka ulang".']);
}
if ($p['selesai']) { db()->commit(); balas_u(['ok' => false, 'selesai' => true]); }
$sisa = strtotime((string)$p['batas']) - time();
if ($u['status'] !== 'buka' || $sisa < -20) {
    q('UPDATE guru_ujian_peserta SET selesai = ? WHERE ujian_id = ? AND kode = ?', [$u['status'] === 'buka' ? $p['batas'] : sekarang(), $id, $kode]);
    db()->commit();
    ujian_simpan_hitung($u, $p);
    balas_u(['ok' => false, 'selesai' => true]);
}

$n_soal = count(ujian_soal($u));
$jw = json_decode((string)$p['jawaban'], true) ?: [];
$rg = json_decode((string)$p['ragu'], true) ?: [];
$set = ['terakhir' => sekarang()];
$aksi = (string)post('aksi');

// jawaban yang menumpuk saat sinyal putus dikirim sekaligus: {"3":"B","7":"teks"}
foreach ((array)json_decode((string)post('jawab', '{}'), true) as $i => $v) {
    $i = (int)$i;
    if ($i < 0 || $i >= $n_soal) continue;
    $v = mb_substr((string)$v, 0, 5000);
    if ($v === '') unset($jw[$i]); else $jw[$i] = $v;
}
foreach ((array)json_decode((string)post('ragu', '{}'), true) as $i => $v) {
    $i = (int)$i;
    if ($i < 0 || $i >= $n_soal) continue;
    if ($v) $rg[$i] = 1; else unset($rg[$i]);
}
$set['jawaban'] = json_encode((object)$jw, JSON_UNESCAPED_UNICODE);
$set['ragu'] = json_encode((object)$rg);
if (post('nomor') !== '') $set['nomor'] = max(0, min($n_soal - 1, (int)post('nomor')));
$kunci_pengaman = false;
if ($aksi === 'keluar') {
    $set['keluar'] = (int)$p['keluar'] + 1;
    // pengaman aktif: sudah mencapai batas keluar → kumpulkan otomatis
    if ((int)$u['pengaman'] === 1 && !ujian_latihan($u) && $set['keluar'] >= max(1, (int)$u['batas_keluar'])) {
        $set['selesai'] = sekarang(); $set['alasan'] = 'pengaman'; $kunci_pengaman = true;
    }
}
if ($aksi === 'selesai') { $set['selesai'] = sekarang(); $set['alasan'] = 'siswa'; }

$sql = implode(', ', array_map(fn($k) => "$k = ?", array_keys($set)));
q("UPDATE guru_ujian_peserta SET $sql WHERE ujian_id = ? AND kode = ?", array_merge(array_values($set), [$id, $kode]));
db()->commit();

if ($aksi === 'selesai' || $kunci_pengaman) {
    $p = satu('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ?', [$id, $kode]);
    ujian_simpan_hitung($u, $p);
    balas_u(['ok' => true, 'selesai' => true]);
}
balas_u(['ok' => true, 'sisa' => max(0, $sisa), 'keluar' => (int)($set['keluar'] ?? $p['keluar'])]);

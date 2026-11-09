<?php
/* API siswa untuk kuis cepat: status & menjawab. */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$kid = (int)($_REQUEST['id'] ?? 0);
$kode = $_SESSION['kuis'][$kid] ?? null;
session_write_close();
$k = kuis_ambil($kid, false);
if (!$k || !$kode) { echo json_encode(['ok' => false, 'keluar' => true]); exit; }
$p = satu('SELECT * FROM guru_kuis_peserta WHERE kuis_id = ? AND kode = ?', [$kid, $kode]);
if (!$p) { echo json_encode(['ok' => false, 'keluar' => true, 'galat' => 'Kamu dikeluarkan dari kuis oleh guru.']); exit; }
$soal = kuis_soal($k);
$no = (int)$k['nomor'];
$jw = json_decode((string)$p['jawaban'], true) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('aksi') === 'jawab') {
    $pilih = strtoupper((string)post('pilihan'));
    $s = $soal[$no] ?? null;
    $sisa = kuis_sisa_ms($k);
    if ($k['status'] === 'soal' && $s && (int)post('nomor') === $no && !isset($jw[$no]) && isset($s['opsi'][$pilih]) && kuis_ms() >= (int)$k['mulai_ms'] && $sisa > -1000) {
        $benar = $pilih === $s['kunci'];
        $poin = $benar ? kuis_poin($sisa, (int)$k['detik']) : 0;
        $jw[$no] = ['p' => $pilih, 'poin' => $poin];
        // hanya tersimpan bila belum menjawab soal ini (cegah kirim ganda)
        q("UPDATE guru_kuis_peserta SET jawaban = ?, poin = poin + ?, benar = benar + ?, terakhir = ? WHERE kuis_id = ? AND kode = ? AND (jawaban IS NULL OR JSON_EXTRACT(jawaban, ?) IS NULL)",
            [json_encode($jw, JSON_FORCE_OBJECT), $poin, $benar ? 1 : 0, sekarang(), $kid, $kode, '$."' . $no . '"']);
    }
    $p = satu('SELECT * FROM guru_kuis_peserta WHERE kuis_id = ? AND kode = ?', [$kid, $kode]);
    $jw = json_decode((string)$p['jawaban'], true) ?: [];
} else {
    q('UPDATE guru_kuis_peserta SET terakhir = ? WHERE kuis_id = ? AND kode = ?', [sekarang(), $kid, $kode]);
}

if ($k['status'] === 'soal' && kuis_sisa_ms($k) <= 0 && kuis_ms() > (int)$k['mulai_ms'] + 1500) $k['status'] = 'jawab'; // menunggu guru
$s = $soal[$no] ?? null;
$rank = null;
$jumlah = 0;
if (in_array($k['status'], ['jawab', 'selesai'], true)) {
    foreach (kuis_peringkat($kid) as $r) { $jumlah++; if ($r['kode'] === $kode) $rank = $r['rank']; }
} else {
    $jumlah = (int)nilai('SELECT COUNT(*) FROM guru_kuis_peserta WHERE kuis_id = ?', [$kid]);
}
echo json_encode([
    'ok' => true, 'status' => $k['status'], 'nomor' => $no, 'total' => count($soal), 'detik' => (int)$k['detik'],
    'sisa_ms' => kuis_sisa_ms($k), 'mulai_dalam' => max(0, (int)$k['mulai_ms'] - kuis_ms()),
    'soal' => $s && $k['status'] === 'soal' ? ['teks' => $s['teks'], 'g' => gambar_url($s['gambar'] ?? null),
        'opsi' => array_map(fn($h, $t) => ['k' => $h, 't' => $t], array_keys($s['opsi']), $s['opsi'])] : null,
    'jawabku' => $jw[$no] ?? null,
    'kunci' => in_array($k['status'], ['jawab', 'selesai'], true) && $s ? $s['kunci'] : null,
    'poin' => (int)$p['poin'], 'benar' => (int)$p['benar'], 'rank' => $rank, 'jumlah' => $jumlah,
], JSON_UNESCAPED_UNICODE);

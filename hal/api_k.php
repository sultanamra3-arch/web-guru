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
$k = kuis_maju($k);
$soal = kuis_soal($k);
$no = (int)$k['nomor'];
$jw = json_decode((string)$p['jawaban'], true) ?: [];

/** Rekap untuk siswa di akhir (urutan asli soal; jawaban ditampilkan sebagai teks pilihannya). */
function kuis_rekap_siswa(array $soal, array $jw): array
{
    $out = [];
    foreach ($soal as $i => $x) $out[] = ['no' => $i + 1, 'teks' => $x['teks'], 'kunci' => $x['kunci'], 'kunci_teks' => (string)($x['opsi'][$x['kunci']] ?? ''),
        'jawabku' => $jw[$i]['p'] ?? null, 'jawab_teks' => isset($jw[$i]['p']) ? (string)($x['opsi'][$jw[$i]['p']] ?? '') : '',
        'poin' => (int)($jw[$i]['poin'] ?? 0), 'pembahasan' => (string)$x['pembahasan']];
    return $out;
}

if (kuis_mandiri($k)) {
    // ---------- mode mandiri: tiap siswa maju sendiri ----------
    $p = kuis_mandiri_langkah($k, $p);
    $total = count($soal);
    $aku_mulai = (int)$p['pos'] > 0 || $p['selesai_ms'] !== null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('aksi') === 'mulai_saya') {
        if (!$aku_mulai && $k['status'] === 'jalan') {
            $now = kuis_ms();
            q('UPDATE guru_kuis_peserta SET mulai_soal_ms = ?, terakhir = ? WHERE kuis_id = ? AND kode = ? AND mulai_soal_ms IS NULL',
                [$now, sekarang(), $kid, $kode]);
            $p = satu('SELECT * FROM guru_kuis_peserta WHERE kuis_id = ? AND kode = ?', [$kid, $kode]);
            $aku_mulai = (int)$p['pos'] > 0 || $p['selesai_ms'] !== null;
            $out = ['ok' => true, 'mulai_saya' => true, 'mulai_dalam_saya' => 0, 'jawabku' => null, 'soal' => null, 'sisa_ms_saya' => 0];
            echo json_encode($out, JSON_UNESCAPED_UNICODE); exit;
        }
        $p = satu('SELECT * FROM guru_kuis_peserta WHERE kuis_id = ? AND kode = ?', [$kid, $kode]);
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('aksi') === 'jawab' && $k['status'] === 'jalan') {
        $ur = json_decode((string)$p['urutan'], true) ?: [];
        $pos = (int)$p['pos'];
        $i = $ur['s'][$pos] ?? null;
        $pilih = strtoupper((string)post('pilihan'));
        $sisa = (int)$p['soal_ms'] + (int)$k['detik'] * 1000 - kuis_ms();
        if ($i !== null && (int)post('nomor') === $pos && isset($soal[$i]['opsi'][$pilih]) && !isset($jw[$i]) && $sisa > -1000) {
            $benar = $pilih === $soal[$i]['kunci'];
            $poin = $benar ? kuis_poin($sisa, (int)$k['detik']) : 0;
            $jw[$i] = ['p' => $pilih, 'poin' => $poin];
            $now = kuis_ms();
            // simpan & langsung ke soal berikutnya (hanya bila posisi belum berubah: cegah kirim ganda)
            $n = q('UPDATE guru_kuis_peserta SET jawaban = ?, poin = poin + ?, benar = benar + ?, pos = pos + 1, soal_ms = ?, selesai_ms = ?, terakhir = ?
                    WHERE kuis_id = ? AND kode = ? AND pos = ?',
                [json_encode($jw, JSON_FORCE_OBJECT), $poin, $benar ? 1 : 0, $now, $pos + 1 >= $total ? $now : null, sekarang(), $kid, $kode, $pos])->rowCount();
            $p = satu('SELECT * FROM guru_kuis_peserta WHERE kuis_id = ? AND kode = ?', [$kid, $kode]);
            $jw = json_decode((string)$p['jawaban'], true) ?: [];
            if ($n) $k = kuis_mandiri_cek_akhir($k);
        }
    } else {
        q('UPDATE guru_kuis_peserta SET terakhir = ? WHERE kuis_id = ? AND kode = ?', [sekarang(), $kid, $kode]);
    }
    $out = ['ok' => true, 'mode' => 'mandiri', 'status' => $k['status'], 'total' => $total, 'detik' => (int)$k['detik'],
        'mulai_dalam' => max(0, (int)$k['mulai_ms'] - kuis_ms()), 'nomor' => (int)$p['pos'], 'soal' => null, 'sisa_ms' => 0,
        'selesai_saya' => $p['selesai_ms'] !== null, 'mulai_saya' => $aku_mulai, 'mulai_dalam_saya' => $aku_mulai ? 0 : max(0, (int)$k['mulai_ms'] - kuis_ms()),
        'poin' => null, 'benar' => null, 'rank' => null, 'jumlah' => (int)nilai('SELECT COUNT(*) FROM guru_kuis_peserta WHERE kuis_id = ?', [$kid])];
    if ($k['status'] === 'jalan' && $p['selesai_ms'] === null && !$aku_mulai) {
        $out['mulai_dalam_saya'] = max(0, (int)$k['mulai_ms'] - kuis_ms());
    }
    $ms_saya = $aku_mulai ? (int)$p['soal_ms'] + (int)$k['detik'] * 1000 - kuis_ms() : 0;
    if ($k['status'] === 'jalan' && $aku_mulai && (int)$p['pos'] < $total) {
        $out['sisa_ms_saya'] = max(0, $ms_saya);
        $ur = json_decode((string)$p['urutan'], true) ?: [];
        $i = $ur['s'][(int)$p['pos']] ?? 0;
        $x = $soal[$i];
        $out['soal'] = ['teks' => $x['teks'], 'g' => gambar_url($x['gambar'] ?? null),
            'opsi' => array_map(fn($h) => ['k' => $h, 't' => (string)$x['opsi'][$h]], $ur['o'][$i] ?? array_keys($x['opsi']))];
    }
    if ($p['selesai_ms'] !== null) $out['urut_selesai'] = 1 + (int)nilai('SELECT COUNT(*) FROM guru_kuis_peserta WHERE kuis_id = ? AND selesai_ms IS NOT NULL AND selesai_ms < ?', [$kid, $p['selesai_ms']]);
    if ($k['status'] === 'selesai') {
        foreach (kuis_peringkat($kid) as $r) if ($r['kode'] === $kode) $out['rank'] = $r['rank'];
        $out['poin'] = (int)$p['poin']; $out['benar'] = (int)$p['benar'];
        $out['rekap'] = kuis_rekap_siswa($soal, $jw);
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

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

$s = $soal[$no] ?? null;
$rank = null;
$jumlah = 0;
$rekap = null;
if ($k['status'] === 'selesai') {
    foreach (kuis_peringkat($kid) as $r) { $jumlah++; if ($r['kode'] === $kode) $rank = $r['rank']; }
    $rekap = kuis_rekap_siswa($soal, $jw);
} else {
    $jumlah = (int)nilai('SELECT COUNT(*) FROM guru_kuis_peserta WHERE kuis_id = ?', [$kid]);
}
echo json_encode([
    'ok' => true, 'status' => $k['status'], 'nomor' => $no, 'total' => count($soal), 'detik' => (int)$k['detik'],
    'sisa_ms' => kuis_sisa_ms($k), 'mulai_dalam' => max(0, (int)$k['mulai_ms'] - kuis_ms()),
    'soal' => $s && $k['status'] === 'soal' ? ['teks' => $s['teks'], 'g' => gambar_url($s['gambar'] ?? null),
        'opsi' => array_map(fn($h, $t) => ['k' => $h, 't' => $t], array_keys($s['opsi']), $s['opsi'])] : null,
    // selama kuis berjalan siswa hanya tahu pilihannya sendiri; benar/salah & poin dibuka di akhir
    'jawabku' => isset($jw[$no]) ? ['p' => $jw[$no]['p']] : null,
    'rekap' => $rekap,
    'poin' => $k['status'] === 'selesai' ? (int)$p['poin'] : null, 'benar' => $k['status'] === 'selesai' ? (int)$p['benar'] : null,
    'rank' => $rank, 'jumlah' => $jumlah,
], JSON_UNESCAPED_UNICODE);

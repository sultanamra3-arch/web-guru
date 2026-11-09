<?php
/* API guru untuk kuis cepat: status (dipanggil tiap detik oleh layar kuis) & kendali. */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$k = kuis_ambil((int)($_REQUEST['id'] ?? 0));
if (!$k) { echo json_encode(['ok' => false, 'galat' => 'Kuis tidak ditemukan']); exit; }
session_write_close();
$kid = (int)$k['id'];
$soal = kuis_soal($k);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ((string)post('aksi')) {
        case 'mulai':
        case 'berikut':
            $n = $k['status'] === 'lobi' ? 0 : (int)$k['nomor'] + 1;
            if ($n >= count($soal)) q("UPDATE guru_kuis SET status = 'selesai' WHERE id = ?", [$kid]);
            else q("UPDATE guru_kuis SET status = 'soal', nomor = ?, mulai_ms = ? WHERE id = ?", [$n, kuis_ms() + 3000, $kid]); // 3 detik hitung mundur
            break;
        case 'selesai':
            q("UPDATE guru_kuis SET status = 'selesai' WHERE id = ?", [$kid]);
            break;
        case 'ulang':
            q("UPDATE guru_kuis SET status = 'lobi', nomor = -1, mulai_ms = NULL WHERE id = ?", [$kid]);
            q('UPDATE guru_kuis_peserta SET poin = 0, benar = 0, jawaban = NULL WHERE kuis_id = ?', [$kid]);
            break;
        case 'keluarkan':
            q('DELETE FROM guru_kuis_peserta WHERE kuis_id = ? AND kode = ?', [$kid, (string)post('kode')]);
            break;
        case 'simpan_nilai':
            $pid = (int)$k['penilaian_id'];
            if (!$pid || !nilai('SELECT 1 FROM guru_penilaian WHERE id = ? AND guru_id = ?', [$pid, uid()])) {
                $mapel = (string)(nilai("SELECT mapel FROM guru_dokumen WHERE id = ?", [$k['paket_id']]) ?: '-');
                q('INSERT INTO guru_penilaian (guru_id, kelas, mapel, judul, jenis, tanggal, kktp, dibuat) VALUES (?,?,?,?,?,?,?,?)',
                    [uid(), $k['kelas'], $mapel, mb_substr($k['judul'] . ' (kuis cepat)', 0, 200), 'Asesmen Formatif', hari_ini(), 75, sekarang()]);
                $pid = (int)db()->lastInsertId();
                q('UPDATE guru_kuis SET penilaian_id = ? WHERE id = ?', [$pid, $kid]);
            }
            $n = 0;
            foreach (kuis_peringkat($kid) as $p) {
                q('INSERT INTO guru_nilai (penilaian_id, member_id, nilai, catatan) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), catatan = VALUES(catatan)',
                    [$pid, $p['kode'], round((int)$p['benar'] / max(1, count($soal)) * 100), 'Kuis cepat: ' . (int)$p['benar'] . '/' . count($soal) . ' benar, peringkat ' . $p['rank']]);
                $n++;
            }
            echo json_encode(['ok' => true, 'pesan' => "$n nilai disimpan ke Penilaian.", 'tautan' => '?p=nilai&id=' . $pid]);
            exit;
    }
    $k = kuis_ambil($kid);
}

// kuis berjalan sendiri: waktu habis / semua menjawab → soal berikutnya (jawaban baru dibuka di akhir)
$k = kuis_maju($k);
$peringkat = kuis_peringkat($kid);
$no = (int)$k['nomor'];
$dijawab = 0;
foreach ($peringkat as $p) if (isset((json_decode((string)$p['jawaban'], true) ?: [])[$no])) $dijawab++;
$s = $soal[$no] ?? null;
echo json_encode([
    'ok' => true, 'status' => $k['status'], 'nomor' => $no, 'total' => count($soal), 'detik' => (int)$k['detik'],
    'sisa_ms' => kuis_sisa_ms($k), 'mulai_dalam' => max(0, (int)$k['mulai_ms'] - kuis_ms()),
    'soal' => $s && $k['status'] === 'soal' ? ['teks' => $s['teks'], 'g' => gambar_url($s['gambar'] ?? null),
        'opsi' => array_map(fn($h, $t) => ['k' => $h, 't' => $t, 'g' => gambar_url($s['gambar_opsi'][$h] ?? null)], array_keys($s['opsi']), $s['opsi'])] : null,
    'rekap' => $k['status'] === 'selesai' ? kuis_rekap($k, $peringkat) : null,
    'dijawab' => $dijawab,
    'peserta' => array_map(fn($p) => ['kode' => $p['kode'], 'nama' => $p['nama'], 'poin' => $k['status'] === 'selesai' ? (int)$p['poin'] : null,
        'benar' => $k['status'] === 'selesai' ? (int)$p['benar'] : null, 'rank' => $k['status'] === 'selesai' ? $p['rank'] : null], $peringkat),
], JSON_UNESCAPED_UNICODE);

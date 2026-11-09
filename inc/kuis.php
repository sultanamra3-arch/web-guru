<?php
/* Kuis cepat di kelas: soal tampil di proyektor, siswa menjawab dari HP, poin berdasarkan ketepatan & kecepatan. */
declare(strict_types=1);

function kuis_ambil(int $id, bool $milik_saya = true): ?array
{
    $k = $milik_saya
        ? satu('SELECT * FROM guru_kuis WHERE id = ? AND guru_id = ?', [$id, uid()])
        : satu('SELECT * FROM guru_kuis WHERE id = ?', [$id]);
    return $k ?: null;
}

function kuis_kode_baru(): string
{
    $huruf = '23456789';
    do {
        $k = '';
        for ($i = 0; $i < 6; $i++) $k .= $huruf[random_int(0, strlen($huruf) - 1)];
    } while (nilai('SELECT 1 FROM guru_kuis WHERE kode = ?', [$k]));
    return $k;
}

/** Ambil soal pilihan ganda & benar/salah dari paket (isian/uraian dilewati). */
function kuis_soal_dari_paket(int $paket_id): array
{
    $d = satu("SELECT * FROM guru_dokumen WHERE id = ? AND guru_id = ? AND jenis = 'paket'", [$paket_id, uid()]);
    if (!$d) return [];
    $out = [];
    foreach (ujian_soal_dari_paket($paket_id, 1) ?? [] as $s) {
        if (!in_array($s['tipe'], ['pg', 'bs'], true) || !$s['opsi'] || !isset($s['opsi'][$s['kunci']])) continue;
        $out[] = ['teks' => $s['teks'], 'gambar' => $s['gambar'] ?? null, 'opsi' => $s['opsi'], 'gambar_opsi' => $s['gambar_opsi'] ?? [],
            'kunci' => $s['kunci'], 'pembahasan' => $s['pembahasan']];
    }
    return $out;
}

function kuis_soal(array $k): array
{
    return json_decode((string)$k['soal'], true) ?: [];
}

function kuis_ms(): int
{
    return (int)round(microtime(true) * 1000);
}

/** Sisa waktu soal aktif (ms); 0 bila habis / tidak sedang menampilkan soal. */
function kuis_sisa_ms(array $k): int
{
    if ($k['status'] !== 'soal' || !$k['mulai_ms']) return 0;
    return max(0, (int)$k['mulai_ms'] + (int)$k['detik'] * 1000 - kuis_ms());
}

function kuis_peringkat(int $kid): array
{
    $r = semua('SELECT p.*, COALESCE(s.nama, p.kode) AS nama FROM guru_kuis_peserta p LEFT JOIN guru_siswa s ON s.kode = p.kode
                WHERE p.kuis_id = ? ORDER BY p.poin DESC, p.benar DESC, p.gabung', [$kid]);
    foreach ($r as $i => &$p) $p['rank'] = $i + 1;
    unset($p);
    return $r;
}

function kuis_url(array $k): string
{
    $s = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $s . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/k/?k=' . $k['kode'];
}

/** Poin satu jawaban benar: 500 + sampai 500 tambahan untuk yang cepat. */
function kuis_poin(int $sisa_ms, int $detik): int
{
    return 500 + (int)round(500 * max(0, min(1, $sisa_ms / max(1, $detik * 1000))));
}

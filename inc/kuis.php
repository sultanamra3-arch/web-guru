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

/**
 * Kuis berjalan sendiri sampai akhir: bila waktu soal habis atau semua peserta sudah menjawab,
 * langsung lanjut ke soal berikutnya (jeda hitung mundur 3 detik); setelah soal terakhir → selesai.
 * Dipanggil oleh layar guru maupun HP siswa (siapa pun yang lebih dulu), aman dari lompatan ganda.
 */
function kuis_maju(array $k): array
{
    if (kuis_mandiri($k)) return kuis_mandiri_cek_akhir($k);
    if (!in_array($k['status'], ['soal', 'jawab'], true)) return $k;
    $sekarang = kuis_ms();
    $habis = $sekarang > (int)$k['mulai_ms'] + (int)$k['detik'] * 1000 + 800;
    $semua = false;
    if (!$habis && $k['status'] === 'soal' && $sekarang > (int)$k['mulai_ms']) {
        $n = (int)nilai('SELECT COUNT(*) FROM guru_kuis_peserta WHERE kuis_id = ?', [$k['id']]);
        $jawab = (int)nilai('SELECT COUNT(*) FROM guru_kuis_peserta WHERE kuis_id = ? AND JSON_EXTRACT(jawaban, ?) IS NOT NULL', [$k['id'], '$."' . (int)$k['nomor'] . '"']);
        $semua = $n > 0 && $jawab >= $n;
    }
    if ($k['status'] !== 'jawab' && !$habis && !$semua) return $k;
    $berikut = (int)$k['nomor'] + 1;
    $total = count(kuis_soal($k));
    $ok = $berikut >= $total
        ? q("UPDATE guru_kuis SET status = 'selesai' WHERE id = ? AND nomor = ? AND status IN ('soal','jawab')", [$k['id'], $k['nomor']])->rowCount()
        : q("UPDATE guru_kuis SET status = 'soal', nomor = ?, mulai_ms = ? WHERE id = ? AND nomor = ? AND status IN ('soal','jawab')", [$berikut, $sekarang + 3000, $k['id'], $k['nomor']])->rowCount();
    return $ok ? (kuis_ambil((int)$k['id'], false) ?? $k) : (kuis_ambil((int)$k['id'], false) ?? $k);
}

/** Rekap per soal (dipakai di akhir): kunci, sebaran pilihan, jumlah benar. */
function kuis_rekap(array $k, array $peringkat): array
{
    $out = [];
    foreach (kuis_soal($k) as $i => $s) {
        $seb = [];
        $benar = 0;
        foreach ($peringkat as $p) {
            $j = json_decode((string)$p['jawaban'], true) ?: [];
            if (!isset($j[$i])) continue;
            $seb[$j[$i]['p']] = ($seb[$j[$i]['p']] ?? 0) + 1;
            if ($j[$i]['p'] === $s['kunci']) $benar++;
        }
        $out[] = ['no' => $i + 1, 'teks' => $s['teks'], 'g' => gambar_url($s['gambar'] ?? null), 'kunci' => $s['kunci'],
            'opsi' => array_map(fn($h, $t) => ['k' => $h, 't' => $t, 'n' => $seb[$h] ?? 0], array_keys($s['opsi']), $s['opsi']),
            'benar' => $benar, 'pembahasan' => (string)$s['pembahasan']];
    }
    return $out;
}

/* ---------- Mode mandiri: tiap siswa maju sendiri, soal & pilihan diacak per siswa ---------- */
function kuis_mandiri(array $k): bool
{
    return ($k['mode'] ?? 'bersama') === 'mandiri';
}

function kuis_urutan_baru(array $k): array
{
    $soal = kuis_soal($k);
    $s = array_keys($soal);
    shuffle($s);
    $o = [];
    foreach ($soal as $i => $x) { $kk = array_keys($x['opsi']); shuffle($kk); $o[$i] = $kk; }
    return ['s' => $s, 'o' => $o];
}

/**
 * Langkah seorang siswa (mode mandiri): siapkan urutan, mulai jam soal, lewati soal yang waktunya habis
 * (dianggap tidak menjawab), tandai selesai setelah soal terakhir. Mengembalikan baris peserta terbaru.
 */
function kuis_mandiri_langkah(array $k, array $p): array
{
    if ($k['status'] !== 'jalan') return $p;
    $now = kuis_ms();
    if ($now < (int)$k['mulai_ms']) return $p;
    $total = count(kuis_soal($k));
    $ubah = [];
    if (!$p['urutan']) { $p['urutan'] = json_encode(kuis_urutan_baru($k)); $ubah['urutan'] = $p['urutan']; }
    $pos = (int)$p['pos'];
    $ms = $p['soal_ms'] !== null ? (int)$p['soal_ms'] : null;
    if ($pos < $total && $ms === null) { $ms = $now; $ubah['soal_ms'] = $ms; }
    $batas = (int)$k['detik'] * 1000 + 800;
    while ($pos < $total && $ms !== null && $now > $ms + $batas) { $pos++; $ms += $batas; $ubah['pos'] = $pos; $ubah['soal_ms'] = $ms; }
    if ($pos >= $total && $p['selesai_ms'] === null) { $ubah['selesai_ms'] = min($now, $ms ?? $now); }
    if ($ubah) {
        q('UPDATE guru_kuis_peserta SET ' . implode(', ', array_map(fn($c) => "$c = ?", array_keys($ubah))) . ' WHERE kuis_id = ? AND kode = ?',
            [...array_values($ubah), $k['id'], $p['kode']]);
        $p = array_merge($p, $ubah);
    }
    return $p;
}

/** Mode mandiri: kuis berakhir bila waktu total habis atau semua peserta sudah selesai. */
function kuis_mandiri_cek_akhir(array $k): array
{
    if ($k['status'] !== 'jalan') return $k;
    $now = kuis_ms();
    $selesai = $now > (int)$k['selesai_ms'];
    if (!$selesai && $now > (int)$k['mulai_ms']) {
        $r = satu('SELECT COUNT(*) n, SUM(selesai_ms IS NOT NULL) s FROM guru_kuis_peserta WHERE kuis_id = ?', [$k['id']]);
        $selesai = (int)$r['n'] > 0 && (int)$r['s'] >= (int)$r['n'];
    }
    if ($selesai) {
        q("UPDATE guru_kuis SET status = 'selesai' WHERE id = ? AND status = 'jalan'", [$k['id']]);
        $k['status'] = 'selesai';
    }
    return $k;
}

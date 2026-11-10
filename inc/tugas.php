<?php
/* Tugas online: siswa mengirim foto/teks tugas lewat HP, guru menilai. */
declare(strict_types=1);

const TERTIB_TUGAS = "Kerjakan sendiri. Boleh membuka buku catatan.\nTulis nama dan kelas di kertas tugasmu.\nFoto harus jelas, terang, dan tegak (tidak miring atau gelap).";
const TUGAS_HAPUS_FOTO_HARI = 90;

function tugas_ambil(int $id, bool $milik_saya = true): ?array
{
    $t = $milik_saya
        ? satu('SELECT * FROM guru_tugas WHERE id = ? AND guru_id = ?', [$id, uid()])
        : satu('SELECT * FROM guru_tugas WHERE id = ?', [$id]);
    return $t ?: null;
}

function tugas_kode_baru(): string
{
    $huruf = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    do {
        $k = 'T';
        for ($i = 0; $i < 5; $i++) $k .= $huruf[random_int(0, strlen($huruf) - 1)];
    } while (nilai('SELECT 1 FROM guru_tugas WHERE kode = ?', [$k]));
    return $k;
}

function tugas_tertib_bawaan(int $guru): string
{
    return (string)setelan('tertib_tugas:' . $guru, TERTIB_TUGAS);
}

function tugas_lewat(array $t): bool
{
    return $t['batas'] && strtotime((string)$t['batas']) < time();
}

/** Boleh mengirim? ($k = kiriman siswa ini bila ada; "perbaiki" selalu boleh selama tugas dibuka). */
function tugas_bisa_kirim(array $t, ?array $k = null): string
{
    if ($t['status'] !== 'buka') return 'Tugas sudah ditutup oleh guru.';
    if ($k && $k['status'] === 'perbaiki') return '';
    if ($k && $k['status'] === 'dinilai') return 'Tugasmu sudah dinilai.';
    if (tugas_lewat($t) && !$t['terima_telat']) return 'Batas waktu pengumpulan sudah lewat.';
    return '';
}

/** Poin tata tertib: tulisan guru + poin otomatis sesuai pengaturan. */
function tugas_tata_tertib(array $t): array
{
    $guru = ($t['tata_tertib'] ?? '') !== '' ? (string)$t['tata_tertib'] : tugas_tertib_bawaan((int)$t['guru_id']);
    $poin = array_map('h', array_values(array_filter(array_map('trim', preg_split('/\r\n|\n/', $guru)), fn($x) => $x !== '')));
    if ($t['batas']) {
        $poin[] = 'Kirim sebelum <b>' . h(ujian_waktu_teks((string)$t['batas'])) . '</b>. '
            . ($t['terima_telat'] ? 'Yang lewat batas tetap diterima tapi <b>ditandai terlambat</b>.' : 'Setelah lewat batas, tugas <b>tidak bisa dikirim lagi</b>.');
    }
    $poin[] = 'Foto paling banyak <b>' . (int)$t['maks_foto'] . '</b> lembar; foto otomatis dikecilkan agar hemat kuota.';
    $poin[] = 'Kamu boleh mengirim ulang sebelum batas waktu; kiriman terakhir yang dinilai.';
    return $poin;
}

function tugas_url_siswa(array $t): string
{
    $s = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $s . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/t/?k=' . $t['kode'];
}

/* ---------- foto kiriman (folder terkunci, hanya guru pemilik & siswa pengirim) ---------- */
function tugas_folder(): string
{
    $d = ai_folder_data() . '/tugas';
    if (!is_dir($d)) @mkdir($d, 0750, true);
    return $d;
}

function tugas_foto_simpan(string $data_url): ?string
{
    if (!preg_match('#^data:image/(jpeg|png|webp);base64,([A-Za-z0-9+/=\s]+)$#', $data_url, $m)) return null;
    $isi = base64_decode(preg_replace('/\s+/', '', $m[2]), true);
    if ($isi === false || strlen($isi) > 4 * 1024 * 1024) return null;
    $info = @getimagesizefromstring($isi);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) return null;
    $ext = [IMAGETYPE_JPEG => '.jpg', IMAGETYPE_PNG => '.png', IMAGETYPE_WEBP => '.webp'][$info[2]];
    $nama = bin2hex(random_bytes(12)) . $ext;
    return @file_put_contents(tugas_folder() . '/' . $nama, $isi) ? $nama : null;
}

function tugas_foto_daftar(?array $k): array
{
    return array_values(array_filter((array)json_decode((string)($k['foto'] ?? ''), true), fn($f) => is_string($f) && preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $f)));
}

function tugas_foto_url(int $tid, string $f): string
{
    return '?p=tugas_foto&t=' . $tid . '&f=' . rawurlencode($f);
}

function tugas_hapus_foto(array $files): void
{
    foreach ($files as $f) if (preg_match('/^[a-f0-9]{24}\.(jpg|png|webp)$/', $f)) @unlink(tugas_folder() . '/' . $f);
}

/** Hapus foto tugas yang nilainya sudah dikirim ke Penilaian lebih dari 90 hari lalu (nilai tetap tersimpan). */
function tugas_bersihkan_lama(): void
{
    $batas = date('Y-m-d H:i:s', time() - TUGAS_HAPUS_FOTO_HARI * 86400);
    foreach (semua("SELECT k.tugas_id, k.kode, k.foto FROM guru_tugas_kirim k JOIN guru_tugas t ON t.id = k.tugas_id
                    WHERE t.guru_id = ? AND t.nilai_dikirim IS NOT NULL AND t.nilai_dikirim < ? AND k.foto IS NOT NULL AND k.foto <> '[]'", [uid(), $batas]) as $k) {
        tugas_hapus_foto(tugas_foto_daftar($k));
        q("UPDATE guru_tugas_kirim SET foto = '[]' WHERE tugas_id = ? AND kode = ?", [$k['tugas_id'], $k['kode']]);
    }
}

function alat_perangkat(): string
{
    $c = (string)($_COOKIE['sgu_alat'] ?? '');
    if (!preg_match('/^[a-f0-9]{32}$/', $c)) {
        $c = bin2hex(random_bytes(16));
        setcookie('sgu_alat', $c, ['expires' => time() + 400 * 86400, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
        $_COOKIE['sgu_alat'] = $c;
    }
    return $c;
}

/** Saran nilai dari AI untuk satu kiriman (membaca foto + teks, memakai petunjuk & kunci/rubrik guru). */
function ai_nilai_tugas(array $t, array $k): array
{
    $foto = [];
    foreach (array_slice(tugas_foto_daftar($k), 0, 5) as $f) {
        $path = tugas_folder() . '/' . $f;
        if (!is_file($path)) continue;
        $mime = str_ends_with($f, '.png') ? 'image/png' : (str_ends_with($f, '.webp') ? 'image/webp' : 'image/jpeg');
        $foto[] = 'data:' . $mime . ';base64,' . base64_encode((string)file_get_contents($path));
    }
    $teks = trim((string)$k['teks']);
    if (!$foto && $teks === '') return ['ok' => false, 'galat' => 'Kiriman kosong.'];
    $perintah = "Kamu membantu guru SMP menilai tugas siswa. Beri saran nilai 0–100 dan catatan singkat untuk siswa.\n"
        . "Judul tugas: {$t['judul']}\nMapel: {$t['mapel']}\nPetunjuk tugas: " . mb_substr((string)$t['petunjuk'], 0, 2000) . "\n"
        . (trim((string)$t['kunci']) !== '' ? "Kunci/rubrik dari guru: " . mb_substr((string)$t['kunci'], 0, 3000) . "\n" : "Tidak ada kunci; nilai berdasarkan kelengkapan, ketepatan, dan kerapian sesuai petunjuk.\n")
        . ($teks !== '' ? "Jawaban teks siswa: " . mb_substr($teks, 0, 3000) . "\n" : '')
        . ($foto ? count($foto) . " foto hasil kerja siswa terlampir.\n" : '')
        . "Bila foto tidak terbaca atau bukan tugas yang diminta, beri nilai 0 dan jelaskan. Catatan ditujukan ke siswa, ramah, maksimal 2 kalimat, sebutkan yang sudah baik dan yang perlu diperbaiki.\n"
        . 'Balas HANYA JSON: {"nilai": 80, "catatan": "...", "ragu": false}';
    $isi = $foto ? array_merge([['type' => 'text', 'text' => $perintah]], ai_bagian_foto($foto, 5)) : $perintah;
    $r = ai_minta([['role' => 'user', 'content' => $isi]], ['gambar' => (bool)$foto, 'maks_token' => 4000, 'waktu' => 120, 'suhu' => 0.2]);
    if (!$r['ok']) return $r;
    $j = ai_ambil_json((string)$r['pesan']['content']);
    if (!is_array($j) || !isset($j['nilai'])) return ['ok' => false, 'galat' => 'AI membalas dengan format yang tidak dikenali.', 'rincian' => [$r['oleh']]];
    return ['ok' => true, 'nilai' => max(0, min(100, (float)$j['nilai'])), 'catatan' => mb_substr(trim((string)($j['catatan'] ?? '')), 0, 400),
        'ragu' => !empty($j['ragu']), 'oleh' => $r['oleh']];
}

/** Nama siswa per status kiriman untuk dibagikan ke grup WA (tanpa nilai). */
function tugas_status_kumpul(array $t): array
{
    $kirim = [];
    foreach (semua('SELECT kode, dikirim, status FROM guru_tugas_kirim WHERE tugas_id = ?', [$t['id']]) as $k) $kirim[$k['kode']] = $k;
    $daftar = [];
    foreach (siswa_kelas((string)$t['kelas'], (string)($t['tahun'] ?: tahun_aktif())) as $s) {
        $daftar[] = [$s['member_name'], $kirim[$s['member_id']] ?? null];
        unset($kirim[$s['member_id']]);
    }
    foreach ($kirim as $kd => $k) $daftar[] = [nama_siswa((string)$kd), $k];
    $o = ['sudah' => [], 'jalan' => [], 'belum' => [], 'perbaiki' => []];
    foreach ($daftar as [$nama, $k]) {
        if ($k && $k['status'] === 'perbaiki') $o['perbaiki'][] = $nama;
        elseif ($k && $k['dikirim']) $o['sudah'][] = $nama;
        else $o['belum'][] = $nama;
    }
    return $o;
}

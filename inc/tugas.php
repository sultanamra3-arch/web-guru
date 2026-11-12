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
    if (tugas_kelompok($t)) $poin[] = 'Ini <b>tugas kelompok</b>: cukup <b>satu anggota</b> yang mengirim. Anggota lain bisa melihat, menambah foto, atau mengirim ulang.';
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
const KOMENTAR_GAYA = ['lucu' => 'Lucu', 'hangat' => 'Hangat', 'singkat' => 'Singkat', 'tegas' => 'Tegas'];
const SAPAAN_GURU = ['' => '(tanpa menyebut diri)', 'Bapak' => 'Bapak', 'Ibu' => 'Ibu', 'Cikgu' => 'Cikgu', 'Pak Guru' => 'Pak Guru', 'Bu Guru' => 'Bu Guru'];

/** Aturan menulis komentar AI untuk siswa, sesuai gaya & sapaan yang dipilih guru di Profil. */
function komentar_aturan(int $guru_id, string $nama): string
{
    $gaya = setelan('komentar_gaya:' . $guru_id) ?: 'hangat';
    if (!isset(KOMENTAR_GAYA[$gaya])) $gaya = 'hangat';
    $sapa = (string)(setelan('sapaan:' . $guru_id) ?: '');
    $depan = trim(explode(' ', trim(preg_replace('/^(muhammad|muhammad|m\.|moh\.?|nur)\s+/i', '', $nama)))[0] ?? '');
    $depan = $depan !== '' ? mb_convert_case(mb_strtolower($depan), MB_CASE_TITLE) : '';
    $o = "KOMENTAR (\"catatan\") ditulis seperti guru yang berbicara langsung kepada muridnya, bukan laporan:\n"
        . ($depan !== '' ? "- Sapa siswa dengan nama panggilannya: \"$depan\" (boleh di awal kalimat).\n" : '')
        . ($sapa !== '' ? "- Guru menyebut dirinya \"$sapa\" (mis. \"$sapa senang membacanya\"). Jangan memakai kata \"saya\".\n" : "- Jangan menyebut diri guru (tanpa \"saya\"/\"Bapak\"/\"Ibu\").\n")
        . "- Sebut ISI jawaban siswa yang spesifik (nomor soal atau gagasannya), bukan kalimat umum.\n"
        . "- Beri SATU saran perbaikan yang jelas dan bisa langsung dilakukan.\n"
        . "- Nada menyesuaikan nilai: tinggi → puji dengan tulus; sedang → akui yang baik lalu dorong; rendah → semangati, jangan menghakimi atau membuat malu.\n"
        . "- Hindari kata-kata kaku seperti \"secara keseluruhan\", \"namun demikian\", \"perlu ditingkatkan lagi\", \"sudah cukup baik\". Bahasa Indonesia sehari-hari yang sopan.\n";
    switch ($gaya) {
        case 'lucu':
            $pantun = random_int(1, 4) === 1;
            $o .= "- GAYA LUCU: jenaka dan hangat seperti guru yang disukai murid. Boleh perumpamaan lucu bernuansa Melayu Riau (sampan, dayung, pantai, Hang Tuah, durian, sagu, dll.) dan 1–2 emoji.\n"
                . "- Lucu yang MENYEMANGATI: jangan menyindir, mengejek, membandingkan dengan teman, atau membahas fisik/keluarga siswa.\n"
                . ($pantun ? "- Kali ini SERTAKAN satu pantun pendek 4 baris (sampiran a-b-a-b, isi tentang jawabannya), lalu satu kalimat saran.\n" : "- Kali ini jangan memakai pantun.\n")
                . "- Panjang 2–3 kalimat pendek, MAKSIMAL 45 kata" . ($pantun ? ' di luar pantun; pantun diletakkan paling akhir' : '') . ".\n";
            break;
        case 'singkat':
            $o .= "- GAYA SINGKAT: tepat SATU kalimat pendek (maks. 20 kata), tetap menyebut isi jawaban & saran. Tanpa emoji, tanpa lelucon.\n";
            break;
        case 'tegas':
            $o .= "- GAYA TEGAS: lugas dan jelas, langsung ke poin (apa yang benar, apa yang harus diperbaiki), tetap sopan. Tanpa emoji, tanpa lelucon, tanpa perumpamaan. 2 kalimat, maksimal 35 kata.\n";
            break;
        default:
            $o .= "- GAYA HANGAT: ramah dan menyemangati, boleh 1 emoji. 2–3 kalimat, maksimal 40 kata. Tanpa pantun dan tanpa lelucon.\n";
    }
    return $o . "- Bila foto buram/tidak terbaca atau soal belum semua dijawab, katakan dengan bahasa yang sama gayanya dan minta dikirim ulang/dilengkapi.\n";
}

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
        . "Bila foto tidak terbaca atau bukan tugas yang diminta, beri nilai 0 dan jelaskan di catatan.\n"
        . komentar_aturan((int)$t['guru_id'], tugas_kelompok($t) ? '' : nama_siswa((string)$k['kode']))
        . (tugas_kelompok($t) ? "- Ini tugas KELOMPOK: sapa \"kelompok " . (int)substr((string)$k['kode'], 1) . "\" atau \"kalian\".\n" : '')
        . 'Balas HANYA JSON: {"nilai": 80, "catatan": "...", "ragu": false}';
    $isi = $foto ? array_merge([['type' => 'text', 'text' => $perintah]], ai_bagian_foto($foto, 5)) : $perintah;
    $r = ai_minta([['role' => 'user', 'content' => $isi]], ['gambar' => (bool)$foto, 'maks_token' => 4000, 'waktu' => 120, 'suhu' => 0.2]);
    if (!$r['ok']) return $r;
    $j = ai_ambil_json((string)$r['pesan']['content']);
    if (!is_array($j) || !isset($j['nilai'])) return ['ok' => false, 'galat' => 'AI membalas dengan format yang tidak dikenali.', 'rincian' => [$r['oleh']]];
    return ['ok' => true, 'nilai' => max(0, min(100, (float)$j['nilai'])), 'catatan' => mb_substr(trim((string)($j['catatan'] ?? '')), 0, 700),
        'ragu' => !empty($j['ragu']), 'oleh' => $r['oleh']];
}

/* ---------- tugas kelompok (v1.39): satu kiriman per kelompok, kode kiriman "K<nomor>" ---------- */
function tugas_kelompok(array $t): bool
{
    return !empty($t['kelompok']);
}

/** [kode siswa => nomor kelompok] untuk kelas & tahun tugas ini. */
function tugas_peta_kelompok(array $t): array
{
    static $cache = [];
    $id = (int)$t['id'];
    return $cache[$id] ??= kelompok_kelas((string)$t['kelas'], (string)($t['tahun'] ?: tahun_aktif((int)$t['guru_id'])), (int)$t['guru_id']);
}

/** Kode baris kiriman untuk siswa ini (individu: kode siswa; kelompok: "K<n>", null bila belum masuk kelompok). */
function tugas_kode_kirim(array $t, string $siswa): ?string
{
    if (!tugas_kelompok($t)) return $siswa;
    $n = tugas_peta_kelompok($t)[$siswa] ?? 0;
    return $n ? 'K' . $n : null;
}

function tugas_kode_kelompok(string $kode): int
{
    return preg_match('/^K(\d{1,2})$/', $kode, $m) ? (int)$m[1] : 0;
}

/** Anggota kelompok: [kode siswa => nama], urut nomor absen. */
function tugas_anggota(array $t, int $n): array
{
    $peta = tugas_peta_kelompok($t);
    $o = [];
    foreach (siswa_kelas((string)$t['kelas'], (string)($t['tahun'] ?: tahun_aktif((int)$t['guru_id'])), (int)$t['guru_id']) as $s) {
        if (($peta[$s['member_id']] ?? 0) === $n) $o[$s['member_id']] = $s['member_name'];
    }
    return $o;
}

/** Nomor-nomor kelompok yang ada untuk tugas ini. */
function tugas_daftar_kelompok(array $t): array
{
    $n = array_values(array_unique(tugas_peta_kelompok($t)));
    sort($n);
    return $n;
}

/** Nama untuk ditampilkan: "Kelompok 2" atau nama siswa. */
function tugas_nama_kirim(string $kode): string
{
    $n = tugas_kode_kelompok($kode);
    return $n ? 'Kelompok ' . $n : nama_siswa($kode);
}

/** Nama siswa per status kiriman untuk dibagikan ke grup WA (tanpa nilai). */
function tugas_status_kumpul(array $t): array
{
    $kirim = [];
    foreach (semua('SELECT kode, dikirim, status FROM guru_tugas_kirim WHERE tugas_id = ?', [$t['id']]) as $k) $kirim[$k['kode']] = $k;
    $daftar = [];
    if (tugas_kelompok($t)) {
        foreach (tugas_daftar_kelompok($t) as $n) {
            $daftar[] = ['Kelompok ' . $n . ' (' . implode(', ', tugas_anggota($t, $n)) . ')', $kirim['K' . $n] ?? null];
            unset($kirim['K' . $n]);
        }
    } else {
        foreach (siswa_kelas((string)$t['kelas'], (string)($t['tahun'] ?: tahun_aktif())) as $s) {
            $daftar[] = [$s['member_name'], $kirim[$s['member_id']] ?? null];
            unset($kirim[$s['member_id']]);
        }
    }
    foreach ($kirim as $kd => $k) $daftar[] = [tugas_nama_kirim((string)$kd), $k];
    $o = ['sudah' => [], 'jalan' => [], 'belum' => [], 'perbaiki' => []];
    foreach ($daftar as [$nama, $k]) {
        if ($k && $k['status'] === 'perbaiki') $o['perbaiki'][] = $nama;
        elseif ($k && $k['dikirim']) $o['sudah'][] = $nama;
        else $o['belum'][] = $nama;
    }
    return $o;
}

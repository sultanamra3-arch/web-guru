<?php
/* Gambar soal: disimpan di data/gambar (tidak bisa dibuka langsung), dilayani lewat ?p=gambar. */
declare(strict_types=1);

const GAMBAR_POLA = '/^[a-f0-9]{24}\.(jpg|png)$/';

function gambar_folder(): string
{
    $d = ai_folder_data() . '/gambar';
    if (!is_dir($d)) @mkdir($d, 0750, true);
    return $d;
}

/** Simpan gambar dari data URL (sudah dikecilkan di peramban). Kembalikan nama berkas atau null. */
function gambar_simpan(string $data_url): ?string
{
    if (!preg_match('#^data:image/(jpeg|png);base64,([A-Za-z0-9+/=\s]+)$#', $data_url, $m)) return null;
    $isi = base64_decode(preg_replace('/\s+/', '', $m[2]), true);
    if ($isi === false || strlen($isi) > 3 * 1024 * 1024) return null;
    $info = @getimagesizefromstring($isi);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) return null;
    $nama = bin2hex(random_bytes(12)) . ($info[2] === IMAGETYPE_PNG ? '.png' : '.jpg');
    return @file_put_contents(gambar_folder() . '/' . $nama, $isi) ? $nama : null;
}

function gambar_url(?string $f, bool $mutlak = false): string
{
    if (!$f || !preg_match(GAMBAR_POLA, $f)) return '';
    $rel = '?p=gambar&f=' . $f;
    if (!$mutlak) return $rel;
    $s = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $s . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/' . $rel;
}

function gambar_html(?string $f, string $kelas = 'gambar-soal', bool $mutlak = false): string
{
    $u = gambar_url($f, $mutlak);
    return $u ? '<img class="' . h($kelas) . '" src="' . h($u) . '" alt="" loading="lazy">' : '';
}

/** Gambar opsi dari kolom JSON {"A":"berkas.jpg"} */
function gambar_opsi(?string $json): array
{
    $g = json_decode((string)$json, true);
    return is_array($g) ? array_filter($g, fn($f) => is_string($f) && preg_match(GAMBAR_POLA, $f)) : [];
}

<?php
/* Melayani gambar soal (nama berkas acak, tidak bisa ditebak). */
declare(strict_types=1);

$f = (string)get('f');
$path = gambar_folder() . '/' . $f;
if (!preg_match(GAMBAR_POLA, $f) || !is_file($path)) { http_response_code(404); exit; }
header('Content-Type: ' . (str_ends_with($f, '.png') ? 'image/png' : 'image/jpeg'));
header('Content-Length: ' . filesize($path));
header('Cache-Control: public, max-age=31536000, immutable');
readfile($path);
exit;

<?php
/*
 * SiGuru → SLiMS tanpa login ulang (v1, SiGuru v1.74).
 * PASANG: unggah berkas INI SAJA ke folder public_html (sebelah index.php SLiMS). Jangan ekstrak zip apa pun di public_html.
 * Tidak mengubah kode SLiMS. Memakai kunci bersama yang sama dengan SiPustaka (dibuat otomatis oleh SiGuru).
 *
 * Alamat dari SiGuru (?p=ke_slims): siguru-masuk.php?u=USER&t=WAKTU&n=ACAK&s=TANDA_TANGAN
 * Tanda tangan = HMAC-SHA256("slims|u|t|n", kunci). Berlaku 60 detik, sekali pakai.
 * Bila gagal / tidak sah → dibuka halaman login SLiMS biasa.
 */

$KUNCI_FILE = '/home/bzydmpxm/kelola.otomasismpn6keritang.my.id/data/sso-kunci.php';

function sg_ke_login(): void
{
    header('Cache-Control: no-store');
    header('Location: index.php?p=login');
    exit;
}

// 1. periksa tanda tangan (sebelum SLiMS dimuat)
$u = (string)($_GET['u'] ?? '');
$t = (string)($_GET['t'] ?? '');
$n = (string)($_GET['n'] ?? '');
$s = (string)($_GET['s'] ?? '');
$kunci = is_file($KUNCI_FILE) ? (include $KUNCI_FILE) : null;
if (!is_string($kunci) || strlen($kunci) < 32) sg_ke_login();
if (!preg_match('/^[A-Za-z0-9_.@-]{1,50}$/', $u) || !ctype_digit($t) || !preg_match('/^[a-f0-9]{16}$/', $n) || !preg_match('/^[a-f0-9]{64}$/', $s)) sg_ke_login();
if (abs(time() - (int)$t) > 60) sg_ke_login();
if (!hash_equals(hash_hmac('sha256', 'slims|' . $u . '|' . $t . '|' . $n, $kunci), $s)) sg_ke_login();

// 2. sekali pakai: catat nomor acak yang sudah dipakai (dibersihkan setelah 10 menit)
$catat = __DIR__ . '/files/cache/siguru-masuk';
if (!is_dir($catat)) @mkdir($catat, 0750, true);
if (is_dir($catat)) {
    foreach ((array)glob($catat . '/*') as $f) if (is_file($f) && filemtime($f) < time() - 600) @unlink($f);
    $f = $catat . '/' . $n;
    if (is_file($f) || @file_put_contents($f, (string)time(), LOCK_EX) === false) sg_ke_login();
}

// 3. masuk memakai cara SLiMS sendiri (sama seperti halaman login SLiMS)
define('INDEX_AUTH', '1');
require __DIR__ . '/sysconfig.inc.php';

use SLiMS\DB;
use SLiMS\Auth\Validator;
use SLiMS\Auth\Methods\Native;
use SLiMS\Http\Cookie;

$q = DB::query('SELECT u.user_id AS uid, u.username AS uname, u.passwd, u.realname AS realname, u.groups, u.user_image AS upict, u.`2fa`, u.admin_template AS template
                FROM user AS u WHERE u.username = ?', [$u]);
if ($q->count() < 1) sg_ke_login();
$data = $q->first();
if (!empty($data['2fa'])) sg_ke_login(); // akun dengan verifikasi 2 langkah: tetap lewat login biasa

require SB . 'admin/default/session.inc.php';
session_regenerate_id(true);

$logon = Validator::use(Native::class);
$m = $logon->getMethodInstance();
$m->setData($data);
$m->setAdminData();
$m->generateSession();

Cookie::withPath(SWB)->withExpires(time() + 14400)->withHttponly()->withSamesite('Lax')->set('admin_logged_in', true);
if ($bahasa = ($m->getData('template')['default_lang'] ?? null)) {
    Cookie::withPath(SWB . 'admin')->withExpires(time() + 14400)->set('admin_lang', $bahasa);
}

// tetap masuk 30 hari di HP ini (fitur "ingat saya" bawaan SLiMS)
$_SESSION['remember_me'] = true;
try { $logon->rememberMe(30); } catch (Throwable $e) { /* abaikan */ }

if (function_exists('writeLog')) {
    try { writeLog('staff', $u, 'Login', 'Login dari SiGuru (tanpa sandi, tautan bertanda tangan) untuk ' . $u . ' dari ' . ip()); } catch (Throwable $e) { /* abaikan */ }
}

header('Cache-Control: no-store');
header('Location: admin/index.php');
exit;

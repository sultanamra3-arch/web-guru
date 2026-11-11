<?php
/* Notifikasi untuk APK SiGuru: ?p=api_notif&k=KUNCI&s=SEJAK (unix). Tanpa login; kunci = kunci notifikasi guru. */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$g = notif_guru((string)get('k'));
if (!$g) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'alasan' => 'kunci']);
    exit;
}
$kini = time();
$s = (int)get('s');
// pemeriksaan pertama / sudah lama tidak memeriksa: jangan menumpuk notifikasi lama
$notif = ($s <= 0 || $s > $kini) ? [] : notif_kumpulkan($g, max($s, $kini - 86400));
echo json_encode(['ok' => true, 'kini' => $kini, 'notif' => $notif], JSON_UNESCAPED_UNICODE);
exit;

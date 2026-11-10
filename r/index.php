<?php
// Alamat pendek resi: /r/?k=KODE → halaman status tugas/ujian
$k = preg_replace('/[^A-Za-z0-9]/', '', (string)($_GET['k'] ?? ''));
header('Location: ../?p=resi' . ($k !== '' ? '&k=' . $k : ''), true, 302);

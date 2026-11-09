<?php
// Alamat pendek untuk siswa: /k/?k=KODE → kuis cepat
$k = preg_replace('/[^0-9]/', '', (string)($_GET['k'] ?? ''));
header('Location: ../?p=k' . ($k !== '' ? '&k=' . $k : ''), true, 302);

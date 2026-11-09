<?php
// Alamat pendek untuk siswa: /u/?k=KODE → halaman masuk ujian
$k = preg_replace('/[^A-Za-z0-9]/', '', (string)($_GET['k'] ?? ''));
header('Location: ../?p=u' . ($k !== '' ? '&k=' . $k : ''), true, 302);

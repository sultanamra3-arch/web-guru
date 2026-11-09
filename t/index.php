<?php
// Alamat pendek untuk siswa: /t/?k=KODE → halaman tugas online
$k = preg_replace('/[^A-Za-z0-9]/', '', (string)($_GET['k'] ?? ''));
header('Location: ../?p=t' . ($k !== '' ? '&k=' . $k : ''), true, 302);

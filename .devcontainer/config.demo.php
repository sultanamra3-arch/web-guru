<?php
/* Disalin ke config.php (folder utama) oleh siapkan.sh. Konfigurasi khusus uji coba di Codespaces (database contoh, bukan data sekolah). */
$c = require __DIR__ . '/config.contoh.php';
$c['slims_path'] = '';
$c['db'] = ['host' => '127.0.0.1', 'port' => 3306, 'database' => 'siguru_demo', 'username' => 'demo', 'password' => 'demo'];
$c['ai_kunci'] = '';
$c['kepala_sekolah'] = 'Kepala Sekolah (contoh)';
$c['nip_kepala_sekolah'] = '';
return $c;

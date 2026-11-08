<?php
/*
 * Pengaturan SiGuru. Salin berkas ini menjadi config.php di server bila perlu mengubah sesuatu.
 * (config.php tidak disimpan di GitHub.)
 */
return [
    // Folder SLiMS (koneksi database dibaca otomatis dari config/database.php)
    'slims_path' => '/home/bzydmpxm/public_html',

    // Isi HANYA jika pembacaan otomatis gagal
    'db' => [
        'host' => '', 'port' => 3306, 'database' => '', 'username' => '', 'password' => '',
    ],

    // Kunci AI dipakai bersama dengan SiPustaka (tidak disimpan di GitHub)
    'ai_kunci' => '/home/bzydmpxm/kelola.otomasismpn6keritang.my.id/data/ai-kunci.php',

    // Identitas sekolah (untuk kop, modul ajar, sertifikat)
    'sekolah' => 'SMP Negeri 6 Keritang',
    'kota' => 'Keritang',
    'kepala_sekolah' => '',
    'nip_kepala_sekolah' => '',
    'tahun_ajaran' => '2026/2027',
];

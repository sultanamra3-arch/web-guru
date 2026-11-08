<?php
/* Tabel SiGuru (dibuat otomatis saat pertama dijalankan; tabel SLiMS tidak diubah). */
declare(strict_types=1);

const SKEMA_VERSI = 1;

function pastikan_skema(): void
{
    $f = ai_folder_data() . '/skema-' . SKEMA_VERSI . '.ok';
    if (is_file($f)) return;
    $sql = [
        "CREATE TABLE IF NOT EXISTS guru_akun (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            nama VARCHAR(120) NOT NULL,
            nip VARCHAR(30) NULL,
            mapel VARCHAR(120) NULL,
            sandi VARCHAR(255) NOT NULL,
            peran VARCHAR(10) NOT NULL DEFAULT 'guru',
            aktif TINYINT NOT NULL DEFAULT 1,
            dibuat DATETIME NULL,
            masuk_terakhir DATETIME NULL
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_jadwal (
            id INT AUTO_INCREMENT PRIMARY KEY,
            guru_id INT NOT NULL,
            hari TINYINT NOT NULL,
            jam_ke TINYINT NOT NULL DEFAULT 1,
            mulai TIME NULL,
            selesai TIME NULL,
            kelas VARCHAR(50) NOT NULL,
            mapel VARCHAR(120) NOT NULL,
            ruang VARCHAR(50) NULL,
            KEY (guru_id, hari)
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_absensi (
            id INT AUTO_INCREMENT PRIMARY KEY,
            guru_id INT NOT NULL,
            kelas VARCHAR(50) NOT NULL,
            tanggal DATE NOT NULL,
            mapel VARCHAR(120) NOT NULL DEFAULT '',
            member_id VARCHAR(20) NOT NULL,
            status CHAR(1) NOT NULL DEFAULT 'H',
            ket VARCHAR(200) NULL,
            diubah DATETIME NULL,
            UNIQUE KEY unik (guru_id, kelas, tanggal, mapel, member_id),
            KEY (kelas, tanggal)
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_penilaian (
            id INT AUTO_INCREMENT PRIMARY KEY,
            guru_id INT NOT NULL,
            kelas VARCHAR(50) NOT NULL,
            mapel VARCHAR(120) NOT NULL,
            judul VARCHAR(200) NOT NULL,
            jenis VARCHAR(30) NOT NULL DEFAULT 'Tugas',
            tanggal DATE NULL,
            kktp DECIMAL(5,2) NOT NULL DEFAULT 75,
            dibuat DATETIME NULL,
            KEY (guru_id, kelas)
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_nilai (
            penilaian_id INT NOT NULL,
            member_id VARCHAR(20) NOT NULL,
            nilai DECIMAL(5,2) NULL,
            catatan VARCHAR(255) NULL,
            PRIMARY KEY (penilaian_id, member_id)
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_soal (
            id INT AUTO_INCREMENT PRIMARY KEY,
            guru_id INT NOT NULL,
            mapel VARCHAR(120) NOT NULL,
            kelas VARCHAR(20) NULL,
            topik VARCHAR(200) NULL,
            tipe VARCHAR(10) NOT NULL DEFAULT 'pg',
            tingkat VARCHAR(10) NOT NULL DEFAULT 'sedang',
            level VARCHAR(5) NULL,
            pertanyaan TEXT NOT NULL,
            opsi TEXT NULL,
            kunci TEXT NULL,
            pembahasan TEXT NULL,
            berbagi TINYINT NOT NULL DEFAULT 0,
            dibuat DATETIME NULL,
            KEY (guru_id), KEY (mapel)
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_dokumen (
            id INT AUTO_INCREMENT PRIMARY KEY,
            guru_id INT NOT NULL,
            jenis VARCHAR(20) NOT NULL,
            judul VARCHAR(255) NOT NULL,
            mapel VARCHAR(120) NULL,
            kelas VARCHAR(20) NULL,
            isi LONGTEXT NULL,
            dibuat DATETIME NULL,
            diubah DATETIME NULL,
            KEY (guru_id, jenis)
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_sertifikat (
            id INT AUTO_INCREMENT PRIMARY KEY,
            guru_id INT NOT NULL,
            judul VARCHAR(150) NOT NULL,
            sebutan VARCHAR(150) NULL,
            isi TEXT NULL,
            tanggal DATE NULL,
            kota VARCHAR(80) NULL,
            ttd1_nama VARCHAR(120) NULL, ttd1_jabatan VARCHAR(120) NULL, ttd1_nip VARCHAR(40) NULL,
            ttd2_nama VARCHAR(120) NULL, ttd2_jabatan VARCHAR(120) NULL, ttd2_nip VARCHAR(40) NULL,
            format_nomor VARCHAR(120) NULL,
            warna VARCHAR(20) NULL,
            dibuat DATETIME NULL
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_sertifikat_penerima (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sertifikat_id INT NOT NULL,
            urut INT NOT NULL DEFAULT 1,
            nama VARCHAR(150) NOT NULL,
            keterangan VARCHAR(255) NULL,
            KEY (sertifikat_id)
        ) DEFAULT CHARSET=utf8mb4",
    ];
    foreach ($sql as $q) db()->exec($q);
    @file_put_contents($f, date('c'));
}

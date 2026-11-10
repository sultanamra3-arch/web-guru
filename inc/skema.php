<?php
/* Tabel SiGuru (dibuat otomatis saat pertama dijalankan; tabel SLiMS tidak diubah). */
declare(strict_types=1);

const SKEMA_VERSI = 9;

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
        "CREATE TABLE IF NOT EXISTS guru_koreksi_kunci (
            penilaian_id INT NOT NULL PRIMARY KEY,
            format_lj VARCHAR(10) NOT NULL DEFAULT 'otomatis',
            teks MEDIUMTEXT NULL,
            diubah DATETIME NULL
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_koreksi (
            penilaian_id INT NOT NULL,
            member_id VARCHAR(20) NOT NULL,
            detail MEDIUMTEXT NULL,
            nilai DECIMAL(5,2) NULL,
            oleh VARCHAR(200) NULL,
            dibuat DATETIME NULL,
            PRIMARY KEY (penilaian_id, member_id)
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_setelan (
            k VARCHAR(50) NOT NULL PRIMARY KEY,
            v TEXT NULL
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_siswa (
            kode VARCHAR(20) NOT NULL PRIMARY KEY,
            guru_id INT NOT NULL DEFAULT 0,
            nisn VARCHAR(20) NULL,
            nama VARCHAR(120) NOT NULL,
            jk CHAR(1) NULL,
            status VARCHAR(10) NOT NULL DEFAULT 'aktif',
            dibuat DATETIME NULL,
            diubah DATETIME NULL,
            KEY (guru_id), KEY (nisn), KEY (nama)
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_kelas (
            tahun VARCHAR(9) NOT NULL,
            guru_id INT NOT NULL DEFAULT 0,
            nama VARCHAR(50) NOT NULL,
            wali_id INT NULL,
            PRIMARY KEY (tahun, guru_id, nama)
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_kelas_siswa (
            tahun VARCHAR(9) NOT NULL,
            kode VARCHAR(20) NOT NULL,
            kelas VARCHAR(50) NOT NULL,
            absen SMALLINT NULL,
            PRIMARY KEY (tahun, kode),
            KEY (tahun, kelas)
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_ujian (
            id INT AUTO_INCREMENT PRIMARY KEY,
            guru_id INT NOT NULL,
            paket_id INT NULL,
            judul VARCHAR(200) NOT NULL,
            mapel VARCHAR(120) NULL,
            kelas VARCHAR(50) NOT NULL,
            tahun VARCHAR(9) NULL,
            kode VARCHAR(10) NOT NULL UNIQUE,
            rahasia VARCHAR(40) NOT NULL,
            token_menit SMALLINT NOT NULL DEFAULT 10,
            mulai DATETIME NULL,
            selesai DATETIME NULL,
            durasi SMALLINT NOT NULL DEFAULT 60,
            acak_soal TINYINT NOT NULL DEFAULT 1,
            acak_opsi TINYINT NOT NULL DEFAULT 1,
            tampil_nilai TINYINT NOT NULL DEFAULT 0,
            status VARCHAR(10) NOT NULL DEFAULT 'draf',
            soal LONGTEXT NULL,
            penilaian_id INT NULL,
            dibuat DATETIME NULL,
            KEY (guru_id)
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_ujian_peserta (
            ujian_id INT NOT NULL,
            kode VARCHAR(20) NOT NULL,
            perangkat VARCHAR(64) NULL,
            mulai DATETIME NULL,
            batas DATETIME NULL,
            selesai DATETIME NULL,
            urutan TEXT NULL,
            jawaban MEDIUMTEXT NULL,
            ragu TEXT NULL,
            nomor SMALLINT NOT NULL DEFAULT 0,
            keluar SMALLINT NOT NULL DEFAULT 0,
            terakhir DATETIME NULL,
            skor DECIMAL(7,2) NULL,
            nilai DECIMAL(5,2) NULL,
            nilai_uraian TEXT NULL,
            perlu_koreksi TINYINT NOT NULL DEFAULT 0,
            PRIMARY KEY (ujian_id, kode)
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
        "CREATE TABLE IF NOT EXISTS guru_tugas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            guru_id INT NOT NULL,
            kelas VARCHAR(50) NOT NULL,
            tahun VARCHAR(9) NULL,
            mapel VARCHAR(120) NULL,
            judul VARCHAR(200) NOT NULL,
            petunjuk TEXT NULL,
            lampiran VARCHAR(40) NULL,
            kode VARCHAR(10) NOT NULL UNIQUE,
            batas DATETIME NULL,
            terima_telat TINYINT NOT NULL DEFAULT 1,
            maks_foto TINYINT NOT NULL DEFAULT 5,
            tampil_tertib TINYINT NOT NULL DEFAULT 1,
            tata_tertib TEXT NULL,
            kunci TEXT NULL,
            status VARCHAR(10) NOT NULL DEFAULT 'buka',
            penilaian_id INT NULL,
            nilai_dikirim DATETIME NULL,
            dibuat DATETIME NULL,
            KEY (guru_id)
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_tugas_kirim (
            tugas_id INT NOT NULL,
            kode VARCHAR(20) NOT NULL,
            perangkat VARCHAR(64) NULL,
            foto TEXT NULL,
            teks TEXT NULL,
            dikirim DATETIME NULL,
            telat TINYINT NOT NULL DEFAULT 0,
            kali SMALLINT NOT NULL DEFAULT 0,
            status VARCHAR(12) NOT NULL DEFAULT 'kirim',
            nilai DECIMAL(5,2) NULL,
            komentar TEXT NULL,
            saran_ai TEXT NULL,
            dinilai DATETIME NULL,
            PRIMARY KEY (tugas_id, kode)
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_kuis (
            id INT AUTO_INCREMENT PRIMARY KEY,
            guru_id INT NOT NULL,
            paket_id INT NULL,
            kelas VARCHAR(50) NOT NULL,
            tahun VARCHAR(9) NULL,
            judul VARCHAR(200) NOT NULL,
            kode VARCHAR(10) NOT NULL UNIQUE,
            soal LONGTEXT NULL,
            detik SMALLINT NOT NULL DEFAULT 20,
            status VARCHAR(10) NOT NULL DEFAULT 'lobi',
            nomor SMALLINT NOT NULL DEFAULT -1,
            mulai_ms BIGINT NULL,
            penilaian_id INT NULL,
            dibuat DATETIME NULL,
            KEY (guru_id)
        ) DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS guru_kuis_peserta (
            kuis_id INT NOT NULL,
            kode VARCHAR(20) NOT NULL,
            perangkat VARCHAR(64) NULL,
            poin INT NOT NULL DEFAULT 0,
            benar SMALLINT NOT NULL DEFAULT 0,
            jawaban TEXT NULL,
            gabung DATETIME NULL,
            terakhir DATETIME NULL,
            PRIMARY KEY (kuis_id, kode)
        ) DEFAULT CHARSET=utf8mb4",
    ];
    foreach ($sql as $q) db()->exec($q);
    skema_migrasi_4();
    skema_migrasi_5();
    skema_migrasi_8();
    skema_migrasi_9();
    @file_put_contents($f, date('c'));
}

function kolom_ada(string $tabel, string $kolom): bool
{
    return (bool)nilai('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$tabel, $kolom]);
}

/** v4: data siswa & kelas milik masing-masing guru (data lama diberikan ke admin pertama). */
function skema_migrasi_4(): void
{
    $pemilik = (int)(nilai("SELECT MIN(id) FROM guru_akun WHERE peran = 'admin'") ?: 0);
    if (!kolom_ada('guru_siswa', 'guru_id')) {
        db()->exec('ALTER TABLE guru_siswa ADD COLUMN guru_id INT NOT NULL DEFAULT 0 AFTER kode, ADD KEY (guru_id)');
    }
    if (!kolom_ada('guru_kelas', 'guru_id')) {
        db()->exec('ALTER TABLE guru_kelas ADD COLUMN guru_id INT NOT NULL DEFAULT 0 AFTER tahun, DROP PRIMARY KEY, ADD PRIMARY KEY (tahun, guru_id, nama)');
    }
    if ($pemilik) {
        q('UPDATE guru_siswa SET guru_id = ? WHERE guru_id = 0', [$pemilik]);
        q('UPDATE guru_kelas SET guru_id = ? WHERE guru_id = 0', [$pemilik]);
    }
}

/** v5: soal bergambar, mode latihan, pengaman ujian, tata tertib, hasil & pembahasan untuk siswa. */
function skema_migrasi_5(): void
{
    $kolom = [
        'guru_soal' => ['gambar' => 'VARCHAR(40) NULL', 'gambar_opsi' => 'TEXT NULL'],
        'guru_ujian' => ['mode' => "VARCHAR(10) NOT NULL DEFAULT 'ujian'", 'pengaman' => 'TINYINT NOT NULL DEFAULT 0',
            'batas_keluar' => 'TINYINT NOT NULL DEFAULT 3', 'tampil_tertib' => 'TINYINT NOT NULL DEFAULT 1',
            'tata_tertib' => 'TEXT NULL', 'tampil_hasil' => 'TINYINT NOT NULL DEFAULT 0'],
        'guru_ujian_peserta' => ['alasan' => 'VARCHAR(20) NULL', 'dikunci' => 'SMALLINT NOT NULL DEFAULT 0',
            'percobaan' => 'SMALLINT NOT NULL DEFAULT 1', 'nilai_terbaik' => 'DECIMAL(5,2) NULL'],
    ];
    foreach ($kolom as $t => $daftar) {
        foreach ($daftar as $k => $def) if (!kolom_ada($t, $k)) db()->exec("ALTER TABLE $t ADD COLUMN $k $def");
    }
    // perbaiki data lama: pilihan kelas dulu terkirim sebagai "Kelas 8"
    db()->exec("UPDATE guru_soal SET kelas = TRIM(SUBSTRING(kelas, 7)) WHERE kelas LIKE 'Kelas %'");
    db()->exec("UPDATE guru_dokumen SET kelas = TRIM(SUBSTRING(kelas, 7)) WHERE kelas LIKE 'Kelas %' AND jenis IN ('modul', 'lkpd', 'presentasi')");
}

/** v8: kuis cepat mode mandiri (siswa mengerjakan sendiri-sendiri, soal & pilihan diacak per siswa). */
function skema_migrasi_8(): void
{
    $kolom = [
        'guru_kuis' => ['mode' => "VARCHAR(10) NOT NULL DEFAULT 'bersama'", 'selesai_ms' => 'BIGINT NULL'],
        'guru_kuis_peserta' => ['urutan' => 'TEXT NULL', 'pos' => 'SMALLINT NOT NULL DEFAULT 0', 'soal_ms' => 'BIGINT NULL', 'selesai_ms' => 'BIGINT NULL'],
    ];
    foreach ($kolom as $t => $daftar) {
        foreach ($daftar as $k => $def) if (!kolom_ada($t, $k)) db()->exec("ALTER TABLE $t ADD COLUMN $k $def");
    }
}

/** v9: latihan remedial & pengayaan (khusus siswa tertentu, terhubung ke ujian asal). */
function skema_migrasi_9(): void
{
    $kolom = [
        'guru_ujian' => ['khusus' => 'TEXT NULL', 'induk_id' => 'INT NULL', 'tindak' => 'VARCHAR(10) NULL',
            'kktp' => 'DECIMAL(5,2) NULL', 'batasi_kktp' => 'TINYINT NOT NULL DEFAULT 1'],
    ];
    foreach ($kolom as $t => $daftar) {
        foreach ($daftar as $k => $def) if (!kolom_ada($t, $k)) db()->exec("ALTER TABLE $t ADD COLUMN $k $def");
    }
}

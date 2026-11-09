/*M!999999\- enable the sandbox mode */ 

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `guru_absensi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_absensi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guru_id` int(11) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `tanggal` date NOT NULL,
  `mapel` varchar(120) NOT NULL DEFAULT '',
  `member_id` varchar(20) NOT NULL,
  `status` char(1) NOT NULL DEFAULT 'H',
  `ket` varchar(200) DEFAULT NULL,
  `diubah` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unik` (`guru_id`,`kelas`,`tanggal`,`mapel`,`member_id`),
  KEY `kelas` (`kelas`,`tanggal`)
) ENGINE=InnoDB AUTO_INCREMENT=101 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_absensi` WRITE;
/*!40000 ALTER TABLE `guru_absensi` DISABLE KEYS */;
INSERT INTO `guru_absensi` VALUES
(1,1,'VIII A','2026-10-05','Informatika','9000000011','H','','2026-11-08 07:19:47'),
(2,1,'VIII A','2026-10-05','Informatika','9000000012','S','demam','2026-11-08 07:19:47'),
(3,1,'VIII A','2026-10-05','Informatika','9000000013','H','','2026-11-08 07:19:47'),
(4,1,'VIII A','2026-10-05','Informatika','9000000014','H','','2026-11-08 07:19:47'),
(5,1,'VIII A','2026-10-05','Informatika','9000000015','H','','2026-11-08 07:19:47'),
(6,1,'VIII A','2026-10-05','Informatika','9000000016','H','','2026-11-08 07:19:47'),
(7,1,'VIII A','2026-10-05','Informatika','9000000017','H','','2026-11-08 07:19:47'),
(8,1,'VIII A','2026-10-05','Informatika','9000000018','A','','2026-11-08 07:19:47'),
(9,1,'VIII A','2026-10-05','Informatika','9000000019','H','','2026-11-08 07:19:47'),
(10,1,'VIII A','2026-10-05','Informatika','9000000020','H','','2026-11-08 07:19:47'),
(11,1,'VIII A','2026-10-07','Informatika','9000000011','H','','2026-11-08 07:19:47'),
(12,1,'VIII A','2026-10-07','Informatika','9000000012','H','','2026-11-08 07:19:47'),
(13,1,'VIII A','2026-10-07','Informatika','9000000013','A','','2026-11-08 07:19:47'),
(14,1,'VIII A','2026-10-07','Informatika','9000000014','H','','2026-11-08 07:19:47'),
(15,1,'VIII A','2026-10-07','Informatika','9000000015','A','','2026-11-08 07:19:47'),
(16,1,'VIII A','2026-10-07','Informatika','9000000016','H','','2026-11-08 07:19:47'),
(17,1,'VIII A','2026-10-07','Informatika','9000000017','H','','2026-11-08 07:19:47'),
(18,1,'VIII A','2026-10-07','Informatika','9000000018','H','','2026-11-08 07:19:47'),
(19,1,'VIII A','2026-10-07','Informatika','9000000019','H','','2026-11-08 07:19:47'),
(20,1,'VIII A','2026-10-07','Informatika','9000000020','H','','2026-11-08 07:19:47'),
(21,1,'VIII A','2026-10-12','Informatika','9000000011','H','','2026-11-08 07:19:47'),
(22,1,'VIII A','2026-10-12','Informatika','9000000012','H','','2026-11-08 07:19:47'),
(23,1,'VIII A','2026-10-12','Informatika','9000000013','H','','2026-11-08 07:19:47'),
(24,1,'VIII A','2026-10-12','Informatika','9000000014','H','','2026-11-08 07:19:47'),
(25,1,'VIII A','2026-10-12','Informatika','9000000015','H','','2026-11-08 07:19:47'),
(26,1,'VIII A','2026-10-12','Informatika','9000000016','H','','2026-11-08 07:19:47'),
(27,1,'VIII A','2026-10-12','Informatika','9000000017','H','','2026-11-08 07:19:47'),
(28,1,'VIII A','2026-10-12','Informatika','9000000018','H','','2026-11-08 07:19:47'),
(29,1,'VIII A','2026-10-12','Informatika','9000000019','H','','2026-11-08 07:19:47'),
(30,1,'VIII A','2026-10-12','Informatika','9000000020','H','','2026-11-08 07:19:47'),
(31,1,'VIII A','2026-10-14','Informatika','9000000011','H','','2026-11-08 07:19:47'),
(32,1,'VIII A','2026-10-14','Informatika','9000000012','H','','2026-11-08 07:19:47'),
(33,1,'VIII A','2026-10-14','Informatika','9000000013','H','','2026-11-08 07:19:47'),
(34,1,'VIII A','2026-10-14','Informatika','9000000014','H','','2026-11-08 07:19:47'),
(35,1,'VIII A','2026-10-14','Informatika','9000000015','H','','2026-11-08 07:19:47'),
(36,1,'VIII A','2026-10-14','Informatika','9000000016','H','','2026-11-08 07:19:47'),
(37,1,'VIII A','2026-10-14','Informatika','9000000017','H','','2026-11-08 07:19:47'),
(38,1,'VIII A','2026-10-14','Informatika','9000000018','S','demam','2026-11-08 07:19:47'),
(39,1,'VIII A','2026-10-14','Informatika','9000000019','H','','2026-11-08 07:19:47'),
(40,1,'VIII A','2026-10-14','Informatika','9000000020','H','','2026-11-08 07:19:47'),
(41,1,'VIII A','2026-10-19','Informatika','9000000011','H','','2026-11-08 07:19:47'),
(42,1,'VIII A','2026-10-19','Informatika','9000000012','H','','2026-11-08 07:19:47'),
(43,1,'VIII A','2026-10-19','Informatika','9000000013','H','','2026-11-08 07:19:47'),
(44,1,'VIII A','2026-10-19','Informatika','9000000014','H','','2026-11-08 07:19:47'),
(45,1,'VIII A','2026-10-19','Informatika','9000000015','H','','2026-11-08 07:19:47'),
(46,1,'VIII A','2026-10-19','Informatika','9000000016','H','','2026-11-08 07:19:47'),
(47,1,'VIII A','2026-10-19','Informatika','9000000017','H','','2026-11-08 07:19:47'),
(48,1,'VIII A','2026-10-19','Informatika','9000000018','H','','2026-11-08 07:19:47'),
(49,1,'VIII A','2026-10-19','Informatika','9000000019','H','','2026-11-08 07:19:47'),
(50,1,'VIII A','2026-10-19','Informatika','9000000020','H','','2026-11-08 07:19:47'),
(51,1,'VIII A','2026-10-21','Informatika','9000000011','H','','2026-11-08 07:19:47'),
(52,1,'VIII A','2026-10-21','Informatika','9000000012','A','','2026-11-08 07:19:47'),
(53,1,'VIII A','2026-10-21','Informatika','9000000013','S','demam','2026-11-08 07:19:47'),
(54,1,'VIII A','2026-10-21','Informatika','9000000014','H','','2026-11-08 07:19:47'),
(55,1,'VIII A','2026-10-21','Informatika','9000000015','H','','2026-11-08 07:19:47'),
(56,1,'VIII A','2026-10-21','Informatika','9000000016','H','','2026-11-08 07:19:47'),
(57,1,'VIII A','2026-10-21','Informatika','9000000017','H','','2026-11-08 07:19:47'),
(58,1,'VIII A','2026-10-21','Informatika','9000000018','H','','2026-11-08 07:19:47'),
(59,1,'VIII A','2026-10-21','Informatika','9000000019','H','','2026-11-08 07:19:47'),
(60,1,'VIII A','2026-10-21','Informatika','9000000020','S','demam','2026-11-08 07:19:47'),
(61,1,'VIII A','2026-10-26','Informatika','9000000011','H','','2026-11-08 07:19:47'),
(62,1,'VIII A','2026-10-26','Informatika','9000000012','H','','2026-11-08 07:19:47'),
(63,1,'VIII A','2026-10-26','Informatika','9000000013','H','','2026-11-08 07:19:47'),
(64,1,'VIII A','2026-10-26','Informatika','9000000014','H','','2026-11-08 07:19:47'),
(65,1,'VIII A','2026-10-26','Informatika','9000000015','H','','2026-11-08 07:19:47'),
(66,1,'VIII A','2026-10-26','Informatika','9000000016','H','','2026-11-08 07:19:47'),
(67,1,'VIII A','2026-10-26','Informatika','9000000017','H','','2026-11-08 07:19:47'),
(68,1,'VIII A','2026-10-26','Informatika','9000000018','H','','2026-11-08 07:19:47'),
(69,1,'VIII A','2026-10-26','Informatika','9000000019','H','','2026-11-08 07:19:47'),
(70,1,'VIII A','2026-10-26','Informatika','9000000020','S','demam','2026-11-08 07:19:47'),
(71,1,'VIII A','2026-10-28','Informatika','9000000011','H','','2026-11-08 07:19:47'),
(72,1,'VIII A','2026-10-28','Informatika','9000000012','H','','2026-11-08 07:19:47'),
(73,1,'VIII A','2026-10-28','Informatika','9000000013','H','','2026-11-08 07:19:47'),
(74,1,'VIII A','2026-10-28','Informatika','9000000014','I','acara keluarga','2026-11-08 07:19:47'),
(75,1,'VIII A','2026-10-28','Informatika','9000000015','H','','2026-11-08 07:19:47'),
(76,1,'VIII A','2026-10-28','Informatika','9000000016','H','','2026-11-08 07:19:47'),
(77,1,'VIII A','2026-10-28','Informatika','9000000017','H','','2026-11-08 07:19:47'),
(78,1,'VIII A','2026-10-28','Informatika','9000000018','H','','2026-11-08 07:19:47'),
(79,1,'VIII A','2026-10-28','Informatika','9000000019','H','','2026-11-08 07:19:47'),
(80,1,'VIII A','2026-10-28','Informatika','9000000020','H','','2026-11-08 07:19:47'),
(81,1,'VIII A','2026-11-02','Informatika','9000000011','H','','2026-11-08 07:19:47'),
(82,1,'VIII A','2026-11-02','Informatika','9000000012','H','','2026-11-08 07:19:47'),
(83,1,'VIII A','2026-11-02','Informatika','9000000013','H','','2026-11-08 07:19:47'),
(84,1,'VIII A','2026-11-02','Informatika','9000000014','H','','2026-11-08 07:19:47'),
(85,1,'VIII A','2026-11-02','Informatika','9000000015','H','','2026-11-08 07:19:47'),
(86,1,'VIII A','2026-11-02','Informatika','9000000016','H','','2026-11-08 07:19:47'),
(87,1,'VIII A','2026-11-02','Informatika','9000000017','H','','2026-11-08 07:19:47'),
(88,1,'VIII A','2026-11-02','Informatika','9000000018','H','','2026-11-08 07:19:47'),
(89,1,'VIII A','2026-11-02','Informatika','9000000019','H','','2026-11-08 07:19:47'),
(90,1,'VIII A','2026-11-02','Informatika','9000000020','H','','2026-11-08 07:19:47'),
(91,1,'VIII A','2026-11-04','Informatika','9000000011','H','','2026-11-08 07:19:47'),
(92,1,'VIII A','2026-11-04','Informatika','9000000012','H','','2026-11-08 07:19:47'),
(93,1,'VIII A','2026-11-04','Informatika','9000000013','H','','2026-11-08 07:19:47'),
(94,1,'VIII A','2026-11-04','Informatika','9000000014','H','','2026-11-08 07:19:47'),
(95,1,'VIII A','2026-11-04','Informatika','9000000015','H','','2026-11-08 07:19:47'),
(96,1,'VIII A','2026-11-04','Informatika','9000000016','H','','2026-11-08 07:19:47'),
(97,1,'VIII A','2026-11-04','Informatika','9000000017','H','','2026-11-08 07:19:47'),
(98,1,'VIII A','2026-11-04','Informatika','9000000018','H','','2026-11-08 07:19:47'),
(99,1,'VIII A','2026-11-04','Informatika','9000000019','S','demam','2026-11-08 07:19:47'),
(100,1,'VIII A','2026-11-04','Informatika','9000000020','S','demam','2026-11-08 07:19:47');
/*!40000 ALTER TABLE `guru_absensi` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_akun`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_akun` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `nama` varchar(120) NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `mapel` varchar(120) DEFAULT NULL,
  `sandi` varchar(255) NOT NULL,
  `peran` varchar(10) NOT NULL DEFAULT 'guru',
  `aktif` tinyint(4) NOT NULL DEFAULT 1,
  `dibuat` datetime DEFAULT NULL,
  `masuk_terakhir` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_akun` WRITE;
/*!40000 ALTER TABLE `guru_akun` DISABLE KEYS */;
INSERT INTO `guru_akun` VALUES
(1,'admin','Budi Santoso, S.Pd.','198501012010011001','Informatika','$2y$10$VXXiSTis5cKmmcm00ypfZuC6J7Di1EXsu3aVjmDyCBFH5H0DC1VpC','admin',1,'2026-11-08 07:19:47','2026-11-09 13:07:38'),
(2,'guru','Rina Marlina, S.Pd.','','Bahasa Indonesia','$2y$10$Yw3mhbCfjooqB0Du1toAHOUjFDbN9QrzkiWwGmppHqYXAPc4C3aKS','guru',1,'2026-11-08 07:19:47','2026-11-09 12:39:02');
/*!40000 ALTER TABLE `guru_akun` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_dokumen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_dokumen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guru_id` int(11) NOT NULL,
  `jenis` varchar(20) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `mapel` varchar(120) DEFAULT NULL,
  `kelas` varchar(20) DEFAULT NULL,
  `isi` longtext DEFAULT NULL,
  `dibuat` datetime DEFAULT NULL,
  `diubah` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `guru_id` (`guru_id`,`jenis`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_dokumen` WRITE;
/*!40000 ALTER TABLE `guru_dokumen` DISABLE KEYS */;
INSERT INTO `guru_dokumen` VALUES
(1,1,'paket','Ulangan Harian Informatika Bab 1','Informatika','VIII','{\"ids\": [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], \"mapel\": \"Informatika\", \"kelas\": \"VIII\", \"waktu\": \"60 menit\", \"jenis_ujian\": \"Ulangan Harian\", \"petunjuk\": \"Berdoalah sebelum mengerjakan. Kerjakan soal yang mudah terlebih dahulu.\", \"acak\": 0}','2026-11-08 07:19:47','2026-11-08 07:19:47'),
(2,1,'modul','Modul Ajar: Teks Deskripsi Tempat Wisata Daerah','Bahasa Indonesia','7','# Modul Ajar: Teks Deskripsi Tempat Wisata Daerah\n\n## A. Identitas\n\n| Komponen | Keterangan |\n| :--- | :--- |\n| **Penyusun** | Budi Santoso, S.Pd. |\n| **Satuan Pendidikan** | SMP Negeri 6 Keritang |\n| **Mata Pelajaran** | Bahasa Indonesia |\n| **Fase / Kelas / Semester** | Fase D / Kelas VII (Tujuh) / Semester 1 |\n| **Materi Pokok** | Teks Deskripsi Tempat Wisata Daerah |\n| **Alokasi Waktu** | 2 JP Ã— 40 menit (1 Pertemuan / Total 80 menit) |\n| **Tahun Ajaran** | 2026/2027 |\n\n---\n\n## B. Identifikasi\n\n### 1. Peserta Didik\n*   **Pengetahuan Awal:** Peserta didik umumnya telah mengenali kata sifat sederhana dan dapat menyebutkan tempat-tempat yang ada di sekitar tempat tinggalnya, namun belum terbiasa menguraikan rincian objek menggunakan pencitraan pancaindra secara terstruktur.\n*   **Minat:** Menyukai cerita tentang lingkungan sekitar, media visual (gambar/foto tempat wisata lokal), serta aktivitas kelompok bertema petualangan alam pesisir dan perkebunan.\n*   **Kebutuhan Belajar:** \n    *   *Visual:* Membutuhkan gambar atau foto objek wisata lokal Keritang/Indragiri Hilir.\n    *   *Auditori:* Membutuhkan penjelasan lisan yang disertai diskusi dialogis dan pendengaran deskripsi lisan.\n    *   *Kinestetik:* Membutuhkan aktivitas bergerak seperti mengamati objek langsung di sekitar lingkungan sekolah atau melakukan teknik *gallery walk*.\n\n### 2. Materi Pelajaran\n*   **Jenis Pengetahuan:** Konseptual (struktur teks deskripsi: identifikasi, deskripsi bagian, simpulan/kesan) dan Prosedural (langkah-langkah merinci objek wisata menggunakan frasa pancaindra dan kata konkret).\n*   **Relevansi:** Sangat relevan dengan upaya mengenalkan dan mempromosikan potensi daerah setempat (seperti kawasan ekowisata perairan, hamparan perkebunan kelapa Keritang, atau dermaga sungai).\n*   **Tingkat Kesulitan:** Sedang (tantangan utama pada pemilihan kata konkret yang spesifik dan pelibatan pancaindra dalam tulisan).\n\n### 3. Dimensi Profil Lulusan\n*   **Penalaran Kritis:** Menganalisis ketepatan penggunaan kata konkret dan penginderaan dalam menggambarkan objek.\n*   **Kreativitas:** Menyusun teks deskripsi yang memikat dengan variasi kata yang kaya untuk mempromosikan wisata lokal.\n*   **Kolaborasi:** Bekerja sama dalam kelompok untuk mengidentifikasi dan menyusun rincian deskripsi objek.\n\n---\n\n## C. Desain Pembelajaran\n\n*   **Capaian Pembelajaran:** *(ringkasan, cocokkan dengan dokumen CP resmi terbaru)*: Peserta didik mampu menulis teks deskripsi dengan menyajikan gagasan, pikiran, pandangan, atau pesan tertulis secara logis, kritis, dan kreatif untuk menyampaikan informasi tentang objek wisata daerah dengan memperhatikan struktur dan kaidah kebahasaan.\n*   **Lintas Disiplin Ilmu:** Ilmu Pengetahuan Sosial (Geografi lokal & Pariwisata daerah) dan Ilmu Pengetahuan Alam (Ekosistem lahan basah/perkebunan kelapa pesisir).\n*   **Tujuan Pembelajaran:**\n    1. Melalui kegiatan analisis contoh teks, peserta didik dapat mengidentifikasi struktur teks deskripsi (identifikasi, deskripsi bagian, dan simpulan) dengan tepat.\n    2. Melalui kerja kelompok kontekstual, peserta didik dapat merinci ciri fisik dan suasana objek wisata daerah menggunakan minimal 3 pancaindra secara tepat.\n    3. Peserta didik dapat menyusun draf teks deskripsi singkat tentang tempat wisata di lingkungan Indragiri Hilir secara kreatif dan terstruktur.\n*   **Topik Kontekstual:** \"Pesona Wisata Alam dan Perkebunan Kelapa Negeri Sri Gemilang (Keritang - Indragiri Hilir)\".\n*   **Praktik Pedagogis:** Model *Problem-Based Learning* (PBL) berbasis Pembelajaran Mendalam (*Deep Learning*). Alasan: Memandu peserta didik memecahkan masalah nyata tentang kurangnya media promosi tertulis untuk keindahan wisata lokal.\n*   **Kemitraan Pembelajaran:** Teman sebaya (tutor sebaya), komunitas lingkungan sekolah, serta orang tua/masyarakat lokal sebagai sumber informasi tentang cerita unik objek wisata setempat.\n*   **Lingkungan Pembelajaran:** Ruang kelas yang ditata secara fleksibel untuk kerja kelompok, serta area halaman sekolah sebagai objek latihan pengamatan langsung.\n*   **Pemanfaatan Digital (dan Alternatif Non-Digital):**\n    *   *Opsi Digital:* Tayangan foto/video pendek destinasi wisata Indragiri Hilir (misalnya: kawasan pariwisata perairan sungai, pemandangan hamparan kebun kelapa).\n    *   *Opsi Non-Digital (Tanpa Internet):* Lembar cetak berisikan foto/gambar tempat wisata lokal dan kartu kata (*word cards*) pancaindra.\n\n---\n\n## D. Pengalaman Belajar ( Total: 80 Menit )\n\n### 1. Kegiatan Awal (15 Menit)\n*   **Orientasi & Kesadaran Diri (5 menit):**\n    *   Guru membuka pembelajaran dengan salam, berdoa bersama, dan mengecek kehadiran.\n    *   Guru mengajak peserta didik melakukan latihan bernapas sejenak (teknik STOP) untuk menumbuhkan rasa tenang dan fokus sebelum belajar. **[Berkesadaran]**\n*   **Apersepsi & Keterhubungan (5 menit):**\n    *   Guru memperlihatkan foto/gambar hamparan perkebunan kelapa atau pemandangan tepi sungai di daerah Keritang.\n    *   Guru mengajukan pertanyaan pemantik: *\"Jika ada wisatawan dari luar daerah datang ke Keritang, tempat mana yang paling indah untuk dikunjungi? Bagaimana cara kalian menceritakan keindahannya agar mereka tertarik datang?\"* **[Bermakna]**\n*   **Motivasi & Pemahaman Tujuan (5 menit):**\n    *   Guru menyampaikan tujuan pembelajaran dan manfaat menulis teks deskripsi sebagai sarana mempromosikan potensi daerah Indragiri Hilir.\n    *   Guru membagi peserta didik ke dalam kelompok kecil (4â€“5 orang) secara heterogen. **[Menggembirakan]**\n\n### 2. Kegiatan Inti (50 Menit)\n\n#### A. Memahami / *Deep Understanding* (15 Menit)\n*   Guru membagikan lembar kerja berisi contoh teks deskripsi pendek berjudul *\"Pesona Senja di Tepian Sungai Keritang\"*.\n*   Peserta didik membaca teks tersebut secara bergantian dalam kelompok. **[Bermakna]**\n*   Peserta didik berdiskusi untuk mengidentifikasi bagian struktur teks (identifikasi, deskripsi bagian, simpulan) serta menandai kata-kata yang melibatkan pancaindra (penglihatan, pendengaran, penciuman, perabaan). **[Berkesadaran]**\n*   Guru mengonfirmasi hasil analisis peserta didik dan memberikan penguatan singkat tentang cara kerja kata konkret dan pancaindra dalam mendeskripsikan tempat.\n\n#### B. Mengaplikasi / *Deep Application* (25 Menit)\n*   Guru memberikan tugas kelompok: Setiap kelompok memilih satu topik objek wisata/tempat khas daerah (contoh: *Wisata Kebun Kelapa Keritang*, *Dermaga Perahu Sungai Reteh*, atau *Taman Lingkungan Sekolah*).\n*   **Diferensiasi Proses & Produk:**\n    *   *Kelompok Visual:* Menggunakan bantuan bagan alur gambar untuk menyusun perincian warna, bentuk, dan pemandangan objek.\n    *   *Kelompok Auditori:* Menggali rincian melalui wawancara antaranggota kelompok mengenai suara-suara dan suasana khas objek.\n    *   *Kelompok Kinestetik:* Diizinkan melangkah keluar kelas sebentar ke halaman sekolah untuk mengamati objek nyata secara langsung dan mencatat hasilnya.\n*   Peserta didik berdiskusi menyusun kerangka draf teks deskripsi dengan mengisi peta pikiran (*mind map*) yang berisikan: Nama Tempat, Ciri Fisik, Suasana, dan Kesan. **[Bermakna]**\n*   Peserta didik merangkai kerangka tersebut menjadi satu paragraf deskripsi bagian yang kaya akan kalimat pancaindra secara bersama-sama. **[Menggembirakan]**\n\n#### C. Merefleksi / *Deep Reflection* (10 Menit)\n*   Setiap kelompok bertukar draf tulisan dengan kelompok tetangga (*peer review*).\n*   Peserta didik memberikan umpan balik sederhana menggunakan lembar centang: *\"Apakah tulisan temanmu sudah membuatmu seolah-olah melihat/mendengar objek tersebut?\"* **[Berkesadaran]**\n*   Beberapa perwakilan kelompok membacakan hasil revisi tulisannya dengan nada pembacaan yang menarik. **[Menggembirakan]**\n\n### 3. Kegiatan Penutup (15 Menit)\n*   **Rangkuman & Simpulan (5 menit):**\n    *   Guru bersama peserta didik merangkum poin-poin penting tentang struktur dan pancaindra dalam teks deskripsi.\n*   **Refleksi Diri (5 menit):**\n    *   Peserta didik menuliskan jawaban singkat pada lembar kertas kecil (Refleksi 3-2-1):\n        *   3 hal baru yang dipelajari hari ini.\n        *   2 hal yang paling disukai dari aktivitas kelompok.\n        *   1 pertanyaan yang masih mengganjal. **[Berkesadaran]**\n*   **Apresiasi & Tindak Lanjut (5 menit):**\n    *   Guru memberikan apresiasi atas kerja sama dan kreativitas seluruh kelompok. **[Menggembirakan]**\n    *   Guru menyampaikan rencana pembelajaran pada pertemuan berikutnya.\n    *   Kegiatan ditutup dengan doa bersama dan salam.\n\n---\n\n## E. Asesmen Pembelajaran\n\n### 1. Asesmen Awal (Diagnostik Formatif)\nDilakukan di awal kegiatan untuk mengukur pengetahuan awal peserta didik:\n1. Sebutkan 3 kata sifat yang paling menggambarkan suasana perkebunan kelapa di sore hari!\n2. Manakah kalimat yang membuatmu seolah-olah merasakan suasana tempat: \n   a. \"Kebun itu sangat luas.\"\n   b. \"Angin sepoi-sepoi membawa aroma daun kelapa kering dan suara gemericik air sungai.\"\n3. Sebutkan satu tempat wisata atau tempat menarik yang ada di sekitar Indragiri Hilir yang kamu ketahui!\n\n### 2. Asesmen Proses (Formatif)\n*   **Teknik:** Observasi kinerja kelompok dan penilaian antarteman (*peer assessment*).\n*   **Instrumen:** Lembar observasi keaktifan diskusi, pengisian peta pikiran deskripsi, dan catatan umpan balik draf tulisan.\n\n### 3. Asesmen Akhir (Sumatif)\n*   **Teknik:** Penilaian Produk (Hasil Draf Teks Deskripsi Tempat Wisata Daerah).\n*   **Instrumen:** Rubrik penilaian menulis teks deskripsi.\n\n### 4. Rubrik Penilaian Menulis Teks Deskripsi\n\n| Kriteria Penilaian | Perlu Bimbingan (1) | Cukup (2) | Baik (3) | Sangat Baik (4) |\n| :--- | :--- | :--- | :--- | :--- |\n| **Struktur Teks** | Hanya memuat 1 komponen struktur teks deskripsi. | Memuat 2 komponen struktur teks deskripsi, namun belum runut. | Memuat 3 komponen struktur (identifikasi, deskripsi bagian, simpulan) dengan runut. | Memuat 3 komponen struktur dengan sangat lengkap, runut, dan transisi antarbagian halus. |\n| **Penggunaan Pancaindra & Kata Konkret** | Belum menggunakan kalimat pancaindra dan kata yang digunakan masih sangat umum/abstrak. | Menggunakan 1 jenis pancaindra dan sedikit kata konkret dalam menggambarkan objek. | Menggunakan 2â€“3 jenis pancaindra dan kata konkret yang tepat dalam menggambarkan objek. | Menggunakan lebih dari 3 jenis pancaindra dengan kata konkret yang sangat bervariasi dan hidup. |\n| **Kerapian & Keterpaduan Bahasa** | Banyak kesalahan ejaan/tanda baca dan antar kalimat tidak padu. | Terdapat beberapa kesalahan ejaan/tanda baca, keterpaduan kalimat cukup baik. | Ejaan dan tanda baca sebagian besar tepat, kalimat padu dan mudah dipahami. | Ejaan dan tanda baca sangat tepat, pilihan kata menarik, serta seluruh kalimat sangat padu. |\n| **Kerjasama & Partisipasi Kelompok** | Pasif dan memerlukan dorongan penuh dari guru untuk terlibat. | Kurang aktif berdiskusi, hanya mengerjakan bagian jika diminta teman. | Aktif berdiskusi dan berkontribusi menyelesaikan tugas kelompok dengan baik. | Sangat aktif, menunjukkan kepemimpinan yang baik, dan membantu anggota kelompok lain. |\n\n---\n\n## F. Lampiran\n\n### 1. Ringkasan Bahan Bacaan Guru dan Peserta Didik\n\n#### A. Definisi dan Tujuan Teks Deskripsi\nTeks deskripsi adalah teks yang melukiskan atau menggambarkan suatu objek, tempat, atau peristiwa secara jelas dan terperinci sehingga pembaca seolah-olah melihat, mendengar, merasakan, atau mengalami sendiri apa yang dideskripsikan oleh penulis.\n\n#### B. Struktur Teks Deskripsi\n1.  **Identifikasi/Gambaran Umum:** Bagian yang berisi nama objek, lokasi, sejarah lahirnya, atau gambaran umum tentang objek yang dideskripsikan.\n2.  **Deskripsi Bagian:** Bagian yang berisi rincian bahan, warna, bentuk, suasana, atau bagian-bagian objek berdasarkan tanggapan subjektif penulis (memanfaatkan pencitraan pancaindra).\n3.  **Simpulan/Kesan:** Bagian penutup yang berisi kesan umum atau pandangan pribadi penulis terhadap objek tersebut.\n\n#### C. Contoh Teks Deskripsi Singkat Kontekstual\n\n> **Pesona Tepian Sungai Keritang**\n> \n> Sungai Keritang merupakan salah satu urat nadi kehidupan sekaligus tempat wisata alam yang menenangkan di kawasan Indragiri Hilir. Sungai ini mengalir tenang membelah pemukiman warga dan kawasan hijau daerah setempat.\n> \n> Memandang Sungai Keritang di sore hari menyajikan pemandangan yang memanjakan mata. Air sungai berkilau keemasan memantulkan sinar matahari senja. Di sepanjang tepian, jajaran pohon kelapa meliuk-liuk tertiup angin sepoi-sepoi. Dengar deru mesin perahu pompong yang melintas perlahan membelah ketenangan air, menyisakan buih-buih putih di permukaannya. Udara terasa sejuk bercampur dengan aroma khas dedaunan basah dan tanah pesisir.\n> \n> Berada di tepian Sungai Keritang memberikan kedamaian tersendiri. Keasrian alamnya membuat tempat ini sangat cocok untuk melepas lelah bersama keluarga di akhir pekan.\n\n---\n\n### 2. Glosarium\n*   **Deskripsi:** Pemaparan atau penggambaran dengan kata-kata secara jelas dan terperinci.\n*   **Konkret:** Nyata; benar-benar ada (dapat dilihat, diraba, dan sebagainya).\n*   **Pancaindra:** Alat perasa yang lima jumlahnya (penglihat, pendengar, pencium, perasa, dan peraba).\n*   **Pompong:** Perahu kayu tradisional bermotor yang umum digunakan di wilayah perairan/pesisir Riau.\n*   **Struktur:** Cara sesuatu disusun atau dibangun; susunan unsur-unsur dalam teks.\n\n---\n\n### 3. Daftar Pustaka\n*(Catatan: Guru disarankan untuk mencocokkan dengan edisi buku teks resmi yang berlaku di sekolah)*\n\n*   Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi. (2021). *Bahasa Indonesia: Buku Siswa SMP Kelas VII*. Jakarta: Pusat Kurikulum dan Perbukuan.\n*   Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi. (2021). *Bahasa Indonesia: Buku Panduan Guru SMP Kelas VII*. Jakarta: Pusat Kurikulum dan Perbukuan.','2026-11-08 07:03:08','2026-11-08 07:03:08'),
(3,1,'lkpd','LKPD: Menjelajah Perjalanan Makanan di Tubuh Kita','IPA','8','# LKPD: Menjelajah Perjalanan Makanan di Tubuh Kita\n\nNama: ........................................................  \nKelas: 8 (Fase D) ........................................  \nKelompok: ................................................  \nTanggal: ....................................................  \n\n---\n\n## Tujuan Pembelajaran\n1. Peserta didik dapat mengidentifikasi organ-organ penyusun sistem pencernaan manusia dan fungsinya.\n2. Peserta didik dapat membedakan proses pencernaan mekanik dan kimiawi beserta enzim yang berperan di dalamnya.\n3. Peserta didik dapat menganalisis keterkaitan antara nutrisi makanan lokal dengan kesehatan sistem pencernaan.\n\n*(Catatan Guru: Sesuaikan rumusan Tujuan Pembelajaran dengan dokumen CP terbaru yang berlaku di satuan pendidikan).*\n\n## Petunjuk Kerja\n1. Berdoalah sebelum mulai mengerjakan LKPD ini.\n2. Bacalah informasi singkat di bawah ini dengan teliti bersama kelompokmu.\n3. Diskusikan setiap kegiatan secara aktif dan kerja sama.\n4. Jawablah pertanyaan pada ruang atau tabel yang telah disediakan dengan jelas.\n5. Tanyakan kepada guru jika ada instruksi yang kurang dipahami.\n\n## Info Singkat / Bahan Bacaan\nBayangkan saat sarapan pagi sebelum berangkat ke SMP Negeri 6 Keritang, kamu menikmati seporsi nasi hangat dengan lauk ikan patin sungai atau sepotong kelapa parut manis. Makanan tersebut masuk ke mulut dan mulai mengalami perjalanan panjang. Sistem pencernaan manusia bertugas mengubah makanan yang besar dan kompleks menjadi nutrisi yang sangat kecil agar bisa diserap oleh tubuh menjadi energi. \n\nPencernaan dibagi menjadi dua jenis: **pencernaan mekanik** (proses penghancuran makanan oleh gigi atau gerakan otot lambung) dan **pencernaan kimiawi** (proses pengubahan zat makanan menggunakan bantuan enzim pencernaan). Organ utama yang dilewati meliputi mulut, kerongkongan, lambung, usus halus, usus besar, hingga anus, serta dibantu oleh organ kelenjar pencernaan seperti hati dan pankreas.\n\n## Kegiatan\n\n### Kegiatan 1: Mengamati dan Menanya (Mengenal Jalur Makanan)\nAmatilah gambar atau diagram sistem pencernaan manusia pada buku sumber/modul ajar. Lakukan tanya jawab dengan teman sekelompokmu mengenai organ apa saja yang dilalui oleh makanan.\n\nTuliskan urutan organ pencernaan dari awal makanan masuk hingga sisa makanan dikeluarkan!\n1. ........................................................................................................\n2. ........................................................................................................\n3. ........................................................................................................\n4. ........................................................................................................\n5. ........................................................................................................\n6. ........................................................................................................\n\n### Kegiatan 2: Mengumpulkan Data (Mekanik vs Kimiawi)\nLengkapilah tabel di bawah ini dengan menentukan jenis pencernaan (mekanik/kimiawi) serta enzim yang bekerja di organ tersebut!\n\n| No | Organ Pencernaan | Jenis Pencernaan (Mekanik / Kimiawi) | Enzim yang Berperan & Fungsinya (Jika ada) |\n| :--- | :--- | :--- | :--- |\n| 1. | Mulut | ........................................................ | ................................................................................ |\n| 2. | Lambung | ........................................................ | ................................................................................ |\n| 3. | Usus Halus | ........................................................ | ................................................................................ |\n\n### Kegiatan 3: Menganalisis (Peran Hati dan Pankreas)\nMeskipun makanan tidak melewati hati dan pankreas, kedua organ ini sangat penting dalam sistem pencernaan. Diskusikan mengapa organ hati dan pankreas disebut sebagai kelenjar pencernaan pendukung!\n\nJawab:  \n..................................................................................................................................................................  \n..................................................................................................................................................................  \n..................................................................................................................................................................\n\n### Kegiatan 4: Menjaga Kesehatan Pencernaan dengan Makanan Khas\nKesehatan sistem pencernaan sangat dipengaruhi oleh jenis makanan yang kita konsumsi sehari-hari. Di Riau, kita mengenal berbagai makanan khas seperti **gulai lambok** (sayur tradisional yang kaya serat) atau olahan **ikan patin** yang kaya protein dan asam lemak baik. Diskusikan dengan kelompokmu: bagaimana cara memilih dan mengonsumsi makanan khas daerah agar kesehatan organ pencernaan kita tetap terjaga dengan baik?\n\nJawab:  \n..................................................................................................................................................................  \n..................................................................................................................................................................  \n..................................................................................................................................................................\n\n### Kegiatan 5: Menyimpulkan\nBuatlah kesimpulan singkat mengenai perbedaan utama antara pencernaan mekanik dan pencernaan kimiawi berdasarkan kegiatan yang sudah kamu lakukan!\n\nJawab:  \n..................................................................................................................................................................  \n..................................................................................................................................................................  \n\n## Ayo Berpikir Kritis\n1. Daerah Kecamatan Keritang dikenal sebagai wilayah perkebunan kelapa yang luas, di mana masyarakatnya sering mengonsumsi makanan yang mengandung santan atau kelapa. Jika seseorang terlalu sering mengonsumsi makanan yang sangat berlemak tanpa diimbangi serat yang cukup dari sayuran, bagaimana dampaknya terhadap kerja organ lambung dan usus besar? Jelaskan!\n   \n   *Jawaban:*  \n   ..................................................................................................................................................................  \n   ..................................................................................................................................................................\n\n2. Mengapa makanan harus dikunyah dengan lembut terlebih dahulu di dalam mulut sebelum ditelan, meskipun di dalam lambung nantinya makanan tersebut akan dihancurkan lagi oleh asam lambung?\n\n   *Jawaban:*  \n   ..................................................................................................................................................................  \n   ..................................................................................................................................................................\n\n## Refleksi\nJawablah pertanyaan berikut secara jujur!\n1. Bagian materi mana dari sistem pencernaan yang paling mudah kamu pahami?  \n   *Jawab:* .........................................................................................................................................\n2. Bagian materi mana yang masih membuatmu bingung atau ingin kamu tanyakan lebih lanjut?  \n   *Jawab:* .........................................................................................................................................\n3. Bagaimana kerjasama kelompokmu dalam menyelesaikan LKPD ini?  \n   *Jawab:* .........................................................................................................................................\n\n**Skala Pemahaman Hari Ini:** (Lingkari salah satu atau beri tanda centang)\n* â­â­â­â­â­ (Sangat paham dan senang belajar)\n* â­â­â­â­ (Paham sebagian besar materi)\n* â­â­â­ (Cukup paham, perlu membaca lagi)\n* â­â­ (Kurang paham)\n\n---\n*Informasi Dokumen: Mata Pelajaran IPA Kelas 8 SMP | Disusun oleh: Budi Santoso, S.Pd. | Tahun Ajaran: 2026/2027 | Sekolah: SMP Negeri 6 Keritang, Kab. Indragiri Hilir, Riau.*\n\n---\n\n## Kunci/Pedoman untuk Guru\n* **Model Pembelajaran yang Disarankan:** *Discovery Learning* atau *Inquiry Based Learning* yang dipadukan dengan diskusi kelompok.\n* **Alokasi Waktu:** 2 JP (2 x 40 menit).\n* **Kunci Kegiatan 1 (Urutan Organ):** Mulut $\\rightarrow$ Kerongkongan (Esofagus) $\\rightarrow$ Lambung $\\rightarrow$ Usus Halus $\\rightarrow$ Usus Besar $\\rightarrow$ Anus.\n* **Kunci Kegiatan 2 (Tabel):**\n  1. Mulut: Mekanik (oleh gigi) dan Kimiawi (oleh enzim ptialin/amilase).\n  2. Lambung: Mekanik (oleh otot lambung) dan Kimiawi (oleh enzim pepsin, renin, asam klorida/HCl).\n  3. Usus Halus: Kimiawi (oleh enzim amilase, tripsin, lipase dari pankreas dan usus halus).\n* **Kunci Kegiatan 3 (Hati & Pankreas):** Hati menghasilkan empedu untuk memecah lemak, sedangkan pankreas menghasilkan berbagai enzim pencernaan yang disalurkan ke usus halus. Keduanya tidak dilewati makanan secara langsung, tetapi menghasilkan getah pencernaan.\n* **Kunci Kegiatan 4 (Menjaga Kesehatan Pencernaan):** Memilih makanan yang seimbang, misalnya memadukan olahan ikan yang kaya protein dengan sayuran berserat tinggi (seperti gulai lambok) serta menghindari konsumsi santan atau lemak berlebih secara berlebihan agar kerja lambung dan usus tetap optimal.\n* **Pedoman Penilaian HOTS:** \n  * Soal 1: Lemak berlebih memperlambat pengosongan lambung; kurang serat dapat menyebabkan feses keras di usus besar (konstipasi).\n  * Soal 2: Mengunyah memperluas permukaan makanan agar enzim pencernaan bekerja lebih efektif dan meringankan kerja mekanik lambung.','2026-11-08 07:03:14','2026-11-08 07:03:29'),
(4,1,'presentasi','Algoritma dan Pemrograman Dasar','Informatika','8','{\"judul\":\"Algoritma dan Pemrograman Dasar\",\"subjudul\":\"Informatika Kelas 8 - SMP Negeri 6 Keritang T.A. 2026\\/2027\",\"slide\":[{\"judul\":\"Algoritma dan Pemrograman Dasar\",\"poin\":[\"Mata Pelajaran Informatika Kelas 8\",\"SMP Negeri 6 Keritang, Indragiri Hilir, Riau\",\"Tahun Ajaran 2026\\/2027\",\"Fokus pada logika berpikir komputasional\",\"Dasar pembuatan program komputer sederhana\"],\"catatan\":\"Selamat pagi anak-anak. Hari ini kita akan belajar dasar-dasar berpikir logis dan pemrograman. Silakan sesuaikan materi dengan Capaian Pembelajaran resmi kurikulum yang berlaku di sekolah.\"},{\"judul\":\"Tujuan Pembelajaran\",\"poin\":[\"Memahami definisi algoritma secara benar\",\"Menjelaskan konsep dasar pemrograman komputer\",\"Mengenal contoh algoritma dalam kehidupan sehari-hari\",\"Membuat urutan langkah logis sederhana\",\"Berpartisipasi aktif dalam diskusi kelompok\"],\"catatan\":\"Melalui pembelajaran ini, kalian diharapkan mampu memahami cara menyusun langkah penyelesaian masalah secara sistematis. Pemahaman ini sangat penting sebelum kita mulai menulis kode program.\"},{\"judul\":\"Pertanyaan Pemantik\",\"poin\":[\"Bagaimana cara merebus telur dengan benar?\",\"Apakah langkah memasak harus berurutan?\",\"Apa bedanya manusia dan komputer?\",\"Bisakah komputer berpikir sendiri tanpa instruksi?\"],\"catatan\":\"Ajukan pertanyaan ini untuk menarik perhatian siswa. Hubungkan jawaban mereka dengan konsep bahwa komputer butuh instruksi yang sangat jelas dan berurutan agar tidak salah kerja.\"},{\"judul\":\"Apa Itu Algoritma?\",\"poin\":[\"Definisi: Urutan langkah logis penyelesaian masalah\",\"Sifat: Harus jelas, logis, dan pasti\",\"Contoh kebun: Langkah memupuk pohon kelapa\",\"Setiap aktivitas harian punya algoritma\"],\"catatan\":\"Algoritma ibarat resep masakan atau panduan kerja. Di daerah perkebunan kelapa seperti Keritang, petani punya urutan kerja yang jelas mulai dari membersihkan lahan hingga panen.\"},{\"judul\":\"Aktivitas dan Diskusi\",\"poin\":[\"Bentuk kelompok kecil beranggotakan 4-5 siswa\",\"Diskusikan langkah membuat parut kelapa tradisional\",\"Tuliskan urutan langkah secara sistematis\",\"Presentasikan hasil diskusi di depan kelas\"],\"catatan\":\"Gunakan aktivitas kelompok agar siswa aktif berkolaborasi. Minta mereka merinci langkah kerja nyata yang sering mereka temui di lingkungan sekitar perkebunan kelapa.\"},{\"judul\":\"Kuis Singkat\",\"poin\":[\"1. Apa arti algoritma dalam informatika?\",\"2. Mengapa urutan langkah tidak boleh terbalik?\",\"3. Berikan satu contoh algoritma harianmu!\"],\"catatan\":\"Berikan waktu lima menit bagi siswa untuk menjawab di buku catatan. Bahas bersama-sama untuk mengukur pemahaman awal mereka terhadap materi algoritma.\"},{\"judul\":\"Rangkuman\",\"poin\":[\"Algoritma adalah urutan langkah logis\",\"Langkah harus runtut dan tidak ambigu\",\"Pemrograman adalah cara memberi instruksi komputer\",\"Logika yang baik hasilkan program yang benar\"],\"catatan\":\"Tegaskan kembali poin-poin penting hari ini. Ingatkan bahwa kemampuan menyusun algoritma adalah fondasi utama seorang pembuat program yang handal.\"},{\"judul\":\"Refleksi\",\"poin\":[\"Bagian mana yang paling mudah dipahami?\",\"Kesulitan apa saja yang kalian temukan?\",\"Bagaimana perasaan kalian selama pembelajaran hari ini?\",\"Tuliskan satu hal baru yang dipelajari\"],\"catatan\":\"Minta siswa menuliskan refleksi singkat di kertas lembar. Hal ini membantu guru mengevaluasi efektivitas metode mengajar pada pertemuan hari ini.\"}]}','2026-11-08 07:03:17','2026-11-08 07:03:17'),
(5,1,'presentasi','Interaksi Sosial','IPS','7','{\"judul\":\"Interaksi Sosial\",\"subjudul\":\"IPS Kelas 7 - SMP Negeri 6 Keritang\",\"slide\":[{\"judul\":\"Tujuan Pembelajaran\",\"poin\":[\"Menjelaskan pengertian interaksi sosial dengan tepat.\",\"Mengidentifikasi syarat terjadinya interaksi sosial.\",\"Memberikan contoh interaksi sosial di lingkungan sekitar.\",\"Membedakan bentuk interaksi sosial asosiatif dan disosiatif.\"],\"catatan\":\"Guru menyampaikan tujuan pembelajaran hari ini kepada peserta didik. Pastikan tujuan ini selaras dengan dokumen resmi kurikulum yang berlaku di sekolah.\"},{\"judul\":\"Pertanyaan Pemantik\",\"poin\":[\"Mengapa manusia tidak bisa hidup sendiri?\",\"Apa yang terjadi saat penjual dan pembeli bertemu?\",\"Bagaimana warga parit perkebunan saling bekerja sama?\"],\"catatan\":\"Guru mengajukan pertanyaan ini untuk menarik perhatian siswa. Arahkan jawaban siswa pada pentingnya hubungan antarmanusia dalam kehidupan sehari-hari di daerah perkebunan kelapa.\"},{\"judul\":\"Materi: Konsep dan Contoh\",\"poin\":[\"Interaksi sosial adalah hubungan timbal balik antarmanusia.\",\"Syarat: adanya kontak sosial dan komunikasi.\",\"Contoh: tawar-menawar harga kelapa di pasar parit.\",\"Contoh: gotong royong membersihkan saluran air desa.\"],\"catatan\":\"Guru menjelaskan konsep dasar interaksi sosial menggunakan contoh lokal yang dekat dengan siswa. Penekanan diberikan pada pentingnya komunikasi dan kontak langsung.\"},{\"judul\":\"Aktivitas Diskusi Kelompok\",\"poin\":[\"Bentuk kelompok kecil 3 hingga 4 orang.\",\"Amati aktivitas warga di sekitar perkebunan kelapa.\",\"Identifikasi satu contoh kerja sama dan satu persaingan.\",\"Presentasikan hasil pengamatan kelompok di depan kelas.\"],\"catatan\":\"Guru memandu siswa melakukan diskusi kelompok singkat. Berikan bimbingan agar setiap kelompok aktif mengaitkan materi dengan situasi nyata di Kecamatan Keritang.\"},{\"judul\":\"Kuis Singkat\",\"poin\":[\"1. Apa dua syarat utama terjadinya interaksi sosial?\",\"2. Sebutkan satu contoh interaksi asosiatif di desa!\",\"3. Mengapa konflik dapat terjadi dalam masyarakat?\"],\"catatan\":\"Guru memberikan waktu beberapa menit untuk menjawab pertanyaan secara mandiri. Lakukan pembahasan singkat untuk meluruskan pemahaman siswa yang kurang tepat.\"},{\"judul\":\"Rangkuman dan Refleksi\",\"poin\":[\"Interaksi sosial butuh kontak dan komunikasi.\",\"Bentuknya bisa berupa kerja sama atau pertentangan.\",\"Refleksi: Apa hal baru yang kamu pelajari hari ini?\",\"Refleksi: Bagaimana cara menjaga kerukunan di lingkungan kita?\"],\"catatan\":\"Guru menyimpulkan materi bersama siswa dan menutup pelajaran dengan refleksi. Ajak siswa merenungkan pentingnya menjaga hubungan baik antarwarga di lingkungan pesisir dan perkebunan.\"}]}','2026-11-08 07:04:34','2026-11-08 07:04:34');
/*!40000 ALTER TABLE `guru_dokumen` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_jadwal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_jadwal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guru_id` int(11) NOT NULL,
  `hari` tinyint(4) NOT NULL,
  `jam_ke` tinyint(4) NOT NULL DEFAULT 1,
  `mulai` time DEFAULT NULL,
  `selesai` time DEFAULT NULL,
  `kelas` varchar(50) NOT NULL,
  `mapel` varchar(120) NOT NULL,
  `ruang` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `guru_id` (`guru_id`,`hari`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_jadwal` WRITE;
/*!40000 ALTER TABLE `guru_jadwal` DISABLE KEYS */;
INSERT INTO `guru_jadwal` VALUES
(47,1,1,1,'07:00:00','08:10:00','VII B','Informatika',''),
(48,1,1,2,'08:10:00','08:50:00','VIII A','Informatika',''),
(50,1,1,4,'09:45:00','10:25:00','VIII A','Informatika',''),
(51,1,1,5,'10:25:00','11:05:00','VII B','Informatika',''),
(52,1,1,6,'11:05:00','11:45:00','IX A','Informatika',''),
(53,1,1,7,'12:15:00','12:55:00','VII B','Informatika',''),
(54,1,1,8,'12:55:00','13:35:00','VIII A','Informatika',''),
(55,1,2,1,'07:30:00','08:10:00','VII B','Informatika',''),
(56,1,2,2,'08:10:00','08:50:00','VIII B','Informatika',''),
(57,1,2,3,'08:50:00','09:30:00','IX A','Informatika',''),
(58,1,2,4,'09:45:00','10:25:00','VII A','Informatika',''),
(59,1,2,5,'10:25:00','11:05:00','VII B','Informatika',''),
(60,1,2,6,'11:05:00','11:45:00','IX A','Informatika',''),
(61,1,2,7,'12:15:00','12:55:00','VII B','Informatika',''),
(62,1,2,8,'12:55:00','13:35:00','IX B','Informatika',''),
(63,1,3,1,'07:30:00','08:10:00','VIII A','Informatika',''),
(64,1,3,2,'08:10:00','08:50:00','VIII A','Informatika',''),
(65,1,3,3,'08:50:00','09:30:00','IX A','Informatika',''),
(66,1,3,4,'09:45:00','10:25:00','IX B','Informatika',''),
(67,1,3,5,'10:25:00','11:05:00','VIII A','Informatika',''),
(68,1,3,7,'12:15:00','12:55:00','VIII A','Informatika',''),
(69,1,3,8,'12:55:00','13:35:00','VII A','Informatika',''),
(70,1,4,1,'07:30:00','08:10:00','VII A','Informatika',''),
(71,1,4,3,'08:50:00','09:30:00','VIII B','Informatika',''),
(72,1,4,4,'09:45:00','10:25:00','VIII B','Informatika',''),
(73,1,4,5,'10:25:00','11:05:00','VII B','Informatika',''),
(74,1,4,6,'11:05:00','11:45:00','IX B','Informatika',''),
(75,1,4,7,'12:15:00','12:55:00','VIII B','Informatika',''),
(76,1,5,2,'08:05:00','08:40:00','VII B','Informatika',''),
(77,1,5,3,'08:40:00','09:15:00','IX B','Informatika',''),
(78,1,5,4,'09:30:00','10:05:00','VII A','Informatika',''),
(79,1,5,5,'10:05:00','10:40:00','VII B','Informatika',''),
(80,1,6,1,'07:30:00','08:10:00','VIII A','Informatika',''),
(81,1,6,2,'08:10:00','08:50:00','VII A','Informatika',''),
(82,1,6,3,'08:50:00','09:30:00','VIII A','Informatika',''),
(83,1,6,4,'09:45:00','10:25:00','VII B','Informatika',''),
(84,1,6,5,'10:25:00','11:05:00','VII B','Informatika',''),
(85,1,6,6,'11:05:00','11:45:00','IX A','Informatika','');
/*!40000 ALTER TABLE `guru_jadwal` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_kelas` (
  `tahun` varchar(9) NOT NULL,
  `guru_id` int(11) NOT NULL DEFAULT 0,
  `nama` varchar(50) NOT NULL,
  `wali_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`tahun`,`guru_id`,`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_kelas` WRITE;
/*!40000 ALTER TABLE `guru_kelas` DISABLE KEYS */;
INSERT INTO `guru_kelas` VALUES
('2026/2027',1,'IX A',NULL),
('2026/2027',1,'IX B',NULL),
('2026/2027',1,'VII A',NULL),
('2026/2027',1,'VII B',NULL),
('2026/2027',1,'VII C',NULL),
('2026/2027',1,'VIII A',1),
('2026/2027',2,'VIII A',NULL);
/*!40000 ALTER TABLE `guru_kelas` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_kelas_siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_kelas_siswa` (
  `tahun` varchar(9) NOT NULL,
  `kode` varchar(20) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `absen` smallint(6) DEFAULT NULL,
  PRIMARY KEY (`tahun`,`kode`),
  KEY `tahun` (`tahun`,`kelas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_kelas_siswa` WRITE;
/*!40000 ALTER TABLE `guru_kelas_siswa` DISABLE KEYS */;
INSERT INTO `guru_kelas_siswa` VALUES
('2026/2027','12840210','IX B',8),
('2026/2027','21814233','IX B',1),
('2026/2027','22293940','VII A',8),
('2026/2027','36620051','IX B',4),
('2026/2027','39104132','IX B',2),
('2026/2027','39411128','IX B',5),
('2026/2027','43181034','IX A',1),
('2026/2027','52507342','IX B',3),
('2026/2027','54825020','VII A',11),
('2026/2027','68649413','IX A',2),
('2026/2027','70607818','VII A',13),
('2026/2027','72903171','VII C',4),
('2026/2027','77878701','VII C',3),
('2026/2027','78064918','VIII A',NULL),
('2026/2027','78605414','VII B',2),
('2026/2027','89868444','VII B',1),
('2026/2027','9000000001','VII A',14),
('2026/2027','9000000002','VII A',12),
('2026/2027','9000000003','VII A',4),
('2026/2027','9000000004','VII A',5),
('2026/2027','9000000005','VII A',9),
('2026/2027','9000000006','VII A',1),
('2026/2027','9000000007','VII A',3),
('2026/2027','9000000008','VII A',6),
('2026/2027','9000000009','VII A',2),
('2026/2027','9000000010','VII A',10),
('2026/2027','9000000011','VIII A',NULL),
('2026/2027','9000000012','VIII A',NULL),
('2026/2027','9000000013','VIII A',NULL),
('2026/2027','9000000014','VIII A',NULL),
('2026/2027','9000000015','VIII A',NULL),
('2026/2027','9000000016','VIII A',NULL),
('2026/2027','9000000017','VIII A',NULL),
('2026/2027','9000000018','VIII A',NULL),
('2026/2027','9000000019','VIII A',NULL),
('2026/2027','9000000020','VIII A',NULL),
('2026/2027','9000000021','IX A',NULL),
('2026/2027','9000000022','IX A',NULL),
('2026/2027','9000000023','IX A',NULL),
('2026/2027','9000000024','IX A',NULL),
('2026/2027','9000000025','IX A',NULL),
('2026/2027','9000000026','IX A',NULL),
('2026/2027','9000000027','IX A',NULL),
('2026/2027','9000000028','IX A',NULL),
('2026/2027','9000000029','IX A',NULL),
('2026/2027','9000000030','IX A',NULL),
('2026/2027','92260601','VIII A',NULL),
('2026/2027','94856327','IX B',7),
('2026/2027','95881326','IX B',6);
/*!40000 ALTER TABLE `guru_kelas_siswa` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_koreksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_koreksi` (
  `penilaian_id` int(11) NOT NULL,
  `member_id` varchar(20) NOT NULL,
  `detail` mediumtext DEFAULT NULL,
  `nilai` decimal(5,2) DEFAULT NULL,
  `oleh` varchar(200) DEFAULT NULL,
  `dibuat` datetime DEFAULT NULL,
  PRIMARY KEY (`penilaian_id`,`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_koreksi` WRITE;
/*!40000 ALTER TABLE `guru_koreksi` DISABLE KEYS */;
INSERT INTO `guru_koreksi` VALUES
(1,'9000000013','{\"soal\":[{\"no\":\"1\",\"jenis\":\"pg\",\"maks\":1,\"kunci\":\"B\",\"jawaban\":\"B\",\"skor\":1,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"\"},{\"no\":\"2\",\"jenis\":\"pg\",\"maks\":1,\"kunci\":\"D\",\"jawaban\":\"D\",\"skor\":1,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"\"},{\"no\":\"3\",\"jenis\":\"pg\",\"maks\":1,\"kunci\":\"A\",\"jawaban\":\"A\",\"skor\":1,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"\"},{\"no\":\"4\",\"jenis\":\"pg\",\"maks\":1,\"kunci\":\"C\",\"jawaban\":\"D\",\"skor\":0,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"ditulis huruf kecil d\"},{\"no\":\"5\",\"jenis\":\"pg\",\"maks\":1,\"kunci\":\"B\",\"jawaban\":\"C\",\"skor\":0,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"\"},{\"no\":\"6\",\"jenis\":\"pg\",\"maks\":1,\"kunci\":\"A\",\"jawaban\":\"D\",\"skor\":0,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"C dicoret dua garis, D ditulis di samping kotak\"},{\"no\":\"7\",\"jenis\":\"pg\",\"maks\":1,\"kunci\":\"D\",\"jawaban\":\"C\",\"skor\":0,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"\"},{\"no\":\"8\",\"jenis\":\"pg\",\"maks\":1,\"kunci\":\"C\",\"jawaban\":\"B\",\"skor\":0,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"\"},{\"no\":\"9\",\"jenis\":\"pg\",\"maks\":1,\"kunci\":\"B\",\"jawaban\":\"B\",\"skor\":1,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"huruf dalam kotak ditimpa coretan, B ditulis di samping kotak\"},{\"no\":\"10\",\"jenis\":\"pg\",\"maks\":1,\"kunci\":\"A\",\"jawaban\":\"B\",\"skor\":0,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"\"},{\"no\":\"11\",\"jenis\":\"pg\",\"maks\":1,\"kunci\":\"C\",\"jawaban\":\"A\",\"skor\":0,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"\"},{\"no\":\"12\",\"jenis\":\"pg\",\"maks\":1,\"kunci\":\"D\",\"jawaban\":\"\",\"skor\":0,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"kosong\"},{\"no\":\"13\",\"jenis\":\"pg\",\"maks\":1,\"kunci\":\"B\",\"jawaban\":\"B\",\"skor\":1,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"\"},{\"no\":\"14\",\"jenis\":\"pg\",\"maks\":1,\"kunci\":\"A\",\"jawaban\":\"B\",\"skor\":0,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"kotak kosong, huruf B ditulis di samping kanan kotak\"},{\"no\":\"15\",\"jenis\":\"pg\",\"maks\":1,\"kunci\":\"C\",\"jawaban\":\"A\",\"skor\":0,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"\"},{\"no\":\"16\",\"jenis\":\"bs\",\"maks\":1,\"kunci\":\"B\",\"jawaban\":\"B\",\"skor\":1,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"\"},{\"no\":\"17\",\"jenis\":\"bs\",\"maks\":1,\"kunci\":\"S\",\"jawaban\":\"S\",\"skor\":1,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"\"},{\"no\":\"18\",\"jenis\":\"bs\",\"maks\":1,\"kunci\":\"S\",\"jawaban\":\"S\",\"skor\":1,\"skor_ai\":null,\"ragu\":false,\"catatan\":\"\"},{\"no\":\"19\",\"jenis\":\"isian\",\"maks\":1,\"kunci\":\"\",\"jawaban\":\"debuging\",\"skor\":1,\"skor_ai\":1,\"ragu\":false,\"catatan\":\"Terdapat kesalahan ejaan kecil (\'debuging\'), namun maknanya sama dengan kunci sehingga mendapat skor penuh.\"},{\"no\":\"20\",\"jenis\":\"esai\",\"maks\":10,\"kunci\":\"\",\"jawaban\":\"1. datang ke perpustakaan\\n2. cari buku di rak\\/katalog\\n3. bawa buku ke petugas\\n4. tunjukkan kartu anggota\",\"skor\":6,\"skor_ai\":6,\"ragu\":false,\"catatan\":\"Langkah logis dan urut, tetapi hanya 4 langkah (kurang dari syarat minimal 5) serta belum memuat poin pencatatan dan tanggal kembali.\"}],\"skor\":15,\"maks\":29,\"komentar\":\"Usaha yang bagus! Jangan lupa untuk lebih teliti membaca instruksi soal mengenai jumlah minimal langkah dan melengkapi detail proses pencatatan pinjaman, ya.\"}',52.00,'Google Gemini · gemini-3.5-flash + Google Gemini · gemini-3.6-flash','2026-11-09 10:23:07');
/*!40000 ALTER TABLE `guru_koreksi` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_koreksi_kunci`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_koreksi_kunci` (
  `penilaian_id` int(11) NOT NULL,
  `format_lj` varchar(10) NOT NULL DEFAULT 'otomatis',
  `teks` mediumtext DEFAULT NULL,
  `diubah` datetime DEFAULT NULL,
  PRIMARY KEY (`penilaian_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_koreksi_kunci` WRITE;
/*!40000 ALTER TABLE `guru_koreksi_kunci` DISABLE KEYS */;
INSERT INTO `guru_koreksi_kunci` VALUES
(1,'lj','1. B\n2. D\n3. A\n4. C\n5. B\n6. A\n7. D\n8. C\n9. B\n10. A\n11. C\n12. D\n13. B\n14. A\n15. C\n16. (bs) B\n17. (bs) S\n18. (bs) S\n19. (isian) debugging\n20. (esai, 10 poin) Buatlah algoritma meminjam buku di perpustakaan (minimal 5 langkah). Poin kunci: urut, lengkap, logis, ada pencatatan & tanggal kembali.','2026-11-09 10:10:49');
/*!40000 ALTER TABLE `guru_koreksi_kunci` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_nilai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_nilai` (
  `penilaian_id` int(11) NOT NULL,
  `member_id` varchar(20) NOT NULL,
  `nilai` decimal(5,2) DEFAULT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`penilaian_id`,`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_nilai` WRITE;
/*!40000 ALTER TABLE `guru_nilai` DISABLE KEYS */;
INSERT INTO `guru_nilai` VALUES
(1,'9000000011',74.00,'remedial'),
(1,'9000000012',84.00,''),
(1,'9000000013',52.00,'Foto: benar 8/18 PG'),
(1,'9000000014',93.00,''),
(1,'9000000015',65.00,'remedial'),
(1,'9000000016',86.00,''),
(1,'9000000017',66.00,'remedial'),
(1,'9000000018',63.00,'remedial'),
(1,'9000000019',71.00,'remedial'),
(1,'9000000020',100.00,''),
(2,'9000000011',65.00,'remedial'),
(2,'9000000012',92.00,''),
(2,'9000000013',79.00,''),
(2,'9000000014',75.00,''),
(2,'9000000015',82.00,''),
(2,'9000000016',82.00,''),
(2,'9000000017',97.00,''),
(2,'9000000018',96.00,''),
(2,'9000000019',94.00,''),
(2,'9000000020',87.00,''),
(3,'9000000001',95.00,''),
(3,'9000000002',96.00,''),
(3,'9000000003',71.00,'remedial'),
(3,'9000000004',60.00,'remedial'),
(3,'9000000005',61.00,'remedial'),
(3,'9000000006',70.00,'remedial'),
(3,'9000000007',88.00,''),
(3,'9000000008',61.00,'remedial'),
(3,'9000000009',62.00,'remedial'),
(3,'9000000010',71.00,'remedial'),
(4,'9000000011',26.00,'Ujian online'),
(4,'9000000012',68.00,'Ujian online'),
(4,'9000000013',5.00,'Ujian online'),
(4,'9000000014',74.00,'Ujian online'),
(4,'9000000015',11.00,'Ujian online'),
(4,'9000000016',16.00,'Ujian online'),
(4,'9000000017',21.00,'Ujian online'),
(4,'9000000018',5.00,'Ujian online'),
(4,'9000000019',11.00,'Ujian online'),
(4,'9000000020',21.00,'Ujian online');
/*!40000 ALTER TABLE `guru_nilai` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_penilaian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_penilaian` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guru_id` int(11) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `mapel` varchar(120) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `jenis` varchar(30) NOT NULL DEFAULT 'Tugas',
  `tanggal` date DEFAULT NULL,
  `kktp` decimal(5,2) NOT NULL DEFAULT 75.00,
  `dibuat` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `guru_id` (`guru_id`,`kelas`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_penilaian` WRITE;
/*!40000 ALTER TABLE `guru_penilaian` DISABLE KEYS */;
INSERT INTO `guru_penilaian` VALUES
(1,1,'VIII A','Informatika','Ulangan Bab 1: Berpikir Komputasional','Ulangan Harian','2026-10-14',75.00,'2026-11-08 07:19:47'),
(2,1,'VIII A','Informatika','Tugas Proyek Algoritma','Tugas','2026-10-28',75.00,'2026-11-08 07:19:47'),
(3,1,'VII A','Informatika','Ulangan Bab 1: Informatika & Kehidupan','Ulangan Harian','2026-10-21',75.00,'2026-11-08 07:19:47'),
(4,1,'VIII A','Informatika','Ulangan Harian Informatika Bab 1 (online)','Ulangan Harian','2026-11-09',75.00,'2026-11-09 10:55:55');
/*!40000 ALTER TABLE `guru_penilaian` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_sertifikat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_sertifikat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guru_id` int(11) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `sebutan` varchar(150) DEFAULT NULL,
  `isi` text DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `kota` varchar(80) DEFAULT NULL,
  `ttd1_nama` varchar(120) DEFAULT NULL,
  `ttd1_jabatan` varchar(120) DEFAULT NULL,
  `ttd1_nip` varchar(40) DEFAULT NULL,
  `ttd2_nama` varchar(120) DEFAULT NULL,
  `ttd2_jabatan` varchar(120) DEFAULT NULL,
  `ttd2_nip` varchar(40) DEFAULT NULL,
  `format_nomor` varchar(120) DEFAULT NULL,
  `warna` varchar(20) DEFAULT NULL,
  `dibuat` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_sertifikat` WRITE;
/*!40000 ALTER TABLE `guru_sertifikat` DISABLE KEYS */;
INSERT INTO `guru_sertifikat` VALUES
(1,1,'PIAGAM PENGHARGAAN','Diberikan kepada','Atas prestasinya dalam Lomba Coding Antarkelas dalam rangka Hari Guru Nasional 2026.','2026-11-25','Keritang','Kepala Sekolah (contoh)','Kepala Sekolah','','Budi Santoso, S.Pd.','Guru Informatika','198501012010011001','{n}/SRT/XI/2026','biru','2026-11-08 07:19:47');
/*!40000 ALTER TABLE `guru_sertifikat` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_sertifikat_penerima`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_sertifikat_penerima` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sertifikat_id` int(11) NOT NULL,
  `urut` int(11) NOT NULL DEFAULT 1,
  `nama` varchar(150) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sertifikat_id` (`sertifikat_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_sertifikat_penerima` WRITE;
/*!40000 ALTER TABLE `guru_sertifikat_penerima` DISABLE KEYS */;
INSERT INTO `guru_sertifikat_penerima` VALUES
(1,1,1,'Rafi Saputra','Juara 1 - Kelas VIII A'),
(2,1,2,'Nabila Maharani','Juara 2 - Kelas IX A'),
(3,1,3,'Galih Pratama','Juara 3 - Kelas VII A');
/*!40000 ALTER TABLE `guru_sertifikat_penerima` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_setelan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_setelan` (
  `k` varchar(50) NOT NULL,
  `v` text DEFAULT NULL,
  PRIMARY KEY (`k`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_setelan` WRITE;
/*!40000 ALTER TABLE `guru_setelan` DISABLE KEYS */;
INSERT INTO `guru_setelan` VALUES
('jam_h1:1','1 07:00-08:10\n2 08:10-08:50\n3 08:50-09:30\n4 09:45-10:25\n5 10:25-11:05\n6 11:05-11:45\n7 12:15-12:55\n8 12:55-13:35\n9 13:35-14:15'),
('jam_h1:2','1 07:15-07:45\n2 07:45-08:30\n3 08:30-09:15'),
('jam_h2:1','1 07:30-08:10\n2 08:10-08:50\n3 08:50-09:30\n4 09:45-10:25\n5 10:25-11:05\n6 11:05-11:45\n7 12:15-12:55\n8 12:55-13:35'),
('jam_h2:2','1 07:15-07:45\n2 07:45-08:30\n3 08:30-09:15\n4 09:15-09:55'),
('jam_h3:1','1 07:30-08:10\n2 08:10-08:50\n3 08:50-09:30\n4 09:45-10:25\n5 10:25-11:05\n6 11:05-11:45\n7 12:15-12:55\n8 12:55-13:35'),
('jam_h3:2','1 07:00-07:45\n2 07:45-08:30\n3 08:30-09:15'),
('jam_h4:1','1 07:30-08:10\n2 08:10-08:50\n3 08:50-09:30\n4 09:45-10:25\n5 10:25-11:05\n6 11:05-11:45\n7 12:15-12:55\n8 12:55-13:35'),
('jam_h4:2','1 07:00-07:45\n2 07:45-08:30\n3 08:30-09:15'),
('jam_h5:1','1 07:30-08:05\n2 08:05-08:40\n3 08:40-09:15\n4 09:30-10:05\n5 10:05-10:40\n6 10:40-11:15'),
('jam_h5:2','1 07:00-07:40'),
('jam_h6:1','1 07:30-08:10\n2 08:10-08:50\n3 08:50-09:30\n4 09:45-10:25\n5 10:25-11:05\n6 11:05-11:45\n7 12:15-12:55\n8 12:55-13:35'),
('jam_h6:2','0'),
('jam_jumat:2','1 07:00-07:40'),
('jam_umum:2','1 07:00-07:45\n2 07:45-08:30\n3 08:30-09:15'),
('tahun_ajaran','2026/2027');
/*!40000 ALTER TABLE `guru_setelan` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_siswa` (
  `kode` varchar(20) NOT NULL,
  `guru_id` int(11) NOT NULL DEFAULT 0,
  `nisn` varchar(20) DEFAULT NULL,
  `nama` varchar(120) NOT NULL,
  `jk` char(1) DEFAULT NULL,
  `status` varchar(10) NOT NULL DEFAULT 'aktif',
  `dibuat` datetime DEFAULT NULL,
  `diubah` datetime DEFAULT NULL,
  PRIMARY KEY (`kode`),
  KEY `nisn` (`nisn`),
  KEY `nama` (`nama`),
  KEY `guru_id` (`guru_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_siswa` WRITE;
/*!40000 ALTER TABLE `guru_siswa` DISABLE KEYS */;
INSERT INTO `guru_siswa` VALUES
('12840210',1,NULL,'Indah Permatasari','P','aktif','2026-11-09 10:45:25','2026-11-09 10:45:25'),
('21814233',1,NULL,'Ahmad Fauzi','L','aktif','2026-11-09 10:45:25','2026-11-09 10:45:25'),
('22293940',1,NULL,'Rahmat Hidayat','L','aktif','2026-11-09 10:40:53','2026-11-09 10:40:53'),
('36620051',1,NULL,'Eka Putri Ramadhani','P','aktif','2026-11-09 10:45:25','2026-11-09 10:45:25'),
('39104132',1,NULL,'Bunga Citra Lestari','P','aktif','2026-11-09 10:45:25','2026-11-09 10:45:25'),
('39411128',1,NULL,'Fikri Maulana','L','aktif','2026-11-09 10:45:25','2026-11-09 10:45:25'),
('43181034',1,NULL,'Fajar Hidayat','L','aktif','2026-11-09 10:41:01','2026-11-09 10:41:01'),
('52507342',1,NULL,'Dimas Arya Nugraha','L','aktif','2026-11-09 10:45:25','2026-11-09 10:45:25'),
('54825020',1,'0091234567','Siti Nurhaliza','P','aktif','2026-11-09 10:40:53','2026-11-09 10:40:53'),
('68649413',1,NULL,'Wulan Permata','P','aktif','2026-11-09 10:41:01','2026-11-09 10:41:01'),
('70607818',1,NULL,'Yoga Pratama','L','aktif','2026-11-09 10:40:53','2026-11-09 10:40:53'),
('72903171',1,'0098765404','Nur Aisyah','P','aktif','2026-11-09 10:40:56','2026-11-09 10:40:58'),
('77878701',1,'0098765403','Reza Pratama','L','aktif','2026-11-09 10:40:56','2026-11-09 10:40:58'),
('78064918',2,NULL,'Eko Prasetyo','L','aktif','2026-11-09 12:31:05','2026-11-09 12:31:05'),
('78605414',1,'0098765402','Dewi Lestari','P','aktif','2026-11-09 10:40:56','2026-11-09 10:40:58'),
('89868444',1,'0098765401','Andi Saputra','L','aktif','2026-11-09 10:40:56','2026-11-09 10:40:58'),
('9000000001',1,'0000000001','Yusuf Rahmawati','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000002',1,'0000000002','Siti Pratama','P','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000003',1,'0000000003','Hendra Wijaya','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000004',1,'0000000004','Intan Safitri','P','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000005',1,'0000000005','Rina Rahmawati','P','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000006',1,'0000000006','Bayu Maharani','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000007',1,'0000000007','Galih Setiawan','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000008',1,'0000000008','Mega Kurniawan','P','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000009',1,'0000000009','Dimas Wijaya','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000010',1,'0000000010','Salsa Rahmawati','P','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000011',1,'0000000011','Taufik Anggraini','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000012',1,'0000000012','Putri Permata','P','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000013',1,'0000000013','Intan Nugroho','P','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000014',1,'0000000014','Wulan Saputra','P','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000015',1,'0000000015','Galih Lestari','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000016',1,'0000000016','Putri Pratama','P','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000017',1,'0000000017','Taufik Lestari','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000018',1,'0000000018','Indah Setiawan','P','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000019',1,'0000000019','Arif Wijaya','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000020',1,'0000000020','Hendra Permata','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000021',1,'0000000021','Ilham Anggraini','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000022',1,'0000000022','Rina Wijaya','P','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000023',1,'0000000023','Rizki Kurniawan','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000024',1,'0000000024','Rizki Wijaya','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000025',1,'0000000025','Dewi Safitri','P','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000026',1,'0000000026','Rafi Maharani','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000027',1,'0000000027','Fajar Wijaya','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000028',1,'0000000028','Taufik Setiawan','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000029',1,'0000000029','Dimas Rahmawati','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('9000000030',1,'0000000030','Ahmad Lestari','L','aktif','2026-11-09 10:40:21','2026-11-09 10:40:21'),
('92260601',2,NULL,'Dina Amalia','P','aktif','2026-11-09 12:31:05','2026-11-09 12:31:05'),
('94856327',1,NULL,'Hendra Wijaya','L','aktif','2026-11-09 10:45:25','2026-11-09 10:45:25'),
('95881326',1,NULL,'Gita Safitri','P','aktif','2026-11-09 10:45:25','2026-11-09 10:45:25');
/*!40000 ALTER TABLE `guru_siswa` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_soal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_soal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guru_id` int(11) NOT NULL,
  `mapel` varchar(120) NOT NULL,
  `kelas` varchar(20) DEFAULT NULL,
  `topik` varchar(200) DEFAULT NULL,
  `tipe` varchar(10) NOT NULL DEFAULT 'pg',
  `tingkat` varchar(10) NOT NULL DEFAULT 'sedang',
  `level` varchar(5) DEFAULT NULL,
  `pertanyaan` text NOT NULL,
  `opsi` text DEFAULT NULL,
  `kunci` text DEFAULT NULL,
  `pembahasan` text DEFAULT NULL,
  `berbagi` tinyint(4) NOT NULL DEFAULT 0,
  `dibuat` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `guru_id` (`guru_id`),
  KEY `mapel` (`mapel`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_soal` WRITE;
/*!40000 ALTER TABLE `guru_soal` DISABLE KEYS */;
INSERT INTO `guru_soal` VALUES
(1,1,'Informatika','8','Algoritma','pg','mudah','C1','Urutan langkah logis untuk menyelesaikan suatu masalah disebut ...','{\"A\": \"Program\", \"B\": \"Algoritma\", \"C\": \"Data\", \"D\": \"Variabel\"}','B','Algoritma adalah langkah-langkah terurut dan logis untuk menyelesaikan masalah.',1,'2026-11-08 07:19:47'),
(2,1,'Informatika','8','Algoritma','pg','sedang','C2','Simbol flowchart berbentuk belah ketupat digunakan untuk ...','{\"A\": \"Mulai/selesai\", \"B\": \"Proses\", \"C\": \"Percabangan/keputusan\", \"D\": \"Input/output\"}','C','Belah ketupat menandakan keputusan dengan dua kemungkinan jawaban.',0,'2026-11-08 07:19:47'),
(3,1,'Informatika','8','Algoritma','pg','sedang','C3','Ibu menanak nasi: (1) cuci beras, (2) masukkan ke rice cooker, (3) tekan tombol masak, (4) takar beras. Urutan yang benar adalah ...','{\"A\": \"1-2-3-4\", \"B\": \"4-1-2-3\", \"C\": \"4-2-1-3\", \"D\": \"1-4-2-3\"}','B','Beras ditakar dulu, dicuci, dimasukkan, lalu dimasak.',1,'2026-11-08 07:19:47'),
(4,1,'Informatika','8','Berpikir Komputasional','pg','sedang','C2','Memecah masalah besar menjadi bagian-bagian kecil disebut ...','{\"A\": \"Abstraksi\", \"B\": \"Pengenalan pola\", \"C\": \"Dekomposisi\", \"D\": \"Algoritma\"}','C','Dekomposisi = memecah masalah menjadi bagian kecil.',0,'2026-11-08 07:19:47'),
(5,1,'Informatika','8','Berpikir Komputasional','pg','sulit','C4','Petani sawit di Keritang mencatat hasil panen tiap minggu dan melihat panen naik setiap musim hujan. Kegiatan ini termasuk ...','{\"A\": \"Dekomposisi\", \"B\": \"Pengenalan pola\", \"C\": \"Abstraksi\", \"D\": \"Debugging\"}','B','Menemukan keteraturan pada data adalah pengenalan pola.',1,'2026-11-08 07:19:47'),
(6,1,'Informatika','8','Teknologi Informasi','pg','mudah','C1','Perangkat yang berfungsi memasukkan data ke komputer adalah ...','{\"A\": \"Monitor\", \"B\": \"Printer\", \"C\": \"Keyboard\", \"D\": \"Speaker\"}','C','Keyboard adalah perangkat input.',0,'2026-11-08 07:19:47'),
(7,1,'Informatika','8','Teknologi Informasi','bs','mudah','C1','RAM menyimpan data secara permanen walaupun komputer dimatikan.',NULL,'S','RAM bersifat sementara (volatile).',1,'2026-11-08 07:19:47'),
(8,1,'Informatika','8','Algoritma','isian','sedang','C2','Kesalahan dalam program disebut bug, sedangkan proses mencari dan memperbaikinya disebut ...',NULL,'debugging','',0,'2026-11-08 07:19:47'),
(9,1,'Informatika','8','Algoritma','esai','sulit','C6','Buatlah algoritma (minimal 6 langkah) untuk meminjam buku di perpustakaan sekolah!',NULL,'Langkah logis: datang, cari buku di katalog, ambil buku, ke meja petugas, tunjukkan kartu, buku dicatat/discan, terima buku & tanggal kembali.','Nilai penuh bila urut, lengkap, dan logis.',1,'2026-11-08 07:19:47'),
(10,1,'Informatika','8','Etika Digital','pg','sedang','C3','Temanmu membagikan berita yang belum jelas sumbernya di grup kelas. Sikap yang tepat adalah ...','{\"A\": \"Ikut menyebarkan\", \"B\": \"Mengecek kebenaran dari sumber terpercaya\", \"C\": \"Mengabaikan dan menyebarkan ke grup lain\", \"D\": \"Menghapus grup\"}','B','Saring sebelum sharing.',0,'2026-11-08 07:19:47');
/*!40000 ALTER TABLE `guru_soal` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_ujian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_ujian` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guru_id` int(11) NOT NULL,
  `paket_id` int(11) DEFAULT NULL,
  `judul` varchar(200) NOT NULL,
  `mapel` varchar(120) DEFAULT NULL,
  `kelas` varchar(50) NOT NULL,
  `tahun` varchar(9) DEFAULT NULL,
  `kode` varchar(10) NOT NULL,
  `rahasia` varchar(40) NOT NULL,
  `token_menit` smallint(6) NOT NULL DEFAULT 10,
  `mulai` datetime DEFAULT NULL,
  `selesai` datetime DEFAULT NULL,
  `durasi` smallint(6) NOT NULL DEFAULT 60,
  `acak_soal` tinyint(4) NOT NULL DEFAULT 1,
  `acak_opsi` tinyint(4) NOT NULL DEFAULT 1,
  `tampil_nilai` tinyint(4) NOT NULL DEFAULT 0,
  `status` varchar(10) NOT NULL DEFAULT 'draf',
  `soal` longtext DEFAULT NULL,
  `penilaian_id` int(11) DEFAULT NULL,
  `dibuat` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode` (`kode`),
  KEY `guru_id` (`guru_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_ujian` WRITE;
/*!40000 ALTER TABLE `guru_ujian` DISABLE KEYS */;
INSERT INTO `guru_ujian` VALUES
(1,1,1,'Ulangan Harian Informatika Bab 1','Informatika','VIII A','2026/2027','PZY7Y4','972206694dbb828ef63f0881dc720fbb',10,NULL,NULL,30,1,1,1,'tutup','[{\"tipe\":\"pg\",\"teks\":\"Urutan langkah logis untuk menyelesaikan suatu masalah disebut ...\",\"opsi\":{\"A\":\"Program\",\"B\":\"Algoritma\",\"C\":\"Data\",\"D\":\"Variabel\"},\"kunci\":\"B\",\"pembahasan\":\"Algoritma adalah langkah-langkah terurut dan logis untuk menyelesaikan masalah.\",\"maks\":1},{\"tipe\":\"pg\",\"teks\":\"Simbol flowchart berbentuk belah ketupat digunakan untuk ...\",\"opsi\":{\"A\":\"Mulai\\/selesai\",\"B\":\"Proses\",\"C\":\"Percabangan\\/keputusan\",\"D\":\"Input\\/output\"},\"kunci\":\"C\",\"pembahasan\":\"Belah ketupat menandakan keputusan dengan dua kemungkinan jawaban.\",\"maks\":1},{\"tipe\":\"pg\",\"teks\":\"Ibu menanak nasi: (1) cuci beras, (2) masukkan ke rice cooker, (3) tekan tombol masak, (4) takar beras. Urutan yang benar adalah ...\",\"opsi\":{\"A\":\"1-2-3-4\",\"B\":\"4-1-2-3\",\"C\":\"4-2-1-3\",\"D\":\"1-4-2-3\"},\"kunci\":\"B\",\"pembahasan\":\"Beras ditakar dulu, dicuci, dimasukkan, lalu dimasak.\",\"maks\":1},{\"tipe\":\"pg\",\"teks\":\"Memecah masalah besar menjadi bagian-bagian kecil disebut ...\",\"opsi\":{\"A\":\"Abstraksi\",\"B\":\"Pengenalan pola\",\"C\":\"Dekomposisi\",\"D\":\"Algoritma\"},\"kunci\":\"C\",\"pembahasan\":\"Dekomposisi = memecah masalah menjadi bagian kecil.\",\"maks\":1},{\"tipe\":\"pg\",\"teks\":\"Petani sawit di Keritang mencatat hasil panen tiap minggu dan melihat panen naik setiap musim hujan. Kegiatan ini termasuk ...\",\"opsi\":{\"A\":\"Dekomposisi\",\"B\":\"Pengenalan pola\",\"C\":\"Abstraksi\",\"D\":\"Debugging\"},\"kunci\":\"B\",\"pembahasan\":\"Menemukan keteraturan pada data adalah pengenalan pola.\",\"maks\":1},{\"tipe\":\"pg\",\"teks\":\"Perangkat yang berfungsi memasukkan data ke komputer adalah ...\",\"opsi\":{\"A\":\"Monitor\",\"B\":\"Printer\",\"C\":\"Keyboard\",\"D\":\"Speaker\"},\"kunci\":\"C\",\"pembahasan\":\"Keyboard adalah perangkat input.\",\"maks\":1},{\"tipe\":\"bs\",\"teks\":\"RAM menyimpan data secara permanen walaupun komputer dimatikan.\",\"opsi\":{\"A\":\"Benar\",\"B\":\"Salah\"},\"kunci\":\"B\",\"pembahasan\":\"RAM bersifat sementara (volatile).\",\"maks\":1},{\"tipe\":\"isian\",\"teks\":\"Kesalahan dalam program disebut bug, sedangkan proses mencari dan memperbaikinya disebut ...\",\"opsi\":[],\"kunci\":\"debugging\",\"pembahasan\":\"\",\"maks\":1},{\"tipe\":\"esai\",\"teks\":\"Buatlah algoritma (minimal 6 langkah) untuk meminjam buku di perpustakaan sekolah!\",\"opsi\":[],\"kunci\":\"Langkah logis: datang, cari buku di katalog, ambil buku, ke meja petugas, tunjukkan kartu, buku dicatat\\/discan, terima buku & tanggal kembali.\",\"pembahasan\":\"Nilai penuh bila urut, lengkap, dan logis.\",\"maks\":10},{\"tipe\":\"pg\",\"teks\":\"Temanmu membagikan berita yang belum jelas sumbernya di grup kelas. Sikap yang tepat adalah ...\",\"opsi\":{\"A\":\"Ikut menyebarkan\",\"B\":\"Mengecek kebenaran dari sumber terpercaya\",\"C\":\"Mengabaikan dan menyebarkan ke grup lain\",\"D\":\"Menghapus grup\"},\"kunci\":\"B\",\"pembahasan\":\"Saring sebelum sharing.\",\"maks\":1}]',4,'2026-11-09 10:52:00'),
(2,1,1,'Kuis Informatika (coba sebagai siswa)','Informatika','VII A','2026/2027','A5QZTZ','5225f2f9e7b53b73abc5b3d250c33631',0,NULL,'2026-11-20 13:30:00',20,1,1,0,'buka','[{\"tipe\":\"pg\",\"teks\":\"Urutan langkah logis untuk menyelesaikan suatu masalah disebut ...\",\"opsi\":{\"A\":\"Program\",\"B\":\"Algoritma\",\"C\":\"Data\",\"D\":\"Variabel\"},\"kunci\":\"B\",\"pembahasan\":\"Algoritma adalah langkah-langkah terurut dan logis untuk menyelesaikan masalah.\",\"maks\":1},{\"tipe\":\"pg\",\"teks\":\"Simbol flowchart berbentuk belah ketupat digunakan untuk ...\",\"opsi\":{\"A\":\"Mulai\\/selesai\",\"B\":\"Proses\",\"C\":\"Percabangan\\/keputusan\",\"D\":\"Input\\/output\"},\"kunci\":\"C\",\"pembahasan\":\"Belah ketupat menandakan keputusan dengan dua kemungkinan jawaban.\",\"maks\":1},{\"tipe\":\"pg\",\"teks\":\"Ibu menanak nasi: (1) cuci beras, (2) masukkan ke rice cooker, (3) tekan tombol masak, (4) takar beras. Urutan yang benar adalah ...\",\"opsi\":{\"A\":\"1-2-3-4\",\"B\":\"4-1-2-3\",\"C\":\"4-2-1-3\",\"D\":\"1-4-2-3\"},\"kunci\":\"B\",\"pembahasan\":\"Beras ditakar dulu, dicuci, dimasukkan, lalu dimasak.\",\"maks\":1},{\"tipe\":\"pg\",\"teks\":\"Memecah masalah besar menjadi bagian-bagian kecil disebut ...\",\"opsi\":{\"A\":\"Abstraksi\",\"B\":\"Pengenalan pola\",\"C\":\"Dekomposisi\",\"D\":\"Algoritma\"},\"kunci\":\"C\",\"pembahasan\":\"Dekomposisi = memecah masalah menjadi bagian kecil.\",\"maks\":1},{\"tipe\":\"pg\",\"teks\":\"Petani sawit di Keritang mencatat hasil panen tiap minggu dan melihat panen naik setiap musim hujan. Kegiatan ini termasuk ...\",\"opsi\":{\"A\":\"Dekomposisi\",\"B\":\"Pengenalan pola\",\"C\":\"Abstraksi\",\"D\":\"Debugging\"},\"kunci\":\"B\",\"pembahasan\":\"Menemukan keteraturan pada data adalah pengenalan pola.\",\"maks\":1},{\"tipe\":\"pg\",\"teks\":\"Perangkat yang berfungsi memasukkan data ke komputer adalah ...\",\"opsi\":{\"A\":\"Monitor\",\"B\":\"Printer\",\"C\":\"Keyboard\",\"D\":\"Speaker\"},\"kunci\":\"C\",\"pembahasan\":\"Keyboard adalah perangkat input.\",\"maks\":1},{\"tipe\":\"bs\",\"teks\":\"RAM menyimpan data secara permanen walaupun komputer dimatikan.\",\"opsi\":{\"A\":\"Benar\",\"B\":\"Salah\"},\"kunci\":\"B\",\"pembahasan\":\"RAM bersifat sementara (volatile).\",\"maks\":1},{\"tipe\":\"isian\",\"teks\":\"Kesalahan dalam program disebut bug, sedangkan proses mencari dan memperbaikinya disebut ...\",\"opsi\":[],\"kunci\":\"debugging\",\"pembahasan\":\"\",\"maks\":1},{\"tipe\":\"esai\",\"teks\":\"Buatlah algoritma (minimal 6 langkah) untuk meminjam buku di perpustakaan sekolah!\",\"opsi\":[],\"kunci\":\"Langkah logis: datang, cari buku di katalog, ambil buku, ke meja petugas, tunjukkan kartu, buku dicatat\\/discan, terima buku & tanggal kembali.\",\"pembahasan\":\"Nilai penuh bila urut, lengkap, dan logis.\",\"maks\":5},{\"tipe\":\"pg\",\"teks\":\"Temanmu membagikan berita yang belum jelas sumbernya di grup kelas. Sikap yang tepat adalah ...\",\"opsi\":{\"A\":\"Ikut menyebarkan\",\"B\":\"Mengecek kebenaran dari sumber terpercaya\",\"C\":\"Mengabaikan dan menyebarkan ke grup lain\",\"D\":\"Menghapus grup\"},\"kunci\":\"B\",\"pembahasan\":\"Saring sebelum sharing.\",\"maks\":1}]',NULL,'2026-11-09 10:57:52');
/*!40000 ALTER TABLE `guru_ujian` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `guru_ujian_peserta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `guru_ujian_peserta` (
  `ujian_id` int(11) NOT NULL,
  `kode` varchar(20) NOT NULL,
  `perangkat` varchar(64) DEFAULT NULL,
  `mulai` datetime DEFAULT NULL,
  `batas` datetime DEFAULT NULL,
  `selesai` datetime DEFAULT NULL,
  `urutan` text DEFAULT NULL,
  `jawaban` mediumtext DEFAULT NULL,
  `ragu` text DEFAULT NULL,
  `nomor` smallint(6) NOT NULL DEFAULT 0,
  `keluar` smallint(6) NOT NULL DEFAULT 0,
  `terakhir` datetime DEFAULT NULL,
  `skor` decimal(7,2) DEFAULT NULL,
  `nilai` decimal(5,2) DEFAULT NULL,
  `nilai_uraian` text DEFAULT NULL,
  `perlu_koreksi` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ujian_id`,`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `guru_ujian_peserta` WRITE;
/*!40000 ALTER TABLE `guru_ujian_peserta` DISABLE KEYS */;
INSERT INTO `guru_ujian_peserta` VALUES
(1,'9000000011','5f9e798d1c25b29ed9318ab1b824b042989201566ab6a1409b5161c8cd55ca21','2026-11-09 10:52:10','2026-11-09 11:22:10','2026-11-09 10:52:31','{\"s\":[6,9,3,5,8,1,7,4,2,0],\"o\":[[\"A\",\"C\",\"D\",\"B\"],[\"C\",\"B\",\"A\",\"D\"],[\"C\",\"D\",\"A\",\"B\"],[\"C\",\"B\",\"D\",\"A\"],[\"D\",\"A\",\"C\",\"B\"],[\"C\",\"A\",\"B\",\"D\"],[\"A\",\"B\"],[],[],[\"D\",\"C\",\"B\",\"A\"]]}','{\"0\":\"D\",\"2\":\"B\",\"3\":\"D\",\"5\":\"C\",\"6\":\"B\",\"7\":\"debugging\",\"8\":\"debugging\",\"9\":\"B\"}','{}',9,0,'2026-11-09 10:52:31',5.00,26.00,'{\"8\":{\"skor\":0,\"skor_ai\":0,\"catatan\":\"Jawaban tidak sesuai dengan pertanyaan tentang algoritma meminjam buku.\",\"oleh\":\"ai\",\"ragu\":false}}',0),
(1,'9000000012','a07e6a17b0972255f15aa56bb71f2a9cac01925d6ccb77e36fca0b03189b4563','2026-11-09 10:52:07','2026-11-09 11:22:07','2026-11-09 10:52:31','{\"s\":[9,1,3,4,2,5,6,0,7,8],\"o\":[[\"C\",\"D\",\"B\",\"A\"],[\"A\",\"C\",\"D\",\"B\"],[\"C\",\"D\",\"B\",\"A\"],[\"C\",\"B\",\"D\",\"A\"],[\"D\",\"A\",\"B\",\"C\"],[\"A\",\"D\",\"B\",\"C\"],[\"A\",\"B\"],[],[],[\"B\",\"A\",\"C\",\"D\"]]}','{\"2\":\"C\",\"4\":\"B\",\"6\":\"B\",\"7\":\"1. datang ke perpustakaan 2. cari buku di katalog 3. bawa ke petugas 4. tunjukkan kartu anggota 5. petugas mencatat 6. terima buku dan tanggal kembali\",\"8\":\"1. datang ke perpustakaan\\n2. cari buku di katalog\\n3. bawa ke petugas\\n4. tunjukkan kartu anggota\\n5. petugas mencatat\\n6. terima buku dan tanggal kembali\",\"9\":\"B\"}','{}',9,0,'2026-11-09 10:52:31',13.00,68.00,'{\"7\":{\"skor\":0,\"skor_ai\":0,\"catatan\":\"Siswa menjawab soal nomor 8 dengan jawaban untuk nomor 9.\",\"oleh\":\"ai\",\"ragu\":false},\"8\":{\"skor\":10,\"skor_ai\":10,\"catatan\":\"Langkah-langkah algoritma lengkap, urut, dan sangat logis.\",\"oleh\":\"ai\",\"ragu\":false}}',0),
(1,'9000000013','3bb45fe319371fda70c004b14e30be2a51da1c875e0ae9e2757352517c543fc1','2026-11-09 10:52:06','2026-11-09 11:22:06','2026-11-09 10:52:30','{\"s\":[6,2,4,0,5,3,8,1,7,9],\"o\":[[\"D\",\"A\",\"B\",\"C\"],[\"C\",\"D\",\"B\",\"A\"],[\"B\",\"C\",\"D\",\"A\"],[\"D\",\"C\",\"A\",\"B\"],[\"D\",\"B\",\"A\",\"C\"],[\"D\",\"C\",\"B\",\"A\"],[\"A\",\"B\"],[],[],[\"C\",\"B\",\"A\",\"D\"]]}','{\"0\":\"D\",\"1\":\"C\",\"2\":\"C\",\"3\":\"A\",\"4\":\"C\",\"5\":\"D\",\"6\":\"A\",\"7\":\"mencari kesalahan program\",\"8\":\"mencari kesalahan program\",\"9\":\"C\"}','{}',9,1,'2026-11-09 10:52:30',1.00,5.00,'{\"7\":{\"skor\":0,\"skor_ai\":0,\"catatan\":\"Jawaban siswa salah karena menyebutkan definisinya, bukan istilah prosesnya (debugging).\",\"oleh\":\"ai\",\"ragu\":false},\"8\":{\"skor\":0,\"skor_ai\":0,\"catatan\":\"Jawaban siswa salah\\/salah salin karena menjawab soal nomor 8, bukan membuat algoritma peminjaman buku.\",\"oleh\":\"ai\",\"ragu\":false}}',0),
(1,'9000000014','cbf9027ff41dcf6a40207138beec7cfbca8bb00ea8e8ed2399f645d96d52ed7a','2026-11-09 10:52:12','2026-11-09 11:22:12','2026-11-09 10:52:52','{\"s\":[2,0,3,7,5,8,4,1,9,6],\"o\":[[\"B\",\"C\",\"A\",\"D\"],[\"B\",\"C\",\"D\",\"A\"],[\"B\",\"D\",\"C\",\"A\"],[\"B\",\"D\",\"A\",\"C\"],[\"A\",\"B\",\"C\",\"D\"],[\"C\",\"B\",\"A\",\"D\"],[\"A\",\"B\"],[],[],[\"C\",\"A\",\"D\",\"B\"]]}','{\"0\":\"B\",\"1\":\"C\",\"2\":\"D\",\"3\":\"A\",\"4\":\"B\",\"5\":\"B\",\"6\":\"A\",\"7\":\"mencari kesalahan program\",\"8\":\"1. datang ke perpustakaan\\n2. cari buku di katalog\\n3. bawa ke petugas\\n4. tunjukkan kartu anggota\\n5. petugas mencatat\\n6. terima buku dan tanggal kembali\",\"9\":\"B\"}','{\"7\":1}',9,0,'2026-11-09 10:52:21',14.00,74.00,'{\"7\":{\"skor\":0,\"skor_ai\":0,\"catatan\":\"Siswa mendefinisikan prosesnya, bukan istilah (debugging) yang diminta.\",\"oleh\":\"ai\",\"ragu\":false},\"8\":{\"skor\":10,\"skor_ai\":10,\"catatan\":\"Langkah lengkap, logis, dan sesuai dengan minimal 6 langkah yang diminta.\",\"oleh\":\"ai\",\"ragu\":false}}',0),
(1,'9000000015','f2b4f8f4d24937a1d8a379923fe927b108dbc4771766c9782dc68869c40e87b7','2026-11-09 10:52:03','2026-11-09 11:22:03','2026-11-09 10:52:30','{\"s\":[2,1,4,3,5,7,9,0,6,8],\"o\":[[\"A\",\"C\",\"B\",\"D\"],[\"B\",\"D\",\"A\",\"C\"],[\"A\",\"D\",\"B\",\"C\"],[\"D\",\"C\",\"A\",\"B\"],[\"B\",\"D\",\"C\",\"A\"],[\"D\",\"C\",\"B\",\"A\"],[\"A\",\"B\"],[],[],[\"C\",\"D\",\"B\",\"A\"]]}','{\"0\":\"D\",\"1\":\"B\",\"2\":\"C\",\"3\":\"D\",\"4\":\"B\",\"5\":\"B\",\"6\":\"B\",\"7\":\"1. datang ke perpustakaan 2. cari buku di katalog 3. bawa ke petugas 4. tunjukkan kartu anggota 5. petugas mencatat 6. terima buku dan tanggal kembali\",\"8\":\"Debugging\",\"9\":\"A\"}','{}',9,0,'2026-11-09 10:52:30',2.00,11.00,'{\"7\":{\"skor\":0,\"skor_ai\":0,\"catatan\":\"Tertukar dengan jawaban soal nomor 9.\",\"oleh\":\"ai\",\"ragu\":false},\"8\":{\"skor\":0,\"skor_ai\":0,\"catatan\":\"Tertukar dengan jawaban soal nomor 8, bukan algoritma meminjam buku.\",\"oleh\":\"ai\",\"ragu\":false}}',0),
(1,'9000000016','08edd2d63ac4c66aad8de9c66b4ce112687f28108147b55ec75afc022ad5a40c','2026-11-09 10:52:09','2026-11-09 11:22:09','2026-11-09 10:52:31','{\"s\":[4,6,8,3,0,5,9,2,7,1],\"o\":[[\"A\",\"D\",\"C\",\"B\"],[\"D\",\"C\",\"A\",\"B\"],[\"A\",\"B\",\"C\",\"D\"],[\"C\",\"A\",\"D\",\"B\"],[\"A\",\"D\",\"C\",\"B\"],[\"B\",\"D\",\"C\",\"A\"],[\"A\",\"B\"],[],[],[\"B\",\"A\",\"D\",\"C\"]]}','{\"1\":\"C\",\"2\":\"D\",\"3\":\"A\",\"4\":\"B\",\"5\":\"C\",\"6\":\"A\",\"7\":\"mencari kesalahan program\",\"8\":\"mencari kesalahan program\",\"9\":\"D\"}','{\"3\":1}',9,0,'2026-11-09 10:52:31',3.00,16.00,'{\"7\":{\"skor\":0,\"skor_ai\":0,\"catatan\":\"Jawaban siswa salah karena menyebutkan prosesnya, bukan istilah yang ditanyakan (debugging).\",\"oleh\":\"ai\",\"ragu\":false},\"8\":{\"skor\":0,\"skor_ai\":0,\"catatan\":\"Siswa salah menjawab, isi jawaban sama dengan soal nomor 8 dan tidak sesuai dengan perintah membuat algoritma meminjam buku.\",\"oleh\":\"ai\",\"ragu\":false}}',0),
(1,'9000000017','f6ced24189c89d742a8c38202f5d75d73fd07717db39e9b7cd022c8aa4e1cea5','2026-11-09 10:52:11','2026-11-09 11:22:11','2026-11-09 10:52:52','{\"s\":[7,0,9,6,8,3,2,4,5,1],\"o\":[[\"D\",\"B\",\"C\",\"A\"],[\"A\",\"D\",\"C\",\"B\"],[\"C\",\"D\",\"B\",\"A\"],[\"A\",\"D\",\"B\",\"C\"],[\"C\",\"B\",\"D\",\"A\"],[\"C\",\"B\",\"A\",\"D\"],[\"A\",\"B\"],[],[],[\"C\",\"B\",\"D\",\"A\"]]}','{\"0\":\"C\",\"1\":\"C\",\"3\":\"A\",\"4\":\"B\",\"5\":\"B\",\"6\":\"B\",\"7\":\"Debugging\",\"8\":\"debugging\",\"9\":\"D\"}','{}',9,0,'2026-11-09 10:52:20',4.00,21.00,'{\"8\":{\"skor\":0,\"skor_ai\":0,\"catatan\":\"Jawaban \'debugging\' tidak sesuai dengan konteks peminjaman buku di perpustakaan.\",\"oleh\":\"ai\",\"ragu\":false}}',0),
(1,'9000000018','a865160d0a2082ee7d6ae5eb6b415ea7eaa9a2372508c6f9cd5fdef7f45f187b','2026-11-09 10:52:05','2026-11-09 11:22:05','2026-11-09 10:52:30','{\"s\":[9,2,0,4,3,8,1,7,6,5],\"o\":[[\"A\",\"D\",\"C\",\"B\"],[\"C\",\"B\",\"D\",\"A\"],[\"A\",\"D\",\"C\",\"B\"],[\"C\",\"B\",\"A\",\"D\"],[\"B\",\"D\",\"C\",\"A\"],[\"B\",\"D\",\"A\",\"C\"],[\"A\",\"B\"],[],[],[\"A\",\"B\",\"D\",\"C\"]]}','{\"0\":\"C\",\"1\":\"B\",\"2\":\"D\",\"5\":\"C\",\"6\":\"A\",\"7\":\"mencari kesalahan program\",\"8\":\"debugging\"}','{\"4\":1}',9,0,'2026-11-09 10:52:30',1.00,5.00,'{\"7\":{\"skor\":0,\"skor_ai\":0,\"catatan\":\"Siswa menjawab definisi prosesnya, bukan istilah spesifik yang diminta (debugging).\",\"oleh\":\"ai\",\"ragu\":false},\"8\":{\"skor\":0,\"skor_ai\":0,\"catatan\":\"Jawaban tertukar dengan soal nomor 8.\",\"oleh\":\"ai\",\"ragu\":false}}',0),
(1,'9000000019','72fd2c112e6a4401e64ed2172c50d6524f2ab18d8c67e80f31d2618a9130c5f1','2026-11-09 10:52:02','2026-11-09 11:22:02','2026-11-09 10:52:29','{\"s\":[7,3,1,2,6,0,9,4,5,8],\"o\":[[\"D\",\"C\",\"A\",\"B\"],[\"D\",\"C\",\"A\",\"B\"],[\"C\",\"B\",\"A\",\"D\"],[\"A\",\"B\",\"C\",\"D\"],[\"D\",\"B\",\"C\",\"A\"],[\"D\",\"A\",\"B\",\"C\"],[\"A\",\"B\"],[],[],[\"A\",\"D\",\"B\",\"C\"]]}','{\"0\":\"C\",\"1\":\"B\",\"2\":\"C\",\"3\":\"B\",\"4\":\"A\",\"5\":\"A\",\"6\":\"B\",\"7\":\"Debugging\",\"8\":\"Debugging\",\"9\":\"C\"}','{\"2\":1}',9,0,'2026-11-09 10:52:29',2.00,11.00,'{\"8\":{\"skor\":0,\"skor_ai\":0,\"catatan\":\"Jawaban tidak sesuai karena berisi istilah pemrograman \'Debugging\' bukan algoritma meminjam buku.\",\"oleh\":\"ai\",\"ragu\":false}}',0),
(1,'9000000020','e33f3e6129512eeaae7d00e8e245374c6aadab581b3fc2b2dae85f18a6146e8b','2026-11-09 10:52:04','2026-11-09 11:22:04','2026-11-09 10:52:30','{\"s\":[5,0,4,2,7,6,9,1,3,8],\"o\":[[\"D\",\"C\",\"B\",\"A\"],[\"A\",\"D\",\"C\",\"B\"],[\"B\",\"C\",\"A\",\"D\"],[\"C\",\"A\",\"B\",\"D\"],[\"C\",\"A\",\"B\",\"D\"],[\"C\",\"D\",\"A\",\"B\"],[\"A\",\"B\"],[],[],[\"B\",\"A\",\"C\",\"D\"]]}','{\"0\":\"A\",\"1\":\"C\",\"2\":\"A\",\"3\":\"C\",\"4\":\"D\",\"5\":\"C\",\"6\":\"B\",\"7\":\"1. datang ke perpustakaan 2. cari buku di katalog 3. bawa ke petugas 4. tunjukkan kartu anggota 5. petugas mencatat 6. terima buku dan tanggal kembali\",\"8\":\"mencari kesalahan program\",\"9\":\"D\"}','{}',9,0,'2026-11-09 10:52:30',4.00,21.00,'{\"7\":{\"skor\":0,\"skor_ai\":0,\"catatan\":\"Siswa salah menempatkan jawaban; jawaban untuk soal nomor 8 tertulis pada nomor 9.\",\"oleh\":\"ai\",\"ragu\":false},\"8\":{\"skor\":0,\"skor_ai\":0,\"catatan\":\"Siswa salah menempatkan jawaban; jawaban untuk soal nomor 9 tertulis pada nomor 8.\",\"oleh\":\"ai\",\"ragu\":false}}',0);
/*!40000 ALTER TABLE `guru_ujian_peserta` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


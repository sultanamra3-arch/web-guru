# Panduan untuk agen AI (Freebuff, Claude, dll.) — SiGuru

## 1. Bahasa & cara berkomunikasi (WAJIB)
- **Selalu jawab dalam Bahasa Indonesia** yang sederhana dan mudah dipahami orang awam. Pengguna (Pak Sultan Amra, guru SMP Negeri 6 Keritang) bukan programmer.
- Jangan memakai istilah teknis bahasa Inggris tanpa penjelasan singkat. Contoh: tulis "kirim ke GitHub (push)", bukan hanya "push".
- Sebelum mengubah file, **jelaskan dulu dengan singkat apa yang akan diubah dan kenapa**. Untuk perubahan besar (fitur baru, database, ujian, nilai), **tanya dulu dan tunggu persetujuan** ("buat").
- Setelah selesai, laporkan singkat: apa yang berubah, cara mencobanya, dan langkah memasang di website.
- Jawaban singkat dan jelas; pengguna sering membaca di HP.

## 2. Tentang aplikasi
SiGuru = aplikasi web untuk guru (PHP 8.2 + MySQL/MariaDB, tanpa framework, tanpa Composer/npm).
Dipasang di cPanel lewat Git (subdomain `guru.otomasismpn6keritang.my.id`). Memakai database yang sama dengan perpustakaan SLiMS, tetapi **hanya tabel berawalan `guru_`**. Jangan menyentuh tabel SLiMS.

Struktur:
- `index.php` — router (`?p=nama_halaman`), daftar halaman yang diizinkan, `HALAMAN_UMUM` (halaman siswa tanpa login).
- `hal/` — satu file per halaman (mis. `hal/kuis.php`), termasuk API (`hal/api_*.php`).
- `inc/` — fungsi bersama: `inti.php` (VERSI, database, helper), `skema.php` (tabel & migrasi), `tampilan.php` (kepala/kaki, menu, panel bagikan), `ikon.php` (ikon garis Lucide), `ai.php` & `guru_ai.php` (AI), `siswa.php`, `ujian.php`, `tugas.php`, `kuis.php`, `koreksi.php`, `gambar.php`, `asisten.php`.
- `aset/` — CSS (`gaya.css`, `guru.css`) dan JavaScript.
- `u/`, `t/`, `k/` — alamat pendek untuk siswa (ujian, tugas, kuis).
- `data/` — berkas di server (kunci AI, gambar soal, foto tugas). **Tidak ikut Git dan jangan pernah dimasukkan ke Git.**

## 3. Aturan wajib setiap perubahan
1. **Mulai dengan `git pull`** — repo ini juga diubah oleh agen lain.
2. **Jangan pernah commit kunci API, sandi, `config.php`, atau isi folder `data/`.** Kunci AI hanya ada di server (`data/ai-kunci.php`).
3. **Setiap perubahan yang dikirim ke GitHub: naikkan versi di DUA tempat sekaligus**:
   - `inc/inti.php` → `const VERSI = '1.xx';`
   - `sw.js` → `const CACHE = 'siguru-v1.xx';` (angkanya harus sama dengan VERSI)
   Kalau `sw.js` lupa dinaikkan, HP yang memasang aplikasi bisa terus menampilkan halaman lama.
   (Sejak v1.36 daftar berkas yang disimpan di HP (`ASET` di `sw.js`) otomatis memakai angka versi ini.)
4. **Perubahan database hanya lewat `inc/skema.php`**: tambah tabel di daftar `CREATE TABLE IF NOT EXISTS`, kolom baru lewat fungsi migrasi (pakai `kolom_ada()`), lalu naikkan `SKEMA_VERSI`. Migrasi berjalan otomatis saat halaman dibuka. Jangan memberi pengguna perintah SQL manual kecuali terpaksa.
5. Data selalu milik akun yang login: setiap kueri data guru wajib memakai `guru_id = uid()` (atau dicek lewat pemiliknya). Guru tidak boleh melihat/mengubah data guru lain.
6. Keamanan: pakai `q()` / `satu()` / `semua()` dengan parameter `?` (jangan menempel input ke SQL), keluaran HTML lewat `h()`, formulir POST wajib `input_token()` (CSRF).
7. Periksa sebelum push: `php -l` untuk file PHP yang diubah, `node --check` untuk JS yang diubah.
8. Pesan commit dalam Bahasa Indonesia, diawali versi, mis. `v1.27: ...`.
9. **Halaman disiapkan lebih awal** (v1.36, `siap_cepat_aturan()` di `inc/tampilan.php`, Speculation Rules): tautan `?p=...` di halaman guru dimuat di latar saat jari menyentuh tautan. Karena itu **tautan GET tidak boleh mengubah data**. Kalau terpaksa ada tautan yang mengubah data, mengunduh, atau mencetak, tambahkan atribut `data-tanpa-siap` pada `<a>` atau tambahkan polanya ke daftar `$tolak`.
10. Huruf Quicksand disimpan sendiri di `aset/huruf/` (jangan kembali memakai Google Fonts).

## 4. Gaya tampilan
- Semua teks untuk pengguna dalam Bahasa Indonesia.
- **Harus rapi di HP** (lebar 320–412 px): tidak boleh ada halaman yang bisa digeser ke samping.
- Ikon di dalam aplikasi pakai `ikon('nama')` dari `inc/ikon.php` (ikon garis), bukan emoji di menu/tombol utama.
- Jam memakai format 24 jam (`07:30`, `13:45`), **tanpa AM/PM**.
- Warna: navy `#2A2C84`, emas `#F2C230`, hijau `#2E9E48`. Font Quicksand.

## 5. Keputusan pengguna yang harus dipertahankan
- Data siswa **tidak** disambungkan ke perpustakaan. **Kelas, daftar siswa, jadwal, dan jam pelajaran milik masing-masing guru.**
- Soal & paket soal **milik masing-masing akun** (tidak ada fitur berbagi soal). Soal yang sudah masuk paket tidak tampil di bank soal; diubah lewat halaman paket.
- Pengaturan AI hanya untuk admin; kunci AI dipakai semua akun. Asisten AI hanya untuk admin dan hanya mengubah data admin sendiri lewat kartu "Simpan".
- AI memakai model gratis (OpenRouter hanya model `:free`).
- Absensi yang belum diisi **tidak boleh** tampil sebagai "hadir"; hari yang tidak diisi tidak dihitung di rekap.
- Ujian online: tata tertib sebelum mulai, pengaman bisa ON/OFF, mode latihan, hasil & pembahasan setelah ujian ditutup, tombol bagikan WA (token opsional).
- Ujian online (v1.37): **kunci lanjut** (`kunci_lanjut` di `guru_ujian_peserta`, hash; kunci asli hanya di HP siswa/localStorage `sgu-kunci-ID`). Bila sesi/cookie di HP hilang, `api_u` menyambung lagi otomatis memakai kunci ini, dan halaman pilih nama tidak meminta token/"Buka ulang" untuk HP yang sama. `api_u` memeriksa tokennya sendiri (dikecualikan dari `cek_token()` di `index.php`). Tombol "Ya, kumpulkan" tidak boleh diam: bila sesi tidak bisa disambung, siswa diarahkan masuk lagi.
- Kelompok belajar (v1.38): tabel `guru_kelompok` (guru_id, tahun, kelas, kode → nomor kelompok), halaman `?p=kelompok` (acak dengan L/P dicampur, atau atur sendiri). Di halaman nilai, kotak "Nilai per kelompok" hanya alat bantu JavaScript: mengisi nilai semua anggota sekaligus; nilai tetap disimpan per siswa di `guru_nilai` sehingga anggota yang tidak ikut bekerja bisa diberi nilai berbeda.
- Kuis cepat: yang salah/tidak menjawab tetap lanjut sampai habis; **jawaban, poin, dan peringkat baru dibuka di akhir**. Mode mandiri: siswa maju sendiri-sendiri, soal & pilihan diacak per siswa.
- Data contoh di repo hanya nama siswa fiktif.

## 6. Cara memasang perubahan di website
Setelah commit & push ke GitHub: cPanel → **Git Version Control** → Manage `web-guru` → **Pull or Deploy** → **Update from Remote**. Cek tulisan versi di bagian bawah halaman SiGuru.

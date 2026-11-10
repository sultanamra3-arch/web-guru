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
- `inc/` — fungsi bersama: `inti.php` (VERSI, database, helper), `skema.php` (tabel & migrasi), `tampilan.php` (kepala/kaki, menu, panel bagikan), `ikon.php` (ikon garis Lucide), `ai.php` & `guru_ai.php` (AI), `siswa.php`, `ujian.php`, `tugas.php`, `resi.php`, `otomatis.php`, `beranda.php`, `sekolah.php`, `kuis.php`, `koreksi.php`, `gambar.php`, `asisten.php`.
- `aset/` — CSS (`gaya.css`, `guru.css`) dan JavaScript.
- `u/`, `t/`, `k/`, `r/` — alamat pendek untuk siswa (ujian, tugas, kuis, resi).
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
- Tugas online kelompok (v1.39): `guru_tugas.kelompok = 1`. Satu baris kiriman per kelompok di `guru_tugas_kirim` dengan kode `K<nomor>` (`oleh` = siswa yang terakhir mengirim). Anggota diambil dari `guru_kelompok` (fungsi `tugas_kode_kirim()`, `tugas_anggota()` di `inc/tugas.php`). Semua anggota kelompok boleh melihat foto & mengirim ulang/menambah foto (foto lama yang dicentang dipertahankan). Saat dikirim ke Penilaian, nilai kelompok ditulis ke setiap anggota. Jenis tugas tidak bisa diganti setelah ada kiriman.
- Resi tugas & ujian (v1.40): kolom `resi` (unik, 10 huruf: T… tugas, U… ujian) di `guru_tugas_kirim` & `guru_ujian_peserta`, dibuat oleh `resi_tugas()` / `resi_ujian()` di `inc/resi.php`. Halaman umum tanpa login `?p=resi&k=KODE` (alamat pendek `/r/?k=`), memeriksa perubahan tiap 30 detik (`&sig=1`) lalu memuat ulang sendiri. Kode resi = kunci akses, hanya menampilkan kiriman/peserta itu. Kotak resi (Lihat resi + Kirim ke WA) muncul untuk siswa setelah mengirim tugas (hanya di HP pengirim; tugas kelompok: semua anggota) dan di layar selesai ujian. Guru punya tombol "Salin resi" (`data-salin`). Nilai ujian di resi mengikuti `tampil_nilai` / hasil terbuka — jangan dibuka lebih dari itu.
- Hasil & rekap (v1.41): halaman resi juga menjadi **lembar hasil** yang bisa disimpan PDF (`window.print`, kop `.kop-pdf-resi`). Tugas: setelah dinilai tampil soal, foto jawaban (dibuka lewat `tugas_foto&r=RESI`), nilai, komentar, dan **pembahasan guru** (`guru_tugas.pembahasan`, `pembahasan_gambar`, `buka_bahas` = 'tutup' setelah tugas ditutup / 'nilai' setelah dinilai). Ujian: pembahasan per soal (`ujian_pembahasan_html()` di `inc/ujian.php`, dipakai juga oleh `u_kerja`) hanya bila `ujian_hasil_terbuka()`. Guru: `?p=rekap_cetak&jenis=tugas|ujian&id=` = lembar rekap kelas siap cetak/PDF (tombol "Rekap kelas (PDF)" di halaman tugas & pantau ujian).
- Otomatis (v1.42, `inc/otomatis.php`, dipanggil `otomatis_jalankan()` di `index.php` tiap permintaan, bagian tutup paling sering 60 detik — tanpa cron): ujian ditutup otomatis bila batas akhir lewat & tak ada yang masih mengerjakan, atau (tanpa batas akhir, bukan latihan) semua siswa sudah mengumpulkan; tugas ditutup otomatis saat batas lewat hanya bila `terima_telat = 0`; bisa dimatikan per ujian/tugas (`tutup_otomatis`). Menutup ujian selalu lewat `ujian_tutup()`. Nilai ujian yang ditutup masuk Penilaian sekali (`nilai_masuk`), setelah uraian dikoreksi, untuk guru yang sedang login (`otomatis_nilai(uid())`); ujian lama yang sudah tutup ditandai `nilai_masuk = 1` saat migrasi agar nilai yang sudah diubah guru tidak tertimpa. Ujian susulan = `tindak = 'susulan'` (salinan ujian asal, khusus siswa yang belum ikut; nilai ke Penilaian ujian asal). Formulir ujian baru mengisi jam dari jadwal mengajar (`jadwal_blok_kelas()`), tanpa jadwal batas akhir 23:59. Beranda: pengingat "terbuka ≥3 hari" + tombol Tutup. HP siswa: peringatan 5 menit sebelum waktu habis.
- QR (v1.43): elemen `data-qr="alamat"` diisi gambar QR oleh `aset/qr-isi.js` (muat `aset/lib/qrcode.js` dulu lewat `skrip_tambahan`); `data-buka-qr="id-panel"` = tombol buka/tutup panel QR. Dipakai di halaman resi (tombol "Tunjukkan QR" + QR di kop PDF hasil) dan kop rekap kelas (QR ke halaman guru).
- Tata letak (v1.44): menu bawah = Beranda (angka "perlu tindakan", `angka_beranda()` disimpan di sesi 60 detik), Kelas, **＋ Buat** (lembar `#lembar-buat`, isi `MENU_BUAT`), Perangkat, Lainnya. **Soal ada di tab Perangkat** (`tab_perangkat()`; halaman soal memanggil `kepala(..., 'soal')` yang dipetakan ke Perangkat). Pojok kanan atas = inisial nama → profil; Keluar hanya di Lainnya. Beranda: cari → Perlu tindakan (`perlu_tindakan()` di `inc/beranda.php`) → Sekarang/Berikutnya dari jadwal (`jadwal_hari_ini()`, tombol Absen/Tugas/Kuis/Ujian membawa `kelas`) → Sedang berjalan → Pintasan → Perangkat terakhir; 2 kolom di layar lebar. Halaman kelas `?p=kelas_lihat&kelas=`. Pencarian `?p=cari&q=`. Ukuran huruf per guru: `setelan('huruf:<uid>')` = besar → `html.huruf-besar`. Daftar ujian/tugas dibagi Berjalan/Draf/Selesai (`&st=`).
- Kotak "Sekolah" khusus admin (v1.46, `inc/sekolah.php`): Beranda admin menampilkan ringkasan perpustakaan dari tabel SLiMS (`loan`, `visitor_count`, `item`, `member`) — **HANYA SELECT, jangan pernah menulis ke tabel SLiMS** — dan tombol ke SiPustaka, SLiMS, Kartu, Buku tamu, Katalog (`APLIKASI_SEKOLAH`, alamat bisa diganti lewat config `url_*`). Guru bukan admin tidak melihatnya. "SiPustaka tanpa login ulang": `?p=ke_pustaka` membuat alamat `?p=sso` bertanda tangan HMAC (kunci bersama `sso-kunci.php` di folder data SiPustaka, dibuat otomatis; berlaku 60 detik, sekali pakai) untuk akun SLiMS yang diatur di Profil (`setelan('slims_user:<uid>')`, bawaan = username SiGuru bila ada di tabel `user`). Pasangannya `hal/sso.php` di repo SiPustaka v1.12.
- APK "SiGuru Admin": sejak v1.50 (APK 1.1) aplikasi **WebView** biasa (sumber `android/aplikasi/`; TWA v1.47–1.49 tidak mau terbuka di HP pengguna). Semua `*.otomasismpn6keritang.my.id` dibuka di dalam aplikasi, `window.print()` → cetak/PDF Android, user agent berisi `SiGuruAdminApp`; detail di `android/BACA-SAYA.md`. `.well-known/assetlinks.json` tidak lagi wajib (biarkan). File APK di `apk/` (diblokir `apk/.htaccess`; **jangan ubah `.htaccess` utama** — cPanel sering mengubahnya di server sehingga "Update from Remote" gagal) hanya diunduh admin lewat `?p=apk&unduh=1`. **Kunci tanda tangan TIDAK di repo** (disimpan Pak Sultan). Tombol aplikasi sekolah dibuka di jendela yang sama dengan `?dari=siguru` (memunculkan tombol "‹ SiGuru" di aplikasi tujuan). Tanpa efek memantul di semua mode (`html,body{overscroll-behavior:none}`) — permintaan pengguna untuk semua aplikasi.
- Kuis cepat: yang salah/tidak menjawab tetap lanjut sampai habis; **jawaban, poin, dan peringkat baru dibuka di akhir**. Mode mandiri: siswa maju sendiri-sendiri, soal & pilihan diacak per siswa.
- Data contoh di repo hanya nama siswa fiktif.

## 6. Cara memasang perubahan di website
Setelah commit & push ke GitHub: cPanel → **Git Version Control** → Manage `web-guru` → **Pull or Deploy** → **Update from Remote**. Cek tulisan versi di bagian bawah halaman SiGuru.

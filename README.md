# SiGuru — Asisten kerja guru SMP Negeri 6 Keritang

Aplikasi web (bisa dipasang di HP) untuk guru:

| Fitur | Keterangan |
|---|---|
| ✨ Generator soal AI | PG, benar/salah, isian, uraian; level C1–C6; bisa dari teks/foto materi |
| 📝 Bank soal | simpan, cari, bagikan ke guru lain, jadikan **paket soal** (cetak/Word, dengan kunci) |
| ✨ Modul ajar | struktur Perencanaan Pembelajaran Mendalam (Kurikulum Merdeka), cetak/Word, revisi dengan AI |
| ✨ LKPD | siap cetak, dengan pedoman guru |
| ✨ Presentasi | tayang layar penuh di browser + unduh PowerPoint (.pptx) |
| ✅ Absensi | H/S/I/A per kelas & tanggal, rekap bulanan, unduh Excel |
| 📊 Penilaian | input nilai, KKTP, rekap per mapel, unduh Excel, cetak |
| 🗓 Jadwal | jadwal mengajar per hari, tampil di beranda |
| 🏅 Sertifikat | A4 mendatar, banyak penerima sekaligus (dari kelas atau ketik nama) |

- Tiap guru punya akun sendiri (dibuat admin).
- Data siswa & kelas diambil dari anggota perpustakaan SLiMS (`member.inst_name`); kelas diisi lewat SiPustaka.
- Tabel SiGuru berawalan `guru_` di database yang sama; dibuat otomatis saat pertama dibuka. Tabel SLiMS tidak diubah.
- AI gratis (Gemini, Groq, OpenRouter) memakai kunci yang sama dengan SiPustaka; **kunci tidak disimpan di GitHub**.

## Coba dulu (sebelum dipasang)

- **Pratinjau** (lihat tampilan, data contoh, tidak bisa menyimpan, AI nonaktif):
  https://sultanamra3-arch.github.io/web-guru/
- **Versi lengkap di Codespaces** (semua fitur jalan, data contoh, gratis ±60 jam/bulan):
  [![Buka di GitHub Codespaces](https://github.com/codespaces/badge.svg)](https://codespaces.new/sultanamra3-arch/web-guru?quickstart=1)
  Tunggu persiapan, buka tab **PORTS** → **SiGuru (8080)** → 🌐, lalu masuk **admin / admin123**.
  Petunjuk: `.devcontainer/BACA-SAYA.md`. Matikan Codespace setelah selesai.

Semua nama siswa di data contoh adalah fiktif.

## Pasang di hosting (cPanel) dari GitHub

1. **Tarik kode dari GitHub dulu**: cPanel → **Git™ Version Control** → *Create*
   - *Clone a Repository*: aktif
   - *Clone URL*: `https://github.com/sultanamra3-arch/web-guru.git`
     (repositori privat: `https://<TOKEN>@github.com/sultanamra3-arch/web-guru.git`; token dibuat di
     GitHub → Settings → Developer settings → Fine-grained tokens, akses *Contents: Read-only* ke repo ini saja)
   - *Repository Path*: `/home/bzydmpxm/guru.otomasismpn6keritang.my.id` (folder ini **belum boleh ada**)
   - Klik *Create*.
2. **Baru buat subdomain**: cPanel → Domains → `guru.otomasismpn6keritang.my.id`,
   document root diisi folder yang sama: `/home/bzydmpxm/guru.otomasismpn6keritang.my.id`.
3. **PHP 8.2** untuk subdomain (MultiPHP Manager).
4. Buka `https://guru.otomasismpn6keritang.my.id` → buat **akun admin** pertama → menu Lainnya → Akun guru.
5. (Opsional) buat `config.php` di folder subdomain (salin dari `config.contoh.php`) untuk mengisi nama &
   NIP kepala sekolah, tahun ajaran, atau koneksi database manual.

### Memperbarui
cPanel → Git Version Control → *Manage* → tab *Pull or Deploy* → **Update from Remote**.
`config.php` dan folder `data/` tidak tersentuh.

## Struktur
```
index.php        router
inc/             inti, database, AI, skema tabel, tampilan
hal/             halaman
aset/            CSS, JS, logo, pptxgenjs (MIT)
data/            berkas server (tidak masuk Git)
```
Pustaka pihak ketiga: Parsedown 1.7.4 (MIT), PptxGenJS 3.12.0 (MIT).

## Memperbarui pratinjau (pengembang)

Jalankan SiGuru dengan database contoh di `http://127.0.0.1:8092/`, lalu
`python3 .github/pratinjau/ekspor.py <folder-keluaran> <folder-siguru>` dan unggah hasilnya ke cabang `gh-pages`.

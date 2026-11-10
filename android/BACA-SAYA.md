# APK "SiGuru Admin" (v1.47)

Aplikasi Android pembungkus Chrome asli (Trusted Web Activity) untuk admin. Layar penuh tanpa bilah alamat untuk:
guru.*, kelola.*, otomasismpn6keritang.my.id (SLiMS), kartu.*, bukutamu.*, katalog.* — masing-masing wajib punya
`/.well-known/assetlinks.json` (isi sama dengan `.well-known/assetlinks.json` di repo ini).

- Paket: `id.my.otomasismpn6keritang.siguruadmin`, minSdk 24 (Android 7.0+).
- Sidik jari SHA-256 kunci: `6B:FF:14:36:70:5A:A1:B9:76:9E:57:E3:FF:83:FA:9A:28:26:82:4B:F6:84:1F:BD:35:F2:20:DF:99:CB:CE:37`
- **Berkas kunci (`siguru-admin.keystore`) dan sandinya TIDAK ada di repo** — disimpan oleh Pak Sultan. Tanpa kunci yang sama,
  APK baru tidak bisa memperbarui APK lama dan semua `assetlinks.json` harus diganti.
- APK hanya perlu dibuat ulang bila daftar alamat/nama/ikon berubah. Isi aplikasi (SiGuru, SiPustaka, dll.) selalu terbaru dari web.

Membuat ulang: `npm i @bubblewrap/core` → `node buat-proyek.js` (sesuaikan path ikon & kunci) → di folder proyek ganti `jcenter()`
dengan mirror Maven Central, minSdk 24 → `./gradlew assembleRelease` → `zipalign` → `apksigner sign` dengan kunci di atas →
salin hasilnya ke `apk/SiGuru-Admin.apk`, naikkan `appVersionCode`.

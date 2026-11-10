# APK "SiGuru Admin"

Sejak versi 1.1 (SiGuru v1.50) APK ini adalah aplikasi **WebView biasa** (folder `aplikasi/`),
bukan lagi TWA. Alasan: APK TWA 1.0 dan 1.0.1 terpasang tetapi tidak mau terbuka di HP pengguna.

Ciri:
- Membuka `https://guru.otomasismpn6keritang.my.id/?p=beranda` langsung di dalam aplikasi — tanpa Chrome, tanpa bilah alamat.
- Semua alamat `*.otomasismpn6keritang.my.id` dibuka di dalam aplikasi; alamat lain dibuka di aplikasi luar.
- Tombol "‹ SiGuru" (pojok kiri bawah) disisipkan aplikasi di semua halaman sekolah selain guru.* (bila halaman belum punya `.ke-siguru`/`.ke-siguru-tbh`).
- Tanpa efek memantul (`OVER_SCROLL_NEVER`), tombol Kembali HP = kembali satu halaman.
- `window.print()` diarahkan ke layanan cetak Android (bisa "Simpan sebagai PDF").
- Unggah foto/berkas (kamera & galeri), kamera untuk pemindai (getUserMedia), unduhan lewat DownloadManager.
- Bila aplikasi sempat tertutup sendiri, pesan galatnya tampil saat dibuka lagi (untuk dikirim ke Claude).
- Paket `id.my.otomasismpn6keritang.siguruadmin`, nama aktivitas tetap `LauncherActivity` agar ikon lama tetap jalan.

Membuat ulang:
1. Android SDK (platform 35, build-tools 35/36), JDK 17+.
2. `cd aplikasi && ANDROID_HOME=... ./gradlew assembleRelease`
3. `zipalign -p -f 4 app/build/outputs/apk/release/app-release-unsigned.apk al.apk`
4. `apksigner sign --ks siguru-admin.keystore --ks-key-alias siguru --out SiGuru-Admin.apk al.apk`
   (keystore + sandi disimpan Pak Sultan — JANGAN di-commit). Naikkan `versionCode` tiap rilis.
5. Salin ke `apk/SiGuru-Admin.apk`, naikkan VERSI SiGuru.

`/.well-known/assetlinks.json` di tiap subdomain tidak dibutuhkan lagi oleh APK ini (aman dibiarkan).

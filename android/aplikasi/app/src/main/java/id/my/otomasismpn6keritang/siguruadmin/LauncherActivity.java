package id.my.otomasismpn6keritang.siguruadmin;

import android.Manifest;
import android.app.Activity;
import android.app.AlertDialog;
import android.app.DownloadManager;
import android.content.ActivityNotFoundException;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.content.res.ColorStateList;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.Environment;
import android.print.PrintAttributes;
import android.print.PrintDocumentAdapter;
import android.print.PrintManager;
import android.provider.MediaStore;
import android.view.Gravity;
import android.view.View;
import android.view.ViewGroup;
import android.webkit.CookieManager;
import android.webkit.JavascriptInterface;
import android.webkit.PermissionRequest;
import android.webkit.URLUtil;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceError;
import android.webkit.WebResourceRequest;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.FrameLayout;
import android.widget.ProgressBar;
import android.widget.Toast;

import androidx.core.content.FileProvider;

import java.io.File;
import java.nio.charset.StandardCharsets;
import java.io.FileInputStream;
import java.io.ByteArrayOutputStream;
import java.util.ArrayList;

/**
 * SiGuru Admin: membuka SiGuru dan aplikasi sekolah lain langsung di dalam aplikasi
 * (tanpa Chrome, tanpa bilah alamat, tanpa efek memantul).
 */
public class LauncherActivity extends Activity {

    static final String AWAL = "https://guru.otomasismpn6keritang.my.id/?p=beranda";
    static final String DOMAIN = "otomasismpn6keritang.my.id";
    static final int MINTA_BERKAS = 1, IZIN_KAMERA_WEB = 2, IZIN_KAMERA_FOTO = 3;

    WebView web;
    ProgressBar bar;
    ValueCallback<Uri[]> tungguBerkas;
    WebChromeClient.FileChooserParams paramBerkas;
    Uri fotoUri;
    PermissionRequest tungguIzin;
    String urlGagal = AWAL;

    static final String SISIP_CETAK =
            "window.print=function(){try{SiGuruApp.cetak(document.title||'SiGuru')}catch(e){}};";

    /** Tombol "‹ SiGuru" di semua halaman sekolah selain SiGuru (kecuali bila halaman sudah punya tombolnya). */
    static final String SISIP_KEMBALI =
            "(function(){try{if(location.hostname.indexOf('guru.')===0)return;"
            + "if(document.getElementById('siguru-kembali')||document.querySelector('.ke-siguru,.ke-siguru-tbh'))return;"
            + "var b=document.body;if(!b)return;var a=document.createElement('a');a.id='siguru-kembali';"
            + "a.href='" + AWAL + "';a.textContent='\u2039 SiGuru';"
            + "a.setAttribute('style','position:fixed;left:12px;bottom:calc(14px + env(safe-area-inset-bottom,0px));"
            + "z-index:2147483647;background:#2A2C84;color:#fff;font:600 14px/1 sans-serif;padding:11px 15px;"
            + "border-radius:999px;text-decoration:none;box-shadow:0 2px 8px rgba(0,0,0,.25);border:2px solid #F2C230');"
            + "b.appendChild(a);}catch(e){}})();";

    @Override
    protected void onCreate(Bundle simpanan) {
        super.onCreate(simpanan);

        FrameLayout akar = new FrameLayout(this);
        web = new WebView(this);
        web.setOverScrollMode(View.OVER_SCROLL_NEVER);
        akar.addView(web, new FrameLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT, ViewGroup.LayoutParams.MATCH_PARENT));

        bar = new ProgressBar(this, null, android.R.attr.progressBarStyleHorizontal);
        bar.setMax(100);
        bar.setProgressTintList(ColorStateList.valueOf(0xFFF2C230));
        FrameLayout.LayoutParams lp = new FrameLayout.LayoutParams(
                ViewGroup.LayoutParams.MATCH_PARENT, dp(3), Gravity.TOP);
        akar.addView(bar, lp);
        setContentView(akar);

        WebSettings s = web.getSettings();
        s.setJavaScriptEnabled(true);
        s.setDomStorageEnabled(true);
        s.setDatabaseEnabled(true);
        s.setMediaPlaybackRequiresUserGesture(false);
        s.setAllowFileAccess(false);
        s.setAllowContentAccess(true);
        s.setSupportMultipleWindows(false);
        s.setJavaScriptCanOpenWindowsAutomatically(true);
        s.setLoadWithOverviewMode(true);
        s.setUseWideViewPort(true);
        s.setTextZoom(100);
        s.setMixedContentMode(WebSettings.MIXED_CONTENT_NEVER_ALLOW);
        s.setUserAgentString(s.getUserAgentString() + " SiGuruAdminApp/1.2");

        CookieManager cm = CookieManager.getInstance();
        cm.setAcceptCookie(true);
        cm.setAcceptThirdPartyCookies(web, true);

        web.addJavascriptInterface(new Jembatan(), "SiGuruApp");
        web.setWebViewClient(new Klien());
        web.setWebChromeClient(new Krom());
        web.setDownloadListener((url, ua, cd, mime, panjang) -> unduh(url, ua, cd, mime));

        if (simpanan == null || web.restoreState(simpanan) == null) {
            web.loadUrl(AWAL);
        }
        tampilkanGalatLama();
    }

    // ---------- navigasi ----------

    static boolean milikSekolah(Uri u) {
        if (u == null) return false;
        String skema = u.getScheme(), host = u.getHost();
        if (skema == null || host == null) return false;
        if (!skema.equals("https") && !skema.equals("http")) return false;
        host = host.toLowerCase();
        return host.equals(DOMAIN) || host.endsWith("." + DOMAIN);
    }

    void bukaLuar(Uri u) {
        try {
            Intent i;
            if ("intent".equals(u.getScheme())) {
                i = Intent.parseUri(u.toString(), Intent.URI_INTENT_SCHEME);
                i.addCategory(Intent.CATEGORY_BROWSABLE);
                i.setComponent(null);
                i.setSelector(null);
            } else {
                i = new Intent(Intent.ACTION_VIEW, u);
            }
            startActivity(i);
        } catch (Exception e) {
            Toast.makeText(this, "Tidak ada aplikasi untuk membuka tautan ini", Toast.LENGTH_SHORT).show();
        }
    }

    class Klien extends WebViewClient {
        @Override
        public boolean shouldOverrideUrlLoading(WebView v, WebResourceRequest r) {
            Uri u = r.getUrl();
            if (milikSekolah(u)) return false;
            if ("about".equals(u.getScheme()) || "data".equals(u.getScheme())
                    || "blob".equals(u.getScheme())) return false;
            bukaLuar(u);
            return true;
        }

        @Override
        public void onPageStarted(WebView v, String url, Bitmap ikon) {
            bar.setVisibility(View.VISIBLE);
        }

        @Override
        public void onPageCommitVisible(WebView v, String url) {
            v.evaluateJavascript(SISIP_CETAK, null);
        }

        @Override
        public void onPageFinished(WebView v, String url) {
            v.evaluateJavascript(SISIP_CETAK + SISIP_KEMBALI, null);
            bar.setVisibility(View.GONE);
            CookieManager.getInstance().flush();
        }

        @Override
        public void onReceivedError(WebView v, WebResourceRequest r, WebResourceError e) {
            if (!r.isForMainFrame()) return;
            urlGagal = r.getUrl().toString();
            String html = "<!doctype html><html><head><meta name='viewport' content='width=device-width,initial-scale=1'>"
                    + "<style>body{font-family:sans-serif;margin:0;display:flex;align-items:center;justify-content:center;"
                    + "min-height:100vh;background:#EAF6FD;color:#1A1A2E;text-align:center;overscroll-behavior:none}"
                    + ".k{background:#fff;border-radius:16px;padding:28px 22px;margin:20px;max-width:360px;"
                    + "box-shadow:0 2px 10px rgba(0,0,0,.08)}h2{color:#2A2C84;margin:0 0 8px}"
                    + "p{color:#5B6382;line-height:1.5}button{background:#2A2C84;color:#fff;border:0;border-radius:12px;"
                    + "padding:12px 28px;font-size:16px;margin-top:8px}</style></head><body><div class='k'>"
                    + "<h2>Tidak tersambung</h2><p>Periksa internet HP, lalu tekan Coba lagi.</p>"
                    + "<button onclick='SiGuruApp.ulang()'>Coba lagi</button></div></body></html>";
            v.loadDataWithBaseURL(null, html, "text/html", "utf-8", null);
        }
    }

    class Krom extends WebChromeClient {
        @Override
        public void onProgressChanged(WebView v, int p) {
            bar.setProgress(p);
            bar.setVisibility(p >= 100 ? View.GONE : View.VISIBLE);
        }

        @Override
        public void onPermissionRequest(PermissionRequest req) {
            runOnUiThread(() -> {
                boolean kamera = false;
                for (String r : req.getResources()) {
                    if (PermissionRequest.RESOURCE_VIDEO_CAPTURE.equals(r)) kamera = true;
                }
                if (!kamera || !milikSekolah(req.getOrigin())) {
                    req.deny();
                    return;
                }
                if (bolehKamera()) {
                    req.grant(new String[]{PermissionRequest.RESOURCE_VIDEO_CAPTURE});
                } else {
                    tungguIzin = req;
                    requestPermissions(new String[]{Manifest.permission.CAMERA}, IZIN_KAMERA_WEB);
                }
            });
        }

        @Override
        public boolean onShowFileChooser(WebView v, ValueCallback<Uri[]> cb, FileChooserParams p) {
            if (tungguBerkas != null) tungguBerkas.onReceiveValue(null);
            tungguBerkas = cb;
            paramBerkas = p;
            if (p.isCaptureEnabled() && hanyaGambar(p)) {
                if (bolehKamera()) bukaKamera();
                else requestPermissions(new String[]{Manifest.permission.CAMERA}, IZIN_KAMERA_FOTO);
            } else {
                bukaPemilih();
            }
            return true;
        }
    }

    // ---------- berkas & kamera ----------

    boolean bolehKamera() {
        return checkSelfPermission(Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED;
    }

    static boolean hanyaGambar(WebChromeClient.FileChooserParams p) {
        String[] jenis = p.getAcceptTypes();
        if (jenis == null || jenis.length == 0) return false;
        boolean ada = false;
        for (String j : jenis) {
            if (j == null || j.trim().isEmpty()) continue;
            if (!j.trim().startsWith("image/")) return false;
            ada = true;
        }
        return ada;
    }

    Intent niatKamera() {
        try {
            File dir = new File(getCacheDir(), "foto");
            if (!dir.exists()) dir.mkdirs();
            File f = new File(dir, "foto-" + System.currentTimeMillis() + ".jpg");
            fotoUri = FileProvider.getUriForFile(this, getPackageName() + ".berkas", f);
            Intent k = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
            k.putExtra(MediaStore.EXTRA_OUTPUT, fotoUri);
            k.addFlags(Intent.FLAG_GRANT_WRITE_URI_PERMISSION | Intent.FLAG_GRANT_READ_URI_PERMISSION);
            return k;
        } catch (Exception e) {
            fotoUri = null;
            return null;
        }
    }

    void bukaKamera() {
        Intent k = niatKamera();
        if (k == null) {
            bukaPemilih();
            return;
        }
        try {
            startActivityForResult(k, MINTA_BERKAS);
        } catch (ActivityNotFoundException e) {
            fotoUri = null;
            bukaPemilih();
        }
    }

    void bukaPemilih() {
        boolean gambar = paramBerkas != null && hanyaGambar(paramBerkas);
        Intent pilih = new Intent(Intent.ACTION_GET_CONTENT);
        pilih.addCategory(Intent.CATEGORY_OPENABLE);
        pilih.setType(gambar ? "image/*" : "*/*");
        if (paramBerkas != null && paramBerkas.getMode() == WebChromeClient.FileChooserParams.MODE_OPEN_MULTIPLE) {
            pilih.putExtra(Intent.EXTRA_ALLOW_MULTIPLE, true);
        }
        Intent pemilih = Intent.createChooser(pilih, gambar ? "Pilih foto" : "Pilih berkas");
        fotoUri = null;
        if (gambar && bolehKamera()) {
            Intent k = niatKamera();
            if (k != null) pemilih.putExtra(Intent.EXTRA_INITIAL_INTENTS, new Intent[]{k});
        }
        try {
            startActivityForResult(pemilih, MINTA_BERKAS);
        } catch (ActivityNotFoundException e) {
            selesaiBerkas(null);
        }
    }

    void selesaiBerkas(Uri[] hasil) {
        if (tungguBerkas != null) tungguBerkas.onReceiveValue(hasil);
        tungguBerkas = null;
        paramBerkas = null;
        fotoUri = null;
    }

    @Override
    protected void onActivityResult(int kode, int hasilKode, Intent data) {
        if (kode != MINTA_BERKAS) {
            super.onActivityResult(kode, hasilKode, data);
            return;
        }
        Uri[] hasil = null;
        if (hasilKode == RESULT_OK) {
            ArrayList<Uri> daftar = new ArrayList<>();
            if (data != null && data.getClipData() != null) {
                for (int i = 0; i < data.getClipData().getItemCount(); i++) {
                    Uri u = data.getClipData().getItemAt(i).getUri();
                    if (u != null) daftar.add(u);
                }
            } else if (data != null && data.getData() != null) {
                daftar.add(data.getData());
            } else if (fotoUri != null) {
                daftar.add(fotoUri);
            }
            if (!daftar.isEmpty()) hasil = daftar.toArray(new Uri[0]);
        }
        selesaiBerkas(hasil);
    }

    @Override
    public void onRequestPermissionsResult(int kode, String[] izin, int[] hasil) {
        boolean boleh = hasil.length > 0 && hasil[0] == PackageManager.PERMISSION_GRANTED;
        if (kode == IZIN_KAMERA_WEB && tungguIzin != null) {
            if (boleh) tungguIzin.grant(new String[]{PermissionRequest.RESOURCE_VIDEO_CAPTURE});
            else tungguIzin.deny();
            tungguIzin = null;
        } else if (kode == IZIN_KAMERA_FOTO) {
            if (boleh) bukaKamera();
            else bukaPemilih();
        }
    }

    // ---------- unduhan & cetak ----------

    void unduh(String url, String ua, String cd, String mime) {
        String nama = URLUtil.guessFileName(url, cd, mime);
        if (!(url.startsWith("https://") || url.startsWith("http://"))) {
            Toast.makeText(this, "Unduhan ini belum bisa dari aplikasi", Toast.LENGTH_LONG).show();
            return;
        }
        if (Build.VERSION.SDK_INT < 29) {
            bukaLuar(Uri.parse(url));
            return;
        }
        try {
            DownloadManager.Request r = new DownloadManager.Request(Uri.parse(url));
            String kue = CookieManager.getInstance().getCookie(url);
            if (kue != null) r.addRequestHeader("Cookie", kue);
            r.addRequestHeader("User-Agent", ua);
            if (mime != null) r.setMimeType(mime);
            r.setTitle(nama);
            r.setNotificationVisibility(DownloadManager.Request.VISIBILITY_VISIBLE_NOTIFY_COMPLETED);
            r.setDestinationInExternalPublicDir(Environment.DIRECTORY_DOWNLOADS, nama);
            ((DownloadManager) getSystemService(DOWNLOAD_SERVICE)).enqueue(r);
            Toast.makeText(this, "Mengunduh " + nama + " (lihat folder Download)", Toast.LENGTH_LONG).show();
        } catch (Exception e) {
            bukaLuar(Uri.parse(url));
        }
    }

    class Jembatan {
        @JavascriptInterface
        public void cetak(String judul) {
            runOnUiThread(() -> {
                try {
                    String j = (judul == null || judul.trim().isEmpty()) ? "SiGuru" : judul.trim();
                    PrintManager pm = (PrintManager) getSystemService(PRINT_SERVICE);
                    PrintDocumentAdapter a = web.createPrintDocumentAdapter(j);
                    pm.print(j, a, new PrintAttributes.Builder()
                            .setMediaSize(PrintAttributes.MediaSize.ISO_A4).build());
                } catch (Exception e) {
                    Toast.makeText(LauncherActivity.this, "Gagal membuka cetak", Toast.LENGTH_SHORT).show();
                }
            });
        }

        @JavascriptInterface
        public void ulang() {
            runOnUiThread(() -> web.loadUrl(urlGagal));
        }
    }

    // ---------- siklus hidup ----------

    @Override
    public void onBackPressed() {
        if (web != null && web.canGoBack()) web.goBack();
        else super.onBackPressed();
    }

    @Override
    protected void onPause() {
        super.onPause();
        CookieManager.getInstance().flush();
        if (web != null) web.onPause();
    }

    @Override
    protected void onResume() {
        super.onResume();
        if (web != null) web.onResume();
    }

    @Override
    protected void onSaveInstanceState(Bundle keluar) {
        super.onSaveInstanceState(keluar);
        if (web != null) web.saveState(keluar);
    }

    @Override
    protected void onDestroy() {
        if (web != null) {
            web.stopLoading();
            web.destroy();
            web = null;
        }
        super.onDestroy();
    }

    void tampilkanGalatLama() {
        try {
            File f = new File(getFilesDir(), "galat.txt");
            if (!f.exists()) return;
            ByteArrayOutputStream b = new ByteArrayOutputStream();
            try (FileInputStream in = new FileInputStream(f)) {
                byte[] buf = new byte[4096];
                int n;
                while ((n = in.read(buf)) > 0) b.write(buf, 0, n);
            }
            String isi = new String(b.toByteArray(), StandardCharsets.UTF_8);
            f.delete();
            if (isi.length() > 2500) isi = isi.substring(0, 2500);
            new AlertDialog.Builder(this)
                    .setTitle("Aplikasi sempat tertutup")
                    .setMessage("Tolong foto layar ini dan kirim ke Claude.\n\n" + isi)
                    .setPositiveButton("Tutup", null)
                    .show();
        } catch (Exception ignored) {
        }
    }

    int dp(int n) {
        return Math.round(n * getResources().getDisplayMetrics().density);
    }
}

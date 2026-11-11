package id.my.otomasismpn6keritang.siguruadmin;

import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.job.JobInfo;
import android.app.job.JobParameters;
import android.app.job.JobScheduler;
import android.app.job.JobService;
import android.content.ComponentName;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.app.Notification;
import android.os.Build;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.ByteArrayOutputStream;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.List;

/**
 * Pemeriksa notifikasi SiGuru: tiap ±15 menit bertanya ke `?p=api_notif` (tanpa Firebase),
 * lalu menampilkan notifikasi yang belum pernah tampil.
 */
public class Periksa extends JobService {

    static final int JOB_BERKALA = 7101, JOB_SEKALI = 7102;
    static final String PREF = "notif", SALURAN = "siguru";

    static SharedPreferences pref(Context c) {
        return c.getSharedPreferences(PREF, MODE_PRIVATE);
    }

    /** Dipanggil halaman SiGuru (di dalam aplikasi) saat guru login: simpan kunci & jadwalkan pemeriksaan. Kunci kosong = berhenti. */
    static void atur(Context c, String kunci, String dasar) {
        SharedPreferences p = pref(c);
        JobScheduler js = (JobScheduler) c.getSystemService(JOB_SCHEDULER_SERVICE);
        if (kunci == null || kunci.isEmpty()) {
            p.edit().clear().apply();
            if (js != null) {
                js.cancel(JOB_BERKALA);
                js.cancel(JOB_SEKALI);
            }
            return;
        }
        boolean sama = kunci.equals(p.getString("kunci", "")) && dasar.equals(p.getString("dasar", ""));
        if (!sama) p.edit().putString("kunci", kunci).putString("dasar", dasar).putLong("sejak", 0).apply();
        buatSaluran(c);
        if (js == null) return;
        ComponentName cn = new ComponentName(c, Periksa.class);
        boolean ada = false;
        for (JobInfo j : js.getAllPendingJobs()) if (j.getId() == JOB_BERKALA) ada = true;
        if (!ada || !sama) {
            js.schedule(new JobInfo.Builder(JOB_BERKALA, cn)
                    .setRequiredNetworkType(JobInfo.NETWORK_TYPE_ANY)
                    .setPeriodic(15 * 60 * 1000L)
                    .setPersisted(true)
                    .build());
        }
        if (!sama) {   // pemeriksaan pertama: hanya mencatat waktu server (notifikasi lama tidak ditampilkan)
            js.schedule(new JobInfo.Builder(JOB_SEKALI, cn)
                    .setRequiredNetworkType(JobInfo.NETWORK_TYPE_ANY)
                    .setOverrideDeadline(60 * 1000L)
                    .build());
        }
    }

    static void buatSaluran(Context c) {
        if (Build.VERSION.SDK_INT >= 26) {
            NotificationManager nm = (NotificationManager) c.getSystemService(NOTIFICATION_SERVICE);
            if (nm != null && nm.getNotificationChannel(SALURAN) == null) {
                NotificationChannel ch = new NotificationChannel(SALURAN, "SiGuru", NotificationManager.IMPORTANCE_DEFAULT);
                ch.setDescription("Tugas dikumpulkan, ujian ditutup, jadwal, dan lainnya");
                nm.createNotificationChannel(ch);
            }
        }
    }

    @Override
    public boolean onStartJob(JobParameters params) {
        new Thread(() -> {
            try {
                periksa(getApplicationContext());
            } catch (Throwable ignored) {
            }
            jobFinished(params, false);
        }).start();
        return true;
    }

    @Override
    public boolean onStopJob(JobParameters params) {
        return false;
    }

    static void periksa(Context c) throws Exception {
        SharedPreferences p = pref(c);
        String kunci = p.getString("kunci", ""), dasar = p.getString("dasar", "");
        if (kunci.isEmpty() || dasar.isEmpty()) return;
        long sejak = p.getLong("sejak", 0);
        URL url = new URL(dasar + "?p=api_notif&k=" + URLEncoder.encode(kunci, "UTF-8") + "&s=" + sejak);
        HttpURLConnection h = (HttpURLConnection) url.openConnection();
        h.setConnectTimeout(15000);
        h.setReadTimeout(20000);
        h.setRequestProperty("User-Agent", "SiGuruApp/1.5 notif");
        int kode = h.getResponseCode();
        if (kode == 403) {   // kunci tidak berlaku lagi
            atur(c, "", "");
            return;
        }
        if (kode != 200) return;
        ByteArrayOutputStream b = new ByteArrayOutputStream();
        try (InputStream in = h.getInputStream()) {
            byte[] buf = new byte[4096];
            int n;
            while ((n = in.read(buf)) > 0) b.write(buf, 0, n);
        }
        JSONObject j = new JSONObject(b.toString("UTF-8"));
        if (!j.optBoolean("ok")) return;
        JSONArray daftar = j.optJSONArray("notif");

        List<String> sudah = new ArrayList<>(Arrays.asList(p.getString("sudah", "").split("\n")));
        NotificationManager nm = (NotificationManager) c.getSystemService(NOTIFICATION_SERVICE);
        buatSaluran(c);
        if (daftar != null && nm != null) {
            for (int i = 0; i < daftar.length(); i++) {
                JSONObject n = daftar.getJSONObject(i);
                String id = n.optString("id");
                if (id.isEmpty() || sudah.contains(id)) continue;
                sudah.add(id);
                tampil(c, nm, id, n.optString("judul"), n.optString("isi"), dasar + n.optString("url", ""));
            }
        }
        while (sudah.size() > 150) sudah.remove(0);
        p.edit().putLong("sejak", j.optLong("kini", System.currentTimeMillis() / 1000))
                .putString("sudah", String.join("\n", sudah)).apply();
    }

    static void tampil(Context c, NotificationManager nm, String id, String judul, String isi, String alamat) {
        Intent buka = new Intent(c, LauncherActivity.class)
                .putExtra("buka", alamat)
                .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TOP);
        PendingIntent pi = PendingIntent.getActivity(c, id.hashCode(), buka,
                PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE);
        Notification.Builder nb = Build.VERSION.SDK_INT >= 26
                ? new Notification.Builder(c, SALURAN) : new Notification.Builder(c);
        nb.setSmallIcon(R.drawable.ic_notif)
                .setColor(0xFF2A2C84)
                .setContentTitle(judul)
                .setContentText(isi)
                .setStyle(new Notification.BigTextStyle().bigText(isi))
                .setContentIntent(pi)
                .setAutoCancel(true);
        try {
            nm.notify(id.hashCode(), nb.build());
        } catch (SecurityException ignored) {   // izin notifikasi belum diberikan
        }
    }
}

<?php
/* Pekerjaan otomatis (v1.42) — berjalan "menumpang" saat ada halaman dibuka (tanpa cron), paling sering tiap 60 detik:
 *  1. Ujian ditutup otomatis: (a) batas akhir lewat & tidak ada yang masih mengerjakan; (b) tanpa batas akhir: semua siswa sudah
 *     mengumpulkan (bukan latihan). Bisa dimatikan per ujian (`tutup_otomatis` = 0).
 *  2. Tugas ditutup otomatis saat batas lewat bila kiriman terlambat TIDAK diterima (`tutup_otomatis` = 1).
 *  3. Nilai ujian yang sudah ditutup otomatis masuk ke Penilaian (`nilai_masuk`), setelah semua uraian dikoreksi.
 *     Bagian ini butuh akun guru pemilik, jadi dijalankan untuk guru yang sedang membuka SiGuru. */
declare(strict_types=1);

function otomatis_jalankan(): void
{
    $f = ai_folder_data() . '/otomatis.waktu';
    $baru = !is_file($f) || filemtime($f) < time() - 60;
    if ($baru) {
        @touch($f);
        try { otomatis_tutup(); } catch (Throwable $e) { error_log('[siguru otomatis] ' . $e->getMessage()); }
        try { sampah_bersihkan(); } catch (Throwable $e) { error_log('[siguru sampah] ' . $e->getMessage()); }
    }
    if (uid()) {
        try { otomatis_nilai(uid()); } catch (Throwable $e) { error_log('[siguru nilai otomatis] ' . $e->getMessage()); }
    }
}

/** Tutup ujian (dipakai tombol Tutup guru & penutupan otomatis). */
function ujian_tutup(array $u, bool $otomatis = false): void
{
    q("UPDATE guru_ujian SET status = 'tutup', nilai_masuk = 0, ditutup = ?, ditutup_otomatis = ? WHERE id = ?", [sekarang(), $otomatis ? 1 : 0, $u['id']]);
    ujian_akhiri_kedaluwarsa($u, true);
}

function otomatis_tutup(): void
{
    $kini = sekarang();
    // ujian dengan batas akhir yang sudah lewat
    foreach (semua("SELECT * FROM guru_ujian WHERE status = 'buka' AND tutup_otomatis = 1 AND selesai IS NOT NULL AND selesai <= ?", [$kini]) as $u) {
        ujian_akhiri_kedaluwarsa($u);   // yang waktunya habis dikumpulkan dulu
        $masih = nilai('SELECT 1 FROM guru_ujian_peserta WHERE ujian_id = ? AND mulai IS NOT NULL AND selesai IS NULL LIMIT 1', [$u['id']]);
        if (!$masih) ujian_tutup($u, true);   // yang dibuka ulang guru boleh menyelesaikan dulu
    }
    // ujian tanpa batas akhir: tutup bila semua siswa sudah mengumpulkan (latihan tidak, karena boleh diulang)
    foreach (semua("SELECT * FROM guru_ujian WHERE status = 'buka' AND tutup_otomatis = 1 AND selesai IS NULL AND mode <> 'latihan'") as $u) {
        $siswa = array_column(ujian_siswa($u), 'member_id');
        if (!$siswa) continue;
        $selesai = q('SELECT kode FROM guru_ujian_peserta WHERE ujian_id = ? AND selesai IS NOT NULL', [$u['id']])->fetchAll(PDO::FETCH_COLUMN);
        if (!array_diff($siswa, $selesai)) ujian_tutup($u, true);
    }
    // tugas dengan batas lewat yang tidak menerima kiriman terlambat
    q("UPDATE guru_tugas SET status = 'tutup' WHERE status = 'buka' AND tutup_otomatis = 1 AND terima_telat = 0 AND batas IS NOT NULL AND batas <= ?", [$kini]);
}

/** Kirim nilai ujian yang sudah ditutup ke Penilaian (sekali; dikirim ulang bila ujian dibuka lalu ditutup lagi). */
function otomatis_nilai(int $guru): void
{
    foreach (semua("SELECT * FROM guru_ujian WHERE guru_id = ? AND status = 'tutup' AND nilai_masuk = 0", [$guru]) as $u) {
        if (nilai('SELECT 1 FROM guru_ujian_peserta WHERE ujian_id = ? AND perlu_koreksi = 1 LIMIT 1', [$u['id']])) continue; // tunggu koreksi uraian
        if (!nilai('SELECT 1 FROM guru_ujian_peserta WHERE ujian_id = ? AND selesai IS NOT NULL LIMIT 1', [$u['id']])) {
            q('UPDATE guru_ujian SET nilai_masuk = 1 WHERE id = ?', [$u['id']]);   // tidak ada yang mengerjakan
            continue;
        }
        $tindak = (string)($u['tindak'] ?? '');
        if ($tindak === 'remedial') ujian_kirim_remedial($u);
        elseif ($tindak === 'susulan') ujian_kirim_susulan($u);
        else ujian_kirim_penilaian($u, ujian_kktp($u), $tindak === 'pengayaan' ? 'Lainnya' : 'Ulangan Harian');
        q('UPDATE guru_ujian SET nilai_masuk = 1 WHERE id = ?', [$u['id']]);
    }
}

/** Ujian susulan: soal & pengaturan sama dengan ujian asal, khusus siswa yang belum mengerjakan. */
function ujian_buat_susulan(array $u, array $kode): array
{
    $kode = array_values(array_unique(array_map('strval', $kode)));
    if (!$kode) return ['ok' => false, 'galat' => 'Tidak ada siswa yang perlu ujian susulan.'];
    $data = $u;
    foreach (['id', 'penilaian_id', 'nilai_masuk'] as $k) unset($data[$k]);
    $data = array_merge($data, [
        'judul' => mb_substr('Susulan: ' . preg_replace('/^Susulan: /', '', (string)$u['judul']), 0, 200),
        'kode' => ujian_kode_baru(), 'rahasia' => bin2hex(random_bytes(16)), 'status' => 'buka', 'mulai' => null, 'selesai' => null,
        'dibuat' => sekarang(), 'khusus' => json_encode($kode), 'induk_id' => (int)$u['id'], 'tindak' => 'susulan', 'tutup_otomatis' => 1,
    ]);
    $data = array_filter($data, fn($k) => kolom_ada('guru_ujian', (string)$k), ARRAY_FILTER_USE_KEY);
    q('INSERT INTO guru_ujian (' . implode(', ', array_keys($data)) . ') VALUES (' . implode(',', array_fill(0, count($data), '?')) . ')', array_values($data));
    return ['ok' => true, 'id' => (int)db()->lastInsertId(), 'n' => count($kode)];
}

/** Nilai ujian susulan masuk ke Penilaian ujian asal. */
function ujian_kirim_susulan(array $s): array
{
    $induk = ujian_ambil((int)$s['induk_id']);
    if (!$induk) return [0, 0];
    $pid = (int)$induk['penilaian_id'];
    if (!$pid || !nilai('SELECT 1 FROM guru_penilaian WHERE id = ? AND guru_id = ?', [$pid, uid()])) [$pid] = ujian_kirim_penilaian($induk, ujian_kktp($induk));
    $n = 0;
    foreach (semua('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ?', [$s['id']]) as $p) {
        $nl = ujian_nilai_peserta($s, $p);
        if ($nl === null) continue;
        q('INSERT INTO guru_nilai (penilaian_id, member_id, nilai, catatan) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), catatan = VALUES(catatan)',
            [$pid, $p['kode'], $nl, 'Ujian susulan online' . ($p['perlu_koreksi'] ? ' (uraian belum dinilai)' : '')]);
        $n++;
    }
    q('UPDATE guru_ujian SET penilaian_id = ? WHERE id = ?', [$pid, $s['id']]);
    return [$pid, $n];
}

/** Blok jam pelajaran per kelas per hari dari jadwal guru: [kelas => [hari => [[mulai, selesai], ...]]] (jam berurutan digabung). */
function jadwal_blok_kelas(): array
{
    $o = [];
    foreach (semua('SELECT hari, jam_ke, mulai, selesai, kelas FROM guru_jadwal WHERE guru_id = ? AND mulai IS NOT NULL AND selesai IS NOT NULL ORDER BY kelas, hari, jam_ke', [uid()]) as $j) {
        $b = &$o[$j['kelas']][(int)$j['hari']];
        $m = substr((string)$j['mulai'], 0, 5); $s = substr((string)$j['selesai'], 0, 5);
        $akhir = $b ? count($b) - 1 : -1;
        if ($akhir >= 0 && $b[$akhir][2] === (int)$j['jam_ke'] - 1) { $b[$akhir][1] = $s; $b[$akhir][2] = (int)$j['jam_ke']; }
        else $b[] = [$m, $s, (int)$j['jam_ke']];
        unset($b);
    }
    foreach ($o as &$hari) foreach ($hari as &$blok) $blok = array_map(fn($x) => [$x[0], $x[1]], $blok);
    return $o;
}

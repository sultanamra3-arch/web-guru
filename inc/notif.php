<?php
/* Notifikasi aplikasi Android SiGuru (v1.54).
 * APK memeriksa `?p=api_notif&k=KUNCI&s=SEJAK` kira-kira tiap 15 menit (tanpa Firebase).
 * KUNCI = kunci notifikasi per guru (setelan `notif_kunci:<uid>`), diberikan ke APK lewat kaki() hanya bila halaman
 * dibuka di dalam APK (user agent `SiGuruApp`). Pilihan jenis notifikasi per guru: setelan `notif:<uid>` (JSON). */
declare(strict_types=1);

const NOTIF_JENIS = [
    'tugas'  => ['Tugas dikumpulkan', 'Siswa mengirim tugas online'],
    'ujian'  => ['Ujian ditutup otomatis', 'Ujian selesai sendiri saat waktunya habis / semua sudah mengumpulkan'],
    'jadwal' => ['Jadwal pagi', 'Ringkasan jam mengajar hari ini (sekitar pukul 06.00)'],
    'pinjam' => ['Pinjaman lewat jatuh tempo', 'Khusus admin: jumlah pinjaman perpustakaan yang terlambat'],
];

/** Kunci notifikasi guru (dibuat sekali bila belum ada). */
function notif_kunci(int $guru): string
{
    $k = (string)setelan('notif_kunci:' . $guru, '');
    if ($k === '') {
        $k = bin2hex(random_bytes(20));
        setelan_simpan('notif_kunci:' . $guru, $k);
    }
    return $k;
}

/** Jenis notifikasi yang dinyalakan guru (bawaan: semua). */
function notif_pilihan(int $guru): array
{
    $j = json_decode((string)setelan('notif:' . $guru, ''), true);
    $o = [];
    foreach (array_keys(NOTIF_JENIS) as $k) $o[$k] = is_array($j) && array_key_exists($k, $j) ? (bool)$j[$k] : true;
    return $o;
}

/** Guru pemilik kunci (null bila kunci tidak dikenal). */
function notif_guru(string $kunci): ?array
{
    if (!preg_match('/^[a-f0-9]{40}$/', $kunci)) return null;
    $k = nilai("SELECT k FROM guru_setelan WHERE k LIKE 'notif\\_kunci:%' AND v = ? LIMIT 1", [$kunci]);
    if (!$k) return null;
    $g = satu('SELECT id, nama, peran, aktif FROM guru_akun WHERE id = ?', [(int)substr((string)$k, 12)]);
    return ($g && (int)$g['aktif'] === 1) ? $g : null;
}

/** Daftar notifikasi baru sejak waktu `$sejak` (unix). Tiap notifikasi punya `id` unik — APK tidak menampilkan id yang sama dua kali. */
function notif_kumpulkan(array $g, int $sejak): array
{
    $guru = (int)$g['id'];
    $pil = notif_pilihan($guru);
    $dari = date('Y-m-d H:i:s', $sejak);
    $o = [];

    if ($pil['tugas']) {
        $rows = semua("SELECT t.id, t.judul, t.kelas, t.kelompok,
                SUM(k.dikirim > ?) baru,
                SUM(k.dikirim IS NOT NULL) total
            FROM guru_tugas t JOIN guru_tugas_kirim k ON k.tugas_id = t.id
            WHERE t.guru_id = ? GROUP BY t.id, t.judul, t.kelas, t.kelompok HAVING baru > 0", [$dari, $guru]);
        foreach ($rows as $r) {
            $siapa = (int)$r['kelompok'] === 1 ? 'kelompok' : 'siswa';
            $o[] = ['id' => 't' . $r['id'] . '-' . $sejak, 'judul' => (int)$r['baru'] . ' ' . $siapa . ' mengumpulkan tugas',
                'isi' => $r['judul'] . ' · Kelas ' . $r['kelas'] . ' (total ' . (int)$r['total'] . ' kiriman)',
                'url' => '?p=tugas_lihat&id=' . $r['id']];
        }
    }

    if ($pil['ujian']) {
        foreach (semua('SELECT id, judul, kelas, ditutup FROM guru_ujian WHERE guru_id = ? AND ditutup_otomatis = 1 AND ditutup > ?', [$guru, $dari]) as $r) {
            $o[] = ['id' => 'u' . $r['id'] . '-' . strtotime((string)$r['ditutup']),
                'judul' => 'Ujian ditutup otomatis', 'isi' => $r['judul'] . ' · Kelas ' . $r['kelas'] . '. Ketuk agar nilai masuk Penilaian.',
                'url' => '?p=ujian_lihat&id=' . $r['id']];
        }
    }

    $jam = (int)date('G');
    $hari = (int)date('N');
    if ($pil['jadwal'] && $hari <= 6 && $jam >= 6 && $jam < 10) {
        $j = semua('SELECT jam_ke, mulai, kelas, mapel FROM guru_jadwal WHERE guru_id = ? AND hari = ? ORDER BY jam_ke, mulai', [$guru, $hari]);
        if ($j) {
            $a = $j[0];
            $o[] = ['id' => 'j' . date('Ymd'), 'judul' => 'Jadwal hari ini: ' . count($j) . ' jam mengajar',
                'isi' => 'Pertama: ' . ($a['mulai'] ? substr((string)$a['mulai'], 0, 5) . ' ' : 'jam ke-' . $a['jam_ke'] . ' ') . $a['mapel'] . ' ' . $a['kelas'],
                'url' => '?p=beranda'];
        }
    }

    if ($pil['pinjam'] && ($g['peran'] ?? '') === 'admin' && $hari <= 6 && $jam >= 7 && $jam < 14) {
        try {
            $n = (int)nilai('SELECT COUNT(*) FROM loan WHERE is_lent = 1 AND is_return = 0 AND due_date < CURDATE()');   // hanya baca tabel SLiMS
        } catch (Throwable $e) {
            $n = 0;
        }
        if ($n > 0) {
            $o[] = ['id' => 'p' . date('Ymd'), 'judul' => $n . ' pinjaman lewat jatuh tempo', 'isi' => 'Ketuk untuk membuka daftar pinjaman di SiPustaka.',
                'url' => '?p=ke_pustaka&ke=pinjaman'];
        }
    }
    return $o;
}

/** Sisipan di kaki halaman: memberi kunci notifikasi ke APK (hanya bila dibuka di dalam APK). */
function notif_sisip_apk(): string
{
    if (!str_contains((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 'SiGuruApp')) return '';
    $dasar = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'guru.otomasismpn6keritang.my.id') . '/';
    $kunci = uid() ? notif_kunci(uid()) : '';
    if (!uid() && ($_GET['p'] ?? '') !== 'masuk') return '';
    return '<script>try{if(window.SiGuruApp&&SiGuruApp.notif)SiGuruApp.notif(' . json_encode($kunci) . ',' . json_encode($dasar) . ')}catch(e){}</script>';
}

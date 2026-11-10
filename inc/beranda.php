<?php
/* Bahan Beranda (v1.44): daftar "Perlu tindakan" dan pelajaran sekarang menurut jadwal. */
declare(strict_types=1);

/**
 * Hal yang menunggu guru. Tiap butir: ikon, judul, ket, url, tombol; opsional 'tutup' => [aksi, id] untuk tombol Tutup langsung.
 * Dipakai Beranda dan angka di menu (angka_beranda()).
 */
function perlu_tindakan(): array
{
    static $hasil = null;
    if ($hasil !== null) return $hasil;
    $o = [];
    // 1. kiriman tugas yang menunggu dinilai
    foreach (semua("SELECT t.id, t.judul, t.kelas, COUNT(*) n FROM guru_tugas_kirim k JOIN guru_tugas t ON t.id = k.tugas_id
                    WHERE t.guru_id = ? AND k.dikirim IS NOT NULL AND k.status = 'kirim' GROUP BY t.id, t.judul, t.kelas ORDER BY MIN(k.dikirim)", [uid()]) as $t) {
        $o[] = ['ikon' => 'tugas', 'judul' => (int)$t['n'] . ' kiriman tugas perlu dinilai', 'ket' => $t['judul'] . ' · ' . $t['kelas'],
            'url' => '?p=tugas_lihat&id=' . (int)$t['id'], 'tombol' => 'Nilai'];
    }
    // 2. jawaban uraian ujian yang belum dikoreksi
    foreach (semua("SELECT u.id, u.judul, u.kelas, COUNT(*) n FROM guru_ujian_peserta p JOIN guru_ujian u ON u.id = p.ujian_id
                    WHERE u.guru_id = ? AND p.selesai IS NOT NULL AND p.perlu_koreksi = 1 GROUP BY u.id, u.judul, u.kelas", [uid()]) as $u) {
        $o[] = ['ikon' => 'ujian', 'judul' => 'Uraian belum dikoreksi (' . (int)$u['n'] . ' siswa)', 'ket' => $u['judul'] . ' · ' . $u['kelas'],
            'url' => '?p=ujian_hasil&id=' . (int)$u['id'], 'tombol' => 'Koreksi'];
    }
    // 3. siswa yang belum ikut ujian yang sudah ditutup (14 hari terakhir), belum dibuatkan susulan
    $batas = date('Y-m-d H:i:s', time() - 14 * 86400);
    foreach (semua("SELECT * FROM guru_ujian WHERE guru_id = ? AND status = 'tutup' AND mode = 'ujian' AND (tindak IS NULL OR tindak = '')
                    AND COALESCE(selesai, mulai, dibuat) >= ? ORDER BY id DESC LIMIT 10", [uid(), $batas]) as $u) {
        $siswa = array_column(ujian_siswa($u), 'member_name', 'member_id');
        if (!$siswa) continue;
        $sudah = q('SELECT kode FROM guru_ujian_peserta WHERE ujian_id = ? AND selesai IS NOT NULL', [$u['id']])->fetchAll(PDO::FETCH_COLUMN);
        foreach (semua("SELECT khusus FROM guru_ujian WHERE induk_id = ? AND tindak = 'susulan'", [$u['id']]) as $s) {
            $sudah = array_merge($sudah, array_map('strval', (array)json_decode((string)$s['khusus'], true)));
        }
        $belum = array_diff_key($siswa, array_flip(array_map('strval', $sudah)));
        if (!$belum || count($belum) === count($siswa)) continue;   // semua belum = ujian tidak dipakai, abaikan
        $nama = array_values($belum);
        $o[] = ['ikon' => 'peringatan', 'judul' => count($belum) . ' siswa belum ikut ujian',
            'ket' => implode(', ', array_slice($nama, 0, 3)) . (count($nama) > 3 ? ' +' . (count($nama) - 3) : '') . ' · ' . $u['judul'] . ' · ' . $u['kelas'],
            'url' => '?p=ujian_hasil&id=' . (int)$u['id'], 'tombol' => 'Susulan'];
    }
    // 4. ujian/tugas yang sudah lama terbuka (≥ 3 hari)
    $lama = function (?string $batas_waktu, string $acuan): ?int {
        $dari = $batas_waktu ? (strtotime($batas_waktu) < time() ? $batas_waktu : null) : $acuan;
        if (!$dari) return null;
        $h = (int)floor((time() - strtotime($dari)) / 86400);
        return $h >= 3 ? $h : null;
    };
    foreach (semua("SELECT * FROM guru_ujian WHERE guru_id = ? AND status = 'buka' AND mode = 'ujian'", [uid()]) as $u) {
        if (($h = $lama($u['selesai'], (string)($u['mulai'] ?: $u['dibuat']))) !== null) {
            $o[] = ['ikon' => 'jam', 'judul' => 'Ujian terbuka ' . $h . ' hari', 'ket' => $u['judul'] . ' · ' . $u['kelas'] . ' · pembahasan baru terbuka setelah ditutup',
                'url' => '?p=ujian_lihat&id=' . (int)$u['id'], 'tombol' => 'Lihat', 'tutup' => ['tutup_ujian', (int)$u['id']]];
        }
    }
    foreach (semua("SELECT * FROM guru_tugas WHERE guru_id = ? AND status = 'buka'", [uid()]) as $t) {
        if (($h = $lama($t['batas'], (string)$t['dibuat'])) !== null) {
            $o[] = ['ikon' => 'jam', 'judul' => 'Tugas terbuka ' . $h . ' hari', 'ket' => $t['judul'] . ' · ' . $t['kelas'],
                'url' => '?p=tugas_lihat&id=' . (int)$t['id'], 'tombol' => 'Lihat', 'tutup' => ['tutup_tugas', (int)$t['id']]];
        }
    }
    return $hasil = $o;
}

/** Jadwal hari ini digabung per blok (jam berurutan, kelas & mapel sama): [[mulai, selesai, kelas, mapel, jam_awal, jam_akhir], ...] */
function jadwal_hari_ini(): array
{
    $hari = (int)date('N');
    if ($hari > 6) return [];
    $o = [];
    foreach (semua('SELECT * FROM guru_jadwal WHERE guru_id = ? AND hari = ? ORDER BY jam_ke, mulai', [uid(), $hari]) as $j) {
        $m = $j['mulai'] ? substr((string)$j['mulai'], 0, 5) : ''; $s = $j['selesai'] ? substr((string)$j['selesai'], 0, 5) : '';
        $n = count($o) - 1;
        if ($n >= 0 && $o[$n][2] === $j['kelas'] && $o[$n][3] === $j['mapel'] && $o[$n][5] === (int)$j['jam_ke'] - 1) { $o[$n][1] = $s; $o[$n][5] = (int)$j['jam_ke']; }
        else $o[] = [$m, $s, (string)$j['kelas'], (string)$j['mapel'], (int)$j['jam_ke'], (int)$j['jam_ke']];
    }
    return $o;
}

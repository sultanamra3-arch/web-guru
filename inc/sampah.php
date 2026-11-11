<?php
/*
 * Sampah: data yang dihapus disimpan 30 hari (baris asli, nomor id sama) dan bisa dikembalikan utuh.
 * Pola di setiap tombol hapus:
 *   $data = [sampah_baris('guru_ujian', 'id = ?', [$id]), sampah_baris('guru_ujian_peserta', 'ujian_id = ?', [$id])];
 *   sampah_simpan('ujian', 'Judul', $data);   // lalu hapus seperti biasa (berkas foto JANGAN dihapus dulu)
 * Urutan $data = urutan saat dikembalikan (induk dulu).
 */
declare(strict_types=1);

const SAMPAH_HARI = 30;
const SAMPAH_JENIS = [
    'soal' => 'Soal', 'paket' => 'Paket soal', 'ujian' => 'Ujian online', 'jawaban' => 'Jawaban ujian siswa', 'tugas' => 'Tugas online',
    'kiriman' => 'Kiriman tugas siswa', 'kuis' => 'Kuis', 'penilaian' => 'Penilaian', 'siswa' => 'Siswa', 'kelas' => 'Kelas',
    'absensi' => 'Absensi', 'dokumen' => 'Dokumen', 'sertifikat' => 'Sertifikat',
];

/** Ambil baris (SELECT *) yang akan dihapus. Hanya tabel guru_*. */
function sampah_baris(string $tabel, string $where, array $p): array
{
    if (!preg_match('/^guru_[a-z_]+$/', $tabel)) throw new InvalidArgumentException('tabel');
    return ['tabel' => $tabel, 'baris' => semua("SELECT * FROM $tabel WHERE $where", $p)];
}

/**
 * Simpan ke sampah. $meta opsional: ['ke_paket' => [paket_id, posisi]] (soal dikeluarkan dari paket),
 * ['berkas_tugas' => [...nama foto]] (dihapus saat sampah dibersihkan).
 */
function sampah_simpan(string $jenis, string $judul, array $data, array $meta = []): int
{
    $data = array_values(array_filter($data, fn($d) => !empty($d['baris'])));
    if (!$data) return 0;
    q('INSERT INTO guru_sampah (guru_id, jenis, judul, data, meta, dihapus) VALUES (?,?,?,?,?,?)',
        [uid(), $jenis, mb_substr($judul !== '' ? $judul : (SAMPAH_JENIS[$jenis] ?? $jenis), 0, 250),
         json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE), $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null, sekarang()]);
    $id = (int)db()->lastInsertId();
    $_SESSION['sampah_batal'] = ['id' => $id, 'judul' => $judul, 'jenis' => $jenis, 'waktu' => time()];
    return $id;
}

/** Kembalikan isi sampah persis seperti semula. Tidak menimpa data yang sudah ada. */
function sampah_kembalikan(int $id): array
{
    $s = satu('SELECT * FROM guru_sampah WHERE id = ? AND guru_id = ?', [$id, uid()]);
    if (!$s) return ['ok' => false, 'galat' => 'Data tidak ada di Sampah (mungkin sudah dikembalikan).'];
    $data = json_decode((string)$s['data'], true) ?: [];
    $meta = json_decode((string)($s['meta'] ?? ''), true) ?: [];
    db()->beginTransaction();
    try {
        foreach ($data as $d) {
            $t = (string)($d['tabel'] ?? '');
            if (!preg_match('/^guru_[a-z_]+$/', $t)) continue;
            foreach ((array)$d['baris'] as $b) {
                // kolom yang sudah tidak ada (perubahan versi) dilewati
                $b = array_filter($b, fn($k) => kolom_ada($t, (string)$k), ARRAY_FILTER_USE_KEY);
                if (array_key_exists('guru_id', $b) && (int)$b['guru_id'] !== uid() && (int)$b['guru_id'] !== 0) throw new RuntimeException('pemilik');
                q("INSERT INTO $t (" . implode(', ', array_map(fn($k) => "`$k`", array_keys($b))) . ') VALUES (' . implode(',', array_fill(0, count($b), '?')) . ')', array_values($b));
            }
        }
        // soal yang dikeluarkan/dihapus dari paket: masukkan lagi ke paketnya
        if (!empty($meta['ke_paket'])) {
            [$pid, $pos] = $meta['ke_paket'];
            $p = satu("SELECT id, isi FROM guru_dokumen WHERE id = ? AND guru_id = ? AND jenis = 'paket'", [(int)$pid, uid()]);
            if ($p) {
                $isi = json_decode((string)$p['isi'], true) ?: [];
                $ids = array_values(array_map('intval', (array)($isi['ids'] ?? [])));
                foreach (array_reverse(array_column($data[0]['baris'] ?? [], 'id')) as $sid) if (!in_array((int)$sid, $ids, true)) array_splice($ids, min((int)$pos, count($ids)), 0, [(int)$sid]);
                $isi['ids'] = $ids;
                q('UPDATE guru_dokumen SET isi = ? WHERE id = ?', [json_encode($isi, JSON_UNESCAPED_UNICODE), $p['id']]);
            }
        }
        q('DELETE FROM guru_sampah WHERE id = ?', [$id]);
        db()->commit();
    } catch (Throwable $e) {
        db()->rollBack();
        if ($e instanceof PDOException && (string)$e->getCode() === '23000')
            return ['ok' => false, 'galat' => 'Tidak bisa dikembalikan karena sudah ada data yang sama (mis. siswa dengan kode itu sudah dibuat lagi). Data yang ada tidak ditimpa.'];
        return ['ok' => false, 'galat' => 'Gagal mengembalikan: ' . $e->getMessage()];
    }
    if (($_SESSION['sampah_batal']['id'] ?? 0) === $id) unset($_SESSION['sampah_batal']);
    unset($_SESSION['angka_beranda']);
    return ['ok' => true, 'jenis' => $s['jenis'], 'judul' => $s['judul'], 'data' => $data];
}

/** Hapus permanen satu isi sampah (berkas foto tugas ikut dihapus). */
function sampah_hapus_permanen(array $s): void
{
    $data = json_decode((string)$s['data'], true) ?: [];
    foreach ($data as $d) if (($d['tabel'] ?? '') === 'guru_tugas_kirim') foreach ((array)$d['baris'] as $b) tugas_hapus_foto(tugas_foto_daftar($b));
    q('DELETE FROM guru_sampah WHERE id = ?', [$s['id']]);
}

/** Bersihkan sampah lebih dari 30 hari (semua guru; dipanggil sesekali). */
function sampah_bersihkan(): void
{
    $batas = date('Y-m-d H:i:s', time() - SAMPAH_HARI * 86400);
    foreach (semua('SELECT * FROM guru_sampah WHERE dihapus < ? LIMIT 50', [$batas]) as $s) sampah_hapus_permanen($s);
}

/** Alamat untuk melihat lagi data yang dikembalikan. */
function sampah_alamat(string $jenis, array $data): string
{
    $b = $data[0]['baris'][0] ?? [];
    return match ($jenis) {
        'ujian', 'jawaban' => '?p=ujian_lihat&id=' . (int)($b['id'] ?? $b['ujian_id'] ?? 0),
        'tugas' => '?p=tugas_lihat&id=' . (int)($b['id'] ?? 0),
        'kiriman' => '?p=tugas_lihat&id=' . (int)($b['tugas_id'] ?? 0),
        'paket' => '?p=paket_cetak&id=' . (int)($b['id'] ?? 0),
        'penilaian' => '?p=nilai&id=' . (int)($b['id'] ?? 0),
        'kuis' => '?p=kuis',
        'soal' => '?p=soal',
        'siswa', 'kelas' => '?p=siswa',
        'absensi' => '?p=absensi&kelas=' . rawurlencode((string)($b['kelas'] ?? '')) . '&tanggal=' . rawurlencode((string)($b['tanggal'] ?? '')),
        'sertifikat' => '?p=sertifikat',
        default => '?p=perangkat',
    };
}

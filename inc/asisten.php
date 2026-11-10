<?php
/*
 * Asisten AI khusus admin: membaca & mengubah data MILIK AKUN ADMIN SENDIRI (uid()).
 * Alat baca dijalankan langsung; alat tulis hanya membuat KARTU USULAN yang baru disimpan setelah admin menekan Simpan.
 */
declare(strict_types=1);

const ALAT_TULIS_GURU = [
    'tambah_siswa', 'ubah_siswa', 'hapus_siswa', 'tambah_kelas', 'ganti_nama_kelas', 'hapus_kelas',
    'buat_penilaian', 'ubah_penilaian', 'hapus_penilaian', 'isi_nilai', 'isi_absensi', 'ubah_jadwal',
    'ubah_soal', 'hapus_soal', 'ubah_judul_dokumen', 'hapus_dokumen',
];

function asisten_prompt(): string
{
    $u = pengguna();
    return "Kamu asisten AI di SiGuru (aplikasi guru " . k('sekolah', 'sekolah') . "). Kamu membantu " . $u['nama'] . " (admin) mengelola DATANYA SENDIRI: "
        . "kelas & siswa, penilaian & nilai, absensi, jadwal, bank soal, dokumen (modul ajar, LKPD, presentasi, paket soal).\n"
        . "Hari ini " . HARI[(int)date('N')] . ', ' . tgl(date('Y-m-d')) . " (" . date('Y-m-d') . "). Tahun ajaran " . tahun_aktif() . ".\n"
        . "ATURAN:\n"
        . "- Selalu cari/lihat data dulu dengan alat baca (cari_siswa, daftar_penilaian, dst.) untuk mendapatkan id/kode yang benar. Jangan menebak id.\n"
        . "- Setiap perubahan HANYA lewat alat tulis; alat tulis membuat kartu usulan dan data baru tersimpan setelah pengguna menekan Simpan. "
        . "Bila alat tulis membalas DITOLAK, JANGAN bilang kartu sudah dibuat: jelaskan alasannya atau perbaiki argumennya lalu panggil lagi.\n"
        . "Kamu TIDAK PERNAH bisa menyimpan sendiri. Jawaban pengguna seperti \"ya\"/\"oke\"/\"simpan\" BUKAN penyimpanan: ingatkan untuk menekan tombol Simpan di kartu. "
        . "Jangan pernah menulis \"[Catatan sistem]\" atau mengaku data sudah tersimpan; hanya sistem yang menulis catatan DISIMPAN.\n"
        . "- Bila nama ambigu (beberapa siswa/penilaian cocok), tanyakan dulu yang mana.\n"
        . "- Untuk banyak perubahan sejenis, gabungkan dalam satu alat (mis. isi_nilai dengan banyak siswa).\n"
        . "- Jangan mengisi kolom yang tidak disebut pengguna (mis. mapel, jenis, KKTP); biarkan kosong agar sistem memakai nilai bawaan.\n"
        . "- Jawab singkat dalam Bahasa Indonesia yang ramah. Kamu tidak bisa mengubah data guru lain, akun, atau pengaturan.";
}

function asisten_alat(): array
{
    $f = fn(string $n, string $d, array $p = [], array $wajib = []) => ['type' => 'function', 'function' => [
        'name' => $n, 'description' => $d,
        'parameters' => ['type' => 'object', 'properties' => $p ?: new stdClass(), 'required' => $wajib],
    ]];
    $s = ['type' => 'string'];
    $i = ['type' => 'integer'];
    $n = ['type' => 'number'];
    $siswa_ref = ['type' => 'string', 'description' => 'kode siswa (dari cari_siswa) atau nama lengkap'];
    return [
        // ---- baca ----
        $f('lihat_kelas', 'Daftar kelas milik pengguna beserta jumlah siswa aktif.'),
        $f('cari_siswa', 'Cari siswa milik pengguna berdasarkan nama/NISN dan/atau kelas.', ['kata' => $s, 'kelas' => $s]),
        $f('daftar_penilaian', 'Daftar penilaian (tugas/ulangan) milik pengguna.', ['kelas' => $s, 'kata' => $s]),
        $f('lihat_nilai', 'Nilai semua siswa pada satu penilaian.', ['penilaian_id' => $i], ['penilaian_id']),
        $f('lihat_jadwal', 'Jadwal mengajar mingguan pengguna beserta jam pelajarannya.'),
        $f('lihat_absensi', 'Absensi satu kelas pada satu tanggal.', ['kelas' => $s, 'tanggal' => ['type' => 'string', 'description' => 'YYYY-MM-DD'], 'mapel' => $s], ['kelas', 'tanggal']),
        $f('daftar_soal', 'Cari soal di bank soal milik pengguna.', ['kata' => $s, 'mapel' => $s]),
        $f('daftar_dokumen', 'Daftar dokumen pengguna (modul, lkpd, presentasi, paket).', ['jenis' => $s, 'kata' => $s]),
        // ---- tulis (usulan) ----
        $f('tambah_siswa', 'Usulkan menambah siswa ke satu kelas.', ['kelas' => $s, 'siswa' => ['type' => 'array', 'items' => ['type' => 'object',
            'properties' => ['nama' => $s, 'jk' => ['type' => 'string', 'description' => 'L atau P'], 'nisn' => $s], 'required' => ['nama']]]], ['kelas', 'siswa']),
        $f('ubah_siswa', 'Usulkan mengubah data satu siswa (isi hanya yang diubah).', ['siswa' => $siswa_ref, 'nama' => $s, 'kelas' => $s, 'jk' => $s, 'nisn' => $s, 'absen' => $i,
            'status' => ['type' => 'string', 'enum' => ['aktif', 'keluar', 'lulus']]], ['siswa']),
        $f('hapus_siswa', 'Usulkan menghapus siswa (bila sudah punya nilai/absensi, ditandai keluar).', ['siswa' => $siswa_ref], ['siswa']),
        $f('tambah_kelas', 'Usulkan membuat kelas kosong.', ['nama' => $s], ['nama']),
        $f('ganti_nama_kelas', 'Usulkan mengganti nama kelas (jadwal, absensi, penilaian ikut).', ['lama' => $s, 'baru' => $s], ['lama', 'baru']),
        $f('hapus_kelas', 'Usulkan menghapus kelas yang sudah kosong.', ['nama' => $s], ['nama']),
        $f('buat_penilaian', 'Usulkan membuat penilaian baru.', ['judul' => $s, 'kelas' => $s, 'mapel' => $s,
            'jenis' => ['type' => 'string', 'enum' => JENIS_PENILAIAN], 'tanggal' => ['type' => 'string', 'description' => 'YYYY-MM-DD'], 'kktp' => $n], ['judul', 'kelas']),
        $f('ubah_penilaian', 'Usulkan mengubah nama/jenis/mapel/tanggal/KKTP penilaian.', ['penilaian_id' => $i, 'judul' => $s, 'mapel' => $s,
            'jenis' => ['type' => 'string', 'enum' => JENIS_PENILAIAN], 'tanggal' => $s, 'kktp' => $n], ['penilaian_id']),
        $f('hapus_penilaian', 'Usulkan menghapus penilaian beserta nilainya.', ['penilaian_id' => $i], ['penilaian_id']),
        $f('isi_nilai', 'Usulkan mengisi/mengubah nilai siswa pada satu penilaian.', ['penilaian_id' => $i, 'nilai' => ['type' => 'array', 'items' => ['type' => 'object',
            'properties' => ['siswa' => $siswa_ref, 'nilai' => ['type' => ['number', 'null'], 'description' => '0–100, null untuk mengosongkan'], 'catatan' => $s], 'required' => ['siswa']]]], ['penilaian_id', 'nilai']),
        $f('isi_absensi', 'Usulkan mengisi absensi satu kelas pada SATU tanggal & mapel (beberapa tanggal = panggil berkali-kali).', ['kelas' => $s, 'tanggal' => ['type' => 'string', 'description' => 'YYYY-MM-DD'], 'mapel' => $s,
            'siswa' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['siswa' => $siswa_ref, 'status' => ['type' => 'string', 'enum' => ['H', 'S', 'I', 'A']], 'ket' => $s], 'required' => ['siswa', 'status']]],
            'sisanya_hadir' => ['type' => 'boolean', 'description' => 'true = siswa lain yang tidak disebut ditandai Hadir']], ['kelas', 'tanggal', 'mapel']),
        $f('ubah_jadwal', 'Usulkan menambah/mengganti/menghapus jam di jadwal mengajar.', [
            'tambah' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['hari' => $s, 'jam_ke' => $i, 'kelas' => $s, 'mapel' => $s], 'required' => ['hari', 'jam_ke', 'kelas']]],
            'hapus' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['hari' => $s, 'jam_ke' => $i], 'required' => ['hari', 'jam_ke']]]]),
        $f('ubah_soal', 'Usulkan mengubah satu soal di bank soal.', ['soal_id' => $i, 'pertanyaan' => $s, 'kunci' => $s, 'pembahasan' => $s, 'topik' => $s,
            'tingkat' => ['type' => 'string', 'enum' => ['mudah', 'sedang', 'sulit']], 'opsi' => ['type' => 'object', 'description' => 'mis. {"A":"...","B":"..."}']], ['soal_id']),
        $f('hapus_soal', 'Usulkan menghapus satu soal.', ['soal_id' => $i], ['soal_id']),
        $f('ubah_judul_dokumen', 'Usulkan mengganti judul dokumen (modul/LKPD/presentasi/paket).', ['dokumen_id' => $i, 'judul' => $s], ['dokumen_id', 'judul']),
        $f('hapus_dokumen', 'Usulkan menghapus dokumen.', ['dokumen_id' => $i], ['dokumen_id']),
    ];
}

/* ---------- bantu ---------- */

function asisten_hari(mixed $h): int
{
    if (is_numeric($h)) return max(0, min(6, (int)$h));
    $h = mb_strtolower(trim((string)$h));
    foreach (HARI as $no => $nm) if (str_starts_with(mb_strtolower($nm), mb_substr($h, 0, 3))) return $no;
    return 0;
}

/** Cari kode siswa milik guru dari kode atau nama (opsional dibatasi kelas). */
function asisten_siswa(string $ref, ?string $kelas = null): array
{
    $ref = trim($ref);
    if ($ref === '') return ['ok' => false, 'galat' => 'Siswa belum disebutkan.'];
    $s = siswa_milik_saya($ref);
    if ($s) return ['ok' => true, 'kode' => $s['kode'], 'nama' => $s['nama']];
    $par = [uid(), tahun_aktif(), '%' . $ref . '%'];
    $sql = 'SELECT s.kode, s.nama, ks.kelas FROM guru_siswa s LEFT JOIN guru_kelas_siswa ks ON ks.kode = s.kode AND ks.tahun = ? WHERE s.guru_id = ? AND s.nama LIKE ?';
    $par = [tahun_aktif(), uid(), '%' . $ref . '%'];
    if ($kelas) { $sql .= ' AND ks.kelas = ?'; $par[] = $kelas; }
    $c = semua($sql . ' ORDER BY s.nama LIMIT 6', $par);
    $persis = array_values(array_filter($c, fn($x) => mb_strtolower($x['nama']) === mb_strtolower($ref)));
    if (count($persis) === 1) $c = $persis;
    if (count($c) === 1) return ['ok' => true, 'kode' => $c[0]['kode'], 'nama' => $c[0]['nama']];
    if (!$c) return ['ok' => false, 'galat' => "Siswa \"$ref\" tidak ditemukan" . ($kelas ? " di kelas $kelas" : '') . '.'];
    return ['ok' => false, 'galat' => "Nama \"$ref\" cocok dengan beberapa siswa: " . implode('; ', array_map(fn($x) => $x['nama'] . ' (' . $x['kelas'] . ', kode ' . $x['kode'] . ')', $c)) . '. Pakai kode siswa.'];
}

function asisten_penilaian(int $id): ?array
{
    return satu('SELECT * FROM guru_penilaian WHERE id = ? AND guru_id = ?', [$id, uid()]) ?: null;
}

/** Mapel bawaan: mapel yang sudah dipakai di kelas itu, lalu mapel terbanyak di jadwal, lalu profil. */
function asisten_mapel(string $kelas = ''): string
{
    $m = $kelas !== '' ? nilai('SELECT mapel FROM guru_jadwal WHERE guru_id = ? AND kelas = ? GROUP BY mapel ORDER BY COUNT(*) DESC LIMIT 1', [uid(), $kelas]) : false;
    if (!$m) {
        $profil = trim(explode(',', (string)(pengguna()['mapel'] ?? ''))[0]);
        $m = $profil !== '' ? $profil : nilai('SELECT mapel FROM guru_jadwal WHERE guru_id = ? GROUP BY mapel ORDER BY COUNT(*) DESC LIMIT 1', [uid()]);
    }
    return $m ? (string)$m : '-';
}

/** Terima 2026-10-09, 09/10/2026, 9-10-2026, "hari ini", "kemarin". */
function asisten_tgl(?string $t): ?string
{
    $t = mb_strtolower(trim((string)$t));
    if ($t === 'hari ini' || $t === 'sekarang') return date('Y-m-d');
    if ($t === 'kemarin') return date('Y-m-d', strtotime('-1 day'));
    if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $t, $m) && checkdate((int)$m[2], (int)$m[3], (int)$m[1])) return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
    if (preg_match('/^(\d{1,2})[\/.-](\d{1,2})[\/.-](\d{4})$/', $t, $m) && checkdate((int)$m[2], (int)$m[1], (int)$m[3])) return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
    return null;
}

/* ---------- alat baca ---------- */

function asisten_baca(string $nama, array $a): array
{
    switch ($nama) {
        case 'lihat_kelas':
            $out = [];
            foreach (daftar_kelas() as $k) $out[] = ['kelas' => $k, 'siswa' => count(siswa_kelas($k))];
            return ['tahun' => tahun_aktif(), 'kelas' => $out];
        case 'cari_siswa':
            $sql = 'SELECT s.kode, s.nama, s.jk, s.nisn, s.status, ks.kelas, ks.absen FROM guru_siswa s
                LEFT JOIN guru_kelas_siswa ks ON ks.kode = s.kode AND ks.tahun = ? WHERE s.guru_id = ?';
            $par = [tahun_aktif(), uid()];
            if (!empty($a['kata'])) { $sql .= ' AND (s.nama LIKE ? OR s.nisn = ?)'; $par[] = '%' . $a['kata'] . '%'; $par[] = (string)$a['kata']; }
            if (!empty($a['kelas'])) { $sql .= ' AND ks.kelas = ?'; $par[] = (string)$a['kelas']; }
            return ['siswa' => semua($sql . ' ORDER BY ks.kelas, ks.absen IS NULL, ks.absen, s.nama LIMIT 60', $par)];
        case 'daftar_penilaian':
            $sql = 'SELECT p.id, p.judul, p.jenis, p.kelas, p.mapel, p.tanggal, p.kktp, (SELECT COUNT(*) FROM guru_nilai n WHERE n.penilaian_id = p.id AND n.nilai IS NOT NULL) terisi
                FROM guru_penilaian p WHERE p.guru_id = ?';
            $par = [uid()];
            if (!empty($a['kelas'])) { $sql .= ' AND p.kelas = ?'; $par[] = (string)$a['kelas']; }
            if (!empty($a['kata'])) { $sql .= ' AND p.judul LIKE ?'; $par[] = '%' . $a['kata'] . '%'; }
            return ['penilaian' => semua($sql . ' ORDER BY p.tanggal DESC, p.id DESC LIMIT 40', $par)];
        case 'lihat_nilai':
            $p = asisten_penilaian((int)($a['penilaian_id'] ?? 0));
            if (!$p) return ['galat' => 'Penilaian tidak ditemukan.'];
            $ada = [];
            foreach (semua('SELECT member_id, nilai, catatan FROM guru_nilai WHERE penilaian_id = ?', [$p['id']]) as $r) $ada[$r['member_id']] = $r;
            return ['penilaian' => $p, 'nilai' => array_map(fn($s) => ['kode' => $s['member_id'], 'nama' => $s['member_name'],
                'nilai' => $ada[$s['member_id']]['nilai'] ?? null, 'catatan' => $ada[$s['member_id']]['catatan'] ?? ''], siswa_kelas($p['kelas']))];
        case 'lihat_jadwal':
            $jam = [];
            foreach (HARI as $no => $nm) $jam[$nm] = jam_hari($no);
            return ['jadwal' => array_map(fn($j) => ['hari' => HARI[(int)$j['hari']] ?? $j['hari'], 'jam_ke' => (int)$j['jam_ke'], 'kelas' => $j['kelas'], 'mapel' => $j['mapel'],
                'mulai' => $j['mulai'], 'selesai' => $j['selesai']], semua('SELECT * FROM guru_jadwal WHERE guru_id = ? ORDER BY hari, jam_ke', [uid()])), 'jam_pelajaran' => $jam];
        case 'lihat_absensi':
            $par = [uid(), (string)($a['kelas'] ?? ''), (string)($a['tanggal'] ?? '')];
            $sql = 'SELECT member_id, mapel, status, ket FROM guru_absensi WHERE guru_id = ? AND kelas = ? AND tanggal = ?';
            if (!empty($a['mapel'])) { $sql .= ' AND mapel = ?'; $par[] = (string)$a['mapel']; }
            $r = semua($sql, $par);
            foreach ($r as &$x) $x['nama'] = nama_siswa((string)$x['member_id']);
            return ['absensi' => $r, 'catatan' => $r ? '' : 'Belum ada absensi untuk kelas/tanggal itu.'];
        case 'daftar_soal':
            $sql = 'SELECT id, mapel, kelas, topik, tipe, tingkat, LEFT(pertanyaan, 160) pertanyaan, kunci FROM guru_soal WHERE guru_id = ?';
            $par = [uid()];
            if (!empty($a['kata'])) { $sql .= ' AND (pertanyaan LIKE ? OR topik LIKE ?)'; $par[] = '%' . $a['kata'] . '%'; $par[] = '%' . $a['kata'] . '%'; }
            if (!empty($a['mapel'])) { $sql .= ' AND mapel LIKE ?'; $par[] = '%' . $a['mapel'] . '%'; }
            return ['soal' => semua($sql . ' ORDER BY id DESC LIMIT 30', $par)];
        case 'daftar_dokumen':
            $sql = 'SELECT id, jenis, judul, mapel, kelas, diubah FROM guru_dokumen WHERE guru_id = ?';
            $par = [uid()];
            if (!empty($a['jenis'])) { $sql .= ' AND jenis = ?'; $par[] = (string)$a['jenis']; }
            if (!empty($a['kata'])) { $sql .= ' AND judul LIKE ?'; $par[] = '%' . $a['kata'] . '%'; }
            return ['dokumen' => semua($sql . ' ORDER BY id DESC LIMIT 30', $par)];
    }
    return ['galat' => 'Alat tidak dikenal: ' . $nama];
}

/* ---------- alat tulis: periksa (tanpa menyimpan) → kartu; jalankan setelah Simpan ---------- */

/** Periksa & rapikan argumen. Hasil: ['ok', 'galat', 'judul', 'baris' => [[label, isi]], 'data' => argumen bersih]. */
function asisten_periksa(string $nama, array $a): array
{
    $g = fn(string $m) => ['ok' => false, 'galat' => $m];
    switch ($nama) {
        case 'tambah_siswa':
            $kelas = rapikan_kelas(trim((string)($a['kelas'] ?? '')));
            $daftar = [];
            foreach ((array)($a['siswa'] ?? []) as $s) {
                $nm = rapikan_nama((string)($s['nama'] ?? ''));
                if ($nm === '') continue;
                $daftar[] = ['nama' => $nm, 'jk' => strtoupper(substr((string)($s['jk'] ?? ''), 0, 1)), 'nisn' => preg_replace('/\D/', '', (string)($s['nisn'] ?? '')), 'kelas' => $kelas];
            }
            if ($kelas === '' || !$daftar) return $g('Kelas dan nama siswa wajib diisi.');
            return ['ok' => true, 'judul' => 'Tambah ' . count($daftar) . ' siswa ke ' . $kelas . (kelas_valid($kelas) ? '' : ' (kelas baru)'),
                'baris' => array_map(fn($d, $i) => [(string)($i + 1), $d['nama'] . ($d['jk'] ? ' (' . $d['jk'] . ')' : '') . ($d['nisn'] ? ' · NISN ' . $d['nisn'] : '')], $daftar, array_keys($daftar)),
                'data' => ['siswa' => $daftar]];
        case 'ubah_siswa':
            $c = asisten_siswa((string)($a['siswa'] ?? ''));
            if (!$c['ok']) return $c;
            $s = siswa_milik_saya($c['kode']);
            $ks = satu('SELECT * FROM guru_kelas_siswa WHERE tahun = ? AND kode = ?', [tahun_aktif(), $c['kode']]);
            $baru = [];
            $baris = [];
            if (!empty($a['nama'])) { $baru['nama'] = rapikan_nama((string)$a['nama']); $baris[] = ['Nama', $s['nama'] . ' → ' . $baru['nama']]; }
            if (!empty($a['kelas'])) {
                $k = rapikan_kelas((string)$a['kelas']);
                if (!kelas_valid($k)) return $g("Kelas $k belum ada. Buat dulu dengan tambah_kelas.");
                $baru['kelas'] = $k; $baris[] = ['Kelas', ($ks['kelas'] ?? '-') . ' → ' . $k];
            }
            if (!empty($a['jk']) && in_array(strtoupper($a['jk'][0]), ['L', 'P'], true)) { $baru['jk'] = strtoupper($a['jk'][0]); $baris[] = ['L/P', ($s['jk'] ?: '-') . ' → ' . $baru['jk']]; }
            if (isset($a['nisn']) && $a['nisn'] !== '') { $baru['nisn'] = preg_replace('/\D/', '', (string)$a['nisn']); $baris[] = ['NISN', ($s['nisn'] ?: '-') . ' → ' . $baru['nisn']]; }
            if (!empty($a['absen'])) { $baru['absen'] = max(1, (int)$a['absen']); $baris[] = ['No. absen', ($ks['absen'] ?? '-') . ' → ' . $baru['absen']]; }
            if (!empty($a['status']) && in_array($a['status'], ['aktif', 'keluar', 'lulus'], true)) { $baru['status'] = $a['status']; $baris[] = ['Status', $s['status'] . ' → ' . $baru['status']]; }
            if (!$baru) return $g('Tidak ada yang diubah.');
            return ['ok' => true, 'judul' => 'Ubah data ' . $s['nama'], 'baris' => $baris, 'data' => ['kode' => $c['kode']] + $baru];
        case 'hapus_siswa':
            $c = asisten_siswa((string)($a['siswa'] ?? ''));
            if (!$c['ok']) return $c;
            return ['ok' => true, 'judul' => 'Hapus siswa ' . $c['nama'], 'baris' => [['Catatan', 'Bila sudah punya nilai/absensi, siswa hanya ditandai "keluar".']], 'data' => ['kode' => $c['kode']]];
        case 'tambah_kelas':
            $k = rapikan_kelas(mb_substr(trim((string)($a['nama'] ?? '')), 0, 50));
            if ($k === '') return $g('Nama kelas kosong.');
            if (kelas_valid($k)) return $g("Kelas $k sudah ada.");
            return ['ok' => true, 'judul' => 'Buat kelas ' . $k, 'baris' => [['Tahun ajaran', tahun_aktif()]], 'data' => ['nama' => $k]];
        case 'ganti_nama_kelas':
            $lama = (string)($a['lama'] ?? '');
            $baru = rapikan_kelas(mb_substr(trim((string)($a['baru'] ?? '')), 0, 50));
            if (!kelas_valid($lama)) return $g("Kelas $lama tidak ditemukan.");
            if ($baru === '' || kelas_valid($baru)) return $g('Nama baru kosong atau sudah dipakai.');
            return ['ok' => true, 'judul' => 'Ganti nama kelas', 'baris' => [['Kelas', "$lama → $baru"], ['Ikut diganti', 'jadwal, absensi, penilaian, ujian']], 'data' => ['lama' => $lama, 'baru' => $baru]];
        case 'hapus_kelas':
            $k = (string)($a['nama'] ?? '');
            if (!kelas_valid($k)) return $g("Kelas $k tidak ditemukan.");
            if (siswa_kelas($k)) return $g("Kelas $k masih berisi siswa aktif; pindahkan/hapus siswanya dulu.");
            return ['ok' => true, 'judul' => 'Hapus kelas ' . $k, 'baris' => [], 'data' => ['nama' => $k]];
        case 'buat_penilaian':
            $kelas = (string)($a['kelas'] ?? '');
            $judul = mb_substr(trim((string)($a['judul'] ?? '')), 0, 200);
            if (!kelas_valid($kelas)) return $g("Kelas $kelas tidak ditemukan.");
            if ($judul === '') return $g('Nama penilaian kosong.');
            $d = ['judul' => $judul, 'kelas' => $kelas, 'mapel' => mb_substr(trim((string)($a['mapel'] ?? '')), 0, 120) ?: asisten_mapel($kelas),
                'jenis' => in_array($a['jenis'] ?? '', JENIS_PENILAIAN, true) ? $a['jenis'] : 'Tugas',
                'tanggal' => asisten_tgl($a['tanggal'] ?? null) ?? date('Y-m-d'), 'kktp' => max(0, min(100, (float)($a['kktp'] ?? 75)))];
            return ['ok' => true, 'judul' => 'Buat penilaian', 'baris' => [['Nama', $d['judul']], ['Kelas', $d['kelas']], ['Mapel', $d['mapel']], ['Jenis', $d['jenis']], ['Tanggal', tgl($d['tanggal'])], ['KKTP', (string)$d['kktp']]], 'data' => $d];
        case 'ubah_penilaian':
            $p = asisten_penilaian((int)($a['penilaian_id'] ?? 0));
            if (!$p) return $g('Penilaian tidak ditemukan.');
            $d = [];
            $baris = [];
            foreach (['judul' => 'Nama', 'mapel' => 'Mapel', 'jenis' => 'Jenis', 'tanggal' => 'Tanggal', 'kktp' => 'KKTP'] as $k => $lbl) {
                if (!isset($a[$k]) || $a[$k] === '' || $a[$k] === null) continue;
                $v = $k === 'kktp' ? max(0, min(100, (float)$a[$k])) : ($k === 'tanggal' ? asisten_tgl((string)$a[$k]) : mb_substr(trim((string)$a[$k]), 0, 200));
                if ($k === 'jenis' && !in_array($v, JENIS_PENILAIAN, true)) continue;
                if ($v === null || $v === '') continue;
                $d[$k] = $v;
                $baris[] = [$lbl, $p[$k] . ' → ' . $v];
            }
            if (!$d) return $g('Tidak ada yang diubah.');
            return ['ok' => true, 'judul' => 'Ubah penilaian "' . $p['judul'] . '"', 'baris' => $baris, 'data' => ['id' => (int)$p['id']] + $d];
        case 'hapus_penilaian':
            $p = asisten_penilaian((int)($a['penilaian_id'] ?? 0));
            if (!$p) return $g('Penilaian tidak ditemukan.');
            $n = (int)nilai('SELECT COUNT(*) FROM guru_nilai WHERE penilaian_id = ? AND nilai IS NOT NULL', [$p['id']]);
            return ['ok' => true, 'judul' => 'Hapus penilaian', 'baris' => [['Nama', $p['judul']], ['Kelas', $p['kelas']], ['Nilai ikut terhapus', (string)$n]], 'data' => ['id' => (int)$p['id']]];
        case 'isi_nilai':
            $p = asisten_penilaian((int)($a['penilaian_id'] ?? 0));
            if (!$p) return $g('Penilaian tidak ditemukan.');
            $isi = [];
            $baris = [];
            $salah = [];
            foreach ((array)($a['nilai'] ?? []) as $x) {
                $c = asisten_siswa((string)($x['siswa'] ?? ''), $p['kelas']);
                if (!$c['ok']) { $salah[] = $c['galat']; continue; }
                $v = $x['nilai'] ?? null;
                $v = ($v === null || $v === '') ? null : max(0, min(100, (float)str_replace(',', '.', (string)$v)));
                $isi[$c['kode']] = ['nilai' => $v, 'catatan' => mb_substr(trim((string)($x['catatan'] ?? '')), 0, 255), 'ada_catatan' => array_key_exists('catatan', $x)];
                $baris[] = [$c['nama'], $v === null ? '(dikosongkan)' : rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.')];
            }
            if ($salah) return $g(implode(' ', $salah));
            if (!$isi) return $g('Tidak ada nilai yang diisi.');
            return ['ok' => true, 'judul' => 'Isi nilai: ' . $p['judul'] . ' (' . $p['kelas'] . ')', 'baris' => $baris, 'data' => ['id' => (int)$p['id'], 'isi' => $isi]];
        case 'isi_absensi':
            $kelas = (string)($a['kelas'] ?? '');
            $tgl = asisten_tgl($a['tanggal'] ?? null);
            $mapel = mb_substr(trim((string)($a['mapel'] ?? '')), 0, 120);
            if (!kelas_valid($kelas)) return $g("Kelas \"$kelas\" tidak ada. Kelas yang ada: " . implode(', ', daftar_kelas()) . '.');
            if (!$tgl) return $g('Tanggal tidak dikenali: "' . ($a['tanggal'] ?? '') . '". Pakai YYYY-MM-DD.');
            if ($mapel === '') $mapel = asisten_mapel($kelas);
            if ($mapel === '-') return $g('Mapel belum disebutkan.');
            $isi = [];
            $salah = [];
            foreach ((array)($a['siswa'] ?? []) as $x) {
                $c = asisten_siswa((string)($x['siswa'] ?? ''), $kelas);
                if (!$c['ok']) { $salah[] = $c['galat']; continue; }
                $st = strtoupper(substr((string)($x['status'] ?? 'H'), 0, 1));
                $isi[$c['kode']] = ['status' => in_array($st, ['H', 'S', 'I', 'A'], true) ? $st : 'H', 'ket' => mb_substr(trim((string)($x['ket'] ?? '')), 0, 200), 'nama' => $c['nama']];
            }
            if ($salah) return $g(implode(' ', $salah));
            $sisa = !empty($a['sisanya_hadir']);
            if ($sisa) foreach (siswa_kelas($kelas) as $s) if (!isset($isi[$s['member_id']])) $isi[$s['member_id']] = ['status' => 'H', 'ket' => '', 'nama' => $s['member_name']];
            if (!$isi) return $g('Tidak ada siswa yang diabsen.');
            $hit = array_count_values(array_column($isi, 'status'));
            $baris = [['Kelas / tanggal', $kelas . ' · ' . tgl($tgl) . ' · ' . $mapel], ['Ringkas', 'H ' . ($hit['H'] ?? 0) . ' · S ' . ($hit['S'] ?? 0) . ' · I ' . ($hit['I'] ?? 0) . ' · A ' . ($hit['A'] ?? 0)]];
            foreach ($isi as $x) if ($x['status'] !== 'H') $baris[] = [$x['nama'], ['S' => 'Sakit', 'I' => 'Izin', 'A' => 'Alpa'][$x['status']] . ($x['ket'] ? ' — ' . $x['ket'] : '')];
            return ['ok' => true, 'judul' => 'Isi absensi', 'baris' => $baris, 'data' => ['kelas' => $kelas, 'tanggal' => $tgl, 'mapel' => $mapel, 'isi' => $isi]];
        case 'ubah_jadwal':
            $tambah = [];
            $hapus = [];
            $baris = [];
            foreach ((array)($a['tambah'] ?? []) as $x) {
                $h = asisten_hari($x['hari'] ?? '');
                $ke = (int)($x['jam_ke'] ?? 0);
                $k = rapikan_kelas((string)($x['kelas'] ?? ''));
                if (!$h || !isset(jam_hari($h)[$ke])) return $g('Hari/jam ke-' . $ke . ' tidak ada di jam pelajaranmu.');
                if ($k === '') return $g('Kelas wajib diisi.');
                $tambah[] = ['hari' => $h, 'jam_ke' => $ke, 'kelas' => $k, 'mapel' => mb_substr(trim((string)($x['mapel'] ?? '')), 0, 120) ?: asisten_mapel($k)];
                $baris[] = ['＋ ' . HARI[$h] . ' jam ' . $ke, $k . ' · ' . end($tambah)['mapel']];
            }
            foreach ((array)($a['hapus'] ?? []) as $x) {
                $h = asisten_hari($x['hari'] ?? '');
                $ke = (int)($x['jam_ke'] ?? 0);
                if (!$h || !$ke) continue;
                $hapus[] = ['hari' => $h, 'jam_ke' => $ke];
                $lama = satu('SELECT kelas FROM guru_jadwal WHERE guru_id = ? AND hari = ? AND jam_ke = ?', [uid(), $h, $ke]);
                $baris[] = ['－ ' . HARI[$h] . ' jam ' . $ke, $lama['kelas'] ?? '(kosong)'];
            }
            if (!$tambah && !$hapus) return $g('Tidak ada perubahan jadwal.');
            return ['ok' => true, 'judul' => 'Ubah jadwal mengajar', 'baris' => $baris, 'data' => ['tambah' => $tambah, 'hapus' => $hapus]];
        case 'ubah_soal':
            $s = satu('SELECT * FROM guru_soal WHERE id = ? AND guru_id = ?', [(int)($a['soal_id'] ?? 0), uid()]);
            if (!$s) return $g('Soal tidak ditemukan.');
            $d = [];
            $baris = [];
            foreach (['pertanyaan' => 'Pertanyaan', 'kunci' => 'Kunci', 'pembahasan' => 'Pembahasan', 'topik' => 'Topik', 'tingkat' => 'Tingkat'] as $k => $lbl) {
                if (!isset($a[$k]) || trim((string)$a[$k]) === '') continue;
                $d[$k] = mb_substr(trim((string)$a[$k]), 0, 5000);
                $baris[] = [$lbl, mb_substr((string)$s[$k], 0, 80) . ' → ' . mb_substr($d[$k], 0, 120)];
            }
            if (!empty($a['opsi']) && is_array($a['opsi'])) {
                $o = [];
                foreach ($a['opsi'] as $k => $v) if (in_array(strtoupper((string)$k), HURUF_OPSI, true)) $o[strtoupper((string)$k)] = mb_substr(trim((string)$v), 0, 1000);
                if ($o) { ksort($o); $d['opsi'] = json_encode($o, JSON_UNESCAPED_UNICODE); $baris[] = ['Pilihan', implode(' | ', array_map(fn($k, $v) => "$k. $v", array_keys($o), $o))]; }
            }
            if (!$d) return $g('Tidak ada yang diubah.');
            return ['ok' => true, 'judul' => 'Ubah soal #' . $s['id'], 'baris' => $baris, 'data' => ['id' => (int)$s['id']] + $d];
        case 'hapus_soal':
            $s = satu('SELECT id, pertanyaan FROM guru_soal WHERE id = ? AND guru_id = ?', [(int)($a['soal_id'] ?? 0), uid()]);
            if (!$s) return $g('Soal tidak ditemukan.');
            return ['ok' => true, 'judul' => 'Hapus soal #' . $s['id'], 'baris' => [['Soal', mb_substr($s['pertanyaan'], 0, 160)]], 'data' => ['id' => (int)$s['id']]];
        case 'ubah_judul_dokumen':
        case 'hapus_dokumen':
            $d = satu('SELECT id, jenis, judul FROM guru_dokumen WHERE id = ? AND guru_id = ?', [(int)($a['dokumen_id'] ?? 0), uid()]);
            if (!$d) return $g('Dokumen tidak ditemukan.');
            if ($nama === 'hapus_dokumen') return ['ok' => true, 'judul' => 'Hapus ' . $d['jenis'], 'baris' => [['Judul', $d['judul']]], 'data' => ['id' => (int)$d['id']]];
            $j = mb_substr(trim((string)($a['judul'] ?? '')), 0, 200);
            if ($j === '') return $g('Judul baru kosong.');
            return ['ok' => true, 'judul' => 'Ganti judul ' . $d['jenis'], 'baris' => [['Judul', $d['judul'] . ' → ' . $j]], 'data' => ['id' => (int)$d['id'], 'judul' => $j]];
    }
    return $g('Alat tidak dikenal: ' . $nama);
}

/** Jalankan usulan yang sudah diperiksa. Hasil: ['ok', 'teks', 'tautan']. */
function asisten_jalankan(string $nama, array $d): array
{
    $tahun = tahun_aktif();
    switch ($nama) {
        case 'tambah_siswa':
            $h = simpan_banyak_siswa($d['siswa']);
            $k = $d['siswa'][0]['kelas'];
            return ['ok' => true, 'teks' => "{$h['baru']} siswa baru, {$h['diperbarui']} diperbarui" . ($h['dilewati'] ? ", {$h['dilewati']} dilewati (sudah ada)" : '') . '.', 'tautan' => '?p=siswa&kelas=' . rawurlencode($k)];
        case 'ubah_siswa':
            if (!siswa_milik_saya($d['kode'])) return ['ok' => false, 'teks' => 'Siswa tidak ditemukan.'];
            $set = array_intersect_key($d, array_flip(['nama', 'jk', 'nisn', 'status']));
            if ($set) q('UPDATE guru_siswa SET ' . implode(', ', array_map(fn($k) => "$k = ?", array_keys($set))) . ', diubah = ? WHERE kode = ?', array_merge(array_values($set), [sekarang(), $d['kode']]));
            if (isset($d['kelas']) || isset($d['absen'])) {
                $ks = satu('SELECT * FROM guru_kelas_siswa WHERE tahun = ? AND kode = ?', [$tahun, $d['kode']]);
                q('INSERT INTO guru_kelas_siswa (tahun, kode, kelas, absen) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE kelas = VALUES(kelas), absen = VALUES(absen)',
                    [$tahun, $d['kode'], $d['kelas'] ?? ($ks['kelas'] ?? ''), $d['absen'] ?? ($ks['absen'] ?? null)]);
            }
            return ['ok' => true, 'teks' => 'Data siswa diperbarui.', 'tautan' => '?p=siswa_ubah&kode=' . $d['kode']];
        case 'hapus_siswa':
            if (!siswa_milik_saya($d['kode'])) return ['ok' => false, 'teks' => 'Siswa tidak ditemukan.'];
            $dipakai = nilai('SELECT 1 FROM guru_absensi WHERE member_id = ? UNION SELECT 1 FROM guru_nilai WHERE member_id = ? UNION SELECT 1 FROM guru_ujian_peserta WHERE kode = ? LIMIT 1', [$d['kode'], $d['kode'], $d['kode']]);
            if ($dipakai) {
                q("UPDATE guru_siswa SET status = 'keluar', diubah = ? WHERE kode = ?", [sekarang(), $d['kode']]);
                return ['ok' => true, 'teks' => 'Siswa sudah punya nilai/absensi, jadi ditandai "keluar".'];
            }
            q('DELETE FROM guru_kelas_siswa WHERE kode = ?', [$d['kode']]);
            q('DELETE FROM guru_kelompok WHERE kode = ?', [$d['kode']]);
            q('DELETE FROM guru_siswa WHERE kode = ?', [$d['kode']]);
            return ['ok' => true, 'teks' => 'Siswa dihapus.'];
        case 'tambah_kelas':
            pastikan_kelas($d['nama']);
            return ['ok' => true, 'teks' => 'Kelas ' . $d['nama'] . ' dibuat.', 'tautan' => '?p=siswa&kelas=' . rawurlencode($d['nama'])];
        case 'ganti_nama_kelas':
            if (!kelas_valid($d['lama']) || kelas_valid($d['baru'])) return ['ok' => false, 'teks' => 'Nama kelas sudah berubah atau dipakai.'];
            db()->beginTransaction();
            q('UPDATE guru_kelas SET nama = ? WHERE tahun = ? AND nama = ? AND guru_id = ?', [$d['baru'], $tahun, $d['lama'], uid()]);
            q('UPDATE guru_kelas_siswa ks JOIN guru_siswa s ON s.kode = ks.kode SET ks.kelas = ? WHERE ks.tahun = ? AND ks.kelas = ? AND s.guru_id = ?', [$d['baru'], $tahun, $d['lama'], uid()]);
            foreach (['guru_jadwal', 'guru_absensi', 'guru_penilaian'] as $t) q("UPDATE $t SET kelas = ? WHERE kelas = ? AND guru_id = ?", [$d['baru'], $d['lama'], uid()]);
            q('UPDATE guru_ujian SET kelas = ? WHERE kelas = ? AND tahun = ? AND guru_id = ?', [$d['baru'], $d['lama'], $tahun, uid()]);
            db()->commit();
            return ['ok' => true, 'teks' => 'Nama kelas diganti menjadi ' . $d['baru'] . '.', 'tautan' => '?p=siswa&kelas=' . rawurlencode($d['baru'])];
        case 'hapus_kelas':
            if (siswa_kelas($d['nama'])) return ['ok' => false, 'teks' => 'Kelas masih berisi siswa.'];
            q('DELETE FROM guru_kelas WHERE tahun = ? AND nama = ? AND guru_id = ?', [$tahun, $d['nama'], uid()]);
            return ['ok' => true, 'teks' => 'Kelas dihapus.'];
        case 'buat_penilaian':
            q('INSERT INTO guru_penilaian (guru_id, kelas, mapel, judul, jenis, tanggal, kktp, dibuat) VALUES (?,?,?,?,?,?,?,?)',
                [uid(), $d['kelas'], $d['mapel'], $d['judul'], $d['jenis'], $d['tanggal'], $d['kktp'], sekarang()]);
            $id = (int)db()->lastInsertId();
            return ['ok' => true, 'teks' => 'Penilaian dibuat (id ' . $id . ').', 'tautan' => '?p=nilai&id=' . $id, 'catat' => 'penilaian_id ' . $id];
        case 'ubah_penilaian':
            if (!asisten_penilaian($d['id'])) return ['ok' => false, 'teks' => 'Penilaian tidak ditemukan.'];
            $set = array_diff_key($d, ['id' => 1]);
            q('UPDATE guru_penilaian SET ' . implode(', ', array_map(fn($k) => "$k = ?", array_keys($set))) . ' WHERE id = ?', array_merge(array_values($set), [$d['id']]));
            return ['ok' => true, 'teks' => 'Penilaian diperbarui.', 'tautan' => '?p=nilai&id=' . $d['id']];
        case 'hapus_penilaian':
            if (!asisten_penilaian($d['id'])) return ['ok' => false, 'teks' => 'Penilaian tidak ditemukan.'];
            q('DELETE FROM guru_nilai WHERE penilaian_id = ?', [$d['id']]);
            q('DELETE FROM guru_penilaian WHERE id = ?', [$d['id']]);
            return ['ok' => true, 'teks' => 'Penilaian dihapus.'];
        case 'isi_nilai':
            if (!asisten_penilaian($d['id'])) return ['ok' => false, 'teks' => 'Penilaian tidak ditemukan.'];
            db()->beginTransaction();
            foreach ($d['isi'] as $kode => $x) {
                $lama = satu('SELECT catatan FROM guru_nilai WHERE penilaian_id = ? AND member_id = ?', [$d['id'], (string)$kode]);
                q('INSERT INTO guru_nilai (penilaian_id, member_id, nilai, catatan) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), catatan = VALUES(catatan)',
                    [$d['id'], (string)$kode, $x['nilai'], $x['ada_catatan'] ? $x['catatan'] : ($lama['catatan'] ?? '')]);
            }
            db()->commit();
            return ['ok' => true, 'teks' => count($d['isi']) . ' nilai tersimpan.', 'tautan' => '?p=nilai&id=' . $d['id']];
        case 'isi_absensi':
            db()->beginTransaction();
            foreach ($d['isi'] as $kode => $x) {
                q('INSERT INTO guru_absensi (guru_id, kelas, tanggal, mapel, member_id, status, ket, diubah) VALUES (?,?,?,?,?,?,?,?)
                   ON DUPLICATE KEY UPDATE status = VALUES(status), ket = VALUES(ket), diubah = VALUES(diubah)',
                    [uid(), $d['kelas'], $d['tanggal'], $d['mapel'], (string)$kode, $x['status'], $x['ket'], sekarang()]);
            }
            db()->commit();
            return ['ok' => true, 'teks' => 'Absensi ' . count($d['isi']) . ' siswa tersimpan.', 'tautan' => '?p=absensi&kelas=' . rawurlencode($d['kelas']) . '&tanggal=' . $d['tanggal'] . '&mapel=' . rawurlencode($d['mapel'])];
        case 'ubah_jadwal':
            db()->beginTransaction();
            foreach ($d['hapus'] as $x) q('DELETE FROM guru_jadwal WHERE guru_id = ? AND hari = ? AND jam_ke = ?', [uid(), $x['hari'], $x['jam_ke']]);
            foreach ($d['tambah'] as $x) {
                $jm = jam_hari($x['hari'])[$x['jam_ke']] ?? [null, null];
                q('DELETE FROM guru_jadwal WHERE guru_id = ? AND hari = ? AND jam_ke = ?', [uid(), $x['hari'], $x['jam_ke']]);
                q('INSERT INTO guru_jadwal (guru_id, hari, jam_ke, mulai, selesai, kelas, mapel, ruang) VALUES (?,?,?,?,?,?,?,?)',
                    [uid(), $x['hari'], $x['jam_ke'], $jm[0], $jm[1], $x['kelas'], $x['mapel'], '']);
            }
            db()->commit();
            return ['ok' => true, 'teks' => 'Jadwal diperbarui.', 'tautan' => '?p=jadwal'];
        case 'ubah_soal':
            $set = array_diff_key($d, ['id' => 1]);
            q('UPDATE guru_soal SET ' . implode(', ', array_map(fn($k) => "$k = ?", array_keys($set))) . ' WHERE id = ? AND guru_id = ?', array_merge(array_values($set), [$d['id'], uid()]));
            return ['ok' => true, 'teks' => 'Soal diperbarui.', 'tautan' => '?p=soal_form&id=' . $d['id']];
        case 'hapus_soal':
            q('DELETE FROM guru_soal WHERE id = ? AND guru_id = ?', [$d['id'], uid()]);
            return ['ok' => true, 'teks' => 'Soal dihapus.'];
        case 'ubah_judul_dokumen':
            q('UPDATE guru_dokumen SET judul = ?, diubah = ? WHERE id = ? AND guru_id = ?', [$d['judul'], sekarang(), $d['id'], uid()]);
            return ['ok' => true, 'teks' => 'Judul diganti.', 'tautan' => '?p=dokumen_lihat&id=' . $d['id']];
        case 'hapus_dokumen':
            q('DELETE FROM guru_dokumen WHERE id = ? AND guru_id = ?', [$d['id'], uid()]);
            return ['ok' => true, 'teks' => 'Dokumen dihapus.'];
    }
    return ['ok' => false, 'teks' => 'Alat tidak dikenal.'];
}

/* ---------- usulan & riwayat (di sesi) ---------- */

function asisten_buat_usulan(string $nama, array $a): array
{
    $c = asisten_periksa($nama, $a);
    if (!$c['ok']) return [['status' => 'DITOLAK', 'galat' => $c['galat']], null];
    $id = bin2hex(random_bytes(4));
    $u = ['id' => $id, 'alat' => $nama, 'judul' => $c['judul'], 'baris' => $c['baris'], 'data' => $c['data'], 'waktu' => time()];
    $_SESSION['usulan_guru'][$id] = $u;
    if (count($_SESSION['usulan_guru']) > 30) $_SESSION['usulan_guru'] = array_slice($_SESSION['usulan_guru'], -30, null, true);
    return [['status' => 'MENUNGGU_KONFIRMASI', 'usulan_id' => $id, 'pesan' => 'Kartu usulan sudah tampil di layar dengan tombol Simpan. Jangan memanggil alat ini lagi untuk data yang sama.'], $u];
}

function asisten_simpan_usulan(string $id): array
{
    $u = $_SESSION['usulan_guru'][$id] ?? null;
    if (!$u || !empty($u['selesai'])) return ['ok' => false, 'teks' => 'Usulan sudah diproses atau kedaluwarsa.'];
    try {
        $h = asisten_jalankan($u['alat'], $u['data']);
    } catch (Throwable $e) {
        if (db()->inTransaction()) db()->rollBack();
        return ['ok' => false, 'teks' => 'Gagal: ' . $e->getMessage()];
    }
    if ($h['ok']) {
        $_SESSION['usulan_guru'][$id]['selesai'] = true;
        asisten_riwayat_tambah('assistant', '[Catatan sistem] Usulan ' . $id . ' (' . $u['alat'] . ') DISIMPAN: ' . $h['teks'] . (isset($h['catat']) ? ' ' . $h['catat'] : ''));
    }
    return $h;
}

function asisten_riwayat(): array
{
    return $_SESSION['asisten_guru'] ?? [];
}

function asisten_riwayat_tambah(string $peran, string $isi): void
{
    $_SESSION['asisten_guru'][] = ['role' => $peran, 'content' => $isi];
    if (count($_SESSION['asisten_guru']) > 40) $_SESSION['asisten_guru'] = array_slice($_SESSION['asisten_guru'], -40);
}

/** Usulan yang masih menunggu tombol Simpan. */
function asisten_usulan_menunggu(): array
{
    return array_values(array_filter($_SESSION['usulan_guru'] ?? [], fn($u) => empty($u['selesai']) && time() - (int)$u['waktu'] < 7200));
}

/** Satu giliran percakapan → ['ok', 'teks', 'usulan', 'oleh', 'rincian']. */
function asisten_giliran(string $teks, array $foto): array
{
    // "ya / oke / simpan" bukan penyimpanan: tampilkan lagi kartu yang menunggu, tanpa bertanya ke AI
    $menunggu = asisten_usulan_menunggu();
    $ya = (bool)preg_match('/^\s*(ya+|iya|y|ok(e|ey)?|sip|simpan( saja| aja)?|lanjut(kan)?|boleh|setuju|betul|benar)\s*[.!]*\s*$/iu', $teks);
    if (!$foto && $ya && $menunggu) {
        return ['ok' => true, 'teks' => 'Untuk menyimpan, tekan tombol **💾 Simpan** pada kartu. Ini kartunya lagi:', 'oleh' => '',
            'usulan' => array_map(fn($u) => ['id' => $u['id'], 'judul' => $u['judul'], 'baris' => $u['baris']], array_slice($menunggu, -5)), 'rincian' => []];
    }
    $bagian_foto = ai_bagian_foto($foto, 3);
    $isi = $teks !== '' ? $teks : 'Lihat foto ini.';
    if (!$foto && $ya) $isi .= ' (Belum ada kartu usulan yang menunggu. Bila tadi saya meminta perubahan, buat kartunya sekarang dengan alat tulis.)';
    $pesan_pengguna = $bagian_foto ? ['role' => 'user', 'content' => array_merge([['type' => 'text', 'text' => $isi]], $bagian_foto)] : ['role' => 'user', 'content' => $isi];
    $pesan = array_merge([['role' => 'system', 'content' => asisten_prompt()]], asisten_riwayat(), [$pesan_pengguna]);
    $alat = asisten_alat();
    $usulan = [];
    $ditolak = [];
    $rincian = [];
    $oleh = '';
    $jawab = '';
    $klaim = '/(kartu|usulan)[^.\n]{0,80}(simpan|dibuat|muncul|tampil|layar)|tekan\W+(tombol\W+)?\W*simpan/iu';
    for ($coba = 0; $coba < 2; $coba++) {
        for ($putaran = 0; $putaran < 8; $putaran++) {
            $r = ai_minta($pesan, ['alat' => $alat, 'gambar' => (bool)$bagian_foto, 'maks_token' => 4000]);
            $rincian = array_values(array_unique(array_merge($rincian, $r['rincian'] ?? [])));
            if (!$r['ok']) return ['ok' => false, 'teks' => $r['galat'], 'usulan' => $usulan, 'rincian' => $rincian];
            $oleh = $r['oleh'];
            $m = $r['pesan'];
            if (empty($m['tool_calls'])) { $jawab = (string)$m['content']; break; }
            $pesan[] = ['role' => 'assistant', 'content' => ($m['content'] ?? '') !== '' ? $m['content'] : null, 'tool_calls' => $m['tool_calls']];
            foreach ($m['tool_calls'] as $tc) {
                $nama = (string)$tc['function']['name'];
                $arg = json_decode($tc['function']['arguments'] ?: '{}', true);
                if (!is_array($arg)) $arg = [];
                try {
                    if (in_array($nama, ALAT_TULIS_GURU, true)) {
                        [$hasil, $u] = asisten_buat_usulan($nama, $arg);
                        if ($u) $usulan[] = $u; else $ditolak[] = $hasil['galat'] ?? 'ditolak';
                    } else {
                        $hasil = asisten_baca($nama, $arg);
                    }
                } catch (Throwable $e) {
                    $hasil = ['galat' => 'Kesalahan server: ' . $e->getMessage()];
                    $ditolak[] = $hasil['galat'];
                }
                $pesan[] = ['role' => 'tool', 'tool_call_id' => $tc['id'], 'content' => json_encode($hasil, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)];
            }
        }
        // AI bilang ada kartu padahal tidak ada → minta sekali lagi memanggil alat dengan benar
        if ($usulan || !preg_match($klaim, $jawab) || $coba === 1) break;
        $pesan[] = ['role' => 'assistant', 'content' => $jawab];
        $pesan[] = ['role' => 'user', 'content' => '[Pemeriksaan sistem] Tidak ada kartu usulan yang terbuat'
            . ($ditolak ? ' — alat tulis DITOLAK: ' . implode(' | ', $ditolak) : ' karena kamu belum memanggil alat tulis')
            . '. Panggil alat tulis yang benar sekarang (perbaiki argumennya), atau bila tidak bisa, jelaskan masalahnya kepada pengguna tanpa menyebut kartu.'];
    }
    $jawab = trim(preg_replace('/\[Usulan [0-9a-f]{8}[^\]]*\]?|\[(Catatan|Pemeriksaan) sistem\][^\n]*/u', '', $jawab));
    if (!$usulan && preg_match($klaim, $jawab)) {
        $jawab = 'Maaf, kartu usulan **belum terbuat**' . ($ditolak ? ' karena: ' . implode(' ', array_unique($ditolak)) : '.') . "\nCoba tulis ulang perintahnya dengan lebih rinci (nama kelas, tanggal, nama siswa).";
    } elseif (!$usulan && preg_match('/\b(sudah|telah|berhasil)\s+(di)?(simpan|ubah|perbarui|pindah|hapus|tambah)|tersimpan\b/iu', $jawab)) {
        $jawab .= "\n\n⚠ Belum ada perubahan yang tersimpan. Data baru berubah setelah kamu menekan tombol **💾 Simpan** pada kartu usulan.";
    }
    if (!$usulan && $ditolak && !str_contains($jawab, $ditolak[0])) $jawab .= "\n\n⚠ Ditolak sistem: " . implode(' ', array_unique($ditolak));
    if (trim($jawab) === '') $jawab = $usulan ? 'Silakan periksa usulan di bawah, lalu tekan Simpan.' : 'Maaf, saya belum bisa menyelesaikannya. Coba jelaskan dengan cara lain.';
    asisten_riwayat_tambah('user', $teks !== '' ? $teks : 'Lihat foto ini.' . ($bagian_foto ? ' [melampirkan ' . count($bagian_foto) . ' foto]' : ''));
    $catat = '';
    foreach ($usulan as $u) $catat .= "\n[Usulan " . $u['id'] . ' ' . $u['alat'] . ' menunggu tombol Simpan: ' . json_encode($u['data'], JSON_UNESCAPED_UNICODE) . ']';
    if (!$usulan && $ditolak) $catat .= "\n[Catatan sistem] Tidak ada kartu dibuat; ditolak: " . implode(' | ', $ditolak);
    asisten_riwayat_tambah('assistant', $jawab . $catat);
    return ['ok' => true, 'teks' => $jawab, 'usulan' => array_map(fn($u) => ['id' => $u['id'], 'judul' => $u['judul'], 'baris' => $u['baris']], $usulan), 'oleh' => $oleh, 'rincian' => $rincian];
}

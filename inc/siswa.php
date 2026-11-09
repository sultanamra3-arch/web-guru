<?php
/*
 * Data siswa milik SiGuru (terpisah dari perpustakaan).
 * guru_siswa = identitas; guru_kelas = daftar kelas per tahun ajaran; guru_kelas_siswa = siswa di kelas pada tahun itu.
 * Fungsi lama (siswa_kelas dst.) tetap mengembalikan kolom member_id/member_name/gender agar modul lain tidak berubah.
 */
declare(strict_types=1);

function setelan(string $k, ?string $bawaan = null): ?string
{
    static $c = [];
    if (!array_key_exists($k, $c)) $c[$k] = nilai('SELECT v FROM guru_setelan WHERE k = ?', [$k]);
    $v = $c[$k];
    return ($v === false || $v === null || $v === '') ? $bawaan : (string)$v;
}

function setelan_simpan(string $k, string $v): void
{
    q('INSERT INTO guru_setelan (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
}

/** Tahun ajaran aktif, mis. 2026/2027. */
function tahun_aktif(): string
{
    return setelan('tahun_ajaran', (string)k('tahun_ajaran', date('n') >= 7 ? date('Y') . '/' . (date('Y') + 1) : (date('Y') - 1) . '/' . date('Y')));
}

function tahun_berikut(string $t): string
{
    return preg_match('/^(\d{4})\/(\d{4})$/', $t, $m) ? ($m[1] + 1) . '/' . ($m[2] + 1) : $t;
}

function daftar_kelas(?string $tahun = null): array
{
    $tahun ??= tahun_aktif();
    $k = q('SELECT nama FROM guru_kelas WHERE tahun = ?', [$tahun])->fetchAll(PDO::FETCH_COLUMN);
    usort($k, 'banding_kelas');
    return $k;
}

/** Urutkan VII A, VII B, VIII A, IX A … (romawi/angka dulu, lalu huruf). */
function banding_kelas(string $a, string $b): int
{
    return [tingkat_kelas($a), $a] <=> [tingkat_kelas($b), $b];
}

function tingkat_kelas(string $k): int
{
    if (preg_match('/^\s*(?:kelas\s*)?(xii|xi|x|ix|viii|vii|vi|v|iv|iii|ii|i|\d{1,2})\b/i', $k, $m)) {
        $r = ['i' => 1, 'ii' => 2, 'iii' => 3, 'iv' => 4, 'v' => 5, 'vi' => 6, 'vii' => 7, 'viii' => 8, 'ix' => 9, 'x' => 10, 'xi' => 11, 'xii' => 12];
        return ctype_digit($m[1]) ? (int)$m[1] : $r[strtolower($m[1])];
    }
    return 99;
}

/** "7a", "Kelas 7 A", "VII-A" → "VII A". Teks lain dibiarkan. */
function rapikan_kelas(string $k): string
{
    $k = trim(preg_replace('/\s+/', ' ', $k));
    if (preg_match('/^(?:kelas\s*)?(7|8|9|vii|viii|ix)\s*[-.\s]?\s*([a-z])?\s*$/i', $k, $m)) {
        $rom = ['7' => 'VII', '8' => 'VIII', '9' => 'IX'][$m[1]] ?? strtoupper($m[1]);
        return $rom . (isset($m[2]) && $m[2] !== '' ? ' ' . strtoupper($m[2]) : '');
    }
    return $k;
}

function siswa_kelas(string $kelas, ?string $tahun = null): array
{
    $tahun ??= tahun_aktif();
    return semua("SELECT s.kode AS member_id, s.nama AS member_name, (s.jk = 'L') AS gender, s.nisn, s.jk, ks.absen
        FROM guru_kelas_siswa ks JOIN guru_siswa s ON s.kode = ks.kode
        WHERE ks.tahun = ? AND ks.kelas = ? AND s.status = 'aktif'
        ORDER BY ks.absen IS NULL, ks.absen, s.nama", [$tahun, $kelas]);
}

function kelas_valid(string $kelas): bool
{
    return $kelas !== '' && in_array($kelas, daftar_kelas(), true);
}

/** Kelas yang diajar guru menurut jadwalnya (+ kelas walinya), lalu semua kelas. */
function kelas_saya(): array
{
    $j = q('SELECT DISTINCT kelas FROM guru_jadwal WHERE guru_id = ? ORDER BY kelas', [uid()])->fetchAll(PDO::FETCH_COLUMN);
    $w = q('SELECT nama FROM guru_kelas WHERE tahun = ? AND wali_id = ?', [tahun_aktif(), uid()])->fetchAll(PDO::FETCH_COLUMN);
    $semua = daftar_kelas();
    return array_values(array_unique(array_merge($w, array_intersect($j, $semua), $semua)));
}

function mapel_saya(): array
{
    $j = q('SELECT DISTINCT mapel FROM guru_jadwal WHERE guru_id = ? ORDER BY mapel', [uid()])->fetchAll(PDO::FETCH_COLUMN);
    $m = array_filter(array_map('trim', explode(',', (string)(pengguna()['mapel'] ?? ''))));
    return array_values(array_unique(array_merge($m, $j)));
}

function nama_siswa(string $kode): string
{
    return (string)(nilai('SELECT nama FROM guru_siswa WHERE kode = ?', [$kode]) ?: $kode);
}

function kode_siswa_baru(): string
{
    do {
        $k = (string)random_int(10000000, 99999999);
    } while (nilai('SELECT 1 FROM guru_siswa WHERE kode = ?', [$k]));
    return $k;
}

function pastikan_kelas(string $kelas, ?string $tahun = null): void
{
    q('INSERT IGNORE INTO guru_kelas (tahun, nama) VALUES (?, ?)', [$tahun ?? tahun_aktif(), $kelas]);
}

function rapikan_nama(string $n): string
{
    $n = trim(preg_replace('/\s+/u', ' ', $n));
    // HURUF BESAR SEMUA → Huruf Awal Besar
    if ($n !== '' && mb_strtoupper($n) === $n) $n = mb_convert_case(mb_strtolower($n), MB_CASE_TITLE);
    return mb_substr($n, 0, 120);
}

/**
 * Simpan banyak siswa sekaligus ke tahun aktif.
 * Cocokkan: NISN sama → siswa yang sama (kelas dipindah, nama dirapikan); tanpa NISN → nama sama di kelas yang sama dilewati.
 */
function simpan_banyak_siswa(array $baris): array
{
    $tahun = tahun_aktif();
    $hasil = ['baru' => 0, 'diperbarui' => 0, 'dilewati' => 0];
    db()->beginTransaction();
    foreach ($baris as $r) {
        $nama = rapikan_nama((string)($r['nama'] ?? ''));
        $kelas = mb_substr(trim((string)($r['kelas'] ?? '')), 0, 50);
        if ($nama === '' || $kelas === '' || mb_strlen($nama) < 2) { $hasil['dilewati']++; continue; }
        $nisn = preg_replace('/\D/', '', (string)($r['nisn'] ?? ''));
        $nisn = $nisn === '' ? null : substr($nisn, 0, 20);
        $jk = strtoupper(substr(trim((string)($r['jk'] ?? '')), 0, 1));
        $jk = in_array($jk, ['L', 'P'], true) ? $jk : null;
        pastikan_kelas($kelas, $tahun);
        $kode = $nisn ? nilai('SELECT kode FROM guru_siswa WHERE nisn = ?', [$nisn]) : false;
        if (!$kode) {
            $kode = nilai('SELECT s.kode FROM guru_siswa s JOIN guru_kelas_siswa ks ON ks.kode = s.kode AND ks.tahun = ?
                WHERE ks.kelas = ? AND LOWER(s.nama) = LOWER(?)', [$tahun, $kelas, $nama]);
            if ($kode) { $hasil['dilewati']++; continue; }
        }
        if ($kode) {
            q('UPDATE guru_siswa SET nama = ?, jk = COALESCE(?, jk), status = ?, diubah = ? WHERE kode = ?', [$nama, $jk, 'aktif', sekarang(), $kode]);
            $hasil['diperbarui']++;
        } else {
            $kode = kode_siswa_baru();
            q('INSERT INTO guru_siswa (kode, nisn, nama, jk, status, dibuat, diubah) VALUES (?,?,?,?,?,?,?)', [$kode, $nisn, $nama, $jk, 'aktif', sekarang(), sekarang()]);
            $hasil['baru']++;
        }
        $absen = isset($r['absen']) && ctype_digit((string)$r['absen']) ? (int)$r['absen'] : null;
        q('INSERT INTO guru_kelas_siswa (tahun, kode, kelas, absen) VALUES (?,?,?,?)
           ON DUPLICATE KEY UPDATE kelas = VALUES(kelas), absen = COALESCE(VALUES(absen), absen)', [$tahun, $kode, $kelas, $absen]);
    }
    db()->commit();
    return $hasil;
}

/* ---------- Baca berkas Excel (.xlsx) / CSV tanpa pustaka tambahan ---------- */

function baca_tabel_berkas(string $path, string $nama_asli): array
{
    $ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));
    if ($ext === 'xlsx') return baca_xlsx($path);
    if (in_array($ext, ['csv', 'txt'], true)) {
        $teks = (string)file_get_contents($path);
        $teks = preg_replace('/^\xEF\xBB\xBF/', '', $teks);
        if (!mb_check_encoding($teks, 'UTF-8')) $teks = mb_convert_encoding($teks, 'UTF-8', 'Windows-1252');
        $baris1 = strtok($teks, "\n");
        $pemisah = substr_count((string)$baris1, ';') > substr_count((string)$baris1, ',') ? ';' : (substr_count((string)$baris1, "\t") ? "\t" : ',');
        $out = [];
        foreach (preg_split('/\r\n|\n|\r/', $teks) as $b) if (trim($b) !== '') $out[] = str_getcsv($b, $pemisah, '"', '\\');
        return $out;
    }
    if ($ext === 'xls') throw new RuntimeException('Format .xls lama belum didukung. Buka di Excel lalu "Simpan sebagai" .xlsx, atau .csv.');
    throw new RuntimeException('Gunakan berkas .xlsx atau .csv.');
}

function baca_xlsx(string $path): array
{
    if (!class_exists('ZipArchive')) throw new RuntimeException('Server tidak mendukung pembacaan .xlsx. Simpan sebagai .csv.');
    $z = new ZipArchive();
    if ($z->open($path) !== true) throw new RuntimeException('Berkas Excel tidak bisa dibuka.');
    $bersama = [];
    if (($s = $z->getFromName('xl/sharedStrings.xml')) !== false) {
        $x = @simplexml_load_string($s);
        if ($x) foreach ($x->si as $si) {
            $t = '';
            if (isset($si->t)) $t = (string)$si->t;
            foreach ($si->r as $r) $t .= (string)$r->t;
            $bersama[] = $t;
        }
    }
    // lembar pertama menurut workbook + rels
    $lembar = 'xl/worksheets/sheet1.xml';
    $wb = @simplexml_load_string((string)$z->getFromName('xl/workbook.xml'));
    $rels = @simplexml_load_string((string)$z->getFromName('xl/_rels/workbook.xml.rels'));
    if ($wb && $rels && isset($wb->sheets->sheet[0])) {
        $rid = (string)$wb->sheets->sheet[0]->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        foreach ($rels->Relationship as $r) {
            if ((string)$r['Id'] === $rid) { $lembar = 'xl/' . ltrim(preg_replace('#^/?xl/#', '', (string)$r['Target']), '/'); break; }
        }
    }
    $xml = $z->getFromName($lembar);
    $z->close();
    if ($xml === false) throw new RuntimeException('Lembar kerja tidak ditemukan.');
    $x = @simplexml_load_string($xml);
    if (!$x) throw new RuntimeException('Isi Excel tidak terbaca.');
    $out = [];
    foreach ($x->sheetData->row as $row) {
        $baris = [];
        foreach ($row->c as $c) {
            $ref = (string)$c['r'];
            $kol = 0;
            foreach (str_split(preg_replace('/\d/', '', $ref)) as $h) $kol = $kol * 26 + (ord($h) - 64);
            $t = (string)$c['t'];
            $v = $t === 's' ? ($bersama[(int)$c->v] ?? '') : ($t === 'inlineStr' ? (string)$c->is->t : (string)$c->v);
            $baris[$kol - 1] = $v;
        }
        if ($baris) {
            $maks = max(array_keys($baris));
            $penuh = [];
            for ($i = 0; $i <= $maks; $i++) $penuh[] = $baris[$i] ?? '';
            $out[] = $penuh;
        }
        if (count($out) > 3000) break;
    }
    return $out;
}

/** Cari baris judul (yang memuat "nama") lalu petakan kolom nama/nisn/jk/kelas/absen. */
function petakan_tabel_siswa(array $tabel): array
{
    $pola = [
        'nama' => '/^(nama( lengkap| peserta didik| siswa)?|nama pd)$/i',
        'nisn' => '/^nisn$/i',
        'jk' => '/^(jk|l\/p|jenis kelamin|kelamin|gender)$/i',
        'kelas' => '/^(rombel( saat ini)?|kelas|rombongan belajar)$/i',
        'absen' => '/^(no\.? ?absen|absen)$/i',
    ];
    foreach (array_slice($tabel, 0, 15) as $i => $baris) {
        $peta = [];
        foreach ($baris as $j => $sel) {
            $s = strtolower(trim(preg_replace('/\s+/', ' ', (string)$sel)));
            foreach ($pola as $kunci => $p) if (!isset($peta[$kunci]) && preg_match($p, $s)) $peta[$kunci] = $j;
        }
        if (isset($peta['nama'])) {
            $hasil = [];
            foreach (array_slice($tabel, $i + 1) as $b) {
                $nama = trim((string)($b[$peta['nama']] ?? ''));
                if ($nama === '' || preg_match('/^\d+$/', $nama)) continue;
                $hasil[] = [
                    'nama' => $nama,
                    'nisn' => isset($peta['nisn']) ? trim((string)($b[$peta['nisn']] ?? '')) : '',
                    'jk' => isset($peta['jk']) ? strtoupper(substr(trim((string)($b[$peta['jk']] ?? '')), 0, 1)) : '',
                    'kelas' => isset($peta['kelas']) ? trim((string)($b[$peta['kelas']] ?? '')) : '',
                    'absen' => isset($peta['absen']) ? trim((string)($b[$peta['absen']] ?? '')) : '',
                ];
            }
            return ['ok' => true, 'siswa' => $hasil, 'kolom' => array_keys($peta)];
        }
    }
    // tanpa judul: anggap kolom pertama yang berisi huruf = nama
    $hasil = [];
    foreach ($tabel as $b) {
        foreach ($b as $sel) {
            $sel = trim((string)$sel);
            if ($sel !== '' && preg_match('/\p{L}{2,}/u', $sel) && !preg_match('/^(no|nama|kelas)\b/i', $sel)) { $hasil[] = ['nama' => $sel, 'nisn' => '', 'jk' => '', 'kelas' => '', 'absen' => '']; break; }
        }
    }
    return ['ok' => (bool)$hasil, 'siswa' => $hasil, 'kolom' => ['nama']];
}

/** Baca daftar nama siswa dari foto (daftar hadir, buku absen, dsb.). */
function ai_baca_daftar_siswa(array $foto): array
{
    $p = "Foto terlampir adalah daftar siswa (daftar hadir/absen/rapor/papan nama kelas) di sebuah SMP di Indonesia.\n"
        . "Salin SEMUA nama siswa sesuai urutan di foto. Jangan menambah atau menebak nama yang tidak terbaca; bila sebagian huruf tidak jelas, tulis sebisanya dan beri yakin=false.\n"
        . "Ambil juga nomor urut/absen, NISN (10 digit) dan jenis kelamin (L/P) bila tertulis. Tulis nama dengan huruf awal besar.\n"
        . "Bila judul/kolom menyebut kelas (mis. \"VII A\", \"Kelas 8B\"), isi \"kelas\".\n"
        . 'Balas HANYA JSON: {"kelas":"","siswa":[{"absen":"1","nama":"","nisn":"","jk":"","yakin":true}]}';
    $isi = array_merge([['type' => 'text', 'text' => $p]], ai_bagian_foto($foto, 4));
    $r = ai_minta([['role' => 'user', 'content' => $isi]], ['gambar' => true, 'maks_token' => 6000, 'waktu' => 120, 'suhu' => 0]);
    if (!$r['ok']) return $r;
    $j = ai_ambil_json((string)$r['pesan']['content']);
    if (!is_array($j) || !is_array($j['siswa'] ?? null)) return ['ok' => false, 'galat' => 'AI tidak menemukan daftar nama di foto.', 'rincian' => [$r['oleh']]];
    $out = [];
    foreach ($j['siswa'] as $s) {
        if (!is_array($s) || trim((string)($s['nama'] ?? '')) === '') continue;
        $out[] = ['nama' => rapikan_nama((string)$s['nama']), 'nisn' => preg_replace('/\D/', '', (string)($s['nisn'] ?? '')),
            'jk' => strtoupper(substr((string)($s['jk'] ?? ''), 0, 1)), 'absen' => preg_replace('/\D/', '', (string)($s['absen'] ?? '')),
            'kelas' => '', 'ragu' => ($s['yakin'] ?? true) === false];
    }
    return ['ok' => true, 'siswa' => $out, 'kelas' => rapikan_kelas((string)($j['kelas'] ?? '')), 'oleh' => $r['oleh']];
}

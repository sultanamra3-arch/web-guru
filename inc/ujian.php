<?php
/* Ujian online: siswa mengerjakan paket soal di HP. */
declare(strict_types=1);

const HURUF_OPSI = ['A', 'B', 'C', 'D', 'E'];

function ujian_ambil(int $id, bool $milik_saya = true): ?array
{
    $u = $milik_saya
        ? satu('SELECT * FROM guru_ujian WHERE id = ? AND guru_id = ?', [$id, uid()])
        : satu('SELECT * FROM guru_ujian WHERE id = ?', [$id]);
    return $u ?: null;
}

function ujian_kode_baru(): string
{
    $huruf = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    do {
        $k = '';
        for ($i = 0; $i < 6; $i++) $k .= $huruf[random_int(0, strlen($huruf) - 1)];
    } while (nilai('SELECT 1 FROM guru_ujian WHERE kode = ?', [$k]));
    return $k;
}

/** Token 4 angka; berganti tiap token_menit (0 = tetap). $geser -1 = token sebelumnya. */
function ujian_token(array $u, int $geser = 0): string
{
    $m = (int)$u['token_menit'];
    $slot = $m > 0 ? intdiv(time(), $m * 60) + $geser : 0;
    $h = hash_hmac('sha256', 'token-' . $slot, (string)$u['rahasia']);
    return str_pad((string)(hexdec(substr($h, 0, 8)) % 10000), 4, '0', STR_PAD_LEFT);
}

function ujian_token_sisa(array $u): int
{
    $m = (int)$u['token_menit'];
    return $m > 0 ? $m * 60 - (time() % ($m * 60)) : 0;
}

function ujian_token_cocok(array $u, string $t): bool
{
    $t = preg_replace('/\D/', '', $t);
    return $t !== '' && (hash_equals(ujian_token($u), $t) || ((int)$u['token_menit'] > 0 && hash_equals(ujian_token($u, -1), $t)));
}

/** Salin soal dari paket (dibekukan agar perubahan bank soal tidak mengubah ujian yang berjalan). */
function ujian_soal_dari_paket(int $paket_id, float $poin_uraian): ?array
{
    $d = satu("SELECT * FROM guru_dokumen WHERE id = ? AND guru_id = ? AND jenis = 'paket'", [$paket_id, uid()]);
    if (!$d) return null;
    $cfg = json_decode((string)$d['isi'], true) ?: [];
    $ids = array_values(array_filter(array_map('intval', (array)($cfg['ids'] ?? []))));
    if (!$ids) return [];
    $peta = [];
    $tanda = implode(',', array_fill(0, count($ids), '?'));
    foreach (semua("SELECT * FROM guru_soal WHERE id IN ($tanda) AND guru_id = ?", array_merge($ids, [uid()])) as $s) $peta[(int)$s['id']] = $s;
    $out = [];
    foreach ($ids as $id) {
        if (!isset($peta[$id])) continue;
        $s = $peta[$id];
        $opsi = json_decode((string)$s['opsi'], true) ?: [];
        $tipe = $s['tipe'];
        $kunci = trim((string)$s['kunci']);
        if ($tipe === 'bs') {
            if (!$opsi) $opsi = ['A' => 'Benar', 'B' => 'Salah'];
            // kunci bisa "A"/"B" atau "B"(enar)/"S"(alah)
            if (!isset($opsi[strtoupper($kunci)]) || strtoupper($kunci) === 'S') $kunci = preg_match('/^s/i', $kunci) ? 'B' : 'A';
        }
        if (in_array($tipe, ['pg', 'bs'], true)) $kunci = strtoupper(mb_substr($kunci, 0, 1));
        $go = gambar_opsi($s['gambar_opsi'] ?? null);
        $out[] = [
            'tipe' => $tipe,
            'teks' => (string)$s['pertanyaan'],
            'gambar' => $s['gambar'] ?? null,
            'gambar_opsi' => in_array($tipe, ['pg', 'bs'], true) ? $go : [],
            'opsi' => $opsi,
            'kunci' => $kunci,
            'pembahasan' => (string)$s['pembahasan'],
            'maks' => $tipe === 'esai' ? $poin_uraian : 1.0,
        ];
    }
    return $out;
}

function ujian_soal(array $u): array
{
    return json_decode((string)$u['soal'], true) ?: [];
}

/** Urutan soal & opsi khusus satu siswa. */
function ujian_buat_urutan(array $u): array
{
    $soal = ujian_soal($u);
    $s = array_keys($soal);
    if ($u['acak_soal']) shuffle($s);
    $o = [];
    foreach ($soal as $i => $x) {
        $k = array_keys($x['opsi']);
        if ($u['acak_opsi'] && $x['tipe'] === 'pg') shuffle($k);
        $o[$i] = $k;
    }
    return ['s' => $s, 'o' => $o];
}

/** Data soal untuk peramban siswa (tanpa kunci). */
function ujian_data_siswa(array $u, array $p): array
{
    $soal = ujian_soal($u);
    $ur = json_decode((string)$p['urutan'], true) ?: ujian_buat_urutan($u);
    $out = [];
    foreach ($ur['s'] as $i) {
        if (!isset($soal[$i])) continue;
        $x = $soal[$i];
        $opsi = [];
        foreach ($ur['o'][$i] ?? array_keys($x['opsi']) as $k) $opsi[] = ['k' => (string)$k, 't' => (string)($x['opsi'][$k] ?? ''), 'g' => gambar_url($x['gambar_opsi'][$k] ?? null)];
        $out[] = ['i' => $i, 'tipe' => $x['tipe'], 'teks' => $x['teks'], 'g' => gambar_url($x['gambar'] ?? null), 'opsi' => $opsi];
    }
    return $out;
}

function ujian_norm(string $s): string
{
    $s = mb_strtolower(trim($s));
    return preg_replace('/[^\p{L}\p{N}]+/u', '', $s);
}

/**
 * Hitung skor satu peserta. PG/BS dari kunci; isian sama persis (setelah dirapikan) = benar, selain itu perlu AI/guru;
 * uraian memakai nilai_uraian (AI/guru), bila belum ada dianggap 0 dan ditandai perlu koreksi.
 */
function ujian_hitung(array $u, array $p): array
{
    $soal = ujian_soal($u);
    $jw = json_decode((string)$p['jawaban'], true) ?: [];
    $nu = json_decode((string)$p['nilai_uraian'], true) ?: [];
    $skor = 0.0;
    $maks = 0.0;
    $perlu = false;
    $rinci = [];
    foreach ($soal as $i => $x) {
        $a = (string)($jw[$i] ?? '');
        $m = (float)$x['maks'];
        $maks += $m;
        $sk = 0.0;
        $status = '';
        if (in_array($x['tipe'], ['pg', 'bs'], true)) {
            $sk = ($a !== '' && strtoupper($a) === $x['kunci']) ? $m : 0.0;
        } elseif ($x['tipe'] === 'isian') {
            $kunci = array_filter(array_map('ujian_norm', preg_split('/\s*(?:\/|;|\batau\b|,)\s*/iu', (string)$x['kunci'])));
            if ($a === '') $sk = 0.0;
            elseif (in_array(ujian_norm($a), $kunci, true)) $sk = $m;
            elseif (isset($nu[$i])) { $sk = (float)$nu[$i]['skor']; $status = $nu[$i]['oleh'] ?? ''; }
            else { $perlu = true; $status = 'perlu'; }
        } else {
            if ($a === '') $sk = 0.0;
            elseif (isset($nu[$i])) { $sk = (float)$nu[$i]['skor']; $status = $nu[$i]['oleh'] ?? ''; }
            else { $perlu = true; $status = 'perlu'; }
        }
        $skor += $sk;
        $rinci[$i] = ['skor' => $sk, 'maks' => $m, 'status' => $status];
    }
    return ['skor' => $skor, 'maks' => $maks, 'nilai' => $maks > 0 ? round($skor / $maks * 100) : 0, 'perlu' => $perlu, 'rinci' => $rinci];
}

function ujian_simpan_hitung(array $u, array $p): array
{
    $h = ujian_hitung($u, $p);
    q('UPDATE guru_ujian_peserta SET skor = ?, nilai = ?, perlu_koreksi = ? WHERE ujian_id = ? AND kode = ?',
        [$h['skor'], $h['nilai'], $h['perlu'] ? 1 : 0, $u['id'], $p['kode']]);
    return $h;
}

/** Akhiri peserta yang waktunya sudah habis (atau semua bila ujian ditutup). */
function ujian_akhiri_kedaluwarsa(array $u, bool $semua = false): void
{
    $sql = 'SELECT * FROM guru_ujian_peserta WHERE ujian_id = ? AND mulai IS NOT NULL AND selesai IS NULL' . ($semua ? '' : ' AND batas < ?');
    $par = $semua ? [$u['id']] : [$u['id'], date('Y-m-d H:i:s', time() - 20)];
    foreach (semua($sql, $par) as $p) {
        q('UPDATE guru_ujian_peserta SET selesai = ? WHERE ujian_id = ? AND kode = ?', [$semua ? sekarang() : $p['batas'], $u['id'], $p['kode']]);
        ujian_simpan_hitung($u, $p);
    }
}

/** Nilai isian/uraian yang belum dinilai dengan AI (satu peserta). */
function ujian_nilai_ai(array $u, array $p): array
{
    $soal = ujian_soal($u);
    $jw = json_decode((string)$p['jawaban'], true) ?: [];
    $nu = json_decode((string)$p['nilai_uraian'], true) ?: [];
    $h = ujian_hitung($u, $p);
    $daftar = [];
    $jawaban = [];
    foreach ($soal as $i => $x) {
        if (($h['rinci'][$i]['status'] ?? '') !== 'perlu') continue;
        $no = (string)($i + 1);
        $daftar[] = ['no' => $no, 'jenis' => $x['tipe'] === 'isian' ? 'isian' : 'esai', 'maks' => $x['maks'],
            'teks' => 'Soal: ' . $x['teks'] . ' Kunci/pedoman: ' . trim($x['kunci'] . ' ' . $x['pembahasan'])];
        $jawaban[$no] = (string)($jw[$i] ?? '');
    }
    if (!$daftar) return ['ok' => true, 'dinilai' => 0];
    $r = koreksi_nilai_uraian($daftar, $jawaban, ujian_contoh_guru($u), (string)$u['judul']);
    if (!$r['ok']) return $r;
    $n = 0;
    foreach ($daftar as $d) {
        $x = $r['peta'][$d['no']] ?? null;
        if (!$x) continue;
        $i = (int)$d['no'] - 1;
        $sk = max(0.0, min((float)$d['maks'], round((float)($x['skor'] ?? 0) * 2) / 2));
        $nu[$i] = ['skor' => $sk, 'skor_ai' => $sk, 'catatan' => mb_substr((string)($x['catatan'] ?? ''), 0, 300), 'oleh' => 'ai', 'ragu' => !empty($x['ragu'])];
        $n++;
    }
    q('UPDATE guru_ujian_peserta SET nilai_uraian = ? WHERE ujian_id = ? AND kode = ?', [json_encode($nu, JSON_UNESCAPED_UNICODE), $u['id'], $p['kode']]);
    $p['nilai_uraian'] = json_encode($nu);
    $hh = ujian_simpan_hitung($u, $p);
    return ['ok' => true, 'dinilai' => $n, 'nilai' => $hh['nilai'], 'oleh' => $r['oleh']];
}

/** Skor uraian yang diubah guru di ujian ini → contoh untuk AI. */
function ujian_contoh_guru(array $u): array
{
    $soal = ujian_soal($u);
    $c = [];
    foreach (semua('SELECT jawaban, nilai_uraian FROM guru_ujian_peserta WHERE ujian_id = ? AND nilai_uraian IS NOT NULL', [$u['id']]) as $r) {
        $jw = json_decode((string)$r['jawaban'], true) ?: [];
        foreach (json_decode((string)$r['nilai_uraian'], true) ?: [] as $i => $x) {
            if (($x['oleh'] ?? '') !== 'guru' || !isset($x['skor_ai']) || abs((float)$x['skor'] - (float)$x['skor_ai']) < 0.5) continue;
            $c[] = ['no' => (string)($i + 1), 'jawaban' => mb_substr((string)($jw[$i] ?? ''), 0, 300), 'skor' => $x['skor'], 'skor_ai' => $x['skor_ai'], 'maks' => $soal[$i]['maks'] ?? 0];
            if (count($c) >= 12) return $c;
        }
    }
    return $c;
}

const TERTIB_BAWAAN = "Berdoalah sebelum mulai mengerjakan.\nKerjakan sendiri, tidak boleh bertanya atau mencontek teman.\nPeriksa kembali jawabanmu sebelum menekan Selesai.";

function ujian_tertib_bawaan(int $guru): string
{
    return (string)setelan('tertib_ujian:' . $guru, TERTIB_BAWAAN);
}

function ujian_latihan(array $u): bool
{
    return ($u['mode'] ?? 'ujian') === 'latihan';
}

/** Poin tata tertib yang dibaca siswa: tulisan guru + poin otomatis sesuai pengaturan ujian. */
function ujian_tata_tertib(array $u): array
{
    $guru = $u['tata_tertib'] !== null && $u['tata_tertib'] !== '' ? (string)$u['tata_tertib'] : ujian_tertib_bawaan((int)$u['guru_id']);
    $poin = array_values(array_filter(array_map('trim', preg_split('/\r\n|\n/', $guru)), fn($x) => $x !== ''));
    $menit = (int)$u['durasi'];
    $oto = [];
    if (ujian_latihan($u)) $oto[] = 'Ini <b>latihan</b>: boleh diulang, nilai dan pembahasan langsung muncul setelah selesai.';
    $oto[] = "Waktu mengerjakan <b>$menit menit</b>, dihitung sejak kamu menekan Mulai. Saat waktu habis, jawaban dikumpulkan otomatis.";
    $oto[] = 'Jawaban tersimpan otomatis. Kalau sinyal putus, tetap lanjutkan — jawaban dikirim saat sinyal kembali.';
    if (!ujian_latihan($u)) {
        $oto[] = $u['pengaman']
            ? 'Jangan keluar dari halaman ujian (membuka WA, Google, kamera, dll.). Kalau keluar <b>' . (int)$u['batas_keluar'] . ' kali</b>, jawabanmu <b>langsung dikumpulkan otomatis</b>.'
            : 'Jangan keluar dari halaman ujian (membuka WA, Google, dll.). Setiap kali keluar akan <b>tercatat</b> dan dilihat guru.';
        $oto[] = 'Satu nama hanya bisa dipakai di satu HP.';
        if ($u['tampil_hasil']) $oto[] = 'Setelah ujian ditutup guru, kamu bisa melihat <b>nilai dan pembahasan</b> lewat link yang sama di HP ini.';
    }
    return array_merge(array_map('h', $poin), $oto);
}

/** Alasan tidak bisa mulai (jadwal), '' bila boleh. */
function ujian_bisa_masuk_kerja(array $u): string
{
    if ($u['mulai'] && strtotime($u['mulai']) > time()) return 'belum';
    if ($u['selesai'] && strtotime($u['selesai']) <= time()) return 'lewat';
    return '';
}

/** Hasil (nilai, jawaban benar/salah, pembahasan) boleh dilihat siswa? */
function ujian_hasil_terbuka(array $u): bool
{
    return ujian_latihan($u) || ($u['tampil_hasil'] && $u['status'] === 'tutup');
}

/** Mulai mengerjakan: buat/isi baris peserta dan kunci ke perangkat ini. */
function ujian_mulai_peserta(array $u, string $siswa, string $alat_hash): void
{
    $p = satu('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ?', [$u['id'], $siswa]);
    if (!$p || !$p['mulai']) {
        $batas = time() + (int)$u['durasi'] * 60;
        if ($u['selesai']) $batas = min($batas, strtotime($u['selesai']));
        q('INSERT INTO guru_ujian_peserta (ujian_id, kode, perangkat, mulai, batas, urutan, jawaban, ragu, terakhir) VALUES (?,?,?,?,?,?,?,?,?)
           ON DUPLICATE KEY UPDATE perangkat = VALUES(perangkat), mulai = VALUES(mulai), batas = VALUES(batas), urutan = VALUES(urutan), terakhir = VALUES(terakhir)',
            [$u['id'], $siswa, $alat_hash, sekarang(), date('Y-m-d H:i:s', $batas), json_encode(ujian_buat_urutan($u)), '{}', '{}', sekarang()]);
    } else {
        q('UPDATE guru_ujian_peserta SET perangkat = ?, terakhir = ? WHERE ujian_id = ? AND kode = ?', [$alat_hash, sekarang(), $u['id'], $siswa]);
    }
}

/** Latihan: ulangi dari awal (nilai terbaik disimpan). */
function ujian_ulang_latihan(array $u, array $p): void
{
    $terbaik = max((float)($p['nilai_terbaik'] ?? 0), (float)($p['nilai'] ?? 0));
    $batas = time() + (int)$u['durasi'] * 60;
    if ($u['selesai']) $batas = min($batas, strtotime($u['selesai']));
    q('UPDATE guru_ujian_peserta SET mulai = ?, batas = ?, selesai = NULL, urutan = ?, jawaban = ?, ragu = ?, nomor = 0, keluar = 0, skor = NULL, nilai = NULL,
       nilai_uraian = NULL, perlu_koreksi = 0, alasan = NULL, percobaan = percobaan + 1, nilai_terbaik = ?, terakhir = ? WHERE ujian_id = ? AND kode = ?',
        [sekarang(), date('Y-m-d H:i:s', $batas), json_encode(ujian_buat_urutan($u)), '{}', '{}', $terbaik, sekarang(), $u['id'], $p['kode']]);
}

/** "Senin, 10 November 2026 pukul 07.30" */
function ujian_waktu_teks(?string $dt): string
{
    if (!$dt) return '';
    $t = strtotime($dt);
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][(int)date('w', $t)];
    return $hari . ', ' . tgl_panjang(substr($dt, 0, 10)) . ' pukul ' . date('H.i', $t);
}

function ujian_status_teks(array $u): string
{
    return ['draf' => 'Belum dibuka', 'buka' => 'Sedang dibuka', 'tutup' => 'Ditutup'][$u['status']] ?? $u['status'];
}

function ujian_url_siswa(array $u): string
{
    $s = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $s . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/u/?k=' . $u['kode'];
}

/** Nama siswa per status untuk dibagikan ke grup WA (tanpa nilai). */
function ujian_status_kumpul(array $u): array
{
    ujian_akhiri_kedaluwarsa($u);
    $peserta = [];
    foreach (semua('SELECT kode, mulai, selesai FROM guru_ujian_peserta WHERE ujian_id = ?', [$u['id']]) as $p) $peserta[$p['kode']] = $p;
    $daftar = [];
    foreach (ujian_siswa($u) as $s) {
        $daftar[] = [$s['member_name'], $peserta[$s['member_id']] ?? null];
        unset($peserta[$s['member_id']]);
    }
    foreach ($peserta as $k => $p) $daftar[] = [nama_siswa((string)$k), $p];
    $o = ['sudah' => [], 'jalan' => [], 'belum' => [], 'perbaiki' => []];
    foreach ($daftar as [$nama, $p]) {
        if (!$p || !$p['mulai']) $o['belum'][] = $nama;
        elseif ($p['selesai']) $o['sudah'][] = $nama;
        else $o['jalan'][] = $nama;
    }
    return $o;
}

/* ===================== Remedial & pengayaan ===================== */

/** Siswa peserta ujian: seluruh kelas, atau hanya siswa tertentu (latihan remedial/pengayaan). */
function ujian_siswa(array $u): array
{
    $s = siswa_kelas((string)$u['kelas'], (string)($u['tahun'] ?: tahun_aktif((int)$u['guru_id'])), (int)$u['guru_id']);
    $k = json_decode((string)($u['khusus'] ?? ''), true);
    if (is_array($k) && $k) {
        $boleh = array_flip(array_map('strval', $k));
        $s = array_values(array_filter($s, fn($x) => isset($boleh[(string)$x['member_id']])));
    }
    return $s;
}

/** Nilai yang dipakai untuk satu peserta (latihan: nilai terbaik). null bila belum selesai. */
function ujian_nilai_peserta(array $u, array $p): ?float
{
    $nl = $p['selesai'] && $p['nilai'] !== null ? (float)$p['nilai'] : null;
    if (ujian_latihan($u) && $p['nilai_terbaik'] !== null) $nl = max((float)$p['nilai_terbaik'], (float)($nl ?? 0));
    return $nl;
}

/** Kirim nilai ujian ke Penilaian (buat penilaian bila belum ada). Mengembalikan [id penilaian, jumlah nilai]. */
function ujian_kirim_penilaian(array $u, float $kktp, string $jenis = 'Ulangan Harian'): array
{
    $pid = (int)$u['penilaian_id'];
    if (!$pid || !nilai('SELECT 1 FROM guru_penilaian WHERE id = ? AND guru_id = ?', [$pid, uid()])) {
        q('INSERT INTO guru_penilaian (guru_id, kelas, mapel, judul, jenis, tanggal, kktp, dibuat) VALUES (?,?,?,?,?,?,?,?)',
            [uid(), $u['kelas'], $u['mapel'] ?: '-', mb_substr($u['judul'] . ' (online)', 0, 200), $jenis,
             substr((string)($u['mulai'] ?: $u['dibuat']), 0, 10), $kktp, sekarang()]);
        $pid = (int)db()->lastInsertId();
        q('UPDATE guru_ujian SET penilaian_id = ? WHERE id = ?', [$pid, $u['id']]);
    }
    $n = 0;
    foreach (semua('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ?', [$u['id']]) as $p) {
        $nl = ujian_nilai_peserta($u, $p);
        if ($nl === null) continue;
        q('INSERT INTO guru_nilai (penilaian_id, member_id, nilai, catatan) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), catatan = VALUES(catatan)',
            [$pid, $p['kode'], $nl, (ujian_latihan($u) ? 'Latihan online, nilai terbaik dari ' . (int)$p['percobaan'] . ' percobaan' : 'Ujian online') . ($p['perlu_koreksi'] ? ' (uraian belum dinilai)' : '')]);
        $n++;
    }
    return [$pid, $n];
}

/** KKTP ujian: dari Penilaian yang terhubung, atau 75. */
function ujian_kktp(array $u): float
{
    if ($u['kktp'] !== null && $u['kktp'] !== '') return (float)$u['kktp'];
    $k = $u['penilaian_id'] ? nilai('SELECT kktp FROM guru_penilaian WHERE id = ? AND guru_id = ?', [$u['penilaian_id'], uid()]) : null;
    return $k !== null && $k !== false ? (float)$k : 75.0;
}

/** Soal pilihan ganda baru dari AI dalam bentuk snapshot ujian. */
function ujian_soal_ai(array $u, int $jumlah, string $tingkat, string $fokus): array
{
    if ($jumlah < 1) return [];
    $r = ai_buat_soal(['mapel' => $u['mapel'] ?: 'umum', 'kelas' => (string)$u['kelas'], 'topik' => $u['judul'],
        'tingkat' => $tingkat, 'catatan' => $fokus, 'jml_pg' => $jumlah, 'jumlah_opsi' => 4]);
    if (!$r['ok']) return [];
    $out = [];
    foreach ($r['soal'] as $s) {
        if ($s['tipe'] !== 'pg' || count($s['opsi']) < 2) continue;
        $out[] = ['tipe' => 'pg', 'teks' => $s['pertanyaan'], 'gambar' => null, 'gambar_opsi' => [], 'opsi' => $s['opsi'],
            'kunci' => strtoupper(mb_substr($s['kunci'], 0, 1)), 'pembahasan' => (string)($s['pembahasan'] ?? ''), 'maks' => 1.0, 'ai' => 1];
    }
    return $out;
}

/**
 * Buat latihan remedial / pengayaan dari ujian asal untuk siswa tertentu.
 * $o: kode[] , kktp, batasi (bool), ai (jumlah soal AI)
 * Remedial: soal yang paling banyak dijawab salah oleh siswa remedial (+ soal AI mirip bila diminta).
 * Pengayaan: soal AI tingkat sulit (bila AI gagal: soal tersulit dari ujian asal).
 */
function ujian_buat_tindak(array $u, string $jenis, array $o): array
{
    $kode = array_values(array_unique(array_map('strval', (array)$o['kode'])));
    if (!$kode) return ['ok' => false, 'galat' => 'Tidak ada siswa untuk ' . $jenis . '.'];
    $soal = ujian_soal($u);
    $salah = array_fill(0, count($soal), 0);   // salah oleh siswa terpilih
    $semua = array_fill(0, count($soal), 0);   // salah oleh semua peserta
    foreach (semua('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ? AND mulai IS NOT NULL AND selesai IS NOT NULL', [$u['id']]) as $p) {
        $h = ujian_hitung($u, $p);
        foreach ($soal as $i => $_) {
            $r = $h['rinci'][$i] ?? null;
            if (!$r || $r['skor'] >= $r['maks']) continue;
            $semua[$i]++;
            if (in_array((string)$p['kode'], $kode, true)) $salah[$i]++;
        }
    }
    $pilih = [];
    $catatan = [];
    if ($jenis === 'remedial') {
        $urut = array_keys($salah);
        usort($urut, fn($a, $b) => [$salah[$b], $semua[$b], $a] <=> [$salah[$a], $semua[$a], $b]);
        foreach ($urut as $i) if ($salah[$i] > 0) $pilih[] = $i;
        if (count($pilih) < 5) foreach ($urut as $i) if (!in_array($i, $pilih, true) && count($pilih) < min(5, count($soal))) $pilih[] = $i;
        $baru = array_map(fn($i) => $soal[$i], $pilih);
        if ((int)$o['ai'] > 0) {
            $fokus = "Soal remedial (lebih mudah–sedang, bertahap) untuk siswa yang belum tuntas. Fokus pada materi soal yang banyak dijawab salah:\n- "
                . implode("\n- ", array_map(fn($s) => mb_substr(strip_tags((string)$s['teks']), 0, 160), array_slice($baru, 0, 6)));
            $ai = ujian_soal_ai($u, (int)$o['ai'], 'mudah sampai sedang (LOTS–MOTS)', $fokus);
            if ($ai) $baru = array_merge($baru, $ai); else $catatan[] = 'soal tambahan AI gagal dibuat';
        }
    } else {
        $fokus = 'Soal pengayaan HOTS (C4–C6) untuk siswa yang sudah sangat menguasai materi ujian ini. Gunakan konteks nyata, analisis, dan penalaran. Materi ujian:'
            . "\n- " . implode("\n- ", array_map(fn($s) => mb_substr(strip_tags((string)$s['teks']), 0, 120), array_slice($soal, 0, 8)));
        $baru = ujian_soal_ai($u, max(3, (int)$o['ai']), 'sulit (HOTS, C4–C6)', $fokus);
        if (!$baru) {
            $urut = array_keys($semua);
            usort($urut, fn($a, $b) => [$semua[$b], $a] <=> [$semua[$a], $b]);
            $baru = array_map(fn($i) => $soal[$i], array_slice($urut, 0, min(5, count($soal))));
            $catatan[] = 'AI gagal, dipakai soal tersulit dari ujian asal';
        }
    }
    if (!$baru) return ['ok' => false, 'galat' => 'Tidak ada soal untuk ' . $jenis . '.'];
    $judul = ($jenis === 'remedial' ? 'Remedial: ' : 'Pengayaan: ') . preg_replace('/^(Remedial|Pengayaan): /', '', (string)$u['judul']);
    $data = ['guru_id' => uid(), 'paket_id' => null, 'judul' => mb_substr($judul, 0, 200), 'mapel' => $u['mapel'], 'kelas' => $u['kelas'], 'tahun' => $u['tahun'],
        'kode' => ujian_kode_baru(), 'rahasia' => bin2hex(random_bytes(16)), 'token_menit' => 0, 'mulai' => null, 'selesai' => null,
        'durasi' => max(10, min(120, (int)round(count($baru) * 2.5))), 'acak_soal' => 1, 'acak_opsi' => 1, 'tampil_nilai' => 1,
        'status' => 'buka', 'soal' => json_encode($baru, JSON_UNESCAPED_UNICODE), 'dibuat' => sekarang(),
        'mode' => 'latihan', 'pengaman' => 0, 'tampil_hasil' => 1, 'tampil_tertib' => (int)$u['tampil_tertib'], 'tata_tertib' => $u['tata_tertib'],
        'khusus' => json_encode($kode), 'induk_id' => (int)$u['id'], 'tindak' => $jenis, 'kktp' => (float)$o['kktp'], 'batasi_kktp' => !empty($o['batasi']) ? 1 : 0];
    q('INSERT INTO guru_ujian (' . implode(', ', array_keys($data)) . ') VALUES (' . implode(',', array_fill(0, count($data), '?')) . ')', array_values($data));
    return ['ok' => true, 'id' => (int)db()->lastInsertId(), 'soal' => count($baru), 'catatan' => $catatan];
}

/** Masukkan nilai latihan remedial ke Penilaian ujian asal (nilai naik bila lebih tinggi; dibatasi KKTP bila diatur). */
function ujian_kirim_remedial(array $r): array
{
    $induk = ujian_ambil((int)$r['induk_id']);
    if (!$induk) return ['ok' => false, 'galat' => 'Ujian asal tidak ditemukan.'];
    $kktp = (float)($r['kktp'] ?? 75);
    $pid = (int)$induk['penilaian_id'];
    if (!$pid || !nilai('SELECT 1 FROM guru_penilaian WHERE id = ? AND guru_id = ?', [$pid, uid()])) [$pid] = ujian_kirim_penilaian($induk, $kktp);
    $naik = 0; $n = 0;
    foreach (semua('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ?', [$r['id']]) as $p) {
        $nl = ujian_nilai_peserta($r, $p);
        if ($nl === null) continue;
        $n++;
        $akhir = $r['batasi_kktp'] ? min($nl, $kktp) : $nl;
        $lama = satu('SELECT nilai, catatan FROM guru_nilai WHERE penilaian_id = ? AND member_id = ?', [$pid, $p['kode']]);
        $awal = $lama && $lama['nilai'] !== null ? (float)$lama['nilai'] : null;
        $teks = 'Remedial: nilai remedial ' . ($nl + 0) . ($awal !== null ? ', nilai awal ' . ($awal + 0) : '') . ($r['batasi_kktp'] && $nl > $kktp ? ', dibatasi KKTP ' . ($kktp + 0) : '');
        if ($awal === null || $akhir > $awal) {
            q('INSERT INTO guru_nilai (penilaian_id, member_id, nilai, catatan) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), catatan = VALUES(catatan)',
                [$pid, $p['kode'], $akhir, mb_substr($teks, 0, 255)]);
            $naik++;
        } else {
            q('UPDATE guru_nilai SET catatan = ? WHERE penilaian_id = ? AND member_id = ?', [mb_substr($teks . ' (nilai awal tetap dipakai)', 0, 255), $pid, $p['kode']]);
        }
    }
    q('UPDATE guru_ujian SET penilaian_id = ? WHERE id = ?', [$pid, $r['id']]);
    return ['ok' => true, 'pid' => $pid, 'n' => $n, 'naik' => $naik];
}

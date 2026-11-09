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
    foreach (semua("SELECT * FROM guru_soal WHERE id IN ($tanda) AND (guru_id = ? OR berbagi = 1)", array_merge($ids, [uid()])) as $s) $peta[(int)$s['id']] = $s;
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
        $out[] = [
            'tipe' => $tipe,
            'teks' => (string)$s['pertanyaan'],
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
        foreach ($ur['o'][$i] ?? array_keys($x['opsi']) as $k) $opsi[] = ['k' => (string)$k, 't' => (string)($x['opsi'][$k] ?? '')];
        $out[] = ['i' => $i, 'tipe' => $x['tipe'], 'teks' => $x['teks'], 'opsi' => $opsi];
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

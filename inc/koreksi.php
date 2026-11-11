<?php
/*
 * Nilai otomatis dari foto lembar jawaban.
 * Prinsip: AI hanya MEMBACA tanda (tanpa diberi kunci), dibaca dua kali oleh dua AI berbeda.
 * Pilihan ganda & benar/salah dinilai oleh program; uraian dinilai AI terpisah tanpa foto.
 * Nomor yang meragukan ditandai untuk diperiksa guru.
 */
declare(strict_types=1);

const FORMAT_LJ = [
    'otomatis' => 'Campuran / tidak tentu',
    'lingkar' => 'Huruf pilihan dilingkari / disilang di lembar soal',
    'tulis' => 'Siswa menulis huruf jawaban di samping nomor',
    'lj' => 'Lembar Jawaban SiGuru (huruf ditulis di kotak)',
];
const JENIS_KOREKSI = ['pg' => 'pilihan ganda', 'bs' => 'benar/salah', 'isian' => 'isian', 'esai' => 'uraian', 'pendapat' => 'pendapat'];
// Tanda yang berarti MEMILIH
const TANDA_PILIH = ['lingkar', 'silang', 'centang', 'hitam', 'garis_bawah', 'tulis'];

/** "1. B" · "2. (bs) S" · "3. (isian) fotosintesis" · "4. (esai, 10 poin) ..." → daftar soal. */
function koreksi_urai_kunci(string $teks): array
{
    $out = [];
    foreach (preg_split('/\r?\n/', $teks) as $baris) {
        $b = trim($baris);
        if ($b === '') continue;
        if (!preg_match('/^(\d{1,3}[a-z]?)\s*[.)]\s*(.*)$/iu', $b, $m)) {
            if ($out) $out[count($out) - 1]['teks'] .= ' ' . $b;
            continue;
        }
        $isi = trim($m[2]);
        $jenis = null;
        $poin = null;
        if (preg_match('/^\(([^)]*)\)\s*(.*)$/u', $isi, $t)) {
            $tag = mb_strtolower($t[1]);
            $isi = trim($t[2]);
            if (preg_match('/esai|uraian/', $tag)) $jenis = 'esai';
            elseif (preg_match('/pendapat|opini/', $tag)) $jenis = 'pendapat';
            elseif (preg_match('/isian|singkat/', $tag)) $jenis = 'isian';
            elseif (preg_match('/\bbs\b|benar|salah/', $tag)) $jenis = 'bs';
            elseif (preg_match('/pg|pilihan/', $tag)) $jenis = 'pg';
            if (preg_match('/(\d+(?:[.,]\d+)?)\s*poin/', $tag, $pp)) $poin = (float)str_replace(',', '.', $pp[1]);
        }
        if (!$jenis) $jenis = preg_match('/^[a-e](?:$|[\s.)])/i', $isi) ? 'pg' : 'isian';
        $kunci_huruf = '';
        if ($jenis === 'pg') $kunci_huruf = strtoupper(mb_substr($isi, 0, 1));
        if ($jenis === 'bs') {
            $kunci_huruf = preg_match('/^(s|salah)\b/i', $isi) ? 'S' : 'B';
        }
        $out[] = [
            'no' => strtolower($m[1]),
            'jenis' => $jenis,
            'maks' => $poin ?? (in_array($jenis, ['esai', 'pendapat'], true) ? 5.0 : 1.0),
            'teks' => $isi,
            'kunci' => $kunci_huruf,
        ];
    }
    return $out;
}

/** Bangun teks kunci dari paket soal di bank soal. */
function koreksi_kunci_dari_paket(int $paket_id): ?string
{
    $d = satu("SELECT isi FROM guru_dokumen WHERE id = ? AND guru_id = ? AND jenis = 'paket'", [$paket_id, uid()]);
    if (!$d) return null;
    $cfg = json_decode((string)$d['isi'], true) ?: [];
    $ids = array_values(array_filter(array_map('intval', (array)($cfg['ids'] ?? []))));
    if (!$ids) return '';
    $soal = [];
    $tanda = implode(',', array_fill(0, count($ids), '?'));
    foreach (semua("SELECT * FROM guru_soal WHERE id IN ($tanda) AND guru_id = ?", array_merge($ids, [uid()])) as $s) $soal[(int)$s['id']] = $s;
    $baris = [];
    $no = 0;
    $pp = paket_poin($cfg);
    $f = fn($x) => (string)($x + 0);
    $tg = fn(string $j) => $pp['pg'] == 1 && $j !== 'esai' ? ($j === 'pg' ? '' : "($j) ") : '(' . $j . ', ' . $f($j === 'esai' ? $pp['uraian'] : $pp['pg']) . ' poin) ';
    $rubrik = $pp['rubrik'] !== '' ? ' Rubrik: ' . preg_replace('/\s+/', ' ', mb_substr($pp['rubrik'], 0, 500)) : '';
    foreach ($ids as $id) {
        if (!isset($soal[$id])) continue;
        $s = $soal[$id];
        $no++;
        $kunci = trim((string)$s['kunci']);
        $opsi = json_decode((string)$s['opsi'], true) ?: [];
        switch ($s['tipe']) {
            case 'pg':
                $baris[] = "$no. " . $tg('pg') . strtoupper(mb_substr($kunci, 0, 1));
                break;
            case 'bs':
                $teks = $opsi[strtoupper($kunci)] ?? $kunci;
                $baris[] = "$no. " . $tg('bs') . (preg_match('/^s/i', (string)$teks) ? 'S' : 'B');
                break;
            case 'isian':
                $baris[] = "$no. " . $tg('isian') . preg_replace('/\s+/', ' ', $kunci);
                break;
            default:
                $pedoman = trim($kunci . ' ' . trim((string)$s['pembahasan']));
                $baris[] = "$no. " . $tg('esai') . "Soal: " . preg_replace('/\s+/', ' ', mb_substr((string)$s['pertanyaan'], 0, 300))
                    . ' Pedoman: ' . preg_replace('/\s+/', ' ', mb_substr($pedoman, 0, 600)) . $rubrik;
        }
    }
    return implode("\n", $baris);
}

/* ---------- Langkah 1: baca lembar jawaban (dengan foto, tanpa kunci) ---------- */

function koreksi_prompt_baca(array $soal, string $format, bool $baca_nama): string
{
    $petunjuk_format = [
        'otomatis' => 'Lembar bisa berupa lembar soal dengan huruf pilihan yang dilingkari/disilang, atau lembar jawaban tempat siswa menulis huruf jawabannya.',
        'lingkar' => 'Siswa menandai SATU huruf pilihan (a/b/c/d/e) yang TERCETAK di lembar dengan melingkari, menyilang, mencentang, atau mengarsir.',
        'tulis' => 'Siswa MENULIS SENDIRI huruf jawabannya (tulisan tangan) di samping nomor, mis. "1. b". Huruf ini bisa kecil/besar, kadang miring atau kurang rapi.',
        'lj' => 'Ini Lembar Jawaban SiGuru: setiap nomor punya satu kotak; siswa menulis satu huruf kapital di dalam kotak. Bila huruf di kotak dicoret, huruf pengganti biasanya ditulis di samping kotak.',
    ][$format] ?? '';
    $daftar = [];
    foreach ($soal as $s) {
        $daftar[] = '- No ' . $s['no'] . ': ' . match ($s['jenis']) {
            'pg' => 'pilihan ganda',
            'bs' => 'benar/salah (siswa menandai/menulis B atau S, atau Benar/Salah)',
            'isian' => 'isian singkat (salin tulisan)',
            default => 'uraian (salin lengkap)',
        };
    }
    return "Kamu membantu guru MEMBACA lembar jawaban satu siswa (foto terlampir, bisa beberapa halaman, urut).\n"
        . "Tugasmu HANYA mencatat apa yang ditandai/ditulis siswa. JANGAN menilai, JANGAN menebak jawaban yang benar dari isi soal.\n\n"
        . "Bentuk lembar: $petunjuk_format\n\nDaftar nomor yang harus dibaca:\n" . implode("\n", $daftar) . "\n\n"
        . "CARA MEMBACA PILIHAN GANDA DAN BENAR/SALAH (sangat penting, lakukan per nomor dengan teliti):\n"
        . "1. Cari baris nomor itu dulu. Lembar bisa punya beberapa kolom nomor; jangan tertukar kolom/baris.\n"
        . "2. Periksa SETIAP huruf pilihan pada nomor itu dan catat semua tanda yang ada di \"tanda\" (opsi + jenis). Jenis tanda:\n"
        . "   lingkar = huruf dilingkari; silang = diberi X; centang = ✓; hitam = bulatan/huruf diarsir penuh; garis_bawah = digarisbawahi;\n"
        . "   tulis = huruf ditulis tangan oleh siswa (bukan huruf cetak);\n"
        . "   batal = tanda yang sengaja DIBATALKAN siswa: dicoret-coret, diberi garis dua (=), dicoret lalu ada pilihan lain, atau ditulis \"batal\";\n"
        . "   bekas_hapus = sisa penghapus/tip-ex: jauh lebih samar/tipis/abu-abu dibanding tanda lain di halaman yang sama, kertas tampak tergosok.\n"
        . "3. Bandingkan ketebalan dengan tanda jawaban di nomor-nomor lain pada halaman yang sama. Tanda yang samar karena dihapus = bekas_hapus, BUKAN jawaban.\n"
        . "4. Huruf a/b/c/d/e yang TERCETAK pada soal bukan jawaban. \"tulis\" hanya untuk tulisan tangan siswa.\n"
        . "5. X (silang) pada huruf berarti MEMILIH, kecuali ada tanda pembatalan tambahan.\n"
        . "   Lingkaran/silang yang DIBERI GARIS MENDATAR (satu atau dua garis \"=\" melintang) atau ditimpa coretan-coretan = batal, BUKAN pilihan aktif.\n"
        . "6. Bila satu pilihan dibatalkan/dihapus dan pilihan lain ditandai jelas, jawabannya pilihan yang tidak dibatalkan.\n"
        . "7. \"pilihan\" = huruf akhir yang dipilih siswa menurutmu (A–E, atau B/S untuk benar/salah). Kosong \"\" bila tidak dijawab. Bila benar-benar ada dua pilihan aktif yang sama kuat, tulis keduanya \"A,C\".\n"
        . "8. Untuk huruf tulisan tangan yang mirip (mis. a/d, b/d/6, c/e, B/8/S/5), lihat bentuk huruf siswa di nomor lain. Bila tetap ragu, yakin=false.\n"
        . "9. yakin=false bila ada keraguan sekecil apa pun: tanda samar, dua tanda hampir sama kuat, foto buram/gelap/terpotong, atau nomor tidak terlihat.\n"
        . "10. Catatan (singkat) hanya bila ada hal yang perlu diketahui guru, mis. \"A dihapus, B dilingkari\".\n\n"
        . "ISIAN/URAIAN: salin persis yang tertulis di \"teks\" (termasuk salah ketik). Kosong bila tidak dijawab.\n"
        . ($baca_nama ? "Baca juga nama, kelas, dan nomor absen siswa bila tertulis di lembar.\n" : '')
        . "\nBalas HANYA JSON tanpa teks lain:\n"
        . '{"nama":"","kelas":"","absen":"","jawaban":[{"no":"1","tanda":[{"opsi":"A","jenis":"bekas_hapus"},{"opsi":"B","jenis":"lingkar"}],"pilihan":"B","teks":"","yakin":true,"catatan":"A dihapus"}]}'
        . "\nUntuk isian/uraian, \"tanda\" = [] dan \"pilihan\" = \"\". Sertakan SEMUA nomor di daftar, urut.";
}

function koreksi_baca(array $soal, array $foto, string $format, bool $baca_nama, array $kecuali = []): array
{
    $isi = array_merge([['type' => 'text', 'text' => koreksi_prompt_baca($soal, $format, $baca_nama)]], ai_bagian_foto($foto, 4));
    $r = ai_minta([['role' => 'user', 'content' => $isi]], ['gambar' => true, 'maks_token' => 9000, 'waktu' => 120, 'suhu' => 0, 'kecuali' => $kecuali]);
    if (!$r['ok']) return $r;
    $j = ai_ambil_json((string)$r['pesan']['content']);
    if (!is_array($j) || !isset($j['jawaban']) || !is_array($j['jawaban'])) {
        return ['ok' => false, 'galat' => 'AI membalas dengan format yang tidak dikenali.', 'rincian' => [$r['oleh']], 'kunci' => $r['kunci']];
    }
    $peta = [];
    foreach ($j['jawaban'] as $x) {
        if (!is_array($x)) continue;
        $no = koreksi_norm_no((string)($x['no'] ?? ''));
        if ($no !== '') $peta[$no] = $x;
    }
    return ['ok' => true, 'peta' => $peta, 'nama' => trim((string)($j['nama'] ?? '')), 'kelas' => trim((string)($j['kelas'] ?? '')),
        'absen' => trim((string)($j['absen'] ?? '')), 'oleh' => $r['oleh'], 'kunci' => $r['kunci'], 'rincian' => $r['rincian']];
}

function koreksi_norm_no(string $n): string
{
    $n = strtolower(trim($n));
    $n = preg_replace('/^(no|nomor)\.?\s*/', '', $n);
    return rtrim($n, '.)');
}

/** Ubah teks jawaban menjadi huruf (A–E atau B/S). */
function koreksi_huruf(string $s, string $jenis): string
{
    $s = strtoupper(trim($s));
    if ($jenis === 'bs') {
        $s = preg_replace('/\bBENAR\b/', 'B', $s);
        $s = preg_replace('/\bSALAH\b/', 'S', $s);
        preg_match_all('/[BS]/', $s, $m);
    } else {
        preg_match_all('/[A-E]/', $s, $m);
    }
    $h = array_values(array_unique($m[0]));
    sort($h);
    return implode(',', $h);
}

/**
 * Tentukan jawaban satu nomor dari satu bacaan AI.
 * Dihitung ulang dari daftar tanda (tanda pilih dikurangi yang dibatalkan/dihapus),
 * lalu dibandingkan dengan "pilihan" yang ditulis AI; beda = ragu.
 */
function koreksi_putuskan_satu(?array $x, string $jenis): array
{
    if (!$x) return ['huruf' => '', 'ragu' => true, 'catatan' => 'Nomor ini tidak terbaca.'];
    $pilih = [];
    $batal = [];
    foreach ((array)($x['tanda'] ?? []) as $t) {
        if (!is_array($t)) continue;
        $o = koreksi_huruf((string)($t['opsi'] ?? ''), $jenis);
        if ($o === '' || str_contains($o, ',')) continue;
        $jt = strtolower(trim((string)($t['jenis'] ?? '')));
        if (in_array($jt, ['batal', 'bekas_hapus', 'hapus', 'coret'], true)) $batal[$o] = true;
        elseif (in_array($jt, TANDA_PILIH, true)) $pilih[$o] = true;
    }
    $dari_tanda = array_keys(array_diff_key($pilih, $batal));
    sort($dari_tanda);
    $dari_tanda = implode(',', $dari_tanda);
    $kata_ai = koreksi_huruf((string)($x['pilihan'] ?? ''), $jenis);
    $yakin = ($x['yakin'] ?? true) !== false;
    $catatan = trim((string)($x['catatan'] ?? ''));

    $huruf = $kata_ai !== '' ? $kata_ai : $dari_tanda;
    $ragu = !$yakin;
    if ($dari_tanda !== '' && $kata_ai !== '' && $dari_tanda !== $kata_ai) {
        $ragu = true;
        $catatan = trim($catatan . ' Tanda yang terlihat: ' . $dari_tanda . '.');
    }
    if (str_contains($huruf, ',')) {
        $ragu = true;
        $catatan = trim($catatan . ' Lebih dari satu pilihan ditandai.');
    }
    if ($batal && !$ragu && $huruf !== '') {
        // ada bekas hapus/pembatalan: tetap dipakai, tapi beri keterangan agar guru tahu
        $catatan = $catatan !== '' ? $catatan : 'Ada bekas hapus/coretan pada ' . implode(',', array_keys($batal)) . '.';
    }
    return ['huruf' => $huruf, 'ragu' => $ragu, 'catatan' => $catatan];
}

/* ---------- Langkah 2: nilai uraian/isian (tanpa foto) ---------- */

function koreksi_nilai_uraian(array $soal, array $jawaban, array $contoh, string $judul): array
{
    $baris_soal = [];
    $baris_jwb = [];
    foreach ($soal as $s) {
        $baris_soal[] = '- No ' . $s['no'] . ' [' . (JENIS_KOREKSI[$s['jenis']] ?? $s['jenis']) . ', maks ' . $s['maks'] . ' poin]: ' . ($s['teks'] !== '' ? $s['teks'] : '(tanpa kunci)');
        $j = trim((string)($jawaban[$s['no']] ?? ''));
        $baris_jwb[] = '- No ' . $s['no'] . ': ' . ($j !== '' ? json_encode($j, JSON_UNESCAPED_UNICODE) : '(kosong)');
    }
    $teks_contoh = '';
    if ($contoh) {
        $teks_contoh = "\nCONTOH KOREKSI GURU SEBELUMNYA (ikuti standar guru ini):\n";
        foreach ($contoh as $c) {
            $teks_contoh .= '- No ' . $c['no'] . ': jawaban ' . json_encode($c['jawaban'], JSON_UNESCAPED_UNICODE) . ' → guru memberi ' . $c['skor'] . '/' . $c['maks'] . ' (AI semula ' . $c['skor_ai'] . ")\n";
        }
    }
    $p = "Kamu guru yang teliti dan adil. Nilai jawaban siswa berikut.\nPENILAIAN: $judul\n\nSOAL, KUNCI/PEDOMAN, SKOR MAKSIMAL:\n" . implode("\n", $baris_soal)
        . "\n\nJAWABAN SISWA (salinan dari foto):\n" . implode("\n", $baris_jwb) . "\n$teks_contoh\n"
        . "ATURAN:\n- isian: terima makna sama, sinonim, atau salah ejaan kecil. Skor penuh atau 0, kecuali kunci menyebut skor sebagian.\n"
        . "- uraian: skor sebagian sesuai pedoman/poin kunci; sebutkan poin yang kurang di catatan.\n"
        . "- pendapat: tidak ada benar/salah; nilai kejelasan sikap, kekuatan alasan, dan kaitannya dengan pertanyaan.\n"
        . "- Kosong/tidak terbaca: 0. Skor tidak melebihi maks, boleh kelipatan 0,5.\n"
        . "- ragu=true bila jawaban ambigu atau kamu tidak yakin skornya.\n"
        . "- catatan: 1 kalimat singkat untuk guru. komentar: 1–2 kalimat umpan balik ramah untuk siswa.\n\n"
        . 'Balas HANYA JSON: {"soal":[{"no":"3","skor":4,"catatan":"...","ragu":false}],"komentar":"..."}';
    $r = ai_minta([['role' => 'user', 'content' => $p]], ['maks_token' => 4000, 'waktu' => 90, 'suhu' => 0.1]);
    if (!$r['ok']) return $r;
    $j = ai_ambil_json((string)$r['pesan']['content']);
    if (!is_array($j)) return ['ok' => false, 'galat' => 'Format penilaian uraian tidak dikenali.', 'rincian' => [$r['oleh']]];
    $peta = [];
    foreach ((array)($j['soal'] ?? []) as $x) {
        if (is_array($x)) $peta[koreksi_norm_no((string)($x['no'] ?? ''))] = $x;
    }
    return ['ok' => true, 'peta' => $peta, 'komentar' => trim((string)($j['komentar'] ?? '')), 'oleh' => $r['oleh']];
}

/** Contoh koreksi guru (skor uraian yang diubah guru) dari penilaian yang sama. */
function koreksi_contoh_guru(int $penilaian_id): array
{
    $c = [];
    foreach (semua('SELECT detail FROM guru_koreksi WHERE penilaian_id = ? ORDER BY dibuat DESC LIMIT 40', [$penilaian_id]) as $r) {
        $d = json_decode((string)$r['detail'], true) ?: [];
        foreach ((array)($d['soal'] ?? []) as $s) {
            if (!in_array($s['jenis'] ?? '', ['isian', 'esai', 'pendapat'], true)) continue;
            if (!isset($s['skor_ai']) || abs((float)$s['skor'] - (float)$s['skor_ai']) < 0.5) continue;
            $c[] = ['no' => $s['no'], 'jawaban' => mb_substr((string)$s['jawaban'], 0, 300), 'skor' => $s['skor'], 'skor_ai' => $s['skor_ai'], 'maks' => $s['maks']];
            if (count($c) >= 12) return $c;
        }
    }
    return $c;
}

/** Cocokkan nama yang terbaca dengan daftar siswa kelas. */
function koreksi_cocok_nama(string $nama, string $absen, array $siswa): ?string
{
    if ($absen !== '' && ctype_digit($absen) && isset($siswa[(int)$absen - 1]) && $nama === '') return $siswa[(int)$absen - 1]['member_id'];
    $n = koreksi_rapikan_nama($nama);
    if ($n === '') return null;
    $terbaik = null;
    $skor_terbaik = 0.0;
    foreach ($siswa as $s) {
        $m = koreksi_rapikan_nama((string)$s['member_name']);
        similar_text($n, $m, $persen);
        // nama panggilan / hanya nama depan
        if (str_starts_with($m, $n) || str_starts_with($n, $m)) $persen = max($persen, 85.0);
        if ($persen > $skor_terbaik) { $skor_terbaik = $persen; $terbaik = $s['member_id']; }
    }
    return $skor_terbaik >= 70 ? $terbaik : null;
}

function koreksi_rapikan_nama(string $s): string
{
    $s = mb_strtolower($s);
    $s = preg_replace('/[^a-z\s]/u', '', $s);
    return trim(preg_replace('/\s+/', ' ', $s));
}

/**
 * Proses lengkap satu lembar jawaban.
 * $dua_kali: baca ulang dengan AI kedua (model berbeda) lalu bandingkan.
 */
function koreksi_proses(array $penilaian, array $soal, array $foto, string $format, bool $dua_kali, bool $baca_nama): array
{
    $b1 = koreksi_baca($soal, $foto, $format, $baca_nama);
    if (!$b1['ok']) return $b1;
    $b2 = null;
    $info2 = '';
    if ($dua_kali) {
        $b2 = koreksi_baca($soal, $foto, $format, false, [$b1['kunci']]);
        if (!$b2['ok']) { $info2 = 'Pembacaan kedua gagal (' . ($b2['galat'] ?? '') . '); hanya dibaca satu kali.'; $b2 = null; }
    }
    $hasil = [];
    $jawaban_uraian = [];
    $uraian = [];
    foreach ($soal as $s) {
        $x1 = $b1['peta'][$s['no']] ?? null;
        if (in_array($s['jenis'], ['pg', 'bs'], true)) {
            $a = koreksi_putuskan_satu($x1, $s['jenis']);
            $bacaan2 = null;
            if ($b2) {
                $c = koreksi_putuskan_satu($b2['peta'][$s['no']] ?? null, $s['jenis']);
                $bacaan2 = $c['huruf'];
                if ($c['huruf'] !== $a['huruf']) {
                    $a['ragu'] = true;
                    $a['catatan'] = trim('AI ke-1 membaca "' . ($a['huruf'] ?: 'kosong') . '", AI ke-2 membaca "' . ($c['huruf'] ?: 'kosong') . '". ' . $a['catatan']);
                } elseif ($c['ragu'] && !$a['ragu']) {
                    $a['ragu'] = true;
                    if ($c['catatan'] !== '') $a['catatan'] = trim($a['catatan'] . ' ' . $c['catatan']);
                }
            }
            $hasil[] = ['no' => $s['no'], 'jenis' => $s['jenis'], 'maks' => $s['maks'], 'kunci' => $s['kunci'], 'jawaban' => $a['huruf'],
                'bacaan2' => $bacaan2, 'ragu' => $a['ragu'], 'catatan' => $a['catatan']];
        } else {
            $teks = trim((string)($x1['teks'] ?? ($x1['pilihan'] ?? '')));
            $jawaban_uraian[$s['no']] = $teks;
            $uraian[] = $s;
            $hasil[] = ['no' => $s['no'], 'jenis' => $s['jenis'], 'maks' => $s['maks'], 'kunci' => mb_substr($s['teks'], 0, 400), 'jawaban' => $teks,
                'ragu' => $x1 ? (($x1['yakin'] ?? true) === false) : true, 'catatan' => trim((string)($x1['catatan'] ?? ''))];
        }
    }
    $komentar = '';
    $oleh = [$b1['oleh']];
    if ($b2) $oleh[] = $b2['oleh'];
    if ($uraian) {
        $u = koreksi_nilai_uraian($uraian, $jawaban_uraian, koreksi_contoh_guru((int)$penilaian['id']), (string)$penilaian['judul']);
        if ($u['ok']) {
            $oleh[] = $u['oleh'];
            $komentar = $u['komentar'];
        } else {
            $info2 = trim($info2 . ' Penilaian uraian gagal, isi skornya manual.');
        }
        foreach ($hasil as &$h) {
            if (in_array($h['jenis'], ['pg', 'bs'], true)) continue;
            $x = $u['ok'] ? ($u['peta'][$h['no']] ?? null) : null;
            $skor = $x ? max(0.0, min((float)$h['maks'], round((float)($x['skor'] ?? 0) * 2) / 2)) : 0.0;
            $h['skor'] = $skor;
            $h['skor_ai'] = $skor;
            if ($x) {
                $h['catatan'] = trim($h['catatan'] . ' ' . (string)($x['catatan'] ?? ''));
                if (!empty($x['ragu'])) $h['ragu'] = true;
            } else {
                $h['ragu'] = true;
                if ($h['jawaban'] === '') $h['catatan'] = 'Tidak dijawab.';
            }
        }
        unset($h);
    }
    return ['ok' => true, 'soal' => $hasil, 'komentar' => $komentar, 'oleh' => implode(' + ', array_unique($oleh)), 'info' => $info2,
        'nama' => $b1['nama'], 'kelas' => $b1['kelas'], 'absen' => $b1['absen'], 'dua_kali' => (bool)$b2];
}

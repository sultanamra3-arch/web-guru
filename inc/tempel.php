<?php
/*
 * Tempel soal: ubah teks tempelan / file Word menjadi soal.
 * Langkah 1 (AI): pisahkan soal, pilihan, kunci, pedoman, rubrik, poin — SALIN apa adanya, yang tidak ada = null.
 * Langkah 2 (AI): hanya untuk bagian yang kosong → saran AI (ditandai agar guru memeriksa).
 */
declare(strict_types=1);

const TEMPEL_MAKS_HURUF = 60000;

/* ---------------- Word (.docx) → teks (+ gambar) ---------------- */

/** Baca .docx: teks per paragraf (nomor otomatis Word ikut ditulis), tabel jadi "a | b | c", gambar jadi [GAMBAR n]. */
function docx_ke_teks(string $berkas, array &$gambar): ?string
{
    if (!class_exists('ZipArchive')) return null;
    $z = new ZipArchive();
    if ($z->open($berkas) !== true) return null;
    $xml = $z->getFromName('word/document.xml');
    if ($xml === false) { $z->close(); return null; }
    // relasi gambar
    $rel = [];
    $rx = $z->getFromName('word/_rels/document.xml.rels');
    if ($rx !== false && ($r = @simplexml_load_string($rx)) !== false) {
        foreach ($r->Relationship as $x) $rel[(string)$x['Id']] = 'word/' . ltrim(str_replace('../', '', (string)$x['Target']), '/');
    }
    // format penomoran otomatis
    $fmt = [];
    $nx = $z->getFromName('word/numbering.xml');
    if ($nx !== false && ($nd = @simplexml_load_string($nx)) !== false) {
        $nd->registerXPathNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $abs = [];
        foreach ($nd->xpath('//w:abstractNum') as $a) {
            $aid = (string)$a->attributes('w', true)->abstractNumId;
            foreach ($a->children('w', true)->lvl as $l) {
                $abs[$aid][(int)$l->attributes('w', true)->ilvl] = (string)$l->children('w', true)->numFmt->attributes('w', true)->val;
            }
        }
        foreach ($nd->xpath('//w:num') as $n) {
            $nid = (string)$n->attributes('w', true)->numId;
            $aid = (string)$n->children('w', true)->abstractNumId->attributes('w', true)->val;
            $fmt[$nid] = $abs[$aid] ?? [];
        }
    }
    // penomoran yang menempel pada gaya paragraf (mis. "List Number")
    $gaya = [];
    $sx = $z->getFromName('word/styles.xml');
    if ($sx !== false && ($sd = @simplexml_load_string($sx)) !== false) {
        foreach ($sd->children('w', true)->style as $st) {
            $c = $st->children('w', true);
            if (!isset($c->pPr)) continue;
            $pc = $c->pPr->children('w', true);
            if (!isset($pc->numPr)) continue;
            $nc = $pc->numPr->children('w', true);
            if (!isset($nc->numId)) continue;
            $gaya[(string)$st->attributes('w', true)->styleId] = [(string)$nc->numId->attributes('w', true)->val,
                isset($nc->ilvl) ? (int)$nc->ilvl->attributes('w', true)->val : 0];
        }
    }
    $dom = new DOMDocument();
    if (!@$dom->loadXML($xml)) { $z->close(); return null; }
    $xp = new DOMXPath($dom);
    $W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
    $xp->registerNamespace('w', $W);
    $xp->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
    $xp->registerNamespace('v', 'urn:schemas-microsoft-com:vml');
    $xp->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
    $hitung = [];
    $huruf = fn(int $n, bool $besar) => ($besar ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' : 'abcdefghijklmnopqrstuvwxyz')[($n - 1) % 26];
    $romawi = function (int $n): string {
        $o = ''; foreach ([10 => 'x', 9 => 'ix', 5 => 'v', 4 => 'iv', 1 => 'i'] as $v => $s) while ($n >= $v) { $o .= $s; $n -= $v; } return $o;
    };
    $paragraf = function (DOMElement $p) use ($xp, $z, $rel, &$gambar, &$hitung, $fmt, $huruf, $romawi, $gaya): string {
        $t = '';
        foreach ($xp->query('.//w:t|.//w:tab|.//w:br|.//a:blip|.//v:imagedata', $p) as $n) {
            if ($n->localName === 't') $t .= $n->textContent;
            elseif ($n->localName === 'tab') $t .= ' ';
            elseif ($n->localName === 'br') $t .= "\n";
            else {
                $rid = $n->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', $n->localName === 'blip' ? 'embed' : 'id');
                $isi = isset($rel[$rid]) ? $z->getFromName($rel[$rid]) : false;
                $info = $isi !== false ? @getimagesizefromstring($isi) : false;
                if ($info && in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true) && $info[0] >= 40 && $info[1] >= 40) {
                    $nama = gambar_simpan('data:' . $info['mime'] . ';base64,' . base64_encode($isi));
                    if ($nama) { $gambar[] = $nama; $t .= ' [GAMBAR ' . count($gambar) . '] '; }
                }
            }
        }
        // nomor otomatis (1. / a. / A. / i.)
        $W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $num = $xp->query('./w:pPr/w:numPr', $p)->item(0);
        $ps = $xp->query('./w:pPr/w:pStyle', $p)->item(0);
        $ps = $ps ? $ps->getAttributeNS($W, 'val') : '';
        if ($num || isset($gaya[$ps])) {
            $nid = $num ? $xp->query('./w:numId', $num)->item(0) : null;
            $il = $num ? $xp->query('./w:ilvl', $num)->item(0) : null;
            $nid = $nid ? $nid->getAttributeNS($W, 'val') : ($gaya[$ps][0] ?? '');
            $il = $il ? (int)$il->getAttributeNS($W, 'val') : ($gaya[$ps][1] ?? 0);
            if ($nid !== '' && $nid !== '0') {
                $hitung[$nid][$il] = ($hitung[$nid][$il] ?? 0) + 1;
                foreach (array_keys($hitung[$nid]) as $k) if ($k > $il) unset($hitung[$nid][$k]);
                $c = $hitung[$nid][$il];
                $f = $fmt[$nid][$il] ?? 'decimal';
                $label = match ($f) {
                    'upperLetter' => $huruf($c, true) . '.', 'lowerLetter' => $huruf($c, false) . '.',
                    'upperRoman' => strtoupper($romawi($c)) . '.', 'lowerRoman' => $romawi($c) . '.',
                    'bullet', 'none' => '-', default => $c . '.',
                };
                $t = $label . ' ' . ltrim($t);
            }
        }
        return rtrim($t);
    };
    $baris = [];
    $body = $xp->query('/w:document/w:body')->item(0);
    foreach ($body ? $body->childNodes : [] as $el) {
        if (!$el instanceof DOMElement) continue;
        if ($el->localName === 'p') $baris[] = $paragraf($el);
        elseif ($el->localName === 'tbl') {
            foreach ($xp->query('./w:tr', $el) as $tr) {
                $sel = [];
                foreach ($xp->query('./w:tc', $tr) as $tc) {
                    $isi = [];
                    foreach ($xp->query('.//w:p', $tc) as $p) { $s = trim($paragraf($p)); if ($s !== '') $isi[] = $s; }
                    $sel[] = implode(' / ', $isi);
                }
                if (array_filter($sel, fn($s) => $s !== '')) $baris[] = implode(' | ', $sel);
            }
            $baris[] = '';
        }
    }
    $z->close();
    $teks = preg_replace("/\n{3,}/", "\n\n", implode("\n", $baris));
    return trim((string)$teks);
}

/* ---------------- AI ---------------- */

/** Langkah 1: pisahkan soal & bagian-bagiannya tanpa mengubah kata. */
function tempel_ekstrak(string $teks, string $mapel, string $kelas): array
{
    $perintah = "Kamu membantu guru memindahkan soal ke aplikasi. Di bawah ini teks yang ditempel guru (bisa berisi soal, kunci jawaban, pedoman jawaban, rubrik penilaian, aturan skor, kisi-kisi, kop surat — dalam urutan apa pun, kunci bisa terpisah di bagian bawah atau dalam tabel).\n"
        . "Mata pelajaran: $mapel. Kelas: $kelas.\n\n"
        . "TUGAS: pisahkan menjadi daftar soal. ATURAN PENTING:\n"
        . "1. SALIN teks pertanyaan dan pilihan jawaban PERSIS seperti aslinya (ejaan, tanda baca, kalimat). Jangan meringkas, memperbaiki, atau menambah. Buang hanya nomor soal dan huruf pilihan di depannya.\n"
        . "2. Tentukan tipe: \"pg\" (pilihan ganda A–E), \"bs\" (benar/salah), \"isian\" (isian singkat/titik-titik), \"esai\" (uraian).\n"
        . "3. kunci: untuk pg huruf saja (A–E); untuk bs \"B\" (benar) atau \"S\" (salah); untuk isian jawaban singkat. Ambil HANYA bila tertulis di teks (mis. tabel kunci, \"Kunci: B\", jawaban bertanda). Kalau tidak tertulis isi null. JANGAN menebak.\n"
        . "4. pedoman: untuk esai/isian, pedoman/jawaban yang diharapkan bila tertulis di teks (salin apa adanya, boleh dirapikan jadi satu paragraf). Kalau tidak ada, null.\n"
        . "5. pembahasan: bila ada pembahasan/penjelasan tertulis, salin; kalau tidak, null.\n"
        . "6. gambar: bila teks soal/pilihan memuat penanda [GAMBAR n], tulis nomornya di \"gambar\" (mis. [1]) dan hapus penandanya dari teks. Pilihan bergambar: \"gambar_opsi\": {\"A\": 2}.\n"
        . "7. Dari teks juga ambil (bila tertulis, kalau tidak null): poin_pg (skor per soal benar pilihan ganda/bs/isian), poin_uraian (skor maksimal per soal uraian), rubrik (rubrik/kriteria penskoran uraian, salin apa adanya, satu baris per tingkat, mis. \"7 = ...\"), petunjuk (HANYA bila ada bagian berjudul Petunjuk atau Petunjuk umum; jangan mengambil identitas seperti waktu, kelas, mapel), judul (judul/jenis asesmen bila ada, mis. \"Asesmen Sumatif Tengah Semester Ganjil\").\n"
        . "8. Kunci yang terpisah (mis. tabel \"No | Kunci\") HARUS dicocokkan ke soal dengan nomor yang sama, dan pedoman esai nomor N ke soal esai nomor N (penomoran esai sering mulai lagi dari 1).\n"
        . "9. Kalau teks hanya berisi kunci/kisi-kisi tanpa kalimat pertanyaan, jangan mengarang pertanyaan: isi \"pertanyaan\": \"\" dan tetap isi kunci/pedomannya.\n\n"
        . "Balas HANYA JSON tanpa teks lain:\n"
        . '{"soal":[{"no":1,"tipe":"pg","pertanyaan":"...","opsi":{"A":"...","B":"..."},"kunci":"B","pedoman":null,"pembahasan":null,"gambar":null,"gambar_opsi":null}],"poin_pg":null,"poin_uraian":null,"rubrik":null,"petunjuk":null,"judul":null}'
        . "\n\nTEKS:\n<<<\n" . $teks . "\n>>>";
    $r = ai_minta([['role' => 'user', 'content' => $perintah]], ['maks_token' => (int)min(30000, 4000 + mb_strlen($teks) / 2), 'waktu' => 240, 'suhu' => 0]);
    if (!$r['ok']) return $r;
    $j = ai_ambil_json((string)$r['pesan']['content']);
    if (!is_array($j) || !isset($j['soal']) || !is_array($j['soal'])) return ['ok' => false, 'galat' => 'AI belum bisa membaca soalnya. Coba lagi, atau tempel sebagian dulu.', 'rincian' => [$r['oleh']]];
    return ['ok' => true, 'data' => $j, 'oleh' => $r['oleh']];
}

/** Rapikan hasil AI menjadi struktur tetap + tanda sumber ('teks' / 'ai' / ''). */
function tempel_rapikan(array $j, array $gambar, string $teks = ''): array
{
    $soal = [];
    foreach ($j['soal'] as $i => $s) {
        if (!is_array($s)) continue;
        $tipe = in_array($s['tipe'] ?? '', ['pg', 'bs', 'isian', 'esai'], true) ? $s['tipe'] : 'esai';
        $opsi = [];
        if (in_array($tipe, ['pg', 'bs'], true) && is_array($s['opsi'] ?? null)) {
            foreach ($s['opsi'] as $k => $v) {
                $k = strtoupper(trim((string)$k));
                if (preg_match('/^[A-E]$/', $k)) $opsi[$k] = trim((string)$v);
            }
            ksort($opsi);
        }
        if ($tipe === 'bs' && !$opsi) $opsi = ['A' => 'Benar', 'B' => 'Salah'];
        $kunci = trim((string)($s['kunci'] ?? ''));
        if ($tipe === 'pg') $kunci = preg_match('/[A-E]/i', $kunci, $m) ? strtoupper($m[0]) : '';
        if ($tipe === 'bs') $kunci = $kunci === '' ? '' : (preg_match('/^(s|salah|b\b)/i', $kunci) && !preg_match('/^benar/i', $kunci) ? (strtoupper($kunci[0]) === 'B' && strlen($kunci) === 1 ? 'B' : 'S') : 'B');
        $pedoman = trim((string)($s['pedoman'] ?? ''));
        if ($tipe === 'isian' && $kunci === '' && $pedoman !== '') { $kunci = $pedoman; $pedoman = ''; }
        $ambil_g = function ($v) use ($gambar): ?string {
            $n = is_array($v) ? (int)($v[0] ?? 0) : (int)$v;
            return $n >= 1 && isset($gambar[$n - 1]) ? $gambar[$n - 1] : null;
        };
        $go = [];
        foreach ((array)($s['gambar_opsi'] ?? []) as $k => $v) if (($f = $ambil_g($v)) && isset($opsi[strtoupper((string)$k)])) $go[strtoupper((string)$k)] = $f;
        $tanya = trim(preg_replace('/\s*\[GAMBAR \d+\]\s*/', ' ', (string)($s['pertanyaan'] ?? '')));
        $soal[] = [
            'tipe' => $tipe, 'pertanyaan' => $tanya, 'opsi' => $opsi,
            'kunci' => $tipe === 'esai' ? $pedoman : $kunci,
            'sumber_kunci' => ($tipe === 'esai' ? $pedoman : $kunci) !== '' ? 'teks' : '',
            'pembahasan' => trim((string)($s['pembahasan'] ?? '')),
            'gambar' => $ambil_g($s['gambar'] ?? null), 'gambar_opsi' => $go,
            'ada_gambar' => preg_match('/\[GAMBAR \d+\]|gambar (di|ber)ikut|perhatikan gambar/i', (string)($s['pertanyaan'] ?? '')) && !$ambil_g($s['gambar'] ?? null),
        ];
    }
    // gambar yang tidak disebut AI: pasangkan ke soal yang teksnya tepat sebelum penanda [GAMBAR n]
    $dipakai = array_filter(array_merge(array_column($soal, 'gambar'), ...array_map(fn($s) => array_values($s['gambar_opsi']), $soal ?: [['gambar_opsi' => []]])));
    if ($teks !== '' && preg_match_all('/\[GAMBAR (\d+)\]/', $teks, $mm, PREG_OFFSET_CAPTURE)) {
        $posisi = [];
        foreach ($soal as $i => $s) {
            $kunci_teks = mb_substr(preg_replace('/\s+/', ' ', $s['pertanyaan']), 0, 40);
            if (mb_strlen($kunci_teks) < 12) continue;
            $p = strpos(preg_replace('/\s+/', ' ', $teks), $kunci_teks);
            if ($p !== false) $posisi[$i] = $p;
        }
        $rata = preg_replace('/\s+/', ' ', $teks);
        preg_match_all('/\[GAMBAR (\d+)\]/', $rata, $mm, PREG_OFFSET_CAPTURE);
        foreach ($mm[1] as [$n, $off]) {
            $f = $gambar[(int)$n - 1] ?? null;
            if (!$f || in_array($f, $dipakai, true)) continue;
            $calon = null;
            foreach ($posisi as $i => $p) if ($p < $off && ($calon === null || $p > $posisi[$calon])) $calon = $i;
            if ($calon === null || $soal[$calon]['gambar'] || $off - $posisi[$calon] > 1500) continue;
            // jangan menyeberang ke file lain (mis. logo kop di file kunci)
            if (str_contains(substr($rata, $posisi[$calon], $off - $posisi[$calon]), '=== Isi file')) continue;
            $soal[$calon]['gambar'] = $f;
            $soal[$calon]['ada_gambar'] = false;
            $dipakai[] = $f;
        }
    }
    $angka = fn($v) => is_numeric(str_replace(',', '.', (string)$v)) && (float)str_replace(',', '.', (string)$v) > 0 ? (float)str_replace(',', '.', (string)$v) : null;
    return [
        'soal' => $soal,
        'poin_pg' => $angka($j['poin_pg'] ?? null), 'poin_uraian' => $angka($j['poin_uraian'] ?? null),
        'rubrik' => trim((string)($j['rubrik'] ?? '')), 'petunjuk' => trim((string)($j['petunjuk'] ?? '')), 'judul' => trim((string)($j['judul'] ?? '')),
        'sumber_poin' => '', 'sumber_rubrik' => '',
    ];
}

/** Saran poin bila tidak tertulis: total 100 bila bisa, uraian ±2,5× PG. */
function tempel_saran_poin(int $n_obj, int $n_ur, ?float $pg, ?float $ur): array
{
    if ($pg && $ur) return [$pg, $ur];
    if ($pg && $n_ur) { $sisa = 100 - $n_obj * $pg; $u = $sisa > 0 ? $sisa / $n_ur : 5; return [$pg, $u == floor($u * 2) / 2 && $u > 0 ? $u : 5.0]; }
    if ($ur && $n_obj) { $sisa = 100 - $n_ur * $ur; $p = $sisa > 0 ? $sisa / $n_obj : 1; return [$p == floor($p * 2) / 2 && $p > 0 ? $p : 1.0, $ur]; }
    if (!$n_ur) return [$n_obj && 100 % $n_obj === 0 ? 100 / $n_obj : 1.0, 5.0];
    if (!$n_obj) return [1.0, 100 % $n_ur === 0 ? 100 / $n_ur : 5.0];
    $terbaik = null;
    foreach ([1, 1.5, 2, 2.5, 3, 4, 5] as $p) {
        $u = (100 - $n_obj * $p) / $n_ur;
        if ($u <= $p || $u != floor($u * 2) / 2) continue;
        $selisih = abs($u / $p - 2.5);
        if ($terbaik === null || $selisih < $terbaik[2]) $terbaik = [(float)$p, (float)$u, $selisih];
    }
    return $terbaik ? [$terbaik[0], $terbaik[1]] : [1.0, 5.0];
}

/** Langkah 2: AI melengkapi kunci/pedoman/rubrik yang kosong (ditandai 'ai'). */
function tempel_lengkapi(array $h, string $mapel, string $kelas): array
{
    $kurang = [];
    foreach ($h['soal'] as $i => $s) if ($s['kunci'] === '' && $s['pertanyaan'] !== '') $kurang[$i] = $s;
    $n_ur = count(array_filter($h['soal'], fn($s) => $s['tipe'] === 'esai'));
    $n_obj = count($h['soal']) - $n_ur;
    if ($h['poin_pg'] && $h['poin_uraian']) $h['sumber_poin'] = 'teks';
    elseif ($h['poin_pg'] || $h['poin_uraian']) $h['sumber_poin'] = 'sebagian';
    else $h['sumber_poin'] = 'ai';
    [$h['poin_pg'], $h['poin_uraian']] = tempel_saran_poin($n_obj, $n_ur, $h['poin_pg'], $h['poin_uraian']);
    $perlu_rubrik = $n_ur > 0 && $h['rubrik'] === '';
    $h['sumber_rubrik'] = $h['rubrik'] !== '' ? 'teks' : '';
    if (!$kurang && !$perlu_rubrik) return ['ok' => true, 'data' => $h];
    $daftar = [];
    foreach ($kurang as $i => $s) {
        $o = $s['opsi'] ? ' | Pilihan: ' . implode('; ', array_map(fn($k, $v) => "$k. $v", array_keys($s['opsi']), $s['opsi'])) : '';
        $daftar[] = 'Nomor ' . ($i + 1) . ' [' . TIPE_SOAL[$s['tipe']] . '] ' . mb_substr(preg_replace('/\s+/', ' ', $s['pertanyaan']), 0, 700) . $o;
    }
    $maks = $h['poin_uraian'] + 0;
    $perintah = "Kamu guru $mapel kelas $kelas yang teliti. " . ($daftar ? "Lengkapi kunci jawaban yang belum ada untuk soal berikut:\n" . implode("\n", $daftar)
            . "\n\nAturan: pg → huruf jawaban paling tepat (A–E); bs → \"B\" (benar) atau \"S\" (salah); isian → jawaban singkat (boleh beberapa alternatif dipisah /); esai → pedoman jawaban berupa poin-poin kunci yang diharapkan (2–5 poin, ringkas, sesuai materi SMP).\n" : '')
        . ($perlu_rubrik ? "\nBuat juga rubrik penskoran umum untuk soal uraian dengan skala 0–$maks: 4–5 tingkat, satu baris per tingkat dengan format \"skor = kriteria\" (mis. \"$maks = jawaban lengkap, tepat, dan disertai alasan/contoh yang logis\"), dari skor tertinggi ke 0 = tidak menjawab.\n" : '')
        . "\n" . ATURAN_UMUM . "\nBalas HANYA JSON tanpa teks lain: {\"kunci\":{\"1\":\"B\",\"5\":\"pedoman...\"},\"rubrik\":" . ($perlu_rubrik ? '"..."' : 'null') . "}";
    $r = ai_minta([['role' => 'user', 'content' => $perintah]], ['maks_token' => min(16000, 2500 + 400 * count($daftar)), 'waktu' => 200, 'suhu' => 0.2]);
    if (!$r['ok']) return ['ok' => true, 'data' => $h, 'catatan' => 'Saran AI untuk kunci yang kosong gagal dibuat; isi sendiri di halaman periksa.'];
    $j = ai_ambil_json((string)$r['pesan']['content']) ?: [];
    foreach ((array)($j['kunci'] ?? []) as $no => $v) {
        $i = (int)$no - 1;
        if (!isset($kurang[$i])) continue;
        $v = trim(is_array($v) ? implode('; ', $v) : (string)$v);
        $t = $h['soal'][$i]['tipe'];
        if ($t === 'pg') $v = preg_match('/[A-E]/i', $v, $m) && isset($h['soal'][$i]['opsi'][strtoupper($m[0])]) ? strtoupper($m[0]) : '';
        if ($t === 'bs') $v = $v === '' ? '' : (preg_match('/^s/i', $v) ? 'S' : 'B');
        if ($v === '') continue;
        $h['soal'][$i]['kunci'] = mb_substr($v, 0, 3000);
        $h['soal'][$i]['sumber_kunci'] = 'ai';
    }
    if ($perlu_rubrik && trim((string)($j['rubrik'] ?? '')) !== '') {
        $rb = $j['rubrik'];
        $h['rubrik'] = mb_substr(trim(is_array($rb) ? implode("\n", array_map(fn($k, $v) => is_int($k) ? (string)$v : "$k = $v", array_keys($rb), $rb)) : (string)$rb), 0, 4000);
        $h['sumber_rubrik'] = 'ai';
    }
    return ['ok' => true, 'data' => $h];
}

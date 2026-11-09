<?php
/* Fitur AI SiGuru: soal, modul ajar, LKPD, presentasi, revisi dokumen. */
declare(strict_types=1);

require_once __DIR__ . '/Parsedown.php';

const JENIS_DOKUMEN = ['modul' => 'Modul ajar', 'lkpd' => 'LKPD', 'presentasi' => 'Presentasi', 'paket' => 'Paket soal'];
const TIPE_SOAL = ['pg' => 'Pilihan ganda', 'bs' => 'Benar/Salah', 'isian' => 'Isian singkat', 'esai' => 'Uraian/esai'];
const TINGKAT_SOAL = ['mudah' => 'Mudah', 'sedang' => 'Sedang', 'sulit' => 'Sulit (HOTS)'];
const LEVEL_KOGNITIF = ['C1', 'C2', 'C3', 'C4', 'C5', 'C6'];

function md(string $teks): string
{
    // Poin yang menjorok 4+ spasi jangan dianggap blok kode; jadikan sub-poin
    $teks = preg_replace('/^ {4,}([*+\-]|\d+\.)\s/m', '  $1 ', $teks);
    $teks = preg_replace('/^\t+([*+\-]|\d+\.)\s/m', '  $1 ', $teks);
    $p = new Parsedown();
    $p->setSafeMode(true);
    return $p->text($teks);
}

function fase_dari_kelas(string $kelas): string
{
    if (preg_match('/\b(VII|VIII|IX|7|8|9)\b/i', $kelas, $m)) {
        $n = ['VII' => 7, 'VIII' => 8, 'IX' => 9][strtoupper($m[1])] ?? (int)$m[1];
        return 'Fase D (SMP kelas ' . $n . ')';
    }
    return 'Fase D (SMP)';
}

function konteks_sekolah(): string
{
    return 'Sekolah: ' . k('sekolah', 'SMP Negeri 6 Keritang') . ', Kecamatan Keritang, Kabupaten Indragiri Hilir, Riau (daerah pesisir/perkebunan kelapa). '
        . 'Tahun ajaran ' . k('tahun_ajaran', '') . '. Gunakan contoh kontekstual dari lingkungan setempat bila cocok.';
}

const ATURAN_UMUM = "Tulis dalam Bahasa Indonesia baku yang jelas dan praktis untuk guru SMP. "
    . "Jangan mengarang kutipan Capaian Pembelajaran resmi, nomor regulasi, data, judul buku, atau tautan; bila perlu beri catatan agar guru mencocokkan dengan dokumen resmi.";

/* ---------- Soal ---------- */

function ai_buat_soal(array $in, array $foto = []): array
{
    $jumlah = max(1, min(30, (int)($in['jumlah'] ?? 10)));
    $tipe = $in['tipe'] ?? 'pg';
    $tipe_teks = $tipe === 'campur' ? 'campuran (sebagian besar pilihan ganda, beberapa isian dan uraian)' : (TIPE_SOAL[$tipe] ?? 'pilihan ganda');
    $opsi = (int)($in['jumlah_opsi'] ?? 4) === 5 ? 'A–E' : 'A–D';
    $perintah = "Buat $jumlah soal untuk asesmen.\n"
        . "Mata pelajaran: {$in['mapel']}\nKelas: {$in['kelas']} — " . fase_dari_kelas((string)$in['kelas']) . "\n"
        . "Materi/topik: {$in['topik']}\nBentuk soal: $tipe_teks\nTingkat kesulitan: " . ($in['tingkat'] ?? 'campuran (LOTS, MOTS, HOTS seimbang)') . "\n"
        . (trim((string)($in['catatan'] ?? '')) !== '' ? "Catatan guru: {$in['catatan']}\n" : '')
        . (trim((string)($in['bahan'] ?? '')) !== '' ? "Bahan/teks materi dari guru (soal harus berdasarkan ini):\n" . mb_substr((string)$in['bahan'], 0, 8000) . "\n" : '')
        . ($foto ? "Guru juga melampirkan foto materi/buku; gunakan isinya sebagai bahan.\n" : '')
        . "\nKetentuan: pilihan ganda memakai opsi $opsi dengan satu jawaban benar dan pengecoh yang masuk akal; benar/salah memakai opsi A=Benar, B=Salah; "
        . "isian dan uraian tanpa opsi, kunci berisi jawaban/pedoman penskoran singkat. Variasikan level kognitif (C1–C6). Kontekstual dan sesuai usia. "
        . ATURAN_UMUM . "\n" . konteks_sekolah() . "\n\n"
        . "Balas HANYA dengan JSON tanpa teks lain, format:\n"
        . '{"soal":[{"tipe":"pg|bs|isian|esai","tingkat":"mudah|sedang|sulit","level":"C1".."C6","pertanyaan":"...","opsi":{"A":"...","B":"..."},"kunci":"A","pembahasan":"..."}]}';
    $isi = $foto ? array_merge([['type' => 'text', 'text' => $perintah]], ai_bagian_foto($foto)) : $perintah;
    $r = ai_minta([['role' => 'user', 'content' => $isi]], ['gambar' => (bool)$foto, 'maks_token' => 8000, 'waktu' => 150, 'suhu' => 0.5]);
    if (!$r['ok']) return $r;
    $j = ai_ambil_json((string)$r['pesan']['content']);
    $daftar = $j['soal'] ?? (is_array($j) && array_is_list($j) ? $j : null);
    if (!$daftar) return ['ok' => false, 'galat' => 'AI membalas dengan format yang tidak dikenali. Coba lagi.', 'rincian' => [$r['oleh']]];
    $bersih = [];
    foreach ($daftar as $s) {
        if (!is_array($s) || trim((string)($s['pertanyaan'] ?? '')) === '') continue;
        $t = in_array($s['tipe'] ?? '', array_keys(TIPE_SOAL), true) ? $s['tipe'] : 'pg';
        $o = [];
        foreach ((array)($s['opsi'] ?? []) as $kk => $vv) {
            $kk = strtoupper(trim((string)$kk));
            if (preg_match('/^[A-E]$/', $kk) && trim((string)$vv) !== '') $o[$kk] = trim((string)$vv);
        }
        if ($t === 'bs' && !$o) $o = ['A' => 'Benar', 'B' => 'Salah'];
        $bersih[] = [
            'tipe' => $t,
            'tingkat' => in_array($s['tingkat'] ?? '', array_keys(TINGKAT_SOAL), true) ? $s['tingkat'] : 'sedang',
            'level' => in_array(strtoupper((string)($s['level'] ?? '')), LEVEL_KOGNITIF, true) ? strtoupper($s['level']) : '',
            'pertanyaan' => trim((string)$s['pertanyaan']),
            'opsi' => $o,
            'kunci' => trim((string)($s['kunci'] ?? '')),
            'pembahasan' => trim((string)($s['pembahasan'] ?? '')),
        ];
    }
    return ['ok' => true, 'soal' => $bersih, 'oleh' => $r['oleh']];
}

/* ---------- Modul ajar & LKPD ---------- */

const STRUKTUR_MODUL = <<<'TXT'
Susun PERENCANAAN PEMBELAJARAN MENDALAM (modul ajar Kurikulum Merdeka, pendekatan pembelajaran mendalam) dalam Markdown dengan struktur:
# Modul Ajar: <materi>
## A. Identitas — tabel: Penyusun, Satuan pendidikan, Mata pelajaran, Fase/Kelas/Semester, Materi pokok, Alokasi waktu, Tahun ajaran.
## B. Identifikasi — Peserta didik (pengetahuan awal, minat, kebutuhan belajar); Materi pelajaran (jenis pengetahuan, relevansi, tingkat kesulitan); Dimensi profil lulusan (pilih 2–3 paling relevan dari: keimanan dan ketakwaan terhadap Tuhan YME, kewargaan, penalaran kritis, kreativitas, kolaborasi, kemandirian, kesehatan, komunikasi).
## C. Desain Pembelajaran — Capaian Pembelajaran (jika guru tidak memberi teks CP, tulis ringkasan elemen CP berlabel "(ringkasan, cocokkan dengan dokumen CP resmi terbaru)"); Lintas disiplin ilmu; Tujuan Pembelajaran (terukur, kata kerja operasional); Topik kontekstual; Praktik pedagogis (model/metode + alasan); Kemitraan pembelajaran; Lingkungan pembelajaran; Pemanfaatan digital (sertakan alternatif tanpa internet).
## D. Pengalaman Belajar — per pertemuan dengan alokasi menit: Awal (orientasi, apersepsi, pertanyaan pemantik), Inti (Memahami, Mengaplikasi, Merefleksi), Penutup. Tandai prinsip **Berkesadaran**, **Bermakna**, **Menggembirakan** pada kegiatan. Sertakan diferensiasi konkret. Total menit harus sama dengan alokasi waktu.
## E. Asesmen Pembelajaran — asesmen awal (2–3 pertanyaan), proses (formatif), akhir (sumatif), dan tabel rubrik (kriteria × Perlu Bimbingan, Cukup, Baik, Sangat Baik) dengan deskripsi di setiap sel.
## F. Lampiran — ringkasan bahan bacaan guru/siswa, glosarium; daftar pustaka hanya bila yakin sumbernya nyata.
Kalimat kegiatan dari sudut pandang aktivitas ("Peserta didik ...", "Guru ..."). Gunakan tabel Markdown agar mudah disalin ke Word.
TXT;

const STRUKTUR_LKPD = <<<'TXT'
Susun LKPD (Lembar Kerja Peserta Didik) dalam Markdown untuk dicetak dan dikerjakan siswa:
# LKPD: <judul menarik>
Baris identitas isian: Nama, Kelas, Kelompok, Tanggal (dengan garis titik-titik).
## Tujuan Pembelajaran
## Petunjuk Kerja (bernomor)
## Info Singkat / Bahan Bacaan (singkat, kontekstual)
## Kegiatan (2–4 kegiatan bertahap: mengamati/menanya, mengumpulkan data, menganalisis, menyimpulkan; sediakan tabel atau ruang jawaban berupa garis titik-titik)
## Ayo Berpikir Kritis (1–2 soal HOTS)
## Refleksi (3 pertanyaan singkat + skala bintang)
Sertakan di akhir, terpisah dengan garis, "Kunci/Pedoman untuk Guru" singkat.
TXT;

function ai_buat_dokumen(string $jenis, array $in, array $foto = []): array
{
    $struktur = $jenis === 'lkpd' ? STRUKTUR_LKPD : STRUKTUR_MODUL;
    $data = "Mata pelajaran: {$in['mapel']}\nKelas: {$in['kelas']} — " . fase_dari_kelas((string)$in['kelas']) . "\nMateri/topik: {$in['topik']}\n"
        . "Alokasi waktu: " . ($in['alokasi'] ?? '2 JP × 40 menit, 1 pertemuan') . "\n"
        . (trim((string)($in['model'] ?? '')) !== '' ? "Model pembelajaran yang diinginkan: {$in['model']}\n" : "Model pembelajaran: pilih yang paling cocok\n")
        . (trim((string)($in['cp'] ?? '')) !== '' ? "Teks CP dari guru (pakai persis):\n{$in['cp']}\n" : '')
        . (trim((string)($in['kondisi'] ?? '')) !== '' ? "Kondisi kelas: {$in['kondisi']}\n" : '')
        . (trim((string)($in['catatan'] ?? '')) !== '' ? "Permintaan khusus guru: {$in['catatan']}\n" : '')
        . "Penyusun: " . pengguna()['nama'] . (pengguna()['nip'] ? ' (NIP ' . pengguna()['nip'] . ')' : '') . "\n"
        . ($foto ? "Guru melampirkan foto materi/buku; gunakan isinya.\n" : '');
    $perintah = $struktur . "\n\n" . ATURAN_UMUM . "\n" . konteks_sekolah() . "\n\nData:\n" . $data
        . "\nBalas HANYA dengan isi dokumen Markdown, tanpa pengantar atau penutup percakapan.";
    $isi = $foto ? array_merge([['type' => 'text', 'text' => $perintah]], ai_bagian_foto($foto)) : $perintah;
    $r = ai_minta([['role' => 'user', 'content' => $isi]], ['gambar' => (bool)$foto, 'maks_token' => 9000, 'waktu' => 170, 'suhu' => 0.5]);
    if (!$r['ok']) return $r;
    $md = trim(preg_replace('/^```(?:markdown|md)?\s*|\s*```$/m', '', (string)$r['pesan']['content']));
    if (mb_strlen($md) < 200) return ['ok' => false, 'galat' => 'Jawaban AI terlalu pendek. Coba lagi.', 'rincian' => [$r['oleh']]];
    if (!preg_match('/^#\s+(.+)$/m', $md, $m)) $m = [1 => (JENIS_DOKUMEN[$jenis] ?? 'Dokumen') . ': ' . $in['topik']];
    return ['ok' => true, 'isi' => $md, 'judul' => mb_substr(trim($m[1]), 0, 250), 'oleh' => $r['oleh']];
}

/* ---------- Presentasi ---------- */

function ai_buat_presentasi(array $in, array $foto = []): array
{
    $n = max(4, min(20, (int)($in['jumlah'] ?? 10)));
    $perintah = "Buat bahan presentasi kelas sebanyak $n slide.\nMata pelajaran: {$in['mapel']}\nKelas: {$in['kelas']}\nMateri: {$in['topik']}\n"
        . (trim((string)($in['catatan'] ?? '')) !== '' ? "Permintaan guru: {$in['catatan']}\n" : '')
        . (trim((string)($in['bahan'] ?? '')) !== '' ? "Bahan dari guru:\n" . mb_substr((string)$in['bahan'], 0, 8000) . "\n" : '')
        . "Slide judul (judul, mapel, nama guru) SUDAH dibuat otomatis, jangan dibuat lagi. Susunan $n slide isi: tujuan pembelajaran, pertanyaan pemantik, isi materi bertahap (contoh kontekstual), aktivitas/diskusi, kuis singkat 2–3 soal, rangkuman, refleksi. "
        . "Tiap slide maksimal 5 poin pendek (≤ 15 kata per poin). Catatan pembicara berisi penjelasan guru 2–4 kalimat. "
        . ATURAN_UMUM . "\n" . konteks_sekolah() . "\n"
        . 'Balas HANYA JSON: {"judul":"...","subjudul":"...","slide":[{"judul":"...","poin":["..."],"catatan":"..."}]}';
    $isi = $foto ? array_merge([['type' => 'text', 'text' => $perintah]], ai_bagian_foto($foto)) : $perintah;
    $r = ai_minta([['role' => 'user', 'content' => $isi]], ['gambar' => (bool)$foto, 'maks_token' => 7000, 'waktu' => 150, 'suhu' => 0.6]);
    if (!$r['ok']) return $r;
    $j = ai_ambil_json((string)$r['pesan']['content']);
    if (!$j || empty($j['slide']) || !is_array($j['slide'])) return ['ok' => false, 'galat' => 'AI membalas dengan format yang tidak dikenali. Coba lagi.', 'rincian' => [$r['oleh']]];
    $slide = [];
    foreach ($j['slide'] as $s) {
        if (!is_array($s)) continue;
        $slide[] = ['judul' => trim((string)($s['judul'] ?? '')), 'poin' => array_values(array_filter(array_map(fn($x) => trim((string)$x), (array)($s['poin'] ?? [])), 'strlen')),
            'catatan' => trim((string)($s['catatan'] ?? ''))];
    }
    $data = ['judul' => trim((string)($j['judul'] ?? $in['topik'])), 'subjudul' => trim((string)($j['subjudul'] ?? $in['mapel'] . ' · Kelas ' . $in['kelas'])), 'slide' => $slide];
    return ['ok' => true, 'data' => $data, 'judul' => $data['judul'], 'oleh' => $r['oleh']];
}

/* ---------- Revisi dokumen dengan AI ---------- */

function ai_revisi(string $md, string $perintah): array
{
    $p = "Berikut dokumen Markdown milik guru. Lakukan perubahan sesuai permintaan, pertahankan bagian lain dan format Markdown-nya.\n"
        . "Permintaan: $perintah\n" . ATURAN_UMUM . "\nBalas HANYA dengan dokumen Markdown lengkap hasil revisi.\n\n---\n" . $md;
    $r = ai_minta([['role' => 'user', 'content' => $p]], ['maks_token' => 9000, 'waktu' => 170, 'suhu' => 0.4]);
    if (!$r['ok']) return $r;
    $baru = trim(preg_replace('/^```(?:markdown|md)?\s*|\s*```$/m', '', (string)$r['pesan']['content']));
    if (mb_strlen($baru) < mb_strlen($md) * 0.3) return ['ok' => false, 'galat' => 'Hasil revisi AI terlalu pendek, dibatalkan agar dokumen tidak rusak.', 'rincian' => [$r['oleh']]];
    return ['ok' => true, 'isi' => $baru, 'oleh' => $r['oleh']];
}

/* ---------- Jadwal dari foto roster sekolah ---------- */

/**
 * AI menyalin SELURUH tabel jadwal (tanpa mencari guru tertentu), lalu program yang memilih sel milik guru.
 * Menyalin lebih andal daripada menyuruh AI mencari di tabel yang padat.
 */
function ai_baca_jadwal(array $foto, string $guru): array
{
    $p = "Foto terlampir adalah jadwal pelajaran (roster) sebuah SMP. SALIN tabelnya apa adanya, sel per sel; jangan menyimpulkan apa pun.\n"
        . "- \"kolom\": daftar nama kelas pada judul kolom, urut kiri ke kanan (mis. [\"VII A\",\"VII B\"]).\n"
        . "- \"baris\": satu objek per jam pelajaran: hari (Senin–Sabtu; untuk baris tanpa nama hari, pakai hari di atasnya), jam (angka jam ke-), "
        . "isi = daftar isi sel untuk tiap kolom kelas, urut sama dengan \"kolom\" (kode/nama guru dan/atau mapel persis seperti tertulis; \"\" bila kosong). Abaikan baris istirahat/upacara.\n"
        . "- \"keterangan\": daftar kode guru → nama (dan mapel) bila ada keterangan kode di foto.\n"
        . "Bila jadwal berbentuk lain (mis. per guru), salin ke bentuk yang sama sebaik mungkin.\n"
        . 'Balas HANYA JSON: {"kolom":["VII A"],"baris":[{"hari":"Senin","jam":1,"isi":["05"]}],"keterangan":[{"kode":"05","nama":"Budi Santoso","mapel":"Informatika"}]}';
    $isi = array_merge([['type' => 'text', 'text' => $p]], ai_bagian_foto($foto, 4));
    $r = ai_minta([['role' => 'user', 'content' => $isi]], ['gambar' => true, 'maks_token' => 16000, 'waktu' => 180, 'suhu' => 0]);
    if (!$r['ok']) return $r;
    $j = ai_ambil_json((string)$r['pesan']['content']);
    if (!is_array($j) || !is_array($j['baris'] ?? null) || !is_array($j['kolom'] ?? null)) return ['ok' => false, 'galat' => 'AI tidak bisa membaca tabel jadwal dari foto.', 'rincian' => [$r['oleh']]];
    // kode guru: angka yang diketik langsung, atau dicocokkan dari keterangan
    $cari = trim($guru);
    $kode = [];
    $mapel_kode = [];
    foreach ((array)($j['keterangan'] ?? []) as $k) {
        if (!is_array($k)) continue;
        $kd = trim((string)($k['kode'] ?? ''));
        $nm = (string)($k['nama'] ?? '');
        if ($kd === '') continue;
        $mapel_kode[$kd] = trim((string)($k['mapel'] ?? ''));
        if (jadwal_nama_cocok($cari, $nm) || strcasecmp($kd, $cari) === 0) $kode[] = $kd;
    }
    if (!$kode && preg_match('/^[A-Za-z0-9.]{1,6}$/', $cari)) $kode[] = $cari;
    $out = [];
    $kolom = array_map(fn($k) => rapikan_kelas((string)$k), $j['kolom']);
    foreach ($j['baris'] as $b) {
        if (!is_array($b) || empty($b['jam'])) continue;
        foreach (array_values((array)($b['isi'] ?? [])) as $i => $sel) {
            $sel = trim((string)$sel);
            if ($sel === '' || !isset($kolom[$i])) continue;
            $cocok = null;
            foreach ($kode as $kd) if (preg_match('/(^|[^0-9A-Za-z])' . preg_quote($kd, '/') . '($|[^0-9A-Za-z])/i', $sel)) $cocok = $kd;
            if ($cocok === null && !jadwal_nama_cocok($cari, $sel)) continue;
            $out[] = ['hari' => trim((string)($b['hari'] ?? '')), 'jam_ke' => (int)$b['jam'], 'kelas' => $kolom[$i], 'mapel' => $cocok !== null ? ($mapel_kode[$cocok] ?? '') : ''];
        }
    }
    return ['ok' => true, 'jadwal' => $out, 'kode' => $kode, 'oleh' => $r['oleh']];
}

function jadwal_nama_cocok(string $cari, string $teks): bool
{
    $bersih = fn($s) => trim(preg_replace('/\s+/', ' ', preg_replace('/,?\s*(s\.?\s?pd|s\.?\s?ag|s\.?\s?kom|s\.?\s?si|s\.?\s?e|m\.?\s?pd|gr)\.?\b|\(.*?\)|[^a-z\s]/i', ' ', mb_strtolower($s))));
    $a = $bersih($cari);
    $b = $bersih($teks);
    if (mb_strlen($a) < 3 || mb_strlen($b) < 3) return false;
    if (str_contains($b, $a) || str_contains($a, $b)) return true;
    similar_text($a, $b, $persen);
    return $persen >= 80;
}

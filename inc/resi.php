<?php
/* Resi tugas & ujian online (v1.40): bukti siswa sudah mengumpulkan + halaman status yang berubah sendiri.
 * Kode resi acak (huruf awal T = tugas, U = ujian) disimpan di kolom `resi` guru_tugas_kirim / guru_ujian_peserta.
 * Kode itu sekaligus kunci: siapa pun yang punya link bisa melihat status kiriman itu saja. */
declare(strict_types=1);

function resi_kode_baru(string $awal): string
{
    $huruf = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    do {
        $k = $awal;
        for ($i = 0; $i < 9; $i++) $k .= $huruf[random_int(0, strlen($huruf) - 1)];
    } while (nilai('SELECT 1 FROM guru_tugas_kirim WHERE resi = ?', [$k]) || nilai('SELECT 1 FROM guru_ujian_peserta WHERE resi = ?', [$k]));
    return $k;
}

/** Resi kiriman tugas (dibuat bila belum ada). */
function resi_tugas(int $tid, string $kode): string
{
    $r = (string)nilai('SELECT resi FROM guru_tugas_kirim WHERE tugas_id = ? AND kode = ?', [$tid, $kode]);
    if ($r !== '') return $r;
    $r = resi_kode_baru('T');
    q('UPDATE guru_tugas_kirim SET resi = ? WHERE tugas_id = ? AND kode = ? AND resi IS NULL', [$r, $tid, $kode]);
    return (string)nilai('SELECT resi FROM guru_tugas_kirim WHERE tugas_id = ? AND kode = ?', [$tid, $kode]);
}

/** Resi peserta ujian (dibuat bila belum ada; hanya untuk yang sudah mengumpulkan). */
function resi_ujian(int $uid, string $kode): string
{
    $r = (string)nilai('SELECT resi FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ?', [$uid, $kode]);
    if ($r !== '') return $r;
    $r = resi_kode_baru('U');
    q('UPDATE guru_ujian_peserta SET resi = ? WHERE ujian_id = ? AND kode = ? AND resi IS NULL', [$r, $uid, $kode]);
    return (string)nilai('SELECT resi FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ?', [$uid, $kode]);
}

/** Tampilan nomor resi: T7M2Q-X9K4R */
function resi_teks(string $r): string
{
    return strlen($r) === 10 ? substr($r, 0, 5) . '-' . substr($r, 5) : $r;
}

function alamat_dasar(): string
{
    $s = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $s . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir;
}

function resi_url(string $r): string
{
    return alamat_dasar() . '/r/?k=' . rawurlencode($r);
}

/** Tulisan WA untuk membagikan resi. */
function resi_teks_wa(string $r, string $jenis, string $judul, string $mapel, string $nama, string $kelas, string $waktu): string
{
    return "🧾 *Resi " . $jenis . " SiGuru*\n" . $nama . ' · Kelas ' . $kelas . "\n" . $judul . ($mapel !== '' ? ' (' . $mapel . ')' : '') . "\n"
        . 'No. resi: ' . resi_teks($r) . "\n" . ($waktu !== '' ? 'Dikumpulkan: ' . $waktu . "\n" : '') . "Cek status & nilai:\n" . resi_url($r);
}

/** Kotak resi untuk siswa (setelah mengirim tugas / mengumpulkan ujian). */
function resi_kotak(string $r, string $teks_wa): string
{
    $url = resi_url($r);
    return '<div class="kotak-resi">'
        . '<div class="resi-nomor"><div><small class="redup">No. resi</small><b>' . h(resi_teks($r)) . '</b></div>' . ikon('rekap') . '</div>'
        . '<div class="dua-tombol"><a class="tombol" href="' . h($url) . '" target="_blank" rel="noopener">Lihat resi</a>'
        . '<a class="tombol utama" href="https://wa.me/?text=' . h(rawurlencode($teks_wa)) . '" target="_blank" rel="noopener">Kirim ke WA</a></div>'
        . '<small class="redup">Simpan resi ini sebagai bukti. Statusnya berubah sendiri saat guru memeriksa.</small></div>';
}

/** Tombol "Salin resi" untuk guru. */
function resi_tombol_salin(string $r, string $teks_wa): string
{
    return '<button type="button" class="kecil" data-salin="' . h($teks_wa) . '" title="Salin resi ' . h(resi_teks($r)) . '">Salin resi</button>';
}

/** Data lengkap halaman resi (null = tidak ditemukan). Langkah: [status, judul, keterangan]; status ok|tunggu|ulang|kosong. */
function resi_data(string $r): ?array
{
    if (!preg_match('/^[TU][A-Z0-9]{9}$/', $r)) return null;
    return $r[0] === 'T' ? resi_data_tugas($r) : resi_data_ujian($r);
}

function resi_data_tugas(string $r): ?array
{
    $k = satu('SELECT * FROM guru_tugas_kirim WHERE resi = ?', [$r]);
    $t = $k ? tugas_ambil((int)$k['tugas_id'], false) : null;
    if (!$k || !$t) return null;
    $kn = tugas_kode_kelompok((string)$k['kode']);
    $guru = satu('SELECT nama FROM guru_akun WHERE id = ?', [$t['guru_id']]);
    $d = [
        'jenis' => 'tugas', 'judul_resi' => $kn ? 'Resi tugas kelompok' : 'Resi tugas', 'judul' => $t['judul'], 'mapel' => (string)$t['mapel'],
        'kelas' => $t['kelas'], 'guru' => (string)($guru['nama'] ?? ''), 'nama' => $kn ? 'Kelompok ' . $kn : nama_siswa((string)$k['kode']),
        'anggota' => $kn ? array_values(tugas_anggota($t, $kn)) : [], 'langkah' => [], 'nilai' => null, 'komentar' => '', 'tombol' => null,
        'catatan' => '',
    ];
    $oleh = $kn && $k['oleh'] ? ' oleh ' . nama_siswa((string)$k['oleh']) : '';
    $foto = count(tugas_foto_daftar($k));
    $ket = ujian_waktu_teks((string)$k['dikirim']) . ($foto ? ' · ' . $foto . ' foto' : '') . ((int)$k['kali'] > 1 ? ' · kirim ke-' . (int)$k['kali'] : '') . ($k['telat'] ? ' · terlambat' : '');
    $d['langkah'][] = ['ok', 'Tugas terkirim' . $oleh, $ket];
    if ($k['status'] === 'perbaiki') {
        $d['status'] = ['Perlu perbaikan', 'merah'];
        $d['langkah'][] = ['ulang', 'Guru minta perbaikan', $k['dinilai'] ? ujian_waktu_teks((string)$k['dinilai']) : ''];
        $d['langkah'][] = ['kosong', 'Selesai dinilai', ''];
        $d['komentar'] = (string)$k['komentar'];
        if ($t['status'] === 'buka') $d['tombol'] = ['Kirim perbaikan', alamat_dasar() . '/t/?k=' . $t['kode']];
    } elseif ($k['status'] === 'dinilai') {
        $d['status'] = ['Selesai dinilai', 'hijau'];
        $d['langkah'][] = ['ok', 'Diperiksa guru', $k['dinilai'] ? ujian_waktu_teks((string)$k['dinilai']) : ''];
        $d['langkah'][] = ['ok', 'Selesai dinilai', ''];
        $d['nilai'] = rtrim(rtrim((string)$k['nilai'], '0'), '.');
        $d['komentar'] = (string)$k['komentar'];
        // hasil tugas (untuk layar & PDF): soal, jawaban siswa, pembahasan guru bila sudah dibuka
        $bahas_ada = trim((string)$t['pembahasan']) !== '' || !empty($t['pembahasan_gambar']);
        $bahas_buka = $bahas_ada && (($t['buka_bahas'] ?? 'tutup') === 'nilai' || $t['status'] !== 'buka');
        $d['hasil'] = [
            'petunjuk' => (string)$t['petunjuk'], 'lampiran' => $t['lampiran'],
            'foto' => array_map(fn($f) => tugas_foto_url((int)$t['id'], $f) . '&r=' . rawurlencode($r), tugas_foto_daftar($k)),
            'foto_hapus' => !tugas_foto_daftar($k) && $t['nilai_dikirim'],
            'teks' => (string)$k['teks'], 'bahas_ada' => $bahas_ada, 'bahas_buka' => $bahas_buka,
            'bahas' => $bahas_buka ? (string)$t['pembahasan'] : '', 'bahas_gambar' => $bahas_buka ? $t['pembahasan_gambar'] : null,
            'dikirim' => ujian_waktu_teks((string)$k['dikirim']),
        ];
        $d['pdf'] = 'Hasil tugas';
    } else {
        $d['status'] = ['Menunggu dinilai', 'kuning'];
        $d['langkah'][] = ['tunggu', 'Sedang diperiksa guru', ''];
        $d['langkah'][] = ['kosong', 'Selesai dinilai', ''];
    }
    return $d;
}

function resi_data_ujian(string $r): ?array
{
    $p = satu('SELECT * FROM guru_ujian_peserta WHERE resi = ?', [$r]);
    $u = $p ? ujian_ambil((int)$p['ujian_id'], false) : null;
    if (!$p || !$u) return null;
    $lat = ujian_latihan($u);
    $guru = satu('SELECT nama FROM guru_akun WHERE id = ?', [$u['guru_id']]);
    $d = [
        'jenis' => $lat ? 'latihan' : 'ujian', 'judul_resi' => $lat ? 'Resi latihan soal' : 'Resi ujian online', 'judul' => $u['judul'],
        'mapel' => (string)$u['mapel'], 'kelas' => $u['kelas'], 'guru' => (string)($guru['nama'] ?? ''), 'nama' => nama_siswa((string)$p['kode']),
        'anggota' => [], 'langkah' => [], 'nilai' => null, 'komentar' => '', 'tombol' => null, 'catatan' => '',
    ];
    if ($p['mulai']) $d['langkah'][] = ['ok', $lat && (int)$p['percobaan'] > 1 ? 'Mulai mengerjakan (percobaan ke-' . (int)$p['percobaan'] . ')' : 'Mulai mengerjakan', ujian_waktu_teks((string)$p['mulai'])];
    if (!$p['selesai']) {
        $d['status'] = ['Sedang mengerjakan', 'kuning'];
        $d['langkah'][] = ['tunggu', 'Belum dikumpulkan', 'Siswa sedang mengerjakan lagi (dibuka ulang guru atau latihan diulang).'];
        $d['langkah'][] = ['kosong', 'Nilai keluar', ''];
        return $d;
    }
    $h = ujian_hitung($u, $p);
    $n_soal = count($h['rinci']);
    $jw = json_decode((string)$p['jawaban'], true) ?: [];
    $n_jawab = count(array_filter($jw, fn($v) => (string)$v !== ''));
    $cara = match (true) {
        $p['alasan'] === 'siswa' => 'Dikumpulkan sendiri',
        $p['alasan'] === 'pengaman' => 'Dikumpulkan otomatis (keluar dari layar ujian)',
        $p['batas'] && strtotime((string)$p['selesai']) >= strtotime((string)$p['batas']) => 'Dikumpulkan otomatis (waktu habis)',
        default => 'Dikumpulkan oleh guru / saat ujian ditutup',
    };
    $d['langkah'][] = ['ok', $cara, ujian_waktu_teks((string)$p['selesai']) . ' · ' . $n_jawab . ' dari ' . $n_soal . ' soal dijawab'];
    $ada_uraian = (bool)array_filter($h['rinci'], fn($x) => $x['status'] !== '');
    if ($h['perlu']) $d['langkah'][] = ['tunggu', 'Soal uraian sedang dikoreksi guru', ''];
    elseif ($ada_uraian) $d['langkah'][] = ['ok', 'Soal uraian sudah dikoreksi guru', ''];
    $lihat = ujian_hasil_terbuka($u) || $u['tampil_nilai'];
    if (!$lihat) {
        $d['status'] = ['Sudah dikumpulkan', 'hijau'];
        $d['catatan'] = 'Nilai akan diumumkan oleh guru.';
    } elseif ($h['perlu']) {
        $d['status'] = ['Menunggu koreksi', 'kuning'];
        $d['langkah'][] = ['kosong', 'Nilai keluar', ''];
        $d['catatan'] = 'Nilai keluar setelah soal uraian selesai dikoreksi.';
    } else {
        $d['status'] = ['Nilai keluar', 'hijau'];
        $d['langkah'][] = ['ok', 'Nilai keluar', ''];
        $d['nilai'] = (string)$h['nilai'];
        $d['pdf'] = $lat ? 'Hasil latihan' : 'Hasil ujian';
        if (ujian_hasil_terbuka($u)) $d['pembahasan_html'] = ujian_pembahasan_html($u, $p, $h);
        $kktp = $u['kktp'] !== null ? (float)$u['kktp'] : null;
        $d['rincian'] = ($kktp !== null ? 'KKTP ' . rtrim(rtrim((string)$kktp, '0'), '.') . ' · ' . ((float)$h['nilai'] >= $kktp ? 'Tuntas' : 'Belum tuntas') : '')
            . ($lat && $p['nilai_terbaik'] !== null ? ' · terbaik ' . rtrim(rtrim((string)max((float)$p['nilai_terbaik'], (float)$h['nilai']), '0'), '.') : '');
        if (ujian_hasil_terbuka($u)) $d['catatan'] = '';
        elseif ($u['tampil_hasil']) $d['catatan'] = 'Pembahasan dibuka setelah ujian ditutup guru.';
    }
    return $d;
}

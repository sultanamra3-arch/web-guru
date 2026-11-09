<?php
/* Cadangan data milik guru: satu berkas ZIP berisi Excel (CSV) yang mudah dibuka + data lengkap (JSON) + gambar soal. */
declare(strict_types=1);

/** Kueri data milik guru ini, per bagian. */
function cadangan_bagian(): array
{
    $g = uid();
    $id_in = fn(string $sql, array $par) => array_map('intval', q($sql, $par)->fetchAll(PDO::FETCH_COLUMN)) ?: [0];
    $pen = $id_in('SELECT id FROM guru_penilaian WHERE guru_id = ?', [$g]);
    $uji = $id_in('SELECT id FROM guru_ujian WHERE guru_id = ?', [$g]);
    $tug = $id_in('SELECT id FROM guru_tugas WHERE guru_id = ?', [$g]);
    $ser = $id_in('SELECT id FROM guru_sertifikat WHERE guru_id = ?', [$g]);
    $in = fn(array $ids) => implode(',', $ids);
    return [
        'akun' => ['SELECT id, username, nama, nip, mapel, peran, dibuat FROM guru_akun WHERE id = ?', [$g]],
        'siswa' => ['SELECT kode, nisn, nama, jk, status, dibuat, diubah FROM guru_siswa WHERE guru_id = ? ORDER BY nama', [$g]],
        'kelas' => ['SELECT tahun, nama FROM guru_kelas WHERE guru_id = ? ORDER BY tahun, nama', [$g]],
        'anggota_kelas' => ['SELECT ks.tahun, ks.kelas, ks.absen, s.nisn, s.nama, ks.kode FROM guru_kelas_siswa ks JOIN guru_siswa s ON s.kode = ks.kode WHERE s.guru_id = ? ORDER BY ks.tahun, ks.kelas, ks.absen, s.nama', [$g]],
        'jadwal' => ['SELECT hari, jam_ke, mulai, selesai, kelas, mapel, ruang FROM guru_jadwal WHERE guru_id = ? ORDER BY hari, jam_ke', [$g]],
        'absensi' => ['SELECT a.tanggal, a.kelas, a.mapel, s.nama, a.status, a.ket, a.member_id FROM guru_absensi a LEFT JOIN guru_siswa s ON s.kode = a.member_id WHERE a.guru_id = ? ORDER BY a.tanggal, a.kelas, s.nama', [$g]],
        'penilaian' => ['SELECT id, kelas, mapel, judul, jenis, tanggal, kktp, dibuat FROM guru_penilaian WHERE guru_id = ? ORDER BY tanggal', [$g]],
        'nilai' => ['SELECT n.penilaian_id, p.judul, p.kelas, s.nama, n.nilai, n.catatan, n.member_id FROM guru_nilai n JOIN guru_penilaian p ON p.id = n.penilaian_id LEFT JOIN guru_siswa s ON s.kode = n.member_id WHERE n.penilaian_id IN (' . $in($pen) . ') ORDER BY p.tanggal, s.nama', []],
        'koreksi_kunci' => ['SELECT * FROM guru_koreksi_kunci WHERE penilaian_id IN (' . $in($pen) . ')', []],
        'koreksi' => ['SELECT * FROM guru_koreksi WHERE penilaian_id IN (' . $in($pen) . ')', []],
        'bank_soal' => ['SELECT id, mapel, kelas, topik, tipe, tingkat, level, pertanyaan, opsi, kunci, pembahasan, gambar, gambar_opsi, dibuat FROM guru_soal WHERE guru_id = ? ORDER BY id', [$g]],
        'dokumen' => ["SELECT id, jenis, judul, mapel, kelas, isi, dibuat, diubah FROM guru_dokumen WHERE guru_id = ? ORDER BY id", [$g]],
        'ujian' => ['SELECT * FROM guru_ujian WHERE guru_id = ? ORDER BY id', [$g]],
        'ujian_peserta' => ['SELECT * FROM guru_ujian_peserta WHERE ujian_id IN (' . $in($uji) . ')', []],
        'tugas' => ['SELECT * FROM guru_tugas WHERE guru_id = ? ORDER BY id', [$g]],
        'tugas_kiriman' => ['SELECT tugas_id, kode, teks, foto, dikirim, telat, kali, status, nilai, komentar, dinilai FROM guru_tugas_kirim WHERE tugas_id IN (' . $in($tug) . ')', []],
        'sertifikat' => ['SELECT * FROM guru_sertifikat WHERE guru_id = ? ORDER BY id', [$g]],
        'sertifikat_penerima' => ['SELECT * FROM guru_sertifikat_penerima WHERE sertifikat_id IN (' . $in($ser) . ')', []],
        'setelan' => ["SELECT k, v FROM guru_setelan WHERE k LIKE ?", ['%:' . $g]],
    ];
}

// kolom yang terlalu panjang/teknis tidak dimasukkan ke versi Excel (tetap ada di data.json)
const CADANGAN_TANPA_CSV = ['dokumen' => ['isi'], 'ujian' => ['soal', 'rahasia'], 'ujian_peserta' => ['urutan', 'jawaban', 'ragu', 'nilai_uraian', 'perangkat'], 'koreksi' => ['detail']];

if (get('unduh')) {
    if (!class_exists('ZipArchive')) { pesan('Server tidak mendukung ZIP.', 'galat'); ke('?p=cadangan'); }
    set_time_limit(300);
    $tmp = tempnam(sys_get_temp_dir(), 'sgu');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::OVERWRITE);
    $semua = [];
    $ringkas = [];
    foreach (cadangan_bagian() as $nama => [$sql, $par]) {
        $rows = semua($sql, $par);
        $semua[$nama] = $rows;
        $ringkas[] = "$nama: " . count($rows) . ' baris';
        if (!$rows) continue;
        $buang = CADANGAN_TANPA_CSV[$nama] ?? [];
        $kol = array_values(array_diff(array_keys($rows[0]), $buang));
        $f = fopen('php://temp', 'w+');
        fwrite($f, "\xEF\xBB\xBF");
        fputcsv($f, $kol, ';');
        foreach ($rows as $r) fputcsv($f, array_map(fn($k) => $r[$k], $kol), ';');
        rewind($f);
        $zip->addFromString('excel/' . $nama . '.csv', (string)stream_get_contents($f));
        fclose($f);
    }
    $semua['_info'] = ['aplikasi' => 'SiGuru', 'versi' => VERSI, 'dibuat' => date('c'), 'guru' => pengguna()['nama'], 'sekolah' => (string)k('sekolah', '')];
    $zip->addFromString('data.json', json_encode($semua, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    // gambar soal & lampiran tugas
    $gambar = [];
    foreach ($semua['bank_soal'] as $s) { if ($s['gambar']) $gambar[] = $s['gambar']; foreach (gambar_opsi($s['gambar_opsi']) as $x) $gambar[] = $x; }
    foreach ($semua['tugas'] as $t) if ($t['lampiran']) $gambar[] = $t['lampiran'];
    foreach (array_unique($gambar) as $x) if (preg_match(GAMBAR_POLA, $x) && is_file(gambar_folder() . '/' . $x)) $zip->addFile(gambar_folder() . '/' . $x, 'gambar/' . $x);
    $n_foto = 0;
    if (get('foto')) {
        foreach ($semua['tugas_kiriman'] as $k) foreach (tugas_foto_daftar($k) as $x) {
            if (is_file(tugas_folder() . '/' . $x)) { $zip->addFile(tugas_folder() . '/' . $x, 'foto_tugas/' . $k['tugas_id'] . '_' . $k['kode'] . '_' . $x); $n_foto++; }
        }
    }
    $zip->addFromString('BACA-SAYA.txt', "Cadangan data SiGuru\r\nGuru: " . pengguna()['nama'] . "\r\nDibuat: " . date('d-m-Y H:i') . "\r\nVersi: " . VERSI . "\r\n\r\n"
        . "Folder excel/  : data dalam bentuk CSV, bisa dibuka dengan Excel/Google Sheets.\r\n"
        . "data.json      : data lengkap (dipakai bila perlu dipulihkan oleh admin).\r\n"
        . "gambar/        : gambar soal dan lampiran tugas.\r\n" . (get('foto') ? "foto_tugas/    : $n_foto foto kiriman tugas siswa.\r\n" : '')
        . "\r\nIsi:\r\n" . implode("\r\n", $ringkas) . "\r\n");
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="cadangan-siguru-' . preg_replace('/[^A-Za-z0-9]+/', '-', (string)pengguna()['username']) . '-' . date('Y-m-d') . '.zip"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    setelan_simpan('cadangan_terakhir:' . uid(), date('Y-m-d H:i:s'));
    exit;
}

$jumlah = [];
foreach (['siswa', 'penilaian', 'nilai', 'absensi', 'bank_soal', 'dokumen', 'ujian', 'tugas'] as $b) {
    [$sql, $par] = cadangan_bagian()[$b];
    $jumlah[$b] = count(semua($sql, $par));
}
$terakhir = setelan('cadangan_terakhir:' . uid());
kepala('Cadangan data', 'lainnya');
judul_baris('Cadangan data');
?>
<div class="kartu">
  <p style="margin-top:0">Unduh <b>semua data milikmu</b> dalam satu berkas ZIP: siswa & kelas, jadwal, absensi, penilaian & nilai, bank soal (dengan gambar), perangkat ajar, ujian online, tugas online, dan sertifikat.</p>
  <div class="statistik">
    <span><b><?= $jumlah['siswa'] ?></b>siswa</span><span><b><?= $jumlah['nilai'] ?></b>nilai</span><span><b><?= $jumlah['absensi'] ?></b>absensi</span>
    <span><b><?= $jumlah['bank_soal'] ?></b>soal</span><span><b><?= $jumlah['dokumen'] ?></b>dokumen</span><span><b><?= $jumlah['ujian'] + $jumlah['tugas'] ?></b>ujian/tugas</span>
  </div>
  <form method="get" class="formulir">
    <input type="hidden" name="p" value="cadangan"><input type="hidden" name="unduh" value="1">
    <label class="centang"><input type="checkbox" name="foto" value="1"> <span>Sertakan foto kiriman tugas siswa <small class="redup">(berkas jadi lebih besar)</small></span></label>
    <button class="utama lebar">⬇ Unduh cadangan (ZIP)</button>
  </form>
  <p class="kecil redup">Isi ZIP: folder <b>excel</b> (bisa dibuka dengan Excel / Google Sheets), <b>data.json</b> (data lengkap untuk dipulihkan admin bila perlu), dan <b>gambar</b>.
    <?= $terakhir ? 'Cadangan terakhir: ' . h(ujian_waktu_teks($terakhir)) . '.' : 'Belum pernah membuat cadangan.' ?> Saran: buat cadangan tiap akhir bulan dan simpan di Google Drive.</p>
</div>
<?php
kaki();

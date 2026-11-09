<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
set_time_limit(300);
ignore_user_abort(true);

function balas(array $d): never
{
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') balas(['ok' => false, 'galat' => 'Metode tidak didukung']);
$aksi = (string)post('aksi');
$foto = array_values(array_filter((array)($_POST['foto'] ?? []), 'is_string'));
$in = [];
foreach (['mapel', 'kelas', 'topik', 'jumlah', 'tipe', 'tingkat', 'jumlah_opsi', 'catatan', 'bahan', 'alokasi', 'model', 'cp', 'kondisi'] as $k) {
    $in[$k] = mb_substr(trim((string)post($k)), 0, $k === 'bahan' ? 12000 : 3000);
}
// Lepas kunci sesi agar halaman lain tidak menunggu selama AI bekerja
$sesi_ok = true;

switch ($aksi) {
    case 'buat_soal':
        if ($in['mapel'] === '' || $in['topik'] === '') balas(['ok' => false, 'galat' => 'Isi mapel dan materi/topik.']);
        session_write_close();
        balas(ai_buat_soal($in, $foto));

    case 'buat_dokumen':
        $jenis = (string)post('jenis');
        if ($in['mapel'] === '' || $in['topik'] === '') balas(['ok' => false, 'galat' => 'Isi mapel dan materi/topik.']);
        session_write_close();
        if ($jenis === 'presentasi') {
            $r = ai_buat_presentasi($in, $foto);
            if (!$r['ok']) balas($r);
            $isi = json_encode($r['data'], JSON_UNESCAPED_UNICODE);
        } elseif (in_array($jenis, ['modul', 'lkpd'], true)) {
            $r = ai_buat_dokumen($jenis, $in, $foto);
            if (!$r['ok']) balas($r);
            $isi = $r['isi'];
        } else {
            balas(['ok' => false, 'galat' => 'Jenis tidak dikenal']);
        }
        q('INSERT INTO guru_dokumen (guru_id, jenis, judul, mapel, kelas, isi, dibuat, diubah) VALUES (?,?,?,?,?,?,?,?)',
            [uid(), $jenis, mb_substr($r['judul'], 0, 250), $in['mapel'], mb_substr($in['kelas'], 0, 20), $isi, sekarang(), sekarang()]);
        $id = (int)db()->lastInsertId();
        balas(['ok' => true, 'id' => $id, 'oleh' => $r['oleh'], 'url' => '?p=' . ($jenis === 'presentasi' ? 'presentasi' : 'dokumen_lihat') . '&id=' . $id]);

    case 'revisi':
        $id = (int)post('id');
        $d = satu('SELECT * FROM guru_dokumen WHERE id = ? AND guru_id = ?', [$id, uid()]);
        $perintah = trim((string)post('perintah'));
        if (!$d || $d['jenis'] === 'presentasi' || $perintah === '') balas(['ok' => false, 'galat' => 'Dokumen atau permintaan tidak valid.']);
        session_write_close();
        $r = ai_revisi((string)$d['isi'], mb_substr($perintah, 0, 2000));
        if (!$r['ok']) balas($r);
        q('UPDATE guru_dokumen SET isi = ?, diubah = ? WHERE id = ?', [$r['isi'], sekarang(), $id]);
        balas(['ok' => true, 'oleh' => $r['oleh']]);
    case 'koreksi':
        $pid = (int)post('id');
        $pn = satu('SELECT * FROM guru_penilaian WHERE id = ? AND guru_id = ?', [$pid, uid()]);
        $kk = $pn ? satu('SELECT * FROM guru_koreksi_kunci WHERE penilaian_id = ?', [$pid]) : null;
        if (!$pn || !$kk) balas(['ok' => false, 'galat' => 'Kunci jawaban belum diisi.']);
        $soal = koreksi_urai_kunci((string)$kk['teks']);
        if (!$soal) balas(['ok' => false, 'galat' => 'Kunci jawaban kosong.']);
        if (count($soal) > 80) balas(['ok' => false, 'galat' => 'Maksimal 80 nomor per lembar.']);
        if (!$foto) balas(['ok' => false, 'galat' => 'Ambil foto lembar jawaban dulu.']);
        $mid = (string)post('member_id');
        session_write_close();
        $r = koreksi_proses($pn, $soal, $foto, (string)$kk['format_lj'], post('dua_kali') === '1', $mid === '');
        if (!$r['ok']) balas($r);
        if ($mid === '') {
            $saran = koreksi_cocok_nama($r['nama'], $r['absen'], siswa_kelas((string)$pn['kelas']));
            if ($saran) $r['saran_siswa'] = $saran;
        }
        balas($r);

    case 'koreksi_simpan':
        $pid = (int)post('id');
        $pn = satu('SELECT * FROM guru_penilaian WHERE id = ? AND guru_id = ?', [$pid, uid()]);
        $kk = $pn ? satu('SELECT * FROM guru_koreksi_kunci WHERE penilaian_id = ?', [$pid]) : null;
        $mid = (string)post('member_id');
        $ada_siswa = $pn && in_array($mid, array_column(siswa_kelas((string)$pn['kelas']), 'member_id'), true);
        $data = json_decode((string)post('data'), true);
        if (!$pn || !$kk || !$ada_siswa || !is_array($data)) balas(['ok' => false, 'galat' => 'Data tidak lengkap. Pilih siswa dulu.']);
        $kunci = [];
        foreach (koreksi_urai_kunci((string)$kk['teks']) as $s) $kunci[$s['no']] = $s;
        $hasil = [];
        $skor = 0.0;
        $maks = 0.0;
        $benar = 0;
        $jml_pg = 0;
        foreach ((array)($data['soal'] ?? []) as $x) {
            $no = koreksi_norm_no((string)($x['no'] ?? ''));
            if (!isset($kunci[$no])) continue;
            $s = $kunci[$no];
            $jw = mb_substr(trim((string)($x['jawaban'] ?? '')), 0, 3000);
            if (in_array($s['jenis'], ['pg', 'bs'], true)) {
                $jw = koreksi_huruf($jw, $s['jenis']);
                $sk = ($jw !== '' && $jw === $s['kunci']) ? (float)$s['maks'] : 0.0;
                $jml_pg++;
                if ($sk > 0) $benar++;
            } else {
                $sk = max(0.0, min((float)$s['maks'], round((float)($x['skor'] ?? 0) * 2) / 2));
            }
            $skor += $sk;
            $maks += (float)$s['maks'];
            $hasil[] = ['no' => $no, 'jenis' => $s['jenis'], 'maks' => $s['maks'], 'kunci' => $s['jenis'] === 'pg' || $s['jenis'] === 'bs' ? $s['kunci'] : '',
                'jawaban' => $jw, 'skor' => $sk, 'skor_ai' => isset($x['skor_ai']) ? (float)$x['skor_ai'] : null,
                'ragu' => !empty($x['ragu']), 'catatan' => mb_substr((string)($x['catatan'] ?? ''), 0, 500)];
        }
        if (!$hasil || $maks <= 0) balas(['ok' => false, 'galat' => 'Tidak ada jawaban untuk disimpan.']);
        $nilai = round($skor / $maks * 100);
        $cat = $jml_pg ? 'Foto: benar ' . $benar . '/' . $jml_pg . ' PG' : 'Dinilai dari foto';
        $detail = ['soal' => $hasil, 'skor' => $skor, 'maks' => $maks, 'komentar' => mb_substr((string)($data['komentar'] ?? ''), 0, 1000)];
        db()->beginTransaction();
        q('INSERT INTO guru_koreksi (penilaian_id, member_id, detail, nilai, oleh, dibuat) VALUES (?,?,?,?,?,?)
           ON DUPLICATE KEY UPDATE detail = VALUES(detail), nilai = VALUES(nilai), oleh = VALUES(oleh), dibuat = VALUES(dibuat)',
            [$pid, $mid, json_encode($detail, JSON_UNESCAPED_UNICODE), $nilai, mb_substr((string)($data['oleh'] ?? ''), 0, 200), sekarang()]);
        q('INSERT INTO guru_nilai (penilaian_id, member_id, nilai, catatan) VALUES (?,?,?,?)
           ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), catatan = VALUES(catatan)', [$pid, $mid, $nilai, $cat]);
        db()->commit();
        balas(['ok' => true, 'nilai' => $nilai, 'skor' => $skor, 'maks' => $maks]);
}
balas(['ok' => false, 'galat' => 'Aksi tidak dikenal']);

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
}
balas(['ok' => false, 'galat' => 'Aksi tidak dikenal']);

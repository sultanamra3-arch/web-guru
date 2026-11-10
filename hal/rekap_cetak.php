<?php
/* Rekap kelas satu tugas / ujian online: siap dicetak atau disimpan sebagai PDF (arsip guru). ?p=rekap_cetak&jenis=tugas|ujian&id= */
declare(strict_types=1);

$jenis = get('jenis') === 'ujian' ? 'ujian' : 'tugas';
$id = (int)get('id');
$baris = [];          // [nama, anggota, dikirim, keterangan, nilai(float|null), kelas_ket]
$ringkas = [];
$judul = $kelas = $mapel = $sub = '';
$kktp = 75.0;

if ($jenis === 'tugas') {
    $t = tugas_ambil($id);
    if (!$t) ke('?p=tugas');
    [$judul, $kelas, $mapel] = [(string)$t['judul'], (string)$t['kelas'], (string)$t['mapel']];
    $kel = tugas_kelompok($t);
    $sub = ($kel ? 'Rekap tugas kelompok' : 'Rekap tugas online') . ' · batas ' . ($t['batas'] ? ujian_waktu_teks((string)$t['batas']) : 'tidak ada');
    if ($t['penilaian_id']) $kktp = (float)(nilai('SELECT kktp FROM guru_penilaian WHERE id = ? AND guru_id = ?', [$t['penilaian_id'], uid()]) ?: 75);
    $kirim = [];
    foreach (semua('SELECT * FROM guru_tugas_kirim WHERE tugas_id = ?', [$id]) as $k) $kirim[$k['kode']] = $k;
    $daftar = [];
    if ($kel) foreach (tugas_daftar_kelompok($t) as $n) $daftar[] = ['K' . $n, 'Kelompok ' . $n, implode(', ', tugas_anggota($t, $n))];
    else foreach (siswa_kelas($kelas, (string)($t['tahun'] ?: tahun_aktif())) as $s) $daftar[] = [$s['member_id'], $s['member_name'], ''];
    foreach ($daftar as [$kd, $nm, $ang]) {
        $k = $kirim[$kd] ?? null;
        unset($kirim[$kd]);
        $baris[] = rekap_baris_tugas($nm, $ang, $k);
    }
    foreach ($kirim as $kd => $k) $baris[] = rekap_baris_tugas(tugas_nama_kirim((string)$kd), '', $k);
    $label_kirim = 'Dikirim';
} else {
    $u = ujian_ambil($id);
    if (!$u) ke('?p=ujian');
    [$judul, $kelas, $mapel] = [(string)$u['judul'], (string)$u['kelas'], (string)$u['mapel']];
    $sub = (ujian_latihan($u) ? 'Rekap latihan soal' : 'Rekap ujian online') . ' · ' . count(ujian_soal($u)) . ' soal · ' . (int)$u['durasi'] . ' menit';
    if ($u['kktp'] !== null) $kktp = (float)$u['kktp'];
    $n_soal = count(ujian_soal($u));
    $pes = [];
    foreach (semua('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ?', [$id]) as $p) $pes[$p['kode']] = $p;
    foreach (ujian_siswa($u) as $s) {
        $p = $pes[$s['member_id']] ?? null;
        unset($pes[$s['member_id']]);
        $baris[] = rekap_baris_ujian($u, $s['member_name'], $p, $n_soal);
    }
    foreach ($pes as $kd => $p) $baris[] = rekap_baris_ujian($u, nama_siswa((string)$kd), $p, $n_soal);
    $label_kirim = 'Dikumpulkan';
}

$angka = array_values(array_filter(array_column($baris, 4), fn($v) => $v !== null));
$n_kumpul = count(array_filter($baris, fn($b) => $b[2] !== ''));
$ringkas = [
    ['Mengumpulkan', $n_kumpul . ' dari ' . count($baris)],
    ['Belum', (string)(count($baris) - $n_kumpul)],
    ['Rata-rata', $angka ? str_replace('.', ',', (string)round(array_sum($angka) / count($angka), 1)) : '-'],
    ['Tuntas (KKTP ' . rtrim(rtrim((string)$kktp, '0'), '.') . ')', (string)count(array_filter($angka, fn($v) => $v >= $kktp))],
];
if ($jenis === 'tugas') $ringkas[] = ['Terlambat', (string)count(array_filter($baris, fn($b) => $b[5] === 'telat'))];

function rekap_angka(?float $v): string
{
    return $v === null ? '-' : str_replace('.', ',', rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.'));
}

function rekap_waktu(?string $w): string
{
    if (!$w) return '';
    $hari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'][(int)date('w', strtotime($w))];
    return $hari . ' ' . date('j/n', strtotime($w)) . ' ' . date('H.i', strtotime($w));
}

function rekap_baris_tugas(string $nm, string $ang, ?array $k): array
{
    if (!$k || !$k['dikirim']) return [$nm, $ang, '', 'belum mengirim', null, 'belum'];
    $ket = ['kirim' => 'menunggu dinilai', 'perbaiki' => 'diminta perbaiki', 'dinilai' => 'dinilai'][$k['status']] ?? $k['status'];
    if ($k['telat']) $ket = 'terlambat · ' . $ket;
    return [$nm, $ang, rekap_waktu((string)$k['dikirim']), $ket, $k['status'] === 'dinilai' && $k['nilai'] !== null ? (float)$k['nilai'] : null, $k['telat'] ? 'telat' : ''];
}

function rekap_baris_ujian(array $u, string $nm, ?array $p, int $n_soal): array
{
    if (!$p || !$p['mulai']) return [$nm, '', '', 'belum mengerjakan', null, 'belum'];
    if (!$p['selesai']) return [$nm, '', '', 'sedang mengerjakan', null, 'belum'];
    $jw = array_filter(json_decode((string)$p['jawaban'], true) ?: [], fn($v) => (string)$v !== '');
    $cara = match (true) {
        $p['alasan'] === 'siswa' => 'sendiri',
        $p['alasan'] === 'pengaman' => 'otomatis (keluar layar)',
        $p['batas'] && strtotime((string)$p['selesai']) >= strtotime((string)$p['batas']) => 'otomatis (waktu habis)',
        default => 'oleh guru',
    };
    $ket = count($jw) . '/' . $n_soal . ' dijawab · ' . $cara . ($p['perlu_koreksi'] ? ' · uraian belum dikoreksi' : '');
    $v = $p['nilai'] !== null ? (float)$p['nilai'] : null;
    if (ujian_latihan($u) && $p['nilai_terbaik'] !== null) $v = max((float)$p['nilai_terbaik'], (float)$v);
    return [$nm, '', rekap_waktu((string)$p['selesai']), $ket, $p['perlu_koreksi'] ? null : $v, $p['alasan'] === 'pengaman' ? 'telat' : ''];
}

$kembali = $jenis === 'tugas' ? '?p=tugas_lihat&amp;id=' . $id : '?p=ujian_lihat&amp;id=' . $id;
kepala('Rekap ' . $judul . ' - ' . $kelas, 'kelas');
echo '<div class="judul-baris tak-cetak"><h1>Rekap kelas</h1><span><a class="tombol" href="' . $kembali . '">‹ Kembali</a></span></div>';
?>
<div class="kartu tak-cetak">
  <p style="margin-top:0">Lembar rekap <b><?= h($judul) ?></b> · Kelas <?= h($kelas) ?>. Tekan tombol di bawah, lalu pilih <b>Simpan sebagai PDF</b> (atau pilih printer untuk mencetak).</p>
  <button type="button" class="utama lebar" onclick="window.print()"><?= ikon('cetak') ?> Cetak / simpan PDF</button>
</div>
<div class="kertas lembar-rekap">
  <div class="kop-cetak hanya-cetak"><img src="aset/logo.png" alt=""><div style="flex:1"><b><?= h((string)k('sekolah')) ?></b><br><?= h($sub) ?></div>
    <div class="qr-cetak" data-qr="<?= h(alamat_dasar() . ($jenis === 'tugas' ? '/?p=tugas_lihat&id=' : '/?p=ujian_lihat&id=') . $id) ?>" title="Buka di SiGuru (perlu login guru)"></div></div>
  <h2 class="rekap-judul"><?= h($judul) ?></h2>
  <p class="rekap-sub">Kelas <?= h($kelas) ?><?= $mapel !== '' ? ' · ' . h($mapel) : '' ?> · <?= h(pengguna()['nama']) ?><span class="tak-cetak"> · <?= h($sub) ?></span></p>
  <div class="tabel-gulir">
  <table class="tabel tabel-rekap">
    <thead><tr><th>No</th><th>Nama</th><th><?= $label_kirim ?></th><th>Keterangan</th><th>Nilai</th></tr></thead>
    <tbody>
    <?php foreach ($baris as $i => [$nm, $ang, $wkt, $ket, $v, $cls]): ?>
      <tr class="<?= $cls ? 'r-' . h($cls) : '' ?>"><td><?= $i + 1 ?></td>
        <td><?= h($nm) ?><?php if ($ang !== ''): ?><br><small><?= h($ang) ?></small><?php endif; ?></td>
        <td><?= $wkt !== '' ? h($wkt) : '-' ?></td><td><?= h($ket) ?></td><td class="nilai"><?= rekap_angka($v) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <div class="rekap-ringkas">
    <?php foreach ($ringkas as [$a, $b]): ?><div><span><?= h($a) ?></span><b><?= h($b) ?></b></div><?php endforeach; ?>
  </div>
  <div class="ttd-cetak hanya-cetak"><p><?= h((string)k('kota')) ?>, <?= tgl_panjang(hari_ini()) ?><br>Guru Mata Pelajaran<br><br><br><br><b><?= h(pengguna()['nama']) ?></b><br>NIP <?= h(pengguna()['nip'] ?: '-') ?></p></div>
  <p class="hanya-cetak rekap-dicetak">Dicetak dari SiGuru · <?= h(ujian_waktu_teks(sekarang())) ?></p>
</div>
<?php
$GLOBALS['skrip_tambahan'][] = 'aset/lib/qrcode.js';
$GLOBALS['skrip_tambahan'][] = 'aset/qr-isi.js';
kaki();

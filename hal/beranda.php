<?php
declare(strict_types=1);

$dok = semua('SELECT id, jenis, judul, diubah FROM guru_dokumen WHERE guru_id = ? ORDER BY diubah DESC LIMIT 4', [uid()]);

// tombol "Tutup" langsung dari Beranda (pengingat ujian/tugas yang lama terbuka)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(post('aksi'), ['tutup_ujian', 'tutup_tugas'], true)) {
    $tid = (int)post('id');
    if (post('aksi') === 'tutup_ujian' && ($x = ujian_ambil($tid))) { ujian_tutup($x); otomatis_nilai(uid()); pesan('Ujian "' . h($x['judul']) . '" ditutup. Nilai masuk ke Penilaian.'); }
    if (post('aksi') === 'tutup_tugas' && ($x = tugas_ambil($tid))) { q("UPDATE guru_tugas SET status = 'tutup' WHERE id = ?", [$tid]); pesan('Tugas "' . h($x['judul']) . '" ditutup.'); }
    ke('?p=beranda');
}
// ---- yang sedang berjalan: kuis, ujian/latihan yang dibuka, tugas yang dibuka / masih perlu dinilai ----
$berjalan = [];
foreach (semua("SELECT id, judul, kelas, status, (SELECT COUNT(*) FROM guru_kuis_peserta p WHERE p.kuis_id = k.id) n
                FROM guru_kuis k WHERE guru_id = ? AND status <> 'selesai' AND dibuat >= ? ORDER BY dibuat DESC", [uid(), date('Y-m-d H:i:s', time() - 86400)]) as $k) {
    $berjalan[] = ['ikon' => 'kuis', 'judul' => 'Kuis: ' . $k['judul'], 'kelas' => $k['kelas'],
        'ket' => ($k['status'] === 'lobi' ? 'Ruang tunggu' : 'Sedang berjalan') . ' · ' . (int)$k['n'] . ' peserta', 'url' => '?p=kuis_main&id=' . (int)$k['id'], 'tombol' => 'Buka'];
}
foreach (semua("SELECT u.*, (SELECT COUNT(*) FROM guru_ujian_peserta p WHERE p.ujian_id = u.id AND p.selesai IS NOT NULL) n_selesai,
                (SELECT COUNT(*) FROM guru_ujian_peserta p WHERE p.ujian_id = u.id AND p.mulai IS NOT NULL AND p.selesai IS NULL) n_jalan
                FROM guru_ujian u WHERE guru_id = ? AND status = 'buka' ORDER BY id DESC", [uid()]) as $u) {
    $n = count(ujian_siswa($u));
    $berjalan[] = ['ikon' => 'ujian', 'judul' => (ujian_latihan($u) && empty($u['tindak']) ? 'Latihan: ' : '') . $u['judul'], 'kelas' => $u['kelas'],
        'ket' => (int)$u['n_selesai'] . ($n ? '/' . $n : '') . ' mengumpulkan' . ((int)$u['n_jalan'] ? ' · ' . (int)$u['n_jalan'] . ' sedang mengerjakan' : ''),
        'url' => '?p=ujian_lihat&id=' . (int)$u['id'], 'tombol' => 'Pantau'];
}
foreach (semua("SELECT t.*, (SELECT COUNT(*) FROM guru_tugas_kirim k WHERE k.tugas_id = t.id AND k.dikirim IS NOT NULL) n_kirim,
                (SELECT COUNT(*) FROM guru_tugas_kirim k WHERE k.tugas_id = t.id AND k.dikirim IS NOT NULL AND k.status = 'kirim') n_nilai
                FROM guru_tugas t WHERE guru_id = ? ORDER BY (t.batas IS NULL), t.batas, t.id DESC", [uid()]) as $t) {
    if ($t['status'] !== 'buka' && !(int)$t['n_nilai']) continue;
    $n = tugas_kelompok($t) ? count(tugas_daftar_kelompok($t)) : count(siswa_kelas($t['kelas'], $t['tahun'] ?: null));
    $ket = (int)$t['n_kirim'] . ($n ? '/' . $n : '') . (tugas_kelompok($t) ? ' kelompok' : '') . ' terkumpul';
    if ((int)$t['n_nilai']) $ket .= ' · <b>' . (int)$t['n_nilai'] . ' perlu dinilai</b>';
    if ($t['status'] !== 'buka') $ket .= ' · ditutup';
    elseif ($t['batas']) {
        $hari = (int)floor((strtotime(substr($t['batas'], 0, 10)) - strtotime(hari_ini())) / 86400);
        $ket .= strtotime($t['batas']) < time() ? ' · <span class="teks-merah">lewat batas</span>'
            : ' · batas ' . ($hari <= 0 ? 'hari ini ' . substr($t['batas'], 11, 5) : ($hari === 1 ? 'besok' : $hari . ' hari lagi'));
    }
    $berjalan[] = ['ikon' => 'tugas', 'judul' => $t['judul'], 'kelas' => $t['kelas'], 'ket' => $ket, 'html' => true,
        'url' => '?p=tugas_lihat&id=' . (int)$t['id'], 'tombol' => (int)$t['n_nilai'] ? 'Nilai' : 'Lihat'];
}


// ---- tindakan & pelajaran sekarang ----
$tindak = perlu_tindakan();
$_SESSION['angka_beranda'] = [time(), count($tindak)];
$blok = jadwal_hari_ini();
$jam = date('H:i');
$kini = null; $lanjut = [];
foreach ($blok as $i => $b) {
    if ($kini === null && $b[0] !== '' && $b[0] <= $jam && $jam < $b[1]) $kini = $i;
}
$label_kini = 'Sekarang';
if ($kini === null) foreach ($blok as $i => $b) if ($b[0] > $jam) { $kini = $i; $label_kini = 'Berikutnya'; break; }
if ($kini !== null) $lanjut = array_slice($blok, $kini + 1);
$tugas_kelas = [];
if ($kini !== null) {
    $tugas_kelas = semua("SELECT t.*, (SELECT COUNT(*) FROM guru_tugas_kirim k WHERE k.tugas_id = t.id AND k.dikirim IS NOT NULL) n_kirim
        FROM guru_tugas t WHERE t.guru_id = ? AND t.kelas = ? AND t.status = 'buka' ORDER BY t.id DESC LIMIT 2", [uid(), $blok[$kini][2]]);
}
$jam_teks = fn(array $b) => 'jam ' . ($b[4] === $b[5] ? $b[4] : $b[4] . '–' . $b[5]) . ($b[0] !== '' ? ' · ' . $b[0] . '–' . $b[1] : '');
$sapa = (int)date('G') < 11 ? 'pagi' : ((int)date('G') < 15 ? 'siang' : ((int)date('G') < 18 ? 'sore' : 'malam'));

kepala('Beranda', 'beranda');
?>
<div class="sapa"><small><?= HARI[(int)date('N')] ?? 'Minggu' ?>, <?= tgl_panjang(hari_ini()) ?></small><h1>Selamat <?= $sapa ?>, <?= h(preg_replace('/,.*$/', '', pengguna()['nama'])) ?></h1></div>

<form class="cari-besar" method="get" role="search"><input type="hidden" name="p" value="cari">
  <?= ikon('cari') ?><input type="search" name="q" placeholder="Cari siswa, soal, ujian, tugas…" aria-label="Cari"></form>

<div class="beranda-dua"><div>
<?php if (adalah_admin()): $sk = sekolah_ringkas(); ?>
<div class="kartu kartu-sekolah">
  <h2>Sekolah <span class="lencana">khusus admin</span></h2>
  <?php if ($sk): ?>
  <?php if ((int)$sk['terlambat']): ?>
  <ul class="baris-tindak"><li><span class="bt-ikon"><?= ikon('buku') ?></span><span class="bt-isi"><b><?= (int)$sk['terlambat'] ?> pinjaman lewat jatuh tempo</b><small>Perpustakaan · buku belum dikembalikan</small></span>
    <a class="tombol kecil" href="?p=ke_pustaka&amp;ke=pinjaman" data-tanpa-siap>Lihat</a></li></ul>
  <?php endif; ?>
  <div class="statistik sekolah-angka">
    <span><b><?= (int)$sk['tamu'] ?></b>pengunjung hari ini</span>
    <span><b><?= (int)$sk['aktif'] ?></b>pinjaman aktif</span>
    <span><b><?= number_format((int)$sk['eksemplar'], 0, ',', '.') ?></b>eksemplar</span>
    <span><b><?= number_format((int)$sk['anggota'], 0, ',', '.') ?></b>anggota</span>
  </div>
  <?php endif; ?>
  <div class="ikon-kisi apk-sekolah">
    <?php foreach (APLIKASI_SEKOLAH as $i => [$jd, $ket, $ik, $kc, $url]): ?>
    <a href="<?= $i === 0 ? '?p=ke_pustaka' : h(sekolah_url($kc, $url)) ?>" data-tanpa-siap><span class="ikon-kotak"><?= ikon($ik) ?></span><?= h($jd) ?><small><?= h($ket) ?></small></a>
    <?php endforeach; ?>
  </div>
  <?php if (!sso_kunci() || sso_akun_slims() === ''): ?><p class="kecil redup" style="margin:.5rem 0 0">SiPustaka belum bisa terbuka tanpa login: <?= !sso_kunci() ? 'kunci bersama belum bisa dibuat di folder data SiPustaka.' : 'atur nama pengguna SLiMS di <a href="?p=profil">Profil</a>.' ?></p><?php endif; ?>
</div>
<?php endif; ?>
<?php if ($tindak): ?>
<div class="kartu kartu-tindak">
  <h2>Perlu tindakan <span class="lencana kuning"><?= count($tindak) ?></span></h2>
  <ul class="baris-tindak">
  <?php foreach ($tindak as $t): ?>
    <li><span class="bt-ikon"><?= ikon($t['ikon']) ?></span>
      <span class="bt-isi"><b><?= h($t['judul']) ?></b><small><?= h($t['ket']) ?></small></span>
      <?php if (!empty($t['tutup'])): ?>
      <form method="post" onsubmit="return confirm('Tutup sekarang? Siswa tidak bisa mengerjakan/mengirim lagi.')"><?= input_token() ?>
        <input type="hidden" name="aksi" value="<?= h($t['tutup'][0]) ?>"><input type="hidden" name="id" value="<?= (int)$t['tutup'][1] ?>"><button class="kecil">Tutup</button></form>
      <?php else: ?><a class="tombol kecil" href="<?= h($t['url']) ?>"><?= h($t['tombol']) ?></a><?php endif; ?></li>
  <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="kartu kartu-kini">
<?php if ($kini !== null): $b = $blok[$kini]; $kq = rawurlencode($b[2]); ?>
  <small class="kini-label"><?= $label_kini ?> · <?= h($jam_teks($b)) ?></small>
  <b class="kini-kelas"><?= h($b[3]) ?> · <?= h($b[2]) ?></b>
  <div class="kini-tombol">
    <a class="utama" href="?p=absensi&amp;kelas=<?= h($kq) ?>&amp;mapel=<?= h(rawurlencode($b[3])) ?>"><?= ikon('absensi') ?>Absen</a>
    <a href="?p=tugas_form&amp;kelas=<?= h($kq) ?>"><?= ikon('tugas') ?>Tugas</a>
    <a href="?p=kuis&amp;kelas=<?= h($kq) ?>"><?= ikon('kuis') ?>Kuis</a>
    <a href="?p=ujian&amp;kelas=<?= h($kq) ?>"><?= ikon('ujian') ?>Ujian</a>
  </div>
  <?php foreach ($tugas_kelas as $t): ?><p class="kini-ket">Tugas berjalan di kelas ini: <a href="?p=tugas_lihat&amp;id=<?= (int)$t['id'] ?>"><b><?= h($t['judul']) ?></b></a> · <?= (int)$t['n_kirim'] ?> terkumpul<?= $t['batas'] ? ' · batas ' . h(ujian_waktu_teks((string)$t['batas'])) : '' ?></p><?php endforeach; ?>
  <?php if ($lanjut): ?><p class="kini-ket">Berikutnya: <?= h(implode(' · ', array_map(fn($x) => ($x[0] ?: 'jam ' . $x[4]) . ' ' . $x[2], $lanjut))) ?> · <a href="?p=jadwal">jadwal</a></p><?php endif; ?>
<?php elseif ($blok): ?>
  <small class="kini-label">Jadwal hari ini</small>
  <b class="kini-kelas">Pelajaran hari ini sudah selesai</b>
  <p class="kini-ket"><?= h(implode(' · ', array_map(fn($x) => ($x[0] ?: 'jam ' . $x[4]) . ' ' . $x[2], $blok))) ?> · <a href="?p=jadwal">jadwal</a></p>
<?php else: ?>
  <small class="kini-label">Jadwal hari ini</small>
  <b class="kini-kelas">Tidak ada jadwal mengajar hari ini</b>
  <p class="kini-ket"><a href="?p=jadwal">Atur jadwal pelajaran →</a></p>
<?php endif; ?>
</div>

<?php if ($berjalan): ?>
<div class="kartu berjalan">
  <h2>Sedang berjalan <span class="lencana"><?= count($berjalan) ?></span></h2>
  <ul class="daftar">
    <?php foreach (array_slice($berjalan, 0, 5) as $b): ?>
    <li><span class="berjalan-ikon"><?= ikon($b['ikon']) ?></span>
      <span class="berjalan-isi"><b><?= h($b['judul']) ?></b><small>Kelas <?= h($b['kelas']) ?> · <?= !empty($b['html']) ? $b['ket'] : h($b['ket']) ?></small></span>
      <a class="tombol kecil" href="<?= h($b['url']) ?>"><?= h($b['tombol']) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <?php if (count($berjalan) > 5): ?><p class="kecil" style="margin:.4rem 0 0">Lihat semua: <a href="?p=kuis">Kuis</a> · <a href="?p=ujian">Ujian</a> · <a href="?p=tugas">Tugas</a></p><?php endif; ?>
</div>
<?php endif; ?>

</div><div>
<div class="kartu">
  <h2>Pintasan</h2>
  <div class="ikon-kisi">
    <a href="?p=soal_ai"><span class="ikon-kotak"><?= ikon('ai') ?></span>Buat soal</a>
    <a href="?p=dokumen_buat&amp;jenis=modul"><span class="ikon-kotak"><?= ikon('modul') ?></span>Modul ajar</a>
    <a href="?p=kisi"><span class="ikon-kotak"><?= ikon('kisi') ?></span>Kisi-kisi</a>
    <a href="?p=dokumen_buat&amp;jenis=presentasi"><span class="ikon-kotak"><?= ikon('presentasi') ?></span>Presentasi</a>
    <a href="?p=absensi"><span class="ikon-kotak"><?= ikon('absensi') ?></span>Absensi</a>
    <a href="?p=penilaian"><span class="ikon-kotak"><?= ikon('nilai') ?></span>Nilai</a>
    <a href="?p=ujian"><span class="ikon-kotak"><?= ikon('ujian') ?></span>Ujian</a>
    <a href="?p=tugas"><span class="ikon-kotak"><?= ikon('tugas') ?></span>Tugas</a>
  </div>
</div>

<?php if ($dok): ?>
<div class="kartu">
  <h2>Perangkat terakhir</h2>
  <ul class="daftar">
  <?php foreach ($dok as $d): ?>
    <li><a href="?p=<?= ['presentasi' => 'presentasi', 'paket' => 'paket_cetak', 'kisi' => 'kisi'][$d['jenis']] ?? 'dokumen_lihat' ?>&amp;id=<?= (int)$d['id'] ?>"><b><?= h($d['judul']) ?></b>
      <small><?= h(JENIS_DOKUMEN[$d['jenis']] ?? $d['jenis']) ?> · <?= tgl($d['diubah']) ?></small></a></li>
  <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
</div></div>
<?php if (!ai_siap()): ?>
  <div class="pesan peringatan">Fitur AI belum aktif: kunci AI belum ditemukan. <?= adalah_admin() ? '<a href="?p=pengaturan_ai">Atur kunci AI →</a>' : 'Hubungi admin.' ?></div>
<?php endif; ?>
<?php
kaki();

<?php
declare(strict_types=1);

$tahun = tahun_aktif();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('aksi') === 'tambah_kelas') {
        $nm = rapikan_kelas(mb_substr(trim((string)post('nama')), 0, 50));
        if ($nm !== '') { pastikan_kelas($nm); pesan('Kelas ' . h($nm) . ' ditambahkan.'); }
    } elseif (post('aksi') === 'tahun' && preg_match('/^\d{4}\/\d{4}$/', (string)post('tahun'))) {
        setelan_simpan('tahun_ajaran:' . uid(), (string)post('tahun'));
        pesan('Tahun ajaran aktif: ' . h((string)post('tahun')));
    }
    ke('?p=kelas');
}

$kelas = daftar_kelas();
$jumlah = [];
foreach (semua("SELECT ks.kelas, COUNT(*) n, SUM(s.jk = 'L') l, SUM(s.jk = 'P') pr FROM guru_kelas_siswa ks JOIN guru_siswa s ON s.kode = ks.kode
    WHERE ks.tahun = ? AND s.status = 'aktif' AND s.guru_id = ? GROUP BY ks.kelas", [$tahun, uid()]) as $r) $jumlah[$r['kelas']] = $r;
$saya = q('SELECT DISTINCT kelas FROM guru_jadwal WHERE guru_id = ?', [uid()])->fetchAll(PDO::FETCH_COLUMN);
$tahun_ada = q('SELECT DISTINCT tahun FROM guru_kelas WHERE guru_id = ? ORDER BY tahun DESC', [uid()])->fetchAll(PDO::FETCH_COLUMN);
$tahun_ada = array_values(array_unique(array_merge([$tahun, tahun_berikut($tahun)], $tahun_ada)));
rsort($tahun_ada);

kepala('Kelas', 'kelas');
judul_baris('Kelas & siswa', '<a class="tombol" href="?p=siswa_tambah">＋ Siswa</a>');
?>
<p class="redup kecil" style="margin-top:-.3rem">Kelas & siswa milikmu sendiri · tahun ajaran <b><?= h($tahun) ?></b> ·
  <a href="?p=siswa_naik">Naik kelas / tahun ajaran baru</a></p>

<?php if (!$kelas): ?>
<div class="kartu">
  <h3 style="margin-top:0">Belum ada data siswa</h3>
  <p>Isi daftar siswa per kelas dengan salah satu cara: <b>foto daftar hadir</b> (dibaca AI), <b>tempel daftar nama</b>, atau <b>impor Excel</b>.</p>
  <a class="tombol utama lebar" href="?p=siswa_tambah">＋ Tambah siswa</a>
</div>
<?php endif;
$baris_kelas = function (string $k, bool $utama) use ($jumlah): void {
    $j = $jumlah[$k] ?? ['n' => 0, 'l' => 0, 'pr' => 0]; $q = h(rawurlencode($k)); ?>
  <li class="baris-kelas">
    <a class="bk-nama" href="?p=kelas_lihat&amp;kelas=<?= $q ?>"><b>Kelas <?= h($k) ?></b><small><?= (int)$j['n'] ?> siswa · L <?= (int)$j['l'] ?> · P <?= (int)$j['pr'] ?></small></a>
    <a class="tombol kecil<?= $utama ? ' utama' : '' ?>" href="?p=absensi&amp;kelas=<?= $q ?>">Absen</a>
    <details class="menu-titik"><summary aria-label="Menu kelas <?= h($k) ?>"><?= ikon('titik') ?></summary>
      <div><a href="?p=siswa&amp;kelas=<?= $q ?>">Siswa</a><a href="?p=nilai_rekap&amp;kelas=<?= $q ?>">Nilai</a><a href="?p=kelompok&amp;kelas=<?= $q ?>">Kelompok</a><a href="?p=absensi_rekap&amp;kelas=<?= $q ?>">Rekap absensi</a></div></details>
  </li>
<?php };
$kelas_saya = array_values(array_intersect($kelas, $saya));
$kelas_lain = array_values(array_diff($kelas, $saya));
if ($kelas_saya): ?>
<div class="kartu">
  <h2 class="judul-kecil">Kelas saya</h2>
  <ul class="daftar-kelas"><?php foreach ($kelas_saya as $i => $k) $baris_kelas($k, $i === 0); ?></ul>
</div>
<?php endif; ?>
<?php if ($kelas_lain): ?>
<details class="kartu" <?= $kelas_saya ? '' : 'open' ?>>
  <summary><b><?= $kelas_saya ? 'Kelas lain (' . count($kelas_lain) . ')' : 'Kelas' ?></b> <small class="redup"><?= $kelas_saya ? h(implode(', ', $kelas_lain)) : '' ?></small></summary>
  <ul class="daftar-kelas"><?php foreach ($kelas_lain as $k) $baris_kelas($k, false); ?></ul>
  <?php if ($kelas_saya): ?><p class="kecil redup" style="margin:.4rem 0 0">"Kelas saya" diambil dari jadwal mengajar. <a href="?p=jadwal">Atur jadwal →</a></p><?php endif; ?>
</details>
<?php endif; ?>

<details class="kartu">
  <summary><b>＋ Kelas baru (kosong)</b></summary>
  <form method="post" class="formulir" style="margin-top:.6rem">
    <?= input_token() ?><input type="hidden" name="aksi" value="tambah_kelas">
    <label>Nama kelas<input name="nama" required placeholder="mis. VII A"></label>
    <button class="lebar">Tambah kelas</button>
  </form>
</details>
<details class="kartu">
  <summary><b>Tahun ajaran</b></summary>
  <form method="post" class="formulir" style="margin-top:.6rem">
    <?= input_token() ?><input type="hidden" name="aksi" value="tahun">
    <label>Tampilkan & kerjakan data tahun<select name="tahun"><?= pilihan(array_combine($tahun_ada, $tahun_ada), $tahun) ?></select></label>
    <p class="kecil redup">Untuk melihat data tahun lalu. Untuk pindah ke tahun baru beserta kenaikan kelas, pakai "Naik kelas".</p>
    <button class="lebar">Simpan</button>
  </form>
</details>
<?php
kaki();

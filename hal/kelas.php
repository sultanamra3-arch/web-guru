<?php
declare(strict_types=1);

$tahun = tahun_aktif();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('aksi') === 'tambah_kelas') {
        $nm = rapikan_kelas(mb_substr(trim((string)post('nama')), 0, 50));
        if ($nm !== '') { pastikan_kelas($nm); pesan('Kelas ' . h($nm) . ' ditambahkan.'); }
    } elseif (post('aksi') === 'tahun' && adalah_admin() && preg_match('/^\d{4}\/\d{4}$/', (string)post('tahun'))) {
        setelan_simpan('tahun_ajaran', (string)post('tahun'));
        pesan('Tahun ajaran aktif: ' . h((string)post('tahun')));
    }
    ke('?p=kelas');
}

$kelas = daftar_kelas();
$jumlah = [];
foreach (semua("SELECT ks.kelas, COUNT(*) n, SUM(s.jk = 'L') l, SUM(s.jk = 'P') pr FROM guru_kelas_siswa ks JOIN guru_siswa s ON s.kode = ks.kode
    WHERE ks.tahun = ? AND s.status = 'aktif' GROUP BY ks.kelas", [$tahun]) as $r) $jumlah[$r['kelas']] = $r;
$wali = [];
foreach (semua('SELECT k.nama, a.nama guru FROM guru_kelas k JOIN guru_akun a ON a.id = k.wali_id WHERE k.tahun = ?', [$tahun]) as $r) $wali[$r['nama']] = $r['guru'];
$saya = q('SELECT DISTINCT kelas FROM guru_jadwal WHERE guru_id = ?', [uid()])->fetchAll(PDO::FETCH_COLUMN);
$tahun_ada = q('SELECT DISTINCT tahun FROM guru_kelas ORDER BY tahun DESC')->fetchAll(PDO::FETCH_COLUMN);
$tahun_ada = array_values(array_unique(array_merge([$tahun, tahun_berikut($tahun)], $tahun_ada)));
rsort($tahun_ada);

kepala('Kelas', 'kelas');
judul_baris('Kelas & siswa', '<a class="tombol" href="?p=siswa_tambah">＋ Siswa</a>');
?>
<div class="tombol-besar">
  <a class="utama" href="?p=absensi">✅ Isi absensi</a>
  <a href="?p=penilaian">📊 Penilaian</a>
  <a href="?p=ujian">💻 Ujian online</a>
  <a href="?p=absensi_rekap">📋 Rekap absensi</a>
</div>
<p class="redup kecil">Tahun ajaran <b><?= h($tahun) ?></b><?php if (adalah_admin()): ?> ·
  <a href="?p=siswa_naik">Naik kelas / tahun ajaran baru</a><?php endif; ?></p>

<?php if (!$kelas): ?>
<div class="kartu">
  <h3 style="margin-top:0">Belum ada data siswa</h3>
  <p>Isi daftar siswa per kelas dengan salah satu cara: <b>foto daftar hadir</b> (dibaca AI), <b>tempel daftar nama</b>, atau <b>impor Excel</b>.</p>
  <a class="tombol utama lebar" href="?p=siswa_tambah">＋ Tambah siswa</a>
</div>
<?php endif; ?>

<ul class="daftar kartu">
<?php foreach ($kelas as $k): $j = $jumlah[$k] ?? ['n' => 0, 'l' => 0, 'pr' => 0]; ?>
  <li>
    <div class="eks-atas"><a href="?p=siswa&amp;kelas=<?= h(rawurlencode($k)) ?>"><b>Kelas <?= h($k) ?></b></a>
      <?= in_array($k, $saya, true) ? '<span class="lencana">kelas saya</span>' : '' ?>
      <small><?= (int)$j['n'] ?> siswa · L <?= (int)$j['l'] ?> · P <?= (int)$j['pr'] ?><?= isset($wali[$k]) ? ' · wali: ' . h($wali[$k]) : '' ?></small></div>
    <div class="eks-aksi">
      <a class="tombol kecil" href="?p=siswa&amp;kelas=<?= h(rawurlencode($k)) ?>">Siswa</a>
      <a class="tombol kecil" href="?p=absensi&amp;kelas=<?= h(rawurlencode($k)) ?>">Absensi</a>
      <a class="tombol kecil" href="?p=nilai_rekap&amp;kelas=<?= h(rawurlencode($k)) ?>">Nilai</a>
      <a class="tombol kecil" href="?p=absensi_rekap&amp;kelas=<?= h(rawurlencode($k)) ?>">Rekap</a>
    </div>
  </li>
<?php endforeach; ?>
</ul>

<details class="kartu">
  <summary><b>＋ Kelas baru (kosong)</b></summary>
  <form method="post" class="formulir" style="margin-top:.6rem">
    <?= input_token() ?><input type="hidden" name="aksi" value="tambah_kelas">
    <label>Nama kelas<input name="nama" required placeholder="mis. VII A"></label>
    <button class="lebar">Tambah kelas</button>
  </form>
</details>
<?php if (adalah_admin()): ?>
<details class="kartu">
  <summary><b>Tahun ajaran aktif</b></summary>
  <form method="post" class="formulir" style="margin-top:.6rem">
    <?= input_token() ?><input type="hidden" name="aksi" value="tahun">
    <label>Tampilkan & kerjakan data tahun<select name="tahun"><?= pilihan(array_combine($tahun_ada, $tahun_ada), $tahun) ?></select></label>
    <p class="kecil redup">Berlaku untuk semua guru. Untuk pindah ke tahun baru beserta kenaikan kelas, pakai "Naik kelas".</p>
    <button class="lebar">Simpan</button>
  </form>
</details>
<?php endif; ?>
<?php
kaki();

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
<div class="tombol-besar">
  <a class="utama" href="?p=absensi"><?= ikon('absensi') ?><span>Isi absensi</span></a>
  <a href="?p=penilaian"><?= ikon('nilai') ?><span>Penilaian</span></a>
  <a href="?p=ujian"><?= ikon('ujian') ?><span>Ujian online</span></a>
  <a href="?p=tugas"><?= ikon('tugas') ?><span>Tugas online</span></a>
</div>
<p class="redup kecil">Kelas & siswa milikmu sendiri (guru lain tidak melihatnya) · tahun ajaran <b><?= h($tahun) ?></b> ·
  <a href="?p=siswa_naik">Naik kelas / tahun ajaran baru</a></p>

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
      <small><?= (int)$j['n'] ?> siswa · L <?= (int)$j['l'] ?> · P <?= (int)$j['pr'] ?></small></div>
    <div class="eks-aksi kelas-aksi">
      <a class="tombol kecil" href="?p=siswa&amp;kelas=<?= h(rawurlencode($k)) ?>">Siswa</a>
      <a class="tombol kecil" href="?p=absensi&amp;kelas=<?= h(rawurlencode($k)) ?>">Absen</a>
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

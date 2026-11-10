<?php
/* Pencarian satu kotak (v1.44): siswa (beserta ujian & tugas kelasnya), ujian, tugas, soal, perangkat. Hanya data guru yang login. */
declare(strict_types=1);

$q = trim((string)get('q'));
$hasil = ['siswa' => [], 'ujian' => [], 'tugas' => [], 'soal' => [], 'dok' => []];
if (mb_strlen($q) >= 2) {
    $like = '%' . $q . '%';
    $tahun = tahun_aktif();
    $hasil['siswa'] = semua("SELECT s.kode, s.nama, ks.kelas FROM guru_siswa s LEFT JOIN guru_kelas_siswa ks ON ks.kode = s.kode AND ks.tahun = ?
        WHERE s.guru_id = ? AND s.status = 'aktif' AND s.nama LIKE ? ORDER BY s.nama LIMIT 8", [$tahun, uid(), $like]);
    $hasil['ujian'] = semua('SELECT id, judul, kelas, status FROM guru_ujian WHERE guru_id = ? AND judul LIKE ? ORDER BY id DESC LIMIT 6', [uid(), $like]);
    $hasil['tugas'] = semua('SELECT id, judul, kelas, status FROM guru_tugas WHERE guru_id = ? AND judul LIKE ? ORDER BY id DESC LIMIT 6', [uid(), $like]);
    $hasil['soal'] = semua('SELECT id, pertanyaan, tipe, kelas FROM guru_soal WHERE guru_id = ? AND (pertanyaan LIKE ? OR topik LIKE ?) ORDER BY id DESC LIMIT 6', [uid(), $like, $like]);
    $hasil['dok'] = semua('SELECT id, jenis, judul FROM guru_dokumen WHERE guru_id = ? AND judul LIKE ? ORDER BY diubah DESC LIMIT 6', [uid(), $like]);
}

/** Keadaan satu siswa di ujian & tugas terbaru kelasnya. */
function cari_kegiatan_siswa(string $kode, string $kelas): array
{
    if ($kelas === '') return [];
    $o = [];
    foreach (semua("SELECT * FROM guru_ujian WHERE guru_id = ? AND kelas = ? AND status <> 'draf' ORDER BY id DESC LIMIT 3", [uid(), $kelas]) as $u) {
        $ikut = array_column(ujian_siswa($u), 'member_id');
        if (!in_array($kode, $ikut, true)) continue;
        $p = satu('SELECT selesai, nilai, perlu_koreksi FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ?', [$u['id'], $kode]);
        $ket = !$p ? 'belum mengerjakan' : (!$p['selesai'] ? 'sedang mengerjakan' : ($p['perlu_koreksi'] ? 'uraian belum dikoreksi' : 'nilai ' . rtrim(rtrim((string)$p['nilai'], '0'), '.')));
        $o[] = [$u['judul'], 'Ujian', $ket, '?p=ujian_hasil&id=' . (int)$u['id'], !$p && $u['status'] === 'tutup'];
    }
    foreach (semua("SELECT * FROM guru_tugas WHERE guru_id = ? AND kelas = ? ORDER BY id DESC LIMIT 3", [uid(), $kelas]) as $t) {
        $kk = tugas_kode_kirim($t, $kode);
        $k = $kk ? satu('SELECT dikirim, status, nilai FROM guru_tugas_kirim WHERE tugas_id = ? AND kode = ?', [$t['id'], $kk]) : null;
        $ket = !$k || !$k['dikirim'] ? 'belum mengirim' : ($k['status'] === 'dinilai' ? 'dinilai ' . rtrim(rtrim((string)$k['nilai'], '0'), '.') : ($k['status'] === 'perbaiki' ? 'diminta perbaiki' : 'menunggu dinilai'));
        $o[] = [$t['judul'], $kk && tugas_kode_kelompok($kk) ? 'Tugas kelompok' : 'Tugas', $ket, '?p=tugas_lihat&id=' . (int)$t['id'], false];
    }
    return $o;
}

$ada = array_filter($hasil);
kepala('Cari', 'beranda');
judul_baris('Cari', '<a class="tombol" href="?p=beranda">‹ Beranda</a>');
?>
<form class="cari-besar" method="get" role="search"><input type="hidden" name="p" value="cari">
  <?= ikon('cari') ?><input type="search" name="q" value="<?= h($q) ?>" placeholder="Cari siswa, soal, ujian, tugas…" aria-label="Cari" autofocus></form>

<?php if (mb_strlen($q) < 2): ?>
  <p class="redup">Ketik paling sedikit 2 huruf, misalnya nama siswa, judul ujian, atau topik soal.</p>
<?php elseif (!$ada): ?>
  <p class="redup">Tidak ada yang cocok dengan "<?= h($q) ?>".</p>
<?php endif; ?>

<?php if ($hasil['siswa']): ?>
<h2 class="grup-judul">Siswa</h2>
<div class="kartu">
  <ul class="daftar">
  <?php foreach ($hasil['siswa'] as $i => $s): ?>
    <li><a href="?p=siswa_ubah&amp;kode=<?= h(rawurlencode($s['kode'])) ?>"><b><?= h($s['nama']) ?></b><small><?= $s['kelas'] ? 'Kelas ' . h($s['kelas']) : 'belum masuk kelas' ?></small></a>
      <?php if ($i < 3): foreach (cari_kegiatan_siswa((string)$s['kode'], (string)$s['kelas']) as [$j, $jenis, $ket, $url, $susul]): ?>
        <a class="cari-sub" href="<?= h($url) ?>"><span><?= h($jenis) ?>: <?= h($j) ?></span><small class="<?= str_starts_with($ket, 'belum') ? 'teks-merah' : '' ?>"><?= h($ket) ?><?= $susul ? ' · buat susulan' : '' ?></small></a>
      <?php endforeach; endif; ?></li>
  <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<?php foreach (['ujian' => ['Ujian', 'ujian_lihat'], 'tugas' => ['Tugas', 'tugas_lihat']] as $k => [$jd, $hal]): if (!$hasil[$k]) continue; ?>
<h2 class="grup-judul"><?= $jd ?></h2>
<ul class="daftar kartu">
  <?php foreach ($hasil[$k] as $x): ?><li><a href="?p=<?= $hal ?>&amp;id=<?= (int)$x['id'] ?>"><b><?= h($x['judul']) ?></b><small>Kelas <?= h($x['kelas']) ?> · <?= ['buka' => 'berjalan', 'tutup' => 'selesai', 'draf' => 'draf'][$x['status']] ?? h($x['status']) ?></small></a></li><?php endforeach; ?>
</ul>
<?php endforeach; ?>

<?php if ($hasil['soal']): ?>
<h2 class="grup-judul">Soal</h2>
<ul class="daftar kartu">
  <?php foreach ($hasil['soal'] as $s): ?><li><a href="?p=soal_form&amp;id=<?= (int)$s['id'] ?>"><b><?= h(mb_strimwidth((string)$s['pertanyaan'], 0, 120, '…')) ?></b><small><?= h(TIPE_SOAL[$s['tipe']] ?? $s['tipe']) ?><?= $s['kelas'] ? ' · Kls ' . h($s['kelas']) : '' ?></small></a></li><?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if ($hasil['dok']): ?>
<h2 class="grup-judul">Perangkat ajar</h2>
<ul class="daftar kartu">
  <?php foreach ($hasil['dok'] as $d): ?><li><a href="?p=<?= ['presentasi' => 'presentasi', 'paket' => 'paket_cetak', 'kisi' => 'kisi'][$d['jenis']] ?? 'dokumen_lihat' ?>&amp;id=<?= (int)$d['id'] ?>"><b><?= h($d['judul']) ?></b><small><?= h(JENIS_DOKUMEN[$d['jenis']] ?? $d['jenis']) ?></small></a></li><?php endforeach; ?>
</ul>
<?php endif; ?>
<?php
kaki();

<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = array_map('intval', (array)($_POST['pilih'] ?? []));
    if (post('aksi') === 'hapus' && $ids) {
        $n = q('DELETE FROM guru_soal WHERE guru_id = ? AND id IN (' . implode(',', $ids) . ')', [uid()])->rowCount();
        pesan($n . ' soal dihapus.');
    } elseif (post('aksi') === 'paket' && $ids) {
        $_SESSION['paket_pilih'] = $ids;
        ke('?p=paket');
    } else {
        pesan('Centang dulu soal yang dipilih.', 'galat');
    }
    ke('?' . http_build_query(['p' => 'soal'] + array_intersect_key($_GET, array_flip(['mapel', 'kelas', 'q', 'tipe', 'milik']))));
}

$mapel = (string)get('mapel');
$kelas = (string)get('kelas');
$cari = (string)get('q');
$tipe = (string)get('tipe');
$milik = (string)get('milik', 'saya');
$w = [$milik === 'semua' ? '(s.guru_id = ? OR s.berbagi = 1)' : 's.guru_id = ?'];
$par = [uid()];
if ($mapel !== '') { $w[] = 's.mapel = ?'; $par[] = $mapel; }
if ($kelas !== '') { $w[] = 's.kelas = ?'; $par[] = $kelas; }
if ($tipe !== '') { $w[] = 's.tipe = ?'; $par[] = $tipe; }
if ($cari !== '') { $w[] = '(s.pertanyaan LIKE ? OR s.topik LIKE ?)'; array_push($par, "%$cari%", "%$cari%"); }
$daftar = semua('SELECT s.*, g.nama guru FROM guru_soal s LEFT JOIN guru_akun g ON g.id = s.guru_id WHERE ' . implode(' AND ', $w) . ' ORDER BY s.id DESC LIMIT 300', $par);
$mapel_list = q('SELECT DISTINCT mapel FROM guru_soal WHERE guru_id = ? OR berbagi = 1 ORDER BY mapel', [uid()])->fetchAll(PDO::FETCH_COLUMN);
$paket = semua("SELECT id, judul, diubah FROM guru_dokumen WHERE guru_id = ? AND jenis = 'paket' ORDER BY diubah DESC LIMIT 5", [uid()]);

kepala('Bank soal', 'soal');
judul_baris('Bank soal', '<a class="tombol" href="?p=soal_manual">＋ Manual</a> <a class="tombol utama" href="?p=soal_ai">' . ikon('ai') . ' Buat dengan AI</a>');
?>
<form class="cari" method="get">
  <input type="hidden" name="p" value="soal">
  <input type="search" name="q" value="<?= h($cari) ?>" placeholder="Cari pertanyaan atau topik">
  <select name="mapel" onchange="this.form.submit()"><option value="">Semua mapel</option><?= pilihan(array_combine($mapel_list, $mapel_list) ?: [], $mapel) ?></select>
  <select name="kelas" onchange="this.form.submit()"><option value="">Semua kelas</option><?= pilihan(['7' => 'Kelas 7', '8' => 'Kelas 8', '9' => 'Kelas 9'], $kelas) ?></select>
  <select name="tipe" onchange="this.form.submit()"><option value="">Semua bentuk</option><?= pilihan(TIPE_SOAL, $tipe) ?></select>
  <select name="milik" onchange="this.form.submit()"><?= pilihan(['saya' => 'Soal saya', 'semua' => '+ dibagikan guru lain'], $milik) ?></select>
</form>
<?php if ($paket): ?>
<p class="kecil">Paket soal terakhir: <?php foreach ($paket as $pk): ?><a class="lencana" href="?p=paket_cetak&amp;id=<?= (int)$pk['id'] ?>"><?= h($pk['judul']) ?></a> <?php endforeach; ?> · <a href="?p=perangkat&amp;jenis=paket">semua →</a></p>
<?php endif; ?>

<form method="post" id="form-bank">
  <?= input_token() ?>
  <div class="kartu lengket aksi-bank">
    <label class="centang"><input type="checkbox" id="pilih-semua-soal"> Pilih semua (<span id="jml-soal">0</span>/<?= count($daftar) ?>)</label>
    <span>
      <button name="aksi" value="paket" class="utama kecil">📄 Jadikan paket soal</button>
      <button name="aksi" value="hapus" class="kecil bahaya" onclick="return confirm('Hapus soal yang dicentang?')">Hapus</button>
    </span>
  </div>
  <ul class="daftar kartu bank-soal">
  <?php if (!$daftar): ?><li class="redup">Belum ada soal. Mulai dengan <a href="?p=soal_ai">✨ Buat dengan AI</a>.</li><?php endif; ?>
  <?php foreach ($daftar as $i => $s): $opsi = json_decode((string)$s['opsi'], true) ?: []; ?>
    <li>
      <label class="soal-baris">
        <?php if ((int)$s['guru_id'] === uid()): ?><input type="checkbox" name="pilih[]" value="<?= (int)$s['id'] ?>"><?php else: ?><input type="checkbox" name="pilih[]" value="<?= (int)$s['id'] ?>"><?php endif; ?>
        <span class="soal-isi">
          <small class="meta"><?= h($s['mapel']) ?><?= $s['kelas'] ? ' · Kls ' . h($s['kelas']) : '' ?> · <?= h(TIPE_SOAL[$s['tipe']] ?? $s['tipe']) ?> · <?= h(TINGKAT_SOAL[$s['tingkat']] ?? $s['tingkat']) ?><?= $s['level'] ? ' · ' . h($s['level']) : '' ?><?= $s['topik'] ? ' · ' . h($s['topik']) : '' ?><?= (int)$s['guru_id'] !== uid() ? ' · oleh ' . h($s['guru']) : '' ?><?= $s['berbagi'] && (int)$s['guru_id'] === uid() ? ' · dibagikan' : '' ?></small>
          <span class="tanya"><?= nl2br(h($s['pertanyaan'])) ?></span>
          <?php if ($opsi): ?><ol class="opsi" type="A"><?php foreach ($opsi as $hk => $ov): ?><li class="<?= strtoupper(trim((string)$s['kunci'])) === $hk ? 'benar' : '' ?>"><?= h($ov) ?></li><?php endforeach; ?></ol>
          <?php elseif ($s['kunci']): ?><small class="kunci">Kunci: <?= h(mb_strimwidth($s['kunci'], 0, 160, '…')) ?></small><?php endif; ?>
        </span>
      </label>
      <?php if ((int)$s['guru_id'] === uid()): ?><a class="ubah-soal kecil" href="?p=soal_form&amp;id=<?= (int)$s['id'] ?>">Ubah</a><?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ul>
</form>
<?php
kaki();

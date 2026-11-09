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
    } elseif (post('aksi') === 'ke_paket' && $ids) {
        $pid = (int)post('paket_id');
        $d = satu("SELECT * FROM guru_dokumen WHERE id = ? AND guru_id = ? AND jenis = 'paket'", [$pid, uid()]);
        if (!$d) { pesan('Pilih paket tujuannya dulu.', 'galat'); ke('?p=soal'); }
        $milik = array_map('intval', q('SELECT id FROM guru_soal WHERE guru_id = ? AND id IN (' . implode(',', $ids) . ')', [uid()])->fetchAll(PDO::FETCH_COLUMN));
        $isi = json_decode((string)$d['isi'], true) ?: [];
        $isi['ids'] = array_values(array_unique(array_merge(array_map('intval', $isi['ids'] ?? []), $milik)));
        q('UPDATE guru_dokumen SET isi = ?, diubah = ? WHERE id = ?', [json_encode($isi, JSON_UNESCAPED_UNICODE), sekarang(), $pid]);
        pesan(count($milik) . ' soal ditambahkan ke paket "' . h($d['judul']) . '".');
        ke('?p=paket_cetak&id=' . $pid);
    } else {
        pesan('Centang dulu soal yang dipilih.', 'galat');
    }
    ke('?' . http_build_query(['p' => 'soal'] + array_intersect_key($_GET, array_flip(['mapel', 'kelas', 'q', 'tipe']))));
}

$mapel = (string)get('mapel');
$kelas = (string)get('kelas');
$cari = (string)get('q');
$tipe = (string)get('tipe');
$dipaket = soal_di_paket();
$w = ['s.guru_id = ?'];
$par = [uid()];
if ($dipaket) $w[] = 's.id NOT IN (' . implode(',', $dipaket) . ')';
if ($mapel !== '') { $w[] = 's.mapel = ?'; $par[] = $mapel; }
if ($kelas !== '') { $w[] = 's.kelas = ?'; $par[] = $kelas; }
if ($tipe !== '') { $w[] = 's.tipe = ?'; $par[] = $tipe; }
if ($cari !== '') { $w[] = '(s.pertanyaan LIKE ? OR s.topik LIKE ?)'; array_push($par, "%$cari%", "%$cari%"); }
$daftar = semua('SELECT s.* FROM guru_soal s WHERE ' . implode(' AND ', $w) . ' ORDER BY s.id DESC LIMIT 300', $par);
$mapel_list = q('SELECT DISTINCT mapel FROM guru_soal WHERE guru_id = ? ORDER BY mapel', [uid()])->fetchAll(PDO::FETCH_COLUMN);
$paket = semua("SELECT id, judul, diubah FROM guru_dokumen WHERE guru_id = ? AND jenis = 'paket' ORDER BY diubah DESC", [uid()]);

kepala('Bank soal', 'soal');
judul_baris('Bank soal', '<a class="tombol" href="?p=soal_manual">＋ Manual</a> <a class="tombol utama" href="?p=soal_ai">' . ikon('ai') . ' Buat dengan AI</a>');
?>
<form class="cari" method="get">
  <input type="hidden" name="p" value="soal">
  <input type="search" name="q" value="<?= h($cari) ?>" placeholder="Cari pertanyaan atau topik">
  <select name="mapel" onchange="this.form.submit()"><option value="">Semua mapel</option><?= pilihan(array_combine($mapel_list, $mapel_list) ?: [], $mapel) ?></select>
  <select name="kelas" onchange="this.form.submit()"><option value="">Semua kelas</option><?= pilihan(['7' => 'Kelas 7', '8' => 'Kelas 8', '9' => 'Kelas 9'], $kelas) ?></select>
  <select name="tipe" onchange="this.form.submit()"><option value="">Semua bentuk</option><?= pilihan(TIPE_SOAL, $tipe) ?></select>
</form>
<?php if ($paket): ?>
<div class="kartu info-paket">
  <p class="kecil" style="margin:0 0 .3rem"><b>Paket soalmu</b> — soal yang sudah masuk paket tidak tampil di sini. Untuk mengubahnya, buka paketnya:</p>
  <?php foreach (array_slice($paket, 0, 8) as $pk): ?><a class="lencana" href="?p=paket_cetak&amp;id=<?= (int)$pk['id'] ?>"><?= h($pk['judul']) ?></a> <?php endforeach; ?>
  <?php if (count($paket) > 8): ?><a class="kecil" href="?p=perangkat&amp;jenis=paket">semua (<?= count($paket) ?>) →</a><?php endif; ?>
</div>
<?php endif; ?>

<form method="post" id="form-bank">
  <?= input_token() ?>
  <div class="kartu lengket aksi-bank">
    <label class="centang"><input type="checkbox" id="pilih-semua-soal"> Pilih semua (<span id="jml-soal">0</span>/<?= count($daftar) ?>)</label>
    <span>
      <button name="aksi" value="paket" class="utama kecil">📄 Jadikan paket soal</button>
      <?php if ($paket): ?><select name="paket_id" class="kecil" aria-label="Paket tujuan"><option value="">Tambah ke paket…</option><?= pilihan($paket, '', 'id', 'judul') ?></select><button name="aksi" value="ke_paket" class="kecil">＋ Masukkan</button><?php endif; ?>
      <button name="aksi" value="hapus" class="kecil bahaya" onclick="return confirm('Hapus soal yang dicentang?')">Hapus</button>
    </span>
  </div>
  <ul class="daftar kartu bank-soal">
  <?php if (!$daftar): ?><li class="redup"><?= $dipaket ? 'Semua soalmu sudah masuk paket. Buat soal baru dengan' : 'Belum ada soal. Mulai dengan' ?> <a href="?p=soal_ai">✨ Buat dengan AI</a> atau <a href="?p=soal_manual">＋ Manual</a>.</li><?php endif; ?>
  <?php foreach ($daftar as $i => $s): $opsi = json_decode((string)$s['opsi'], true) ?: []; ?>
    <li>
      <label class="soal-baris">
        <input type="checkbox" name="pilih[]" value="<?= (int)$s['id'] ?>">
        <span class="soal-isi">
          <small class="meta"><?= h($s['mapel']) ?><?= $s['kelas'] ? ' · Kls ' . h($s['kelas']) : '' ?> · <?= h(TIPE_SOAL[$s['tipe']] ?? $s['tipe']) ?> · <?= h(TINGKAT_SOAL[$s['tingkat']] ?? $s['tingkat']) ?><?= $s['level'] ? ' · ' . h($s['level']) : '' ?><?= $s['topik'] ? ' · ' . h($s['topik']) : '' ?></small>
          <span class="tanya"><?= nl2br(h($s['pertanyaan'])) ?></span>
          <?php if ($opsi): ?><ol class="opsi" type="A"><?php foreach ($opsi as $hk => $ov): ?><li class="<?= strtoupper(trim((string)$s['kunci'])) === $hk ? 'benar' : '' ?>"><?= h($ov) ?></li><?php endforeach; ?></ol>
          <?php elseif ($s['kunci']): ?><small class="kunci">Kunci: <?= h(mb_strimwidth($s['kunci'], 0, 160, '…')) ?></small><?php endif; ?>
        </span>
      </label>
      <a class="ubah-soal kecil" href="?p=soal_form&amp;id=<?= (int)$s['id'] ?>">Ubah</a>
    </li>
  <?php endforeach; ?>
  </ul>
</form>
<?php
kaki();

<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = array_map('intval', (array)($_POST['pilih'] ?? []));
    if (post('aksi') === 'hapus' && $ids) {
        $sb = sampah_baris('guru_soal', 'guru_id = ? AND id IN (' . implode(',', $ids) . ')', [uid()]);
        sampah_simpan('soal', count($sb['baris']) === 1 ? mb_substr((string)$sb['baris'][0]['pertanyaan'], 0, 80) : count($sb['baris']) . ' soal', [$sb]);
        $n = q('DELETE FROM guru_soal WHERE guru_id = ? AND id IN (' . implode(',', $ids) . ')', [uid()])->rowCount();
        pesan($n . ' soal dipindah ke Sampah.');
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
$total = (int)nilai('SELECT COUNT(*) FROM guru_soal s WHERE ' . implode(' AND ', $w), $par);
$per = max(20, min(500, (int)get('n', 20)));
$daftar = semua('SELECT s.* FROM guru_soal s WHERE ' . implode(' AND ', $w) . ' ORDER BY s.id DESC LIMIT ' . $per, $par);
$n_filter = count(array_filter([$mapel, $kelas, $tipe], fn($x) => $x !== ''));
$mapel_list = q('SELECT DISTINCT mapel FROM guru_soal WHERE guru_id = ? ORDER BY mapel', [uid()])->fetchAll(PDO::FETCH_COLUMN);
$paket = semua("SELECT id, judul, diubah FROM guru_dokumen WHERE guru_id = ? AND jenis = 'paket' ORDER BY diubah DESC", [uid()]);

kepala('Soal', 'perangkat');
judul_baris('Perangkat ajar', '<a class="tombol kecil" href="?p=soal_tempel">' . ikon('tempel') . ' Tempel</a> <a class="tombol kecil" href="?p=soal_manual">＋ Manual</a> <a class="tombol kecil utama" href="?p=soal_ai">' . ikon('ai') . ' Soal AI</a>');
tab_perangkat('soal');
?>
<form class="cari-soal" method="get">
  <input type="hidden" name="p" value="soal">
  <div class="cari-besar" style="margin:0"><?= ikon('cari') ?><input type="search" name="q" value="<?= h($cari) ?>" placeholder="Cari pertanyaan atau topik" aria-label="Cari soal"></div>
  <details class="saring" <?= $n_filter ? 'open' : '' ?>>
    <summary class="tombol kecil">Filter<?= $n_filter ? ' (' . $n_filter . ')' : '' ?></summary>
    <div class="saring-isi">
      <select name="mapel" onchange="this.form.submit()"><option value="">Semua mapel</option><?= pilihan(array_combine($mapel_list, $mapel_list) ?: [], $mapel) ?></select>
      <select name="kelas" onchange="this.form.submit()"><option value="">Semua kelas</option><?= pilihan(['7' => 'Kelas 7', '8' => 'Kelas 8', '9' => 'Kelas 9'], $kelas) ?></select>
      <select name="tipe" onchange="this.form.submit()"><option value="">Semua bentuk</option><?= pilihan(TIPE_SOAL, $tipe) ?></select>
      <?php if ($n_filter): ?><a class="kecil" href="?p=soal">Hapus filter</a><?php endif; ?>
    </div>
  </details>
</form>
<?php if ($paket): ?><p class="kecil redup" style="margin:.2rem 0 .6rem">Soal yang sudah masuk paket tidak tampil di sini, lihat <a href="?p=perangkat&amp;jenis=paket">Paket soal (<?= count($paket) ?>)</a>.</p><?php endif; ?>

<form method="post" id="form-bank">
  <?= input_token() ?>
  <div class="pilih-semua-baris"><label class="centang"><input type="checkbox" id="pilih-semua-soal"> Pilih semua yang tampil</label><small class="redup"><?= count($daftar) ?> dari <?= $total ?> soal</small></div>
  <ul class="daftar kartu bank-soal ringkas">
  <?php if (!$daftar): ?><li class="redup"><?= $dipaket ? 'Semua soalmu sudah masuk paket. Buat soal baru dengan' : 'Belum ada soal. Mulai dengan' ?> <a href="?p=soal_ai">Soal AI</a> atau <a href="?p=soal_manual">＋ Manual</a>.</li><?php endif; ?>
  <?php foreach ($daftar as $i => $s): $opsi = json_decode((string)$s['opsi'], true) ?: []; $go = gambar_opsi($s['gambar_opsi'] ?? null);
      $kunci = strtoupper(trim((string)$s['kunci'])); ?>
    <li>
      <label class="soal-baris">
        <input type="checkbox" name="pilih[]" value="<?= (int)$s['id'] ?>">
        <span class="soal-isi">
          <span class="tanya potong"><?= h($s['pertanyaan']) ?></span>
          <small class="meta"><?= h(TIPE_SOAL[$s['tipe']] ?? $s['tipe']) ?><?= $s['kelas'] ? ' · Kls ' . h($s['kelas']) : '' ?> · <?= h(TINGKAT_SOAL[$s['tingkat']] ?? $s['tingkat']) ?><?= $s['level'] ? ' · ' . h($s['level']) : '' ?><?= $s['topik'] ? ' · ' . h($s['topik']) : '' ?><?= $opsi && isset($opsi[$kunci]) ? ' · kunci ' . h($kunci) : '' ?></small>
        </span>
      </label>
      <details class="soal-detil"><summary>lihat lengkap</summary>
        <div class="tanya"><?= nl2br(h($s['pertanyaan'])) ?></div>
        <?= gambar_html($s['gambar'] ?? null, 'gambar-soal kecil') ?>
        <?php if ($opsi): ?><ol class="opsi" type="A"><?php foreach ($opsi as $hk => $ov): ?><li class="<?= $kunci === $hk ? 'benar' : '' ?>"><?= h($ov) ?><?= gambar_html($go[$hk] ?? null, 'gambar-opsi') ?></li><?php endforeach; ?></ol>
        <?php elseif ($s['kunci']): ?><small class="kunci">Kunci: <?= h(mb_strimwidth($s['kunci'], 0, 400, '…')) ?></small><?php endif; ?>
        <a class="kecil" href="?p=soal_form&amp;id=<?= (int)$s['id'] ?>">Ubah soal</a>
      </details>
    </li>
  <?php endforeach; ?>
  </ul>
  <?php if ($total > count($daftar)): ?><a class="tombol lebar" href="?<?= h(http_build_query(['p' => 'soal', 'n' => $per + 20] + array_filter(['q' => $cari, 'mapel' => $mapel, 'kelas' => $kelas, 'tipe' => $tipe], fn($x) => $x !== ''))) ?>">Muat lagi (<?= $total - count($daftar) ?> soal lagi)</a><?php endif; ?>
  <div class="bar-aksi" id="bar-aksi" hidden>
    <b><span id="jml-soal">0</span> dipilih</b>
    <button name="aksi" value="paket" class="kecil utama">Jadikan paket</button>
    <?php if ($paket): ?><select name="paket_id" class="kecil" aria-label="Paket tujuan"><option value="">Ke paket…</option><?= pilihan($paket, '', 'id', 'judul') ?></select><button name="aksi" value="ke_paket" class="kecil">Masukkan</button><?php endif; ?>
    <button name="aksi" value="hapus" class="kecil bahaya" onclick="return confirm('Hapus soal yang dicentang?\nBisa dikembalikan dari Sampah (Lainnya → Sampah) selama 30 hari.')">Hapus</button>
  </div>
</form>
<?php
kaki();

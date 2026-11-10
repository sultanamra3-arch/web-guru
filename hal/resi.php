<?php
/* Halaman resi (tanpa login): status tugas/ujian yang berubah sendiri. ?p=resi&k=KODE, alamat pendek /r/?k=KODE */
declare(strict_types=1);

$r = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)get('k')));
$d = $r !== '' ? resi_data($r) : null;
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');
$sig = $d ? substr(hash('sha256', json_encode($d)), 0, 16) : '';
if (get('sig')) { header('Content-Type: application/json'); echo json_encode(['sig' => $sig]); exit; }

kepala($d ? (!empty($d['pdf']) ? $d['pdf'] . ' - ' . $d['judul'] . ' - ' . $d['nama'] : $d['judul_resi']) : 'Resi', '', true);
?>
<div class="siswa-wadah halaman-resi">
<?php if (!$d): ?>
  <header class="siswa-kop"><img src="aset/logo.png" alt=""><div><small><?= h((string)k('sekolah', '')) ?></small><b>Resi SiGuru</b></div></header>
  <form method="get" class="kartu formulir">
    <input type="hidden" name="p" value="resi">
    <?php if ($r !== ''): ?><div class="pesan galat">Resi tidak ditemukan. Mungkin kirimannya sudah dihapus guru, atau nomor resinya salah ketik.</div><?php endif; ?>
    <label>Nomor resi<input name="k" class="isian-kode" maxlength="12" autocomplete="off" autocapitalize="characters" required placeholder="mis. T7M2Q-X9K4R"></label>
    <button class="utama lebar">Cek status</button>
  </form>
<?php else: [$st, $warna] = $d['status']; ?>
  <div class="hanya-cetak kop-pdf-resi">
    <div><b><?= h((string)k('sekolah', '')) ?></b><br><?= h($d['pdf'] ?? $d['judul_resi']) ?> · SiGuru</div>
    <div style="text-align:right">No. resi<br><b><?= h(resi_teks($r)) ?></b></div>
  </div>
  <div class="kartu resi-kartu">
    <div class="resi-kop tak-cetak">
      <small><?= h((string)k('sekolah', '')) ?> · SiGuru</small>
      <b><?= h($d['judul_resi']) ?></b>
    </div>
    <div class="resi-info">
      <div><small><?= $d['jenis'] === 'tugas' ? 'Tugas' : ($d['jenis'] === 'latihan' ? 'Latihan' : 'Ujian') ?></small><b><?= h($d['judul']) ?></b></div>
      <div><small>No. resi</small><b class="resi-no"><?= h(resi_teks($r)) ?></b></div>
      <div><small><?= $d['anggota'] ? h($d['nama']) . ' · Kelas ' . h($d['kelas']) : 'Nama' ?></small>
        <span><?= $d['anggota'] ? h(implode(', ', $d['anggota'])) : h($d['nama']) . ' · ' . h($d['kelas']) ?></span></div>
      <div><small>Status</small><span class="lencana resi-<?= h($warna) ?>"><?= h($st) ?></span></div>
    </div>
    <div class="resi-live tak-cetak" id="resi-live" data-k="<?= h($r) ?>" data-sig="<?= h($sig) ?>"><span class="titik hidup">●</span> Live · diperbarui <span id="resi-jam"><?= date('H:i') ?></span></div>
    <ol class="resi-langkah">
      <?php foreach ($d['langkah'] as [$s, $judul, $ket]): ?>
      <li class="rl-<?= h($s) ?>"><span class="rl-titik"><?= ['ok' => '✓', 'tunggu' => '…', 'ulang' => '↩', 'kosong' => ''][$s] ?? '' ?></span>
        <div><?= h($judul) ?><?php if ($ket !== ''): ?><small><?= h($ket) ?></small><?php endif; ?></div></li>
      <?php endforeach; ?>
    </ol>
    <?php if ($d['nilai'] !== null): ?>
    <div class="resi-nilai">
      <div class="resi-bulat"><b><?= h($d['nilai']) ?></b><small>nilai</small></div>
      <div><?php if (!empty($d['rincian'])): ?><small><?= h(trim($d['rincian'], ' ·')) ?></small><?php endif; ?>
        <?php if (trim($d['komentar']) !== ''): ?><small>Komentar guru</small><div><?= nl2br(h($d['komentar'])) ?></div><?php endif; ?></div>
    </div>
    <?php elseif (trim($d['komentar']) !== ''): ?>
    <div class="pesan galat resi-komentar"><b>Komentar guru:</b> <?= nl2br(h($d['komentar'])) ?></div>
    <?php endif; ?>
    <?php if ($d['catatan'] !== ''): ?><p class="resi-catatan"><?= h($d['catatan']) ?></p><?php endif; ?>
    <?php if (!empty($d['hasil'])): $hs = $d['hasil']; ?>
    <div class="resi-hasil">
      <?php if (trim($hs['petunjuk']) !== '' || $hs['lampiran']): ?><h4>Soal / petunjuk</h4>
        <?php if (trim($hs['petunjuk']) !== ''): ?><div class="resi-teks"><?= nl2br(h($hs['petunjuk'])) ?></div><?php endif; ?>
        <?= gambar_html($hs['lampiran'], 'gambar-soal') ?><?php endif; ?>
      <h4><?= $d['anggota'] ? 'Jawaban kelompok' : 'Jawabanmu' ?> <small>dikirim <?= h($hs['dikirim']) ?></small></h4>
      <?php if ($hs['foto']): ?><div class="resi-foto"><?php foreach ($hs['foto'] as $i => $fu): ?><img src="<?= h($fu) ?>" alt="foto <?= $i + 1 ?>" loading="lazy"><?php endforeach; ?></div>
      <?php elseif ($hs['foto_hapus']): ?><p class="kecil redup">Foto sudah dihapus otomatis (<?= TUGAS_HAPUS_FOTO_HARI ?> hari setelah nilai dikirim).</p><?php endif; ?>
      <?php if (trim($hs['teks']) !== ''): ?><div class="resi-teks"><?= nl2br(h($hs['teks'])) ?></div><?php endif; ?>
      <?php if ($hs['bahas_buka']): ?>
        <div class="resi-bahas"><h4>Pembahasan dari guru</h4>
          <?php if (trim($hs['bahas']) !== ''): ?><div><?= nl2br(h($hs['bahas'])) ?></div><?php endif; ?>
          <?= gambar_html($hs['bahas_gambar'], 'gambar-soal') ?></div>
      <?php elseif ($hs['bahas_ada']): ?><p class="resi-catatan tak-cetak">Pembahasan dibuka setelah tugas ditutup guru.</p><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($d['pembahasan_html'])): ?><div class="resi-hasil"><h4>Pembahasan</h4><?= $d['pembahasan_html'] ?></div><?php endif; ?>
    <?php if (!empty($d['pdf'])): ?>
    <div class="resi-pdf tak-cetak"><button type="button" class="utama lebar" onclick="window.print()"><?= ikon('unduh') ?> Simpan PDF <?= h(mb_strtolower($d['pdf'])) ?></button>
      <small class="redup">Di jendela yang muncul, pilih <b>Simpan sebagai PDF</b>, lalu tekan Simpan.</small></div>
    <?php endif; ?>
    <?php if ($d['tombol']): ?><a class="tombol utama lebar tak-cetak" href="<?= h($d['tombol'][1]) ?>"><?= h($d['tombol'][0]) ?></a><?php endif; ?>
    <p class="resi-kaki"><?= h($d['guru']) ?><?= $d['mapel'] !== '' ? ' · ' . h($d['mapel']) : '' ?><br>Link pribadi untuk <?= $d['anggota'] ? 'kelompok ini' : 'siswa ini' ?>. Jangan dibagikan ke orang lain.</p>
  </div>
<?php endif; ?>
</div>
<?php if ($d): ?>
<script>
(function () {
  var L = document.getElementById('resi-live'), jam = document.getElementById('resi-jam');
  function cek() {
    if (document.hidden) return;
    fetch('?p=resi&k=' + encodeURIComponent(L.dataset.k) + '&sig=1', { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (r) {
      if (r.sig && r.sig !== L.dataset.sig) { location.reload(); return; }
      var d = new Date(); jam.textContent = ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
    }).catch(function () {});
  }
  setInterval(cek, 30000);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) cek(); });
})();
</script>
<?php endif;
kaki(true);

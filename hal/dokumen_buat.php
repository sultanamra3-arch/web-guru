<?php
declare(strict_types=1);

$jenis = (string)get('jenis', 'modul');
if (!in_array($jenis, ['modul', 'presentasi'], true)) $jenis = 'modul';
$judul = ['modul' => '✨ Buat modul ajar', 'lkpd' => '✨ Buat LKPD', 'presentasi' => '✨ Buat presentasi'][$jenis];

kepala(strip_tags($judul), 'perangkat');
judul_baris($judul, '<a class="tombol" href="?p=perangkat">‹ Perangkat</a>');
?>
<?php if ($jenis === 'modul'): ?>
<p class="redup kecil">Disusun mengikuti struktur Perencanaan Pembelajaran Mendalam (Kurikulum Merdeka): identitas, identifikasi, desain, pengalaman belajar (memahami–mengaplikasi–merefleksi), asesmen & rubrik. Tetap periksa dan sesuaikan dengan CP resmi.</p>
<?php endif; ?>
<form class="formulir" id="form-dokumen-ai" data-token="<?= h(token()) ?>" data-jenis="<?= h($jenis) ?>">
  <fieldset><legend>Data</legend>
    <div class="baris2">
      <label>Mata pelajaran<input name="mapel" list="dl-mapel" required value="<?= h(mapel_saya()[0] ?? '') ?>"></label>
      <label>Kelas<select name="kelas"><?= pilihan(['7' => 'Kelas 7', '8' => 'Kelas 8', '9' => 'Kelas 9'], '8') ?></select></label>
    </div>
    <label>Materi / topik<input name="topik" required placeholder="mis. Teks prosedur"></label>
    <?php if ($jenis === 'presentasi'): ?>
      <label>Jumlah slide<input type="number" name="jumlah" min="4" max="20" value="10"></label>
    <?php else: ?>
      <label>Alokasi waktu<input name="alokasi" value="<?= $jenis === 'lkpd' ? '1 kali kegiatan (±40 menit)' : '2 JP × 40 menit, 1 pertemuan' ?>"></label>
      <?php if ($jenis === 'modul'): ?>
      <label>Model pembelajaran <small>(opsional)</small>
        <select name="model"><option value="">Pilihkan yang paling cocok</option><?= pilihan(array_combine($m = ['Problem Based Learning', 'Project Based Learning', 'Inkuiri', 'Discovery Learning', 'Cooperative Learning', 'Pembelajaran berdiferensiasi'], $m), '') ?></select></label>
      <?php endif; ?>
    <?php endif; ?>
    <details><summary class="kecil">Tambahan (opsional)</summary>
      <?php if ($jenis === 'modul'): ?>
        <label>Teks Capaian Pembelajaran resmi <small>(tempel bila ada)</small><textarea name="cp" rows="3"></textarea></label>
        <label>Kondisi kelas<input name="kondisi" placeholder="mis. 28 siswa, 3 perlu pendampingan membaca, ada proyektor"></label>
      <?php else: ?>
        <label>Tempel teks materi<textarea name="bahan" rows="4"></textarea></label>
      <?php endif; ?>
      <div class="dua-tombol">
        <label class="tombol">📷 Kamera<input type="file" class="ambil-foto" accept="image/*" capture="environment" hidden></label>
        <label class="tombol">🖼 Galeri<input type="file" class="ambil-foto" accept="image/*" multiple hidden></label>
      </div>
      <div class="pratinjau-foto kiri"></div>
      <label>Permintaan khusus<input name="catatan" placeholder="mis. sertakan kearifan lokal Indragiri Hilir"></label>
    </details>
  </fieldset>
  <button class="utama lebar" id="tombol-buat-dokumen"><?= $judul ?></button>
  <div id="status-ai" class="kecil"></div>
</form>
<datalist id="dl-mapel"><?php foreach (mapel_saya() as $m): ?><option value="<?= h($m) ?>"><?php endforeach; ?></datalist>
<?php
kaki();

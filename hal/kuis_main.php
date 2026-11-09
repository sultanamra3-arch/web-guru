<?php
/* Layar kuis untuk proyektor (dikendalikan guru). */
declare(strict_types=1);

$k = kuis_ambil((int)get('id'));
if (!$k) ke('?p=kuis');
$url = kuis_url($k);
kepala($k['judul'], 'kelas');
?>
<div class="judul-baris tak-cetak"><h1 style="font-size:1.2rem"><?= h($k['judul']) ?></h1><a class="tombol" href="?p=kuis">‹ Kuis</a></div>
<div id="panggung" class="panggung" data-id="<?= (int)$k['id'] ?>" data-url="<?= h($url) ?>" data-kode="<?= h($k['kode']) ?>">
  <div class="panggung-atas">
    <span>Kode: <b><?= h(implode(' ', str_split($k['kode'], 3))) ?></b></span>
    <span id="pg-info"></span>
    <button type="button" class="kecil" id="pg-penuh">⛶ Layar penuh</button>
  </div>
  <div id="pg-isi" class="panggung-isi"><p class="redup">Memuat…</p></div>
  <div class="panggung-bawah" id="pg-tombol"></div>
</div>
<p class="kecil redup">Tampilkan halaman ini di proyektor/TV. Siswa membuka <b><?= h(preg_replace('#^https?://#', '', preg_replace('#\?k=.*$#', '', $url))) ?></b> dan memasukkan kode, atau memindai QR. Waktu soal berjalan otomatis; jawaban tampil sendiri bila waktu habis atau semua sudah menjawab.</p>
<?= input_token() ?>
<?php
$GLOBALS['skrip_tambahan'][] = 'aset/lib/qrcode.js';
$GLOBALS['skrip_tambahan'][] = 'aset/kuis.js';
kaki();

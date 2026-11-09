<?php
declare(strict_types=1);

$u = ujian_ambil((int)get('id'));
if (!$u) ke('?p=ujian');
$id = (int)$u['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string)post('aksi');
    $kode = (string)post('kode');
    switch ($aksi) {
        case 'buka':
            q("UPDATE guru_ujian SET status = 'buka' WHERE id = ?", [$id]);
            pesan('Ujian dibuka. Bagikan kode/QR dan tampilkan token ke siswa.');
            break;
        case 'tutup':
            q("UPDATE guru_ujian SET status = 'tutup' WHERE id = ?", [$id]);
            ujian_akhiri_kedaluwarsa($u, true);
            pesan('Ujian ditutup. Siswa yang masih mengerjakan otomatis dikumpulkan.');
            break;
        case 'buka_ulang':
            // izinkan masuk dari perangkat lain / lanjut lagi setelah dikumpulkan
            q('UPDATE guru_ujian_peserta SET perangkat = NULL, selesai = NULL,
               batas = GREATEST(COALESCE(batas, NOW()), NOW() + INTERVAL 5 MINUTE) WHERE ujian_id = ? AND kode = ?', [$id, $kode]);
            pesan(h(nama_siswa($kode)) . ' boleh masuk lagi (perangkat apa pun, token tetap diperlukan).');
            break;
        case 'tambah_waktu':
            $m = max(1, min(120, (int)post('menit', 10)));
            q("UPDATE guru_ujian_peserta SET batas = batas + INTERVAL $m MINUTE WHERE ujian_id = ? AND kode = ? AND selesai IS NULL", [$id, $kode]);
            pesan('Waktu ' . h(nama_siswa($kode)) . " ditambah $m menit.");
            break;
        case 'akhiri':
            $p = satu('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ? AND selesai IS NULL', [$id, $kode]);
            if ($p) {
                q('UPDATE guru_ujian_peserta SET selesai = ? WHERE ujian_id = ? AND kode = ?', [sekarang(), $id, $kode]);
                ujian_simpan_hitung($u, $p);
                pesan('Ujian ' . h(nama_siswa($kode)) . ' dikumpulkan.');
            }
            break;
        case 'reset':
            q('DELETE FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ?', [$id, $kode]);
            pesan('Jawaban ' . h(nama_siswa($kode)) . ' dihapus; siswa bisa mengulang dari awal.');
            break;
        case 'hapus':
            if (!nilai('SELECT 1 FROM guru_ujian_peserta WHERE ujian_id = ? LIMIT 1', [$id])) {
                q('DELETE FROM guru_ujian WHERE id = ?', [$id]);
                pesan('Ujian dihapus.');
                ke('?p=ujian');
            }
            pesan('Ujian yang sudah dikerjakan tidak bisa dihapus.', 'galat');
            break;
    }
    ke('?p=ujian_lihat&id=' . $id);
}

ujian_akhiri_kedaluwarsa($u);
$url = ujian_url_siswa($u);
$jumlah_soal = count(ujian_soal($u));

kepala($u['judul'], 'kelas');
judul_baris(h($u['judul']), '<a class="tombol" href="?p=ujian">‹ Ujian</a>');
?>
<div class="kartu">
  <p style="margin-top:0">Kelas <b><?= h($u['kelas']) ?></b> · <?= $jumlah_soal ?> soal · <?= (int)$u['durasi'] ?> menit
    <?= $u['mulai'] ? ' · mulai ' . h(tgl(substr($u['mulai'], 0, 10)) . ' ' . substr($u['mulai'], 11, 5)) : '' ?>
    <?= $u['selesai'] ? ' · batas ' . h(tgl(substr($u['selesai'], 0, 10)) . ' ' . substr($u['selesai'], 11, 5)) : '' ?></p>
  <p><span class="lencana st-<?= h($u['status']) ?>"><?= h(ujian_status_teks($u)) ?></span></p>
  <div class="dua-tombol">
    <?php if ($u['status'] !== 'buka'): ?>
    <form method="post"><?= input_token() ?><input type="hidden" name="aksi" value="buka"><button class="utama lebar">▶ Buka ujian</button></form>
    <?php else: ?>
    <form method="post" onsubmit="return confirm('Tutup ujian? Siswa yang belum selesai akan dikumpulkan otomatis.')"><?= input_token() ?><input type="hidden" name="aksi" value="tutup"><button class="lebar bahaya">■ Tutup ujian</button></form>
    <?php endif; ?>
    <a class="tombol" href="?p=ujian_hasil&amp;id=<?= $id ?>">📊 Hasil & nilai</a>
  </div>
  <p class="kecil"><a href="?p=ujian_form&amp;id=<?= $id ?>">⚙ Pengaturan</a></p>
</div>

<div class="kartu layar-token" id="layar-token" data-id="<?= $id ?>" data-url="<?= h($url) ?>">
  <div class="token-kiri">
    <div class="kecil redup">Buka di HP</div>
    <div class="url-siswa"><?= h(preg_replace('#^https?://#', '', $url)) ?></div>
    <div class="kecil redup" style="margin-top:.4rem">Kode ujian</div>
    <div class="kode-ujian"><?= h($u['kode']) ?></div>
    <div class="kecil redup" style="margin-top:.4rem">Token</div>
    <div class="token-besar" id="token">····</div>
    <div class="kecil redup" id="token-sisa"></div>
  </div>
  <div class="token-qr" id="qr"></div>
  <button type="button" class="kecil" id="tombol-layar-penuh">⛶ Tampilkan di proyektor</button>
</div>

<div class="kartu">
  <h3 style="margin-top:0">Pantau siswa <small class="redup" id="pantau-waktu"></small></h3>
  <div class="statistik" id="pantau-ringkas"></div>
  <ul class="daftar pantau" id="pantau"></ul>
</div>
<?php if (!nilai('SELECT 1 FROM guru_ujian_peserta WHERE ujian_id = ? LIMIT 1', [$id])): ?>
<form method="post" onsubmit="return confirm('Hapus ujian ini?')"><?= input_token() ?><input type="hidden" name="aksi" value="hapus"><button class="kecil bahaya">Hapus ujian</button></form>
<?php endif; ?>
<template id="tpl-aksi">
  <form method="post" class="aksi-pantau">
    <?= input_token() ?><input type="hidden" name="kode">
    <button name="aksi" value="buka_ulang" class="kecil" title="Izinkan masuk dari HP lain / lanjut lagi">Buka ulang</button>
    <button name="aksi" value="tambah_waktu" class="kecil">+10 menit</button>
    <button name="aksi" value="akhiri" class="kecil" onclick="return confirm('Kumpulkan sekarang?')">Kumpulkan</button>
    <button name="aksi" value="reset" class="kecil bahaya" onclick="return confirm('Hapus semua jawaban siswa ini dan ulang dari awal?')">Ulang</button>
  </form>
</template>
<?php
$GLOBALS['skrip_tambahan'][] = 'aset/lib/qrcode.js';
$GLOBALS['skrip_tambahan'][] = 'aset/ujian_guru.js';
kaki();

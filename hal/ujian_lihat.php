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
            // pengaman: hitungan keluar direset, jumlah "pernah dikunci" dicatat
            q("UPDATE guru_ujian_peserta SET dikunci = dikunci + (alasan <=> 'pengaman'), keluar = IF(alasan <=> 'pengaman', 0, keluar), alasan = NULL,
               perangkat = NULL, selesai = NULL,
               batas = GREATEST(COALESCE(batas, NOW()), NOW() + INTERVAL 5 MINUTE) WHERE ujian_id = ? AND kode = ?", [$id, $kode]);
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
            // jawaban siswa ikut terhapus; nilai yang sudah dikirim ke Penilaian tetap ada
            db()->beginTransaction();
            q('DELETE FROM guru_ujian_peserta WHERE ujian_id = ?', [$id]);
            q('DELETE FROM guru_ujian WHERE id = ? AND guru_id = ?', [$id, uid()]);
            db()->commit();
            pesan('Ujian "' . h($u['judul']) . '" dihapus.' . ($u['penilaian_id'] ? ' Nilai yang sudah dikirim ke Penilaian tetap tersimpan.' : ''));
            ke('?p=ujian');
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
  <p style="margin-top:0"><?= ujian_latihan($u) ? '<span class="lencana">latihan</span> ' : '' ?>Kelas <b><?= h($u['kelas']) ?></b> · <?= $jumlah_soal ?> soal · <?= (int)$u['durasi'] ?> menit
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
  <p class="kecil"><a href="?p=ujian_form&amp;id=<?= $id ?>">⚙ Pengaturan</a>
    · <?= !ujian_latihan($u) ? ($u['pengaman'] ? '🛡 pengaman <b>aktif</b> (' . (int)$u['batas_keluar'] . '× keluar)' : '🛡 pengaman mati') : 'tanpa token, boleh diulang' ?>
    <?= $u['tampil_tertib'] ? ' · 📋 tata tertib' : '' ?><?= !ujian_latihan($u) && $u['tampil_hasil'] ? ' · hasil terlihat setelah ditutup' : '' ?></p>
</div>

<div class="kartu layar-token" id="layar-token" data-id="<?= $id ?>" data-url="<?= h($url) ?>">
  <div class="token-kiri">
    <div class="kecil redup">Buka di HP</div>
    <div class="url-siswa"><?= h(preg_replace('#^https?://#', '', $url)) ?></div>
    <div class="kecil redup" style="margin-top:.4rem">Kode ujian</div>
    <div class="kode-ujian"><?= h($u['kode']) ?></div>
    <?php if (!ujian_latihan($u)): ?>
    <div class="kecil redup" style="margin-top:.4rem">Token</div>
    <div class="token-besar" id="token">····</div>
    <div class="kecil redup" id="token-sisa"></div>
    <?php else: ?><div class="kecil redup" style="margin-top:.4rem">Latihan: siswa tidak perlu token</div><div id="token" hidden></div><div id="token-sisa" hidden></div><?php endif; ?>
  </div>
  <div class="token-qr" id="qr"></div>
  <div class="token-tombol">
    <button type="button" class="utama kecil" id="tombol-bagikan">📤 Bagikan</button>
    <button type="button" class="kecil" id="tombol-layar-penuh">⛶ Tampilkan di proyektor</button>
  </div>
</div>

<?php panel_bagikan([
    'id' => $id, 'jenis' => ujian_latihan($u) ? 'latihan' : 'ujian', 'judul' => $u['judul'], 'kelas' => $u['kelas'], 'mapel' => (string)$u['mapel'],
    'soal' => $jumlah_soal, 'durasi' => (int)$u['durasi'], 'kode' => $u['kode'], 'url' => $url,
    'mulai' => ujian_waktu_teks($u['mulai']), 'batas' => ujian_waktu_teks($u['selesai']), 'tertib' => (int)$u['tampil_tertib'], 'token-menit' => (int)$u['token_menit'],
]); ?>

<div class="kartu">
  <h3 style="margin-top:0">Pantau siswa <small class="redup" id="pantau-waktu"></small></h3>
  <div class="statistik" id="pantau-ringkas"></div>
  <ul class="daftar pantau" id="pantau"></ul>
</div>
<?php $n_peserta = (int)nilai('SELECT COUNT(*) FROM guru_ujian_peserta WHERE ujian_id = ? AND mulai IS NOT NULL', [$id]);
$tanya = $n_peserta ? "Hapus ujian ini? Jawaban $n_peserta siswa ikut terhapus" . ($u['penilaian_id'] ? ' (nilai yang sudah dikirim ke Penilaian tetap ada).' : '. Kirim dulu nilainya ke Penilaian bila perlu.') : 'Hapus ujian ini?'; ?>
<form method="post" style="margin-top:1rem" onsubmit="return confirm(<?= h(json_encode($tanya, JSON_UNESCAPED_UNICODE)) ?>)"><?= input_token() ?><input type="hidden" name="aksi" value="hapus"><button class="kecil bahaya">🗑 Hapus ujian</button></form>
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
$GLOBALS['skrip_tambahan'][] = 'aset/bagikan.js';
kaki();

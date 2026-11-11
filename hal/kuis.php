<?php
/* Kuis cepat: daftar & buat baru dari paket soal. */
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('aksi') === 'hapus') {
        $k = kuis_ambil((int)post('id'));
        if ($k) { sampah_simpan('kuis', $k['judul'], [sampah_baris('guru_kuis', 'id = ?', [$k['id']]), sampah_baris('guru_kuis_peserta', 'kuis_id = ?', [$k['id']])]);
            q('DELETE FROM guru_kuis_peserta WHERE kuis_id = ?', [$k['id']]); q('DELETE FROM guru_kuis WHERE id = ?', [$k['id']]); pesan('Kuis dipindah ke Sampah.'); }
        ke('?p=kuis');
    }
    $kelas = (string)post('kelas');
    $paket = satu("SELECT * FROM guru_dokumen WHERE id = ? AND guru_id = ? AND jenis = 'paket'", [(int)post('paket'), uid()]);
    if (!$paket || !kelas_valid($kelas)) { pesan('Pilih paket soal dan kelas.', 'galat'); ke('?p=kuis'); }
    $soal = kuis_soal_dari_paket((int)$paket['id']);
    if (!$soal) { pesan('Paket ini tidak punya soal pilihan ganda / benar-salah. Kuis cepat hanya memakai soal PG dan B/S.', 'galat'); ke('?p=kuis'); }
    if (post('acak')) shuffle($soal);
    $n = max(1, min(count($soal), (int)post('jumlah', count($soal))));
    $soal = array_slice($soal, 0, $n);
    q('INSERT INTO guru_kuis (guru_id, paket_id, kelas, tahun, judul, kode, soal, detik, mode, dibuat) VALUES (?,?,?,?,?,?,?,?,?,?)',
        [uid(), $paket['id'], $kelas, tahun_aktif(), mb_substr(trim((string)post('judul')) ?: $paket['judul'], 0, 200), kuis_kode_baru(),
         json_encode($soal, JSON_UNESCAPED_UNICODE), in_array((int)post('detik'), [10, 15, 20, 30, 45, 60, 90], true) ? (int)post('detik') : 20,
         post('mode') === 'bersama' ? 'bersama' : 'mandiri', sekarang()]);
    ke('?p=kuis_main&id=' . db()->lastInsertId());
}

$paket = semua("SELECT id, judul FROM guru_dokumen WHERE guru_id = ? AND jenis = 'paket' ORDER BY diubah DESC", [uid()]);
$daftar = semua('SELECT k.*, (SELECT COUNT(*) FROM guru_kuis_peserta p WHERE p.kuis_id = k.id) n FROM guru_kuis k WHERE k.guru_id = ? ORDER BY k.id DESC LIMIT 50', [uid()]);
$kl = kelas_saya();
kepala('Kuis cepat', 'kelas');
judul_baris('Kuis cepat');
?>
<p class="redup" style="margin-top:-.4rem">Soal tampil di proyektor/layar, siswa menjawab dari HP. Makin cepat dan benar, makin besar poinnya — lalu muncul peringkat. Cocok untuk pemanasan atau penutup pelajaran.</p>
<?php if (!$paket): ?>
  <div class="pesan">Belum ada paket soal. Buat soal lalu jadikan paket di <a href="?p=soal">Bank soal</a>.</div>
<?php else: ?>
<form method="post" class="kartu formulir">
  <?= input_token() ?>
  <fieldset class="pilih-mode">
    <legend>Cara main</legend>
    <label class="kartu-pilih"><input type="radio" name="mode" value="mandiri" checked><span><b>Mandiri</b><small>Siswa maju sendiri-sendiri tanpa menunggu teman; soal &amp; pilihan diacak per siswa; layar guru menampilkan balapan kemajuan</small></span></label>
    <label class="kartu-pilih"><input type="radio" name="mode" value="bersama"><span><b>Bersama</b><small>Soal tampil di proyektor, semua siswa menjawab soal yang sama bersamaan</small></span></label>
  </fieldset>
  <label>Paket soal <small>(yang dipakai hanya soal pilihan ganda & benar/salah)</small><select name="paket" required><?= pilihan($paket, '', 'id', 'judul') ?></select></label>
  <div class="baris2">
    <label>Kelas<select name="kelas" required><?= pilihan(array_combine($kl, $kl) ?: [], (string)get('kelas')) ?></select></label>
    <label>Waktu per soal<select name="detik"><?= pilihan(['10' => '10 detik', '15' => '15 detik', '20' => '20 detik', '30' => '30 detik', '45' => '45 detik', '60' => '60 detik', '90' => '90 detik'], '20') ?></select></label>
  </div>
  <div class="baris2">
    <label>Jumlah soal <small>(kosong = semua)</small><input type="number" name="jumlah" min="1" max="60" placeholder="semua"></label>
    <label>Judul <small>(opsional)</small><input name="judul" placeholder="mis. Kuis Algoritma"></label>
  </div>
  <label class="centang"><input type="checkbox" name="acak" value="1" checked> <span>Acak urutan soal <small class="redup">(mode mandiri selalu diacak per siswa)</small></span></label>
  <button class="utama lebar">▶ Buat kuis &amp; buka layar kuis</button>
</form>
<?php endif; ?>
<?php if ($daftar): ?>
<h3>Kuis sebelumnya</h3>
<ul class="daftar kartu">
  <?php foreach ($daftar as $k): ?>
  <li class="baris-kuis"><a href="?p=kuis_main&amp;id=<?= (int)$k['id'] ?>"><b><?= h($k['judul']) ?></b>
    <small>Kelas <?= h($k['kelas']) ?> · <?= count(kuis_soal($k)) ?> soal · <?= (int)$k['n'] ?> peserta · <?= kuis_mandiri($k) ? 'mandiri' : 'bersama' ?> · <?= ['lobi' => 'belum mulai', 'soal' => 'berlangsung', 'jawab' => 'berlangsung', 'jalan' => 'berlangsung', 'selesai' => 'selesai'][$k['status']] ?? $k['status'] ?> · <?= h(tgl(substr((string)$k['dibuat'], 0, 10))) ?></small></a>
    <form method="post" onsubmit="return confirm('Hapus kuis ini?\nBisa dikembalikan dari Sampah (Lainnya → Sampah) selama 30 hari.')"><?= input_token() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$k['id'] ?>"><button class="kecil bahaya" aria-label="Hapus">🗑</button></form></li>
  <?php endforeach; ?>
</ul>
<?php endif; ?>
<?php
kaki();

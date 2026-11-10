<?php
declare(strict_types=1);

$daftar = semua("SELECT u.*, (SELECT COUNT(*) FROM guru_ujian_peserta p WHERE p.ujian_id = u.id AND p.selesai IS NOT NULL) selesai_n,
    (SELECT COUNT(*) FROM guru_ujian_peserta p WHERE p.ujian_id = u.id AND p.mulai IS NOT NULL AND p.selesai IS NULL) jalan_n
    FROM guru_ujian u WHERE u.guru_id = ? ORDER BY u.id DESC LIMIT 100", [uid()]);
$paket = semua("SELECT id, judul FROM guru_dokumen WHERE guru_id = ? AND jenis = 'paket' ORDER BY id DESC LIMIT 50", [uid()]);

kepala('Ujian online', 'kelas');
judul_baris('Ujian online');
?>
<div class="kartu">
  <p style="margin-top:0">Siswa mengerjakan paket soal langsung di HP. Nilai pilihan ganda keluar otomatis, uraian dinilai AI lalu diperiksa guru.</p>
  <?php if ($paket): ?>
  <form method="get" class="formulir">
    <input type="hidden" name="p" value="ujian_form">
    <label>Buat ujian dari paket soal<select name="paket"><?= pilihan($paket, '', 'id', 'judul') ?></select></label>
    <button class="utama lebar">＋ Buat ujian online</button>
  </form>
  <?php else: ?>
  <p class="pesan">Buat dulu <b>paket soal</b> di menu Soal → Bank soal → pilih soal → Jadikan paket.</p>
  <a class="tombol lebar" href="?p=soal">Ke bank soal</a>
  <?php endif; ?>
</div>
<ul class="daftar kartu">
  <?php if (!$daftar): ?><li class="redup">Belum ada ujian online.</li><?php endif; ?>
  <?php foreach ($daftar as $u): ?>
  <li><a href="?p=ujian_lihat&amp;id=<?= (int)$u['id'] ?>"><b><?= h($u['judul']) ?></b>
    <small>Kelas <?= h($u['kelas']) ?> · <?= (int)$u['durasi'] ?> menit · kode <b><?= h($u['kode']) ?></b></small>
    <small class="kode"><?= !empty($u['tindak']) ? '<span class="lencana">' . h($u['tindak']) . '</span> ' : (ujian_latihan($u) ? '<span class="lencana">latihan</span> ' : '') ?><span class="lencana st-<?= h($u['status']) ?>"><?= h(ujian_status_teks($u)) ?></span>
      <?= (int)$u['jalan_n'] ? (int)$u['jalan_n'] . ' sedang mengerjakan · ' : '' ?><?= (int)$u['selesai_n'] ?> selesai</small></a></li>
  <?php endforeach; ?>
</ul>
<?php
kaki();

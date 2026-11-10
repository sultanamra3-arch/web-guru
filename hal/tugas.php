<?php
/* Daftar tugas online milik guru. */
declare(strict_types=1);

tugas_bersihkan_lama();
$daftar = semua("SELECT t.*, (SELECT COUNT(*) FROM guru_tugas_kirim k WHERE k.tugas_id = t.id AND k.dikirim IS NOT NULL) n_kirim,
                 (SELECT COUNT(*) FROM guru_tugas_kirim k WHERE k.tugas_id = t.id AND k.status = 'dinilai') n_nilai
                 FROM guru_tugas t WHERE t.guru_id = ? ORDER BY t.id DESC LIMIT 200", [uid()]);
$jumlah = [];
foreach (array_unique(array_column($daftar, 'kelas')) as $k) $jumlah[$k] = count(siswa_kelas((string)$k));

kepala('Tugas online', 'kelas');
judul_baris('Tugas online', '<a class="tombol utama" href="?p=tugas_form">＋ Tugas baru</a>');
?>
<p class="redup" style="margin-top:-.4rem">Siswa mengirim foto/jawaban tugas lewat HP (tanpa akun). Kamu memantau siapa yang sudah/belum kirim, lalu menilai.</p>
<ul class="daftar kartu">
  <?php if (!$daftar): ?><li class="redup">Belum ada tugas. Tekan <b>＋ Tugas baru</b>.</li><?php endif; ?>
  <?php foreach ($daftar as $t): $n = tugas_kelompok($t) ? count(tugas_daftar_kelompok($t)) : ($jumlah[$t['kelas']] ?? 0); ?>
  <li><a href="?p=tugas_lihat&amp;id=<?= (int)$t['id'] ?>"><b><?= h($t['judul']) ?></b>
    <small><?= tugas_kelompok($t) ? '<span class="lencana">kelompok</span> ' : '' ?>Kelas <?= h($t['kelas']) ?><?= $t['mapel'] ? ' · ' . h($t['mapel']) : '' ?> · <?= $t['batas'] ? 'batas ' . h(tgl(substr($t['batas'], 0, 10)) . ' ' . substr($t['batas'], 11, 5)) : 'tanpa batas' ?>
      · <b><?= (int)$t['n_kirim'] ?>/<?= $n ?></b> kirim · <?= (int)$t['n_nilai'] ?> dinilai
      <?= $t['status'] !== 'buka' ? ' · <span class="lencana st-tutup">ditutup</span>' : (tugas_lewat($t) ? ' · <span class="lencana st-draf">lewat batas</span>' : ' · <span class="lencana st-buka">dibuka</span>') ?></small></a></li>
  <?php endforeach; ?>
</ul>
<?php
kaki();

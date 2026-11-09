<?php
declare(strict_types=1);

$id = (int)get('id');
$mid = (string)get('m');
$p = satu('SELECT * FROM guru_penilaian WHERE id = ? AND guru_id = ?', [$id, uid()]);
$r = $p ? satu('SELECT * FROM guru_koreksi WHERE penilaian_id = ? AND member_id = ?', [$id, $mid]) : null;
if (!$r) ke('?p=koreksi&id=' . $id);
$s = ['member_name' => nama_siswa($mid)];
$d = json_decode((string)$r['detail'], true) ?: [];

kepala('Hasil koreksi', 'kelas');
judul_baris('Hasil koreksi', '<a class="tombol" href="?p=koreksi&amp;id=' . $id . '">‹ Nilai dari foto</a>');
?>
<div class="kartu">
  <p><b><?= h($s['member_name'] ?? $mid) ?></b> · <?= h($p['judul']) ?> · Kelas <?= h($p['kelas']) ?></p>
  <div class="statistik">
    <span><b><?= h(rtrim(rtrim((string)$r['nilai'], '0'), '.')) ?></b>nilai</span>
    <span><b><?= h((string)($d['skor'] ?? '')) ?>/<?= h((string)($d['maks'] ?? '')) ?></b>skor</span>
  </div>
  <?php if (!empty($d['komentar'])): ?><p>💬 <?= h($d['komentar']) ?></p><?php endif; ?>
  <p class="kecil redup">Dinilai <?= h(tgl(substr((string)$r['dibuat'], 0, 10))) ?> · <?= h((string)$r['oleh']) ?></p>
</div>
<div class="kartu">
  <table class="tabel-koreksi">
    <tr><th>No</th><th>Jawaban</th><th>Kunci</th><th>Skor</th></tr>
    <?php foreach ((array)($d['soal'] ?? []) as $x): $pg = in_array($x['jenis'], ['pg', 'bs'], true); ?>
    <tr class="<?= $x['skor'] >= $x['maks'] ? 'benar' : ($x['skor'] > 0 ? 'sebagian' : 'salah') ?>">
      <td><?= h($x['no']) ?></td>
      <td><?= $pg ? '<b>' . h($x['jawaban'] ?: '–') . '</b>' : '<small>' . nl2br(h($x['jawaban'])) . '</small>' ?>
        <?php if (!empty($x['catatan'])): ?><br><small class="redup"><?= h($x['catatan']) ?></small><?php endif; ?></td>
      <td><?= h($x['kunci'] ?? '') ?></td>
      <td><?= h((string)$x['skor']) ?>/<?= h((string)$x['maks']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php
kaki();

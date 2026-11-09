<?php
declare(strict_types=1);

$u = ujian_ambil((int)get('id'));
$kode = (string)get('s');
$p = $u ? satu('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ?', [$u['id'], $kode]) : null;
if (!$p) ke('?p=ujian');
$soal = ujian_soal($u);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nu = json_decode((string)$p['nilai_uraian'], true) ?: [];
    foreach ((array)($_POST['skor'] ?? []) as $i => $v) {
        $i = (int)$i;
        if (!isset($soal[$i]) || in_array($soal[$i]['tipe'], ['pg', 'bs'], true) || trim((string)$v) === '') continue;
        $sk = max(0.0, min((float)$soal[$i]['maks'], round((float)str_replace(',', '.', (string)$v) * 2) / 2));
        $lama = $nu[$i] ?? [];
        $nu[$i] = ['skor' => $sk, 'skor_ai' => $lama['skor_ai'] ?? null, 'catatan' => $lama['catatan'] ?? '', 'oleh' => 'guru'];
    }
    q('UPDATE guru_ujian_peserta SET nilai_uraian = ? WHERE ujian_id = ? AND kode = ?', [json_encode($nu, JSON_UNESCAPED_UNICODE), $u['id'], $kode]);
    $p['nilai_uraian'] = json_encode($nu);
    $h = ujian_simpan_hitung($u, $p);
    pesan('Skor disimpan. Nilai: ' . $h['nilai'] . ($u['penilaian_id'] ? ' — tekan "Perbarui nilai di Penilaian" di halaman hasil.' : ''));
    ke('?p=ujian_jawaban&id=' . $u['id'] . '&s=' . rawurlencode($kode));
}

$h = ujian_hitung($u, $p);
$jw = json_decode((string)$p['jawaban'], true) ?: [];
$nu = json_decode((string)$p['nilai_uraian'], true) ?: [];
$ur = json_decode((string)$p['urutan'], true) ?: [];

kepala('Jawaban ' . nama_siswa($kode), 'kelas');
judul_baris('Jawaban siswa', '<a class="tombol" href="?p=ujian_hasil&amp;id=' . (int)$u['id'] . '">‹ Hasil</a>');
?>
<div class="kartu">
  <p style="margin-top:0"><b><?= h(nama_siswa($kode)) ?></b> · <?= h($u['judul']) ?></p>
  <div class="statistik">
    <span><b><?= h((string)$h['nilai']) ?></b>nilai</span>
    <span><b><?= h(str_replace('.', ',', (string)$h['skor'])) ?>/<?= h((string)$h['maks']) ?></b>skor</span>
    <span><b><?= (int)$p['keluar'] ?>×</b>keluar layar</span>
    <span><b><?= h(substr((string)$p['mulai'], 11, 5)) ?>–<?= h(substr((string)$p['selesai'], 11, 5)) ?></b>waktu</span>
  </div>
  <p class="kecil redup">Nomor sesuai urutan di paket soal (siswa mungkin melihat urutan acak).</p>
</div>
<form method="post" class="kartu">
  <?= input_token() ?>
  <ol class="jawaban-ujian">
  <?php foreach ($soal as $i => $s): $a = (string)($jw[$i] ?? ''); $r = $h['rinci'][$i]; ?>
    <li class="<?= $r['skor'] >= $r['maks'] ? 'benar' : ($r['skor'] > 0 ? 'sebagian' : 'salah') ?>">
      <div class="tanya"><?= nl2br(h($s['teks'])) ?></div>
      <?php if (in_array($s['tipe'], ['pg', 'bs'], true)): ?>
        <div>Jawaban: <b><?= $a === '' ? '–' : h($a . '. ' . ($s['opsi'][$a] ?? '')) ?></b> · Kunci: <b><?= h($s['kunci'] . '. ' . ($s['opsi'][$s['kunci']] ?? '')) ?></b>
          <?= $r['skor'] >= $r['maks'] ? '✓' : '✗' ?></div>
      <?php else: ?>
        <div class="jawaban-uraian"><?= $a === '' ? '(kosong)' : nl2br(h($a)) ?></div>
        <div class="kecil redup">Kunci/pedoman: <?= h(mb_substr($s['kunci'] . ' ' . $s['pembahasan'], 0, 400)) ?></div>
        <?php if (!empty($nu[$i]['catatan'])): ?><div class="catatan-k">AI: <?= h($nu[$i]['catatan']) ?></div><?php endif; ?>
        <label class="skor-k">Skor <input name="skor[<?= $i ?>]" value="<?= h(isset($nu[$i]) || $r['status'] === '' ? (string)$r['skor'] : '') ?>" inputmode="decimal" placeholder="?"> / <?= h((string)$s['maks']) ?>
          <?= $r['status'] === 'perlu' ? '<small class="redup">belum dinilai</small>' : ($r['status'] ? '<small class="redup">oleh ' . h($r['status']) . '</small>' : '<small class="redup">otomatis</small>') ?></label>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ol>
  <?php if (array_filter($soal, fn($s) => !in_array($s['tipe'], ['pg', 'bs'], true))): ?><button class="utama lebar">Simpan skor uraian</button><?php endif; ?>
</form>
<?php
kaki();

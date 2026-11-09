<?php
declare(strict_types=1);

$u = ujian_ambil((int)get('id'));
if (!$u) ke('?p=ujian');
$id = (int)$u['id'];
ujian_akhiri_kedaluwarsa($u);
$soal = ujian_soal($u);
$siswa = siswa_kelas((string)$u['kelas'], (string)($u['tahun'] ?: tahun_aktif((int)$u['guru_id'])), (int)$u['guru_id']);
$peserta = [];
foreach (semua('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ?', [$id]) as $p) $peserta[$p['kode']] = $p;
$nama = array_column($siswa, 'member_name', 'member_id');
foreach ($peserta as $k => $_) if (!isset($nama[$k])) $nama[$k] = nama_siswa((string)$k);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('aksi') === 'kirim_penilaian') {
    $pid = (int)$u['penilaian_id'];
    if (!$pid || !nilai('SELECT 1 FROM guru_penilaian WHERE id = ? AND guru_id = ?', [$pid, uid()])) {
        q('INSERT INTO guru_penilaian (guru_id, kelas, mapel, judul, jenis, tanggal, kktp, dibuat) VALUES (?,?,?,?,?,?,?,?)',
            [uid(), $u['kelas'], $u['mapel'] ?: '-', mb_substr($u['judul'] . ' (online)', 0, 200), 'Ulangan Harian',
             substr((string)($u['mulai'] ?: $u['dibuat']), 0, 10), (float)post('kktp', 75), sekarang()]);
        $pid = (int)db()->lastInsertId();
        q('UPDATE guru_ujian SET penilaian_id = ? WHERE id = ?', [$pid, $id]);
    }
    $n = 0;
    foreach ($peserta as $k => $p) {
        if (!$p['selesai'] || $p['nilai'] === null) continue;
        q('INSERT INTO guru_nilai (penilaian_id, member_id, nilai, catatan) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), catatan = VALUES(catatan)',
            [$pid, $k, $p['nilai'], 'Ujian online' . ($p['perlu_koreksi'] ? ' (uraian belum dinilai)' : '')]);
        $n++;
    }
    pesan("$n nilai dikirim ke Penilaian.");
    ke('?p=nilai&id=' . $pid);
}

// ringkasan & analisis butir
$baris = [];
$butir = [];
foreach ($soal as $i => $s) $butir[$i] = ['benar' => 0, 'n' => 0, 'sebaran' => []];
foreach ($siswa as $s) $baris[$s['member_id']] = null;
foreach ($peserta as $k => $p) {
    $h = $p['mulai'] ? ujian_hitung($u, $p) : null;
    $baris[$k] = ['p' => $p, 'h' => $h];
    if (!$p['selesai'] || !$h) continue;
    $jw = json_decode((string)$p['jawaban'], true) ?: [];
    foreach ($soal as $i => $s) {
        $butir[$i]['n']++;
        if ($h['rinci'][$i]['skor'] >= $h['rinci'][$i]['maks'] && $h['rinci'][$i]['maks'] > 0) $butir[$i]['benar']++;
        if (in_array($s['tipe'], ['pg', 'bs'], true)) {
            $a = (string)($jw[$i] ?? '–');
            $butir[$i]['sebaran'][$a] = ($butir[$i]['sebaran'][$a] ?? 0) + 1;
        }
    }
}
$selesai = array_filter($peserta, fn($p) => $p['selesai']);
$perlu = array_map("strval", array_keys(array_filter($selesai, fn($p) => $p["perlu_koreksi"])));
$nilai_ada = array_values(array_filter(array_map(fn($p) => $p['nilai'], $selesai), fn($v) => $v !== null));

if (get('unduh')) {
    $out = [[$u['judul'] . ' - Kelas ' . $u['kelas']], ['No', 'Nama', 'Status', 'Nilai', 'Skor', 'Keluar layar', 'Keterangan']];
    $i = 0;
    foreach ($baris as $k => $b) {
        $p = $b['p'] ?? null;
        $out[] = [++$i, $nama[$k] ?? $k, !$p || !$p['mulai'] ? 'Tidak ikut' : ($p['selesai'] ? 'Selesai' : 'Mengerjakan'),
            $p && $p['nilai'] !== null ? str_replace('.', ',', rtrim(rtrim((string)$p['nilai'], '0'), '.')) : '',
            $b ? str_replace('.', ',', (string)$b['h']['skor']) . '/' . $b['h']['maks'] : '', $p['keluar'] ?? '', $p && $p['perlu_koreksi'] ? 'Uraian belum dinilai' : ''];
    }
    kirim_csv('ujian_' . $u['kelas'] . '_' . $u['judul'] . '.csv', $out);
}

kepala('Hasil ' . $u['judul'], 'kelas');
judul_baris('Hasil ujian', '<a class="tombol" href="?p=ujian_lihat&amp;id=' . $id . '">‹ Pantau</a>');
?>
<div class="kartu">
  <p style="margin-top:0"><b><?= h($u['judul']) ?></b> · Kelas <?= h($u['kelas']) ?></p>
  <div class="statistik">
    <span><b><?= count($selesai) ?>/<?= count($siswa) ?></b>selesai</span>
    <span><b><?= $nilai_ada ? round(array_sum($nilai_ada) / count($nilai_ada), 1) : '-' ?></b>rata-rata</span>
    <span><b><?= $nilai_ada ? max($nilai_ada) + 0 : '-' ?></b>tertinggi</span>
    <span><b><?= $nilai_ada ? min($nilai_ada) + 0 : '-' ?></b>terendah</span>
  </div>
  <?php if ($perlu): ?>
  <div class="pesan" id="kotak-ai-uraian" data-id="<?= $id ?>" data-kode='<?= h(json_encode($perlu)) ?>'>
    <?= count($perlu) ?> siswa punya jawaban isian/uraian yang belum dinilai.
    <button type="button" class="utama lebar" id="tombol-ai-uraian" style="margin-top:.5rem">✨ Nilai uraian dengan AI</button>
    <div class="status-ai" id="status-ai-uraian"></div>
  </div>
  <?php endif; ?>
  <form method="post" class="formulir">
    <?= input_token() ?><input type="hidden" name="aksi" value="kirim_penilaian">
    <?php if (!$u['penilaian_id']): ?><label>KKTP<input type="number" name="kktp" value="75" min="0" max="100"></label><?php endif; ?>
    <button class="lebar"><?= $u['penilaian_id'] ? '↻ Perbarui nilai di Penilaian' : '📊 Kirim nilai ke Penilaian' ?></button>
  </form>
  <p class="kecil"><a href="?p=ujian_hasil&amp;id=<?= $id ?>&amp;unduh=1">⬇ Unduh Excel</a></p>
</div>

<div class="kartu">
  <h3 style="margin-top:0">Nilai siswa</h3>
  <ul class="daftar">
  <?php $i = 0; foreach ($baris as $k => $b): $p = $b['p'] ?? null; ?>
    <li class="baris-status">
      <span><?= ++$i ?>. <?= h($nama[$k] ?? $k) ?>
        <?php if ($p && $p['keluar']): ?><small class="peringatan-keluar"> ⚠ keluar <?= (int)$p['keluar'] ?>×</small><?php endif; ?></span>
      <?php if (!$p || !$p['mulai']): ?><span class="redup">tidak ikut</span>
      <?php elseif (!$p['selesai']): ?><span class="redup">mengerjakan</span>
      <?php else: ?><a href="?p=ujian_jawaban&amp;id=<?= $id ?>&amp;s=<?= h(rawurlencode((string)$k)) ?>"><b><?= h(rtrim(rtrim((string)$p['nilai'], '0'), '.')) ?></b><?= $p['perlu_koreksi'] ? ' <small>⏳ uraian</small>' : '' ?> ›</a><?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ul>
</div>

<div class="kartu">
  <h3 style="margin-top:0">Analisis butir soal</h3>
  <p class="kecil redup">Persentase siswa yang menjawab benar. Soal dengan sedikit yang benar perlu dibahas ulang; pengecoh yang tidak dipilih siapa pun kurang berfungsi.</p>
  <table class="tabel-butir">
    <tr><th>No</th><th>Kunci</th><th>Benar</th><th>Sebaran jawaban</th></tr>
    <?php foreach ($soal as $i => $s): $b = $butir[$i]; $pr = $b['n'] ? round($b['benar'] / $b['n'] * 100) : null; ?>
    <tr class="<?= $pr === null ? '' : ($pr < 40 ? 'sulit' : ($pr > 85 ? 'mudah' : '')) ?>">
      <td><?= $i + 1 ?></td>
      <td><?= in_array($s['tipe'], ['pg', 'bs'], true) ? h($s['tipe'] === 'bs' ? ($s['opsi'][$s['kunci']] ?? $s['kunci']) : $s['kunci']) : '<small>' . h($s['tipe']) . '</small>' ?></td>
      <td><?= $pr === null ? '-' : $pr . '%' ?></td>
      <td><small><?php ksort($b['sebaran']); foreach ($b['sebaran'] as $o => $n) echo '<span class="' . ($o === $s['kunci'] ? 'benar' : '') . '">' . h($o) . ':' . $n . '</span> '; ?></small></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php
$GLOBALS['skrip_tambahan'][] = 'aset/ujian_guru.js';
kaki();

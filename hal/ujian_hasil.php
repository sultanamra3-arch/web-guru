<?php
declare(strict_types=1);

$u = ujian_ambil((int)get('id'));
if (!$u) ke('?p=ujian');
$id = (int)$u['id'];
ujian_akhiri_kedaluwarsa($u);
$soal = ujian_soal($u);
$siswa = ujian_siswa($u);
$peserta = [];
foreach (semua('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ?', [$id]) as $p) $peserta[$p['kode']] = $p;
$nama = array_column($siswa, 'member_name', 'member_id');
foreach ($peserta as $k => $_) if (!isset($nama[$k])) $nama[$k] = nama_siswa((string)$k);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('aksi') === 'kirim_penilaian') {
    if (($u['tindak'] ?? '') === 'remedial') {
        $r = ujian_kirim_remedial($u);
        if (!$r['ok']) { pesan($r['galat'], 'galat'); ke('?p=ujian_hasil&id=' . $id); }
        pesan($r['n'] . ' nilai remedial dimasukkan ke Penilaian ujian asal; ' . $r['naik'] . ' nilai naik' . ($u['batasi_kktp'] ? ' (paling tinggi sebesar KKTP ' . ((float)$u['kktp'] + 0) . ')' : '') . '. Nilai remedial asli dicatat di kolom catatan.');
        ke('?p=nilai&id=' . $r['pid']);
    }
    [$pid, $n] = ujian_kirim_penilaian($u, (float)post('kktp', ujian_kktp($u)), ($u['tindak'] ?? '') === 'pengayaan' ? 'Lainnya' : 'Ulangan Harian');
    pesan("$n nilai dikirim ke Penilaian." . (($u['tindak'] ?? '') === 'pengayaan' ? ' Nilai pengayaan tercatat terpisah dan tidak mengubah nilai ujian.' : ''));
    ke('?p=nilai&id=' . $pid);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(post('aksi'), ['remedial', 'pengayaan'], true)) {
    set_time_limit(240);
    $kktp = max(0, min(100, (float)str_replace(',', '.', (string)post('kktp', 75))));
    $r = ujian_buat_tindak($u, (string)post('aksi'), ['kode' => (array)($_POST['kode'] ?? []), 'kktp' => $kktp,
        'batasi' => (bool)post('batasi'), 'ai' => max(0, min(10, (int)post('ai', 0)))]);
    if (!$r['ok']) { pesan($r['galat'], 'galat'); ke('?p=ujian_hasil&id=' . $id); }
    pesan('Latihan ' . h((string)post('aksi')) . ' dibuat (' . $r['soal'] . ' soal) dan sudah dibuka. Bagikan kodenya ke siswa'
        . ($r['catatan'] ? ' — catatan: ' . h(implode('; ', $r['catatan'])) : '') . '.', $r['catatan'] ? 'galat' : 'ok');
    ke('?p=ujian_lihat&id=' . $r['id']);
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
    <?php if (!$u['penilaian_id'] && ($u['tindak'] ?? '') !== 'remedial'): ?><label>KKTP<input type="number" name="kktp" value="<?= ujian_kktp($u) + 0 ?>" min="0" max="100"></label><?php endif; ?>
    <button class="lebar"><?= ($u['tindak'] ?? '') === 'remedial' ? '📊 Masukkan nilai remedial ke Penilaian ujian asal' : ($u['penilaian_id'] ? '↻ Perbarui nilai di Penilaian' : '📊 Kirim nilai ke Penilaian') ?></button>
  </form>
  <p class="kecil"><a href="?p=ujian_hasil&amp;id=<?= $id ?>&amp;unduh=1">⬇ Unduh Excel</a></p>
</div>

<?php
$tindak = $u['tindak'] ?? '';
if ($tindak):
    $induk = ujian_ambil((int)$u['induk_id']); ?>
<div class="pesan info">Latihan <b><?= h($tindak) ?></b> khusus <?= count($siswa) ?> siswa<?= $induk ? ' dari <a href="?p=ujian_hasil&amp;id=' . (int)$induk['id'] . '">' . h($induk['judul']) . '</a>' : '' ?>.
  <?php if ($tindak === 'remedial'): ?>Tombol kirim nilai di atas memasukkan nilai ke Penilaian ujian asal: nilai hanya naik bila lebih tinggi<?= $u['batasi_kktp'] ? ', paling tinggi sebesar KKTP ' . ((float)$u['kktp'] + 0) : '' ?>.<?php else: ?>Nilai pengayaan tercatat terpisah (tidak mengubah nilai ujian).<?php endif; ?></div>
<?php else:
    $kktp = ujian_kktp($u);
    $batas_kaya = max(0, min(100, (float)get('kaya', 90)));
    $rem = []; $kaya = [];
    foreach ($selesai as $k => $p) {
        $nl = ujian_nilai_peserta($u, $p);
        if ($nl === null) continue;
        if ($nl < $kktp) $rem[(string)$k] = $nl; elseif ($nl >= $batas_kaya) $kaya[(string)$k] = $nl;
    }
    asort($rem); arsort($kaya);
    $anak = semua('SELECT id, judul, tindak, status, (SELECT COUNT(*) FROM guru_ujian_peserta p WHERE p.ujian_id = guru_ujian.id AND p.selesai IS NOT NULL) n
                   FROM guru_ujian WHERE induk_id = ? AND guru_id = ? ORDER BY id', [$id, uid()]);
    $daftar_nama = fn(array $x) => implode(', ', array_map(fn($k, $v) => h($nama[$k] ?? $k) . ' (' . ($v + 0) . ')', array_keys($x), $x)); ?>
<div class="kartu tindak-lanjut">
  <h3 style="margin-top:0">Tindak lanjut <small class="redup">KKTP <?= $kktp + 0 ?></small></h3>
  <?php if (!$selesai): ?><p class="redup kecil">Muncul setelah ada siswa yang selesai.</p><?php else: ?>
  <form method="post" class="tindak-blok">
    <?= input_token() ?><input type="hidden" name="aksi" value="remedial">
    <b>🔁 Remedial: <?= count($rem) ?> siswa di bawah <?= $kktp + 0 ?></b>
    <?php if ($rem): ?>
    <p class="kecil"><?= $daftar_nama($rem) ?></p>
    <?php foreach ($rem as $k => $_): ?><input type="hidden" name="kode[]" value="<?= h($k) ?>"><?php endforeach; ?>
    <div class="baris2">
      <label>KKTP<input type="number" name="kktp" value="<?= $kktp + 0 ?>" min="0" max="100"></label>
      <label>Tambah soal mirip dari AI<select name="ai"><option value="0">Tidak</option><option value="3">3 soal</option><option value="5" selected>5 soal</option></select></label>
    </div>
    <label class="centang"><input type="checkbox" name="batasi" value="1" checked> <span>Batasi nilai akhir sebesar KKTP <small class="redup">(nilai remedial asli tetap dicatat)</small></span></label>
    <button class="utama lebar" onclick="this.textContent='Menyiapkan soal… (bisa ±1 menit)'">Buat latihan remedial</button>
    <p class="kecil redup">Isi: soal yang paling banyak dijawab salah oleh siswa remedial. Hanya siswa di atas yang bisa masuk; tanpa token, boleh diulang, dipakai nilai terbaik.</p>
    <?php else: ?><p class="kecil redup">Semua siswa yang selesai sudah tuntas. 🎉</p><?php endif; ?>
  </form>
  <form method="post" class="tindak-blok">
    <?= input_token() ?><input type="hidden" name="aksi" value="pengayaan"><input type="hidden" name="kktp" value="<?= $kktp + 0 ?>">
    <b>⭐ Pengayaan: <?= count($kaya) ?> siswa dengan nilai ≥ <?= $batas_kaya + 0 ?></b>
    <span class="kecil"><a href="?p=ujian_hasil&amp;id=<?= $id ?>&amp;kaya=<?= $batas_kaya >= 90 ? 85 : 90 ?>">ubah batas ke <?= $batas_kaya >= 90 ? 85 : 90 ?></a></span>
    <?php if ($kaya): ?>
    <p class="kecil"><?= $daftar_nama($kaya) ?></p>
    <?php foreach ($kaya as $k => $_): ?><input type="hidden" name="kode[]" value="<?= h($k) ?>"><?php endforeach; ?>
    <label>Jumlah soal HOTS dari AI<select name="ai"><option value="3">3 soal</option><option value="5" selected>5 soal</option><option value="10">10 soal</option></select></label>
    <button class="lebar" onclick="this.textContent='Menyiapkan soal… (bisa ±1 menit)'">Buat latihan pengayaan</button>
    <?php else: ?><p class="kecil redup">Belum ada siswa dengan nilai setinggi itu.</p><?php endif; ?>
  </form>
  <?php endif; ?>
  <?php if ($anak): ?>
  <h4 style="margin:.8rem 0 .3rem">Sudah dibuat</h4>
  <ul class="daftar">
    <?php foreach ($anak as $a): ?><li class="baris-status"><span><?= $a['tindak'] === 'remedial' ? '🔁' : '⭐' ?> <?= h($a['judul']) ?> <small class="redup">· <?= (int)$a['n'] ?> selesai</small></span>
      <span><a href="?p=ujian_lihat&amp;id=<?= (int)$a['id'] ?>">Pantau</a> · <a href="?p=ujian_hasil&amp;id=<?= (int)$a['id'] ?>">Hasil</a></span></li><?php endforeach; ?>
  </ul>
  <?php endif; ?>
</div>
<?php endif; ?>

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

<?php
declare(strict_types=1);

$id = (int)get('id');
$p = satu('SELECT * FROM guru_penilaian WHERE id = ? AND guru_id = ?', [$id, uid()]);
if (!$p) { pesan('Penilaian tidak ditemukan.', 'galat'); ke('?p=penilaian'); }
$kk = satu('SELECT * FROM guru_koreksi_kunci WHERE penilaian_id = ?', [$id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string)post('aksi');
    $format = array_key_exists((string)post('format_lj'), FORMAT_LJ) ? (string)post('format_lj') : ($kk['format_lj'] ?? 'otomatis');
    $teks = null;
    if ($aksi === 'simpan_kunci') {
        $teks = mb_substr(trim((string)post('teks')), 0, 60000);
    } elseif ($aksi === 'dari_paket') {
        $teks = koreksi_kunci_dari_paket((int)post('paket_id'));
        if ($teks === null || $teks === '') { pesan('Paket soal tidak ditemukan atau kosong.', 'galat'); ke('?p=koreksi&id=' . $id); }
    }
    if ($teks !== null) {
        $soal = koreksi_urai_kunci($teks);
        if (!$soal) { pesan('Kunci belum terbaca. Tulis satu nomor per baris, mis. <code>1. B</code>.', 'galat'); ke('?p=koreksi&id=' . $id . '&ubah=1'); }
        q('INSERT INTO guru_koreksi_kunci (penilaian_id, format_lj, teks, diubah) VALUES (?,?,?,?)
           ON DUPLICATE KEY UPDATE format_lj = VALUES(format_lj), teks = VALUES(teks), diubah = VALUES(diubah)', [$id, $format, $teks, sekarang()]);
        pesan('Kunci jawaban tersimpan: ' . count($soal) . ' nomor.');
    }
    ke('?p=koreksi&id=' . $id);
}

$soal = $kk ? koreksi_urai_kunci((string)$kk['teks']) : [];
$hitung = [];
foreach ($soal as $s) $hitung[$s['jenis']] = ($hitung[$s['jenis']] ?? 0) + 1;
$siswa = siswa_kelas((string)$p['kelas']);
$sudah = [];
foreach (semua('SELECT member_id, nilai FROM guru_koreksi WHERE penilaian_id = ?', [$id]) as $r) $sudah[$r['member_id']] = $r['nilai'];
$nilai_ada = [];
foreach (semua('SELECT member_id, nilai FROM guru_nilai WHERE penilaian_id = ? AND nilai IS NOT NULL', [$id]) as $r) $nilai_ada[$r['member_id']] = $r['nilai'];
$paket = semua("SELECT id, judul, mapel, kelas FROM guru_dokumen WHERE guru_id = ? AND jenis = 'paket' ORDER BY id DESC LIMIT 50", [uid()]);
$ubah = !$kk || get('ubah');
$contoh = "1. B\n2. D\n3. A\n4. (bs) S\n5. (isian) fotosintesis\n6. (esai, 10 poin) Jelaskan proses ... Poin kunci: ..., ..., ...";

function angka2($v): string { return $v === null ? '' : rtrim(rtrim((string)$v, '0'), '.'); }

kepala('Nilai dari foto · ' . $p['judul'], 'kelas');
judul_baris('📷 Nilai dari foto', '<a class="tombol" href="?p=nilai&amp;id=' . $id . '">‹ Daftar nilai</a>');
?>
<p class="redup"><b><?= h($p['judul']) ?></b> · Kelas <?= h($p['kelas']) ?> · <?= h($p['mapel']) ?></p>

<details class="kartu" <?= $ubah ? 'open' : '' ?>>
  <summary><b>1. Kunci jawaban</b>
    <?php if ($soal): ?><small class="redup"> — <?= count($soal) ?> nomor
      (<?= implode(', ', array_map(fn($j, $n) => $n . ' ' . (JENIS_KOREKSI[$j] ?? $j), array_keys($hitung), $hitung)) ?>)
      · <?= h(FORMAT_LJ[$kk['format_lj']] ?? '') ?></small><?php endif; ?>
  </summary>
  <?php if ($paket): ?>
  <form method="post" class="formulir" style="margin-top:.8rem">
    <?= input_token() ?><input type="hidden" name="aksi" value="dari_paket">
    <input type="hidden" name="format_lj" value="<?= h($kk['format_lj'] ?? 'lj') ?>">
    <div class="baris2">
      <label>Ambil dari paket soal<select name="paket_id"><?= pilihan($paket, '', 'id', 'judul') ?></select></label>
      <label>&nbsp;<button class="tombol">Pakai kunci paket ini</button></label>
    </div>
  </form>
  <?php endif; ?>
  <form method="post" class="formulir" style="margin-top:.8rem">
    <?= input_token() ?><input type="hidden" name="aksi" value="simpan_kunci">
    <label>Bentuk lembar jawaban siswa
      <select name="format_lj"><?= pilihan(FORMAT_LJ, $kk['format_lj'] ?? 'otomatis') ?></select></label>
    <label>Kunci jawaban (satu nomor per baris)
      <textarea name="teks" rows="10" required placeholder="<?= h($contoh) ?>"><?= h($kk['teks'] ?? '') ?></textarea></label>
    <p class="kecil redup">Tanpa tanda = pilihan ganda (A–E). Tambahkan <code>(bs)</code> untuk benar/salah (kunci B atau S),
      <code>(isian)</code>, <code>(esai, 10 poin)</code>, atau <code>(pendapat, 10 poin)</code>. Untuk uraian tulis soal & poin kunci agar AI bisa menilai.</p>
    <button class="utama lebar">Simpan kunci</button>
  </form>
  <?php if ($soal): ?>
  <p class="kecil"><a href="?p=koreksi_lj&amp;id=<?= $id ?>" target="_blank">🖨 Cetak lembar jawaban siswa</a> — paling mudah dibaca AI (siswa menulis huruf di kotak).</p>
  <?php endif; ?>
</details>

<?php if ($soal): ?>
<form class="kartu formulir" id="form-koreksi" data-token="<?= h(token()) ?>" data-id="<?= $id ?>">
  <h3 style="margin-top:0">2. Foto lembar jawaban</h3>
  <label>Siswa
    <select name="member_id" id="pilih-siswa">
      <option value="">— kenali dari nama di lembar —</option>
      <?php foreach ($siswa as $i => $s): $n = $nilai_ada[$s['member_id']] ?? null; ?>
      <option value="<?= h($s['member_id']) ?>" data-sudah="<?= $n !== null ? '1' : '' ?>"><?= ($i + 1) . '. ' . h($s['member_name']) . ($n !== null ? ' ✓ ' . angka2($n) : '') ?></option>
      <?php endforeach; ?>
    </select></label>
  <div class="dua-tombol">
    <label class="tombol">📷 Kamera<input type="file" class="ambil-foto-lj" accept="image/*" capture="environment" hidden></label>
    <label class="tombol">🖼 Galeri<input type="file" class="ambil-foto-lj" accept="image/*" multiple hidden></label>
  </div>
  <div class="pratinjau-foto kiri" id="foto-lj"></div>
  <label class="centang"><input type="checkbox" name="dua_kali" value="1" checked> Baca dua kali dengan AI berbeda (lebih teliti, ±2× lebih lama)</label>
  <button class="utama lebar" id="tombol-koreksi">✨ Nilai sekarang</button>
  <div id="status-koreksi" class="status-ai"></div>
  <details class="kecil redup"><summary>Tips agar pembacaan tepat</summary>
    <ul>
      <li>Foto satu halaman penuh, tegak lurus dari atas, terang, tanpa bayangan tangan/HP. Halaman kedua dst. ditambahkan sebagai foto berikutnya (maks 4).</li>
      <li>Minta siswa mengganti jawaban dengan <b>mencoret dua garis (=)</b> lalu menandai pilihan baru, atau menghapus sampai bersih.</li>
      <li>Nomor berwarna <b style="background:#fff3c4;padding:0 .3em">kuning</b> = AI ragu (tanda samar, dua tanda, atau dua AI berbeda bacaan). Periksa lembarnya lalu ketuk jawaban yang benar.</li>
    </ul>
  </details>
</form>

<div id="hasil-koreksi" class="kartu" hidden></div>

<div class="kartu">
  <h3 style="margin-top:0">Status kelas</h3>
  <ul class="daftar">
  <?php foreach ($siswa as $i => $s): $mid = $s['member_id']; $n = $nilai_ada[$mid] ?? null; ?>
    <li class="baris-status"><span><?= $i + 1 ?>. <?= h($s['member_name']) ?></span>
      <?php if (isset($sudah[$mid])): ?><a href="?p=koreksi_lihat&amp;id=<?= $id ?>&amp;m=<?= h(rawurlencode($mid)) ?>"><b><?= angka2($n ?? $sudah[$mid]) ?></b> · lihat</a>
      <?php elseif ($n !== null): ?><b><?= angka2($n) ?></b>
      <?php else: ?><span class="redup">belum</span><?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ul>
</div>
<script type="application/json" id="data-siswa"><?= json_encode(array_map(fn($s) => ['id' => $s['member_id'], 'nama' => $s['member_name']], $siswa), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endif;
$GLOBALS['skrip_tambahan'][] = 'aset/koreksi.js';
kaki();

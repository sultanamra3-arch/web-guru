<?php
/* Tempel soal: dari teks salinan / file Word → periksa → simpan sebagai paket (poin & rubrik ikut). */
declare(strict_types=1);

$NAMA = TIPE_SOAL;
$aksi = (string)post('aksi');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $aksi === 'batal') {
    unset($_SESSION['tempel']);
    ke('?p=soal_tempel');
}

/* ---------- 1. Baca teks / Word dengan AI ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $aksi === 'baca') {
    set_time_limit(420);
    $mapel = mb_substr(trim((string)post('mapel')), 0, 120);
    $kelas = mb_substr(trim((string)post('kelas')), 0, 20);
    $teks = trim((string)post('teks'));
    $gambar = [];
    $f = $_FILES['berkas'] ?? null;
    if ($f && is_array($f['name'])) {
        foreach ($f['name'] as $i => $nm) {
            if (($f['error'][$i] ?? 4) !== UPLOAD_ERR_OK) continue;
            if (!preg_match('/\.docx$/i', (string)$nm)) { pesan('File "' . h((string)$nm) . '" bukan Word (.docx). Untuk PDF/.doc, salin teksnya lalu tempel.', 'galat'); continue; }
            $isi = docx_ke_teks((string)$f['tmp_name'][$i], $gambar);
            if ($isi === null) { pesan('File "' . h((string)$nm) . '" tidak bisa dibaca.', 'galat'); continue; }
            $teks .= "\n\n=== Isi file " . basename((string)$nm) . " ===\n" . $isi;
        }
    }
    $teks = trim($teks);
    $_SESSION['tempel_isian'] = ['mapel' => $mapel, 'kelas' => $kelas];
    if ($mapel === '') { pesan('Pilih mata pelajaran dulu.', 'galat'); ke('?p=soal_tempel'); }
    if (mb_strlen($teks) < 20) { pesan('Tempel teks soal atau pilih file Word dulu.', 'galat'); ke('?p=soal_tempel'); }
    if (mb_strlen($teks) > TEMPEL_MAKS_HURUF) { pesan('Teksnya terlalu panjang (' . number_format(mb_strlen($teks), 0, ',', '.') . ' huruf). Tempel paling banyak ±40 soal sekali jalan.', 'galat'); ke('?p=soal_tempel'); }
    $r = tempel_ekstrak($teks, $mapel, $kelas);
    if (!$r['ok']) { pesan($r['galat'] ?? 'AI gagal membaca soal.', 'galat'); ke('?p=soal_tempel'); }
    $h = tempel_rapikan($r['data'], $gambar, $teks);
    if (!$h['soal']) { pesan('Tidak ada soal yang terbaca dari teks itu.', 'galat'); ke('?p=soal_tempel'); }
    $l = tempel_lengkapi($h, $mapel, $kelas);
    $h = $l['data'];
    $h['mapel'] = $mapel; $h['kelas'] = $kelas;
    if (!empty($l['catatan'])) pesan($l['catatan'], 'galat');
    $_SESSION['tempel'] = $h;
    ke('?p=soal_tempel&periksa=1');
}

/* ---------- 3. Simpan ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $aksi === 'simpan') {
    $h = $_SESSION['tempel'] ?? null;
    if (!$h) ke('?p=soal_tempel');
    $mapel = mb_substr(trim((string)post('mapel')), 0, 120);
    $kelas = mb_substr(trim((string)post('kelas')), 0, 20);
    $judul = mb_substr(trim((string)post('judul')), 0, 250);
    $jenis = mb_substr(trim((string)post('jenis_ujian')), 0, 200);
    if ($mapel === '' || $judul === '' || $jenis === '') { pesan('Mapel, Judul paket, dan Jenis ujian wajib diisi.', 'galat'); ke('?p=soal_tempel&periksa=1'); }
    $ids = [];
    $boleh_g = array_filter(array_merge(array_column($h['soal'], 'gambar'), ...array_map(fn($s) => array_values($s['gambar_opsi']), $h['soal'])));
    db()->beginTransaction();
    foreach ((array)($_POST['s'] ?? []) as $i => $s) {
        if (!is_array($s) || empty($s['pakai'])) continue;
        $tipe = isset($NAMA[$s['tipe'] ?? '']) ? $s['tipe'] : 'esai';
        $tanya = trim((string)($s['pertanyaan'] ?? ''));
        $g = in_array($s['gambar'] ?? '', $boleh_g, true) ? $s['gambar'] : null;
        if ($tanya === '' && !$g) continue;
        $opsi = [];
        if (in_array($tipe, ['pg', 'bs'], true)) {
            foreach ((array)($s['opsi'] ?? []) as $k => $v) if (preg_match('/^[A-E]$/', (string)$k) && trim((string)$v) !== '') $opsi[$k] = trim((string)$v);
            if ($tipe === 'bs' && !$opsi) $opsi = ['A' => 'Benar', 'B' => 'Salah'];
        }
        $go = [];
        if (in_array($tipe, ['pg', 'bs'], true)) foreach ($h['soal'][$i]['gambar_opsi'] ?? [] as $k => $f) { $go[$k] = $f; if (!isset($opsi[$k])) $opsi[$k] = ''; }
        ksort($opsi);
        $kunci = trim((string)($s['kunci'] ?? ''));
        if ($tipe === 'bs') $kunci = $kunci === 'S' ? 'B' : 'A';   // di bank soal B/S disimpan sebagai huruf pilihan A=Benar, B=Salah
        q('INSERT INTO guru_soal (guru_id, mapel, kelas, topik, tipe, tingkat, level, pertanyaan, opsi, kunci, pembahasan, berbagi, dibuat, gambar, gambar_opsi) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [uid(), $mapel, $kelas, mb_substr($judul, 0, 200), $tipe, 'sedang', null, $tanya, $opsi ? json_encode($opsi, JSON_UNESCAPED_UNICODE) : null,
             mb_substr($kunci, 0, 5000), mb_substr(trim((string)($s['pembahasan'] ?? '')), 0, 5000), 0, sekarang(), $g, $go ? json_encode($go) : null]);
        $ids[] = (int)db()->lastInsertId();
    }
    if (!$ids) { db()->rollBack(); pesan('Tidak ada soal yang dipilih untuk disimpan.', 'galat'); ke('?p=soal_tempel&periksa=1'); }
    $isi = poin_paket_dari_post(['ids' => $ids, 'mapel' => $mapel, 'kelas' => $kelas, 'waktu' => trim((string)post('waktu')),
        'jenis_ujian' => $jenis, 'petunjuk' => trim((string)post('petunjuk')), 'acak' => 0]);
    q("INSERT INTO guru_dokumen (guru_id, jenis, judul, mapel, kelas, isi, dibuat, diubah) VALUES (?, 'paket', ?, ?, ?, ?, ?, ?)",
        [uid(), $judul, $mapel, mb_substr($kelas, 0, 20), json_encode($isi, JSON_UNESCAPED_UNICODE), sekarang(), sekarang()]);
    $pid = (int)db()->lastInsertId();
    db()->commit();
    unset($_SESSION['tempel']);
    pesan(count($ids) . ' soal tersimpan sebagai paket. Periksa lembar soal & kuncinya di bawah.');
    ke('?p=paket_cetak&id=' . $pid);
}

$h = get('periksa') ? ($_SESSION['tempel'] ?? null) : null;
kepala('Tempel soal', 'soal');
judul_baris('Tempel soal', '<a class="tombol" href="?p=soal">‹ Soal</a>');

/* ---------- 2. Halaman periksa ---------- */
if ($h):
    $n_ur = count(array_filter($h['soal'], fn($s) => $s['tipe'] === 'esai'));
    $n_obj = count($h['soal']) - $n_ur;
    $hit = array_count_values(array_column($h['soal'], 'tipe'));
    $k_teks = count(array_filter($h['soal'], fn($s) => $s['sumber_kunci'] === 'teks'));
    $k_ai = count(array_filter($h['soal'], fn($s) => $s['sumber_kunci'] === 'ai'));
    $k_kosong = count($h['soal']) - $k_teks - $k_ai;
    $tanpa_tanya = count(array_filter($h['soal'], fn($s) => $s['pertanyaan'] === '' && !$s['gambar']));
    $tanda = fn(string $sb) => $sb === 'teks' ? '<span class="tanda-teks">📄 dari teks</span>' : ($sb === 'ai' ? '<span class="tanda-ai">🤖 saran AI — cek</span>' : '<span class="tanda-kosong">belum ada</span>');
?>
<div class="kartu ringkas-tempel">
  <b><?= count($h['soal']) ?> soal terbaca</b>: <?php foreach ($hit as $t => $n) echo h($NAMA[$t]) . ' ' . $n . ' · '; ?>
  <br>Kunci/pedoman: <b><?= $k_teks ?></b> dari teks<?= $k_ai ? ' · <span class="tanda-ai">' . $k_ai . ' saran AI</span>' : '' ?><?= $k_kosong ? ' · <span class="tanda-kosong">' . $k_kosong . ' kosong</span>' : '' ?>
  <?php if ($n_ur): ?> · Rubrik: <?= $tanda($h['sumber_rubrik']) ?><?php endif; ?>
  · Poin: <?= $h['sumber_poin'] === 'teks' ? '<span class="tanda-teks">📄 dari teks</span>' : '<span class="tanda-ai">🤖 saran</span>' ?>
  <?php if ($tanpa_tanya): ?><p class="pesan galat" style="margin:.5rem 0 0"><?= $tanpa_tanya ?> nomor belum punya kalimat pertanyaan (mungkin yang ditempel hanya kunci). Tempel juga teks soalnya, atau isi sendiri / hapus centangnya.</p><?php endif; ?>
  <p class="kecil redup" style="margin:.4rem 0 0">Periksa terutama bagian berwarna kuning (🤖). Soal yang tidak dicentang tidak disimpan.</p>
</div>

<form method="post" class="formulir" id="form-tempel">
  <?= input_token() ?><input type="hidden" name="aksi" value="simpan">
  <?php foreach ($h['soal'] as $i => $s): $t = $s['tipe']; $nm = 's[' . $i . ']'; ?>
  <div class="kartu soal-tempel<?= $s['sumber_kunci'] === 'ai' ? ' ada-ai' : '' ?>">
    <div class="st-atas">
      <label class="centang"><input type="checkbox" name="<?= $nm ?>[pakai]" value="1" checked> <b>No. <?= $i + 1 ?></b></label>
      <select name="<?= $nm ?>[tipe]" class="st-tipe" aria-label="Bentuk soal"><?= pilihan($NAMA, $t) ?></select>
    </div>
    <?php if ($s['ada_gambar']): ?><p class="kecil merah" style="margin:.2rem 0">⚠ Di soal aslinya ada gambar yang tidak ikut terbaca. Tambahkan nanti lewat Ubah soal di paket.</p><?php endif; ?>
    <textarea name="<?= $nm ?>[pertanyaan]" rows="<?= min(8, 2 + (int)(mb_strlen($s['pertanyaan']) / 60)) ?>" aria-label="Pertanyaan" placeholder="Kalimat pertanyaan"><?= h($s['pertanyaan']) ?></textarea>
    <?php if ($s['gambar']): ?><input type="hidden" name="<?= $nm ?>[gambar]" value="<?= h($s['gambar']) ?>"><?= gambar_html($s['gambar']) ?><?php endif; ?>
    <div class="st-opsi"<?= in_array($t, ['pg', 'bs'], true) ? '' : ' hidden' ?>>
      <?php foreach (['A', 'B', 'C', 'D', 'E'] as $hf): if ($t === 'bs' && !isset($s['opsi'][$hf]) && $hf > 'B') continue; ?>
      <label class="st-opsi-baris"><b><?= $hf ?></b><input name="<?= $nm ?>[opsi][<?= $hf ?>]" value="<?= h($s['opsi'][$hf] ?? '') ?>" placeholder="<?= $hf > 'D' ? '(kosongkan bila tidak ada)' : '' ?>"><?php if (isset($s['gambar_opsi'][$hf])) echo gambar_html($s['gambar_opsi'][$hf], 'gambar-opsi-kecil'); ?></label>
      <?php endforeach; ?>
    </div>
    <label class="st-kunci-label"><span><?= in_array($t, ['pg', 'bs'], true) ? 'Kunci' : ($t === 'isian' ? 'Kunci jawaban' : 'Pedoman jawaban') ?> <?= $tanda($s['sumber_kunci']) ?></span>
      <?php if ($t === 'pg'): ?><select name="<?= $nm ?>[kunci]"><option value="">—</option><?= pilihan(array_combine(['A', 'B', 'C', 'D', 'E'], ['A', 'B', 'C', 'D', 'E']), $s['kunci']) ?></select>
      <?php elseif ($t === 'bs'): ?><select name="<?= $nm ?>[kunci]"><?= pilihan(['B' => 'Benar', 'S' => 'Salah'], $s['kunci'] ?: 'B') ?></select>
      <?php else: ?><textarea name="<?= $nm ?>[kunci]" rows="<?= $t === 'esai' ? 3 : 1 ?>"><?= h($s['kunci']) ?></textarea><?php endif; ?>
    </label>
    <details<?= $s['pembahasan'] !== '' ? ' open' : '' ?>><summary class="kecil">Pembahasan (opsional)</summary><textarea name="<?= $nm ?>[pembahasan]" rows="2"><?= h($s['pembahasan']) ?></textarea></details>
  </div>
  <?php endforeach; ?>

  <fieldset><legend>Simpan sebagai paket soal</legend>
    <label>Judul paket <span class="wajib">*</span><input name="judul" required placeholder="mis. STS Budaya Melayu Riau Kelas VII" value=""></label>
    <label>Jenis ujian <span class="wajib">*</span><input name="jenis_ujian" required list="daftar-jenis-ujian" value="<?= h($h['judul']) ?>" placeholder="ketik atau pilih"></label>
    <datalist id="daftar-jenis-ujian">
      <option value="Asesmen Sumatif Lingkup Materi"><option value="Asesmen Sumatif Tengah Semester Ganjil"><option value="Asesmen Sumatif Akhir Semester Ganjil">
      <option value="Asesmen Sumatif Tengah Semester Genap"><option value="Asesmen Sumatif Akhir Tahun"><option value="Asesmen Formatif"><option value="Ulangan Harian">
    </datalist>
    <div class="baris3">
      <label>Mapel<?= pilih_mapel($h['mapel']) ?></label>
      <label>Kelas<input name="kelas" value="<?= h($h['kelas']) ?>"></label>
      <label>Waktu<input name="waktu" value="60 menit"></label>
    </div>
    <label>Petunjuk<textarea name="petunjuk" rows="3"><?= h($h['petunjuk'] !== '' ? $h['petunjuk'] : "Berdoalah sebelum mengerjakan.\nTulis nama dan kelas pada lembar jawaban.\nPilih satu jawaban yang paling tepat untuk soal pilihan ganda.") ?></textarea></label>
  </fieldset>
  <?php if ($h['sumber_poin'] !== 'teks'): ?><p class="kecil tanda-ai" style="margin:0 0 .3rem">🤖 Poin di bawah disarankan AI (tidak tertulis di teks). Ubah bila perlu.</p><?php endif; ?>
  <?php if ($n_ur && $h['sumber_rubrik'] === 'ai'): ?><p class="kecil tanda-ai" style="margin:0 0 .3rem">🤖 Rubrik di bawah disarankan AI. Periksa dan sesuaikan.</p><?php endif; ?>
  <?= form_poin_paket(['poin_pg' => $h['poin_pg'], 'poin_uraian' => $h['poin_uraian'], 'rubrik' => $h['rubrik']], $n_obj, $n_ur) ?>
  <button class="utama lebar">💾 Simpan soal & buat paket</button>
</form>
<form method="post" style="margin-top:.6rem"><?= input_token() ?><input type="hidden" name="aksi" value="batal"><button class="lebar">Batal, tempel ulang</button></form>

<?php else:
/* ---------- 0. Formulir tempel ---------- */
    $lama = $_SESSION['tempel_isian'] ?? [];
?>
<form method="post" enctype="multipart/form-data" class="kartu formulir" id="form-baca">
  <?= input_token() ?><input type="hidden" name="aksi" value="baca">
  <p style="margin-top:0">Salin soal dari Word, PDF, atau WA lalu tempel di bawah, <b>atau</b> pilih file Word. Boleh sekaligus dengan <b>kunci jawaban, pedoman esai, rubrik, dan aturan skor</b> — urutan bebas. Yang tidak tertulis akan <b>disarankan AI</b> dan ditandai kuning untuk diperiksa.</p>
  <div class="baris2">
    <label>Mata pelajaran<?= pilih_mapel((string)($lama['mapel'] ?? '')) ?></label>
    <label>Kelas<select name="kelas"><?= pilihan(['7' => 'Kelas 7', '8' => 'Kelas 8', '9' => 'Kelas 9'], (string)($lama['kelas'] ?? '7')) ?></select></label>
  </div>
  <label>Tempel teks di sini<textarea name="teks" rows="12" placeholder="1. Kampung terbentuk dari ...&#10;A. ...&#10;B. ...&#10;&#10;Kunci: 1. B  2. C ...&#10;Rubrik esai: 7 = lengkap dan tepat ..."></textarea></label>
  <label>atau file Word (.docx) <small>(boleh lebih dari satu, mis. file soal + file kunci)</small><input type="file" name="berkas[]" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" multiple></label>
  <button class="utama lebar" id="tombol-baca"><?= ikon('ai') ?> Baca soal</button>
  <p class="kecil redup">AI menyalin kata-kata soal apa adanya. Butuh ±20–90 detik tergantung panjang soal. Belum ada yang tersimpan sebelum Anda menekan Simpan di langkah berikutnya.</p>
</form>
<script>
document.getElementById('form-baca').addEventListener('submit', function (e) {
  if (e.defaultPrevented) return;
  var b = document.getElementById('tombol-baca'); b.disabled = true; b.textContent = 'Membaca soal… (jangan tutup halaman)';
});
</script>
<?php endif;
kaki();

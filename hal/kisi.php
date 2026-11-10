<?php
/* Kisi-kisi soal dari paket soal (pengganti LKPD). AI mengisi materi, indikator, dan level; guru bisa mengubah. */
declare(strict_types=1);

const BENTUK_KISI = ['pg' => 'PG', 'bs' => 'B/S', 'isian' => 'Isian', 'esai' => 'Uraian'];

function kisi_soal_paket(array $paket): array
{
    $ids = array_values(array_filter(array_map('intval', (array)((json_decode((string)$paket['isi'], true) ?: [])['ids'] ?? []))));
    if (!$ids) return [];
    $peta = [];
    foreach (semua('SELECT * FROM guru_soal WHERE guru_id = ? AND id IN (' . implode(',', $ids) . ')', [uid()]) as $s) $peta[(int)$s['id']] = $s;
    $out = [];
    foreach ($ids as $id) if (isset($peta[$id])) $out[] = $peta[$id];
    return $out;
}

function kisi_kunci_teks(array $s): string
{
    $k = trim((string)$s['kunci']);
    return match ($s['tipe']) {
        'bs' => in_array(strtoupper($k), ['A', 'BENAR'], true) ? 'Benar' : (in_array(strtoupper($k), ['B', 'S', 'SALAH'], true) ? 'Salah' : $k),
        'pg' => strtoupper(mb_substr($k, 0, 1)),
        'isian' => mb_strimwidth($k, 0, 40, '…'),
        default => 'Pedoman',
    };
}

/** Baris kisi-kisi mengikuti urutan paket; isian lama (per id soal) dipertahankan. */
function kisi_baris(array $soal, array $lama = []): array
{
    $per_id = [];
    foreach ($lama as $b) if (isset($b['soal_id'])) $per_id[(int)$b['soal_id']] = $b;
    $out = [];
    foreach ($soal as $i => $s) {
        $l = $per_id[(int)$s['id']] ?? [];
        $out[] = ['no' => $i + 1, 'soal_id' => (int)$s['id'], 'materi' => $l['materi'] ?? (string)$s['topik'], 'indikator' => $l['indikator'] ?? '',
            'level' => $l['level'] ?? (string)$s['level'], 'bentuk' => BENTUK_KISI[$s['tipe']] ?? $s['tipe'], 'kunci' => kisi_kunci_teks($s)];
    }
    return $out;
}

function kisi_cari(int $paket_id): ?array
{
    foreach (semua("SELECT * FROM guru_dokumen WHERE guru_id = ? AND jenis = 'kisi' ORDER BY id DESC", [uid()]) as $d) {
        if ((int)((json_decode((string)$d['isi'], true) ?: [])['paket_id'] ?? 0) === $paket_id) return $d;
    }
    return null;
}

function kisi_ai_isi(array $soal, array $baris, string $mapel, string $kelas): array
{
    $data = [];
    foreach ($soal as $i => $s) $data[] = ['no' => $i + 1, 'tipe' => $s['tipe'], 'pertanyaan' => (string)$s['pertanyaan'],
        'opsi' => in_array($s['tipe'], ['pg', 'bs'], true) ? (json_decode((string)$s['opsi'], true) ?: []) : [], 'kunci' => (string)$s['kunci']];
    $r = ai_kisi_kisi($data, $mapel, $kelas);
    if (!$r['ok']) return $r;
    foreach ($baris as &$b) {
        $x = $r['peta'][$b['no']] ?? null;
        if (!$x) continue;
        if ($x['materi'] !== '') $b['materi'] = $x['materi'];
        if ($x['indikator'] !== '') $b['indikator'] = $x['indikator'];
        if ($x['level'] !== '') $b['level'] = $x['level'];
    }
    unset($b);
    return ['ok' => true, 'baris' => $baris, 'oleh' => $r['oleh']];
}

$id = (int)get('id');
$d = $id ? satu("SELECT * FROM guru_dokumen WHERE id = ? AND guru_id = ? AND jenis = 'kisi'", [$id, uid()]) : null;
if ($id && !$d) ke('?p=kisi');
$paket_id = $d ? (int)((json_decode((string)$d['isi'], true) ?: [])['paket_id'] ?? 0) : (int)get('paket');
$paket = $paket_id ? satu("SELECT * FROM guru_dokumen WHERE id = ? AND guru_id = ? AND jenis = 'paket'", [$paket_id, uid()]) : null;
if (!$d && $paket && ($ada = kisi_cari($paket_id))) ke('?p=kisi&id=' . $ada['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string)post('aksi');
    if ($aksi === 'buat' && $paket) {
        set_time_limit(200);
        $cfg = json_decode((string)$paket['isi'], true) ?: [];
        $soal = kisi_soal_paket($paket);
        if (!$soal) { pesan('Paket soal ini kosong.', 'galat'); ke('?p=kisi'); }
        $baris = kisi_baris($soal);
        $oleh = '';
        if (post('pakai_ai')) {
            $r = kisi_ai_isi($soal, $baris, (string)($cfg['mapel'] ?? $paket['mapel']), (string)($cfg['kelas'] ?? $paket['kelas']));
            if ($r['ok']) { $baris = $r['baris']; $oleh = $r['oleh']; }
            else pesan('AI gagal mengisi (' . h($r['galat']) . '). Kisi-kisi dibuat kosong, silakan isi sendiri atau coba “Isi ulang dengan AI”.', 'galat');
        }
        $bln = (int)date('n');
        $isi = ['paket_id' => $paket_id, 'judul_kop' => 'KISI-KISI ' . mb_strtoupper((string)($cfg['jenis_ujian'] ?? 'ASESMEN')),
            'mapel' => (string)($cfg['mapel'] ?? $paket['mapel']), 'kelas' => (string)($cfg['kelas'] ?? $paket['kelas']),
            'semester' => $bln >= 7 ? 'Ganjil' : 'Genap', 'baris' => $baris];
        q("INSERT INTO guru_dokumen (guru_id, jenis, judul, mapel, kelas, isi, dibuat, diubah) VALUES (?, 'kisi', ?, ?, ?, ?, ?, ?)",
            [uid(), mb_substr('Kisi-kisi ' . $paket['judul'], 0, 250), $isi['mapel'], mb_substr($isi['kelas'], 0, 20), json_encode($isi, JSON_UNESCAPED_UNICODE), sekarang(), sekarang()]);
        $baru = (int)db()->lastInsertId();
        if ($oleh) pesan('Kisi-kisi disusun AI (' . h($oleh) . '). Periksa dan ubah bila perlu.');
        ke('?p=kisi&id=' . $baru);
    }
    if (!$d) ke('?p=kisi');
    $isi = json_decode((string)$d['isi'], true) ?: [];
    if ($aksi === 'simpan') {
        foreach (['judul_kop', 'mapel', 'kelas', 'semester'] as $k) $isi[$k] = mb_substr(trim((string)post($k)), 0, 150);
        foreach ($isi['baris'] as $i => &$b) {
            $b['materi'] = mb_substr(trim((string)($_POST['materi'][$i] ?? $b['materi'])), 0, 150);
            $b['indikator'] = mb_substr(trim((string)($_POST['indikator'][$i] ?? $b['indikator'])), 0, 500);
            $lv = (string)($_POST['level'][$i] ?? $b['level']);
            $b['level'] = in_array($lv, LEVEL_KOGNITIF, true) ? $lv : '';
        }
        unset($b);
        pesan('Kisi-kisi tersimpan.');
    } elseif ($aksi === 'segarkan' && $paket) {
        $isi['baris'] = kisi_baris(kisi_soal_paket($paket), $isi['baris'] ?? []);
        pesan('Kisi-kisi disesuaikan dengan isi paket terbaru (soal baru: materi/indikator perlu diisi).');
    } elseif ($aksi === 'ai' && $paket) {
        set_time_limit(200);
        $soal = kisi_soal_paket($paket);
        $r = kisi_ai_isi($soal, kisi_baris($soal, $isi['baris'] ?? []), (string)$isi['mapel'], (string)$isi['kelas']);
        if ($r['ok']) { $isi['baris'] = $r['baris']; pesan('Materi, indikator, dan level diisi ulang oleh AI (' . h($r['oleh']) . ').'); }
        else pesan('AI gagal: ' . h($r['galat']), 'galat');
    } elseif ($aksi === 'hapus') {
        q('DELETE FROM guru_dokumen WHERE id = ?', [$d['id']]);
        pesan('Kisi-kisi dihapus.');
        ke('?p=kisi');
    }
    q('UPDATE guru_dokumen SET isi = ?, mapel = ?, kelas = ?, diubah = ? WHERE id = ?',
        [json_encode($isi, JSON_UNESCAPED_UNICODE), $isi['mapel'], mb_substr((string)$isi['kelas'], 0, 20), sekarang(), $d['id']]);
    ke('?p=kisi&id=' . $d['id']);
}

function kisi_lembar(array $isi, bool $word = false): string
{
    $g = pengguna();
    $n = array_count_values(array_column($isi['baris'], 'bentuk'));
    ob_start(); ?>
<div class="lembar-kisi">
  <?php if (!$word): ?><div class="kop-cetak" style="display:flex"><img src="aset/logo.png" alt=""><div><b><?= h((string)k('sekolah')) ?></b><br><?= h($isi['judul_kop']) ?></div></div>
  <?php else: ?><p style="text-align:center"><b><?= h((string)k('sekolah')) ?></b><br><b><?= h($isi['judul_kop']) ?></b></p><?php endif; ?>
  <table class="identitas-soal">
    <tr><td>Mata pelajaran</td><td>: <?= h($isi['mapel']) ?></td><td>Tahun ajaran</td><td>: <?= h((string)k('tahun_ajaran')) ?></td></tr>
    <tr><td>Kelas / semester</td><td>: <?= h($isi['kelas']) ?> / <?= h($isi['semester']) ?></td><td>Jumlah soal</td><td>: <?= count($isi['baris']) ?> (<?= h(implode(', ', array_map(fn($k, $v) => "$k $v", array_keys($n), $n))) ?>)</td></tr>
  </table>
  <div class="tabel-gulir"><table class="tabel tabel-kisi"<?= $word ? ' border="1" cellpadding="4" style="border-collapse:collapse;width:100%"' : '' ?>>
    <thead><tr><th>No</th><th>Materi</th><th>Indikator soal</th><th>Level</th><th>Bentuk</th><th>Kunci</th></tr></thead>
    <tbody>
    <?php foreach ($isi['baris'] as $b): ?>
      <tr><td><?= (int)$b['no'] ?></td><td><?= h($b['materi']) ?></td><td><?= h($b['indikator']) ?: '<span class="redup">—</span>' ?></td><td><?= h($b['level']) ?></td><td><?= h($b['bentuk']) ?></td><td><?= h($b['kunci']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <div class="ttd-kisi"><p><?= h((string)k('kota')) ?>, <?= tgl_panjang(hari_ini()) ?><br>Guru Mata Pelajaran<br><br><br><br><b><?= h($g['nama']) ?></b><br>NIP <?= h($g['nip'] ?: '-') ?></p></div>
</div>
<?php
    return (string)ob_get_clean();
}

// ---------- tampilan ----------
if (!$d && !$paket) {
    $paket_list = semua("SELECT id, judul, diubah FROM guru_dokumen WHERE guru_id = ? AND jenis = 'paket' ORDER BY diubah DESC", [uid()]);
    $punya = [];
    foreach (semua("SELECT id, isi FROM guru_dokumen WHERE guru_id = ? AND jenis = 'kisi'", [uid()]) as $k) $punya[(int)((json_decode((string)$k['isi'], true) ?: [])['paket_id'] ?? 0)] = (int)$k['id'];
    kepala('Kisi-kisi soal', 'perangkat');
    judul_baris('Kisi-kisi soal', '<a class="tombol" href="?p=perangkat">‹ Perangkat</a>');
    ?>
    <p class="redup" style="margin-top:-.4rem">Pilih paket soal. AI menyusun materi, indikator, dan level tiap soal; bentuk dan kunci diambil dari soalnya.</p>
    <ul class="daftar kartu">
      <?php if (!$paket_list): ?><li class="redup">Belum ada paket soal. Buat soal lalu jadikan paket di <a href="?p=soal">Bank soal</a>.</li><?php endif; ?>
      <?php foreach ($paket_list as $pk): ?>
        <li><a href="?p=kisi&amp;<?= isset($punya[(int)$pk['id']]) ? 'id=' . $punya[(int)$pk['id']] : 'paket=' . (int)$pk['id'] ?>"><b><?= h($pk['judul']) ?></b>
          <small><?= isset($punya[(int)$pk['id']]) ? '✓ sudah ada kisi-kisi' : 'belum ada kisi-kisi · tekan untuk membuat' ?></small></a></li>
      <?php endforeach; ?>
    </ul>
    <?php
    kaki();
    return;
}

if (!$d) {
    $soal = kisi_soal_paket($paket);
    kepala('Buat kisi-kisi', 'perangkat');
    judul_baris('Buat kisi-kisi', '<a class="tombol" href="?p=kisi">‹ Kembali</a>');
    ?>
    <div class="kartu">
      <p style="margin-top:0">Paket: <b><?= h($paket['judul']) ?></b> · <?= count($soal) ?> soal</p>
      <form method="post" class="formulir" onsubmit="var b=this.querySelector('button.utama');b.disabled=true;b.textContent='⏳ AI sedang menyusun kisi-kisi (±30 detik)…';">
        <?= input_token() ?><input type="hidden" name="aksi" value="buat">
        <label class="centang"><input type="checkbox" name="pakai_ai" value="1" <?= ai_siap() ? 'checked' : 'disabled' ?>> Isi materi, indikator, dan level dengan AI<?= ai_siap() ? '' : ' (AI belum aktif)' ?></label>
        <button class="utama lebar"><?= ikon('kisi') ?> Buat kisi-kisi</button>
      </form>
    </div>
    <?php
    kaki();
    return;
}

$isi = json_decode((string)$d['isi'], true) ?: [];
if (get('word')) {
    header('Content-Type: application/msword; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $d['judul']) . '.doc"');
    echo '<html><head><meta charset="utf-8"><style>body{font-family:"Times New Roman";font-size:12pt}td,th{vertical-align:top;font-size:11pt}.identitas-soal td{padding:1px 8px 1px 0}.ttd-kisi{margin-top:18pt;margin-left:60%}</style></head><body>';
    echo kisi_lembar($isi, true);
    echo '</body></html>';
    exit;
}
$kosong = count(array_filter($isi['baris'] ?? [], fn($b) => trim((string)$b['indikator']) === ''));

kepala($d['judul'], 'perangkat');
?>
<div class="judul-baris tak-cetak"><h1><?= h($d['judul']) ?></h1><a class="tombol" href="?p=kisi">‹ Kisi-kisi</a></div>
<div class="kartu tak-cetak alat-dokumen">
  <button class="utama" onclick="window.print()">🖨 Cetak</button>
  <a class="tombol" href="?p=kisi&amp;id=<?= (int)$d['id'] ?>&amp;word=1">⬇ Word</a>
  <?php if ($paket): ?><a class="tombol" href="?p=paket_cetak&amp;id=<?= (int)$paket['id'] ?>">📄 Paket soalnya</a><?php endif; ?>
</div>
<?php if ($kosong): ?><div class="pesan tak-cetak"><?= $kosong ?> indikator masih kosong. Isi di “✎ Ubah isi kisi-kisi” atau tekan “Isi ulang dengan AI”.</div><?php endif; ?>
<div class="kertas"><?= kisi_lembar($isi) ?></div>

<details class="kartu tak-cetak"<?= $kosong ? ' open' : '' ?>>
  <summary><b>✎ Ubah isi kisi-kisi</b></summary>
  <form method="post" class="formulir" style="margin-top:.6rem">
    <?= input_token() ?><input type="hidden" name="aksi" value="simpan">
    <label>Judul<input name="judul_kop" value="<?= h($isi['judul_kop'] ?? '') ?>"></label>
    <div class="baris3">
      <label>Mapel<?= pilih_mapel((string)($isi['mapel'] ?? '')) ?></label>
      <label>Kelas<input name="kelas" value="<?= h($isi['kelas'] ?? '') ?>"></label>
      <label>Semester<select name="semester"><?= pilihan(['Ganjil' => 'Ganjil', 'Genap' => 'Genap'], $isi['semester'] ?? 'Ganjil') ?></select></label>
    </div>
    <ol class="ubah-kisi">
    <?php foreach ($isi['baris'] as $i => $b): ?>
      <li>
        <div class="kecil redup"><?= h($b['bentuk']) ?> · kunci <?= h($b['kunci']) ?></div>
        <div class="baris2"><label>Materi<input name="materi[<?= $i ?>]" value="<?= h($b['materi']) ?>"></label>
          <label>Level<select name="level[<?= $i ?>]"><option value="">-</option><?= pilihan(array_combine(LEVEL_KOGNITIF, LEVEL_KOGNITIF), $b['level']) ?></select></label></div>
        <label>Indikator soal<textarea name="indikator[<?= $i ?>]" rows="2"><?= h($b['indikator']) ?></textarea></label>
      </li>
    <?php endforeach; ?>
    </ol>
    <button class="utama lebar lengket-bawah">Simpan kisi-kisi</button>
  </form>
</details>

<div class="kartu tak-cetak aksi-kisi">
  <?php if ($paket): ?>
  <form method="post" onsubmit="var b=this.querySelector('button');b.disabled=true;b.textContent='⏳ AI sedang menyusun…';"><?= input_token() ?><input type="hidden" name="aksi" value="ai"><button class="kecil" <?= ai_siap() ? '' : 'disabled' ?>>✨ Isi ulang dengan AI</button></form>
  <form method="post"><?= input_token() ?><input type="hidden" name="aksi" value="segarkan"><button class="kecil" title="Bila soal di paket diubah/ditambah">↻ Sesuaikan dengan paket</button></form>
  <?php endif; ?>
  <form method="post" onsubmit="return confirm('Hapus kisi-kisi ini?')"><?= input_token() ?><input type="hidden" name="aksi" value="hapus"><button class="kecil bahaya">Hapus</button></form>
</div>
<?php
kaki();

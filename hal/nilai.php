<?php
declare(strict_types=1);

$id = (int)get('id');
$p = satu('SELECT * FROM guru_penilaian WHERE id = ? AND guru_id = ?', [$id, uid()]);
if (!$p) { pesan('Penilaian tidak ditemukan.', 'galat'); ke('?p=penilaian'); }
$siswa = siswa_kelas($p['kelas']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('aksi') === 'hapus') {
        q('DELETE FROM guru_nilai WHERE penilaian_id = ?', [$id]);
        q('DELETE FROM guru_penilaian WHERE id = ?', [$id]);
        pesan('Penilaian dihapus.');
        ke('?p=penilaian');
    }
    if (post('aksi') === 'ubah') {
        $judul = mb_substr(trim((string)post('judul')), 0, 200);
        $tgl = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)post('tanggal')) ? (string)post('tanggal') : $p['tanggal'];
        if ($judul === '') { pesan('Nama penilaian tidak boleh kosong.', 'galat'); ke('?p=nilai&id=' . $id); }
        q('UPDATE guru_penilaian SET judul = ?, jenis = ?, mapel = ?, tanggal = ?, kktp = ? WHERE id = ?', [
            $judul, in_array(post('jenis'), JENIS_PENILAIAN, true) ? post('jenis') : $p['jenis'],
            mb_substr(trim((string)post('mapel')), 0, 120) ?: $p['mapel'], $tgl, max(0, min(100, (float)str_replace(',', '.', (string)post('kktp', $p['kktp'])))), $id]);
        pesan('Penilaian diperbarui.');
        ke('?p=nilai&id=' . $id);
    }
    $nilai = (array)($_POST['nilai'] ?? []);
    $cat = (array)($_POST['catatan'] ?? []);
    db()->beginTransaction();
    foreach ($siswa as $s) {
        $mid = $s['member_id'];
        $v = str_replace(',', '.', trim((string)($nilai[$mid] ?? '')));
        $v = $v === '' ? null : max(0, min(100, (float)$v));
        q('INSERT INTO guru_nilai (penilaian_id, member_id, nilai, catatan) VALUES (?,?,?,?)
           ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), catatan = VALUES(catatan)',
            [$id, $mid, $v, mb_substr(trim((string)($cat[$mid] ?? '')), 0, 255)]);
    }
    db()->commit();
    pesan('Nilai tersimpan.');
    ke('?p=nilai&id=' . $id);
}

$ada = [];
foreach (semua('SELECT member_id, nilai, catatan FROM guru_nilai WHERE penilaian_id = ?', [$id]) as $r) $ada[$r['member_id']] = $r;
$angka = array_values(array_filter(array_map(fn($r) => $r['nilai'], $ada), fn($v) => $v !== null));
$tuntas = count(array_filter($angka, fn($v) => (float)$v >= (float)$p['kktp']));

if (get('unduh')) {
    $out = [[$p['judul'] . ' - ' . $p['mapel'] . ' - Kelas ' . $p['kelas'] . ' (' . tgl($p['tanggal']) . ')'], ['No', 'Nama', 'Nilai', 'Keterangan', 'Catatan']];
    foreach ($siswa as $i => $s) {
        $v = $ada[$s['member_id']]['nilai'] ?? null;
        $out[] = [$i + 1, $s['member_name'], $v === null ? '' : str_replace('.', ',', rtrim(rtrim((string)$v, '0'), '.')),
            $v === null ? '' : ((float)$v >= (float)$p['kktp'] ? 'Tuntas' : 'Belum tuntas'), $ada[$s['member_id']]['catatan'] ?? ''];
    }
    kirim_csv('nilai_' . $p['kelas'] . '_' . $p['judul'] . '.csv', $out);
}

function angka(?string $v): string { return $v === null ? '' : rtrim(rtrim($v, '0'), '.'); }

kepala($p['judul'], 'kelas');
judul_baris(h($p['judul']), '<a class="tombol" href="?p=penilaian">‹ Penilaian</a>');
?>
<div class="kartu">
  <p><?= h($p['jenis']) ?> · <b>Kelas <?= h($p['kelas']) ?></b> · <?= h($p['mapel']) ?> · <?= tgl($p['tanggal']) ?> · KKTP <?= angka($p['kktp']) ?></p>
  <div class="statistik">
    <span><b><?= count($angka) ?>/<?= count($siswa) ?></b>terisi</span>
    <span><b><?= $angka ? round(array_sum($angka) / count($angka), 1) : '-' ?></b>rata-rata</span>
    <span><b><?= $angka ? angka((string)max($angka)) . ' / ' . angka((string)min($angka)) : '-' ?></b>tertinggi/terendah</span>
    <span><b><?= $tuntas ?></b>tuntas</span>
  </div>
  <p class="kecil"><a href="?p=nilai&amp;id=<?= $id ?>&amp;unduh=1">⬇ Unduh Excel</a></p>
  <details class="ubah-penilaian">
    <summary>✎ Ubah nama / keterangan</summary>
    <form method="post" class="formulir" style="margin-top:.6rem">
      <?= input_token() ?><input type="hidden" name="aksi" value="ubah">
      <label>Nama penilaian<input name="judul" value="<?= h($p['judul']) ?>" required maxlength="200"></label>
      <div class="baris2">
        <label>Jenis<select name="jenis"><?= pilihan(array_combine(JENIS_PENILAIAN, JENIS_PENILAIAN), in_array($p['jenis'], JENIS_PENILAIAN, true) ? $p['jenis'] : 'Lainnya') ?></select></label>
        <label>Mapel<input name="mapel" value="<?= h($p['mapel']) ?>"></label>
      </div>
      <div class="baris2">
        <label>Tanggal<input type="date" name="tanggal" value="<?= h($p['tanggal']) ?>"></label>
        <label>KKTP<input name="kktp" value="<?= h(angka($p['kktp'])) ?>" inputmode="decimal"></label>
      </div>
      <button class="lebar">Simpan perubahan</button>
      <p class="kecil redup">Kelas tidak bisa diganti karena nilai terikat pada siswa kelas ini.</p>
    </form>
  </details>
</div>
<a class="tombol utama tombol-foto-nilai" href="?p=koreksi&amp;id=<?= $id ?>">📷 Nilai otomatis dari foto lembar jawaban</a>
<form method="post" class="tabel-nilai" data-kktp="<?= h($p['kktp']) ?>">
  <?= input_token() ?>
  <ul class="daftar kartu">
  <?php foreach ($siswa as $i => $s): $v = $ada[$s['member_id']]['nilai'] ?? null; ?>
    <li class="baris-nilai">
      <span class="no"><?= $i + 1 ?></span>
      <b class="nm"><?= h($s['member_name']) ?></b>
      <input class="isi-nilai" name="nilai[<?= h($s['member_id']) ?>]" value="<?= h(angka($v)) ?>" inputmode="decimal" placeholder="0–100" autocomplete="off">
      <input class="ket" name="catatan[<?= h($s['member_id']) ?>]" value="<?= h($ada[$s['member_id']]['catatan'] ?? '') ?>" placeholder="catatan">
    </li>
  <?php endforeach; ?>
  </ul>
  <button class="utama lebar">Simpan nilai</button>
</form>
<form method="post" style="margin-top:1rem" onsubmit="return confirm('Hapus penilaian ini beserta semua nilainya?')">
  <?= input_token() ?><input type="hidden" name="aksi" value="hapus"><button class="kecil bahaya">Hapus penilaian</button>
</form>
<?php
kaki();

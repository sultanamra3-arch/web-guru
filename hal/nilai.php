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

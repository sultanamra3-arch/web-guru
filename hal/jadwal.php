<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('aksi') === 'hapus') {
        q('DELETE FROM guru_jadwal WHERE id = ? AND guru_id = ?', [(int)post('id'), uid()]);
        pesan('Jadwal dihapus.');
    } else {
        $hari = (int)post('hari');
        $kelas = trim((string)post('kelas'));
        $mapel = trim((string)post('mapel'));
        $jam = max(1, min(12, (int)post('jam_ke')));
        $mulai = preg_match('/^\d{2}:\d{2}$/', (string)post('mulai')) ? post('mulai') : null;
        $selesai = preg_match('/^\d{2}:\d{2}$/', (string)post('selesai')) ? post('selesai') : null;
        if (!isset(HARI[$hari]) || $kelas === '' || $mapel === '') {
            pesan('Hari, kelas, dan mapel wajib diisi.', 'galat');
        } else {
            $id = (int)post('id');
            if ($id) {
                q('UPDATE guru_jadwal SET hari=?, jam_ke=?, mulai=?, selesai=?, kelas=?, mapel=?, ruang=? WHERE id=? AND guru_id=?',
                    [$hari, $jam, $mulai, $selesai, $kelas, $mapel, trim((string)post('ruang')), $id, uid()]);
            } else {
                q('INSERT INTO guru_jadwal (guru_id, hari, jam_ke, mulai, selesai, kelas, mapel, ruang) VALUES (?,?,?,?,?,?,?,?)',
                    [uid(), $hari, $jam, $mulai, $selesai, $kelas, $mapel, trim((string)post('ruang'))]);
            }
            pesan('Jadwal tersimpan.');
        }
    }
    ke('?p=jadwal');
}

$semua = semua('SELECT * FROM guru_jadwal WHERE guru_id = ? ORDER BY hari, jam_ke, mulai', [uid()]);
$per_hari = [];
foreach ($semua as $j) $per_hari[$j['hari']][] = $j;
$ubah = (int)get('id') ? satu('SELECT * FROM guru_jadwal WHERE id = ? AND guru_id = ?', [(int)get('id'), uid()]) : null;
$terakhir = end($semua) ?: null;

kepala('Jadwal', 'lainnya');
judul_baris('Jadwal pelajaran', '<button type="button" class="tombol" onclick="window.print()">🖨 Cetak</button>');
?>
<form method="post" class="formulir tak-cetak" autocomplete="off">
  <?= input_token() ?><input type="hidden" name="id" value="<?= (int)($ubah['id'] ?? 0) ?>">
  <fieldset><legend><?= $ubah ? 'Ubah jadwal' : 'Tambah jadwal' ?></legend>
    <div class="baris3">
      <label>Hari<select name="hari"><?= pilihan(HARI, $ubah['hari'] ?? ($terakhir['hari'] ?? 1)) ?></select></label>
      <label>Jam ke-<input type="number" name="jam_ke" min="1" max="12" value="<?= h($ubah['jam_ke'] ?? (($terakhir['jam_ke'] ?? 0) + 1)) ?>"></label>
      <label>Ruang<input name="ruang" value="<?= h($ubah['ruang'] ?? '') ?>"></label>
    </div>
    <div class="baris2">
      <label>Mulai<input type="time" name="mulai" value="<?= h(substr((string)($ubah['mulai'] ?? ''), 0, 5)) ?>"></label>
      <label>Selesai<input type="time" name="selesai" value="<?= h(substr((string)($ubah['selesai'] ?? ''), 0, 5)) ?>"></label>
    </div>
    <div class="baris2">
      <label>Kelas<input name="kelas" list="dl-kelas" required value="<?= h($ubah['kelas'] ?? '') ?>"></label>
      <label>Mata pelajaran<input name="mapel" list="dl-mapel" required value="<?= h($ubah['mapel'] ?? ($terakhir['mapel'] ?? (mapel_saya()[0] ?? ''))) ?>"></label>
    </div>
  </fieldset>
  <button class="utama lebar"><?= $ubah ? 'Simpan perubahan' : 'Tambah ke jadwal' ?></button>
  <?php if ($ubah): ?><p><a href="?p=jadwal">Batal</a></p><?php endif; ?>
</form>

<div class="kartu cetak-penuh" style="margin-top:1rem">
  <h2 class="hanya-cetak">Jadwal Mengajar — <?= h(pengguna()['nama']) ?></h2>
  <?php if (!$semua): ?><p class="redup">Belum ada jadwal.</p><?php endif; ?>
  <?php foreach (HARI as $no => $nm): if (empty($per_hari[$no])) continue; ?>
    <h3><?= $nm ?></h3>
    <table class="tabel">
      <thead><tr><th>Jam</th><th>Waktu</th><th>Kelas</th><th>Mapel</th><th>Ruang</th><th class="tak-cetak"></th></tr></thead>
      <tbody>
      <?php foreach ($per_hari[$no] as $j): ?>
        <tr><td><?= (int)$j['jam_ke'] ?></td><td><?= $j['mulai'] ? substr($j['mulai'], 0, 5) . '–' . substr((string)$j['selesai'], 0, 5) : '-' ?></td>
          <td><?= h($j['kelas']) ?></td><td><?= h($j['mapel']) ?></td><td><?= h($j['ruang'] ?: '-') ?></td>
          <td class="tak-cetak nowrap"><a href="?p=jadwal&amp;id=<?= (int)$j['id'] ?>">Ubah</a>
            <form method="post" class="sebaris" onsubmit="return confirm('Hapus jadwal ini?')"><?= input_token() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$j['id'] ?>"><button class="kecil bahaya">Hapus</button></form></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endforeach; ?>
</div>
<datalist id="dl-kelas"><?php foreach (daftar_kelas() as $k): ?><option value="<?= h($k) ?>"><?php endforeach; ?></datalist>
<datalist id="dl-mapel"><?php foreach (mapel_saya() as $m): ?><option value="<?= h($m) ?>"><?php endforeach; ?></datalist>
<?php
kaki();

<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('aksi') === 'jam') {
        foreach (array_keys(HARI) as $h) {
            $baris = [];
            $mulai = array_values((array)($_POST['mulai'][$h] ?? []));
            $selesai = array_values((array)($_POST['selesai'][$h] ?? []));
            foreach ($mulai as $i => $m) {
                $m = jam_rapi((string)$m);
                $s = jam_rapi((string)($selesai[$i] ?? ''));
                if (preg_match('/^\d{2}:\d{2}$/', (string)$m) && preg_match('/^\d{2}:\d{2}$/', $s)) $baris[] = (count($baris) + 1) . " $m-$s";
            }
            // hari tanpa jam (libur) disimpan sebagai "0" agar tidak kembali ke contoh
            setelan_simpan('jam_h' . $h . ':' . uid(), $baris ? implode("\n", $baris) : '0');
        }
        // perbarui jam mulai–selesai jadwal yang sudah ada
        foreach (semua('SELECT id, hari, jam_ke FROM guru_jadwal WHERE guru_id = ?', [uid()]) as $j) {
            $jm = jam_hari((int)$j['hari'])[(int)$j['jam_ke']] ?? [null, null];
            q('UPDATE guru_jadwal SET mulai = ?, selesai = ? WHERE id = ?', [$jm[0], $jm[1], $j['id']]);
        }
        pesan('Jam pelajaran disimpan.');
        ke('?p=jadwal');
    }
    // simpan seluruh jadwal mingguan guru ini sekaligus
    $mapel_utama = mb_substr(trim((string)post('mapel_utama')), 0, 120);
    $isi = (array)($_POST['kelas'] ?? []);
    $mp = (array)($_POST['mapel'] ?? []);
    db()->beginTransaction();
    q('DELETE FROM guru_jadwal WHERE guru_id = ?', [uid()]);
    $n = 0;
    foreach (HARI as $hari => $_) {
        $jam = jam_hari($hari);
        foreach ((array)($isi[$hari] ?? []) as $ke => $kelas) {
            $kelas = mb_substr(trim((string)$kelas), 0, 50);
            if ($kelas === '') continue;
            $ke = (int)$ke;
            $m = mb_substr(trim((string)($mp[$hari][$ke] ?? '')), 0, 120) ?: $mapel_utama;
            if ($m === '') $m = '-';
            q('INSERT INTO guru_jadwal (guru_id, hari, jam_ke, mulai, selesai, kelas, mapel, ruang) VALUES (?,?,?,?,?,?,?,?)',
                [uid(), $hari, $ke, $jam[$ke][0] ?? null, $jam[$ke][1] ?? null, $kelas, $m, '']);
            $n++;
        }
    }
    db()->commit();
    if ($mapel_utama !== '' && trim((string)(pengguna()['mapel'] ?? '')) === '') q('UPDATE guru_akun SET mapel = ? WHERE id = ?', [$mapel_utama, uid()]);
    pesan("Jadwal tersimpan: $n jam pelajaran per minggu.");
    ke('?p=jadwal');
}

$ada = [];
foreach (semua('SELECT * FROM guru_jadwal WHERE guru_id = ? ORDER BY hari, jam_ke', [uid()]) as $j) $ada[(int)$j['hari']][(int)$j['jam_ke']] = $j;
$mapel_utama = mapel_saya()[0] ?? '';
$kelas_list = array_values(array_unique(array_merge(daftar_kelas(), array_map(fn($j) => $j['kelas'], array_merge(...array_values($ada ?: [[]]))))));
usort($kelas_list, 'banding_kelas');
$total = array_sum(array_map('count', $ada));

kepala('Jadwal', 'lainnya');
judul_baris('Jadwal pelajaran', '<button type="button" class="tombol" onclick="window.print()">🖨 Cetak</button>');
?>
<form method="post" class="kartu formulir tak-cetak" id="form-jadwal" data-token="<?= h(token()) ?>">
  <?= input_token() ?>
  <p style="margin-top:0" class="kecil redup">Pilih <b>kelas</b> di jam yang kamu mengajar, kosongkan yang tidak. Jam mulai–selesai diambil dari <a href="#jam-saya">jam pelajaranmu</a>. Simpan sekali di bawah.</p>
  <label>Mapel yang diajar<input name="mapel_utama" value="<?= h($mapel_utama) ?>" placeholder="mis. Informatika" list="dl-mapel"></label>
  <?php if (!daftar_kelas()): ?><p class="pesan">Belum ada kelas di SiGuru. Kamu tetap bisa mengetik nama kelas (pilih "✎ ketik…"), atau tambahkan dulu di menu <a href="?p=kelas">Kelas</a>.</p><?php endif; ?>
  <details class="foto-jadwal">
    <summary>📷 Isi otomatis dari foto jadwal sekolah</summary>
    <p class="kecil redup">Foto jadwal pelajaran/roster sekolah (maks 4 foto). AI mencari jam yang kamu ajar lalu mengisi tabel di bawah untuk kamu periksa.</p>
    <label>Nama atau kode guru di jadwal<input id="kode-guru" value="<?= h(pengguna()['nama']) ?>" placeholder="mis. 12 atau Budi Santoso"></label>
    <div class="dua-tombol">
      <label class="tombol">📷 Kamera<input type="file" class="ambil-foto-jadwal" accept="image/*" capture="environment" hidden></label>
      <label class="tombol">🖼 Galeri<input type="file" class="ambil-foto-jadwal" accept="image/*" multiple hidden></label>
    </div>
    <div class="pratinjau-foto kiri" id="foto-jadwal"></div>
    <button type="button" class="lebar" id="tombol-baca-jadwal">✨ Baca jadwal dari foto</button>
    <div class="status-ai" id="status-jadwal"></div>
  </details>

  <div class="tab tab-isi tab-hari">
    <?php foreach (HARI as $no => $nm): ?><button type="button" data-hari="<?= $no ?>" class="<?= $no === 1 ? 'aktif' : '' ?>"><?= mb_substr($nm, 0, 3) ?><small class="jml-hari"><?= count($ada[$no] ?? []) ?: '' ?></small></button><?php endforeach; ?>
  </div>
  <?php foreach (HARI as $no => $nm): ?>
  <section class="hari-jadwal" data-hari="<?= $no ?>" <?= $no === 1 ? '' : 'hidden' ?>>
    <h3><?= $nm ?></h3>
    <?php if (!jam_hari($no)): ?><p class="redup">Tidak ada jam pelajaran (libur). Atur di <a href="#jam-saya">Jam pelajaran saya</a>.</p><?php endif; ?>
    <?php foreach (jam_hari($no) as $ke => [$mulai, $selesai]): $j = $ada[$no][$ke] ?? null; ?>
    <div class="baris-jam <?= $j ? 'terisi' : '' ?>">
      <span class="jam-ke"><b><?= $ke ?></b><small><?= $mulai ?>–<?= $selesai ?></small></span>
      <select name="kelas[<?= $no ?>][<?= $ke ?>]" class="pilih-kelas" aria-label="Kelas jam <?= $ke ?>">
        <option value="">— kosong —</option>
        <?php foreach ($kelas_list as $k): ?><option value="<?= h($k) ?>" <?= $j && $j['kelas'] === $k ? 'selected' : '' ?>><?= h($k) ?></option><?php endforeach; ?>
        <option value="__ketik">✎ ketik…</option>
      </select>
      <input name="mapel[<?= $no ?>][<?= $ke ?>]" class="mapel-jam" value="<?= h($j && $j['mapel'] !== $mapel_utama ? $j['mapel'] : '') ?>" placeholder="(mapel sama)" list="dl-mapel">
    </div>
    <?php endforeach; ?>
    <button type="button" class="kecil salin-hari" data-hari="<?= $no ?>">Salin dari hari sebelumnya</button>
  </section>
  <?php endforeach; ?>
  <button class="utama lebar" style="margin-top:.8rem">💾 Simpan jadwal</button>
</form>

<div class="kartu cetak-penuh" style="margin-top:1rem">
  <h2 class="hanya-cetak">Jadwal Mengajar — <?= h(pengguna()['nama']) ?></h2>
  <h3 class="tak-cetak" style="margin-top:0">Jadwal mingguan <small class="redup">(<?= $total ?> JP)</small></h3>
  <?php if (!$total): ?><p class="redup">Belum ada jadwal.</p><?php else: ?>
  <div class="geser-x"><table class="tabel tabel-minggu">
    <tr><th>Jam</th><?php foreach (HARI as $nm): ?><th><?= $nm ?></th><?php endforeach; ?></tr>
    <?php $maks = max(array_map(fn($h) => max(array_keys(jam_hari($h)) ?: [0]), array_keys(HARI)));
    for ($ke = 1; $ke <= $maks; $ke++): ?>
    <tr><td><b><?= $ke ?></b></td>
      <?php foreach (HARI as $no => $_): $j = $ada[$no][$ke] ?? null; ?>
      <td class="<?= $j ? 'isi' : '' ?>"><?= $j ? h($j['kelas']) . '<small>' . h($j['mapel']) . '</small>' : '' ?></td>
      <?php endforeach; ?></tr>
    <?php endfor; ?>
  </table></div>
  <?php endif; ?>
</div>

<?php $sudah_atur = jam_sudah_diatur(); ?>
<details class="kartu tak-cetak" id="jam-saya" <?= $sudah_atur ? '' : 'open' ?>>
  <summary><b>⏰ Jam pelajaran saya</b> <?= $sudah_atur ? '' : '<small class="redup">(masih contoh — sesuaikan dengan sekolahmu)</small>' ?></summary>
  <form method="post" class="formulir" style="margin-top:.6rem" id="form-jam">
    <?= input_token() ?><input type="hidden" name="aksi" value="jam">
    <p class="kecil redup">Tiap hari boleh berbeda. Isi jam mulai–selesai tiap jam pelajaran; jam istirahat tidak perlu ditulis.</p>
    <div class="tab tab-isi tab-jam">
      <?php foreach (HARI as $no => $nm): ?><button type="button" data-hari="<?= $no ?>" class="<?= $no === 1 ? 'aktif' : '' ?>"><?= mb_substr($nm, 0, 3) ?><small class="jml-jam"><?= count(jam_hari($no)) ?: '' ?></small></button><?php endforeach; ?>
    </div>
    <?php foreach (HARI as $no => $nm): ?>
    <fieldset class="daftar-jam" data-hari="<?= $no ?>" <?= $no === 1 ? '' : 'hidden' ?>><legend><?= $nm ?></legend>
      <?php foreach (jam_hari($no) as $ke => [$m, $s]): ?>
      <div class="baris-waktu"><b><?= $ke ?></b><input type="text" class="jam24" inputmode="numeric" maxlength="5" pattern="([01]?\d|2[0-3])[:.][0-5]\d" placeholder="07:30" autocomplete="off" name="mulai[<?= $no ?>][]" value="<?= h($m) ?>" required><span>–</span><input type="text" class="jam24" inputmode="numeric" maxlength="5" pattern="([01]?\d|2[0-3])[:.][0-5]\d" placeholder="07:30" autocomplete="off" name="selesai[<?= $no ?>][]" value="<?= h($s) ?>" required></div>
      <?php endforeach; ?>
      <div class="dua-tombol"><button type="button" class="kecil tambah-jam">＋ Jam</button><button type="button" class="kecil kurang-jam">－ Jam terakhir</button></div>
      <?php if ($no > 1): ?><button type="button" class="kecil salin-jam">Salin jam dari <?= HARI[$no - 1] ?></button><?php endif; ?>
    </fieldset>
    <?php endforeach; ?>
    <button class="utama lebar">Simpan jam pelajaran</button>
  </form>
</details>
<datalist id="dl-mapel"><?php foreach (mapel_saya() as $m): ?><option value="<?= h($m) ?>"><?php endforeach; ?></datalist>
<?php
$GLOBALS['skrip_tambahan'][] = 'aset/jadwal.js';
kaki();

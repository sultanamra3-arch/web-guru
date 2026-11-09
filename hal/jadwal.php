<?php
declare(strict_types=1);

/* Jam pelajaran sekolah (diatur admin, dipakai semua guru). Baris: "1 07:30-08:10". */
const JAM_BAWAAN_UMUM = "1 07:30-08:10\n2 08:10-08:50\n3 08:50-09:30\n4 09:45-10:25\n5 10:25-11:05\n6 11:05-11:45\n7 12:15-12:55\n8 12:55-13:35";
const JAM_BAWAAN_JUMAT = "1 07:30-08:05\n2 08:05-08:40\n3 08:40-09:15\n4 09:30-10:05\n5 10:05-10:40\n6 10:40-11:15";

function urai_jam(string $teks): array
{
    $out = [];
    foreach (preg_split('/\r?\n/', $teks) as $b) {
        if (preg_match('/^\s*(\d{1,2})\D+(\d{1,2})[.:](\d{2})\s*[-–]\s*(\d{1,2})[.:](\d{2})/', $b, $m)) {
            $out[(int)$m[1]] = [sprintf('%02d:%s', $m[2], $m[3]), sprintf('%02d:%s', $m[4], $m[5])];
        }
    }
    ksort($out);
    return $out;
}
function jam_hari(int $hari): array
{
    $t = $hari === 5 ? setelan('jam_jumat', JAM_BAWAAN_JUMAT) : setelan('jam_umum', JAM_BAWAAN_UMUM);
    return urai_jam((string)$t) ?: urai_jam($hari === 5 ? JAM_BAWAAN_JUMAT : JAM_BAWAAN_UMUM);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('aksi') === 'jam' && adalah_admin()) {
        foreach (['jam_umum', 'jam_jumat'] as $k) if (urai_jam((string)post($k))) setelan_simpan($k, trim((string)post($k)));
        pesan('Jam pelajaran sekolah disimpan.');
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
  <p style="margin-top:0" class="kecil redup">Pilih <b>kelas</b> di jam yang kamu mengajar, kosongkan yang tidak. Jam mulai–selesai terisi otomatis. Simpan sekali di bawah.</p>
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

<?php if (adalah_admin()): ?>
<details class="kartu tak-cetak">
  <summary><b>⏰ Jam pelajaran sekolah (admin)</b></summary>
  <form method="post" class="formulir" style="margin-top:.6rem">
    <?= input_token() ?><input type="hidden" name="aksi" value="jam">
    <p class="kecil redup">Satu baris per jam: <code>nomor mulai-selesai</code>. Istirahat cukup tidak ditulis. Berlaku untuk semua guru.</p>
    <div class="baris2">
      <label>Senin–Kamis & Sabtu<textarea name="jam_umum" rows="10"><?= h((string)setelan('jam_umum', JAM_BAWAAN_UMUM)) ?></textarea></label>
      <label>Jumat<textarea name="jam_jumat" rows="10"><?= h((string)setelan('jam_jumat', JAM_BAWAAN_JUMAT)) ?></textarea></label>
    </div>
    <button class="lebar">Simpan jam pelajaran</button>
  </form>
</details>
<?php endif; ?>
<datalist id="dl-mapel"><?php foreach (mapel_saya() as $m): ?><option value="<?= h($m) ?>"><?php endforeach; ?></datalist>
<?php
$GLOBALS['skrip_tambahan'][] = 'aset/jadwal.js';
kaki();

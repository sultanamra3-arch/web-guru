<?php
/* v1.67: guru mengunggahkan foto tugas atas nama siswa/kelompok (buku dikumpulkan langsung, siswa tanpa HP).
   Hasilnya sama seperti siswa mengirim sendiri: masuk daftar kiriman, ada resi, bisa dinilai. */
declare(strict_types=1);

$t = tugas_ambil((int)get('id'));
if (!$t) ke('?p=tugas');
$id = (int)$t['id'];
$kel = tugas_kelompok($t);
$daftar = tugas_daftar_unggah($t);
$kirim = [];
foreach (semua('SELECT * FROM guru_tugas_kirim WHERE tugas_id = ?', [$id]) as $r) $kirim[(string)$r['kode']] = $r;
$belum = array_values(array_filter(array_keys($daftar), fn($kd) => empty($kirim[(string)$kd]['dikirim'])));

$kode = (string)get('kode');
if (!isset($daftar[$kode])) $kode = (string)($belum[0] ?? array_key_first($daftar) ?? '');
$k = $kirim[$kode] ?? null;
$sudah = $k && $k['dikirim'];
$nama = $daftar[$kode] ?? '';
// siswa berikutnya yang belum mengumpulkan (selain yang ini)
$lanjut = null;
foreach ($belum as $kd) if ((string)$kd !== $kode) { $lanjut = (string)$kd; break; }
$alamat = fn(string $kd, string $tambah = '') => '?p=tugas_unggah&id=' . $id . '&kode=' . rawurlencode($kd) . $tambah;

kepala('Unggahkan tugas', 'kelas');
judul_baris('Unggahkan tugas', '<a class="tombol" href="?p=tugas_lihat&amp;id=' . $id . '">‹ Kembali</a>');
?>
<div class="kartu">
  <small class="redup"><?= $kel ? 'Tugas kelompok' : 'Tugas' ?> · Kelas <?= h($t['kelas']) ?><?= $t['mapel'] ? ' · ' . h($t['mapel']) : '' ?></small>
  <b style="display:block;font-size:1.05rem"><?= h($t['judul']) ?></b>
  <p class="kecil redup" style="margin:.4rem 0 0">Untuk siswa yang mengumpulkan buku langsung atau tidak punya HP: foto tugasnya di sini, hasilnya sama seperti siswa mengirim sendiri.</p>
</div>

<?php if (!$daftar): ?>
  <div class="pesan galat"><?= $kel ? 'Belum ada kelompok di kelas ini.' : 'Belum ada siswa di kelas ini.' ?></div>
<?php else: ?>

<form method="get" class="kartu formulir">
  <input type="hidden" name="p" value="tugas_unggah"><input type="hidden" name="id" value="<?= $id ?>">
  <label>Atas nama
    <select name="kode" onchange="this.form.submit()">
      <?php foreach ($daftar as $kd => $nm): $kk = $kirim[(string)$kd] ?? null; ?>
      <option value="<?= h((string)$kd) ?>"<?= (string)$kd === $kode ? ' selected' : '' ?>><?= h($nm) ?><?= $kk && $kk['dikirim'] ? ($kk['status'] === 'dinilai' ? ' — sudah dinilai' : ' — sudah terkirim') : '' ?></option>
      <?php endforeach; ?>
    </select></label>
  <noscript><button class="kecil">Pilih</button></noscript>
  <small class="redup"><?= count($belum) ?> <?= $kel ? 'kelompok' : 'siswa' ?> belum mengumpulkan</small>
</form>

<?php if (get('ok') && $sudah): $resi = resi_tugas($id, $kode); ?>
  <div class="status-besar ok" id="status-besar"><div class="st-ikon">✓</div><div class="st-isi"><b>Tersimpan untuk <?= h($kel ? 'Kelompok ' . tugas_kode_kelompok($kode) : $nama) ?></b>
    <span><?= h(ujian_waktu_teks($k['dikirim'])) ?> · <?= count(tugas_foto_daftar($k)) ?> foto<?= $k['telat'] ? ' · terlambat' : '' ?></span></div></div>
  <?= resi_kotak($resi, resi_teks_wa($resi, $kel ? 'tugas kelompok' : 'tugas', (string)$t['judul'], (string)$t['mapel'], $nama, (string)$t['kelas'], ujian_waktu_teks((string)$k['dikirim']))) ?>
  <?php if ($lanjut !== null): ?>
  <a class="tombol utama lebar" style="margin-top:.4rem" href="<?= h($alamat($lanjut)) ?>"><?= ikon('kamera') ?> Berikutnya: <?= h($kel ? 'Kelompok ' . tugas_kode_kelompok($lanjut) : $daftar[$lanjut]) ?></a>
  <?php else: ?>
  <div class="pesan">Semua <?= $kel ? 'kelompok' : 'siswa' ?> sudah mengumpulkan.</div>
  <?php endif; ?>
  <a class="tombol lebar" style="margin-top:.5rem" href="?p=tugas_lihat&amp;id=<?= $id ?>">Kembali ke daftar kiriman</a>

<?php elseif ($k && $k['status'] === 'dinilai'): ?>
  <div class="pesan">Tugas <b><?= h($nama) ?></b> sudah dinilai, jadi tidak bisa diganti. Pilih nama lain di atas.</div>

<?php else: ?>
  <?php if ($sudah): ?>
  <div class="status-besar ok" id="status-besar"><div class="st-ikon">✓</div><div class="st-isi"><b>Sudah terkirim</b>
    <span><?= h(ujian_waktu_teks($k['dikirim'])) ?> · <?= count(tugas_foto_daftar($k)) ?> foto<?= tugas_dari_guru($k) ? ' · diunggah guru' : '' ?></span>
    <span>Mengunggah lagi akan <b>menggantikan</b> foto yang lama.</span></div></div>
  <?php else: ?>
  <div class="status-besar belum" id="status-besar"><div class="st-ikon">!</div><div class="st-isi"><b>Belum mengumpulkan</b>
    <span>Foto tugas <b><?= h($nama) ?></b>, lalu tekan Simpan.</span></div></div>
  <?php endif; ?>
  <div class="kartu" id="kirim-tugas" data-t="<?= h(token()) ?>" data-id="<?= $id ?>" data-maks="<?= (int)$t['maks_foto'] ?>" data-wajib="1"
       data-api="?p=api_tugas" data-aksi="unggah" data-siswa="<?= h($kode) ?>" data-lanjut="<?= h($alamat($kode, '&ok=1')) ?>">
    <small class="redup">Atas nama</small>
    <b style="display:block;font-size:1.1rem"><?= h($nama) ?></b>
    <div class="foto-pilih" id="foto-pilih">
      <div class="tambah-dua">
        <label class="tambah-foto"><input type="file" accept="image/*" capture="environment" hidden id="ambil-kamera"><b>📷</b><span>Kamera</span></label>
        <label class="tambah-foto"><input type="file" accept="image/*" multiple hidden id="ambil-foto"><b>🖼️</b><span>Galeri</span></label>
      </div>
    </div>
    <small class="redup">Wajib minimal 1 foto · maks. <?= (int)$t['maks_foto'] ?> foto · otomatis dikecilkan</small>
    <label class="setuju-tertib" style="margin-top:.6rem"><input type="checkbox" id="telat-tugas"<?= tugas_lewat($t) ? ' checked' : '' ?>> <span>Dikumpulkan <b>terlambat</b></span></label>
    <label class="formulir" style="display:block;margin-top:.6rem">Catatan (opsional)<textarea id="teks-tugas" rows="2" placeholder="Mis. dikumpulkan di kelas"><?= $sudah ? h((string)$k['teks']) : '' ?></textarea></label>
    <button type="button" class="utama lebar" id="tombol-kirim" style="margin-top:.6rem"><?= ikon('simpan') ?> Simpan sebagai kiriman <?= h($kel ? 'Kelompok ' . tugas_kode_kelompok($kode) : $nama) ?></button>
    <p class="kecil redup" id="status-kirim" style="margin:.4rem 0 0"></p>
  </div>
<?php endif; ?>
<?php endif; ?>
<?php
$GLOBALS['skrip_tambahan'][] = 'aset/tugas.js';
kaki();

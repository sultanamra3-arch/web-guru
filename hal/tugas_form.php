<?php
/* Buat / ubah tugas online. */
declare(strict_types=1);

$id = (int)get('id');
$t = $id ? tugas_ambil($id) : null;
if ($id && !$t) ke('?p=tugas');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kelas = (string)post('kelas');
    $judul = mb_substr(trim((string)post('judul')), 0, 200);
    if (!kelas_valid($kelas) || $judul === '') { pesan('Judul dan kelas wajib diisi.', 'galat'); ke($_SERVER['REQUEST_URI']); }
    $ada_kiriman = $t && nilai('SELECT 1 FROM guru_tugas_kirim WHERE tugas_id = ?', [$t['id']]);
    $kelompok = $ada_kiriman ? (int)$t['kelompok'] : (post('jenis_tugas') === 'kelompok' ? 1 : 0);
    if ($ada_kiriman && $kelas !== $t['kelas']) $kelas = (string)$t['kelas'];
    if ($kelompok && !kelompok_kelas($kelas, $t['tahun'] ?? null)) {
        pesan('Kelas ' . $kelas . ' belum punya kelompok. Bentuk kelompok dulu di menu Kelas → Kelompok, lalu buat tugasnya lagi.', 'galat');
        ke('?p=kelompok&kelas=' . rawurlencode($kelas));
    }
    $batas = null;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)post('batas_tgl'))) $batas = post('batas_tgl') . ' ' . (jam_rapi((string)post('batas_jam')) ?: '23:59') . ':00';
    $lampiran = $t['lampiran'] ?? null;
    if (post('hapus_gambar')) $lampiran = null;
    if ((string)post('gambar_data') !== '') $lampiran = gambar_simpan((string)post('gambar_data')) ?? $lampiran;
    $bahas_gambar = $t['pembahasan_gambar'] ?? null;
    if (post('hapus_bahas_gambar')) $bahas_gambar = null;
    if ((string)post('bahas_data') !== '') $bahas_gambar = gambar_simpan((string)post('bahas_data')) ?? $bahas_gambar;
    $data = [
        'judul' => $judul, 'kelas' => $kelas, 'mapel' => mb_substr(trim((string)post('mapel')), 0, 120),
        'petunjuk' => mb_substr(trim((string)post('petunjuk')), 0, 5000), 'lampiran' => $lampiran, 'batas' => $batas,
        'terima_telat' => post('terima_telat') ? 1 : 0, 'maks_foto' => max(1, min(10, (int)post('maks_foto', 5))),
        'tampil_tertib' => post('tampil_tertib') ? 1 : 0, 'tata_tertib' => mb_substr(trim((string)post('tata_tertib')), 0, 3000),
        'kunci' => mb_substr(trim((string)post('kunci')), 0, 5000), 'kelompok' => $kelompok,
        'pembahasan' => mb_substr(trim((string)post('pembahasan')), 0, 8000), 'pembahasan_gambar' => $bahas_gambar,
        'buka_bahas' => post('buka_bahas') === 'nilai' ? 'nilai' : 'tutup', 'tutup_otomatis' => post('tutup_otomatis') ? 1 : 0,
    ];
    if (post('tertib_bawaan') && $data['tata_tertib'] !== '') setelan_simpan('tertib_tugas:' . uid(), $data['tata_tertib']);
    if ($t) {
        q('UPDATE guru_tugas SET ' . implode(', ', array_map(fn($k) => "$k = ?", array_keys($data))) . ' WHERE id = ?', [...array_values($data), $t['id']]);
        pesan('Tugas disimpan.');
        ke('?p=tugas_lihat&id=' . $t['id']);
    }
    $data += ['guru_id' => uid(), 'tahun' => tahun_aktif(), 'kode' => tugas_kode_baru(), 'status' => 'buka', 'dibuat' => sekarang()];
    q('INSERT INTO guru_tugas (' . implode(', ', array_keys($data)) . ') VALUES (' . implode(',', array_fill(0, count($data), '?')) . ')', array_values($data));
    $baru = (int)db()->lastInsertId();
    pesan('Tugas dibuat dan langsung dibuka. Bagikan link/kode ke siswa.');
    ke('?p=tugas_lihat&id=' . $baru);
}

$kl = kelas_saya();
$ada_kiriman = $t && nilai('SELECT 1 FROM guru_tugas_kirim WHERE tugas_id = ?', [$t['id']]);
$kelas_berkelompok = array_values(array_filter($kl, fn($x) => (bool)kelompok_kelas($x)));
$v = fn($k, $d = '') => h((string)($t[$k] ?? $d));
kepala($t ? 'Ubah tugas' : 'Tugas baru', 'kelas');
judul_baris($t ? 'Ubah tugas' : 'Tugas online baru', '<a class="tombol" href="' . ($t ? '?p=tugas_lihat&amp;id=' . $t['id'] : '?p=tugas') . '">‹ Kembali</a>');
?>
<form method="post" class="kartu formulir">
  <?= input_token() ?>
  <label>Judul tugas<input name="judul" required value="<?= $v('judul') ?>" placeholder="mis. Membuat flowchart peminjaman buku"></label>
  <div class="baris2">
    <label>Kelas<select name="kelas" required><?= pilihan(array_combine($kl, $kl) ?: [], $t['kelas'] ?? (string)get('kelas')) ?></select></label>
    <label>Mapel<?= pilih_mapel((string)($t['mapel'] ?? '')) ?></label>
  </div>
  <fieldset class="jenis-tugas">
    <legend>Dikerjakan</legend>
    <?php if ($ada_kiriman): ?>
      <p style="margin:.2rem 0"><b><?= $t['kelompok'] ? 'Berkelompok' : 'Sendiri-sendiri (individu)' ?></b> <small class="redup">— tidak bisa diganti karena sudah ada kiriman.</small></p>
    <?php else: ?>
      <label class="centang"><input type="radio" name="jenis_tugas" value="individu" <?= empty($t['kelompok']) ? 'checked' : '' ?>> <span><b>Individu</b> — tiap siswa mengirim sendiri</span></label>
      <label class="centang"><input type="radio" name="jenis_tugas" value="kelompok" <?= !empty($t['kelompok']) ? 'checked' : '' ?>> <span><b>Kelompok</b> — satu kiriman per kelompok, satu nilai untuk semua anggota</span></label>
      <p class="kecil redup" style="margin:.2rem 0 0">Tugas kelompok memakai kelompok dari menu Kelas → Kelompok.
        <?= $kelas_berkelompok ? 'Kelas yang sudah punya kelompok: <b>' . h(implode(', ', $kelas_berkelompok)) . '</b>.' : '<b>Belum ada kelas yang punya kelompok.</b>' ?>
        <a href="?p=kelompok">Atur kelompok →</a></p>
    <?php endif; ?>
  </fieldset>
  <label>Petunjuk / soal tugas<textarea name="petunjuk" rows="4" placeholder="Tulis perintah tugasnya…"><?= $v('petunjuk') ?></textarea></label>
  <div class="unggah-gambar" data-nama="gambar_data">
    <div class="pratinjau-gambar"><?= gambar_html($t['lampiran'] ?? null, 'gambar-soal') ?></div>
    <label class="tombol kecil pilih-gambar"><?= ikon('galeri') ?> <span><?= !empty($t['lampiran']) ? 'Ganti gambar soal' : 'Lampirkan gambar soal (opsional)' ?></span><input type="file" accept="image/*" hidden></label>
    <?php if (!empty($t['lampiran'])): ?><label class="centang kecil"><input type="checkbox" name="hapus_gambar" value="1"> Hapus gambar</label><?php endif; ?>
    <input type="hidden" name="gambar_data">
  </div>
  <label>Batas pengumpulan<span class="tgl-jam"><input type="date" name="batas_tgl" value="<?= !empty($t['batas']) ? substr((string)$t['batas'], 0, 10) : date('Y-m-d', strtotime('+2 days')) ?>"><input type="text" class="jam24" inputmode="numeric" maxlength="5" pattern="([01]?\d|2[0-3])[:.][0-5]\d" placeholder="21:00" autocomplete="off" name="batas_jam" value="<?= !empty($t['batas']) ? substr((string)$t['batas'], 11, 5) : '21:00' ?>" aria-label="jam batas"></span></label>
  <label class="centang"><input type="checkbox" name="terima_telat" value="1" <?= ($t['terima_telat'] ?? 1) ? 'checked' : '' ?>> <span>Tetap terima kiriman yang lewat batas (ditandai <b>terlambat</b>)</span></label>
  <label class="centang"><input type="checkbox" name="tutup_otomatis" value="1" <?= ($t['tutup_otomatis'] ?? 1) ? 'checked' : '' ?>> <span><b>Tutup otomatis</b> saat batas lewat (hanya bila kiriman terlambat tidak diterima). Tugas tanpa batas waktu tidak ditutup otomatis.</span></label>
  <label>Foto paling banyak<select name="maks_foto"><?= pilihan(['1' => '1 foto', '2' => '2 foto', '3' => '3 foto', '5' => '5 foto', '8' => '8 foto', '10' => '10 foto'], (string)($t['maks_foto'] ?? 5)) ?></select></label>
  <label>Kunci jawaban / rubrik <small>(opsional, tidak dilihat siswa — dipakai AI untuk menyarankan nilai)</small><textarea name="kunci" rows="3" placeholder="mis. Flowchart lengkap (mulai, input, proses, keputusan, selesai) = 40; benar urutannya = 40; rapi = 20"><?= $v('kunci') ?></textarea></label>
  <fieldset>
    <legend>Pembahasan untuk siswa <small>(opsional)</small></legend>
    <label>Contoh jawaban / penjelasan<textarea name="pembahasan" rows="4" placeholder="mis. Flowchart yang benar: Mulai → tunjukkan kartu anggota → …"><?= $v('pembahasan') ?></textarea></label>
    <div class="unggah-gambar" data-nama="bahas_data">
      <div class="pratinjau-gambar"><?= gambar_html($t['pembahasan_gambar'] ?? null, 'gambar-soal') ?></div>
      <label class="tombol kecil pilih-gambar"><?= ikon('galeri') ?> <span><?= !empty($t['pembahasan_gambar']) ? 'Ganti gambar pembahasan' : 'Lampirkan gambar pembahasan (opsional)' ?></span><input type="file" accept="image/*" hidden></label>
      <?php if (!empty($t['pembahasan_gambar'])): ?><label class="centang kecil"><input type="checkbox" name="hapus_bahas_gambar" value="1"> Hapus gambar</label><?php endif; ?>
      <input type="hidden" name="bahas_data">
    </div>
    <label>Pembahasan dibuka untuk siswa<select name="buka_bahas"><?= pilihan(['tutup' => 'Setelah tugas ditutup (disarankan)', 'nilai' => 'Setelah tugas siswa dinilai'], (string)($t['buka_bahas'] ?? 'tutup')) ?></select></label>
    <p class="kecil redup">Pembahasan, nilai, komentar, dan jawaban siswa tampil di halaman resi siswa, dan bisa disimpan sebagai PDF hasil tugas.</p>
  </fieldset>
  <fieldset>
    <legend>📋 Tata tertib untuk siswa</legend>
    <label class="centang"><input type="checkbox" name="tampil_tertib" value="1" <?= ($t['tampil_tertib'] ?? 1) ? 'checked' : '' ?>> <span>Tampilkan tata tertib sebelum siswa mengirim (wajib dicentang “sudah membaca”)</span></label>
    <label>Aturan dari guru <small>(satu baris = satu poin)</small><textarea name="tata_tertib" rows="3"><?= h((string)(($t['tata_tertib'] ?? '') !== '' ? $t['tata_tertib'] : tugas_tertib_bawaan(uid()))) ?></textarea></label>
    <label class="centang kecil"><input type="checkbox" name="tertib_bawaan" value="1"> Jadikan bawaan untuk tugas berikutnya</label>
    <p class="kecil redup">Poin tentang batas waktu, jumlah foto, dan kirim ulang ditambahkan otomatis.</p>
  </fieldset>
  <button class="utama lebar"><?= $t ? 'Simpan' : 'Buat & buka tugas' ?></button>
</form>
<?php
kaki();

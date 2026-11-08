<?php
declare(strict_types=1);

const WARNA_SERTIFIKAT = ['navy' => 'Biru tua & emas', 'hijau' => 'Hijau & emas', 'merah' => 'Merah marun & emas', 'hitam' => 'Hitam & emas'];
$id = (int)get('id');
$s = $id ? satu('SELECT * FROM guru_sertifikat WHERE id = ? AND guru_id = ?', [$id, uid()]) : null;
if ($id && !$s) ke('?p=sertifikat');
$penerima = $s ? semua('SELECT nama, keterangan FROM guru_sertifikat_penerima WHERE sertifikat_id = ? ORDER BY urut', [$id]) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $d = [
        'judul' => trim((string)post('judul')) ?: 'SERTIFIKAT', 'sebutan' => trim((string)post('sebutan')),
        'isi' => trim((string)post('isi')), 'tanggal' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)post('tanggal')) ? post('tanggal') : hari_ini(),
        'kota' => trim((string)post('kota')),
        'ttd1_nama' => trim((string)post('ttd1_nama')), 'ttd1_jabatan' => trim((string)post('ttd1_jabatan')), 'ttd1_nip' => trim((string)post('ttd1_nip')),
        'ttd2_nama' => trim((string)post('ttd2_nama')), 'ttd2_jabatan' => trim((string)post('ttd2_jabatan')), 'ttd2_nip' => trim((string)post('ttd2_nip')),
        'format_nomor' => trim((string)post('format_nomor')), 'warna' => isset(WARNA_SERTIFIKAT[post('warna')]) ? post('warna') : 'navy',
    ];
    // Penerima: dari kelas (dicentang) + daftar nama manual
    $orang = [];
    foreach ((array)($_POST['siswa'] ?? []) as $nm) if (trim((string)$nm) !== '') $orang[] = [trim((string)$nm), trim((string)post('ket_kelas'))];
    foreach (preg_split('/\r\n|\r|\n/', (string)post('nama_manual')) as $b) {
        $b = trim($b);
        if ($b === '') continue;
        [$nm, $kt] = array_map('trim', array_pad(explode('|', $b, 2), 2, ''));
        $orang[] = [$nm, $kt];
    }
    if (!$orang) {
        pesan('Pilih minimal satu penerima (dari kelas atau ketik nama).', 'galat');
    } else {
        db()->beginTransaction();
        if ($s) {
            $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($d)));
            q("UPDATE guru_sertifikat SET $set WHERE id = ?", [...array_values($d), $id]);
            q('DELETE FROM guru_sertifikat_penerima WHERE sertifikat_id = ?', [$id]);
        } else {
            $d['guru_id'] = uid();
            $d['dibuat'] = sekarang();
            q('INSERT INTO guru_sertifikat (' . implode(',', array_keys($d)) . ') VALUES (' . implode(',', array_fill(0, count($d), '?')) . ')', array_values($d));
            $id = (int)db()->lastInsertId();
        }
        foreach ($orang as $i => [$nm, $kt]) {
            q('INSERT INTO guru_sertifikat_penerima (sertifikat_id, urut, nama, keterangan) VALUES (?,?,?,?)', [$id, $i + 1, mb_substr($nm, 0, 150), mb_substr($kt, 0, 255)]);
        }
        db()->commit();
        pesan('Sertifikat tersimpan untuk ' . count($orang) . ' penerima. <a href="?p=sertifikat_cetak&amp;id=' . $id . '" target="_blank">Cetak sekarang →</a>');
        ke('?p=sertifikat');
    }
}

$bawaan = $s ?: [
    'judul' => 'SERTIFIKAT', 'sebutan' => 'Diberikan kepada:', 'isi' => 'Atas partisipasinya sebagai Peserta dalam kegiatan ... yang diselenggarakan oleh ' . k('sekolah') . '.',
    'tanggal' => hari_ini(), 'kota' => k('kota'), 'ttd1_nama' => k('kepala_sekolah'), 'ttd1_jabatan' => 'Kepala Sekolah', 'ttd1_nip' => k('nip_kepala_sekolah'),
    'ttd2_nama' => pengguna()['nama'], 'ttd2_jabatan' => 'Guru ' . (mapel_saya()[0] ?? ''), 'ttd2_nip' => pengguna()['nip'],
    'format_nomor' => '{n}/SMPN6-KRT/' . ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][(int)date('n')] . '/' . date('Y'), 'warna' => 'navy',
];
$kelas_list = daftar_kelas();
$kelas = (string)get('kelas');

kepala('Sertifikat', 'lainnya');
judul_baris($s ? 'Ubah sertifikat' : 'Buat sertifikat', '<a class="tombol" href="?p=sertifikat">‹ Kembali</a>');
?>
<form method="post" class="formulir" id="form-sertifikat">
  <?= input_token() ?>
  <fieldset><legend>Isi sertifikat</legend>
    <div class="baris2">
      <label>Judul<input name="judul" value="<?= h($bawaan['judul']) ?>" placeholder="SERTIFIKAT / PIAGAM PENGHARGAAN"></label>
      <label>Desain warna<select name="warna"><?= pilihan(WARNA_SERTIFIKAT, $bawaan['warna']) ?></select></label>
    </div>
    <label>Sebutan sebelum nama<input name="sebutan" value="<?= h($bawaan['sebutan']) ?>"></label>
    <label>Isi / keterangan <small>(muncul di bawah nama)</small><textarea name="isi" rows="3"><?= h($bawaan['isi']) ?></textarea></label>
    <div class="baris3">
      <label>Kota<input name="kota" value="<?= h($bawaan['kota']) ?>"></label>
      <label>Tanggal<input type="date" name="tanggal" value="<?= h($bawaan['tanggal']) ?>"></label>
      <label>Format nomor <small>({n} = urut)</small><input name="format_nomor" value="<?= h($bawaan['format_nomor']) ?>"></label>
    </div>
  </fieldset>
  <fieldset><legend>Penanda tangan</legend>
    <div class="baris3">
      <label>Kiri: jabatan<input name="ttd1_jabatan" value="<?= h($bawaan['ttd1_jabatan']) ?>"></label>
      <label>Nama<input name="ttd1_nama" value="<?= h($bawaan['ttd1_nama']) ?>"></label>
      <label>NIP<input name="ttd1_nip" value="<?= h($bawaan['ttd1_nip']) ?>"></label>
    </div>
    <div class="baris3">
      <label>Kanan: jabatan<input name="ttd2_jabatan" value="<?= h($bawaan['ttd2_jabatan']) ?>"></label>
      <label>Nama<input name="ttd2_nama" value="<?= h($bawaan['ttd2_nama']) ?>"></label>
      <label>NIP<input name="ttd2_nip" value="<?= h($bawaan['ttd2_nip']) ?>"></label>
    </div>
    <p class="kecil redup">Kosongkan nama penanda tangan kanan bila hanya satu tanda tangan.</p>
  </fieldset>
  <fieldset><legend>Penerima</legend>
    <label>Ambil dari kelas
      <select id="pilih-kelas-sertifikat"><option value="">— pilih kelas —</option><?= pilihan(array_combine($kelas_list, $kelas_list) ?: [], $kelas) ?></select></label>
    <div id="daftar-siswa-sertifikat">
      <?php foreach ($kelas_list as $kl): ?>
      <div class="blok-kelas" data-kelas="<?= h($kl) ?>" hidden>
        <label class="centang"><input type="checkbox" class="centang-semua-kelas" checked disabled> Pilih semua siswa kelas <?= h($kl) ?></label>
        <input type="hidden" name="ket_kelas" value="Kelas <?= h($kl) ?>" disabled>
        <div class="kolom-centang">
        <?php foreach (siswa_kelas($kl) as $sw): ?>
          <label class="centang"><input type="checkbox" name="siswa[]" value="<?= h($sw['member_name']) ?>" checked disabled> <?= h($sw['member_name']) ?></label>
        <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <label>Atau ketik nama <small>(satu per baris; boleh "Nama | keterangan", mis. "Siti Aminah | Juara 1")</small>
      <textarea name="nama_manual" rows="4"><?php foreach ($penerima as $pn) echo h($pn['nama'] . ($pn['keterangan'] ? ' | ' . $pn['keterangan'] : '')) . "\n"; ?></textarea></label>
  </fieldset>
  <button class="utama lebar">Simpan sertifikat</button>
</form>
<?php
kaki();

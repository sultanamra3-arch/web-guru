<?php
declare(strict_types=1);

$id = (int)get('id');
$u = $id ? ujian_ambil($id) : null;
if ($id && !$u) ke('?p=ujian');
$paket_id = (int)($u['paket_id'] ?? get('paket'));
$paket = $paket_id ? satu("SELECT * FROM guru_dokumen WHERE id = ? AND guru_id = ? AND jenis = 'paket'", [$paket_id, uid()]) : null;
if (!$u && !$paket) { pesan('Pilih paket soal dulu.', 'galat'); ke('?p=ujian'); }
$cfg = $paket ? (json_decode((string)$paket['isi'], true) ?: []) : [];
$sudah_mulai = $u && nilai('SELECT 1 FROM guru_ujian_peserta WHERE ujian_id = ? AND mulai IS NOT NULL LIMIT 1', [$u['id']]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kelas = (string)post('kelas');
    $judul = mb_substr(trim((string)post('judul')), 0, 200);
    if (!kelas_valid($kelas) || $judul === '') { pesan('Judul dan kelas wajib diisi.', 'galat'); ke($_SERVER['REQUEST_URI']); }
    $tgl = fn($k) => preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', (string)post($k)) ? str_replace('T', ' ', (string)post($k)) . ':00' : null;
    $data = [
        'judul' => $judul, 'kelas' => $kelas, 'mapel' => mb_substr(trim((string)post('mapel')), 0, 120),
        'mulai' => $tgl('mulai'), 'selesai' => $tgl('selesai'),
        'durasi' => max(5, min(300, (int)post('durasi', 60))),
        'acak_soal' => post('acak_soal') ? 1 : 0, 'acak_opsi' => post('acak_opsi') ? 1 : 0,
        'token_menit' => in_array((int)post('token_menit'), [0, 5, 10, 15, 30], true) ? (int)post('token_menit') : 10,
        'tampil_nilai' => post('tampil_nilai') ? 1 : 0,
    ];
    if ($u) {
        $set = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
        q("UPDATE guru_ujian SET $set WHERE id = ?", array_merge(array_values($data), [$u['id']]));
        if (!$sudah_mulai && post('segarkan_soal') && $u['paket_id']) {
            $s = ujian_soal_dari_paket((int)$u['paket_id'], max(1, (float)post('poin_uraian', 5)));
            if ($s) q('UPDATE guru_ujian SET soal = ? WHERE id = ?', [json_encode($s, JSON_UNESCAPED_UNICODE), $u['id']]);
        }
        pesan('Pengaturan ujian disimpan.');
        ke('?p=ujian_lihat&id=' . $u['id']);
    }
    $soal = ujian_soal_dari_paket($paket_id, max(1, (float)post('poin_uraian', 5)));
    if (!$soal) { pesan('Paket soal kosong.', 'galat'); ke('?p=ujian'); }
    $data += ['guru_id' => uid(), 'paket_id' => $paket_id, 'tahun' => tahun_aktif(), 'kode' => ujian_kode_baru(), 'rahasia' => bin2hex(random_bytes(16)),
        'status' => 'draf', 'soal' => json_encode($soal, JSON_UNESCAPED_UNICODE), 'dibuat' => sekarang()];
    q('INSERT INTO guru_ujian (' . implode(', ', array_keys($data)) . ') VALUES (' . implode(',', array_fill(0, count($data), '?')) . ')', array_values($data));
    ke('?p=ujian_lihat&id=' . db()->lastInsertId());
}

$kl = kelas_saya();
$v = fn($k, $d = '') => h((string)($u[$k] ?? $d));
$dt = fn($k) => !empty($u[$k]) ? substr(str_replace(' ', 'T', (string)$u[$k]), 0, 16) : '';
$jumlah = $u ? count(ujian_soal($u)) : count((array)($cfg['ids'] ?? []));
$ada_uraian = $u ? (bool)array_filter(ujian_soal($u), fn($s) => $s['tipe'] === 'esai') : true;
$poin = $u ? (float)(array_values(array_filter(ujian_soal($u), fn($s) => $s['tipe'] === 'esai'))[0]['maks'] ?? 5) : 5;

kepala($u ? 'Pengaturan ujian' : 'Ujian online baru', 'kelas');
judul_baris($u ? 'Pengaturan ujian' : 'Ujian online baru', '<a class="tombol" href="' . ($u ? '?p=ujian_lihat&amp;id=' . $u['id'] : '?p=ujian') . '">‹ Kembali</a>');
?>
<form method="post" class="kartu formulir">
  <?= input_token() ?>
  <p class="redup" style="margin-top:0">Paket: <b><?= h($paket['judul'] ?? '-') ?></b> · <?= $jumlah ?> soal</p>
  <label>Judul ujian<input name="judul" required value="<?= $v('judul', ($cfg['jenis_ujian'] ?? 'Ulangan') . ' ' . ($cfg['mapel'] ?? '')) ?>"></label>
  <div class="baris2">
    <label>Kelas<select name="kelas" required><?= pilihan(array_combine($kl, $kl) ?: [], $u['kelas'] ?? '') ?></select></label>
    <label>Mapel<input name="mapel" value="<?= $v('mapel', $cfg['mapel'] ?? '') ?>"></label>
  </div>
  <div class="baris2">
    <label>Durasi (menit)<input type="number" name="durasi" min="5" max="300" value="<?= $v('durasi', 60) ?>"></label>
    <label>Token berganti tiap<select name="token_menit"><?= pilihan(['0' => 'Tetap (tidak berganti)', '5' => '5 menit', '10' => '10 menit', '15' => '15 menit', '30' => '30 menit'], (string)($u['token_menit'] ?? 10)) ?></select></label>
  </div>
  <div class="baris2">
    <label>Boleh mulai sejak (opsional)<input type="datetime-local" name="mulai" value="<?= $dt('mulai') ?>"></label>
    <label>Batas akhir (opsional)<input type="datetime-local" name="selesai" value="<?= $dt('selesai') ?>"></label>
  </div>
  <?php if ($ada_uraian && !$sudah_mulai): ?>
  <label>Poin per soal uraian (PG/isian = 1 poin)<input type="number" name="poin_uraian" min="1" max="50" step="0.5" value="<?= h((string)$poin) ?>"></label>
  <?php endif; ?>
  <label class="centang"><input type="checkbox" name="acak_soal" value="1" <?= ($u['acak_soal'] ?? 1) ? 'checked' : '' ?>> Acak urutan soal per siswa</label>
  <label class="centang"><input type="checkbox" name="acak_opsi" value="1" <?= ($u['acak_opsi'] ?? 1) ? 'checked' : '' ?>> Acak urutan pilihan jawaban (A–E)</label>
  <label class="centang"><input type="checkbox" name="tampil_nilai" value="1" <?= ($u['tampil_nilai'] ?? 0) ? 'checked' : '' ?>> Tampilkan nilai ke siswa setelah selesai</label>
  <?php if ($u && !$sudah_mulai): ?>
  <label class="centang"><input type="checkbox" name="segarkan_soal" value="1"> Ambil ulang soal dari paket (bila paket/bank soal diubah)</label>
  <?php endif; ?>
  <button class="utama lebar"><?= $u ? 'Simpan' : 'Buat ujian' ?></button>
</form>
<?php
kaki();

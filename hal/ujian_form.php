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
    $tgl = function ($k) {
        $d = (string)post($k . '_tgl');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return null;
        $j = jam_rapi((string)post($k . '_jam')) ?: ($k === 'selesai' ? '23:59' : '00:00');
        return "$d $j:00";
    };
    $data = [
        'judul' => $judul, 'kelas' => $kelas, 'mapel' => mb_substr(trim((string)post('mapel')), 0, 120),
        'mulai' => $tgl('mulai'), 'selesai' => $tgl('selesai'),
        'durasi' => max(5, min(300, (int)post('durasi', 60))),
        'acak_soal' => post('acak_soal') ? 1 : 0, 'acak_opsi' => post('acak_opsi') ? 1 : 0,
        'token_menit' => in_array((int)post('token_menit'), [0, 5, 10, 15, 30], true) ? (int)post('token_menit') : 10,
        'tampil_nilai' => post('tampil_nilai') ? 1 : 0,
        'mode' => post('mode') === 'latihan' ? 'latihan' : 'ujian',
        'pengaman' => post('pengaman') ? 1 : 0,
        'batas_keluar' => max(1, min(10, (int)post('batas_keluar', 3))),
        'tampil_hasil' => post('tampil_hasil') ? 1 : 0,
        'tampil_tertib' => post('tampil_tertib') ? 1 : 0,
        'tata_tertib' => mb_substr(trim((string)post('tata_tertib')), 0, 3000),
        'tutup_otomatis' => post('tutup_otomatis') ? 1 : 0,
    ];
    if ($data['mode'] === 'latihan') { $data['pengaman'] = 0; $data['tampil_hasil'] = 1; $data['tampil_nilai'] = 1; }
    if (post('tertib_bawaan') && $data['tata_tertib'] !== '') setelan_simpan('tertib_ujian:' . uid(), $data['tata_tertib']);
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
$tg = fn($k) => !empty($u[$k]) ? substr((string)$u[$k], 0, 10) : '';
$jm = fn($k) => !empty($u[$k]) ? substr((string)$u[$k], 11, 5) : '';
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
    <label>Mapel<?= pilih_mapel((string)($u['mapel'] ?? ($cfg['mapel'] ?? ''))) ?></label>
  </div>
  <fieldset class="pilih-mode">
    <legend>Jenis</legend>
    <label class="kartu-pilih"><input type="radio" name="mode" value="ujian" <?= ($u['mode'] ?? 'ujian') !== 'latihan' ? 'checked' : '' ?>><span><b>Ujian</b><small>Pakai token, satu kali kerja, nilai untuk rapor</small></span></label>
    <label class="kartu-pilih"><input type="radio" name="mode" value="latihan" <?= ($u['mode'] ?? '') === 'latihan' ? 'checked' : '' ?>><span><b>Latihan</b><small>Tanpa token, boleh diulang, nilai &amp; pembahasan langsung muncul</small></span></label>
  </fieldset>
  <div class="baris2">
    <label>Durasi (menit)<input type="number" name="durasi" min="5" max="300" value="<?= $v('durasi', 60) ?>"></label>
    <label class="hanya-ujian">Token berganti tiap<select name="token_menit"><?= pilihan(['0' => 'Tetap (tidak berganti)', '5' => '5 menit', '10' => '10 menit', '15' => '15 menit', '30' => '30 menit'], (string)($u['token_menit'] ?? 10)) ?></select></label>
  </div>
    <label>Boleh mulai sejak (opsional)<span class="tgl-jam"><input type="date" name="mulai_tgl" value="<?= $tg('mulai') ?>"><input type="text" class="jam24" inputmode="numeric" maxlength="5" pattern="([01]?\d|2[0-3])[:.][0-5]\d" placeholder="07:30" autocomplete="off" name="mulai_jam" value="<?= $jm('mulai') ?>" aria-label="jam mulai"></span></label>
    <label>Batas akhir (opsional)<span class="tgl-jam"><input type="date" name="selesai_tgl" value="<?= $tg('selesai') ?>"><input type="text" class="jam24" inputmode="numeric" maxlength="5" pattern="([01]?\d|2[0-3])[:.][0-5]\d" placeholder="07:30" autocomplete="off" name="selesai_jam" value="<?= $jm('selesai') ?>" aria-label="jam batas"></span></label>
    <p class="kecil redup" id="info-jadwal" hidden></p>
    <label class="centang"><input type="checkbox" name="tutup_otomatis" value="1" <?= ($u['tutup_otomatis'] ?? 1) ? 'checked' : '' ?>> <span><b>Tutup otomatis</b> saat batas akhir lewat (atau, bila tanpa batas akhir, saat semua siswa sudah mengumpulkan). Nilai langsung masuk ke Penilaian.</span></label>
  <?php if ($ada_uraian && !$sudah_mulai): ?>
  <label>Poin per soal uraian (PG/isian = 1 poin)<input type="number" name="poin_uraian" min="1" max="50" step="0.5" value="<?= h((string)$poin) ?>"></label>
  <?php endif; ?>
  <label class="centang"><input type="checkbox" name="acak_soal" value="1" <?= ($u['acak_soal'] ?? 1) ? 'checked' : '' ?>> Acak urutan soal per siswa</label>
  <label class="centang"><input type="checkbox" name="acak_opsi" value="1" <?= ($u['acak_opsi'] ?? 1) ? 'checked' : '' ?>> Acak urutan pilihan jawaban (A–E)</label>
  <div class="hanya-ujian">
  <label class="centang"><input type="checkbox" name="tampil_nilai" value="1" <?= ($u['tampil_nilai'] ?? 0) ? 'checked' : '' ?>> Tampilkan nilai ke siswa setelah selesai</label>
  <label class="centang"><input type="checkbox" name="tampil_hasil" value="1" <?= ($u['tampil_hasil'] ?? 0) ? 'checked' : '' ?>> <span>Setelah ujian <b>ditutup</b>, siswa bisa melihat nilai, jawaban yang salah &amp; pembahasan</span></label>
  <fieldset class="kotak-pengaman">
    <legend>🛡 Pengaman ujian</legend>
    <label class="saklar"><input type="checkbox" name="pengaman" value="1" id="pengaman" <?= ($u['pengaman'] ?? 0) ? 'checked' : '' ?>><span class="saklar-tombol"></span> <b>Aktifkan pengaman</b></label>
    <div class="pengaman-isi">
      <label>Jawaban dikumpulkan otomatis bila siswa keluar dari halaman ujian sebanyak
        <select name="batas_keluar"><?= pilihan(array_combine(range(1, 10), array_map(fn($n) => $n . ' kali', range(1, 10))), (string)($u['batas_keluar'] ?? 3)) ?></select></label>
      <p class="kecil peringatan-batas">⚠ Jangan pilih <b>1 kali</b>: telepon masuk, notifikasi, atau layar HP mati sebentar saja sudah langsung mengumpulkan jawaban. Disarankan 3 kali.</p>
      <p class="kecil redup">Siswa melihat peringatan setiap kali keluar (“Peringatan 1 dari 3”). Bila tidak sengaja (HP mati, telepon masuk), tekan <b>Buka ulang</b> di halaman pantau.</p>
    </div>
    <p class="kecil redup pengaman-mati">Pengaman mati: siswa yang keluar layar tetap <b>dicatat</b> dan terlihat di halaman pantau, tapi tidak dihentikan.</p>
  </fieldset>
  </div>
  <fieldset>
    <legend>📋 Tata tertib untuk siswa</legend>
    <label class="centang"><input type="checkbox" name="tampil_tertib" value="1" <?= ($u['tampil_tertib'] ?? 1) ? 'checked' : '' ?>> <span>Tampilkan tata tertib sebelum siswa mulai (siswa wajib mencentang “sudah membaca”)</span></label>
    <label>Aturan dari guru <small>(satu baris = satu poin)</small><textarea name="tata_tertib" rows="4"><?= h((string)(($u['tata_tertib'] ?? '') !== '' && $u ? $u['tata_tertib'] : ujian_tertib_bawaan(uid()))) ?></textarea></label>
    <label class="centang kecil"><input type="checkbox" name="tertib_bawaan" value="1"> Jadikan bawaan untuk ujian berikutnya</label>
    <p class="kecil redup">Poin tentang waktu, simpan otomatis, pengaman, dan hasil <b>ditambahkan otomatis</b> sesuai pengaturan di atas.</p>
  </fieldset>
  <?php if ($u && !$sudah_mulai): ?>
  <label class="centang"><input type="checkbox" name="segarkan_soal" value="1"> Ambil ulang soal dari paket (bila paket/bank soal diubah)</label>
  <?php endif; ?>
  <button class="utama lebar"><?= $u ? 'Simpan' : 'Buat ujian' ?></button>
</form>
<?php if (!$u): ?>
<script>
/* Ujian baru: jam mulai & batas akhir diisi dari jadwal mengajar kelas itu (bisa diubah). Tanpa jadwal: batas akhir 23:59. */
(function () {
  var J = <?= json_encode(jadwal_blok_kelas(), JSON_UNESCAPED_UNICODE) ?>, HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
  var f = document.querySelector('form.formulir'); if (!f) return;
  var el = function (n) { return f.querySelector('[name=' + n + ']'); }, info = document.getElementById('info-jadwal');
  var kls = el('kelas'), mt = el('mulai_tgl'), mj = el('mulai_jam'), st = el('selesai_tgl'), sj = el('selesai_jam');
  [mj, st, sj].forEach(function (x) { x.addEventListener('input', function () { x.dataset.manual = 1; }); });
  function dua(n) { return ('0' + n).slice(-2); }
  function isi() {
    var now = new Date(), hariIni = now.getFullYear() + '-' + dua(now.getMonth() + 1) + '-' + dua(now.getDate());
    var tgl = mt.value || hariIni, h = new Date(tgl + 'T00:00').getDay(), jam = dua(now.getHours()) + ':' + dua(now.getMinutes());
    var blok = (J[kls.value] || {})[h] || [], b = null;
    if (blok.length) { b = blok[blok.length - 1]; for (var i = 0; i < blok.length; i++) if (tgl !== hariIni || blok[i][1] > jam) { b = blok[i]; break; } }
    if (!mt.value) mt.value = tgl;
    if (!st.dataset.manual) st.value = tgl;
    if (b) {
      if (!mj.dataset.manual) mj.value = b[0];
      if (!sj.dataset.manual) sj.value = b[1];
      info.textContent = 'Diisi dari jadwal mengajar: ' + HARI[h] + ', kelas ' + kls.value + ' jam ' + b[0] + '–' + b[1] + '. Boleh diubah.';
    } else {
      if (!sj.dataset.manual) sj.value = '23:59';
      info.textContent = 'Kelas ' + kls.value + ' tidak ada di jadwal hari ' + HARI[h] + ', jadi batas akhir 23:59. Boleh diubah.';
    }
    info.hidden = false;
  }
  kls.addEventListener('change', isi); mt.addEventListener('change', isi);
  isi();
})();
</script>
<?php endif;
kaki();

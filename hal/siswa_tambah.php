<?php
declare(strict_types=1);

$kelas = (string)get('kelas');
$daftar = daftar_kelas();

kepala('Tambah siswa', 'kelas');
judul_baris('Tambah siswa', '<a class="tombol" href="' . ($kelas !== '' ? '?p=siswa&amp;kelas=' . h(rawurlencode($kelas)) : '?p=kelas') . '">‹ Kembali</a>');
?>
<form id="form-siswa" class="kartu formulir" data-token="<?= h(token()) ?>" onsubmit="return false">
  <label>Masukkan ke kelas
    <select id="kelas-tujuan">
      <?php foreach ($daftar as $k): ?><option value="<?= h($k) ?>" <?= $k === $kelas ? 'selected' : '' ?>><?= h($k) ?></option><?php endforeach; ?>
      <option value="__baru" <?= !$daftar ? 'selected' : '' ?>>＋ Kelas baru…</option>
    </select></label>
  <label id="kotak-kelas-baru" <?= $daftar ? 'hidden' : '' ?>>Nama kelas baru<input id="kelas-baru" placeholder="mis. VII A"></label>

  <div class="tab tab-isi" role="tablist">
    <button type="button" class="aktif" data-tab="foto">📷 Foto daftar</button>
    <button type="button" data-tab="tempel">📋 Tempel / ketik</button>
    <button type="button" data-tab="berkas">📄 Excel / CSV</button>
  </div>

  <section data-isi="foto">
    <p class="kecil redup">Foto daftar hadir, buku absen, atau daftar nama dari wali kelas. AI membaca nama (dan NISN/L-P bila ada). Maks 4 foto.</p>
    <div class="dua-tombol">
      <label class="tombol">📷 Kamera<input type="file" class="ambil-foto-siswa" accept="image/*" capture="environment" hidden></label>
      <label class="tombol">🖼 Galeri<input type="file" class="ambil-foto-siswa" accept="image/*" multiple hidden></label>
    </div>
    <div class="pratinjau-foto kiri" id="foto-siswa"></div>
    <button type="button" class="utama lebar" id="tombol-baca-foto">✨ Baca nama dari foto</button>
  </section>

  <section data-isi="tempel" hidden>
    <label>Satu siswa per baris
      <textarea id="teks-siswa" rows="10" placeholder="Ahmad Pratama&#10;Siti Rahma, P&#10;Budi Santoso; 0123456789; L"></textarea></label>
    <p class="kecil redup">Boleh menempel dari Excel, Word, atau WhatsApp. Nomor urut di depan, NISN (10 angka), dan L/P dikenali otomatis.</p>
    <button type="button" class="utama lebar" id="tombol-tempel">Lanjut</button>
  </section>

  <section data-isi="berkas" hidden>
    <p class="kecil redup">Berkas .xlsx atau .csv dengan kolom <b>Nama</b> (wajib) dan bila ada <b>NISN</b>, <b>JK</b>, <b>Rombel/Kelas</b>, <b>No absen</b>. Ekspor peserta didik Dapodik bisa langsung dipakai.</p>
    <label class="tombol lebar" style="text-align:center">📄 Pilih berkas<input type="file" id="berkas-siswa" accept=".xlsx,.csv,.txt" hidden></label>
    <label class="centang"><input type="checkbox" id="pakai-kolom-kelas" checked> Pakai kolom Rombel/Kelas dari berkas bila ada</label>
  </section>
  <div id="status-siswa" class="status-ai"></div>
</form>

<div id="pratinjau-siswa" class="kartu" hidden>
  <h3 style="margin-top:0">Periksa sebelum disimpan</h3>
  <p class="kecil redup">Ubah nama yang salah langsung di kotaknya. Hapus centang untuk melewati. Baris kuning = AI kurang yakin.</p>
  <label class="centang"><input type="checkbox" id="rapikan-kelas" checked> Rapikan nama kelas (7A → VII A)</label>
  <table class="tabel-pratinjau-siswa"><thead><tr><th></th><th>No</th><th>Nama</th><th>L/P</th><th>NISN</th><th>Kelas</th></tr></thead><tbody></tbody></table>
  <button type="button" class="utama lebar" id="tombol-simpan-siswa">💾 Simpan</button>
  <div id="status-simpan-siswa" class="status-ai"></div>
</div>
<?php
$GLOBALS['skrip_tambahan'][] = 'aset/siswa.js';
kaki();

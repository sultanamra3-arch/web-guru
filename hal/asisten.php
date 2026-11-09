<?php
declare(strict_types=1);

wajib_admin();
$siap = ai_siap();
$riwayat = array_values(array_filter(asisten_riwayat(), fn($m) => !str_starts_with((string)$m['content'], '[Catatan sistem]')));

kepala('Asisten AI', 'lainnya');
judul_baris('✨ Asisten AI', '<button type="button" class="tombol kecil" id="chat-baru">Percakapan baru</button>');
?>
<?php if (!$siap): ?><div class="pesan">Asisten belum aktif. Isi kunci API di <a href="?p=pengaturan_ai">Pengaturan AI</a>.</div><?php endif; ?>
<div id="chat" class="chat" data-token="<?= h(token()) ?>">
  <div class="gelembung ai">
    Halo! Saya bisa mengubah <b>data milikmu</b> lewat chat, misalnya:
    <br>• “ganti nama penilaian Ulangan Tengas jadi Ulangan Tengah Semester”
    <br>• “nilai Adelia 85, Aini 90 untuk tugas 1 kelas 8.1”
    <br>• “Rafi hari ini sakit, yang lain hadir — absensi VII A Informatika”
    <br>• “pindahkan Andi Saputra ke VII B”, “Senin jam 3 saya mengajar IX A”
    <br>• 📷 kirim foto daftar nilai/absen, saya siapkan isiannya
    <br><small>Setiap perubahan muncul sebagai kartu dan baru tersimpan setelah kamu menekan <b>Simpan</b>.</small>
  </div>
  <?php foreach ($riwayat as $m): $isi = preg_replace('/\n?\[Usulan [0-9a-f]+ .*$/s', '', (string)$m['content']); ?>
  <div class="gelembung <?= $m['role'] === 'user' ? 'saya' : 'ai' ?>" data-md="1"><?= h($isi) ?></div>
  <?php endforeach; ?>
</div>
<form id="chat-form" class="chat-form" autocomplete="off">
  <div id="chat-foto-pratinjau" class="pratinjau-foto kiri"></div>
  <div class="chat-baris">
    <label class="tombol tombol-foto" title="Kamera">📷<input type="file" class="chat-foto" accept="image/*" capture="environment" hidden></label>
    <label class="tombol tombol-foto" title="Galeri">🖼<input type="file" class="chat-foto" accept="image/*" multiple hidden></label>
    <textarea id="chat-teks" rows="1" placeholder="Tulis perintah…" <?= $siap ? '' : 'disabled' ?>></textarea>
    <button class="utama" id="chat-kirim" <?= $siap ? '' : 'disabled' ?>>Kirim</button>
  </div>
</form>
<?php
$GLOBALS['skrip_tambahan'][] = 'aset/asisten.js';
kaki();

<?php
/* Menilai satu kiriman tugas. */
declare(strict_types=1);

$t = tugas_ambil((int)get('id'));
if (!$t) ke('?p=tugas');
$id = (int)$t['id'];
$kode = (string)get('kode');
$k = satu('SELECT * FROM guru_tugas_kirim WHERE tugas_id = ? AND kode = ?', [$id, $kode]);
$kn = tugas_kode_kelompok($kode);
if (!$k || !$k['dikirim']) { pesan(($kn ? 'Kelompok ini' : 'Siswa ini') . ' belum mengirim.', 'galat'); ke('?p=tugas_lihat&id=' . $id); }

// urutan siswa yang sudah mengirim (untuk ‹ ›)
$urut = [];
$sudah = array_column(semua('SELECT kode FROM guru_tugas_kirim WHERE tugas_id = ? AND dikirim IS NOT NULL', [$id]), 'kode');
if (tugas_kelompok($t)) { foreach (tugas_daftar_kelompok($t) as $x) if (in_array('K' . $x, $sudah, true)) $urut[] = 'K' . $x; }
else foreach (siswa_kelas((string)$t['kelas'], (string)($t['tahun'] ?: tahun_aktif())) as $s) if (in_array($s['member_id'], $sudah, true)) $urut[] = $s['member_id'];
foreach ($sudah as $x) if (!in_array($x, $urut, true)) $urut[] = $x;
$pos = array_search($kode, $urut, true);
$sebelum = $pos > 0 ? $urut[$pos - 1] : null;
$sesudah = $pos !== false && $pos < count($urut) - 1 ? $urut[$pos + 1] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $komentar = mb_substr(trim((string)post('komentar')), 0, 1000);
    if (post('aksi') === 'perbaiki') {
        q("UPDATE guru_tugas_kirim SET status = 'perbaiki', komentar = ?, dinilai = ? WHERE tugas_id = ? AND kode = ?", [$komentar, sekarang(), $id, $kode]);
        pesan(h(tugas_nama_kirim($kode)) . ' diminta memperbaiki. ' . ($kn ? 'Anggotanya' : 'Ia') . ' bisa mengirim ulang lewat link yang sama.');
    } else {
        $v = str_replace(',', '.', trim((string)post('nilai')));
        if ($v === '' || !is_numeric($v) || (float)$v < 0 || (float)$v > 100) { pesan('Isi nilai 0–100.', 'galat'); ke($_SERVER['REQUEST_URI']); }
        q("UPDATE guru_tugas_kirim SET status = 'dinilai', nilai = ?, komentar = ?, dinilai = ? WHERE tugas_id = ? AND kode = ?", [(float)$v, $komentar, sekarang(), $id, $kode]);
        pesan('Nilai ' . h(tugas_nama_kirim($kode)) . ' tersimpan.');
    }
    // lanjut ke siswa berikutnya yang belum dinilai
    foreach (array_slice($urut, $pos + 1) as $x) {
        if (nilai("SELECT 1 FROM guru_tugas_kirim WHERE tugas_id = ? AND kode = ? AND status = 'kirim'", [$id, $x])) ke('?p=tugas_nilai&id=' . $id . '&kode=' . rawurlencode($x));
    }
    ke('?p=tugas_lihat&id=' . $id);
}

$foto = tugas_foto_daftar($k);
$saran = json_decode((string)$k['saran_ai'], true) ?: null;
kepala('Nilai tugas', 'kelas');
judul_baris(h(tugas_nama_kirim($kode)), '<a class="tombol" href="?p=tugas_lihat&amp;id=' . $id . '">‹ Daftar</a>');
?>
<p class="redup" style="margin-top:-.4rem">Tugas: <?= h($t['judul']) ?> · dikirim <?= h(ujian_waktu_teks($k['dikirim'])) ?><?= $k['telat'] ? ' · <span class="lencana st-draf">terlambat</span>' : '' ?><?= (int)$k['kali'] > 1 ? ' · kirim ke-' . (int)$k['kali'] : '' ?><?= $kn && $k['oleh'] ? ' · oleh ' . h(nama_siswa((string)$k['oleh'])) : '' ?></p>
<?php if ($kn): ?><p class="kecil" style="margin-top:-.3rem">Anggota: <?= h(implode(', ', tugas_anggota($t, $kn))) ?></p><?php endif; ?>
<?php if ($foto): ?>
<div class="foto-tugas">
  <?php foreach ($foto as $i => $f): ?><a href="<?= h(tugas_foto_url($id, $f)) ?>" target="_blank" rel="noopener" class="foto-kiriman"><img src="<?= h(tugas_foto_url($id, $f)) ?>" alt="foto <?= $i + 1 ?>" loading="lazy"><small>foto <?= $i + 1 ?> · ketuk untuk perbesar</small></a><?php endforeach; ?>
</div>
<?php elseif (count(json_decode((string)$k['foto'], true) ?: []) === 0 && $t['nilai_dikirim']): ?>
<div class="pesan">Foto sudah dihapus otomatis (<?= TUGAS_HAPUS_FOTO_HARI ?> hari setelah nilai dikirim).</div>
<?php endif; ?>
<?php if (trim((string)$k['teks']) !== ''): ?><div class="kartu"><div class="kecil redup">Jawaban / catatan <?= $kn ? 'kelompok' : 'siswa' ?></div><div style="white-space:pre-line"><?= h($k['teks']) ?></div></div><?php endif; ?>

<div class="kartu saran-ai" id="kotak-saran" data-id="<?= $id ?>" data-kode="<?= h($kode) ?>">
  <?php if ($saran): ?><b>✨ Saran AI: <?= h((string)(float)$saran['nilai']) ?></b><?= !empty($saran['ragu']) ? ' <span class="lencana st-draf">AI ragu</span>' : '' ?><br><small><?= h($saran['catatan']) ?></small>
  <?php else: ?><button type="button" class="kecil" id="minta-saran" <?= ai_siap() ? '' : 'disabled' ?>>✨ Minta saran nilai dari AI</button> <small class="redup"><?= ai_siap() ? (trim((string)$t['kunci']) !== '' ? 'memakai kunci/rubrik tugas' : 'tanpa kunci: dinilai dari kelengkapan & ketepatan') : 'AI belum aktif' ?></small><?php endif; ?>
  <span class="kecil redup" id="status-saran"></span>
</div>

<form method="post" class="kartu formulir">
  <?= input_token() ?>
  <label>Nilai<input name="nilai" id="isi-nilai" inputmode="decimal" value="<?= h($k['nilai'] !== null ? rtrim(rtrim((string)$k['nilai'], '0'), '.') : ($saran ? (string)(float)$saran['nilai'] : '')) ?>" class="isi-nilai-tugas" autocomplete="off"></label>
  <label>Komentar untuk <?= $kn ? 'kelompok' : 'siswa' ?> <small>(<?= $kn ? 'semua anggota' : 'siswa' ?> bisa membacanya)</small><textarea name="komentar" id="isi-komentar" rows="2"><?= h((string)($k['komentar'] ?? ($saran['catatan'] ?? ''))) ?></textarea></label>
  <div class="dua-tombol">
    <button class="utama" name="aksi" value="nilai">Simpan nilai</button>
    <button name="aksi" value="perbaiki" formnovalidate onclick="return confirm('Minta <?= $kn ? 'kelompok ini' : 'siswa' ?> memperbaiki dan mengirim ulang?')">↩ Minta perbaiki</button>
  </div>
</form>
<div class="nav-tugas">
  <?= $sebelum ? '<a class="tombol kecil" href="?p=tugas_nilai&amp;id=' . $id . '&amp;kode=' . h(rawurlencode($sebelum)) . '">‹ ' . h($kn ? tugas_nama_kirim($sebelum) : strtok(nama_siswa($sebelum), ' ')) . '</a>' : '<span></span>' ?>
  <?= $sesudah ? '<a class="tombol kecil" href="?p=tugas_nilai&amp;id=' . $id . '&amp;kode=' . h(rawurlencode($sesudah)) . '">' . h($kn ? tugas_nama_kirim($sesudah) : strtok(nama_siswa($sesudah), ' ')) . ' ›</a>' : '' ?>
</div>
<script>
(function () {
  var b = document.getElementById('minta-saran');
  if (!b) return;
  b.onclick = function () {
    var k = document.getElementById('kotak-saran'), st = document.getElementById('status-saran');
    b.disabled = true; st.textContent = '⏳ AI sedang membaca tugas (±20 detik)…';
    var fd = new FormData(); fd.append('_t', document.querySelector('input[name=_t]').value); fd.append('aksi', 'saran'); fd.append('id', k.dataset.id); fd.append('kode', k.dataset.kode);
    fetch('?p=api_tugas', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (r) {
      if (!r.ok) { st.textContent = '⚠ ' + (r.galat || 'gagal'); b.disabled = false; return; }
      k.innerHTML = '<b>✨ Saran AI: ' + r.nilai + '</b>' + (r.ragu ? ' <span class="lencana st-draf">AI ragu</span>' : '') + '<br><small></small>';
      k.querySelector('small').textContent = r.catatan;
      var n = document.getElementById('isi-nilai'), c = document.getElementById('isi-komentar');
      if (!n.value) n.value = r.nilai; if (!c.value) c.value = r.catatan;
    }).catch(function () { st.textContent = '⚠ Gagal tersambung'; b.disabled = false; });
  };
})();
</script>
<?php
kaki();

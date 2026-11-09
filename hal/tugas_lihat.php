<?php
/* Pantau tugas online: siapa yang sudah/belum kirim, bagikan, nilai, kirim ke Penilaian. */
declare(strict_types=1);

$t = tugas_ambil((int)get('id'));
if (!$t) ke('?p=tugas');
$id = (int)$t['id'];
$siswa = siswa_kelas((string)$t['kelas'], (string)($t['tahun'] ?: tahun_aktif()));
$kirim = [];
foreach (semua('SELECT * FROM guru_tugas_kirim WHERE tugas_id = ?', [$id]) as $k) $kirim[$k['kode']] = $k;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ((string)post('aksi')) {
        case 'tutup': q("UPDATE guru_tugas SET status = 'tutup' WHERE id = ?", [$id]); pesan('Tugas ditutup; siswa tidak bisa mengirim lagi.'); break;
        case 'buka': q("UPDATE guru_tugas SET status = 'buka' WHERE id = ?", [$id]); pesan('Tugas dibuka lagi.'); break;
        case 'hapus_kiriman':
            $k = $kirim[(string)post('kode')] ?? null;
            if ($k) { tugas_hapus_foto(tugas_foto_daftar($k)); q('DELETE FROM guru_tugas_kirim WHERE tugas_id = ? AND kode = ?', [$id, $k['kode']]); pesan('Kiriman ' . h(nama_siswa($k['kode'])) . ' dihapus; siswa bisa mengirim ulang.'); }
            break;
        case 'kirim_penilaian':
            $pid = (int)$t['penilaian_id'];
            if (!$pid || !nilai('SELECT 1 FROM guru_penilaian WHERE id = ? AND guru_id = ?', [$pid, uid()])) {
                q('INSERT INTO guru_penilaian (guru_id, kelas, mapel, judul, jenis, tanggal, kktp, dibuat) VALUES (?,?,?,?,?,?,?,?)',
                    [uid(), $t['kelas'], $t['mapel'] ?: '-', mb_substr($t['judul'] . ' (tugas online)', 0, 200), 'Tugas',
                     substr((string)($t['batas'] ?: $t['dibuat']), 0, 10), (float)post('kktp', 75), sekarang()]);
                $pid = (int)db()->lastInsertId();
                q('UPDATE guru_tugas SET penilaian_id = ? WHERE id = ?', [$pid, $id]);
            }
            $n = 0;
            foreach ($kirim as $k) {
                if ($k['nilai'] === null) continue;
                q('INSERT INTO guru_nilai (penilaian_id, member_id, nilai, catatan) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), catatan = VALUES(catatan)',
                    [$pid, $k['kode'], $k['nilai'], 'Tugas online' . ($k['telat'] ? ' (terlambat)' : '')]);
                $n++;
            }
            q('UPDATE guru_tugas SET nilai_dikirim = ? WHERE id = ?', [sekarang(), $id]);
            pesan("$n nilai dikirim ke Penilaian. Foto kiriman akan dihapus otomatis " . TUGAS_HAPUS_FOTO_HARI . ' hari setelah ini (nilai tetap tersimpan).');
            ke('?p=nilai&id=' . $pid);
        case 'hapus':
            foreach ($kirim as $k) tugas_hapus_foto(tugas_foto_daftar($k));
            q('DELETE FROM guru_tugas_kirim WHERE tugas_id = ?', [$id]);
            q('DELETE FROM guru_tugas WHERE id = ? AND guru_id = ?', [$id, uid()]);
            pesan('Tugas "' . h($t['judul']) . '" dihapus.' . ($t['penilaian_id'] ? ' Nilai yang sudah dikirim ke Penilaian tetap tersimpan.' : ''));
            ke('?p=tugas');
    }
    ke('?p=tugas_lihat&id=' . $id);
}

$n = ['kirim' => 0, 'telat' => 0, 'belum' => 0, 'nilai' => 0];
$baris = [];
$belum_nama = [];
foreach ($siswa as $s) {
    $k = $kirim[$s['member_id']] ?? null;
    unset($kirim[$s['member_id']]);
    $baris[] = [$s['member_id'], $s['member_name'], $k];
}
foreach ($kirim as $kd => $k) $baris[] = [(string)$kd, nama_siswa((string)$kd), $k];
foreach ($baris as [$kd, $nm, $k]) {
    if (!$k || !$k['dikirim']) { $n['belum']++; $belum_nama[] = $nm; continue; }
    $n[$k['telat'] ? 'telat' : 'kirim']++;
    if ($k['status'] === 'dinilai') $n['nilai']++;
}
$url = tugas_url_siswa($t);

kepala($t['judul'], 'kelas');
judul_baris('Tugas: ' . h($t['judul']), '<a class="tombol" href="?p=tugas">‹ Tugas</a>');
?>
<div class="kartu">
  <p style="margin-top:0">Kelas <b><?= h($t['kelas']) ?></b><?= $t['mapel'] ? ' · ' . h($t['mapel']) : '' ?> · batas <b><?= $t['batas'] ? h(ujian_waktu_teks($t['batas'])) : 'tidak ada' ?></b>
    <?= $t['status'] !== 'buka' ? '<span class="lencana st-tutup">ditutup</span>' : (tugas_lewat($t) ? '<span class="lencana st-draf">lewat batas' . ($t['terima_telat'] ? ', masih menerima (terlambat)' : '') . '</span>' : '<span class="lencana st-buka">dibuka</span>') ?></p>
  <?php if ($t['petunjuk']): ?><p class="kecil" style="white-space:pre-line"><?= h(mb_strimwidth((string)$t['petunjuk'], 0, 300, '…')) ?></p><?php endif; ?>
  <div class="layar-token" style="margin:.4rem 0">
    <div class="token-kiri"><div class="kecil redup">Buka di HP</div><div class="url-siswa"><?= h(preg_replace('#^https?://#', '', $url)) ?></div>
      <div class="kecil redup" style="margin-top:.4rem">Kode tugas</div><div class="kode-ujian"><?= h($t['kode']) ?></div></div>
    <div class="token-qr" id="qr" data-url="<?= h($url) ?>"></div>
  </div>
  <div class="token-tombol">
    <button type="button" class="utama kecil" id="tombol-bagikan">📤 Bagikan</button>
    <a class="tombol kecil" href="?p=tugas_form&amp;id=<?= $id ?>">⚙ Ubah tugas</a>
    <form method="post" class="sebaris"><?= input_token() ?><?php if ($t['status'] === 'buka'): ?><button name="aksi" value="tutup" class="kecil">■ Tutup</button><?php else: ?><button name="aksi" value="buka" class="kecil">▶ Buka lagi</button><?php endif; ?></form>
  </div>
</div>
<?php panel_bagikan(['id' => $id, 'jenis' => 'tugas', 'judul' => $t['judul'], 'kelas' => $t['kelas'], 'mapel' => (string)$t['mapel'],
    'kode' => $t['kode'], 'url' => $url, 'batas' => ujian_waktu_teks($t['batas']), 'tertib' => (int)$t['tampil_tertib']]); ?>

<div class="kartu">
  <div class="statistik">
    <span><b style="color:#1d6b33"><?= $n['kirim'] ?></b>terkirim</span>
    <span><b style="color:#7a5200"><?= $n['telat'] ?></b>terlambat</span>
    <span><b style="color:var(--merah)"><?= $n['belum'] ?></b>belum</span>
    <span><b><?= $n['nilai'] ?></b>dinilai</span>
  </div>
  <?php if ($belum_nama): ?>
  <button type="button" class="kecil" id="salin-belum" data-teks="<?= h("Yang belum mengumpulkan tugas *" . $t['judul'] . "*:\n" . implode("\n", array_map(fn($i, $x) => ($i + 1) . '. ' . $x, array_keys($belum_nama), $belum_nama)) . ($t['batas'] ? "\n\nBatas: " . ujian_waktu_teks($t['batas']) : '') . "\nLink: " . $url) ?>">📋 Salin nama yang belum (<?= count($belum_nama) ?>)</button>
  <span class="kecil redup" id="salin-belum-st"></span>
  <?php endif; ?>
  <ul class="daftar daftar-tugas">
  <?php foreach ($baris as [$kd, $nm, $k]): $sudah = $k && $k['dikirim']; ?>
    <li>
      <div><b><?= h($nm) ?></b>
        <?php if (!$sudah): ?> <span class="lencana merah">belum</span>
        <?php elseif ($k['status'] === 'perbaiki'): ?> <span class="lencana st-draf">↩ diminta perbaiki</span>
        <?php elseif ($k['telat']): ?> <span class="lencana st-draf">⏰ terlambat</span>
        <?php else: ?> <span class="lencana st-buka">✓ terkirim</span><?php endif; ?>
        <?php if ($sudah): ?><br><small class="redup"><?= h(ujian_waktu_teks($k['dikirim'])) ?> · <?= count(tugas_foto_daftar($k)) ?> foto<?= trim((string)$k['teks']) !== '' ? ' · ada teks' : '' ?><?= (int)$k['kali'] > 1 ? ' · kirim ke-' . (int)$k['kali'] : '' ?></small><?php endif; ?>
      </div>
      <?php if ($sudah): ?>
        <a class="<?= $k['nilai'] !== null ? 'nilai-tugas' : 'tombol kecil' ?>" href="?p=tugas_nilai&amp;id=<?= $id ?>&amp;kode=<?= h(rawurlencode($kd)) ?>"><?= $k['nilai'] !== null ? h(rtrim(rtrim((string)$k['nilai'], '0'), '.')) : 'Nilai' ?></a>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
  </ul>
</div>
<?php if ($n['nilai']): ?>
<form method="post" class="kartu formulir">
  <?= input_token() ?><input type="hidden" name="aksi" value="kirim_penilaian">
  <div class="baris2"><label>KKTP<input type="number" name="kktp" value="75" min="0" max="100"></label><div></div></div>
  <button class="utama lebar">Kirim <?= $n['nilai'] ?> nilai ke Penilaian<?= $t['penilaian_id'] ? ' (perbarui)' : '' ?></button>
  <p class="kecil redup">Foto kiriman dihapus otomatis <?= TUGAS_HAPUS_FOTO_HARI ?> hari setelah nilai dikirim, supaya hosting tidak penuh. Nilainya tetap tersimpan.</p>
</form>
<?php endif; ?>
<form method="post" style="margin-top:1rem" onsubmit="return confirm('Hapus tugas ini beserta semua foto kiriman siswa?')"><?= input_token() ?><input type="hidden" name="aksi" value="hapus"><button class="kecil bahaya">🗑 Hapus tugas</button></form>
<script>
window.addEventListener('load', function () {
  var q = document.getElementById('qr');
  try { var x = qrcode(0, 'M'); x.addData(q.dataset.url); x.make(); q.innerHTML = x.createSvgTag({ cellSize: 6, margin: 2, scalable: true }); } catch (e) {}
  var b = document.getElementById('salin-belum');
  if (b) b.onclick = function () {
    var t = b.dataset.teks, st = document.getElementById('salin-belum-st');
    (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject()).then(function () { st.textContent = '✓ disalin, tempel di grup WA'; })
      .catch(function () { var a = document.createElement('textarea'); a.value = t; document.body.append(a); a.select(); document.execCommand('copy'); a.remove(); st.textContent = '✓ disalin'; });
  };
});
</script>
<?php
$GLOBALS['skrip_tambahan'][] = 'aset/lib/qrcode.js';
$GLOBALS['skrip_tambahan'][] = 'aset/bagikan.js';
kaki();

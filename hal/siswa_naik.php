<?php
declare(strict_types=1);

$lama = tahun_aktif();
$baru = (string)(get('ke') ?: tahun_berikut($lama));
$kelas = daftar_kelas($lama);

/** Saran kelas tujuan: VII A → VIII A, VIII A → IX A, IX A → lulus. */
function saran_naik(string $k): string
{
    $t = tingkat_kelas($k);
    if ($t === 9 || $t === 12 || $t === 6) return '__lulus';
    $rom = [7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII', 1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI'];
    if (!isset($rom[$t])) return $k;
    return preg_replace('/^\s*(?:kelas\s*)?(xii|xi|x|ix|viii|vii|vi|v|iv|iii|ii|i|\d{1,2})\b/i', $rom[$t + 1] ?? (string)($t + 1), $k);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baru = (string)post('ke');
    if (!preg_match('/^\d{4}\/\d{4}$/', $baru) || $baru === $lama) { pesan('Tahun ajaran baru tidak valid.', 'galat'); ke('?p=siswa_naik'); }
    $tujuan = (array)($_POST['tujuan'] ?? []);
    $n = ['naik' => 0, 'lulus' => 0];
    db()->beginTransaction();
    foreach ($kelas as $k) {
        $ke = trim((string)($tujuan[$k] ?? ''));
        if ($ke === '' || $ke === '__lewati') continue;
        foreach (siswa_kelas($k, $lama) as $s) {
            if ($ke === '__lulus') {
                q("UPDATE guru_siswa SET status = 'lulus', diubah = ? WHERE kode = ?", [sekarang(), $s['member_id']]);
                $n['lulus']++;
            } else {
                $ke = rapikan_kelas(mb_substr($ke, 0, 50));
                pastikan_kelas($ke, $baru);
                q('INSERT INTO guru_kelas_siswa (tahun, kode, kelas, absen) VALUES (?,?,?,NULL) ON DUPLICATE KEY UPDATE kelas = VALUES(kelas)', [$baru, $s['member_id'], $ke]);
                $n['naik']++;
            }
        }
    }
    // kelas baru tingkat terendah (siswa baru) dibuat kosong bila diminta
    foreach (array_filter(array_map('trim', explode(',', (string)post('kelas_kosong')))) as $kk) pastikan_kelas(rapikan_kelas(mb_substr($kk, 0, 50)), $baru);
    setelan_simpan('tahun_ajaran:' . uid(), $baru);
    db()->commit();
    pesan("Tahun ajaran $baru aktif. {$n['naik']} siswa naik kelas, {$n['lulus']} lulus. Data tahun $lama tetap tersimpan.");
    ke('?p=kelas');
}

kepala('Naik kelas', 'kelas');
judul_baris('Naik kelas', '<a class="tombol" href="?p=kelas">‹ Kelas</a>');
?>
<form method="post" class="kartu formulir">
  <?= input_token() ?>
  <p>Pindahkan siswa dari tahun <b><?= h($lama) ?></b> ke tahun ajaran baru. Absensi dan nilai tahun lama tidak berubah.</p>
  <label>Tahun ajaran baru<input name="ke" value="<?= h($baru) ?>" pattern="\d{4}/\d{4}" required></label>
  <table class="tabel-naik">
    <tr><th>Kelas <?= h($lama) ?></th><th>Siswa</th><th>Menjadi</th></tr>
    <?php foreach ($kelas as $k): $s = saran_naik($k); ?>
    <tr><td><b><?= h($k) ?></b></td><td><?= count(siswa_kelas($k, $lama)) ?></td>
      <td><input name="tujuan[<?= h($k) ?>]" value="<?= $s === '__lulus' ? '' : h($s) ?>" list="dl-naik" placeholder="<?= $s === '__lulus' ? 'lulus' : '' ?>" data-lulus="<?= $s === '__lulus' ? '1' : '' ?>"></td></tr>
    <?php endforeach; ?>
  </table>
  <datalist id="dl-naik"><option value="__lulus">Lulus</option><option value="__lewati">Lewati (tidak dipindah)</option></datalist>
  <p class="kecil redup">Isi <code>__lulus</code> untuk kelas akhir (otomatis untuk kelas IX), atau <code>__lewati</code> agar tidak dipindah. Kelas tujuan boleh sama untuk siswa tinggal kelas, lalu pindahkan satu per satu.</p>
  <label>Kelas baru yang kosong (untuk siswa baru)<input name="kelas_kosong" placeholder="mis. VII A, VII B, VII C"></label>
  <button class="utama lebar" onclick="document.querySelectorAll('[data-lulus=\'1\']').forEach(function(i){if(!i.value)i.value='__lulus'}); return confirm('Pindahkan ke tahun ajaran baru?')">Naikkan kelas</button>
</form>
<?php
kaki();

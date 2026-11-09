<?php
/* Halaman masuk ujian untuk siswa (tanpa akun). */
declare(strict_types=1);

$kode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($_REQUEST['k'] ?? '')));
$u = $kode !== '' ? satu('SELECT * FROM guru_ujian WHERE kode = ?', [$kode]) : null;
$galat = '';

function alat_siswa(): string
{
    $c = (string)($_COOKIE['sgu_alat'] ?? '');
    if (!preg_match('/^[a-f0-9]{32}$/', $c)) {
        $c = bin2hex(random_bytes(16));
        setcookie('sgu_alat', $c, ['expires' => time() + 400 * 86400, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
        $_COOKIE['sgu_alat'] = $c;
    }
    return $c;
}

function ujian_bisa_masuk(array $u): string
{
    if ($u['status'] === 'draf') return 'Ujian belum dibuka oleh guru. Tunggu sebentar lalu muat ulang.';
    if ($u['status'] === 'tutup') return 'Ujian sudah ditutup.';
    if ($u['mulai'] && strtotime($u['mulai']) > time()) return 'Ujian baru bisa dimulai pukul ' . substr($u['mulai'], 11, 5) . ', ' . tgl(substr($u['mulai'], 0, 10)) . '.';
    if ($u['selesai'] && strtotime($u['selesai']) <= time()) return 'Batas waktu ujian sudah lewat.';
    return '';
}

$alat = alat_siswa();
// HP dipakai bergantian: lepaskan nama sebelumnya dari HP ini (jawabannya tetap tersimpan; ia bisa lanjut lagi di HP lain setelah guru menekan "Buka ulang")
if ($u && get('ganti') && isset($_SESSION['ujian'][(int)$u['id']])) {
    q('UPDATE guru_ujian_peserta SET perangkat = NULL WHERE ujian_id = ? AND kode = ? AND selesai IS NULL', [$u['id'], $_SESSION['ujian'][(int)$u['id']]]);
    unset($_SESSION['ujian'][(int)$u['id']]);
    ke('?p=u&k=' . $u['kode']);
}
if ($u && $_SERVER['REQUEST_METHOD'] === 'POST' && post('aksi') !== 'mulai') {
    $siswa = (string)post('siswa');
    $_SESSION['coba_token'] = $_SESSION['coba_token'] ?? ['n' => 0, 't' => 0];
    $ct = &$_SESSION['coba_token'];
    $kelas_siswa = array_column(siswa_kelas((string)$u['kelas'], (string)($u['tahun'] ?: tahun_aktif((int)$u['guru_id'])), (int)$u['guru_id']), 'member_name', 'member_id');
    if (($m = ujian_bisa_masuk($u)) !== '') $galat = $m;
    elseif (!isset($kelas_siswa[$siswa])) $galat = 'Pilih namamu dari daftar.';
    elseif (!ujian_latihan($u) && $ct['n'] >= 5 && time() - $ct['t'] < 60) $galat = 'Terlalu sering salah token. Tunggu 1 menit.';
    elseif (!ujian_latihan($u) && !ujian_token_cocok($u, (string)post('token'))) {
        $ct['n'] = $ct['n'] + 1; $ct['t'] = time();
        $galat = 'Token salah. Lihat token terbaru di papan/layar guru.';
    } else {
        $ct = ['n' => 0, 't' => 0];
        $p = satu('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ?', [$u['id'], $siswa]);
        $alat_hash = hash('sha256', $alat);
        if ($p && $p['selesai'] && !ujian_latihan($u)) {
            $galat = 'Kamu sudah mengumpulkan ujian ini. Bila ada masalah, hubungi guru.';
        } elseif ($p && !$p['selesai'] && $p['perangkat'] && !hash_equals((string)$p['perangkat'], $alat_hash)) {
            $galat = 'Nama ini sedang dipakai di HP lain. Bila HP-mu bermasalah, minta guru menekan "Buka ulang".';
        } elseif ($p && $p['selesai']) {
            // latihan yang sudah selesai: buka halaman hasil (ada tombol ulangi)
            q('UPDATE guru_ujian_peserta SET perangkat = ? WHERE ujian_id = ? AND kode = ?', [$alat_hash, $u['id'], $siswa]);
            session_regenerate_id(true);
            $_SESSION['ujian'][(int)$u['id']] = $siswa;
            ke('?p=u_kerja&id=' . $u['id']);
        } elseif ((!$p || !$p['mulai']) && $u['tampil_tertib']) {
            // baca tata tertib dulu; waktu baru berjalan setelah menekan Mulai
            $_SESSION['ujian_siap'][(int)$u['id']] = ['kode' => $siswa, 't' => time()];
            ke('?p=u&k=' . $u['kode'] . '&tertib=1');
        } else {
            ujian_mulai_peserta($u, $siswa, $alat_hash);
            session_regenerate_id(true);
            $_SESSION['ujian'][(int)$u['id']] = $siswa;
            ke('?p=u_kerja&id=' . $u['id']);
        }
    }
}

// tata tertib → Mulai
$siap = $u ? ($_SESSION['ujian_siap'][(int)$u['id']] ?? null) : null;
if ($siap && time() - (int)$siap['t'] > 1800) { unset($_SESSION['ujian_siap'][(int)$u['id']]); $siap = null; }
if ($u && $siap && $_SERVER['REQUEST_METHOD'] === 'POST' && post('aksi') === 'mulai') {
    $alat_hash = hash('sha256', $alat);
    $p = satu('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ?', [$u['id'], $siap['kode']]);
    if (($m = ujian_bisa_masuk($u)) !== '') $galat = $m;
    elseif (!post('setuju')) $galat = 'Centang dulu “Saya sudah membaca”.';
    elseif ($p && $p['perangkat'] && !$p['selesai'] && !hash_equals((string)$p['perangkat'], $alat_hash)) $galat = 'Nama ini sedang dipakai di HP lain.';
    else {
        ujian_mulai_peserta($u, (string)$siap['kode'], $alat_hash);
        unset($_SESSION['ujian_siap'][(int)$u['id']]);
        session_regenerate_id(true);
        $_SESSION['ujian'][(int)$u['id']] = (string)$siap['kode'];
        ke('?p=u_kerja&id=' . $u['id']);
    }
}
if ($u && get('batal_tertib')) { unset($_SESSION['ujian_siap'][(int)$u['id']]); ke('?p=u&k=' . $u['kode']); }

// hasil yang sudah dikumpulkan dari HP ini (untuk melihat nilai & pembahasan)
$hasil_hp = null;
if ($u) {
    $hasil_hp = satu('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ? AND perangkat = ? AND selesai IS NOT NULL ORDER BY selesai DESC LIMIT 1', [$u['id'], hash('sha256', $alat)]);
    if ($hasil_hp && get('lihat')) {
        session_regenerate_id(true);
        $_SESSION['ujian'][(int)$u['id']] = (string)$hasil_hp['kode'];
        ke('?p=u_kerja&id=' . $u['id']);
    }
}

// sudah masuk di perangkat ini → tombol lanjut
$lanjut = null;
if ($u && isset($_SESSION['ujian'][(int)$u['id']])) {
    $lanjut = satu('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ? AND selesai IS NULL', [$u['id'], $_SESSION['ujian'][(int)$u['id']]]);
}

kepala($u ? $u['judul'] : 'Masuk ujian', '', true);
?>
<div class="siswa-wadah">
  <header class="siswa-kop"><img src="aset/logo.png" alt=""><div><small><?= h((string)k('sekolah', '')) ?></small><b><?= $u && ujian_latihan($u) ? 'Latihan Soal' : 'Ujian Online' ?></b></div></header>
  <?php if (!$u): ?>
  <form method="get" class="kartu formulir">
    <input type="hidden" name="p" value="u">
    <?php if ($kode !== ''): ?><div class="pesan galat">Kode ujian tidak ditemukan.</div><?php endif; ?>
    <label>Kode ujian<input name="k" class="isian-kode" maxlength="10" autocomplete="off" autocapitalize="characters" required placeholder="mis. K7M2QX"></label>
    <button class="utama lebar">Lanjut</button>
  </form>
  <?php else: $alasan = ujian_bisa_masuk($u); $siswa = siswa_kelas((string)$u['kelas'], (string)($u['tahun'] ?: tahun_aktif((int)$u['guru_id'])), (int)$u['guru_id']); ?>
  <div class="kartu">
    <h2 style="margin:0 0 .3rem"><?= h($u['judul']) ?></h2>
    <p class="redup" style="margin:0"><?= ujian_latihan($u) ? '<span class="lencana">latihan</span> ' : '' ?>Kelas <?= h($u['kelas']) ?><?= $u['mapel'] ? ' · ' . h($u['mapel']) : '' ?> · <?= count(ujian_soal($u)) ?> soal · <?= (int)$u['durasi'] ?> menit</p>
  </div>
  <?php if ($galat): ?><div class="pesan galat"><?= h($galat) ?></div><?php endif; ?>
  <?php if ($siap): ?>
  <form method="post" class="kartu tata-tertib">
    <?= input_token() ?><input type="hidden" name="k" value="<?= h($u['kode']) ?>"><input type="hidden" name="aksi" value="mulai">
    <p style="margin:0 0 .4rem">Halo, <b><?= h(nama_siswa((string)$siap['kode'])) ?></b> 👋</p>
    <h3 style="margin:0 0 .2rem">📋 Tata tertib <?= ujian_latihan($u) ? 'latihan' : 'ujian' ?></h3>
    <p class="kecil redup" style="margin:0 0 .3rem">Baca dulu sampai selesai ya.</p>
    <ol class="poin-tertib"><?php foreach (ujian_tata_tertib($u) as $t): ?><li><?= $t ?></li><?php endforeach; ?></ol>
    <label class="setuju-tertib"><input type="checkbox" name="setuju" value="1" required> <span>Saya sudah membaca dan <b>berjanji jujur</b> mengerjakan <?= ujian_latihan($u) ? 'latihan' : 'ujian' ?> ini.</span></label>
    <button class="utama lebar" id="tombol-mulai" style="margin-top:.7rem">Mulai mengerjakan</button>
    <p class="kecil redup" style="text-align:center;margin:.4rem 0 0">Waktu mulai dihitung setelah tombol ditekan · <a href="?p=u&amp;k=<?= h($u['kode']) ?>&amp;batal_tertib=1">bukan saya</a></p>
  </form>
  <?php elseif ($lanjut): ?>
    <a class="tombol utama lebar" href="?p=u_kerja&amp;id=<?= (int)$u['id'] ?>">Lanjutkan mengerjakan (<?= h(nama_siswa($lanjut['kode'])) ?>)</a>
    <p class="kecil" style="text-align:center;margin-top:1rem">Bukan <?= h(nama_siswa($lanjut['kode'])) ?>? <a href="?p=u&amp;k=<?= h($u['kode']) ?>&amp;ganti=1" onclick="return confirm('Keluarkan nama ini dari HP ini? Jawabannya tetap tersimpan.')">Masuk dengan nama lain</a></p>
  <?php elseif ($hasil_hp && $alasan !== ''): ?>
    <div class="kartu" style="text-align:center">
      <p style="margin:0 0 .5rem"><b><?= h(nama_siswa((string)$hasil_hp['kode'])) ?></b> sudah mengumpulkan pukul <?= h(substr((string)$hasil_hp['selesai'], 11, 5)) ?>.</p>
      <?php if (ujian_hasil_terbuka($u)): ?><a class="tombol utama lebar" href="?p=u&amp;k=<?= h($u['kode']) ?>&amp;lihat=1"><?= ujian_latihan($u) ? 'Lihat hasil / ulangi latihan' : 'Lihat nilai &amp; pembahasan' ?></a>
      <?php elseif ($u['tampil_hasil']): ?><p class="kecil redup" style="margin:0">Nilai dan pembahasan bisa dilihat di sini setelah guru menutup ujian.</p><?php endif; ?>
    </div>
    <?php if ($alasan !== ''): ?><div class="pesan"><?= h($alasan) ?></div><?php endif; ?>
  <?php elseif ($alasan !== ''): ?>
    <div class="pesan"><?= h($alasan) ?></div>
    <a class="tombol lebar" href="?p=u&amp;k=<?= h($u['kode']) ?>">↻ Muat ulang</a>
  <?php else: ?>
  <form method="post" class="kartu formulir">
    <?= input_token() ?><input type="hidden" name="k" value="<?= h($u['kode']) ?>">
    <label>Nama kamu
      <select name="siswa" required><option value="">— pilih nama —</option>
        <?php foreach ($siswa as $i => $s): ?><option value="<?= h($s['member_id']) ?>" <?= post('siswa') === $s['member_id'] ? 'selected' : '' ?>><?= ($s['absen'] ?: $i + 1) . '. ' . h($s['member_name']) ?></option><?php endforeach; ?>
      </select></label>
    <?php if (!ujian_latihan($u)): ?>
    <label>Token (4 angka dari guru)<input name="token" class="isian-token" inputmode="numeric" pattern="\d{4}" maxlength="4" autocomplete="off" required></label>
    <?php endif; ?>
    <button class="utama lebar">Lanjut</button>
    <?php if ($hasil_hp && $u['tampil_hasil'] && !ujian_hasil_terbuka($u)): ?><p class="kecil redup">Hasil <?= h(nama_siswa((string)$hasil_hp['kode'])) ?> bisa dilihat di HP ini setelah guru menutup ujian.</p><?php endif; ?>
  </form>
  <?php if ($hasil_hp && ujian_hasil_terbuka($u)): ?><a class="tombol lebar" href="?p=u&amp;k=<?= h($u['kode']) ?>&amp;lihat=1">Lihat hasil <?= h(nama_siswa((string)$hasil_hp['kode'])) ?></a><?php endif; ?>
  <?php endif; ?>
  <?php endif; ?>
</div>
<?php
kaki(true);

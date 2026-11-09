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
if ($u && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswa = (string)post('siswa');
    $_SESSION['coba_token'] = $_SESSION['coba_token'] ?? ['n' => 0, 't' => 0];
    $ct = &$_SESSION['coba_token'];
    $kelas_siswa = array_column(siswa_kelas((string)$u['kelas'], (string)($u['tahun'] ?: tahun_aktif((int)$u['guru_id'])), (int)$u['guru_id']), 'member_name', 'member_id');
    if (($m = ujian_bisa_masuk($u)) !== '') $galat = $m;
    elseif (!isset($kelas_siswa[$siswa])) $galat = 'Pilih namamu dari daftar.';
    elseif ($ct['n'] >= 5 && time() - $ct['t'] < 60) $galat = 'Terlalu sering salah token. Tunggu 1 menit.';
    elseif (!ujian_token_cocok($u, (string)post('token'))) {
        $ct['n'] = $ct['n'] + 1; $ct['t'] = time();
        $galat = 'Token salah. Lihat token terbaru di papan/layar guru.';
    } else {
        $ct = ['n' => 0, 't' => 0];
        $p = satu('SELECT * FROM guru_ujian_peserta WHERE ujian_id = ? AND kode = ?', [$u['id'], $siswa]);
        if ($p && $p['selesai']) {
            $galat = 'Kamu sudah mengumpulkan ujian ini. Bila ada masalah, hubungi guru.';
        } elseif ($p && $p['perangkat'] && !hash_equals((string)$p['perangkat'], hash('sha256', $alat))) {
            $galat = 'Nama ini sedang dipakai di HP lain. Bila HP-mu bermasalah, minta guru menekan "Buka ulang".';
        } else {
            if (!$p || !$p['mulai']) {
                $batas = time() + (int)$u['durasi'] * 60;
                if ($u['selesai']) $batas = min($batas, strtotime($u['selesai']));
                q('INSERT INTO guru_ujian_peserta (ujian_id, kode, perangkat, mulai, batas, urutan, jawaban, ragu, terakhir) VALUES (?,?,?,?,?,?,?,?,?)
                   ON DUPLICATE KEY UPDATE perangkat = VALUES(perangkat), mulai = VALUES(mulai), batas = VALUES(batas), urutan = VALUES(urutan), terakhir = VALUES(terakhir)',
                    [$u['id'], $siswa, hash('sha256', $alat), sekarang(), date('Y-m-d H:i:s', $batas), json_encode(ujian_buat_urutan($u)), '{}', '{}', sekarang()]);
            } else {
                q('UPDATE guru_ujian_peserta SET perangkat = ?, terakhir = ? WHERE ujian_id = ? AND kode = ?', [hash('sha256', $alat), sekarang(), $u['id'], $siswa]);
            }
            session_regenerate_id(true);
            $_SESSION['ujian'][(int)$u['id']] = $siswa;
            ke('?p=u_kerja&id=' . $u['id']);
        }
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
  <header class="siswa-kop"><img src="aset/logo.png" alt=""><div><small><?= h((string)k('sekolah', '')) ?></small><b>Ujian Online</b></div></header>
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
    <p class="redup" style="margin:0">Kelas <?= h($u['kelas']) ?><?= $u['mapel'] ? ' · ' . h($u['mapel']) : '' ?> · <?= count(ujian_soal($u)) ?> soal · <?= (int)$u['durasi'] ?> menit</p>
  </div>
  <?php if ($galat): ?><div class="pesan galat"><?= h($galat) ?></div><?php endif; ?>
  <?php if ($lanjut): ?>
    <a class="tombol utama lebar" href="?p=u_kerja&amp;id=<?= (int)$u['id'] ?>">Lanjutkan mengerjakan (<?= h(nama_siswa($lanjut['kode'])) ?>)</a>
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
    <label>Token (4 angka dari guru)<input name="token" class="isian-token" inputmode="numeric" pattern="\d{4}" maxlength="4" autocomplete="off" required></label>
    <button class="utama lebar">Mulai ujian</button>
    <ul class="kecil redup aturan-ujian">
      <li>Jawaban tersimpan otomatis setiap kamu memilih. Bila sinyal putus, lanjutkan saja; jawaban dikirim saat tersambung lagi.</li>
      <li>Jangan keluar dari halaman ujian (membuka WA, Google, dsb.) — tercatat di layar guru.</li>
      <li>Satu nama hanya bisa dipakai di satu HP.</li>
    </ul>
  </form>
  <?php endif; ?>
  <?php endif; ?>
</div>
<?php
kaki(true);

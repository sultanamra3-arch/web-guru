<?php
declare(strict_types=1);

const MENU = [
    'beranda' => ['Beranda', 'beranda'],
    'kelas' => ['Kelas', 'kelas'],
    'soal' => ['Soal', 'soal'],
    'perangkat' => ['Perangkat', 'perangkat'],
    'lainnya' => ['Lainnya', 'lainnya'],
];

function kepala(string $judul, string $aktif = '', bool $polos = false): void
{
    $u = pengguna();
    ?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<script>
/* Saat dibuka sebagai aplikasi terpasang (ikon di layar HP), matikan zoom; di browser biasa zoom tetap bisa. */
(function () {
  var apl = (window.matchMedia && matchMedia('(display-mode: standalone), (display-mode: fullscreen), (display-mode: minimal-ui)').matches) || navigator.standalone;
  if (!apl) return;
  document.querySelector('meta[name=viewport]').setAttribute('content', 'width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover');
  document.documentElement.classList.add('apl');
  // iPhone mengabaikan user-scalable=no: tahan gerakan cubit
  document.addEventListener('gesturestart', function (e) { e.preventDefault(); });
})();
</script>
<meta name="theme-color" content="#2A2C84">
<meta name="robots" content="noindex, nofollow">
<title><?= h($judul) ?> · SiGuru</title>
<meta name="application-name" content="SiGuru">
<meta name="apple-mobile-web-app-title" content="SiGuru">
<link rel="manifest" href="manifest.webmanifest?v=<?= VERSI ?>">
<link rel="icon" href="aset/favicon.png">
<link rel="apple-touch-icon" href="aset/apple-touch-icon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500;600;700&display=swap">
<link rel="stylesheet" href="aset/gaya.css?v=<?= VERSI ?>">
<link rel="stylesheet" href="aset/guru.css?v=<?= VERSI ?>">
</head>
<body class="<?= $polos ? 'polos' : '' ?>">
<?php if (!$polos): ?>
<header class="atas">
  <a class="merek" href="?p=beranda"><img src="aset/logo.png" alt="Logo"> <span><small><?= h((string)k('sekolah', 'SMP Negeri 6 Keritang')) ?></small><b>SiGuru</b></span></a>
  <?php if ($u): ?>
  <form method="post" action="?p=keluar" class="keluar"><?= input_token() ?><button title="Keluar">Keluar</button></form>
  <?php endif; ?>
</header>
<?php if ($u): ?>
<nav class="menu">
  <?php foreach (MENU as $p => [$t, $i]): ?>
  <a href="?p=<?= $p ?>" class="<?= $aktif === $p ? 'aktif' : '' ?>"><span class="ik"><?= ikon($i) ?></span><?= $t ?></a>
  <?php endforeach; ?>
</nav>
<?php endif; ?>
<?php endif; ?>
<main>
<?php foreach (ambil_pesan() as [$j, $t]): ?>
  <div class="pesan <?= h($j) ?> tak-cetak"><?= $t ?></div>
<?php endforeach;
}

function kaki(bool $polos = false): void
{
    ?>
</main>
<?php if (!$polos): ?><footer class="bawah tak-cetak">SiGuru v<?= VERSI ?> · <?= h((string)k('sekolah', '')) ?></footer><?php endif; ?>
<script src="aset/guru.js?v=<?= VERSI ?>"></script>
<?php foreach ((array)($GLOBALS['skrip_tambahan'] ?? []) as $sk): ?><script src="<?= h($sk) ?>?v=<?= VERSI ?>"></script>
<?php endforeach; ?>
</body>
</html>
<?php
}

function pilihan(array $daftar, $terpilih, ?string $kolom_id = null, ?string $kolom_nama = null): string
{
    $o = '';
    // daftar biasa ['a','b'] → nilai = teks; daftar berkunci (termasuk kunci angka seperti 7/8/9) → nilai = kunci
    $berkunci = !array_is_list($daftar);
    foreach ($daftar as $k => $r) {
        $id = $kolom_id ? $r[$kolom_id] : ($berkunci || is_string($k) ? $k : $r);
        $nm = $kolom_nama ? $r[$kolom_nama] : $r;
        $s = ((string)$id === (string)$terpilih) ? ' selected' : '';
        $o .= '<option value="' . h($id) . '"' . $s . '>' . h($nm) . '</option>';
    }
    return $o;
}

function judul_baris(string $judul, string $kanan = ''): void
{
    echo '<div class="judul-baris"><h1>' . $judul . '</h1>' . ($kanan ? '<span>' . $kanan . '</span>' : '') . '</div>';
}

/** Panel "Bagikan" (tulisan + gambar kartu) untuk ujian/latihan/tugas; perilakunya di aset/bagikan.js. */
function panel_bagikan(array $d): void
{
    $jenis = (string)$d['jenis'];
    // nama guru & mata pelajaran ikut di tulisan dan gambar (mapel kosong → mapel di Profil guru)
    $saya = pengguna() ?? [];
    $d['guru'] = (string)($saya['nama'] ?? '');
    if (trim((string)($d['mapel'] ?? '')) === '') $d['mapel'] = trim((string)($saya['mapel'] ?? ''));
    $attr = '';
    foreach ($d as $k => $v) $attr .= ' data-' . $k . '="' . h((string)$v) . '"';
    $attr .= ' data-sekolah="' . h((string)k('sekolah', '')) . '"';
    $nama = ['ujian' => 'ujian', 'latihan' => 'latihan', 'tugas' => 'tugas', 'kuis' => 'kuis'][$jenis] ?? 'ujian';
    ?>
<div class="kartu panel-bagikan" id="panel-bagikan" hidden<?= $attr ?>>
  <div class="judul-panel"><h3 style="margin:0">📤 Bagikan <?= $nama ?></h3><button type="button" class="kecil" id="tutup-bagikan" aria-label="Tutup">✕</button></div>
  <?php if ($jenis === 'ujian'): ?>
  <label class="centang"><input type="checkbox" id="sertakan-token"> <b>Sertakan token</b> <small class="redup">(untuk ujian dari rumah)</small></label>
  <div class="pilih-token" hidden>
    <?php if ((int)($d['token-menit'] ?? 0) > 0): ?>
    <label class="centang kecil"><input type="radio" name="jenis_token" value="tetap" checked> <span>Ubah ke <b>token tetap</b> selama ujian dibuka <span class="lencana">disarankan</span></span></label>
    <label class="centang kecil"><input type="radio" name="jenis_token" value="sekarang"> <span>Token sekarang saja (berganti tiap <?= (int)$d['token-menit'] ?> menit)</span></label>
    <?php else: ?><p class="kecil redup" style="margin:.2rem 0">Token ujian ini sudah tetap (tidak berganti).</p><?php endif; ?>
  </div>
  <?php endif; ?>
  <small class="redup">Pesan yang dikirim:</small>
  <div class="pratinjau-pesan" id="pratinjau-pesan"></div>
  <small class="redup">Gambar yang ikut dikirim:</small>
  <canvas id="kartu-bagikan" width="1080" height="1200" class="kartu-gambar"></canvas>
  <button type="button" class="utama lebar" id="bagikan-sekarang">📤 Bagikan (tulisan + gambar)</button>
  <div class="tombol-bagikan-lain">
    <a class="tombol kecil" id="bagikan-wa" target="_blank" rel="noopener">WhatsApp</a>
    <button type="button" class="kecil" id="salin-teks">Salin teks</button>
    <button type="button" class="kecil" id="unduh-gambar">Unduh gambar</button>
  </div>
  <p class="kecil redup" id="status-bagikan"></p>
</div>
<?php
}

/**
 * Panel "Status ke WA": daftar nama yang sudah / belum mengumpulkan (tanpa nilai).
 * $d: jenis (ujian|latihan|tugas), id, judul, kelas, mapel, url, kode, batas
 */
function panel_status(array $d): void
{
    $saya = pengguna() ?? [];
    $d['guru'] = (string)($saya['nama'] ?? '');
    if (trim((string)($d['mapel'] ?? '')) === '') $d['mapel'] = trim((string)($saya['mapel'] ?? ''));
    $d['api'] = $d['jenis'] === 'tugas' ? '?p=api_tugas' : '?p=api_ujian';
    $attr = '';
    foreach ($d as $k => $v) $attr .= ' data-' . $k . '="' . h((string)$v) . '"';
    ?>
<div class="kartu panel-bagikan" id="panel-status" hidden<?= $attr ?>>
  <div class="judul-panel"><h3 style="margin:0">📋 Status pengumpulan</h3><button type="button" class="kecil" id="tutup-status" aria-label="Tutup">✕</button></div>
  <label class="centang"><input type="checkbox" id="status-sudah" checked> <b>Sertakan daftar yang sudah</b> <small class="redup">(matikan agar pesan pendek)</small></label>
  <small class="redup">Pesan untuk grup WA kelas (nilai tidak ikut):</small>
  <div class="pratinjau-pesan" id="status-pesan">Memuat…</div>
  <div class="tombol-bagikan-lain">
    <a class="tombol kecil utama" id="status-wa" target="_blank" rel="noopener">Kirim ke WA</a>
    <button type="button" class="kecil" id="status-salin">Salin teks</button>
    <button type="button" class="kecil" id="status-segar">↻ Perbarui</button>
  </div>
  <p class="kecil redup" id="status-info"></p>
</div>
<?php
}

<?php
declare(strict_types=1);

// Menu bawah (v1.44): tombol "Buat" di tengah; Soal masuk ke tab Perangkat
const MENU = [
    'beranda' => ['Beranda', 'beranda'],
    'kelas' => ['Kelas', 'kelas'],
    'buat' => ['Buat', 'tambah'],
    'perangkat' => ['Perangkat', 'perangkat'],
    'lainnya' => ['Lainnya', 'lainnya'],
];

/** Isi lembar "＋ Buat" (judul, ikon, alamat). */
const MENU_BUAT = [
    ['Absen', 'absensi', '?p=absensi'], ['Ujian', 'ujian', '?p=ujian'], ['Tugas', 'tugas', '?p=tugas_form'], ['Kuis cepat', 'kuis', '?p=kuis'],
    ['Soal AI', 'ai', '?p=soal_ai'], ['Modul ajar', 'modul', '?p=dokumen_buat&jenis=modul'], ['Presentasi', 'presentasi', '?p=dokumen_buat&jenis=presentasi'], ['Kisi-kisi', 'kisi', '?p=kisi'],
];

/** Inisial nama tanpa gelar: "Budi Santoso, S.Pd." → "BS" */
function inisial(string $nama): string
{
    $nama = trim(preg_replace('/,.*$/', '', $nama));
    $kata = array_values(array_filter(preg_split('/\s+/', $nama), fn($k) => $k !== '' && !preg_match('/\.$/', $k)));
    $i = '';
    foreach (array_slice($kata, 0, 2) as $k) $i .= mb_strtoupper(mb_substr($k, 0, 1));
    return $i ?: 'G';
}

/** Jumlah hal yang "perlu tindakan" (angka di menu Beranda); disimpan di sesi 60 detik agar ringan. */
function angka_beranda(): int
{
    $c = $_SESSION['angka_beranda'] ?? null;
    if ($c && $c[0] > time() - 60) return (int)$c[1];
    $n = count(perlu_tindakan());
    $_SESSION['angka_beranda'] = [time(), $n];
    return $n;
}

function kepala(string $judul, string $aktif = '', bool $polos = false): void
{
    $u = pengguna();
    ?><!DOCTYPE html>
<html lang="id"<?= $u && setelan('huruf:' . uid()) === 'besar' ? ' class="huruf-besar"' : '' ?>>
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
<link rel="icon" href="aset/favicon.png?v=3">
<link rel="apple-touch-icon" href="aset/apple-touch-icon.png?v=3">
<link rel="preload" href="aset/huruf/quicksand-500.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="aset/huruf/quicksand-700.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="aset/gaya.css?v=<?= VERSI ?>">
<link rel="stylesheet" href="aset/guru.css?v=<?= VERSI ?>">
<?php if ($u && !$polos): ?>
<script type="speculationrules">
<?= json_encode(siap_cepat_aturan(), JSON_UNESCAPED_SLASHES) ?>

</script>
<?php endif; ?>
</head>
<body class="<?= $polos ? 'polos' : '' ?>">
<div id="garis-muat" class="garis-muat tak-cetak" aria-hidden="true"></div>
<?php if (!$polos): ?>
<header class="atas">
  <a class="merek" href="?p=beranda"><img src="aset/logo.png" alt="Logo"> <span><small><?= h((string)k('sekolah', 'SMP Negeri 6 Keritang')) ?></small><b>SiGuru</b></span></a>
  <?php if ($u): ?>
  <a class="inisial" href="?p=profil" title="<?= h($u['nama']) ?> · profil"><?= h(inisial((string)$u['nama'])) ?></a>
  <?php endif; ?>
</header>
<?php if ($u): $aktif = $aktif === 'soal' ? 'perangkat' : $aktif; $angka = angka_beranda(); ?>
<nav class="menu">
  <?php foreach (MENU as $p => [$t, $i]): ?>
  <?php if ($p === 'buat'): ?>
  <button type="button" class="menu-buat" id="tombol-buat" aria-haspopup="dialog" aria-controls="lembar-buat"><span class="ik"><?= ikon($i) ?></span><?= $t ?></button>
  <?php else: ?>
  <a href="?p=<?= $p ?>" class="<?= $aktif === $p ? 'aktif' : '' ?>"><span class="ik"><?= ikon($i) ?><?= $p === 'beranda' && $angka ? '<b class="angka-menu">' . $angka . '</b>' : '' ?></span><?= $t ?></a>
  <?php endif; ?>
  <?php endforeach; ?>
</nav>
<div class="tirai-buat tak-cetak" id="lembar-buat" hidden role="dialog" aria-label="Buat baru">
  <div class="lembar">
    <b>Buat baru</b>
    <div class="ikon-kisi">
      <?php foreach (MENU_BUAT as [$t, $i, $url]): ?><a href="<?= h($url) ?>"><span class="ikon-kotak"><?= ikon($i) ?></span><?= h($t) ?></a><?php endforeach; ?>
    </div>
    <button type="button" class="lebar" data-tutup-buat>Tutup</button>
  </div>
</div>
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

/**
 * Kolom pilihan mata pelajaran dari Profil guru (+ jadwal).
 * Punya ≥2 mapel dan belum ada nilai → mulai dari "— Pilih mapel —" (wajib dipilih).
 * Punya 1 mapel → langsung terpilih. "Lainnya…" memunculkan kotak isian.
 */
function pilih_mapel(?string $nilai = '', bool $wajib = true, string $atribut = ''): string
{
    $nilai = trim((string)$nilai);
    $daftar = mapel_saya();
    if (!$daftar) return '<input name="mapel" value="' . h($nilai) . '"' . ($wajib ? ' required' : '') . ' placeholder="mis. Informatika"' . $atribut . '>';
    if ($nilai === '' && count($daftar) === 1) $nilai = $daftar[0];
    $ada = $nilai === '' || in_array($nilai, $daftar, true);
    $o = '<span class="pilih-mapel-bungkus"><select name="mapel" class="pilih-mapel"' . ($wajib ? ' required' : '') . $atribut . '>';
    $o .= '<option value=""' . ($nilai === '' ? ' selected' : '') . '>— Pilih mapel —</option>';
    foreach ($daftar as $m) $o .= '<option value="' . h($m) . '"' . ($m === $nilai ? ' selected' : '') . '>' . h($m) . '</option>';
    $o .= '<option value="__lain"' . ($ada ? '' : ' selected') . '>Lainnya…</option></select>';
    $o .= '<input class="mapel-lain" placeholder="Tulis nama mapel" value="' . ($ada ? '' : h($nilai)) . '"' . ($ada ? ' hidden' : ' name="mapel"' . ($wajib ? ' required' : '')) . '>';
    return $o . '</span>';
}

/**
 * Aturan "siapkan halaman lebih awal" (Speculation Rules, Chrome/Edge di HP & laptop).
 * Halaman disiapkan saat jari menyentuh tautan (conservative), atau saat kursor diam di atasnya (moderate, laptop).
 * Tautan yang mengunduh, mencetak, keluar, atau mengubah data TIDAK ikut disiapkan.
 */
function siap_cepat_aturan(): array
{
    $tolak = [
        ['href_matches' => '/?*unduh=*'], ['href_matches' => '/?*word=*'], ['href_matches' => '/?*ganti=*'],
        ['href_matches' => '/?*batal_tertib=*'], ['href_matches' => '/?*kaya=*'], ['href_matches' => '/?*cetak*'], ['href_matches' => '/?*hapus*'],
        ['href_matches' => '/?p=keluar*'], ['href_matches' => '/?p=cadangan*'], ['href_matches' => '/?p=kuis_main*'],
        ['href_matches' => '/?p=presentasi*'], ['href_matches' => '/?p=koreksi_lj*'], ['href_matches' => '/?p=gambar*'],
        ['href_matches' => '/?p=tugas_foto*'], ['href_matches' => '/?p=api_*'], ['href_matches' => '/?p=ke_pustaka*'], ['href_matches' => '/?p=apk*'],
        ['selector_matches' => '[target], [download], [data-tanpa-siap], [onclick]'],
    ];
    $syarat = ['and' => array_merge([['href_matches' => '/?p=*']], array_map(fn($t) => ['not' => $t], $tolak))];
    return [
        'prerender' => [['where' => $syarat, 'eagerness' => 'conservative']],
        'prefetch' => [['where' => $syarat, 'eagerness' => 'moderate']],
    ];
}


/** Baris pilihan di tab Perangkat (Soal ada di sini sejak v1.44). $aktif: 'soal' | jenis dokumen | '' (semua). */
function tab_perangkat(string $aktif): void
{
    $ada_lkpd = (bool)nilai("SELECT 1 FROM guru_dokumen WHERE guru_id = ? AND jenis = 'lkpd' LIMIT 1", [uid()]);
    $t = ['soal' => ['Soal', '?p=soal']];
    foreach (JENIS_DOKUMEN as $k => $n) if ($k !== 'lkpd' || $ada_lkpd) $t[$k] = [$k === 'paket' ? 'Paket soal' : $n, '?p=perangkat&jenis=' . $k];
    $t[''] = ['Semua', '?p=perangkat'];
    echo '<nav class="tab-geser" aria-label="Jenis perangkat">';
    foreach ($t as $k => [$n, $u]) echo '<a href="' . h($u) . '" class="' . ($aktif === $k ? 'aktif' : '') . '">' . h($n) . '</a>';
    echo '</nav>';
}

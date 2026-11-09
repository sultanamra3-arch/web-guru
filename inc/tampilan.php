<?php
declare(strict_types=1);

const MENU = [
    'beranda' => ['Beranda', '⌂'],
    'kelas' => ['Kelas', '🧑‍🎓'],
    'soal' => ['Soal', '📝'],
    'perangkat' => ['Perangkat', '📘'],
    'lainnya' => ['Lainnya', '☰'],
];

function kepala(string $judul, string $aktif = '', bool $polos = false): void
{
    $u = pengguna();
    ?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
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
  <a href="?p=<?= $p ?>" class="<?= $aktif === $p ? 'aktif' : '' ?>"><span class="ik"><?= $i ?></span><?= $t ?></a>
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
    foreach ($daftar as $k => $r) {
        $id = $kolom_id ? $r[$kolom_id] : (is_string($k) ? $k : $r);
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

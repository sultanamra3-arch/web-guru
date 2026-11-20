<?php
/* v1.74 Admin: buka SLiMS tanpa login ulang (alamat bertanda tangan, berlaku 60 detik, sekali pakai). */
declare(strict_types=1);

if (!adalah_admin()) ke('?p=beranda');
header('Cache-Control: no-store');
ke(sso_alamat_slims());

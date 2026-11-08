<?php
/* Data siswa diambil dari anggota perpustakaan SLiMS (kolom kelas = member.inst_name). */
declare(strict_types=1);

function daftar_kelas(): array
{
    return q("SELECT inst_name FROM member
        WHERE inst_name IS NOT NULL AND inst_name <> '' AND LOWER(inst_name) <> 'guru'
        GROUP BY inst_name ORDER BY inst_name")->fetchAll(PDO::FETCH_COLUMN);
}

function siswa_kelas(string $kelas): array
{
    return semua("SELECT member_id, member_name, gender FROM member WHERE inst_name = ? ORDER BY member_name", [$kelas]);
}

function kelas_valid(string $kelas): bool
{
    return $kelas !== '' && in_array($kelas, daftar_kelas(), true);
}

/** Kelas yang diajar guru menurut jadwalnya, lalu semua kelas. */
function kelas_saya(): array
{
    $j = q('SELECT DISTINCT kelas FROM guru_jadwal WHERE guru_id = ? ORDER BY kelas', [uid()])->fetchAll(PDO::FETCH_COLUMN);
    $semua = daftar_kelas();
    return array_values(array_unique(array_merge(array_intersect($j, $semua), $semua)));
}

function mapel_saya(): array
{
    $j = q('SELECT DISTINCT mapel FROM guru_jadwal WHERE guru_id = ? ORDER BY mapel', [uid()])->fetchAll(PDO::FETCH_COLUMN);
    $m = array_filter(array_map('trim', explode(',', (string)(pengguna()['mapel'] ?? ''))));
    return array_values(array_unique(array_merge($m, $j)));
}

<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('aksi') === 'hapus') {
    $id = (int)post('id');
    if (nilai('SELECT 1 FROM guru_sertifikat WHERE id = ? AND guru_id = ?', [$id, uid()])) {
        $sb = sampah_baris('guru_sertifikat', 'id = ?', [$id]);
        sampah_simpan('sertifikat', (string)($sb['baris'][0]['judul'] ?? $sb['baris'][0]['nama'] ?? 'Sertifikat'), [$sb, sampah_baris('guru_sertifikat_penerima', 'sertifikat_id = ?', [$id])]);
        q('DELETE FROM guru_sertifikat_penerima WHERE sertifikat_id = ?', [$id]);
        q('DELETE FROM guru_sertifikat WHERE id = ?', [$id]);
        pesan('Sertifikat dipindah ke Sampah.');
    }
    ke('?p=sertifikat');
}
$daftar = semua('SELECT s.*, (SELECT COUNT(*) FROM guru_sertifikat_penerima p WHERE p.sertifikat_id = s.id) n FROM guru_sertifikat s WHERE guru_id = ? ORDER BY id DESC', [uid()]);

kepala('Sertifikat', 'lainnya');
judul_baris('Sertifikat', '<a class="tombol utama" href="?p=sertifikat_form">＋ Buat</a>');
?>
<p class="redup">Buat sertifikat/piagam untuk satu kelas atau daftar nama, lalu cetak atau simpan sebagai PDF (A4 mendatar).</p>
<ul class="daftar kartu">
  <?php if (!$daftar): ?><li class="redup">Belum ada sertifikat.</li><?php endif; ?>
  <?php foreach ($daftar as $s): ?>
    <li>
      <div class="eks-atas"><b><?= h($s['judul']) ?></b><small><?= h(mb_strimwidth((string)$s['isi'], 0, 90, '…')) ?></small><small><?= (int)$s['n'] ?> penerima · <?= tgl($s['tanggal']) ?></small></div>
      <div class="eks-aksi">
        <a class="tombol kecil utama" href="?p=sertifikat_cetak&amp;id=<?= (int)$s['id'] ?>" target="_blank">🖨 Cetak</a>
        <a class="tombol kecil" href="?p=sertifikat_form&amp;id=<?= (int)$s['id'] ?>">Ubah</a>
        <form method="post" class="sebaris" onsubmit="return confirm('Hapus sertifikat ini?\nBisa dikembalikan dari Sampah (Lainnya → Sampah) selama 30 hari.')"><?= input_token() ?><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="kecil bahaya">Hapus</button></form>
      </div>
    </li>
  <?php endforeach; ?>
</ul>
<?php
kaki();

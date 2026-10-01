<?php
require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/includes/sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h2 class="page-title mb-1">Daftar Surat Masuk</h2><div class="text-secondary">Kelola seluruh data surat masuk.</div></div><a class="btn btn-dark" href="tambah.php">+ Tambah Surat</a></div>
<div class="card p-3 mb-3"><form class="row g-2"><div class="col-md-4"><input class="form-control" name="q" value="<?= e($filters['q']) ?>" placeholder="Cari uraian atau keterangan..."></div><div class="col-md-3"><input type="date" class="form-control" name="dari" value="<?= e($filters['dari']) ?>"></div><div class="col-md-3"><input type="date" class="form-control" name="sampai" value="<?= e($filters['sampai']) ?>"></div><div class="col-md-2 d-flex gap-2"><button class="btn btn-dark flex-fill">Filter</button><a href="surat-masuk.php" class="btn btn-light">Reset</a></div></form></div>
<div class="card table-card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>No</th><th>Tanggal Masuk</th><th>Tanggal Disposisi</th><th>Uraian / Pengusul</th><th>Keterangan</th><th>Aksi</th></tr></thead><tbody>
<?php if (!$data): ?><tr><td colspan="6" class="empty">Data tidak ditemukan.</td></tr><?php else: foreach ($data as $surat): ?><tr>
<td><?= (int) $surat['no'] ?></td><td><?= e($surat['tanggal_masuk']) ?></td><td><?= e($surat['tanggal_disposisi'] ?: '-') ?></td><td><?= nl2br(e($surat['uraian_pengusul'])) ?></td><td><?= nl2br(e($surat['keterangan'] ?: '-')) ?></td>
<td><div class="d-flex gap-1"><a class="btn btn-sm btn-outline-secondary" href="detail.php?id=<?= (int) $surat['id'] ?>">Detail</a><a class="btn btn-sm btn-outline-primary" href="edit.php?id=<?= (int) $surat['id'] ?>">Edit</a><form method="post" action="hapus.php" data-confirm-delete-form><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int) $surat['id'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button></form></div></td>
</tr><?php endforeach; endif; ?></tbody></table></div></div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
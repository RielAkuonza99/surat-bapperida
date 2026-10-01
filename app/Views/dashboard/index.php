<?php
require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/includes/sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="page-title mb-1">Dashboard</h2>
        <div class="text-secondary">Ringkasan administrasi surat masuk.</div>
    </div>
    <a class="btn btn-dark" href="tambah.php">+ Tambah Surat</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card stat"><div class="label">Total Surat</div><div class="value"><?= (int) $total ?></div></div></div>
    <div class="col-md-4"><div class="card stat"><div class="label">Sudah Disposisi</div><div class="value"><?= (int) $disposisi ?></div></div></div>
    <div class="col-md-4"><div class="card stat"><div class="label">Belum Disposisi</div><div class="value"><?= (int) $belum ?></div></div></div>
</div>

<div class="card table-card">
    <div class="p-4 border-bottom"><h5 class="mb-0">Surat Terbaru</h5></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead><tr><th>No</th><th>Tanggal Masuk</th><th>Tanggal Disposisi</th><th>Uraian / Pengusul</th><th>Keterangan</th></tr></thead>
            <tbody>
                <?php if (!$terbaru): ?>
                    <tr><td colspan="5" class="empty">Belum ada data surat.</td></tr>
                <?php else: foreach ($terbaru as $surat): ?>
                    <tr>
                        <td><?= (int) $surat['no'] ?></td>
                        <td><?= e($surat['tanggal_masuk']) ?></td>
                        <td><?= e($surat['tanggal_disposisi'] ?: '-') ?></td>
                        <td><?= e($surat['uraian_pengusul']) ?></td>
                        <td><?= e($surat['keterangan'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
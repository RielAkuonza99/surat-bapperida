<?php
require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/includes/sidebar.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="page-title mb-1">Dashboard</h2>
        <div class="text-secondary">Ringkasan administrasi surat masuk.</div>
    </div>
    <a class="btn btn-dark" href="<?= e(url('surat/tambah')) ?>">+ Tambah Surat</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card stat"><div class="label">Total Surat</div><div class="value"><?= (int) $total ?></div></div></div>
    <div class="col-md-4"><div class="card stat"><div class="label">Sudah Disposisi</div><div class="value"><?= (int) $disposisi ?></div></div></div>
    <div class="col-md-4"><div class="card stat"><div class="label">Belum Disposisi</div><div class="value"><?= (int) $belum ?></div></div></div>
</div>

<div class="card table-card">
    <div class="card-head"><h3>Surat Terbaru</h3><a href="<?= e(url('surat')) ?>">Lihat semua</a></div>
    <div class="table-responsive">
        <table class="table surat-table">
            <thead><tr><th>No Agenda</th><th>TGL Diterima</th><th>Surat Dari</th><th>Judul</th><th>Disposisi</th></tr></thead>
            <tbody>
                <?php if (!$terbaru): ?>
                    <tr><td colspan="5" class="empty">Belum ada surat masuk. Pilih Tambah Surat untuk memulai pencatatan.</td></tr>
                <?php else: foreach ($terbaru as $surat): ?>
                    <tr>
                        <td><?= (int) $surat['no'] ?></td>
                        <td class="nowrap"><?= e(formatTanggal($surat['tanggal_masuk'])) ?></td>
                        <td><?= e($surat['surat_dari'] ?: '-') ?></td>
                        <td class="cell-judul">
                            <a class="judul-link" href="<?= e(url('surat/' . (int) $surat['id'])) ?>"><?= e($surat['judul'] ?: ($surat['ringkas'] ?: '-')) ?></a>
                        </td>
                        <td class="nowrap"><?= disposisiIndicator((int) $surat['jumlah_disposisi']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
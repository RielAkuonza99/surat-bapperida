<?php
require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/includes/sidebar.php';
?>
<div class="page-header page-header--dashboard">
    <div class="page-header__copy">
        <span class="eyebrow">Ringkasan operasional</span>
        <h2 class="page-title mb-1">Dashboard</h2>
    </div>
    <a class="btn btn-primary btn-pill" href="<?= e(url('surat/tambah')) ?>">+ Tambah Surat</a>
</div>

<div class="stats-grid">
    <article class="stat-card stat-card--primary">
        <div class="stat-card__meta">Total surat</div>
        <div class="stat-card__value"><?= (int) $total ?></div>
        <div class="stat-card__note">Semua arsip aktif</div>
    </article>
    <article class="stat-card stat-card--success">
        <div class="stat-card__meta">Sudah disposisi</div>
        <div class="stat-card__value"><?= (int) $disposisi ?></div>
        <div class="stat-card__note">Siap ditindaklanjuti</div>
    </article>
    <article class="stat-card stat-card--warning">
        <div class="stat-card__meta">Belum disposisi</div>
        <div class="stat-card__value"><?= (int) $belum ?></div>
        <div class="stat-card__note">Butuh perhatian</div>
    </article>
</div>

<div class="card table-card">
    <div class="card-head">
        <div>
            <h3>Surat terbaru</h3>
            <p>Daftar catatan surat yang masuk baru-baru ini.</p>
        </div>
        <a href="<?= e(url('surat')) ?>">Lihat semua</a>
    </div>
    <div class="table-responsive">
        <table class="table surat-table">
            <thead><tr><th>No Agenda</th><th>Tanggal</th><th>Surat Dari</th><th>Judul</th><th>Disposisi</th></tr></thead>
            <tbody>
                <?php if (!$terbaru): ?>
                    <tr><td colspan="5" class="empty">Belum ada surat masuk. Pilih tambah surat untuk memulai pencatatan.</td></tr>
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
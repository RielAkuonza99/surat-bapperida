<?php
require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/includes/sidebar.php';

$hasFilters = $filters['q'] !== '' || $filters['dari'] !== '' || $filters['sampai'] !== '';
$firstPage = $pagination['total'] === 0 ? 0 : (($pagination['page'] - 1) * $pagination['perPage']) + 1;
$lastPage = min($pagination['page'] * $pagination['perPage'], $pagination['total']);
$pageNumbers = range(max(1, $pagination['page'] - 2), min($pagination['pages'], $pagination['page'] + 2));
$pageUrl = static fn (int $page): string => url('surat', array_merge($filters, ['page' => $page]));
?>
<div class="page-head">
    <div>
        <h2 class="page-title">Daftar Surat Masuk</h2>
        <p class="page-sub"><?= number_format($pagination['total'], 0, ',', '.') ?> surat<?= $hasFilters ? ' sesuai filter' : '' ?>.</p>
    </div>
    <a class="btn btn-dark" href="<?= e(url('surat/tambah')) ?>">Tambah Surat</a>
</div>

<form class="card filter-card" method="get" action="<?= e(url('surat')) ?>" role="search">
    <div class="row g-2 align-items-end">
        <div class="col-lg-5">
            <label class="form-label" for="q">Cari surat</label>
            <input id="q" class="form-control" name="q" value="<?= e($filters['q']) ?>" maxlength="200" placeholder="ID, agenda, nomor, nama, pengirim, judul, pengusul">
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label" for="dari">Diterima dari</label>
            <input id="dari" type="date" class="form-control" name="dari" value="<?= e($filters['dari']) ?>">
        </div>
        <div class="col-6 col-lg-2">
            <label class="form-label" for="sampai">Sampai</label>
            <input id="sampai" type="date" class="form-control" name="sampai" value="<?= e($filters['sampai']) ?>">
        </div>
        <div class="col-lg-3 d-flex gap-2">
            <button class="btn btn-dark flex-fill" type="submit">Cari</button>
            <a href="<?= e(url('surat')) ?>" class="btn btn-light">Reset</a>
        </div>
    </div>
</form>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table surat-table">
            <thead>
                <tr>
                    <th>ID Pencarian</th><th>No Agenda</th><th>No Surat</th><th>TGL Diterima</th>
                    <th>Surat Dari</th><th>Judul</th><th>Pengusul</th><th>Disposisi</th><th class="col-aksi">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$data): ?>
                <tr>
                    <td colspan="9" class="empty">
                        <?= $hasFilters
                            ? 'Tidak ada surat yang cocok. Ubah kata kunci atau rentang tanggal.'
                            : 'Belum ada surat masuk. Pilih Tambah Surat untuk mencatat surat pertama.' ?>
                    </td>
                </tr>
            <?php else: foreach ($data as $surat): ?>
                <tr>
                    <td class="mono nowrap"><?= e($surat['kode_pencarian'] ?: '-') ?></td>
                    <td><?= (int) $surat['no'] ?></td>
                    <td><?= e($surat['nomor_surat'] ?: '-') ?></td>
                    <td class="nowrap"><?= e(formatTanggal($surat['tanggal_masuk'])) ?></td>
                    <td><?= e($surat['surat_dari'] ?: '-') ?></td>
                    <td class="cell-judul">
                        <a class="judul-link" href="<?= e(url('surat/' . (int) $surat['id'])) ?>"><?= e($surat['judul'] ?: ($surat['ringkas'] ?: '-')) ?></a>
                        <div class="tags"><?= sifatBadge($surat['sifat'], true) ?></div>
                    </td>
                    <td><?= e($surat['pengusul'] ?: '-') ?></td>
                    <td class="nowrap"><?= disposisiIndicator((int) $surat['jumlah_disposisi']) ?></td>
                    <td class="col-aksi">
                        <div class="d-flex gap-1">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('surat/' . (int) $surat['id'])) ?>">Detail</a>
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('surat/' . (int) $surat['id'] . '/edit')) ?>">Edit</a>
                            <form method="post" action="<?= e(url('surat/' . (int) $surat['id'] . '/hapus')) ?>" data-confirm-delete-form>
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $surat['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($pagination['pages'] > 1): ?>
    <nav class="pagination-bar" aria-label="Halaman daftar surat">
        <span class="pagination-summary"><?= number_format($firstPage, 0, ',', '.') ?>-<?= number_format($lastPage, 0, ',', '.') ?> dari <?= number_format($pagination['total'], 0, ',', '.') ?></span>
        <div class="pagination-links">
            <?php if ($pagination['page'] > 1): ?>
                <a class="page-link" href="<?= e($pageUrl($pagination['page'] - 1)) ?>" aria-label="Halaman sebelumnya">Sebelumnya</a>
            <?php endif; ?>
            <?php if ($pageNumbers[0] > 1): ?>
                <a class="page-link" href="<?= e($pageUrl(1)) ?>">1</a>
                <?php if ($pageNumbers[0] > 2): ?><span class="page-gap" aria-hidden="true">&hellip;</span><?php endif; ?>
            <?php endif; ?>
            <?php foreach ($pageNumbers as $page): ?>
                <?php if ($page === $pagination['page']): ?>
                    <span class="page-link current" aria-current="page"><?= $page ?></span>
                <?php else: ?>
                    <a class="page-link" href="<?= e($pageUrl($page)) ?>"><?= $page ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($pageNumbers[count($pageNumbers) - 1] < $pagination['pages']): ?>
                <?php if ($pageNumbers[count($pageNumbers) - 1] < $pagination['pages'] - 1): ?><span class="page-gap" aria-hidden="true">&hellip;</span><?php endif; ?>
                <a class="page-link" href="<?= e($pageUrl($pagination['pages'])) ?>"><?= $pagination['pages'] ?></a>
            <?php endif; ?>
            <?php if ($pagination['page'] < $pagination['pages']): ?>
                <a class="page-link" href="<?= e($pageUrl($pagination['page'] + 1)) ?>" aria-label="Halaman berikutnya">Berikutnya</a>
            <?php endif; ?>
        </div>
    </nav>
<?php endif; ?>
<?php require APP_ROOT . '/includes/footer.php'; ?>

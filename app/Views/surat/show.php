<?php
require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/includes/sidebar.php';

$judul = $surat['judul'] ?: 'Surat Agenda #' . (int) $surat['no'];
$uraian = $surat['uraian'] ?: $surat['uraian_pengusul'];
$rawDriveLink = trim((string) ($surat['link_drive'] ?? ''));
?>
<?php if ($notice): ?>
    <div class="alert alert-<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div>
<?php endif; ?>
<div class="page-head">
    <div>
        <div class="detail-meta">
            <span class="mono"><?= e($surat['kode_pencarian'] ?: '-') ?></span>
            <?= sifatBadge($surat['sifat']) ?>
        </div>
        <h2 class="page-title"><?= e($judul) ?></h2>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="<?= e(url('surat/' . (int) $id . '/edit')) ?>">Edit</a>
        <form method="post" action="<?= e(url('surat/' . (int) $id . '/hapus')) ?>" data-confirm-delete-form>
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="id" value="<?= (int) $id ?>">
            <button class="btn btn-outline-danger" type="submit">Hapus</button>
        </form>
    </div>
</div>

<div class="card detail-card">
    <section class="detail-section">
        <h3>Informasi Surat</h3>
        <dl class="info-grid">
            <div><dt>No Agenda</dt><dd><?= (int) $surat['no'] ?></dd></div>
            <div><dt>No Surat</dt><dd><?= e($surat['nomor_surat'] ?: '-') ?></dd></div>
            <div><dt>TGL Surat</dt><dd><?= e(formatTanggal($surat['tanggal_surat'])) ?></dd></div>
            <div><dt>TGL Diterima</dt><dd><?= e(formatTanggal($surat['tanggal_masuk'])) ?></dd></div>
            <div><dt>Sifat</dt><dd><?= e($surat['sifat']) ?></dd></div>
            <div><dt>Surat Dari</dt><dd><?= e($surat['surat_dari'] ?: '-') ?></dd></div>
            <div><dt>Nama</dt><dd><?= e($surat['nama'] ?: '-') ?></dd></div>
            <div><dt>ID Pencarian</dt><dd class="mono"><?= e($surat['kode_pencarian'] ?: '-') ?></dd></div>
        </dl>
    </section>

    <section class="detail-section">
        <h3>Isi Surat</h3>
        <div class="text-block">
            <div class="text-label">Pengusul</div>
            <div><?= $surat['pengusul'] ? nl2br(e($surat['pengusul'])) : '-' ?></div>
        </div>
        <div class="text-block">
            <div class="text-label">Uraian</div>
            <div><?= $uraian !== '' && $uraian !== null ? nl2br(e($uraian)) : '-' ?></div>
        </div>
        <div class="text-block">
            <div class="text-label">Keterangan</div>
            <div><?= $surat['keterangan'] ? nl2br(e($surat['keterangan'])) : '-' ?></div>
        </div>
    </section>

    <section class="detail-section">
        <h3>Dokumen</h3>
        <div class="doc-row">
            <div>
                <div class="text-label">Status dokumen</div>
                <div>
                    <?= $driveUrl
                        ? 'Link Google Drive tersedia.'
                        : ($rawDriveLink !== '' ? 'Link tersimpan tidak valid. Periksa atau perbarui data surat.' : 'Belum ada link Google Drive.') ?>
                </div>
            </div>
            <?php if ($driveUrl): ?>
                <a class="btn btn-outline-primary" href="<?= e($driveUrl) ?>" target="_blank" rel="noopener noreferrer">Buka Google Drive</a>
            <?php endif; ?>
        </div>
        <?php if ($surat['documents']): ?>
            <div class="table-responsive mt-3">
                <table class="table surat-table">
                    <thead><tr><th>Nama file</th><th>Ukuran</th><th>API</th><th>Waktu upload (UTC)</th><th>Dokumen</th></tr></thead>
                    <tbody>
                    <?php foreach ($surat['documents'] as $document): ?>
                        <tr>
                            <td><?= e($document['file_name']) ?></td>
                            <td><?= number_format((int) $document['file_size'] / 1048576, 2, ',', '.') ?> MiB</td>
                            <td>API <?= (int) $document['drive_api_id'] ?></td>
                            <td class="nowrap"><?= e($document['uploaded_at']) ?></td>
                            <td><a href="<?= e($document['web_view_url']) ?>" target="_blank" rel="noopener noreferrer">Buka file</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="detail-section">
        <h3>Disposisi</h3>
        <ol class="flow">
            <?php foreach ($surat['disposisi'] as $tahap => $disposisi): ?>
                <li class="flow-item <?= $disposisi ? '' : 'flow-empty' ?>">
                    <span class="flow-marker" aria-hidden="true"><?= (int) $tahap ?></span>
                    <div class="flow-body">
                        <h4 class="flow-title">Tahap <?= (int) $tahap ?></h4>
                        <?php if ($disposisi): ?>
                            <div class="flow-route">
                                <span><?= e($disposisi['asal'] ?: '-') ?></span>
                                <span class="flow-arrow" aria-label="ke">&rarr;</span>
                                <span><?= e($disposisi['tujuan'] ?: '-') ?></span>
                            </div>
                            <div class="flow-sub"><?= e(formatTanggal($disposisi['tanggal'])) ?></div>
                            <?php if ($disposisi['keterangan']): ?>
                                <div class="flow-note"><?= nl2br(e($disposisi['keterangan'])) ?></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="muted">Belum ada disposisi pada tahap ini.</div>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
</div>
<div class="mt-3"><a class="btn btn-light" href="<?= e(url('surat')) ?>">Kembali ke daftar</a></div>
<?php require APP_ROOT . '/includes/footer.php'; ?>

<?php
require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/includes/sidebar.php';
?>
<div class="page-head">
    <div>
        <h2 class="page-title">Sinkronisasi metadata arsip</h2>
        <p class="page-sub">Metadata MySQL dikirim bertahap ke Supabase melalui antrean server-side.</p>
    </div>
</div>

<?php if ($notice): ?>
    <div class="alert alert-<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div>
<?php endif; ?>

<section class="card table-card mb-4" aria-labelledby="provider-heading">
    <div class="card-head">
        <h3 id="provider-heading">Provider arsip eksternal</h3>
        <span class="text-secondary"><?= e($providerStatus) ?></span>
    </div>
    <p class="mb-0">Status konfigurasi hanya diperiksa di server. Secret Supabase tidak ditampilkan di halaman ini.</p>
</section>

<section class="card table-card mb-4" aria-labelledby="queue-heading">
    <div class="card-head">
        <h3 id="queue-heading">Status antrean</h3>
    </div>
    <dl class="sync-summary mb-0">
        <?php foreach ($summary as $status => $total): ?>
            <div><dt><?= e($status) ?></dt><dd><?= number_format((int) $total, 0, ',', '.') ?></dd></div>
        <?php endforeach; ?>
    </dl>
    <p class="small text-secondary mb-0">Pengiriman tidak berjalan pada request halaman. Jalankan worker CLI dengan batch terbatas.</p>
</section>

<section class="card table-card" aria-labelledby="history-heading">
    <div class="card-head">
        <h3 id="history-heading">Aktivitas pengiriman terbaru</h3>
        <span class="text-secondary small">Maksimal 50 baris terakhir</span>
    </div>
    <div class="table-responsive">
        <table class="table surat-table">
            <thead>
            <tr><th>Waktu antre</th><th>ID pencarian</th><th>Operasi</th><th>Status</th><th>Percobaan</th><th>Terakhir dikirim</th><th>Konfirmasi</th><th>Error terakhir</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            <?php if (!$events): ?>
                <tr><td colspan="9" class="empty">Belum ada metadata yang masuk ke antrean. Data surat baru dan perubahan berikutnya akan tercatat otomatis.</td></tr>
            <?php else: foreach ($events as $event): ?>
                <tr>
                    <td class="nowrap"><?= e($event['created_at']) ?></td>
                    <td class="mono"><?= e($event['search_id']) ?></td>
                    <td><?= e($event['operation']) ?></td>
                    <td><?= e($event['status']) ?></td>
                    <td><?= number_format((int) $event['attempt_count'], 0, ',', '.') ?></td>
                    <td class="nowrap"><?= e($event['last_sent_at'] ?: '-') ?></td>
                    <td class="nowrap"><?= e($event['confirmed_at'] ?: '-') ?></td>
                    <td><?= e($event['last_error'] ?: '-') ?></td>
                    <td>
                        <?php if ($event['status'] === 'FAILED'): ?>
                            <form method="post" action="<?= e(url('sinkronisasi/coba-lagi')) ?>" class="m-0">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                <button class="btn btn-sm btn-outline-primary" type="submit">Jadwalkan ulang</button>
                            </form>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require APP_ROOT . '/includes/footer.php'; ?>

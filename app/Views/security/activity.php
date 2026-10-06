<?php
require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/includes/sidebar.php';
?>
<div class="page-head">
    <div>
        <h2 class="page-title">Aktivitas & Sesi</h2>
        <p class="page-sub"><?= $isAdmin ? 'Riwayat aktivitas dan sesi seluruh pengguna.' : 'Riwayat aktivitas serta sesi perangkat akun Anda.' ?></p>
    </div>
</div>

<?php if ($notice): ?>
    <div class="alert alert-<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div>
<?php endif; ?>

<section class="card table-card mb-4" aria-labelledby="sessions-heading">
    <div class="card-head">
        <h3 id="sessions-heading">Perangkat dan sesi</h3>
        <span class="text-secondary small"><?= number_format($sessions['total'], 0, ',', '.') ?> riwayat</span>
    </div>
    <div class="table-responsive">
        <table class="table surat-table">
            <thead>
            <tr>
                <?php if ($isAdmin): ?><th>Pengguna</th><?php endif; ?>
                <th>Sesi / perangkat</th><th>IP</th><th>Login (UTC)</th><th>Terakhir aktif (UTC)</th><th>Berlaku sampai (UTC)</th><th>Status</th><th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$sessions['items']): ?>
                <tr><td colspan="<?= $isAdmin ? 8 : 7 ?>" class="empty">Belum ada sesi perangkat. Login berhasil akan tercatat di sini.</td></tr>
            <?php else: foreach ($sessions['items'] as $session): ?>
                <tr>
                    <?php if ($isAdmin): ?><td><?= e($session['username']) ?></td><?php endif; ?>
                    <td><span class="mono">#<?= (int) $session['id'] ?></span><div class="small text-secondary"><?= e($session['user_agent'] ?: 'Perangkat tidak teridentifikasi') ?></div></td>
                    <td class="mono"><?= e($session['ip_address'] ?: '-') ?></td>
                    <td class="nowrap"><?= e($session['created_at']) ?></td>
                    <td class="nowrap"><?= e($session['last_seen_at']) ?></td>
                    <td class="nowrap"><?= e($session['expires_at']) ?></td>
                    <td><?= e($session['status']) ?><?php if ($session['revoked_by_username']): ?> oleh <?= e($session['revoked_by_username']) ?><?php endif; ?></td>
                    <td>
                        <?php if ($session['status'] === 'Aktif'): ?>
                            <form method="post" action="<?= e(url('sesi/' . (int) $session['id'] . '/cabut')) ?>" class="m-0">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Cabut akses</button>
                            </form>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($sessions['pages'] > 1): ?>
        <nav class="pagination-bar" aria-label="Halaman riwayat sesi">
            <span class="pagination-summary">Halaman <?= (int) $sessions['page'] ?> dari <?= (int) $sessions['pages'] ?></span>
            <div class="pagination-links">
                <?php if ($sessions['page'] > 1): ?><a class="page-link" href="<?= e(url('aktivitas', ['sessions_page' => (int) $sessions['page'] - 1, 'logs_page' => (int) $logs['page']])) ?>">Sebelumnya</a><?php endif; ?>
                <?php if ($sessions['page'] < $sessions['pages']): ?><a class="page-link" href="<?= e(url('aktivitas', ['sessions_page' => (int) $sessions['page'] + 1, 'logs_page' => (int) $logs['page']])) ?>">Berikutnya</a><?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
</section>

<section class="card table-card" aria-labelledby="audit-heading">
    <div class="card-head">
        <h3 id="audit-heading">Change log</h3>
        <span class="text-secondary small"><?= number_format($logs['total'], 0, ',', '.') ?> aktivitas</span>
    </div>
    <div class="table-responsive">
        <table class="table surat-table">
            <thead><tr><th>Waktu (UTC)</th><th>Pengguna</th><th>Aktivitas</th><th>Modul</th><th>Data</th><th>Sesi</th><th>IP</th></tr></thead>
            <tbody>
            <?php if (!$logs['items']): ?>
                <tr><td colspan="7" class="empty">Belum ada aktivitas yang tercatat.</td></tr>
            <?php else: foreach ($logs['items'] as $log): ?>
                <?php $details = $log['details'] ? json_decode($log['details'], true) : null; ?>
                <tr>
                    <td class="nowrap"><?= e($log['created_at']) ?></td>
                    <td><?= e($log['actor_username'] ?: 'Pengguna dihapus') ?></td>
                    <td><?= e($log['action']) ?></td>
                    <td><?= e($log['module']) ?></td>
                    <td>
                        <?= $log['record_id'] === null ? '-' : e($log['record_id']) ?>
                        <?php if (is_array($details) && $details !== []): ?><div class="small text-secondary"><?= e(json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></div><?php endif; ?>
                    </td>
                    <td><?= $log['auth_session_id'] === null ? '-' : '#' . (int) $log['auth_session_id'] ?></td>
                    <td class="mono"><?= e($log['ip_address'] ?: '-') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($logs['pages'] > 1): ?>
        <nav class="pagination-bar" aria-label="Halaman change log">
            <span class="pagination-summary">Halaman <?= (int) $logs['page'] ?> dari <?= (int) $logs['pages'] ?></span>
            <div class="pagination-links">
                <?php if ($logs['page'] > 1): ?><a class="page-link" href="<?= e(url('aktivitas', ['sessions_page' => (int) $sessions['page'], 'logs_page' => (int) $logs['page'] - 1])) ?>">Sebelumnya</a><?php endif; ?>
                <?php if ($logs['page'] < $logs['pages']): ?><a class="page-link" href="<?= e(url('aktivitas', ['sessions_page' => (int) $sessions['page'], 'logs_page' => (int) $logs['page'] + 1])) ?>">Berikutnya</a><?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
</section>
<?php require APP_ROOT . '/includes/footer.php'; ?>

<?php
require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/includes/sidebar.php';
$byId = [];
foreach ($configs as $config) {
    $byId[(int) $config['id']] = $config;
}
?>
<div class="page-head">
    <div>
        <h2 class="page-title">Konfigurasi Google Drive</h2>
        <p class="page-sub">API aktif dicoba sesuai urutan. API 2 dan 3 menjadi opsi pemulihan jika API sebelumnya mengalami gangguan.</p>
    </div>
</div>

<?php if ($notice): ?>
    <div class="alert alert-<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div>
<?php endif; ?>

<div class="drive-config-list">
    <?php for ($slot = 1; $slot <= 3; $slot++): $config = $byId[$slot] ?? null; ?>
        <?php
        $isConfigured = $config && (int) $config['configured'] === 1;
        $isEnabled = $isConfigured && (int) $config['is_enabled'] === 1;
        $role = $slot === 1 ? 'Utama' : 'Pemulihan ' . ($slot - 1);
        ?>
        <details class="drive-config" <?= $slot === 1 ? 'open' : '' ?>>
            <summary class="drive-config-summary">
                <span class="drive-config-identity">
                    <span class="drive-config-order"><?= $slot ?></span>
                    <span>
                        <span class="drive-config-role"><?= e($role) ?></span>
                        <span class="drive-config-title" id="drive-api-<?= $slot ?>">Google Drive API <?= $slot ?></span>
                    </span>
                </span>
                <span class="drive-config-status <?= $isEnabled ? 'is-enabled' : '' ?>">
                    <?= $isEnabled ? 'Aktif' : ($config ? 'Nonaktif' : 'Belum dikonfigurasi') ?>
                </span>
            </summary>
            <div class="drive-config-body" role="region" aria-labelledby="drive-api-<?= $slot ?>">
                <?php if ($config && $config['last_error']): ?>
                    <p class="alert alert-warning drive-config-error" role="status"><?= e($config['last_error']) ?></p>
                <?php endif; ?>
                <form method="post" action="<?= e(url('pengaturan/google-drive/' . $slot)) ?>" class="drive-config-form">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <div class="row g-3 drive-field-grid">
                        <div class="col-md-6">
                            <label class="form-label" for="drive-name-<?= $slot ?>">Nama konfigurasi</label>
                            <input class="form-control" id="drive-name-<?= $slot ?>" name="name" maxlength="100" required value="<?= e($config['name'] ?? 'Google Drive API ' . $slot) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="drive-folder-<?= $slot ?>">Folder ID</label>
                            <input class="form-control" id="drive-folder-<?= $slot ?>" name="folder_id" maxlength="255" value="<?= e($config['folder_id'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="drive-credentials-<?= $slot ?>">Service account JSON</label>
                            <textarea class="form-control drive-credentials" id="drive-credentials-<?= $slot ?>" name="credentials_json" rows="5" autocomplete="off" spellcheck="false" aria-describedby="drive-credentials-help-<?= $slot ?>"></textarea>
                            <div class="form-text drive-credentials-help" id="drive-credentials-help-<?= $slot ?>">
                                <?php if ($isConfigured): ?>
                                    Tersimpan terenkripsi. Biarkan kosong untuk mempertahankan credential yang ada.
                                <?php else: ?>
                                    Credential akan dienkripsi di server sebelum disimpan.
                                <?php endif; ?>
                                <?php if ($isConfigured): ?>
                                    <span class="drive-service-email">Service account: <?= e($config['service_account_email']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="drive-config-actions">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_enabled" value="1" id="drive-enabled-<?= $slot ?>" <?= $isEnabled ? 'checked' : '' ?>>
                            <label class="form-check-label" for="drive-enabled-<?= $slot ?>">Gunakan untuk upload dan pemulihan</label>
                        </div>
                        <button class="btn btn-dark" type="submit">Simpan konfigurasi</button>
                    </div>
                </form>
                <?php if ($isConfigured): ?>
                    <form method="post" action="<?= e(url('pengaturan/google-drive/' . $slot . '/test')) ?>" class="drive-test-form">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <button class="btn btn-outline-primary" type="submit">Uji koneksi dan folder</button>
                        <div class="drive-test-meta">
                            <span>Kesehatan: <strong><?= e($config['health_status']) ?></strong></span>
                            <span>Pemeriksaan: <?= e($config['last_health_check'] ?: 'Belum dilakukan') ?></span>
                            <span>Terakhir digunakan: <?= e($config['last_used_at'] ?: 'Belum digunakan') ?></span>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </details>
    <?php endfor; ?>
</div>
<?php require APP_ROOT . '/includes/footer.php'; ?>

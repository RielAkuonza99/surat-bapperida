<?php require APP_ROOT . '/includes/header.php'; ?>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header mb-4 text-center">
            <div class="brand-badge mb-3">B</div>
            <h2 class="mb-1">Login Sistem</h2>
            <p class="text-secondary mb-0">BAPPERIDA • Surat Masuk</p>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($loginRequired): ?>
            <div class="alert alert-warning">Silakan login terlebih dahulu untuk melanjutkan.</div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('login')) ?>" class="auth-form" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= e($username) ?>" maxlength="50" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-dark w-100">Masuk</button>
        </form>
    </div>
</div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
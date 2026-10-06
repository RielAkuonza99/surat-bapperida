<?php
require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/includes/sidebar.php';
$isEditing = $userRecord !== null;
?>
<div class="page-head">
    <div>
        <h2 class="page-title"><?= $isEditing ? 'Edit Pengguna' : 'Tambah Pengguna' ?></h2>
        <p class="page-sub">Perubahan password berlaku pada login berikutnya dan mencabut sesi perangkat lain.</p>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0"><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form class="card filter-card" method="post" action="<?= e(url($isEditing ? 'pengguna/' . (int) $userRecord['id'] : 'pengguna')) ?>" autocomplete="off">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <?php if ($isEditing): ?><input type="hidden" name="id" value="<?= (int) $userRecord['id'] ?>"><?php endif; ?>
    <div class="mb-3">
        <label class="form-label" for="username">Username</label>
        <input class="form-control" id="username" name="username" value="<?= e($formData['username'] ?? '') ?>" maxlength="50" required>
        <div class="form-text">3 sampai 50 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.</div>
    </div>
    <div class="mb-3">
        <label class="form-label" for="password"><?= $isEditing ? 'Password baru (kosongkan jika tidak diubah)' : 'Password awal' ?></label>
        <input class="form-control" type="password" id="password" name="password" minlength="12" <?= $isEditing ? '' : 'required' ?> autocomplete="new-password">
        <div class="form-text">Minimal 12 karakter. Password lama tidak ditampilkan.</div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label" for="role">Hak akses</label>
            <select class="form-select" id="role" name="role" required>
                <option value="pegawai" <?= ($formData['role'] ?? '') === 'pegawai' ? 'selected' : '' ?>>Pegawai</option>
                <option value="admin" <?= ($formData['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrator</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="is_active">Status akun</label>
            <select class="form-select" id="is_active" name="is_active" required>
                <option value="1" <?= (string) ($formData['is_active'] ?? '1') === '1' ? 'selected' : '' ?>>Aktif</option>
                <option value="0" <?= (string) ($formData['is_active'] ?? '') === '0' ? 'selected' : '' ?>>Nonaktif</option>
            </select>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-dark" type="submit">Simpan Pengguna</button>
        <a class="btn btn-light" href="<?= e(url('pengguna')) ?>">Batal</a>
    </div>
</form>
<?php require APP_ROOT . '/includes/footer.php'; ?>

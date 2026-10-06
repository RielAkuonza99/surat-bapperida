<?php
require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/includes/sidebar.php';
?>
<div class="page-head">
    <div>
        <h2 class="page-title">Pengelolaan Pengguna</h2>
        <p class="page-sub">Akun, role, dan status akses pegawai.</p>
    </div>
    <a class="btn btn-dark" href="<?= e(url('pengguna/tambah')) ?>">Tambah Pengguna</a>
</div>

<?php if ($notice): ?>
    <div class="alert alert-<?= e($notice['type']) ?>" role="status"><?= e($notice['message']) ?></div>
<?php endif; ?>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table surat-table">
            <thead><tr><th>Username</th><th>Role</th><th>Status</th><th>Dibuat</th><th>Terakhir diubah</th><th>Aksi</th></tr></thead>
            <tbody>
            <?php if (!$users): ?>
                <tr><td colspan="6" class="empty">Belum ada akun pengguna. Tambahkan akun pegawai untuk memberi akses.</td></tr>
            <?php else: foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['username']) ?></td>
                    <td><?= $user['role'] === 'admin' ? 'Administrator' : 'Pegawai' ?></td>
                    <td><?= (int) $user['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></td>
                    <td class="nowrap"><?= e($user['created_at']) ?></td>
                    <td class="nowrap"><?= e($user['updated_at']) ?></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('pengguna/' . (int) $user['id'] . '/edit')) ?>">Edit</a>
                            <?php if ((int) $user['id'] !== (int) currentUser()['id']): ?>
                                <form method="post" action="<?= e(url('pengguna/' . (int) $user['id'] . '/hapus')) ?>" class="m-0">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require APP_ROOT . '/includes/footer.php'; ?>

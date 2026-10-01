<?php
require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/includes/sidebar.php';
?>
<h2 class="page-title mb-4"><?= $editing ? 'Edit' : 'Tambah' ?> Surat Masuk</h2>
<?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endforeach; ?>
<div class="card p-4">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="tanggal_masuk">Tanggal Masuk *</label><input id="tanggal_masuk" type="date" name="tanggal_masuk" class="form-control" value="<?= e($formData['tanggal_masuk']) ?>" required></div>
            <div class="col-md-6"><label class="form-label" for="tanggal_disposisi">Tanggal Disposisi</label><input id="tanggal_disposisi" type="date" name="tanggal_disposisi" class="form-control" value="<?= e($formData['tanggal_disposisi']) ?>"></div>
            <div class="col-12"><label class="form-label" for="uraian_pengusul">Uraian / Pengusul *</label><textarea id="uraian_pengusul" name="uraian_pengusul" class="form-control" rows="4" required><?= e($formData['uraian_pengusul']) ?></textarea></div>
            <div class="col-12"><label class="form-label" for="keterangan">Keterangan</label><textarea id="keterangan" name="keterangan" class="form-control" rows="3"><?= e($formData['keterangan']) ?></textarea></div>
            <div class="col-12 d-flex gap-2"><button class="btn btn-dark"><?= $editing ? 'Simpan Perubahan' : 'Simpan' ?></button><a href="<?= $editing ? 'detail.php?id=' . (int) $id : 'surat-masuk.php' ?>" class="btn btn-light">Batal</a></div>
        </div>
    </form>
</div>
<?php require APP_ROOT . '/includes/footer.php'; ?>
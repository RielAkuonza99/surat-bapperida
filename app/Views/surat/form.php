<?php
require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/includes/sidebar.php';

$f = $formData;
?>
<div class="page-head">
    <div>
        <h2 class="page-title"><?= $editing ? 'Edit' : 'Tambah' ?> Surat Masuk</h2>
        <p class="page-sub">No agenda dan ID pencarian dibuat saat surat disimpan.</p>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-danger" role="alert">
        <strong>Data belum dapat disimpan:</strong>
        <ul class="mb-0 mt-1">
            <?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" action="<?= e(url($editing ? 'surat/' . (int) $id . '/edit' : 'surat/tambah')) ?>" class="doc-form card">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

    <fieldset class="form-section">
        <legend>Informasi Surat</legend>
        <?php if ($editing): ?>
            <dl class="id-strip">
                <div><dt>No Agenda</dt><dd><?= (int) $surat['no'] ?></dd></div>
                <div><dt>ID Pencarian</dt><dd class="mono"><?= e($surat['kode_pencarian']) ?></dd></div>
            </dl>
        <?php endif; ?>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label" for="judul">Judul Surat</label>
                <input id="judul" name="judul" class="form-control form-control-lg" maxlength="255" value="<?= e($f['judul']) ?>">
            </div>
            <div class="col-md-8">
                <label class="form-label" for="surat_dari">Surat Dari</label>
                <input id="surat_dari" name="surat_dari" class="form-control" maxlength="255" value="<?= e($f['surat_dari']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="sifat">Sifat</label>
                <select id="sifat" name="sifat" class="form-select">
                    <?php foreach ($sifatOptions as $option): ?>
                        <option value="<?= e($option) ?>" <?= $f['sifat'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="nomor_surat">Nomor Surat</label>
                <input id="nomor_surat" name="nomor_surat" class="form-control" maxlength="100" value="<?= e($f['nomor_surat']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="tanggal_surat">TGL Surat</label>
                <input id="tanggal_surat" type="date" name="tanggal_surat" class="form-control" value="<?= e($f['tanggal_surat']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="tanggal_masuk">TGL Diterima *</label>
                <input id="tanggal_masuk" type="date" name="tanggal_masuk" class="form-control" value="<?= e($f['tanggal_masuk']) ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="nama">Nama</label>
                <input id="nama" name="nama" class="form-control" maxlength="150" value="<?= e($f['nama']) ?>">
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Isi Surat</legend>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label" for="pengusul">Pengusul</label>
                <input id="pengusul" name="pengusul" class="form-control" maxlength="255" value="<?= e($f['pengusul']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label" for="uraian">Uraian</label>
                <textarea id="uraian" name="uraian" class="form-control" rows="4"><?= e($f['uraian']) ?></textarea>
                <div class="form-text">Isi judul surat atau uraian.</div>
            </div>
            <div class="col-12">
                <label class="form-label" for="keterangan">Keterangan</label>
                <textarea id="keterangan" name="keterangan" class="form-control" rows="2"><?= e($f['keterangan']) ?></textarea>
            </div>
        </div>
    </fieldset>

    <fieldset class="form-section">
        <legend>Dokumen</legend>
        <label class="form-label" for="document">Upload dokumen PDF</label>
        <input id="document" type="file" name="document" class="form-control" accept=".pdf,application/pdf">
        <div class="form-text">Maksimal 10 MiB. File dikirim ke Google Drive menggunakan konfigurasi aktif dengan recovery terkontrol.</div>
        <label class="form-label" for="link_drive">Link Google Drive</label>
        <input id="link_drive" type="url" name="link_drive" class="form-control" maxlength="500" placeholder="https://drive.google.com/..." value="<?= e($f['link_drive']) ?>">
        <div class="form-text">Link menjadi rujukan dokumen asli. Gunakan URL https dari drive.google.com atau docs.google.com.</div>
    </fieldset>

    <fieldset class="form-section section-optional">
        <legend>Disposisi <span class="legend-note">maksimal 3 tahap</span></legend>
        <?php foreach (\App\Models\SuratModel::DISPOSISI_TAHAP as $tahap): $d = $f['disposisi'][$tahap]; ?>
            <div class="disp-step">
                <h3 class="disp-step-title">Disposisi <?= (int) $tahap ?></h3>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="d<?= $tahap ?>_asal">Asal</label>
                        <input id="d<?= $tahap ?>_asal" name="disposisi[<?= $tahap ?>][asal]" class="form-control" maxlength="150" value="<?= e($d['asal']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="d<?= $tahap ?>_tujuan">Tujuan</label>
                        <input id="d<?= $tahap ?>_tujuan" name="disposisi[<?= $tahap ?>][tujuan]" class="form-control" maxlength="150" value="<?= e($d['tujuan']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="d<?= $tahap ?>_tanggal">Tanggal</label>
                        <input id="d<?= $tahap ?>_tanggal" type="date" name="disposisi[<?= $tahap ?>][tanggal]" class="form-control" value="<?= e($d['tanggal']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="d<?= $tahap ?>_keterangan">Keterangan</label>
                        <textarea id="d<?= $tahap ?>_keterangan" name="disposisi[<?= $tahap ?>][keterangan]" class="form-control" rows="2"><?= e($d['keterangan']) ?></textarea>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </fieldset>

    <div class="form-actions">
        <button class="btn btn-dark" type="submit"><?= $editing ? 'Simpan Perubahan' : 'Simpan Surat' ?></button>
        <a href="<?= e(url($editing ? 'surat/' . (int) $id : 'surat')) ?>" class="btn btn-light">Batal</a>
    </div>
</form>
<?php require APP_ROOT . '/includes/footer.php'; ?>

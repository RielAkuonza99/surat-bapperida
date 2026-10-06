<?php if (isLoggedIn()): ?>
</div>
</main>
</div>
<?php endif; ?>
<div id="confirm-modal" class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirm-message">
    <div class="confirm-dialog">
        <div class="confirm-icon">!</div>
        <h5 id="confirm-message">Yakin ingin menghapus surat ini?</h5>
        <div class="confirm-actions">
            <button type="button" class="btn btn-light" id="cancel-delete-btn">Batal</button>
            <button type="button" class="btn btn-danger" id="confirm-delete-btn">Hapus</button>
        </div>
    </div>
</div>
<dialog class="logout-dialog" data-logout-dialog aria-labelledby="logout-dialog-title">
    <div class="logout-dialog-content">
        <h2 id="logout-dialog-title">Keluar dari aplikasi?</h2>
        <p>Sesi pada perangkat ini akan diakhiri. Anda perlu masuk kembali untuk menggunakan aplikasi.</p>
        <div class="logout-dialog-actions">
            <button class="btn btn-light" type="button" data-cancel-logout>Batal</button>
            <button class="btn btn-danger" type="submit" form="logout-form">Ya, keluar</button>
        </div>
    </div>
</dialog>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(url('assets/js/script.js?v=5')) ?>"></script>
</body>
</html>

<?php if (basename($_SERVER['SCRIPT_NAME']) !== 'index.php' || isset($_SESSION['user_id'])): ?>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js?v=4"></script>
</body>
</html>

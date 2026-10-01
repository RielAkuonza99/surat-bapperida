<?php
$currentUser = currentUser();
$activePage = basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="sidebar">
    <div class="brand">
        <img src="assets/img/icon.svg" alt="BAPPERIDA" class="brand-icon icon-sm">
        <div><strong>BAPPERIDA</strong><small>Kota Tanjungbalai</small></div>
    </div>
    <button class="nav-toggle" type="button" data-nav-toggle aria-controls="primary-navigation" aria-expanded="false" aria-label="Buka menu" title="Buka menu">
        <span class="nav-toggle-icon" aria-hidden="true"><span></span><span></span><span></span></span>
    </button>
    <nav class="nav flex-column mt-4" id="primary-navigation" aria-label="Navigasi utama">
        <a class="nav-link <?= $activePage === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">Dashboard</a>
        <a class="nav-link <?= $activePage === 'surat-masuk.php' ? 'active' : '' ?>" href="surat-masuk.php">Surat Masuk</a>
        <a class="nav-link <?= $activePage === 'tambah.php' ? 'active' : '' ?>" href="tambah.php">Tambah Surat</a>
    </nav>
    <div class="sidebar-footer">
        <div class="fw-semibold"><?= htmlspecialchars($currentUser['username'] ?? 'Admin') ?></div>
        <form method="post" action="logout.php" class="m-0">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <button type="submit" class="btn btn-link btn-sm p-0 small text-light text-decoration-none">Logout</button>
        </form>
    </div>
</aside>
<main class="main">
<nav class="topbar">
    <div><strong><?= htmlspecialchars($pageTitle) ?></strong></div>
    <div class="small text-secondary">Sistem Administrasi Surat BAPPERIDA</div>
</nav>
<div class="content">

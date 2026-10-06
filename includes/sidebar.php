<?php
$currentUser = currentUser();
$activePath = requestPath();
$isSuratPage = $activePath === '/surat' || str_starts_with($activePath, '/surat/');
$isAdmin = ($currentUser['role'] ?? '') === 'admin';
?>
<aside class="sidebar">
    <div class="brand">
        <span class="brand-seal">
            <img src="<?= e(url('assets/img/icon.svg')) ?>" alt="Logo BAPPERIDA" class="brand-icon" width="44" height="44">
        </span>
        <div class="brand-copy"><strong>BAPPERIDA</strong><small>Kota Tanjungbalai</small></div>
    </div>
    <nav class="nav flex-column mt-4" id="primary-navigation" aria-label="Navigasi utama">
        <a class="nav-link <?= $activePath === '/dashboard' ? 'active' : '' ?>" href="<?= e(url('dashboard')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3h8v8H3zM13 3h8v5h-8zM13 10h8v11h-8zM3 13h8v8H3z" /></svg>
            <span>Dashboard</span>
        </a>
        <a class="nav-link <?= $isSuratPage ? 'active' : '' ?>" href="<?= e(url('surat')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 2h8l5 5v15H6zM14 2v6h5M9 13h7M9 17h7" /></svg>
            <span>Surat Masuk</span>
        </a>
        <a class="nav-link <?= $activePath === '/aktivitas' ? 'active' : '' ?>" href="<?= e(url('aktivitas')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16M4 12h7M4 19h16M15 9l2 3 3-5" /></svg>
            <span>Aktivitas &amp; Sesi</span>
        </a>
        <a class="nav-link <?= $activePath === '/pengaturan' ? 'active' : '' ?>" href="<?= e(url('pengaturan')) ?>">
            <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3" /><path d="m19.4 15 .1.1a1.7 1.7 0 0 1-2.4 2.4l-.1-.1a1.7 1.7 0 0 0-2.9 1.2v.2a1.7 1.7 0 0 1-3.4 0v-.2a1.7 1.7 0 0 0-2.9-1.2l-.1.1a1.7 1.7 0 0 1-2.4-2.4l.1-.1a1.7 1.7 0 0 0-1.2-2.9H4a1.7 1.7 0 0 1 0-3.4h.2a1.7 1.7 0 0 0 1.2-2.9l-.1-.1a1.7 1.7 0 0 1 2.4-2.4l.1.1a1.7 1.7 0 0 0 2.9-1.2V2a1.7 1.7 0 0 1 3.4 0v.2a1.7 1.7 0 0 0 2.9 1.2l.1-.1a1.7 1.7 0 0 1 2.4 2.4l-.1.1a1.7 1.7 0 0 0 1.2 2.9h.2a1.7 1.7 0 0 1 0 3.4h-.2a1.7 1.7 0 0 0-1.2 2.9z" /></svg>
            <span>Pengaturan</span>
        </a>
        <?php if ($isAdmin): ?>
            <a class="nav-link <?= $activePath === '/pengguna' || str_starts_with($activePath, '/pengguna/') ? 'active' : '' ?>" href="<?= e(url('pengguna')) ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M16 20v-1.5a4.5 4.5 0 0 0-4.5-4.5h-4A4.5 4.5 0 0 0 3 18.5V20zM9.5 10a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM16 3.2a3.5 3.5 0 0 1 0 6.6M17 14h.5a3.5 3.5 0 0 1 3.5 3.5V20h-3" /></svg>
                <span>Pengguna</span>
            </a>
            <a class="nav-link <?= $activePath === '/sinkronisasi' ? 'active' : '' ?>" href="<?= e(url('sinkronisasi')) ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 7v5h-5M4 17v-5h5M5.5 9a7 7 0 0 1 11.9-2L20 12M4 12l2.6 5a7 7 0 0 0 11.9-2" /></svg>
                <span>Sinkronisasi metadata</span>
            </a>
            <a class="nav-link <?= $activePath === '/pengaturan/google-drive' ? 'active' : '' ?>" href="<?= e(url('pengaturan/google-drive')) ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h6l2 2h8v12H4zM8 12h8M8 16h5" /></svg>
                <span>Google Drive API</span>
            </a>
        <?php endif; ?>
    </nav>
</aside>
<button class="sidebar-backdrop" type="button" data-mobile-nav-backdrop aria-label="Tutup navigasi" hidden tabindex="-1"></button>
<main class="main">
<div class="navigation-loadbar is-loading" data-navigation-loadbar aria-hidden="true"><span></span></div>
<nav class="topbar" aria-label="Konteks halaman">
    <div class="topbar-leading">
        <button class="sidebar-collapse-toggle" type="button" data-sidebar-toggle aria-expanded="true" aria-label="Tutup navigasi samping" title="Tutup navigasi samping">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16M4 12h16M4 19h16" /></svg>
        </button>
        <div class="topbar-heading"><span class="topbar-kicker">BAPPERIDA · Sistem Surat Masuk</span><strong><?= e($pageTitle) ?></strong></div>
    </div>
    <div class="topbar-actions">
        <details class="topbar-settings">
            <summary aria-label="Pengaturan akun" title="Pengaturan akun">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3" /><path d="m19.4 15 .1.1a1.7 1.7 0 0 1-2.4 2.4l-.1-.1a1.7 1.7 0 0 0-2.9 1.2v.2a1.7 1.7 0 0 1-3.4 0v-.2a1.7 1.7 0 0 0-2.9-1.2l-.1.1a1.7 1.7 0 0 1-2.4-2.4l.1-.1a1.7 1.7 0 0 0-1.2-2.9H4a1.7 1.7 0 0 1 0-3.4h.2a1.7 1.7 0 0 0 1.2-2.9l-.1-.1a1.7 1.7 0 0 1 2.4-2.4l.1.1a1.7 1.7 0 0 0 2.9-1.2V2a1.7 1.7 0 0 1 3.4 0v.2a1.7 1.7 0 0 0 2.9 1.2l.1-.1a1.7 1.7 0 0 1 2.4 2.4l-.1.1a1.7 1.7 0 0 0 1.2 2.9h.2a1.7 1.7 0 0 1 0 3.4h-.2a1.7 1.7 0 0 0-1.2 2.9z" /></svg>
                <span>Pengaturan</span>
            </summary>
            <div class="settings-popover">
                <dl class="settings-account-summary">
                    <div><dt>Username</dt><dd><?= e($currentUser['username'] ?? '') ?></dd></div>
                    <div><dt>Role</dt><dd><?= e(ucfirst((string) ($currentUser['role'] ?? ''))) ?></dd></div>
                    <div><dt>ID pengguna</dt><dd>#<?= (int) ($currentUser['id'] ?? 0) ?></dd></div>
                </dl>
                <a class="settings-profile-link" href="<?= e(url('pengaturan')) ?>">Buka pengaturan dan profil</a>
                <form id="logout-form" method="post" action="<?= e(url('logout')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                    <button class="settings-logout" type="button" data-open-logout>Keluar dari aplikasi</button>
                </form>
            </div>
        </details>
        <button class="mobile-nav-toggle" type="button" data-mobile-nav-toggle aria-controls="primary-navigation" aria-expanded="false" aria-label="Buka navigasi" title="Buka navigasi">
            <span class="nav-toggle-icon" aria-hidden="true"><span></span><span></span><span></span></span>
        </button>
    </div>
</nav>
<div class="content">

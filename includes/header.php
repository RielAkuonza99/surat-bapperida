<?php
if (!isset($pageTitle)) $pageTitle = 'Surat Masuk Kantor';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#14283f">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
    <link href="<?= e(url('assets/css/responsive.css')) ?>" rel="stylesheet">
    <link href="<?= e(url('assets/css/application.css?v=5')) ?>" rel="stylesheet">
</head>
<body>
<?php if (!isLoggedIn()): ?>
<div class="auth-page-shell">
<?php else: ?>
<div class="app <?= (($_COOKIE['bapperida_sidebar_collapsed'] ?? '') === '1') ? 'sidebar-collapsed' : '' ?>">
<?php endif; ?>

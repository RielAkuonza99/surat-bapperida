<?php
if (!isset($pageTitle)) $pageTitle = 'Surat Masuk Kantor';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#111827">
    <title><?= e($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/responsive.css" rel="stylesheet">
</head>
<body>
<div id="page-loader" class="page-loader">
    <div class="loader-ring"></div>
    <span>Loading...</span>
</div>
<?php if (basename($_SERVER['SCRIPT_NAME']) === 'index.php' && !isset($_SESSION['user_id'])): ?>
<div class="auth-page-shell">
<?php else: ?>
<div class="app">
<?php endif; ?>

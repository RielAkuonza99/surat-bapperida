<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatTanggal(?string $date): string
{
    if ($date === null || trim($date) === '') {
        return '-';
    }

    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    $errors = DateTimeImmutable::getLastErrors();
    if ($parsed === false || $parsed->format('Y-m-d') !== $date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        return '-';
    }

    return $parsed->format('d/m/Y');
}

function sifatBadge(string $sifat, bool $compact = false): string
{
    $label = $sifat !== '' ? $sifat : 'Tidak diketahui';
    $variant = match ($label) {
        'Sangat Segera' => ' sifat-segera',
        'Segera', 'Penting' => ' sifat-penting',
        'Rahasia' => ' sifat-rahasia',
        default => '',
    };
    $compactClass = $compact ? ' badge-sifat-compact' : '';

    return '<span class="badge-sifat' . $variant . $compactClass . '">' . e($label) . '</span>';
}

function disposisiIndicator(int $count): string
{
    $count = min(max($count, 0), 3);

    return '<span aria-label="Disposisi ' . $count . ' dari 3 tahap">' . $count . '/3</span>';
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validateCsrf(): void
{
    $token = (string) ($_POST['csrf_token'] ?? '');
    $knownToken = (string) ($_SESSION['csrf_token'] ?? '');

    if ($knownToken === '' || !hash_equals($knownToken, $token)) {
        http_response_code(403);
        exit('Token keamanan tidak valid. Silakan muat ulang halaman.');
    }
}

function requirePost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Metode permintaan tidak diizinkan.');
    }
}

function redirect(string $location, array $query = []): never
{
    header('Location: ' . url($location, $query), true, 303);
    exit;
}

function url(string $path = '', array $query = []): string
{
    $relativePath = trim($path, '/');
    $url = APP_BASE_URL . ($relativePath === '' ? '/' : '/' . $relativePath);
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

function requestPath(): string
{
    $path = $_SERVER['APP_ROUTE_PATH'] ?? '/';

    return is_string($path) ? $path : '/';
}

function render(string $template, array $viewData = []): void
{
    extract($viewData, EXTR_SKIP);
    require APP_ROOT . '/app/Views/' . $template . '.php';
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}
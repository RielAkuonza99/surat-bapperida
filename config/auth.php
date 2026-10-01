<?php
require_once dirname(__DIR__) . '/app/bootstrap.php';

function requireLogin(): void
{
    App\Middleware\AuthMiddleware::requireLogin();
}

function redirectIfLoggedIn(string $redirect = 'dashboard.php'): void
{
    if (isLoggedIn()) {
        redirect($redirect);
    }
}

function logoutUser(): void
{
    (new App\Services\AuthService(
        new App\Models\UserModel(App\Database\Connection::get()),
        new App\Security\AuditLogger()
    ))->logout();
}

<?php
declare(strict_types=1);

namespace App\Middleware;

final class AuthMiddleware
{
    public static function requireLogin(): void
    {
        if (empty($_SESSION['user_id'])) {
            redirect('index.php?status=login_required');
        }
    }
}
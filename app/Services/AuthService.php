<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\UserModel;
use App\Security\AuditLogger;

final class AuthService
{
    public function __construct(private UserModel $users, private AuditLogger $audit)
    {
    }

    public function authenticate(string $username, string $password): bool
    {
        $user = $this->users->findByUsername($username);
        if (!$user || !password_verify($password, $user['password'])) {
            $this->audit->record('LOGIN_FAILURE', 'auth');
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
        ];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $this->audit->record('LOGIN', 'auth', null, (int) $user['id']);

        return true;
    }

    public function logout(): void
    {
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
        if ($userId !== null) {
            $this->audit->record('LOGOUT', 'auth', null, $userId);
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $parameters['path'],
                'domain' => $parameters['domain'],
                'secure' => $parameters['secure'],
                'httponly' => $parameters['httponly'],
                'samesite' => $parameters['samesite'] ?? 'Lax',
            ]);
        }
        session_destroy();
    }
}
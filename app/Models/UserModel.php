<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class UserModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, username, password FROM users WHERE username = :username LIMIT 1');
        $statement->execute(['username' => $username]);
        $user = $statement->fetch();

        return $user ?: null;
    }
}
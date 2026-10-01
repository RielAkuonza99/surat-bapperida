<?php
declare(strict_types=1);

namespace App\Security;

final class AuditLogger
{
    public function record(string $action, string $module, ?int $recordId = null, ?int $userId = null): void
    {
        $directory = APP_ROOT . '/storage/logs';
        if (!is_dir($directory)) {
            mkdir($directory, 0750, true);
        }

        $entry = [
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
            'created_at' => date(DATE_ATOM),
        ];

        file_put_contents($directory . '/audit.log', json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
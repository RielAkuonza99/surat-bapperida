<?php
declare(strict_types=1);

namespace App\Models;

use App\Security\SecretVault;
use App\Services\DriveConfigurationException;
use PDO;
use RuntimeException;

final class GoogleDriveApiModel
{
    public function __construct(
        private PDO $pdo,
        private SecretVault $vault
    ) {
    }

    public function all(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, folder_id, service_account_email, is_enabled, health_status,
                    last_health_check, last_used_at, last_error, credentials_encrypted IS NOT NULL AS configured
             FROM google_drive_apis ORDER BY id'
        );

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, folder_id, service_account_email, is_enabled, health_status,
                    last_health_check, last_used_at, last_error, credentials_encrypted
             FROM google_drive_apis WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
        $config = $statement->fetch();

        return $config ?: null;
    }

    public function enabled(): array
    {
        $statement = $this->pdo->query(
            'SELECT id, name, folder_id, service_account_email, credentials_encrypted
             FROM google_drive_apis WHERE is_enabled = 1 ORDER BY id'
        );
        $configs = [];
        foreach ($statement->fetchAll() as $row) {
            if ($row['credentials_encrypted'] === null) {
                continue;
            }
            $json = $this->vault->decrypt((string) $row['credentials_encrypted']);
            $credentials = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($credentials)) {
                throw new RuntimeException('Credential Google Drive pada konfigurasi aktif tidak valid.');
            }
            $row['credentials'] = $credentials;
            unset($row['credentials_encrypted']);
            $configs[] = $row;
        }

        return $configs;
    }

    public function save(
        int $id,
        string $name,
        string $folderId,
        string $serviceAccountEmail,
        ?string $encryptedCredentials,
        bool $enabled
    ): void {
        if ($id < 1 || $id > 3) {
            throw new DriveConfigurationException('Nomor konfigurasi Google Drive harus antara 1 dan 3.');
        }

        $existing = $this->find($id);
        $secret = $encryptedCredentials ?? $existing['credentials_encrypted'] ?? null;
        if ($secret === null) {
            throw new DriveConfigurationException('Credential service account wajib diisi untuk konfigurasi baru.');
        }
        if ($enabled && ($folderId === '' || $serviceAccountEmail === '')) {
            throw new DriveConfigurationException('Folder ID dan email service account wajib tersedia sebelum API diaktifkan.');
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO google_drive_apis
                (id, name, folder_id, service_account_email, credentials_encrypted, is_enabled,
                 health_status, last_error, created_at, updated_at)
             VALUES
                (:id, :name, :folder_id, :email, :credentials, :enabled, \'unknown\', NULL, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                name = VALUES(name), folder_id = VALUES(folder_id),
                service_account_email = VALUES(service_account_email),
                credentials_encrypted = VALUES(credentials_encrypted),
                is_enabled = VALUES(is_enabled), health_status = \'unknown\',
                last_error = NULL, updated_at = NOW()'
        );
        $statement->execute([
            'id' => $id,
            'name' => $name,
            'folder_id' => $folderId,
            'email' => $serviceAccountEmail,
            'credentials' => $secret,
            'enabled' => $enabled ? 1 : 0,
        ]);
    }

    public function recordHealth(int $id, string $status, ?string $error): void
    {
        if (!in_array($status, ['healthy', 'error'], true)) {
            throw new RuntimeException('Status kesehatan API tidak dikenal.');
        }

        $statement = $this->pdo->prepare(
            'UPDATE google_drive_apis
             SET health_status = :status, last_health_check = NOW(), last_error = :error,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $statement->execute([
            'status' => $status,
            'error' => $error === null ? null : mb_substr($error, 0, 500),
            'id' => $id,
        ]);
    }

    public function recordUse(int $id): void
    {
        $statement = $this->pdo->prepare(
            "UPDATE google_drive_apis
             SET health_status = 'healthy', last_used_at = NOW(), last_error = NULL,
                 updated_at = NOW()
             WHERE id = :id"
        );
        $statement->execute(['id' => $id]);
    }
}

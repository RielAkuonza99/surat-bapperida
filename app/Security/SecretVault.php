<?php
declare(strict_types=1);

namespace App\Security;

use App\Services\DriveConfigurationException;

final class SecretVault
{
    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $this->key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        if ($ciphertext === false || strlen($tag) !== 16) {
            throw new DriveConfigurationException('Credential Google Drive tidak dapat dienkripsi.');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $value): string
    {
        $data = base64_decode($value, true);
        if ($data === false || strlen($data) < 29) {
            throw new DriveConfigurationException('Credential Google Drive terenkripsi tidak valid.');
        }

        $plaintext = openssl_decrypt(
            substr($data, 28),
            'aes-256-gcm',
            $this->key(),
            OPENSSL_RAW_DATA,
            substr($data, 0, 12),
            substr($data, 12, 16)
        );
        if ($plaintext === false) {
            throw new DriveConfigurationException('Credential Google Drive tidak dapat dibuka; periksa APP_ENCRYPTION_KEY.');
        }

        return $plaintext;
    }

    private function key(): string
    {
        $configured = (string) (getenv('APP_ENCRYPTION_KEY') ?: '');
        if (preg_match('/^[a-f0-9]{64}$/i', $configured) !== 1) {
            throw new DriveConfigurationException('APP_ENCRYPTION_KEY harus diisi dengan 64 karakter hexadecimal.');
        }
        $key = hex2bin($configured);
        if ($key === false) {
            throw new DriveConfigurationException('APP_ENCRYPTION_KEY tidak valid.');
        }

        return $key;
    }
}

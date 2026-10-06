<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Connection;
use App\Models\ArchiveSyncModel;
use App\Models\GoogleDriveApiModel;
use App\Security\AuditLogger;
use App\Security\SecretVault;
use JsonException;
use DomainException;
use PDO;

final class GoogleDriveService
{
    private const DRIVE_SCOPE = 'https://www.googleapis.com/auth/drive.file';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const API_BASE = 'https://www.googleapis.com/drive/v3';
    private const UPLOAD_BASE = 'https://www.googleapis.com/upload/drive/v3';
    private const MAX_FILE_SIZE = 10485760;

    private GoogleDriveApiModel $apis;
    private SecretVault $vault;

    public function __construct(private PDO $pdo)
    {
        $this->vault = new SecretVault();
        $this->apis = new GoogleDriveApiModel($pdo, $this->vault);
    }

    public static function validateServiceAccount(string $json): array
    {
        try {
            $credentials = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new DomainException('Isi credential harus berupa JSON service account Google yang valid.', 0, $error);
        }
        if (
            !is_array($credentials)
            || ($credentials['type'] ?? null) !== 'service_account'
            || !filter_var($credentials['client_email'] ?? null, FILTER_VALIDATE_EMAIL)
            || !is_string($credentials['private_key'] ?? null)
            || !str_contains($credentials['private_key'], '-----BEGIN PRIVATE KEY-----')
        ) {
            throw new DomainException('Credential harus memuat type service_account, client_email, dan private_key yang valid.');
        }
        $privateKey = openssl_pkey_get_private($credentials['private_key']);
        if ($privateKey === false) {
            throw new DomainException('Private key service account tidak dapat dibaca.');
        }
        if (isset($credentials['token_uri']) && $credentials['token_uri'] !== self::TOKEN_URL) {
            throw new DomainException('Endpoint token credential tidak cocok dengan endpoint Google yang diizinkan.');
        }

        return $credentials;
    }

    public function configs(): array
    {
        return $this->apis->all();
    }

    public function saveConfig(int $id, array $input): void
    {
        if ($id < 1 || $id > 3) {
            throw new DomainException('Nomor konfigurasi API harus antara 1 dan 3.');
        }
        $name = trim((string) ($input['name'] ?? ''));
        $folderId = trim((string) ($input['folder_id'] ?? ''));
        $email = trim((string) ($input['service_account_email'] ?? ''));
        $credentialsJson = trim((string) ($input['credentials_json'] ?? ''));
        $enabled = isset($input['is_enabled']) && (string) $input['is_enabled'] === '1';

        if ($name === '' || mb_strlen($name) > 100) {
            throw new DomainException('Nama konfigurasi wajib diisi dan maksimal 100 karakter.');
        }
        if ($folderId !== '' && preg_match('/^[A-Za-z0-9_-]{5,255}$/', $folderId) !== 1) {
            throw new DomainException('Folder ID Google Drive tidak valid.');
        }

        $existing = $this->apis->find($id);
        $encryptedCredentials = null;
        if ($credentialsJson !== '') {
            $credentials = self::validateServiceAccount($credentialsJson);
            $email = (string) $credentials['client_email'];
            $encryptedCredentials = $this->vault->encrypt($credentialsJson);
        } elseif ($existing !== null) {
            $email = (string) $existing['service_account_email'];
        }

        if ($enabled && $folderId === '') {
            throw new DomainException('Folder ID wajib diisi sebelum konfigurasi API diaktifkan.');
        }

        $this->apis->save($id, $name, $folderId, $email, $encryptedCredentials, $enabled);
    }

    public function testConnection(int $id): void
    {
        $config = $this->configForTest($id);
        try {
            $token = $this->accessToken($config);
            $url = self::API_BASE . '/files/' . rawurlencode((string) $config['folder_id']) . '?' .
                http_build_query(['fields' => 'id,name,mimeType']);
            $result = $this->request('GET', $url, $token);
            $folder = $this->decodeJson($result['body']);
            if (
                !is_array($folder)
                || ($folder['id'] ?? null) !== $config['folder_id']
                || ($folder['mimeType'] ?? null) !== 'application/vnd.google-apps.folder'
            ) {
                throw new DriveRequestException('Folder tujuan tidak dapat diverifikasi.', false);
            }
            $this->apis->recordHealth($id, 'healthy', null);
        } catch (DriveRequestException $error) {
            $this->apis->recordHealth($id, 'error', $error->getMessage());
            throw $error;
        }
    }

    public function upload(array $upload, int $suratId, int $userId): array
    {
        $file = $this->validateUpload($upload);
        $configs = $this->apis->enabled();
        if ($configs === []) {
            throw new DomainException('Belum ada konfigurasi Google Drive aktif. Metadata surat tersimpan, tetapi file belum diunggah.');
        }

        $bytes = file_get_contents($file['tmp_name']);
        if ($bytes === false) {
            throw new DomainException('File PDF sementara tidak dapat dibaca.');
        }
        $uploadId = bin2hex(random_bytes(16));
        $uncertainResult = false;
        $lastError = null;

        foreach ($configs as $config) {
            $token = null;
            try {
                $token = $this->accessToken($config);
                if ($uncertainResult) {
                    $existing = $this->findUpload($config, $token, $uploadId);
                    if ($existing !== null) {
                        $sourceApiId = (int) ($existing['properties']['bapperida_api_id'] ?? $config['id']);
                        $this->apis->recordHealth((int) $config['id'], 'healthy', null);

                        return $this->saveUploadedDocument($suratId, $userId, $sourceApiId, $file, $existing);
                    }
                    throw new DriveUploadException(
                        'Status file pada Google Drive sebelumnya belum dapat dipastikan. Tidak dilakukan failover untuk mencegah file ganda.'
                    );
                }
                $stored = $this->uploadToDrive($config, $token, $file, $bytes, $suratId, $uploadId);
                $this->apis->recordUse((int) $config['id']);

                return $this->saveUploadedDocument($suratId, $userId, (int) $config['id'], $file, $stored);
            } catch (DriveRequestException $error) {
                $lastError = $error;
                $this->apis->recordHealth((int) $config['id'], 'error', $error->getMessage());
                (new AuditLogger($this->pdo))->record(
                    'DRIVE_API_FAILURE',
                    'google_drive',
                    (int) $config['id'],
                    $userId,
                    null,
                    ['http_status' => $error->httpStatus, 'retryable' => $error->retryable]
                );
                if ($error->mayHaveSucceeded && $token !== null) {
                    try {
                        $existing = $this->findUpload($config, $token, $uploadId);
                        if ($existing !== null) {
                            $this->apis->recordHealth((int) $config['id'], 'healthy', null);

                            return $this->saveUploadedDocument(
                                $suratId,
                                $userId,
                                (int) ($existing['properties']['bapperida_api_id'] ?? $config['id']),
                                $file,
                                $existing
                            );
                        }
                    } catch (DriveRequestException $lookupError) {
                        (new AuditLogger($this->pdo))->record(
                            'DRIVE_UPLOAD_RECONCILIATION_FAILED',
                            'google_drive',
                            (int) $config['id'],
                            $userId,
                            null,
                            ['http_status' => $lookupError->httpStatus]
                        );
                    }
                }
                $uncertainResult = $uncertainResult || $error->mayHaveSucceeded;
                if (!$error->retryable) {
                    throw new DriveUploadException(
                        'Upload ditolak oleh konfigurasi Google Drive ' . (int) $config['id'] . '. Periksa status konfigurasi.',
                        0,
                        $error
                    );
                }
            }
        }

        $message = $uncertainResult
            ? 'Status upload Google Drive belum dapat dipastikan. Periksa folder tujuan sebelum mencoba kembali.'
            : 'Upload belum berhasil. Semua konfigurasi API yang aktif gagal; metadata surat tetap tersimpan dan file dapat dicoba kembali.';
        throw new DriveUploadException($message, 0, $lastError);
    }

    private function configForTest(int $id): array
    {
        if ($id < 1 || $id > 3) {
            throw new DomainException('Nomor konfigurasi API tidak valid.');
        }
        $row = $this->apis->find($id);
        if ($row === null || $row['credentials_encrypted'] === null) {
            throw new DomainException('Konfigurasi API belum memiliki credential service account.');
        }
        $credentials = self::validateServiceAccount($this->vault->decrypt((string) $row['credentials_encrypted']));
        $row['credentials'] = $credentials;

        return $row;
    }

    private function accessToken(array $config): string
    {
        $credentials = $config['credentials'] ?? null;
        if (!is_array($credentials) || !isset($credentials['client_email'], $credentials['private_key'])) {
            throw new DriveRequestException('Credential service account tidak lengkap.', false);
        }

        $now = time();
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => self::DRIVE_SCOPE,
            'aud' => self::TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));
        $unsigned = $header . '.' . $claims;
        $signed = openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256);
        if (!$signed) {
            throw new DriveRequestException('JWT service account tidak dapat ditandatangani.', false);
        }
        $assertion = $unsigned . '.' . $this->base64UrlEncode($signature);
        $response = $this->request(
            'POST',
            self::TOKEN_URL,
            null,
            ['Content-Type: application/x-www-form-urlencoded'],
            http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ])
        );
        $token = $this->decodeJson($response['body']);
        if (!is_array($token) || !is_string($token['access_token'] ?? null) || $token['access_token'] === '') {
            throw new DriveRequestException('Google tidak mengembalikan access token yang valid.', false);
        }

        return $token['access_token'];
    }

    private function uploadToDrive(
        array $config,
        string $token,
        array $file,
        string $bytes,
        int $suratId,
        string $uploadId
    ): array {
        $boundary = 'bapperida_' . bin2hex(random_bytes(12));
        $metadata = json_encode([
            'name' => $file['name'],
            'mimeType' => 'application/pdf',
            'parents' => [(string) $config['folder_id']],
            'properties' => [
                'bapperida_upload_id' => $uploadId,
                'bapperida_api_id' => (string) $config['id'],
                'surat_id' => (string) $suratId,
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $body = '--' . $boundary . "\r\n"
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . $metadata . "\r\n"
            . '--' . $boundary . "\r\n"
            . "Content-Type: application/pdf\r\n\r\n"
            . $bytes . "\r\n"
            . '--' . $boundary . "--\r\n";
        $url = self::UPLOAD_BASE . '/files?' . http_build_query([
            'uploadType' => 'multipart',
            'supportsAllDrives' => 'true',
            'fields' => 'id,name,mimeType,size,webViewLink,properties',
        ]);
        $result = $this->request(
            'POST',
            $url,
            $token,
            ['Content-Type: multipart/related; boundary=' . $boundary],
            $body,
            true
        );
        $stored = $this->decodeJson($result['body']);
        if (
            !is_array($stored)
            || !is_string($stored['id'] ?? null)
            || ($stored['mimeType'] ?? null) !== 'application/pdf'
        ) {
            throw new DriveRequestException('Respons upload Google Drive tidak valid.', true, true);
        }
        $stored['webViewLink'] = is_string($stored['webViewLink'] ?? null)
            ? $stored['webViewLink']
            : 'https://drive.google.com/file/d/' . rawurlencode($stored['id']) . '/view';

        return $stored;
    }

    private function findUpload(array $config, string $token, string $uploadId): ?array
    {
        $escape = static fn (string $value): string => str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
        $query = "'" . $escape((string) $config['folder_id']) . "' in parents"
            . " and properties has { key='bapperida_upload_id' and value='" . $escape($uploadId) . "' }"
            . " and trashed = false";
        $url = self::API_BASE . '/files?' . http_build_query([
            'q' => $query,
            'fields' => 'files(id,name,mimeType,size,webViewLink,properties)',
            'supportsAllDrives' => 'true',
            'includeItemsFromAllDrives' => 'true',
            'pageSize' => 1,
        ]);
        $result = $this->request('GET', $url, $token);
        $response = $this->decodeJson($result['body']);
        if (!is_array($response) || !isset($response['files']) || !is_array($response['files'])) {
            throw new DriveRequestException('Respons pencarian file Google Drive tidak valid.', true);
        }
        $file = $response['files'][0] ?? null;
        if (!is_array($file) || !is_string($file['id'] ?? null)) {
            return null;
        }
        if (($file['mimeType'] ?? null) !== 'application/pdf') {
            throw new DriveRequestException('File dengan ID upload yang sama bukan PDF.', false);
        }
        $file['webViewLink'] = is_string($file['webViewLink'] ?? null)
            ? $file['webViewLink']
            : 'https://drive.google.com/file/d/' . rawurlencode($file['id']) . '/view';

        return $file;
    }

    private function saveUploadedDocument(
        int $suratId,
        int $userId,
        int $apiId,
        array $file,
        array $stored
    ): array {
        $fileId = (string) $stored['id'];
        $webViewUrl = (string) $stored['webViewLink'];
        if (
            filter_var($webViewUrl, FILTER_VALIDATE_URL) === false
            || strtolower((string) parse_url($webViewUrl, PHP_URL_SCHEME)) !== 'https'
            || !in_array(strtolower((string) parse_url($webViewUrl, PHP_URL_HOST)), ['drive.google.com', 'docs.google.com'], true)
        ) {
            throw new DriveUploadException('Google Drive mengembalikan tautan dokumen yang tidak valid.');
        }

        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO surat_documents
                    (surat_id, drive_api_id, drive_file_id, file_name, mime_type, file_size,
                     web_view_url, uploaded_by, uploaded_at)
                 VALUES
                    (:surat_id, :api_id, :file_id, :file_name, \'application/pdf\', :file_size,
                     :web_url, :user_id, UTC_TIMESTAMP())'
            );
            $statement->execute([
                'surat_id' => $suratId,
                'api_id' => $apiId,
                'file_id' => $fileId,
                'file_name' => $file['name'],
                'file_size' => $file['size'],
                'web_url' => $webViewUrl,
                'user_id' => $userId,
            ]);

            $update = $this->pdo->prepare('UPDATE surat_masuk SET link_drive = :url WHERE id = :id');
            $update->execute(['url' => $webViewUrl, 'id' => $suratId]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('Data surat tidak dapat ditautkan ke file Google Drive.');
            }
            (new ArchiveSyncModel($this->pdo))->enqueueUpsert($suratId);
            $documentId = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }

        $this->apis->recordUse($apiId);
        (new AuditLogger($this->pdo))->record(
            'DOCUMENT_UPLOADED',
            'surat_masuk',
            $suratId,
            $userId,
            null,
            ['drive_api_id' => $apiId, 'file_name' => $file['name'], 'file_size' => $file['size']]
        );

        return ['id' => $documentId, 'url' => $webViewUrl];
    }

    private function validateUpload(array $upload): array
    {
        $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            $message = match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File melebihi batas upload server.',
                UPLOAD_ERR_NO_FILE => 'Pilih file PDF terlebih dahulu.',
                default => 'File PDF tidak dapat diterima. Silakan coba kembali.',
            };
            throw new DomainException($message);
        }

        $temporaryPath = $upload['tmp_name'] ?? null;
        $originalName = $upload['name'] ?? null;
        if (!is_string($temporaryPath) || !is_uploaded_file($temporaryPath) || !is_string($originalName)) {
            throw new DomainException('File upload tidak valid.');
        }
        $size = filesize($temporaryPath);
        if ($size === false || $size < 1 || $size > self::MAX_FILE_SIZE) {
            throw new DomainException('Ukuran PDF harus lebih dari 0 dan tidak melebihi 10 MiB.');
        }
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'pdf') {
            throw new DomainException('Ekstensi file harus PDF.');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        if ($finfo->file($temporaryPath) !== 'application/pdf') {
            throw new DomainException('Konten file tidak terdeteksi sebagai PDF.');
        }
        $handle = fopen($temporaryPath, 'rb');
        if ($handle === false) {
            throw new DomainException('File PDF tidak dapat dibaca.');
        }
        $signature = fread($handle, 1024);
        fclose($handle);
        if (!is_string($signature) || strpos($signature, '%PDF-') === false) {
            throw new DomainException('Signature dokumen PDF tidak valid.');
        }

        $baseName = pathinfo(str_replace('\\', '/', $originalName), PATHINFO_FILENAME);
        $safeName = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '_', $baseName), '._-');
        $safeName = substr($safeName !== '' ? $safeName : 'dokumen-surat', 0, 120) . '.pdf';

        return ['tmp_name' => $temporaryPath, 'name' => $safeName, 'size' => $size];
    }

    private function request(
        string $method,
        string $url,
        ?string $token,
        array $headers = [],
        ?string $body = null,
        bool $ambiguousOnServerError = false
    ): array {
        if ($token !== null) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        $handle = curl_init($url);
        if ($handle === false) {
            throw new DriveRequestException('Koneksi Google Drive tidak dapat dimulai.', true);
        }
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => $method === 'POST' && str_contains($url, '/upload/') ? 60 : 20,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        if ($response === false) {
            throw new DriveRequestException(
                'Google Drive tidak merespons dalam batas waktu.',
                true,
                $ambiguousOnServerError
            );
        }
        if ($status < 200 || $status >= 300) {
            $retryable = $status === 408 || $status === 429 || $status >= 500;
            throw new DriveRequestException(
                'Google Drive mengembalikan HTTP ' . $status . '.',
                $retryable,
                $ambiguousOnServerError && $status >= 500,
                $status
            );
        }

        return ['status' => $status, 'body' => $response];
    }

    private function decodeJson(string $json): mixed
    {
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new DriveRequestException('Respons Google Drive tidak dapat dibaca.', false, false, 0);
        }
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

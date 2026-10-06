<?php
declare(strict_types=1);

namespace App\Services;

use JsonException;
use RuntimeException;

final class SupabaseArchiveClient
{
    private string $baseUrl;
    private string $apiKey;
    private string $table;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) (getenv('SUPABASE_URL') ?: ''), '/');
        $this->apiKey = (string) (getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '');
        $this->table = (string) (getenv('SUPABASE_ARCHIVE_TABLE') ?: 'archive_surat_metadata');
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->apiKey !== '' && $this->validBaseUrl() && $this->validTable();
    }

    public function configurationStatus(): string
    {
        if ($this->baseUrl === '' || $this->apiKey === '') {
            return 'Belum dikonfigurasi';
        }
        if (!$this->validBaseUrl() || !$this->validTable()) {
            return 'Konfigurasi tidak valid';
        }

        return 'Siap';
    }

    /**
     * @throws JsonException
     */
    public function send(array $event): void
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Konfigurasi Supabase belum lengkap atau tidak valid.');
        }

        $endpoint = $this->baseUrl . '/rest/v1/' . rawurlencode($this->table);
        $method = strtoupper((string) ($event['operation'] ?? ''));
        $searchId = (string) ($event['search_id'] ?? '');
        if ($searchId === '') {
            throw new RuntimeException('ID pencarian pada antrean metadata tidak tersedia.');
        }

        $headers = [
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->apiKey,
            'Accept: application/json',
        ];
        if ($method === 'UPSERT') {
            $payload = $event['payload'] ?? null;
            if (!is_array($payload) || ($payload['search_id'] ?? null) !== $searchId) {
                throw new RuntimeException('Payload antrean metadata tidak sesuai dengan ID pencarian.');
            }
            $endpoint .= '?on_conflict=search_id';
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Prefer: resolution=merge-duplicates,return=representation';
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } elseif ($method === 'DELETE') {
            $endpoint .= '?search_id=eq.' . rawurlencode($searchId);
            $headers[] = 'Prefer: return=minimal';
            $body = null;
        } else {
            throw new RuntimeException('Operasi antrean metadata tidak dikenal.');
        }

        $handle = curl_init($endpoint);
        if ($handle === false) {
            throw new RuntimeException('Koneksi ke provider metadata tidak dapat dimulai.');
        }
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method === 'UPSERT' ? 'POST' : 'DELETE',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $curlError = curl_error($handle);
        curl_close($handle);

        if ($response === false) {
            throw new RuntimeException('Koneksi provider metadata gagal: ' . ($curlError ?: 'kesalahan transport.'));
        }
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Provider metadata mengembalikan HTTP ' . $status . '.');
        }

        if ($method === 'UPSERT') {
            $rows = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($rows) || !isset($rows[0]['search_id']) || $rows[0]['search_id'] !== $searchId) {
                throw new RuntimeException('Provider tidak mengonfirmasi metadata surat yang dikirim.');
            }
        }
    }

    private function validBaseUrl(): bool
    {
        $parts = parse_url($this->baseUrl);

        return is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && isset($parts['host'])
            && !isset($parts['user'])
            && !isset($parts['pass'])
            && !isset($parts['query'])
            && !isset($parts['fragment']);
    }

    private function validTable(): bool
    {
        return preg_match('/^[a-z][a-z0-9_]{0,62}$/', $this->table) === 1;
    }
}

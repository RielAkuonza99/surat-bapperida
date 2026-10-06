<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database\Connection;
use App\Middleware\AuthMiddleware;
use App\Models\GoogleDriveApiModel;
use App\Security\AuditLogger;
use App\Security\SecretVault;
use App\Services\DriveConfigurationException;
use App\Services\DriveRequestException;
use App\Services\GoogleDriveService;
use DomainException;

final class GoogleDriveController
{
    public function index(): void
    {
        AuthMiddleware::requireAdmin();
        $this->renderPage();
    }

    public function save(): void
    {
        AuthMiddleware::requireAdmin();
        requirePost();
        validateCsrf();

        $id = $this->slotId();
        try {
            $pdo = Connection::get();
            (new GoogleDriveService($pdo))->saveConfig($id, $_POST);
            (new AuditLogger($pdo))->record(
                'DRIVE_API_CONFIG_UPDATED',
                'google_drive',
                $id,
                (int) currentUser()['id'],
                null,
                ['enabled' => isset($_POST['is_enabled']) && (string) $_POST['is_enabled'] === '1']
            );
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Konfigurasi Google Drive berhasil disimpan secara terenkripsi.'];
        } catch (DomainException | DriveConfigurationException $error) {
            $_SESSION['flash'] = ['type' => 'danger', 'message' => $error->getMessage()];
        }
        redirect('pengaturan/google-drive');
    }

    public function test(): void
    {
        AuthMiddleware::requireAdmin();
        requirePost();
        validateCsrf();

        $id = $this->slotId();
        $pdo = Connection::get();
        try {
            (new GoogleDriveService($pdo))->testConnection($id);
            (new AuditLogger($pdo))->record(
                'DRIVE_API_HEALTH_CHECK',
                'google_drive',
                $id,
                (int) currentUser()['id'],
                null,
                ['status' => 'healthy']
            );
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Koneksi dan folder Google Drive berhasil diverifikasi.'];
        } catch (DomainException | DriveConfigurationException | DriveRequestException $error) {
            (new GoogleDriveApiModel($pdo, new SecretVault()))->recordHealth($id, 'error', $error->getMessage());
            (new AuditLogger($pdo))->record(
                'DRIVE_API_HEALTH_CHECK',
                'google_drive',
                $id,
                (int) currentUser()['id'],
                null,
                ['status' => 'error']
            );
            $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Pemeriksaan Google Drive gagal. Periksa konfigurasi dan status API.'];
        }
        redirect('pengaturan/google-drive');
    }

    private function renderPage(): void
    {
        $pdo = Connection::get();
        $notice = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        render('drive-api/index', [
            'pageTitle' => 'Konfigurasi Google Drive',
            'configs' => (new GoogleDriveService($pdo))->configs(),
            'notice' => $notice,
        ]);
    }

    private function slotId(): int
    {
        $id = filter_var($_GET['slot'] ?? '', FILTER_VALIDATE_INT);
        if ($id === false || $id < 1 || $id > 3) {
            http_response_code(400);
            exit('Nomor konfigurasi Google Drive tidak valid.');
        }

        return (int) $id;
    }
}

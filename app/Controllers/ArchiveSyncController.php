<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database\Connection;
use App\Middleware\AuthMiddleware;
use App\Models\ArchiveSyncModel;
use App\Security\AuditLogger;
use App\Services\SupabaseArchiveClient;

final class ArchiveSyncController
{
    public function index(): void
    {
        AuthMiddleware::requireAdmin();
        $pdo = Connection::get();
        $queue = new ArchiveSyncModel($pdo);

        render('archive-sync/index', [
            'pageTitle' => 'Sinkronisasi Metadata',
            'summary' => $queue->summary(),
            'events' => $queue->latest(),
            'providerStatus' => (new SupabaseArchiveClient())->configurationStatus(),
            'notice' => $_SESSION['flash'] ?? null,
        ]);
        unset($_SESSION['flash']);
    }

    public function retry(): void
    {
        AuthMiddleware::requireAdmin();
        requirePost();
        validateCsrf();

        $id = filter_var($_POST['event_id'] ?? '', FILTER_VALIDATE_INT);
        if ($id === false || (int) $id < 1) {
            http_response_code(400);
            exit('Data antrean yang dipilih tidak valid.');
        }

        $pdo = Connection::get();
        $queue = new ArchiveSyncModel($pdo);
        if (!$queue->retry((int) $id)) {
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Hanya data berstatus gagal yang dapat dijadwalkan ulang.'];
            redirect('sinkronisasi');
        }

        (new AuditLogger($pdo))->record(
            'ARCHIVE_SYNC_RETRY',
            'archive_sync',
            (int) $id,
            (int) currentUser()['id']
        );
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Data dijadwalkan ulang. Worker akan mengirimnya sesuai batas batch.'];
        redirect('sinkronisasi');
    }
}

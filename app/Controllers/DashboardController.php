<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database\Connection;
use App\Middleware\AuthMiddleware;
use App\Models\SuratModel;
use App\Security\AuditLogger;
use App\Services\SuratService;

final class DashboardController
{
    public function index(): void
    {
        AuthMiddleware::requireLogin();
        $pdo = Connection::get();
        $summary = (new SuratService(new SuratModel($pdo), new AuditLogger($pdo)))->dashboard();
        render('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'total' => $summary['total'],
            'disposisi' => $summary['disposisi'],
            'belum' => $summary['total'] - $summary['disposisi'],
            'terbaru' => $summary['terbaru'],
        ]);
    }
}
<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database\Connection;
use App\Middleware\AuthMiddleware;
use App\Models\SystemMetricsModel;
use RuntimeException;

final class SettingsController
{
    public function index(): void
    {
        AuthMiddleware::requireLogin();
        $user = currentUser();
        $isAdmin = ($user['role'] ?? '') === 'admin';
        $metrics = null;
        $metricsUnavailable = false;
        if ($isAdmin) {
            try {
                $metrics = (new SystemMetricsModel(Connection::get()))->dashboard();
            } catch (RuntimeException $error) {
                error_log('Settings dashboard metrics unavailable: ' . $error->getMessage());
                $metricsUnavailable = true;
            }
        }

        render('settings/index', [
            'pageTitle' => 'Pengaturan',
            'profile' => $user,
            'isAdmin' => $isAdmin,
            'metrics' => $metrics,
            'metricsUnavailable' => $metricsUnavailable,
        ]);
    }
}

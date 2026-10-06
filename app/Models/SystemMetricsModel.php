<?php
declare(strict_types=1);

namespace App\Models;

use PDO;

final class SystemMetricsModel
{
    public function __construct(private PDO $pdo)
    {
    }

    public function dashboard(): array
    {
        $startedAt = hrtime(true);
        $this->pdo->query('SELECT 1')->fetchColumn();
        $databaseLatency = (hrtime(true) - $startedAt) / 1_000_000;

        $queueStatement = $this->pdo->query(
            "SELECT status, COUNT(*) AS total
             FROM archive_sync_queue
             WHERE status IN ('PENDING', 'PROCESSING', 'SENT', 'CONFIRMED', 'FAILED')
             GROUP BY status"
        );
        $queue = array_fill_keys(['PENDING', 'PROCESSING', 'SENT', 'CONFIRMED', 'FAILED'], 0);
        foreach ($queueStatement->fetchAll() as $row) {
            $queue[$row['status']] = (int) $row['total'];
        }

        $driveStatement = $this->pdo->query(
            'SELECT id, name, is_enabled, health_status, last_health_check, last_used_at
             FROM google_drive_apis ORDER BY id'
        );
        $driveApis = $driveStatement->fetchAll();

        $diskFree = disk_free_space(APP_ROOT);
        $diskTotal = disk_total_space(APP_ROOT);
        $snapshot = [
            'php_memory_bytes' => memory_get_usage(true),
            'database_latency_ms' => round($databaseLatency, 2),
            'disk_free_bytes' => $diskFree === false ? null : (int) $diskFree,
            'disk_total_bytes' => $diskTotal === false ? null : (int) $diskTotal,
            'queue_pending' => $queue['PENDING'],
            'queue_processing' => $queue['PROCESSING'],
            'queue_failed' => $queue['FAILED'],
            'queue_confirmed' => $queue['CONFIRMED'],
            'drive_enabled_count' => count(array_filter(
                $driveApis,
                static fn (array $api): bool => (int) $api['is_enabled'] === 1
            )),
            'drive_error_count' => count(array_filter(
                $driveApis,
                static fn (array $api): bool => $api['health_status'] === 'error'
            )),
        ];

        $bucketTimestamp = intdiv(time(), 300) * 300;
        $bucket = gmdate('Y-m-d H:i:s', $bucketTimestamp);
        $insert = $this->pdo->prepare(
            'INSERT IGNORE INTO system_metric_samples
                (sampled_at, php_memory_bytes, database_latency_ms, disk_free_bytes, disk_total_bytes,
                 queue_pending, queue_processing, queue_failed, queue_confirmed,
                 drive_enabled_count, drive_error_count)
             VALUES
                (:sampled_at, :memory, :database_latency, :disk_free, :disk_total,
                 :pending, :processing, :failed, :confirmed, :drive_enabled, :drive_errors)'
        );
        $insert->execute([
            'sampled_at' => $bucket,
            'memory' => $snapshot['php_memory_bytes'],
            'database_latency' => $snapshot['database_latency_ms'],
            'disk_free' => $snapshot['disk_free_bytes'],
            'disk_total' => $snapshot['disk_total_bytes'],
            'pending' => $snapshot['queue_pending'],
            'processing' => $snapshot['queue_processing'],
            'failed' => $snapshot['queue_failed'],
            'confirmed' => $snapshot['queue_confirmed'],
            'drive_enabled' => $snapshot['drive_enabled_count'],
            'drive_errors' => $snapshot['drive_error_count'],
        ]);

        if ($insert->rowCount() === 1) {
            $this->pdo->exec(
                'DELETE FROM system_metric_samples
                 WHERE sampled_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)'
            );
        }

        $historyStatement = $this->pdo->query(
            'SELECT sampled_at, php_memory_bytes, database_latency_ms, disk_free_bytes, disk_total_bytes,
                    queue_pending, queue_processing, queue_failed, queue_confirmed,
                    drive_enabled_count, drive_error_count
             FROM system_metric_samples
             WHERE sampled_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 24 HOUR)
             ORDER BY sampled_at DESC LIMIT 48'
        );
        $history = array_reverse($historyStatement->fetchAll());

        return [
            'current' => $snapshot,
            'history' => $history,
            'queue' => $queue,
            'drive_apis' => $driveApis,
        ];
    }
}

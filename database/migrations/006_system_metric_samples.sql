-- Jalankan satu kali setelah migration 005_google_drive_api_configs.sql.
CREATE TABLE system_metric_samples (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sampled_at DATETIME NOT NULL,
    php_memory_bytes BIGINT UNSIGNED NOT NULL,
    database_latency_ms DECIMAL(9,2) NOT NULL,
    disk_free_bytes BIGINT UNSIGNED NULL,
    disk_total_bytes BIGINT UNSIGNED NULL,
    queue_pending BIGINT UNSIGNED NOT NULL DEFAULT 0,
    queue_processing BIGINT UNSIGNED NOT NULL DEFAULT 0,
    queue_failed BIGINT UNSIGNED NOT NULL DEFAULT 0,
    queue_confirmed BIGINT UNSIGNED NOT NULL DEFAULT 0,
    drive_enabled_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    drive_error_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_system_metric_samples_sampled_at (sampled_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

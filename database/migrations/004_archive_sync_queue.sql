-- Jalankan satu kali setelah migration 003_private_authentication.sql.
-- Tabel ini menyimpan outbox metadata eksternal, bukan salinan tabel aplikasi.
CREATE TABLE archive_sync_queue (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    surat_id INT UNSIGNED NULL,
    search_id VARCHAR(20) NOT NULL,
    operation ENUM('upsert', 'delete') NOT NULL,
    payload JSON NOT NULL,
    status ENUM('PENDING', 'PROCESSING', 'SENT', 'CONFIRMED', 'FAILED', 'SUPERSEDED') NOT NULL DEFAULT 'PENDING',
    attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
    available_at DATETIME NOT NULL,
    locked_at DATETIME NULL,
    last_attempt_at DATETIME NULL,
    last_sent_at DATETIME NULL,
    confirmed_at DATETIME NULL,
    last_error VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_archive_queue_ready (status, available_at, id),
    INDEX idx_archive_queue_search_status (search_id, status),
    INDEX idx_archive_queue_surat (surat_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

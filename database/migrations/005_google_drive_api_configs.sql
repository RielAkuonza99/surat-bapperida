-- Jalankan satu kali setelah migration 004_archive_sync_queue.sql.
CREATE TABLE google_drive_apis (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    folder_id VARCHAR(255) NOT NULL,
    service_account_email VARCHAR(255) NOT NULL,
    credentials_encrypted MEDIUMTEXT NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    health_status ENUM('unknown', 'healthy', 'error') NOT NULL DEFAULT 'unknown',
    last_health_check DATETIME NULL,
    last_used_at DATETIME NULL,
    last_error VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    CONSTRAINT chk_google_drive_api_slot CHECK (id BETWEEN 1 AND 3),
    INDEX idx_google_drive_api_enabled (is_enabled, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE surat_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    surat_id INT UNSIGNED NOT NULL,
    drive_api_id TINYINT UNSIGNED NOT NULL,
    drive_file_id VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL,
    web_view_url VARCHAR(500) NOT NULL,
    uploaded_by INT UNSIGNED NULL,
    uploaded_at DATETIME NOT NULL,
    INDEX idx_surat_documents_surat (surat_id, uploaded_at),
    UNIQUE KEY uq_surat_documents_drive_file (drive_file_id),
    CONSTRAINT fk_surat_documents_surat FOREIGN KEY (surat_id) REFERENCES surat_masuk (id) ON DELETE CASCADE,
    CONSTRAINT fk_surat_documents_drive_api FOREIGN KEY (drive_api_id) REFERENCES google_drive_apis (id) ON DELETE RESTRICT,
    CONSTRAINT fk_surat_documents_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Impor file ini melalui HeidiSQL/phpMyAdmin Laragon untuk menyiapkan instalasi baru.
CREATE DATABASE IF NOT EXISTS surat_masuk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE surat_masuk;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'pegawai') NOT NULL DEFAULT 'pegawai',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_role_active (role, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS auth_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    username VARCHAR(50) NOT NULL,
    token_hash CHAR(64) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    last_seen_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    revoked_by INT UNSIGNED NULL,
    revoke_reason VARCHAR(50) NULL,
    UNIQUE KEY uq_auth_sessions_token_hash (token_hash),
    INDEX idx_auth_sessions_user_active (user_id, revoked_at, expires_at),
    INDEX idx_auth_sessions_last_seen (last_seen_at),
    CONSTRAINT fk_auth_sessions_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_auth_sessions_revoked_by FOREIGN KEY (revoked_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    actor_username VARCHAR(50) NULL,
    auth_session_id BIGINT UNSIGNED NULL,
    action VARCHAR(64) NOT NULL,
    module VARCHAR(64) NOT NULL,
    record_id BIGINT UNSIGNED NULL,
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_audit_logs_user_created (user_id, created_at),
    INDEX idx_audit_logs_created (created_at),
    INDEX idx_audit_logs_action (action),
    CONSTRAINT fk_audit_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,
    CONSTRAINT fk_audit_logs_session FOREIGN KEY (auth_session_id) REFERENCES auth_sessions (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS surat_masuk (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_pencarian VARCHAR(20) NULL,
    no INT UNSIGNED NOT NULL,
    nama VARCHAR(150) NULL,
    judul VARCHAR(255) NULL,
    nomor_surat VARCHAR(100) NULL,
    tanggal_surat DATE NULL,
    tanggal_masuk DATE NOT NULL,
    sifat VARCHAR(20) NOT NULL DEFAULT 'Biasa',
    surat_dari VARCHAR(255) NULL,
    pengusul VARCHAR(255) NULL,
    uraian TEXT NULL,
    tanggal_disposisi DATE NULL,
    uraian_pengusul TEXT NOT NULL,
    keterangan TEXT NULL,
    link_drive VARCHAR(500) NULL,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_surat_masuk_kode (kode_pencarian),
    INDEX idx_tanggal_masuk (tanggal_masuk),
    INDEX idx_tanggal_disposisi (tanggal_disposisi),
    INDEX idx_surat_masuk_nomor_surat (nomor_surat),
    INDEX idx_surat_masuk_surat_dari (surat_dari),
    INDEX idx_surat_masuk_nama (nama),
    INDEX idx_surat_masuk_created_by (created_by),
    FULLTEXT KEY ft_surat_masuk_search (nama, judul, nomor_surat, surat_dari, pengusul, uraian, keterangan),
    CONSTRAINT fk_surat_masuk_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS surat_disposisi (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    surat_id INT UNSIGNED NOT NULL,
    tahap TINYINT UNSIGNED NOT NULL,
    asal VARCHAR(150) NULL,
    tujuan VARCHAR(150) NULL,
    tanggal DATE NULL,
    keterangan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_disposisi_surat_tahap (surat_id, tahap),
    INDEX idx_disposisi_tanggal (tanggal),
    CONSTRAINT fk_disposisi_surat FOREIGN KEY (surat_id) REFERENCES surat_masuk (id) ON DELETE CASCADE,
    CONSTRAINT chk_disposisi_tahap CHECK (tahap BETWEEN 1 AND 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS archive_sync_queue (
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

CREATE TABLE IF NOT EXISTS google_drive_apis (
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

CREATE TABLE IF NOT EXISTS surat_documents (
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

CREATE TABLE IF NOT EXISTS system_metric_samples (
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

CREATE TABLE IF NOT EXISTS surat_sequences (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    next_no INT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO surat_sequences (id, next_no)
SELECT 1, COALESCE(MAX(no), 0) + 1 FROM surat_masuk
ON DUPLICATE KEY UPDATE next_no = GREATEST(next_no, VALUES(next_no));

-- Konfigurasi API Google Drive dan metadata file asli.
CREATE TABLE IF NOT EXISTS google_drive_apis (
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

CREATE TABLE IF NOT EXISTS surat_documents (
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

INSERT INTO users (username, password, role, is_active)
VALUES ('admin', '$2y$10$vVm93aRDmksI8MX3pJVsi.ZK8VGvPx7k7Iww.1XpJSco7LZEItpOa', 'admin', 1)
ON DUPLICATE KEY UPDATE role = 'admin', is_active = 1;

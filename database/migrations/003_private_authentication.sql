-- Backup database first. Run once after migrations 001 and 002.
ALTER TABLE users
    ADD COLUMN role ENUM('admin', 'pegawai') NOT NULL DEFAULT 'pegawai' AFTER password,
    ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role,
    ADD INDEX idx_users_role_active (role, is_active);

-- Preserve the existing initial administrator account.
UPDATE users SET role = 'admin' WHERE username = 'admin';

CREATE TABLE auth_sessions (
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

CREATE TABLE audit_logs (
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

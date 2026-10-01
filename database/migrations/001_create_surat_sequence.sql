CREATE TABLE IF NOT EXISTS surat_sequences (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    next_no INT UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO surat_sequences (id, next_no)
SELECT 1, COALESCE(MAX(no), 0) + 1 FROM surat_masuk
ON DUPLICATE KEY UPDATE next_no = GREATEST(next_no, VALUES(next_no));
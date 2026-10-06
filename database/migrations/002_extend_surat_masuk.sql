-- Jalankan satu kali setelah 001_create_surat_sequence.sql pada database lama.
-- Buat backup database sebelum menjalankan migration ini.
-- Kolom uraian_pengusul dan tanggal_disposisi dipertahankan untuk kompatibilitas.
ALTER TABLE surat_masuk
    ADD COLUMN kode_pencarian VARCHAR(20) NULL AFTER id,
    ADD COLUMN nama VARCHAR(150) NULL AFTER no,
    ADD COLUMN judul VARCHAR(255) NULL AFTER nama,
    ADD COLUMN nomor_surat VARCHAR(100) NULL AFTER judul,
    ADD COLUMN tanggal_surat DATE NULL AFTER nomor_surat,
    ADD COLUMN sifat VARCHAR(20) NOT NULL DEFAULT 'Biasa' AFTER tanggal_masuk,
    ADD COLUMN surat_dari VARCHAR(255) NULL AFTER sifat,
    ADD COLUMN pengusul VARCHAR(255) NULL AFTER surat_dari,
    ADD COLUMN uraian TEXT NULL AFTER pengusul,
    ADD COLUMN link_drive VARCHAR(500) NULL,
    ADD COLUMN created_by INT UNSIGNED NULL;

-- Teks lama tidak bisa dipisah otomatis, jadi dipertahankan utuh sebagai uraian.
UPDATE surat_masuk
SET uraian = uraian_pengusul
WHERE uraian IS NULL;

UPDATE surat_masuk
SET kode_pencarian = CONCAT('SM-', YEAR(tanggal_masuk), '-', LPAD(id, 5, '0'))
WHERE kode_pencarian IS NULL;

ALTER TABLE surat_masuk
    ADD UNIQUE KEY uq_surat_masuk_kode (kode_pencarian),
    ADD INDEX idx_surat_masuk_nomor_surat (nomor_surat),
    ADD INDEX idx_surat_masuk_surat_dari (surat_dari),
    ADD INDEX idx_surat_masuk_nama (nama),
    ADD INDEX idx_surat_masuk_created_by (created_by),
    ADD FULLTEXT KEY ft_surat_masuk_search (nama, judul, nomor_surat, surat_dari, pengusul, uraian, keterangan),
    ADD CONSTRAINT fk_surat_masuk_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL;

CREATE TABLE surat_disposisi (
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

INSERT INTO surat_disposisi (surat_id, tahap, tanggal)
SELECT id, 1, tanggal_disposisi
FROM surat_masuk
WHERE tanggal_disposisi IS NOT NULL;

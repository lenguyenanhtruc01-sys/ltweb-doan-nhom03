-- Chạy tệp này trong phpMyAdmin nếu database ltweb_nhom03 đã tồn tại.
-- Không xóa dữ liệu người dùng hiện có.
USE ltweb_nhom03;

CREATE TABLE IF NOT EXISTS phien_ghi_nho (
    selector CHAR(18) PRIMARY KEY,
    nguoi_dung_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    het_han DATETIME NOT NULL,
    ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE CASCADE,
    INDEX idx_phien_ghi_nho_het_han (het_han)
);

CREATE TABLE IF NOT EXISTS yeu_cau_dat_lai_mat_khau (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nguoi_dung_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    het_han DATETIME NOT NULL,
    da_dung_luc DATETIME NULL,
    ngay_tao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nguoi_dung_id) REFERENCES nguoi_dung(id) ON DELETE CASCADE,
    INDEX idx_dat_lai_nguoi_dung (nguoi_dung_id),
    INDEX idx_dat_lai_het_han (het_han)
);

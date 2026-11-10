-- Nâng cấp không xóa dữ liệu. Chạy với CSDL ltweb_nhom03 đang được chọn.
CREATE TABLE IF NOT EXISTS dang_nhap_sai (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    khoa CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    email VARCHAR(255) NOT NULL DEFAULT '',
    ip VARCHAR(45) NOT NULL,
    thoi_gian DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dang_nhap_sai (khoa, thoi_gian),
    INDEX idx_dang_nhap_sai_email (email, thoi_gian)
) ENGINE=InnoDB;

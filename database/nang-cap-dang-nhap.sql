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

SET @edugpa_dang_nhap_alter = CONCAT_WS(', ',
    IF(EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dang_nhap_sai' AND COLUMN_NAME = 'email'
    ), NULL, 'ADD COLUMN email VARCHAR(255) NOT NULL DEFAULT '''' AFTER khoa'),
    IF(EXISTS (
        SELECT 1 FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dang_nhap_sai' AND INDEX_NAME = 'idx_dang_nhap_sai_email'
    ), NULL, 'ADD INDEX idx_dang_nhap_sai_email (email, thoi_gian)')
);
SET @edugpa_dang_nhap_sql = IF(@edugpa_dang_nhap_alter = '', 'DO 0',
    CONCAT('ALTER TABLE dang_nhap_sai ', @edugpa_dang_nhap_alter));
PREPARE edugpa_dang_nhap_stmt FROM @edugpa_dang_nhap_sql;
EXECUTE edugpa_dang_nhap_stmt;
DEALLOCATE PREPARE edugpa_dang_nhap_stmt;

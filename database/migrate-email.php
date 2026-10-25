<?php
declare(strict_types=1);

/**
 * Migration CLI không xóa dữ liệu: thêm email và bảng lượt xem cho bản GitHub mới.
 * Chạy php database/migrate-email.php sau khi sao lưu; có thể chạy lại.
 * Không gọi schema.sql vì schema đó DROP DATABASE.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../inc/db.php';
$pdo = db();
$column = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
);
$column->execute(['nguoi_dung', 'email']);
if ((int) $column->fetchColumn() === 0) {
    $pdo->exec('ALTER TABLE nguoi_dung ADD COLUMN email VARCHAR(254) NULL AFTER ten_dang_nhap');
}
$pdo->exec("UPDATE nguoi_dung SET email = CONCAT(ten_dang_nhap, '@edugpa.local') WHERE email IS NULL OR email = ''");
$index = $pdo->prepare(
    'SELECT COUNT(*) FROM information_schema.STATISTICS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND NON_UNIQUE = 0'
);
$index->execute(['nguoi_dung', 'email']);
if ((int) $index->fetchColumn() === 0) {
    $pdo->exec('ALTER TABLE nguoi_dung ADD UNIQUE KEY uq_nguoi_dung_email (email)');
}
$pdo->exec('ALTER TABLE nguoi_dung MODIFY email VARCHAR(254) NOT NULL');
$pdo->exec('CREATE TABLE IF NOT EXISTS tv_luot_xem (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mssv CHAR(10) NOT NULL,
    ngay DATE NOT NULL,
    so_luot INT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uq_tv_luot_xem_mssv_ngay (mssv, ngay),
    FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
$member = $pdo->prepare('INSERT INTO thanh_vien (mssv, ho_ten)
    SELECT ?, ? WHERE NOT EXISTS (SELECT 1 FROM thanh_vien WHERE mssv = ?)');
$member->execute(['3120224042', 'Lê Dương Hoàng Hải', '3120224042']);
echo "Migration OK; existing accounts, passwords and votes preserved.\n";

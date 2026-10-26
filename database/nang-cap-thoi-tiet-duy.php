<?php
declare(strict_types=1);
/**
 * Gắn các check-in từ bảng thời tiết cũ của Duy với MSSV 3120224034.
 * Bộ cài sao lưu trước khi gọi; giữ ID, trạng thái và thời gian cũ.
 * Chạy lặp không nhân bản dữ liệu; dừng nếu gặp MSSV không có trong thanh_vien.
 */
function nangCapThoiTietDuy(PDO $pdo): void
{
    $st = $pdo->prepare('SELECT IS_NULLABLE FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
    $st->execute(['tv_thoi_tiet_duy', 'mssv']);
    $nullable = $st->fetchColumn();
    if ($nullable === false) {
        $pdo->exec('ALTER TABLE tv_thoi_tiet_duy ADD COLUMN mssv CHAR(10) NULL AFTER id');
        $nullable = 'YES';
    }
    $pdo->exec("INSERT INTO thanh_vien (mssv,ho_ten) VALUES ('3120224034','Dương Bảo Duy')
        ON DUPLICATE KEY UPDATE mssv=VALUES(mssv)");
    // Bảng cũ chỉ phục vụ trang Duy; các dòng đã gắn MSSV được giữ nguyên.
    $pdo->exec("UPDATE tv_thoi_tiet_duy SET mssv='3120224034' WHERE mssv IS NULL OR mssv=''");
    $loi = (int) $pdo->query('SELECT COUNT(*) FROM tv_thoi_tiet_duy t
        LEFT JOIN thanh_vien v ON v.mssv=t.mssv WHERE v.mssv IS NULL')->fetchColumn();
    if ($loi > 0) throw new RuntimeException('Thời tiết có MSSV không tồn tại trong thanh_vien; giữ dữ liệu, cần kiểm tra liên kết.');
    if ($nullable === 'YES') $pdo->exec('ALTER TABLE tv_thoi_tiet_duy MODIFY mssv CHAR(10) NOT NULL');
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?');
    $st->execute(['tv_thoi_tiet_duy', 'idx_thoi_tiet_mssv_trang_thai']);
    if ((int) $st->fetchColumn() === 0) {
        $pdo->exec('CREATE INDEX idx_thoi_tiet_mssv_trang_thai ON tv_thoi_tiet_duy (mssv,trang_thai)');
    }
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?
        AND REFERENCED_TABLE_NAME=? AND REFERENCED_COLUMN_NAME=?');
    $st->execute(['tv_thoi_tiet_duy', 'mssv', 'thanh_vien', 'mssv']);
    if ((int) $st->fetchColumn() === 0) {
        $pdo->exec('ALTER TABLE tv_thoi_tiet_duy ADD CONSTRAINT fk_thoi_tiet_duy_thanh_vien
            FOREIGN KEY (mssv) REFERENCES thanh_vien(mssv) ON DELETE CASCADE');
    }
}

<?php
declare(strict_types=1);
/**
 * Bổ sung cột liên hệ cho phiên bản chuyển từ JSONL sang MySQL.
 * Bộ cài gọi sau khi đã sao lưu; không xóa/ghi đè liên hệ đang có.
 * Không tự đọc JSONL cũ; giữ tệp đó để đối chiếu hoặc nhập riêng khi cần.
 */
function nangCapLienHe(PDO $pdo): void
{
    $st = $pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $st->execute(['lien_he']);
    $cot = $st->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('so_dien_thoai', $cot, true)) {
        $pdo->exec('ALTER TABLE lien_he ADD COLUMN so_dien_thoai VARCHAR(20) NULL AFTER email');
    }
    if (!in_array('uu_tien', $cot, true)) {
        $pdo->exec("ALTER TABLE lien_he ADD COLUMN uu_tien ENUM('thap','trungbinh','cao') NOT NULL DEFAULT 'trungbinh' AFTER chu_de");
    }
}

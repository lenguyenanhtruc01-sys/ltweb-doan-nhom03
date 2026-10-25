<?php
declare(strict_types=1);

/**
 * Bổ sung số chỗ còn lại cho môn học, giữ mọi dữ liệu đang có.
 * Sao lưu rồi chạy: php database/nang-cap-so-cho.php; có thể chạy lại.
 * Môn cũ được khởi tạo 50 chỗ từ lúc nâng cấp; không trừ lại kế hoạch cũ.
 * Dữ liệu hết chỗ/1 chỗ chỉ có trong seed mới, không ghi đè CSDL đang dùng.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chỉ chạy nâng cấp bằng PHP CLI.');
}

function nangCapSoCho(PDO $pdo): bool
{
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $st->execute(['mon_hoc', 'so_cho']);
    if ((int) $st->fetchColumn() > 0) {
        return false;
    }
    $pdo->exec('ALTER TABLE mon_hoc ADD COLUMN so_cho INT UNSIGNED NOT NULL DEFAULT 50 AFTER so_tin_chi');
    return true;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        require_once __DIR__ . '/../inc/db.php';
        echo nangCapSoCho(db())
            ? "Đã bổ sung so_cho = 50; giữ nguyên dữ liệu cũ.\n"
            : "Đã có cột so_cho; không thay đổi số chỗ hiện tại.\n";
    } catch (Throwable $loi) {
        fwrite(STDERR, 'Nâng cấp thất bại: ' . $loi->getMessage() . PHP_EOL);
        exit(1);
    }
}

<?php
declare(strict_types=1);

/**
 * Đổi FK chi tiết kế hoạch → môn học sang RESTRICT, giữ nguyên dữ liệu.
 * Sao lưu rồi chạy CLI: php database/nang-cap-khoa-ngoai-mon-hoc.php.
 * Thử: xóa môn đã có kế hoạch phải lỗi 1451; chạy lại không đổi FK.
 * ALTER TABLE tự commit; dùng một lệnh thay FK để không bỏ trống ràng buộc.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chỉ chạy nâng cấp bằng PHP CLI.');
}

function nangCapKhoaNgoaiMonHoc(PDO $pdo): bool
{
    $st = $pdo->prepare(
        'SELECT k.CONSTRAINT_NAME, r.DELETE_RULE
         FROM information_schema.KEY_COLUMN_USAGE k
         JOIN information_schema.REFERENTIAL_CONSTRAINTS r
           ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
          AND r.TABLE_NAME = k.TABLE_NAME AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
         WHERE k.CONSTRAINT_SCHEMA = DATABASE()
           AND k.TABLE_NAME = ? AND k.COLUMN_NAME = ?
           AND k.REFERENCED_TABLE_NAME = ? AND k.REFERENCED_COLUMN_NAME = ?'
    );
    $st->execute(['chi_tiet_ke_hoach', 'mon_hoc_id', 'mon_hoc', 'id']);
    $cacKhoa = $st->fetchAll(PDO::FETCH_ASSOC);
    if (count($cacKhoa) > 1) throw new RuntimeException('Có nhiều khóa ngoại cùng tham chiếu môn học; cần kiểm tra trước khi nâng cấp.');
    $khoa = $cacKhoa[0] ?? null;
    if ($khoa !== null && in_array($khoa['DELETE_RULE'], ['RESTRICT', 'NO ACTION'], true)) return false;

    // MariaDB có thể báo trùng tên nếu DROP và ADD cùng tên trong một ALTER.
    $cacTen = array_map('strtolower', $pdo->query(
        'SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE()'
    )->fetchAll(PDO::FETCH_COLUMN));
    $tenMoi = 'fk_chi_tiet_ke_hoach_mon_hoc';
    while (in_array(strtolower($tenMoi), $cacTen, true)) {
        $tenMoi = 'fk_ctkh_mon_hoc_restrict_' . bin2hex(random_bytes(6));
    }
    // Tên cũ lấy từ metadata; quote identifier để hỗ trợ cả tên FK tùy chỉnh.
    $boKhoaCu = $khoa === null ? ''
        : 'DROP FOREIGN KEY `' . str_replace('`', '``', $khoa['CONSTRAINT_NAME']) . '`, ';
    $pdo->exec(
        'ALTER TABLE chi_tiet_ke_hoach ' . $boKhoaCu
        . 'ADD CONSTRAINT `' . $tenMoi . '`'
        . ' FOREIGN KEY (mon_hoc_id) REFERENCES mon_hoc(id) ON DELETE RESTRICT ON UPDATE RESTRICT'
    );
    return true;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        require_once __DIR__ . '/../inc/db.php';
        $daDoi = nangCapKhoaNgoaiMonHoc(db());
        echo ($daDoi ? 'Đã đổi khóa ngoại sang RESTRICT.' : 'Khóa ngoại đã chặn xóa; không cần thay đổi.') . PHP_EOL;
    } catch (Throwable $loi) {
        fwrite(STDERR, 'Nâng cấp thất bại: ' . $loi->getMessage() . PHP_EOL);
        exit(1);
    }
}

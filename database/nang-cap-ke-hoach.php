<?php
declare(strict_types=1);

/**
 * Bổ sung cột kế hoạch vào CSDL cũ, giữ ID, tài khoản và dữ liệu hiện có.
 * Chi tiết cũ được điền thông tin môn hiện tại; giữ nguyên snapshot đã lưu.
 * Sao lưu trước, chạy CLI: php database/nang-cap-ke-hoach.php; chạy lại được.
 * ALTER TABLE tự commit trên MySQL/MariaDB, không bọc DDL bằng transaction.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chỉ chạy nâng cấp bằng PHP CLI.');
}

function nangCapKeHoach(PDO $pdo): array
{
    // Tên bảng/cột và định nghĩa dưới đây là hằng trong mã, không nhận từ request.
    $dinhNghia = [
        'ke_hoach_hoc_tap' => [
            'trang_thai' => "VARCHAR(20) NOT NULL DEFAULT 'cho_duyet' CHECK (trang_thai IN ('cho_duyet', 'da_duyet', 'tu_choi'))",
            'ghi_chu' => 'VARCHAR(500) NULL',
        ],
        'chi_tiet_ke_hoach' => [
            'ma_mon' => 'VARCHAR(20) NULL',
            'ten_mon' => 'VARCHAR(200) NULL',
            'so_tin_chi' => 'INT UNSIGNED NULL',
        ],
    ];
    $docCot = $pdo->prepare(
        'SELECT COLUMN_NAME, IS_NULLABLE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $ketQua = [];
    foreach ($dinhNghia as $bang => $cacCot) {
        $docCot->execute([$bang]);
        $hienCo = $docCot->fetchAll(PDO::FETCH_KEY_PAIR);
        if ($hienCo === []) throw new RuntimeException('Thiếu bảng ' . $bang . '.');
        $them = [];
        foreach ($cacCot as $cot => $kieu) {
            if (!isset($hienCo[$cot])) {
                $them[] = 'ADD COLUMN `' . $cot . '` ' . $kieu;
                $ketQua[] = 'Đã thêm ' . $bang . '.' . $cot;
            }
        }
        if ($them !== []) $pdo->exec('ALTER TABLE `' . $bang . '` ' . implode(', ', $them));
    }

    // Dữ liệu cũ không có snapshot lịch sử: chỉ có thể lấy thông tin môn hiện tại.
    $pdo->exec(
        'UPDATE chi_tiet_ke_hoach ct JOIN mon_hoc m ON m.id = ct.mon_hoc_id
         SET ct.ma_mon = COALESCE(ct.ma_mon, m.ma_mon),
             ct.ten_mon = COALESCE(ct.ten_mon, m.ten_mon),
             ct.so_tin_chi = COALESCE(ct.so_tin_chi, m.so_tin_chi)
         WHERE ct.ma_mon IS NULL OR ct.ten_mon IS NULL OR ct.so_tin_chi IS NULL'
    );
    $conThieu = (int) $pdo->query(
        'SELECT COUNT(*) FROM chi_tiet_ke_hoach
         WHERE ma_mon IS NULL OR ten_mon IS NULL OR so_tin_chi IS NULL'
    )->fetchColumn();
    if ($conThieu > 0) throw new RuntimeException('Có chi tiết không tìm thấy môn học; kiểm tra dữ liệu trước khi chạy lại.');

    $docCot->execute(['chi_tiet_ke_hoach']);
    $hienCo = $docCot->fetchAll(PDO::FETCH_KEY_PAIR);
    $sua = [];
    foreach (['ma_mon' => 'VARCHAR(20)', 'ten_mon' => 'VARCHAR(200)', 'so_tin_chi' => 'INT UNSIGNED'] as $cot => $kieu) {
        if (($hienCo[$cot] ?? '') === 'YES') $sua[] = 'MODIFY COLUMN `' . $cot . '` ' . $kieu . ' NOT NULL';
    }
    if ($sua !== []) $pdo->exec('ALTER TABLE chi_tiet_ke_hoach ' . implode(', ', $sua));
    return $ketQua;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        require_once __DIR__ . '/../inc/db.php';
        $ketQua = nangCapKeHoach(db());
        foreach ($ketQua as $dong) echo $dong . PHP_EOL;
        echo 'Nâng cấp kế hoạch hoàn tất; dữ liệu cũ được giữ nguyên.' . PHP_EOL;
    } catch (Throwable $loi) {
        fwrite(STDERR, 'Nâng cấp thất bại: ' . $loi->getMessage() . PHP_EOL);
        exit(1);
    }
}

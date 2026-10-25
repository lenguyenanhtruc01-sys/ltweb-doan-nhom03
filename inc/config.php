<?php
declare(strict_types=1);

/**
 * inc/config.php
 * Cấu hình chung cho website EduGPA - Nhóm 03.
 * Nạp Composer, hàm tiện ích và khởi tạo session.
 * Thiết lập môi trường, ghi log và xử lý lỗi PHP.
 * Kiểm thử bằng cách chạy website trên localhost.
 */

// 1. Nạp Composer và các hàm dùng chung.
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/ham.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/xac-thuc.php';
// 2. Cấu hình môi trường.
const MOI_TRUONG = 'dev';
error_reporting(E_ALL);

ini_set(
    'display_errors',
    MOI_TRUONG === 'dev' ? '1' : '0'
);

foreach (['logs', 'storage'] as $thuMuc) {
    $duongDan = __DIR__ . '/../' . $thuMuc;
    if (!is_dir($duongDan) && !@mkdir($duongDan, 0775, true) && !is_dir($duongDan)) {
        throw new RuntimeException('Không thể tạo thư mục ' . $thuMuc . '.');
    }
}

ini_set('log_errors', '1');

ini_set(
    'error_log',
    __DIR__ . '/../logs/php-error.log'
);

// 3. Thiết lập múi giờ Việt Nam.
date_default_timezone_set('Asia/Ho_Chi_Minh');

// 4. Khởi tạo session trước khi xuất HTML.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 5. Xử lý ngoại lệ chưa được bắt.
khoiPhucDangNhapTuCookie();

set_exception_handler(
    static function (Throwable $loi): void {

        // Ghi chi tiết lỗi vào tệp log.
        error_log((string) $loi);

        // Trả về mã HTTP 500.
        http_response_code(500);

        if (MOI_TRUONG === 'dev') {

            // Hiển thị lỗi khi phát triển.
            echo '<pre>' . e((string) $loi) . '</pre>';

        } else {

            // Trang lỗi thân thiện khi chạy thực tế.
            require __DIR__ . '/../500.php';
        }
    }
);

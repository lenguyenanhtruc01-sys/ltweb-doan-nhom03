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
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'])
    ->required()
    ->notEmpty();

require_once __DIR__ . '/ham.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/xac-thuc.php';
// 2. Cấu hình môi trường.
$giaTriGoiLoi = $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? getenv('APP_DEBUG');
$batGoiLoi = $giaTriGoiLoi === 'true';
define('MOI_TRUONG', $batGoiLoi ? 'dev' : 'prod');
error_reporting(E_ALL);

ini_set(
    'display_errors',
    $batGoiLoi ? '1' : '0'
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

// 4. Cấu hình và khởi tạo session an toàn (Chương 7 - Biện pháp 2).
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tự động hết hạn phiên sau 30 phút không hoạt động
if (isset($_SESSION['hoat_dong_cuoi']) && (time() - $_SESSION['hoat_dong_cuoi'] > 1800)) {
    $_SESSION = []; // Xóa sạch dữ liệu
    session_regenerate_id(true); // Cấp mã phiên mới
}
$_SESSION['hoat_dong_cuoi'] = time(); // Cập nhật thời gian hoạt động

// Kiểm tra trước mọi xử lý POST, kể cả khôi phục đăng nhập có truy vấn CSDL.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !csrfHopLe()) {
    http_response_code(403);
    $loi = 'Phiên biểu mẫu không hợp lệ. Vui lòng tải lại trang rồi gửi lại.';
    if (function_exists('traJson')
        || str_contains(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'json')
        || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
        || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['loi' => $loi], JSON_UNESCAPED_UNICODE));
    }
    header('Content-Type: text/plain; charset=utf-8');
    exit($loi);
}
tokenGioHang();

// 5. Xử lý ngoại lệ chưa được bắt.
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

// Khôi phục phiên có thể truy vấn CSDL; đăng ký bộ xử lý lỗi trước khi gọi.
khoiPhucDangNhapTuCookie();

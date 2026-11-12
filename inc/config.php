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
$dotenv->safeLoad();
$dotenv->required(['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'])
    ->required()
    ->notEmpty();

require_once __DIR__ . '/ham.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/xac-thuc.php';

// 1b. Header bảo mật cho mọi phản hồi (Chương 7 - Biện pháp 3).
// Đặt trước mọi output; CSP chỉ cho phép tài nguyên cùng nguồn và đúng
// hai nguồn ngoài mà giao diện thật sự dùng (xem chú thích từng chỉ thị).
if (!headers_sent()) {
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "script-src 'self'; "
        . "style-src 'self'; "
        . "img-src 'self'; "
        . "font-src 'self'; "
        . "connect-src 'self' https://api.open-meteo.com; "
        . "frame-src https://www.google.com; "
        . "object-src 'none'; "
        . "base-uri 'self'; "
        . "form-action 'self'; "
        . "frame-ancestors 'none'"
    );
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header_remove('X-Powered-By');
    // HSTS chỉ có ý nghĩa khi trang đang chạy bằng HTTPS.
    if (dungHttps()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

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
// 4. Cấu hình và khởi tạo session an toàn (Chương 7 - Biện pháp 2).
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime', '1800');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
   'secure' => dungHttps(),
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$bayGio = time();
$lanCuoi = $_SESSION['hoat_dong_cuoi'] ?? null;
$hetHan = $lanCuoi !== null && ($bayGio - $lanCuoi) >= 1800;

// Phiên hết hạn không còn mã CSRF hợp lệ. Chặn POST trước khi xử lý ghi nhớ.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($hetHan || !csrfHopLe())) {
    ghiLogBaoMat('csrf_sai', ['trang' => $_SERVER['REQUEST_URI'] ?? '-']);
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

$matPhien = $_SESSION === [] && isset($_COOKIE[session_name()]);
if (($hetHan || $matPhien) && isset($_COOKIE[TEN_COOKIE_GHI_NHO])) {
    xoaGhiNhoHienTai();
}

if ($hetHan) {
    $_SESSION = [];
    session_regenerate_id(true);
}

$_SESSION['hoat_dong_cuoi'] = $bayGio;
tokenGioHang();
// Khôi phục phiên có thể truy vấn CSDL; đăng ký bộ xử lý lỗi trước khi gọi.
khoiPhucDangNhapTuCookie();
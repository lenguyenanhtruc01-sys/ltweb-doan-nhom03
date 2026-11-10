<?php
declare(strict_types=1);

/**
 * router.php — chỉ dùng khi chạy website bằng máy chủ tích hợp của PHP:
 *
 *     php -S localhost:8000 router.php
 *
 * php -S bỏ qua .htaccess, nên nếu không có router này thì /.env, /.git/config,
 * /database/schema.sql... vẫn tải được. Router áp dụng lại đúng các quy tắc của
 * .htaccess ở tầng ứng dụng; trên Apache (XAMPP, hosting) không cần tệp này.
 * Trả false để PHP phục vụ tệp/thực thi mã như bình thường.
 */

$duongDan = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$duongDan = is_string($duongDan) ? rawurldecode($duongDan) : '/';

// Tệp và thư mục ẩn: .env, .git/, .htaccess... (chừa .well-known).
$laTepAn = preg_match('~(?:^|/)\.(?!well-known/)~', $duongDan) === 1;

// Thư mục nội bộ của dự án.
$laThuMucNoiBo = preg_match(
    '~(?:^|/)(?:inc|src|vendor|database|logs|storage|kiemtra)(?:/|$)~i',
    $duongDan
) === 1;

// Tệp cấu hình, SQL, log, sao lưu.
$laTepCam = preg_match(
    '~\.(?:sql|log|bak|ini|lock|sh|md)$|(?:^|/)composer\.(?:json|lock)$~i',
    $duongDan
) === 1;

if ($laTepAn || $laThuMucNoiBo || $laTepCam) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    exit('Không tìm thấy.');
}

return false;

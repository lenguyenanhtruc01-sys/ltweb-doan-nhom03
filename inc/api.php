<?php
declare(strict_types=1);

/**
 * Bootstrap JSON cho chức năng 6 của Hợp.
 * Thử: api.http; lỗi PHP/CSDL luôn trả JSON 500, không lộ chi tiết.
 * Phải được nạp trước mọi output từ endpoint.
 */
$apiBufferLevel = ob_get_level();
ob_start();

function traJson(array $data, int $status = 200): never
{
    global $apiBufferLevel;
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    while (ob_get_level() > $apiBufferLevel) {
        ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo $json;
    exit;
}

function loiApi(Throwable $error): never
{
    error_log((string) $error);
    traJson(['loi' => 'Lỗi máy chủ'], 500);
}

function soNguyenApi(mixed $raw, int $min = 1, int $max = PHP_INT_MAX): int|false
{
    if (!is_int($raw) && !is_string($raw)) {
        return false;
    }
    return filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
}

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
try {
    require_once __DIR__ . '/config.php';
} catch (Throwable $error) {
    loiApi($error);
}
// config.php có handler HTML; endpoint phải đăng ký lại handler JSON.
set_exception_handler('loiApi');

function tokenGioHangApi(): string
{
    if (!is_string($_SESSION['csrf_gio_hang'] ?? null) || $_SESSION['csrf_gio_hang'] === '') {
        $_SESSION['csrf_gio_hang'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_gio_hang'];
}

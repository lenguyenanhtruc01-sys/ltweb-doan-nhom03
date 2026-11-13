<?php
declare(strict_types=1);

function bienMoiTruong(string $ten): string|false
{
    return $_ENV[$ten] ?? $_SERVER[$ten] ?? getenv($ten);
}

function napCauHinhCSDL(): void
{
    static $daNap = false;
    if ($daNap) {
        return;
    }
    require_once __DIR__ . '/../vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
    $dotenv->required(['DB_HOST', 'DB_DATABASE', 'DB_USERNAME'])->notEmpty();
    $matKhau = $dotenv->required('DB_PASSWORD');
    if (strtolower(bienMoiTruong('DB_USERNAME')) !== 'root') {
        $matKhau->notEmpty();
    }
    $daNap = true;
}

/**
 * Kết nối PDO dùng chung đến CSDL được cấu hình trong môi trường.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        napCauHinhCSDL();
        $host = bienMoiTruong('DB_HOST');
        $csdl = bienMoiTruong('DB_DATABASE');
        $taiKhoan = bienMoiTruong('DB_USERNAME');
        $matKhau = bienMoiTruong('DB_PASSWORD');

        if (PHP_SAPI !== 'cli' && strtolower($taiKhoan) === 'root') {
            $hostWeb = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
            $loopback = ['127.0.0.1', '::1'];
            if (bienMoiTruong('APP_DEBUG') !== 'true'
                || !in_array($host, ['localhost', ...$loopback], true)
                || !in_array($_SERVER['REMOTE_ADDR'] ?? '', $loopback, true)
                || !in_array($hostWeb, ['localhost', '127.0.0.1', '[::1]'], true)) {
                throw new RuntimeException('Cấu hình root chỉ dành cho XAMPP cục bộ với APP_DEBUG=true.');
            }
        }

        $pdo = new PDO(
            'mysql:host=' . $host
            . ';port=3306'
            . ';dbname=' . $csdl . ';charset=utf8mb4',
            $taiKhoan,
            $matKhau,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 3]
        );
    }
    return $pdo;
}

<?php
declare(strict_types=1);

/**
 * Kết nối PDO dùng chung đến CSDL được cấu hình trong môi trường.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        require_once __DIR__ . '/../vendor/autoload.php';

        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->safeLoad();
        $dotenv->required(['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'])
            ->required()
            ->notEmpty();

        $layBienMoiTruong = static function (string $ten): string|false {
            if (array_key_exists($ten, $_ENV)) {
                return (string) $_ENV[$ten];
            }
            if (array_key_exists($ten, $_SERVER)) {
                return (string) $_SERVER[$ten];
            }
            return getenv($ten);
        };

        $giaTriBien = static function (string $ten) use ($layBienMoiTruong): string {
            $giaTri = $layBienMoiTruong($ten);
            if ($giaTri === false || $giaTri === '') {
                throw new RuntimeException('Thiếu hoặc rỗng biến cấu hình môi trường ' . $ten . '.');
            }
            return $giaTri;
        };

        $host = $giaTriBien('DB_HOST');
        $csdl = $giaTriBien('DB_DATABASE');
        $taiKhoan = $giaTriBien('DB_USERNAME');
        $matKhau = $giaTriBien('DB_PASSWORD');

        if (PHP_SAPI !== 'cli' && strtolower($taiKhoan) === 'root') {
            throw new RuntimeException(
                'Website không được kết nối MySQL bằng root; hãy cấu hình DB_USERNAME=nhom03_app.'
            );
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

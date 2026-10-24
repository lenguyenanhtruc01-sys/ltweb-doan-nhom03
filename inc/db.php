<?php
declare(strict_types=1);

/**
 * Kết nối PDO dùng chung đến ltweb_nhom03.
 * Thử: import database/schema.sql và database/seed.sql trước khi mở trang Hợp.
 * Có thể cấu hình DB_HOST, DB_PORT, DB_USER, DB_PASS qua môi trường Apache/PHP.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . (getenv('DB_HOST') ?: '127.0.0.1')
            . ';port=' . (getenv('DB_PORT') ?: '3306')
            . ';dbname=ltweb_nhom03;charset=utf8mb4',
            getenv('DB_USER') === false ? 'root' : getenv('DB_USER'),
            getenv('DB_PASS') === false ? '' : getenv('DB_PASS'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 3]
        );
    }
    return $pdo;
}

<?php
// inc/db.php — Hàm kết nối PDO dùng chung (Slide 17)
// Gọi db() ở đâu cũng được: lần đầu mở kết nối, sau đó dùng lại.

function db(): PDO
{
    static $pdo = null; // static: giữ giá trị suốt request

    if ($pdo === null) {
        $tuyChon = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // lỗi SQL ném PDOException
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // mỗi dòng là mảng kết hợp
            PDO::ATTR_EMULATE_PREPARES => false, // prepared statement thật của MySQL
        ];

        $pdo = new PDO(
            'mysql:host=localhost;dbname=ltweb_nhom03;charset=utf8mb4',
            'root', // XAMPP: tài khoản root
            '',     // XAMPP: mật khẩu rỗng
            $tuyChon
        );
    }

    return $pdo;
}
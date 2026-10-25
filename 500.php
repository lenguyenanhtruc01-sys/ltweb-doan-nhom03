<?php
declare(strict_types=1);

/**
 * Trang lỗi máy chủ độc lập, trả HTTP 500 và không truy vấn CSDL.
 * Chỉ nạp e(); không nạp config, header hoặc footer có dữ liệu động.
 * Thử: môi trường prod, đăng nhập rồi ngắt CSDL trong bản thử riêng.
 */
require_once __DIR__ . '/inc/ham.php';
http_response_code(500);

// Hỗ trợ cả php -S ở thư mục dự án và dự án nằm dưới htdocs của XAMPP.
$thuMucDuAn = str_replace('\\', '/', __DIR__);
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? __DIR__) ?: __DIR__;
$documentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
$gocWeb = strncasecmp($thuMucDuAn, $documentRoot . '/', strlen($documentRoot) + 1) === 0
    ? substr($thuMucDuAn, strlen($documentRoot))
    : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống đang gặp sự cố | EduGPA</title>
    <link rel="stylesheet" href="<?= e($gocWeb . '/css/style.css') ?>">
</head>
<body class="trang">
    <header class="dau-trang">
        <div class="bao">
            <a class="thuong-hieu" href="<?= e($gocWeb . '/index.php') ?>">EduGPA</a>
        </div>
    </header>
    <main class="noi-dung-chinh">
        <section class="khu-vuc">
            <div class="bao">
                <div class="tieu-de-khu-vuc">
                    <p class="tieu-de-khu-vuc__nhan">Lỗi 500</p>
                    <h1 class="tieu-de-khu-vuc__tieu-de">Hệ thống đang gặp sự cố</h1>
                    <p class="tieu-de-khu-vuc__mo-ta">Xin lỗi! EduGPA đang gặp lỗi trong quá trình xử lý yêu cầu. Vui lòng thử lại sau hoặc quay về trang chủ.</p>
                    <p><a class="nut nut--chinh" href="<?= e($gocWeb . '/index.php') ?>">Quay về trang chủ</a></p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>

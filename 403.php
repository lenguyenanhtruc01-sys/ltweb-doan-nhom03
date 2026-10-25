<?php
declare(strict_types=1);

/**
 * Trang từ chối truy cập, luôn trả HTTP 403 và không truy vấn CSDL.
 * Dùng trực tiếp hoặc nạp từ hàm kiểm tra quyền của trang khác.
 * Thử: tài khoản khách mở quan-tri.php; kiểm tra HTTP 403 và liên kết trang chủ.
 */
require_once __DIR__ . '/inc/ham.php';
http_response_code(403);

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
    <title>Không có quyền truy cập | EduGPA</title>
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
                    <p class="tieu-de-khu-vuc__nhan">Lỗi 403</p>
                    <h1 class="tieu-de-khu-vuc__tieu-de">Không có quyền truy cập</h1>
                    <p class="tieu-de-khu-vuc__mo-ta">Bạn không có quyền truy cập nội dung này.</p>
                    <p><a class="nut nut--chinh" href="<?= e($gocWeb . '/index.php') ?>">Quay về trang chủ</a></p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>

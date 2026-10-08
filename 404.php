<?php
/**
 * 404.php - Trang thông báo không tìm thấy.
 * Trả về mã HTTP 404.
 * Sử dụng header và footer chung của EduGPA.
 * Kiểm thử bằng DevTools - Network.
 */

require_once __DIR__ . '/inc/config.php';

// Thiết lập mã HTTP 404.
http_response_code(404);

// Các biến dùng cho giao diện chung.
$tieuDe = 'Không tìm thấy trang';
$trang = '';
$goc ??= '';

// Nạp header chung.
require __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh">

    <section class="khu-vuc">
        <div class="bao">

            <div class="tieu-de-khu-vuc">

                <p class="tieu-de-khu-vuc__nhan">
                    Lỗi 404
                </p>

                <h1 class="tieu-de-khu-vuc__tieu-de">
                    Không tìm thấy trang
                </h1>

                <p class="tieu-de-khu-vuc__mo-ta">
                    Xin lỗi! Trang hoặc nội dung bạn
                    đang tìm kiếm không tồn tại.
                    Vui lòng kiểm tra lại đường dẫn
                    hoặc quay về trang chủ EduGPA.
                </p>

                <p>
                    <a
                        class="nut nut--chinh"
                        href="<?= e($goc) ?>index.php">
                        Quay về trang chủ
                    </a>
                </p>

            </div>

        </div>
    </section>

</main>

<?php
require __DIR__ . '/inc/footer.php';
?>

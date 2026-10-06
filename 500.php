<?php
/**
 * 500.php - Trang thông báo lỗi máy chủ.
 * Trả về mã HTTP 500.
 * Sử dụng header và footer chung của EduGPA.
 * Không hiển thị chi tiết lỗi kỹ thuật.
 * Kiểm thử bằng localhost và DevTools - Network.
 */

require_once __DIR__ . '/inc/config.php';

// Thiết lập mã HTTP 500.
http_response_code(500);

// Các biến dùng cho giao diện chung.
$tieuDe = 'Lỗi máy chủ';
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
                    Lỗi 500
                </p>

                <h1 class="tieu-de-khu-vuc__tieu-de">
                    Hệ thống đang gặp sự cố
                </h1>

                <p class="tieu-de-khu-vuc__mo-ta">
                    Xin lỗi! EduGPA đang gặp lỗi
                    trong quá trình xử lý yêu cầu.

                    Vui lòng thử lại sau hoặc
                    quay về trang chủ.
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

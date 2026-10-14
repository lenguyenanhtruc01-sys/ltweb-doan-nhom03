<?php
/**
 * Tệp: index.php (Anh Trúc — Phần A, khối Đã xem gần đây)
 * Trang chủ EduGPA — dùng chung header/footer.
 * Đọc cookie da_xem do chi-tiet.php tạo và hiển thị tối đa 4 môn gần đây.
 * Cookie được kiểm tra lại bằng filter_var; dữ liệu lỗi hoặc môn không tồn tại
 * sẽ được bỏ qua. Kiểm thử với cookie hợp lệ và cookie chứa dữ liệu rác.
 */

declare(strict_types=1);

require __DIR__ . '/inc/config.php';

use App\Data\KhoMonHoc;

$kho = new KhoMonHoc();

/*
 * ============================================================
 * KHỐI "ĐÃ XEM GẦN ĐÂY"
 * ============================================================
 *
 * Cookie da_xem có dạng:
 * 5,3,8,1
 *
 * Có thể bị người dùng sửa trực tiếp nên không được tin tưởng
 * hoàn toàn. Mỗi giá trị phải được kiểm tra lại trước khi
 * dùng để truy vấn dữ liệu.
 */

$daXem = [];

$rawCookie = (string) ($_COOKIE['da_xem'] ?? '');

if ($rawCookie !== '') {

    $danhSachId = explode(',', $rawCookie);

    foreach ($danhSachId as $giaTri) {

        $giaTri = trim($giaTri);

        /*
         * Chỉ chấp nhận số nguyên.
         */
        $id = filter_var(
            $giaTri,
            FILTER_VALIDATE_INT
        );

        /*
         * Bỏ qua:
         * - giá trị không phải số nguyên
         * - số âm
         * - số 0
         */
        if ($id === false || $id <= 0) {
            continue;
        }

        /*
         * Kiểm tra môn có tồn tại hay không.
         */
        $mon = $kho->timTheoId($id);

        if ($mon === null) {
            continue;
        }

        /*
         * Không hiển thị một môn nhiều lần nếu cookie
         * chứa ID bị trùng.
         */
        $biTrung = false;

        foreach ($daXem as $monDaXem) {

            if ((int) $monDaXem->id === (int) $id) {
                $biTrung = true;
                break;
            }
        }

        if ($biTrung) {
            continue;
        }

        $daXem[] = $mon;

        /*
         * Chỉ hiển thị tối đa 4 môn gần đây.
         */
        if (count($daXem) >= 4) {
            break;
        }
    }
}


/*
 * ============================================================
 * CẤU HÌNH TRANG
 * ============================================================
 */

$tieuDe = 'Trang chủ';
$trang = 'index';

/*
 * footer.php sẽ tự động nạp:
 * - js/main.js
 * - js/trang-chu.js
 */
$scriptsTrang = [
    'js/trang-chu.js'
];

require __DIR__ . '/inc/header.php';
?>

<!-- ================= NỘI DUNG CHÍNH ================= -->
<main class="noi-dung-chinh">

    <!-- ================= HERO ================= -->
    <section class="hero">

        <div class="bao hero__khung">

            <div class="hero__noi-dung">

                <p class="hero__nhan">
                    Quản lý học tập thuận tiện
                </p>

                <h1 class="hero__tieu-de">
                    Tra cứu và theo dõi môn học
                    dễ dàng hơn cùng EduGPA
                </h1>

                <p class="hero__mo-ta">
                    EduGPA hỗ trợ sinh viên tra cứu
                    danh sách môn học, tìm kiếm và lọc dữ liệu,
                    xem thông tin chi tiết và lưu các môn yêu thích.
                </p>

                <div class="hero__hanh-dong">

                    <a
                        class="nut nut--chinh"
                        href="danh-sach.php">
                        Khám phá môn học
                    </a>

                    <a
                        class="nut nut--chinh"
                        href="gioi-thieu.php">
                        Tìm hiểu EduGPA
                    </a>

                </div>

            </div>


            <figure class="hero__hinh">

                <img
                    src="images/mota.png"
                    alt="Minh họa hệ thống EduGPA hỗ trợ sinh viên tra cứu và theo dõi môn học"
                    width="600"
                    height="400">

                <figcaption class="hero__chu-thich">
                    EduGPA hỗ trợ sinh viên tra cứu
                    và quản lý thông tin môn học.
                </figcaption>

            </figure>

        </div>

    </section>


    <!-- ================= ĐÃ XEM GẦN ĐÂY ================= -->

    <?php if (!empty($daXem)): ?>

        <section
            class="khu-vuc khu-vuc--da-xem"
            aria-labelledby="tieu-de-da-xem">

            <div class="bao">

                <div class="tieu-de-khu-vuc">

                    <p class="tieu-de-khu-vuc__nhan">
                        Gần đây
                    </p>

                    <h2
                        id="tieu-de-da-xem"
                        class="tieu-de-khu-vuc__tieu-de">
                        Đã xem gần đây
                    </h2>

                    <p class="tieu-de-khu-vuc__mo-ta">
                        Bốn môn học bạn vừa mở gần nhất.
                    </p>

                </div>


                <div class="luoi-the">

                    <?php foreach ($daXem as $mon): ?>

                        <article class="the">

                            <div
                                class="the__bieu-tuong"
                                aria-hidden="true">
                                ĐX
                            </div>

                            <h3 class="the__tieu-de">

                                <a
                                    class="the__lien-ket"
                                    href="chi-tiet.php?id=<?= (int) $mon->id ?>">
                                    <?= e((string) $mon->tenMon) ?>
                                </a>

                            </h3>

                            <p class="the__noi-dung">

                                Mã môn:
                                <strong>
                                    <?= e((string) $mon->maMon) ?>
                                </strong>

                                — <?= (int) $mon->soTinChi ?> tín chỉ

                            </p>

                            <a
                                class="the__lien-ket"
                                href="chi-tiet.php?id=<?= (int) $mon->id ?>">
                                Xem lại
                            </a>

                        </article>

                    <?php endforeach; ?>

                </div>

            </div>

        </section>

    <?php endif; ?>


    <!-- ================= CÁC CHỨC NĂNG ================= -->

    <section class="khu-vuc khu-vuc--chuc-nang">

        <div class="bao">

            <div class="tieu-de-khu-vuc">

                <p class="tieu-de-khu-vuc__nhan">
                    Chức năng
                </p>

                <h2 class="tieu-de-khu-vuc__tieu-de">
                    Những chức năng chính của EduGPA
                </h2>

                <p class="tieu-de-khu-vuc__mo-ta">
                    Các chức năng được xây dựng bằng JavaScript
                    giúp người dùng thao tác với dữ liệu môn học
                    thuận tiện hơn.
                </p>

            </div>


            <div class="luoi-the">

                <!-- Chức năng 1 -->
                <article class="the">

                    <div
                        class="the__bieu-tuong"
                        aria-hidden="true">
                        TK
                    </div>

                    <h3 class="the__tieu-de">
                        Tìm kiếm và lọc môn học
                    </h3>

                    <p class="the__noi-dung">
                        Tìm kiếm môn học theo tên hoặc mã môn,
                        đồng thời lọc và sắp xếp dữ liệu
                        theo nhu cầu của người dùng.
                    </p>

                    <a
                        class="the__lien-ket"
                        href="danh-sach.php">
                        Tìm kiếm môn học
                    </a>

                </article>


                <!-- Chức năng 2 -->
                <article class="the">

                    <div
                        class="the__bieu-tuong"
                        aria-hidden="true">
                        ♥
                    </div>

                    <h3 class="the__tieu-de">
                        Lưu môn học yêu thích
                    </h3>

                    <p class="the__noi-dung">
                        Thêm hoặc bỏ môn học khỏi danh sách
                        yêu thích và lưu trạng thái trên trình duyệt
                        bằng localStorage.
                    </p>

                    <a
                        class="the__lien-ket"
                        href="danh-sach.php">
                        Xem danh sách
                    </a>

                </article>


                <!-- Chức năng 3 -->
                <article class="the">

                    <div
                        class="the__bieu-tuong"
                        aria-hidden="true">
                        CT
                    </div>

                    <h3 class="the__tieu-de">
                        Xem chi tiết môn học
                    </h3>

                    <p class="the__noi-dung">
                        Chọn một môn học từ danh sách để xem
                        mã môn, số tín chỉ, trạng thái,
                        học kỳ và các thông tin liên quan.
                    </p>

                    <a
                        class="the__lien-ket"
                        href="danh-sach.php">
                        Chọn môn để xem
                    </a>

                </article>

            </div>

        </div>

    </section>


    <!-- ================= QUY TRÌNH ================= -->

    <section class="khu-vuc khu-vuc--nen-phu">

        <div class="bao">

            <div class="tieu-de-khu-vuc">

                <p class="tieu-de-khu-vuc__nhan">
                    Quy trình
                </p>

                <h2 class="tieu-de-khu-vuc__tieu-de">
                    Sử dụng EduGPA như thế nào?
                </h2>

            </div>


            <ol class="cac-buoc">

                <li class="cac-buoc__muc">

                    <span class="cac-buoc__so">
                        01
                    </span>

                    <div>

                        <h3>
                            Mở danh sách môn học
                        </h3>

                        <p>
                            Truy cập trang danh sách để xem
                            các học phần được tải từ dữ liệu JSON.
                        </p>

                    </div>

                </li>


                <li class="cac-buoc__muc">

                    <span class="cac-buoc__so">
                        02
                    </span>

                    <div>

                        <h3>
                            Tìm kiếm và lọc
                        </h3>

                        <p>
                            Nhập từ khóa hoặc sử dụng các bộ lọc
                            để nhanh chóng tìm môn học cần xem.
                        </p>

                    </div>

                </li>


                <li class="cac-buoc__muc">

                    <span class="cac-buoc__so">
                        03
                    </span>

                    <div>

                        <h3>
                            Xem thông tin chi tiết
                        </h3>

                        <p>
                            Chọn một môn học để mở trang chi tiết
                            và xem các thông tin tương ứng.
                        </p>

                    </div>

                </li>


                <li class="cac-buoc__muc">

                    <span class="cac-buoc__so">
                        04
                    </span>

                    <div>

                        <h3>
                            Lưu môn yêu thích
                        </h3>

                        <p>
                            Đánh dấu các môn quan tâm.
                            Danh sách yêu thích được lưu
                            bằng localStorage trên trình duyệt.
                        </p>

                    </div>

                </li>

            </ol>

        </div>

    </section>


    <!-- ================= ĐỐI TƯỢNG SỬ DỤNG ================= -->

    <section class="khu-vuc">

        <div class="bao">

            <div class="tieu-de-khu-vuc">

                <p class="tieu-de-khu-vuc__nhan">
                    Người dùng
                </p>

                <h2 class="tieu-de-khu-vuc__tieu-de">
                    Đối tượng sử dụng
                </h2>

            </div>


            <div class="luoi-doi-tuong">

                <article class="doi-tuong">

                    <h3 class="doi-tuong__tieu-de">
                        Sinh viên
                    </h3>

                    <p class="doi-tuong__noi-dung">
                        Tra cứu môn học, theo dõi thông tin
                        và lưu các học phần quan tâm.
                    </p>

                </article>


                <article class="doi-tuong">

                    <h3 class="doi-tuong__tieu-de">
                        Người xem
                    </h3>

                    <p class="doi-tuong__noi-dung">
                        Xem thông tin giới thiệu,
                        danh sách môn học và các nội dung
                        công khai của website.
                    </p>

                </article>


                <article class="doi-tuong">

                    <h3 class="doi-tuong__tieu-de">
                        Nhóm phát triển
                    </h3>

                    <p class="doi-tuong__noi-dung">
                        Kiểm thử giao diện, dữ liệu,
                        chức năng JavaScript và khả năng
                        truy cập của hệ thống.
                    </p>

                </article>

            </div>

        </div>

    </section>


    <!-- ================= THỜI TIẾT ĐÀ NẴNG ================= -->

    <section
        class="khu-vuc khu-vuc--nen-phu"
        aria-labelledby="tieu-de-thoi-tiet">

        <div class="bao">

            <div class="tieu-de-khu-vuc">

                <p class="tieu-de-khu-vuc__nhan">
                    REST API
                </p>

                <h2
                    id="tieu-de-thoi-tiet"
                    class="tieu-de-khu-vuc__tieu-de">
                    Thời tiết Đà Nẵng hiện tại
                </h2>

                <p class="tieu-de-khu-vuc__mo-ta">
                    Dữ liệu thời tiết được tải trực tuyến
                    từ Open-Meteo API và cập nhật bằng JavaScript
                    mà không cần tải lại toàn bộ trang.
                </p>

            </div>


            <!--
                JavaScript trong js/trang-chu.js
                sẽ thay nội dung bên trong #thoi-tiet
                sau khi nhận dữ liệu từ API.
            -->

            <div
                id="thoi-tiet"
                class="thoi-tiet"
                role="status"
                aria-live="polite"
                aria-busy="true">

                <p>
                    Đang tải dữ liệu thời tiết...
                </p>

            </div>


            <noscript>

                <p class="trang-thai">
                    Cần bật JavaScript để xem
                    dữ liệu thời tiết trực tuyến.
                </p>

            </noscript>

        </div>

    </section>


    <!-- ================= KHÁM PHÁ EDUGPA ================= -->

    <aside class="kham-pha">

        <div class="bao kham-pha__khung">

            <div class="kham-pha__noi-dung">

                <p class="kham-pha__nhan">
                    Bắt đầu với EduGPA
                </p>

                <h2 class="kham-pha__tieu-de">
                    Tra cứu thông tin môn học dễ dàng hơn
                </h2>

                <p class="kham-pha__mo-ta">
                    Khám phá danh sách môn học,
                    sử dụng chức năng tìm kiếm, lọc
                    và lưu các môn học yêu thích.
                </p>

            </div>


            <div class="kham-pha__hanh-dong">

                <a
                    class="nut nut--chinh"
                    href="danh-sach.php">
                    Tra cứu danh sách
                </a>

                <a
                    class="nut nut--chinh"
                    href="lien-he.php">
                    Liên hệ
                </a>

            </div>

        </div>

    </aside>

</main>


<?php
require __DIR__ . '/inc/footer.php';
?>

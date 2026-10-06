<?php
declare(strict_types=1);

/**
 * gioi-thieu.php
 * Trang giới thiệu EduGPA - Nhóm 03.
 * Sử dụng Header và Footer dùng chung.
 */

// 1. Nạp cấu hình chung đầu tiên.
require __DIR__ . '/inc/config.php';

// 2. Thiết lập thông tin trang.
$tieuDe = 'Giới thiệu';
$trang = 'gioi-thieu';
$goc = '';

// 3. Giữ CSS riêng của trang giới thiệu.
$lopBody = 'trang-gioi-thieu';

// 4. Nạp Header dùng chung.
require __DIR__ . '/inc/header.php';
?>

    <!-- ================= NỘI DUNG CHÍNH ================= -->
    <main class="noi-dung-chinh">

        <h1>Giới thiệu EduGPA và nhóm thực hiện</h1>

        <!-- ================= TỔNG QUAN ================= -->
        <section class="gioi-thieu__tong-quan">
            <h2>Giới thiệu về EduGPA</h2>

            <p class="gioi-thieu__mo-ta">
                EduGPA là hệ thống hỗ trợ sinh viên theo dõi
                và lập kế hoạch học tập trong quá trình học
                đại học.
            </p>

            <p class="gioi-thieu__mo-ta">
                Website được xây dựng nhằm hỗ trợ sinh viên
                quản lý kết quả học tập, tính GPA, theo dõi
                tín chỉ và đánh giá tiến độ hướng tới mục tiêu
                tốt nghiệp.
            </p>

            <p class="gioi-thieu__mo-ta">
                Bên cạnh việc quản lý kết quả học tập, EduGPA
                còn hỗ trợ mô phỏng GPA với các mức điểm dự kiến
                và cung cấp những gợi ý giúp sinh viên định hướng
                việc học tập.
            </p>
        </section>

        <!-- ================= MỤC TIÊU ================= -->
        <section class="gioi-thieu__muc-tieu">
            <h2>Mục tiêu của website</h2>

            <ul class="gioi-thieu__van-ban">
                <li>
                    Hỗ trợ sinh viên theo dõi kết quả học tập.
                </li>

                <li>
                    Hỗ trợ tính GPA học kỳ và GPA tích lũy.
                </li>

                <li>
                    Theo dõi số tín chỉ đã hoàn thành và số
                    tín chỉ còn thiếu.
                </li>

                <li>
                    Mô phỏng GPA dự kiến với các mức điểm
                    khác nhau.
                </li>

                <li>
                    Hỗ trợ sinh viên lập kế hoạch học tập.
                </li>

                <li>
                    Đánh giá khả năng đạt mục tiêu tốt nghiệp.
                </li>
            </ul>
        </section>

        <!-- ================= ĐỐI TƯỢNG ================= -->
        <section class="gioi-thieu__doi-tuong">
            <h2>Đối tượng sử dụng</h2>

            <article class="gioi-thieu__doi-tuong-the">
                <h3>Khách truy cập</h3>

                <p>
                    Có thể xem thông tin giới thiệu, hướng dẫn
                    tính GPA, danh sách môn học và các tài liệu
                    học tập được cung cấp trên website.
                </p>
            </article>

            <article class="gioi-thieu__doi-tuong-the">
                <h3>Sinh viên</h3>

                <p>
                    Có thể quản lý thông tin cá nhân, môn học,
                    điểm số, GPA, tín chỉ và mục tiêu tốt nghiệp.
                </p>
            </article>

            <article class="gioi-thieu__doi-tuong-the">
                <h3>Quản trị viên</h3>

                <p>
                    Có thể quản lý tài khoản, môn học, tài liệu,
                    nội dung tư vấn và dữ liệu của hệ thống.
                </p>
            </article>
        </section>

        <!-- ================= THÀNH VIÊN ================= -->
        <section class="gioi-thieu__thanh-vien">
            <h2>Thành viên nhóm</h2>

            <ul class="danh-sach-thanh-vien">
                <li class="danh-sach-thanh-vien__muc">
                    <a
                        class="danh-sach-thanh-vien__lien-ket"
                        href="thanhvien/3120224159_anhtruc/gioithieu.html"
                    >
                        Lê Nguyễn Anh Trúc
                    </a>
                </li>

                <li class="danh-sach-thanh-vien__muc">
                    <a
                        class="danh-sach-thanh-vien__lien-ket"
                        href="thanhvien/3120224174_uyen/gioithieu.html"
                    >
                        Huỳnh Phương Uyên
                    </a>
                </li>

                <li class="danh-sach-thanh-vien__muc">
                    <a
                        class="danh-sach-thanh-vien__lien-ket"
                        href="thanhvien/3120224034_Duy/gioithieu.html"
                    >
                        Dương Bảo Duy
                    </a>
                </li>

                <li class="danh-sach-thanh-vien__muc">
                    <a
                        class="danh-sach-thanh-vien__lien-ket"
                        href="thanhvien/3120224042_hai/gioithieu.html"
                    >
                        Lê Dương Hoàng Hải
                    </a>
                </li>

                <li class="danh-sach-thanh-vien__muc">
                    <a
                        class="danh-sach-thanh-vien__lien-ket"
                        href="thanhvien/3120224064_hop/gioithieu.html"
                    >
                        Phan Văn Hợp
                    </a>
                </li>
            </ul>
        </section>

        <!-- ================= KHÁM PHÁ ================= -->
        <aside class="kham-pha">
            <h2>Khám phá EduGPA</h2>

            <ul>
                <li>
                    <a href="index.php">
                        Quay lại trang chủ
                    </a>
                </li>

                <li>
                    <a href="danh-sach.php">
                        Xem danh sách môn học
                    </a>
                </li>

                <li>
                    <a href="chi-tiet.php?id=1">
                        Xem chi tiết môn học
                    </a>
                </li>

                <li>
                    <a href="lien-he.php">
                        Liên hệ
                    </a>
                </li>
            </ul>
        </aside>

    </main>

    
<?php
/**
 * Nạp Footer dùng chung của EduGPA.
 * Footer đã bao gồm JavaScript chung,
 * thẻ đóng body và html.
 */
require __DIR__ . '/inc/footer.php';

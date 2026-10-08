<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="Chi tiết môn học, kết quả học tập, GPA và tín chỉ trên hệ thống EduGPA."
    >

    <title>Chi tiết môn học | EduGPA</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body class="trang trang-chi-tiet">

    <!-- ================= HEADER ================= -->
    <header class="dau-trang">
        <div class="bao dau-trang__noi-dung">

            <div class="dau-trang__cum-thuong-hieu">
                <a class="thuong-hieu" href="index.php">
                    EduGPA
                </a>

                <a
                    class="dau-trang__yeu-thich"
                    href="danh-sach.php"
                >
                    <span aria-hidden="true">♥</span>
                    <span>Yêu thích:</span>

                    <span
                        data-so-luong-yeu-thich
                        aria-live="polite"
                        aria-atomic="true"
                    >
                        0
                    </span>
                </a>
            </div>

            <p class="dau-trang__mo-ta">
                Hệ thống hỗ trợ theo dõi và lập kế hoạch học tập
                cho sinh viên
            </p>

        </div>
    </header>

    <!-- ================= MENU CHÍNH ================= -->
    <nav
        class="dieu-huong"
        aria-label="Điều hướng chính"
    >
        <button
            class="nut-menu"
            type="button"
            aria-controls="menu-chinh"
            aria-expanded="false"
            aria-label="Mở menu chính"
        >
            <span aria-hidden="true">☰</span>
            <span>Menu</span>
        </button>

        <ul
            id="menu-chinh"
            class="dieu-huong__danh-sach menu-chinh"
        >
            <li class="dieu-huong__muc">
                <a
                    class="dieu-huong__lien-ket"
                    href="index.php"
                >
                    Trang chủ
                </a>
            </li>

            <li class="dieu-huong__muc">
                <a
                    class="dieu-huong__lien-ket"
                    href="danh-sach.php"
                >
                    Danh sách
                </a>
            </li>

            <li class="dieu-huong__muc">
                <a
                    class="dieu-huong__lien-ket dieu-huong__lien-ket--hien-tai"
                    href="chi-tiet.php"
                    aria-current="page"
                >
                    Chi tiết
                </a>
            </li>

            <li class="dieu-huong__muc">
                <a
                    class="dieu-huong__lien-ket"
                    href="gioi-thieu.php"
                >
                    Giới thiệu
                </a>
            </li>

            <li class="dieu-huong__muc">
                <a
                    class="dieu-huong__lien-ket"
                    href="lien-he.php"
                >
                    Liên hệ
                </a>
            </li>
        </ul>
    </nav>

    <!-- ================= NỘI DUNG CHÍNH ================= -->
    <main class="noi-dung-chinh">

        <h1>Chi tiết kết quả học tập</h1>

        <!-- Nội dung môn học được JavaScript tạo theo ?id= -->
        <article
            id="chi-tiet"
            class="chi-tiet__noi-dung"
            aria-live="polite"
        >
            <h2>Tổng quan kết quả học tập</h2>

            <p>
                Trang này hiển thị thông tin chi tiết của từng
                môn học trên hệ thống EduGPA.
            </p>

            <p>
                Vui lòng chọn một môn học từ
                <a href="danh-sach.php">
                    danh sách môn học
                </a>
                để xem thông tin chi tiết.
            </p>
        </article>

        <!-- ================= BẢNG ĐIỂM HỌC KỲ 1 ================= -->
        <article>
            <h2>
                Bảng điểm học kỳ 1 - Năm học 2025 - 2026
            </h2>

            <div class="bang-cuon">
                <table>
                    <caption>
                        Bảng điểm chi tiết học kỳ 1
                        năm học 2025 - 2026
                    </caption>

                    <thead>
                        <tr>
                            <th scope="col">STT</th>
                            <th scope="col">Mã môn</th>
                            <th scope="col">Tên môn học</th>
                            <th scope="col">Số tín chỉ</th>
                            <th scope="col">Điểm chữ</th>
                            <th scope="col">Điểm hệ 4</th>
                            <th scope="col">Kết quả</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td data-label="STT">1</td>
                            <td data-label="Mã môn">31231755</td>
                            <td data-label="Tên môn">
                                Thiết kế và lập trình web
                            </td>
                            <td data-label="Số tín chỉ">3</td>
                            <td data-label="Điểm chữ">A</td>
                            <td data-label="Điểm hệ 4">4.0</td>
                            <td data-label="Kết quả">Đạt</td>
                        </tr>

                        <tr>
                            <td data-label="STT">2</td>
                            <td data-label="Mã môn">31221010</td>
                            <td data-label="Tên môn">
                                An toàn thông tin
                            </td>
                            <td data-label="Số tín chỉ">3</td>
                            <td data-label="Điểm chữ">B+</td>
                            <td data-label="Điểm hệ 4">3.5</td>
                            <td data-label="Kết quả">Đạt</td>
                        </tr>

                        <tr>
                            <td data-label="STT">3</td>
                            <td data-label="Mã môn">31231398</td>
                            <td data-label="Tên môn">
                                Lập trình mạng
                            </td>
                            <td data-label="Số tín chỉ">3</td>
                            <td data-label="Điểm chữ">B</td>
                            <td data-label="Điểm hệ 4">3.0</td>
                            <td data-label="Kết quả">Đạt</td>
                        </tr>

                        <tr>
                            <td data-label="STT">4</td>
                            <td data-label="Mã môn">31241283</td>
                            <td data-label="Tên môn">
                                Hệ quản trị cơ sở dữ liệu
                            </td>
                            <td data-label="Số tín chỉ">3</td>
                            <td data-label="Điểm chữ">A</td>
                            <td data-label="Điểm hệ 4">4.0</td>
                            <td data-label="Kết quả">Đạt</td>
                        </tr>

                        <tr>
                            <td data-label="STT">5</td>
                            <td data-label="Mã môn">31231330</td>
                            <td data-label="Tên môn">
                                Khai phá dữ liệu
                            </td>
                            <td data-label="Số tín chỉ">3</td>
                            <td data-label="Điểm chữ">B</td>
                            <td data-label="Điểm hệ 4">3.0</td>
                            <td data-label="Kết quả">Đạt</td>
                        </tr>

                        <tr>
                            <td data-label="STT">6</td>
                            <td data-label="Mã môn">31231016</td>
                            <td data-label="Tên môn">
                                Công nghệ phần mềm
                            </td>
                            <td data-label="Số tín chỉ">3</td>
                            <td data-label="Điểm chữ">B+</td>
                            <td data-label="Điểm hệ 4">3.5</td>
                            <td data-label="Kết quả">Đạt</td>
                        </tr>

                        <tr>
                            <td data-label="STT">7</td>
                            <td data-label="Mã môn">21221904</td>
                            <td data-label="Tên môn">
                                Lịch sử Đảng Cộng sản Việt Nam
                            </td>
                            <td data-label="Số tín chỉ">2</td>
                            <td data-label="Điểm chữ">A</td>
                            <td data-label="Điểm hệ 4">4.0</td>
                            <td data-label="Kết quả">Đạt</td>
                        </tr>
                    </tbody>

                    <tfoot>
                        <tr>
                            <th scope="row" colspan="3">
                                Tổng kết học kỳ 1
                            </th>
                            <td>20</td>
                            <td colspan="2">
                                GPA học kỳ: 3.58
                            </td>
                            <td>Đạt</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </article>

        <!-- ================= BẢNG ĐIỂM HỌC KỲ 2 ================= -->
        <article>
            <h2>
                Bảng điểm học kỳ 2 - Năm học 2025 - 2026
            </h2>

            <div class="bang-cuon">
                <table>
                    <caption>
                        Bảng điểm chi tiết học kỳ 2
                        năm học 2025 - 2026
                    </caption>

                    <thead>
                        <tr>
                            <th scope="col">STT</th>
                            <th scope="col">Mã môn</th>
                            <th scope="col">Tên môn học</th>
                            <th scope="col">Số tín chỉ</th>
                            <th scope="col">Điểm chữ</th>
                            <th scope="col">Điểm hệ 4</th>
                            <th scope="col">Kết quả</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td data-label="STT">1</td>
                            <td data-label="Mã môn">31232001</td>
                            <td data-label="Tên môn">
                                Trí tuệ nhân tạo
                            </td>
                            <td data-label="Số tín chỉ">3</td>
                            <td data-label="Điểm chữ">A</td>
                            <td data-label="Điểm hệ 4">4.0</td>
                            <td data-label="Kết quả">Đạt</td>
                        </tr>

                        <tr>
                            <td data-label="STT">2</td>
                            <td data-label="Mã môn">31232002</td>
                            <td data-label="Tên môn">
                                Phát triển ứng dụng di động
                            </td>
                            <td data-label="Số tín chỉ">3</td>
                            <td data-label="Điểm chữ">B+</td>
                            <td data-label="Điểm hệ 4">3.5</td>
                            <td data-label="Kết quả">Đạt</td>
                        </tr>

                        <tr>
                            <td data-label="STT">3</td>
                            <td data-label="Mã môn">31232003</td>
                            <td data-label="Tên môn">
                                Kiến trúc phần mềm
                            </td>
                            <td data-label="Số tín chỉ">3</td>
                            <td data-label="Điểm chữ">B</td>
                            <td data-label="Điểm hệ 4">3.0</td>
                            <td data-label="Kết quả">Đạt</td>
                        </tr>

                        <tr>
                            <td data-label="STT">4</td>
                            <td data-label="Mã môn">31232004</td>
                            <td data-label="Tên môn">
                                Điện toán đám mây
                            </td>
                            <td data-label="Số tín chỉ">3</td>
                            <td data-label="Điểm chữ">A</td>
                            <td data-label="Điểm hệ 4">4.0</td>
                            <td data-label="Kết quả">Đạt</td>
                        </tr>

                        <tr>
                            <td data-label="STT">5</td>
                            <td data-label="Mã môn">31232005</td>
                            <td data-label="Tên môn">
                                Kiểm thử phần mềm
                            </td>
                            <td data-label="Số tín chỉ">3</td>
                            <td data-label="Điểm chữ">B+</td>
                            <td data-label="Điểm hệ 4">3.5</td>
                            <td data-label="Kết quả">Đạt</td>
                        </tr>
                    </tbody>

                    <tfoot>
                        <tr>
                            <th scope="row" colspan="3">
                                Tổng kết học kỳ 2
                            </th>
                            <td>15</td>
                            <td colspan="2">
                                GPA học kỳ: 3.60
                            </td>
                            <td>Đạt</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </article>

        <!-- ================= TỔNG HỢP GPA ================= -->
        <article>
            <h2>Tổng hợp GPA và tín chỉ tích lũy</h2>

            <div class="bang-cuon">
                <table>
                    <caption>
                        Bảng tổng hợp GPA và tín chỉ tích lũy
                        toàn khóa
                    </caption>

                    <thead>
                        <tr>
                            <th scope="col">Học kỳ</th>
                            <th scope="col">Số tín chỉ</th>
                            <th scope="col">GPA học kỳ</th>
                            <th scope="col">GPA tích lũy</th>
                            <th scope="col">
                                Tín chỉ tích lũy
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <th
                                scope="row"
                                data-label="Học kỳ"
                            >
                                HK1 - 2025/2026
                            </th>
                            <td data-label="Số tín chỉ">20</td>
                            <td data-label="GPA học kỳ">3.58</td>
                            <td data-label="GPA tích lũy">
                                3.58
                            </td>
                            <td data-label="Tín chỉ tích lũy">
                                20
                            </td>
                        </tr>

                        <tr>
                            <th
                                scope="row"
                                data-label="Học kỳ"
                            >
                                HK2 - 2025/2026
                            </th>
                            <td data-label="Số tín chỉ">15</td>
                            <td data-label="GPA học kỳ">3.60</td>
                            <td data-label="GPA tích lũy">
                                3.59
                            </td>
                            <td data-label="Tín chỉ tích lũy">
                                35
                            </td>
                        </tr>

                        <tr>
                            <th
                                scope="row"
                                data-label="Học kỳ"
                            >
                                Tổng tích lũy
                            </th>
                            <td data-label="Số tín chỉ">35</td>
                            <td data-label="GPA học kỳ">—</td>
                            <td data-label="GPA tích lũy">
                                3.59
                            </td>
                            <td data-label="Tín chỉ tích lũy">
                                35 / 130
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p>
                Với GPA tích lũy hiện tại là
                <strong>3.59</strong>, sinh viên đang nằm trong
                nhóm xếp loại <strong>Giỏi</strong> và có khả
                năng đạt mục tiêu tốt nghiệp loại Giỏi nếu duy
                trì kết quả ở các học kỳ tiếp theo.
            </p>
        </article>

        <!-- ================= VIDEO ================= -->
        <article>
            <h2>Video hướng dẫn xem kết quả học tập</h2>

            <p>
                Video dưới đây hướng dẫn cách tra cứu và đọc
                bảng điểm chi tiết trên hệ thống EduGPA.
            </p>

            <figure>
                <iframe
                    src="https://www.youtube.com/embed/UB1O30fR-EE"
                    title="Video hướng dẫn xem kết quả học tập trên EduGPA"
                    width="560"
                    height="315"
                    loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                ></iframe>

                <figcaption>
                    Video hướng dẫn tra cứu bảng điểm chi tiết
                    trên EduGPA.
                </figcaption>
            </figure>
        </article>

        <!-- ================= NHẬN XÉT ================= -->
        <article>
            <h2>Nhận xét và gợi ý học tập</h2>

            <p>
                Dựa trên kết quả học tập ở hai học kỳ, EduGPA
                đưa ra một số nhận xét:
            </p>

            <ul>
                <li>
                    Các môn đạt kết quả tốt:
                    <strong>
                        Thiết kế và lập trình web
                    </strong>,
                    <strong>
                        Hệ quản trị cơ sở dữ liệu
                    </strong>,
                    <strong>Trí tuệ nhân tạo</strong> và
                    <strong>Điện toán đám mây</strong>.
                </li>

                <li>
                    Các môn cần cải thiện thêm:
                    <strong>Lập trình mạng</strong>,
                    <strong>Khai phá dữ liệu</strong> và
                    <strong>Kiến trúc phần mềm</strong>.
                </li>

                <li>
                    Sinh viên nên tập trung ôn luyện thêm phần
                    lý thuyết nâng cao và làm bài tập nhóm cho
                    các môn cần cải thiện.
                </li>

                <li>
                    Có thể tham khảo tài liệu và video bài
                    giảng được gợi ý trên trang chi tiết từng
                    môn học.
                </li>
            </ul>
        </article>

        <!-- ================= KHÁM PHÁ ================= -->
        <aside class="kham-pha">
            <h2>Khám phá EduGPA</h2>

            <ul>
                <li>
                    <a href="index.php">
                        Quay lại trang chủ EduGPA
                    </a>
                </li>

                <li>
                    <a href="danh-sach.php">
                        Xem danh sách môn học
                    </a>
                </li>

                <li>
                    <a href="chi-tiet.php?id=1">
                        Xem môn học đầu tiên
                    </a>
                </li>

                <li>
                    <a href="gioi-thieu.php">
                        Tìm hiểu về EduGPA và nhóm thực hiện
                    </a>
                </li>

                <li>
                    <a href="lien-he.php">
                        Liên hệ và đăng ký thành viên
                    </a>
                </li>
            </ul>
        </aside>

    </main>

    <!-- ================= FOOTER ================= -->
    <footer class="chan-trang">
        <div class="chan-trang__noi-dung">

            <div>
                <p class="chan-trang__thuong-hieu">
                    EduGPA - Nhóm 3
                </p>

                <p class="chan-trang__mo-ta">
                    Khoa Toán - Tin &middot;
                    Trường Đại học Sư phạm -
                    Đại học Đà Nẵng
                </p>
            </div>

            <nav
                class="chan-trang__dieu-huong"
                aria-label="Điều hướng chân trang"
            >
                <a href="index.php">Trang chủ</a>
                <a href="danh-sach.php">Danh sách</a>
                <a href="chi-tiet.php?id=1">
                    Chi tiết
                </a>
                <a href="gioi-thieu.php">
                    Giới thiệu
                </a>
                <a href="lien-he.php">Liên hệ</a>
            </nav>

        </div>
    </footer>

    <script type="module" src="js/main.js"></script>
    <script
        type="module"
        src="js/trang-chi-tiet.js"
    ></script>

</body>

</html>

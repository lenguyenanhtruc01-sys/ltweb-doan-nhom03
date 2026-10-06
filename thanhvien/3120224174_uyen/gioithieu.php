<?php
declare(strict_types=1);
/**
 * gioithieu.php – Trang cá nhân Huỳnh Phương Uyên, Nhóm 03.
 * PHP 1: Lọc kỹ năng theo nhóm bằng GET với danh sách giá trị cho phép.
 * PHP 2: Tính điểm A1/A2/A3 bằng POST, kiểm tra máy chủ và PRG.
 * Thử: chọn nhóm kỹ năng; nhập điểm hợp lệ/sai; tắt JS và thử lại.
 */
require __DIR__ . '/../../inc/config.php';

// Chức năng 1: PHP lọc kỹ năng, không dựa vào JavaScript.
$nhomKyNang = [
    'tat-ca' => 'Tất cả kỹ năng',
    'web' => 'Thiết kế website',
    'du-lieu' => 'Cơ sở dữ liệu',
    'lam-viec' => 'Làm việc và công cụ',
];
$danhSachKyNang = [
    ['ten' => 'HTML5', 'nhom' => 'web'],
    ['ten' => 'Thiết kế giao diện website', 'nhom' => 'web'],
    ['ten' => 'Làm việc nhóm', 'nhom' => 'lam-viec'],
    ['ten' => 'Quản lý mã nguồn với GitHub', 'nhom' => 'lam-viec'],
    ['ten' => 'Thiết kế và quản lý cơ sở dữ liệu', 'nhom' => 'du-lieu'],
];
$nhomDangChon = $_GET['nhom'] ?? 'tat-ca';
if (!is_string($nhomDangChon) || !array_key_exists($nhomDangChon, $nhomKyNang)) {
    $nhomDangChon = 'tat-ca';
}
$kyNangLoc = array_values(array_filter(
    $danhSachKyNang,
    static fn (array $kyNang): bool =>
        $nhomDangChon === 'tat-ca' || $kyNang['nhom'] === $nhomDangChon
));

// Chức năng 2: PHP tính điểm với xác thực 0–10 và Post/Redirect/Get.
$maXacThuc = $_SESSION['uyen_csrf'] ?? null;
if (!is_string($maXacThuc) || strlen($maXacThuc) !== 32) {
    $maXacThuc = bin2hex(random_bytes(16));
    $_SESSION['uyen_csrf'] = $maXacThuc;
}
$du = ['a1' => '', 'a2' => '', 'a3' => ''];
$loi = [];
$ketQuaDiem = $_SESSION['uyen_ket_qua_diem'] ?? null;
unset($_SESSION['uyen_ket_qua_diem']);
if (!is_float($ketQuaDiem) && !is_int($ketQuaDiem)) {
    $ketQuaDiem = null;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $maGui = $_POST['csrf_uyen'] ?? null;
    if (!is_string($maGui) || !hash_equals($maXacThuc, $maGui)) {
        $loi['csrf'] = 'Phiên biểu mẫu không hợp lệ. Vui lòng tải lại trang rồi thử lại.';
    }
    $diem = [];
    foreach (['a1', 'a2', 'a3'] as $truong) {
        $giaTri = $_POST[$truong] ?? null;
        $du[$truong] = is_string($giaTri) ? trim($giaTri) : '';
        $hopLe = preg_match('/^(?:0|[1-9]|10)(?:\.\d{1,2})?$/D', $du[$truong]) === 1;
        $so = $hopLe ? filter_var($du[$truong], FILTER_VALIDATE_FLOAT) : false;
        if ($so === false || $so < 0 || $so > 10) {
            $loi[$truong] = 'Vui lòng nhập điểm từ 0 đến 10, tối đa 2 chữ số thập phân.';
        } else {
            $diem[$truong] = (float) $so;
        }
    }
    if ($loi === []) {
        $_SESSION['uyen_ket_qua_diem'] = round(
            0.2 * $diem['a1'] + 0.3 * $diem['a2'] + 0.5 * $diem['a3'],
            2
        );
        // POST thành công phải chuyển sang GET để tránh gửi lại khi F5.
        header('Location: gioithieu.php#tinh-diem', true, 303);
        exit;
    }
    $ketQuaDiem = null;
}

// Gắn đường dẫn về thư mục gốc và giữ kiểu riêng của Uyên.
$tieuDe = 'Giới thiệu Huỳnh Phương Uyên';
$trang = 'gioi-thieu';
$goc = '../../';
$lopBody = 'trang-ca-nhan';
$cssTrang = 'css/php-ca-nhan.css';
require __DIR__ . '/../../inc/header.php';
?>
<main class="bao noi-dung-chinh">

        <h1 class="noi-dung-chinh__tieu-de">Giới thiệu bản thân</h1>
            <section class="the-noi-dung tien-ich-ca-nhan">
    <h2 class="the-noi-dung__tieu-de">Tùy chỉnh cỡ chữ</h2>

    <div
        class="bo-chon-co-chu"
        role="group"
        aria-label="Chọn cỡ chữ cho trang"
    >
        <button
            class="nut-co-chu"
            type="button"
            data-co-chu="nho"
            aria-pressed="false"
        >
            Chữ nhỏ
        </button>

        <button
            class="nut-co-chu"
            type="button"
            data-co-chu="vua"
            aria-pressed="true"
        >
            Chữ vừa
        </button>

        <button
            class="nut-co-chu"
            type="button"
            data-co-chu="lon"
            aria-pressed="false"
        >
            Chữ lớn
        </button>
    </div>

    <p
        id="thong-bao-co-chu"
        class="thong-bao-tuong-tac"
        aria-live="polite"
    ></p>

    <noscript>
        <p>
            Hãy bật JavaScript để sử dụng chức năng thay đổi cỡ chữ
            và tìm kiếm môn học.
        </p>
    </noscript>
        </section>      
        <section class="the-noi-dung thong-tin">
            <h2 class="the-noi-dung__tieu-de">Thông tin cá nhân</h2>

            <p>
                Xin chào! Mình là Huỳnh Phương Uyên, thành viên của Nhóm 3
                thực hiện dự án EduGPA.
            </p>

            <p>
                Mình hiện đang học tại lớp 24CNTT1, khoa Toán-Tin, Trường Đại học Sư phạm - Đại học Đà Nẵng.
                Trong dự án EduGPA, mình tham gia xây dựng và phát triển
                nội dung cho website, thiết kế giao diện và hỗ trợ các thành viên trong nhóm.
            </p>

            <figure class="thong-tin__anh">
                <img
                    class="thong-tin__anh-chan-dung"
                    src="anh-ca-nhan.jpg"
                    alt="Ảnh chân dung của Uyên"
                    width="300"
                    height="400"
                    loading="lazy">

                <figcaption class="thong-tin__chu-thich">
                    Ảnh chân dung của Phương Uyên
                </figcaption>
            </figure>
        </section>

        <article class="the-noi-dung so-thich">
            <h2 class="the-noi-dung__tieu-de">Dự án và sở thích</h2>

            <p>
                Mình chưa có nhiều kinh nghiệm tham gia các dự án thực tế.
                Hiện tại, mình đang tập trung học tập và tích lũy thêm kiến thức về lập trình,
                thiết kế website. Mình cũng đang tìm hiểu và tham gia hoạt động nghiên cứu khoa học,
                mình rất mong muốn có cơ hội tiếp xúc với nhiều dự án hơn nữa trong thời gian tới.
            </p>

            <p>
                Ngoài việc học tập, mình thích chơi thể thao và đọc truyện, hay tham gia các hoạt động,
                sự kiện bên đoàn trường tổ chức.
            </p>
        </article>


<section class="the-noi-dung ky-nang" id="loc-ky-nang">
    <h2 class="the-noi-dung__tieu-de">Kỹ năng – lọc bằng PHP</h2>
    <form class="uyen-form" method="get" action="gioithieu.php#loc-ky-nang">
        <label for="nhom-ky-nang">Chọn nhóm kỹ năng</label>
        <div class="uyen-form__hang">
            <select id="nhom-ky-nang" name="nhom">
                <?php foreach ($nhomKyNang as $maNhom => $tenNhom): ?>
                    <option value="<?= e($maNhom) ?>" <?= $nhomDangChon === $maNhom ? 'selected' : '' ?>>
                        <?= e($tenNhom) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Lọc kỹ năng</button>
        </div>
    </form>
    <p class="uyen-huong-dan">Danh sách được lọc trên máy chủ PHP, không phụ thuộc JavaScript.</p>
    <?php if ($kyNangLoc): ?>
        <ul class="ky-nang__danh-sach">
            <?php foreach ($kyNangLoc as $kyNang): ?>
                <li><?= e($kyNang['ten']) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Không có kỹ năng phù hợp.</p>
    <?php endif; ?>
</section>

<section class="the-noi-dung uyen-tinh-diem" id="tinh-diem">
    <h2 class="the-noi-dung__tieu-de">Máy tính điểm học phần bằng PHP</h2>
    <p>Điểm tổng kết dự kiến = 20% A1 + 30% A2 + 50% A3.</p>
    <form class="uyen-form" method="post" action="gioithieu.php#tinh-diem" novalidate>
        <input type="hidden" name="csrf_uyen" value="<?= e($maXacThuc) ?>">
        <div class="uyen-form__luoi">
            <?php foreach (['a1' => 'Điểm A1 (20%)', 'a2' => 'Điểm A2 (30%)', 'a3' => 'Điểm A3 (50%)'] as $maDiem => $nhanDiem): ?>
                <div class="uyen-form__truong">
                    <label for="<?= e($maDiem) ?>"><?= e($nhanDiem) ?></label>
                    <input
                        id="<?= e($maDiem) ?>"
                        name="<?= e($maDiem) ?>"
                        type="number"
                        min="0" max="10" step="any" required
                        inputmode="decimal"
                        value="<?= e($du[$maDiem]) ?>"
                        <?= isset($loi[$maDiem]) ? 'aria-invalid="true" aria-describedby="loi-' . e($maDiem) . '"' : '' ?>
                    >
                    <?php if (isset($loi[$maDiem])): ?>
                        <p class="uyen-loi" id="loi-<?= e($maDiem) ?>" role="alert"><?= e($loi[$maDiem]) ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (isset($loi['csrf'])): ?>
            <p class="uyen-loi" role="alert"><?= e($loi['csrf']) ?></p>
        <?php endif; ?>
        <button type="submit">Tính điểm học phần</button>
    </form>
    <?php if ($ketQuaDiem !== null): ?>
        <div class="uyen-ket-qua" role="status">
            <p>Điểm tổng kết dự kiến:</p>
            <p class="uyen-ket-qua__so"><?= e(number_format($ketQuaDiem, 2, ',', '.')) ?> / 10</p>
            <p>Kết quả được tính trên máy chủ; tải lại trang sẽ không gửi lại biểu mẫu.</p>
        </div>
    <?php endif; ?>
</section>

        <!-- ================= THỜI KHÓA BIỂU ================= -->

        <section class="the-noi-dung lich-hoc">
            <h2 class="the-noi-dung__tieu-de">Thời khóa biểu trong tuần</h2>
            <div class="tim-mon-hoc">
    <label for="o-tim-mon-hoc">
        Tìm môn học trong thời khóa biểu
    </label>

    <div class="tim-mon-hoc__nhom">
        <input
            type="search"
            id="o-tim-mon-hoc"
            placeholder="Nhập tên môn, mã môn hoặc phòng học..."
            autocomplete="off"
        >

        <button id="nut-xoa-tim" type="button">
            Xóa tìm kiếm
        </button>
    </div>

    <p
        id="ket-qua-tim-mon"
        class="thong-bao-tuong-tac"
        aria-live="polite"
    >
        Nhập từ khóa để tìm môn học.
    </p>
</div>
            <div class="tkb-container" tabindex="0" role="region"
                aria-label="Thời khóa biểu có thể cuộn ngang">

                <table class="thoi-khoa-bieu">

                    <caption>Thời khóa biểu</caption>

                    <thead>
                        <tr>
                            <th scope="col">Tiết</th>
                            <th scope="col">Thứ 2</th>
                            <th scope="col">Thứ 3</th>
                            <th scope="col">Thứ 4</th>
                            <th scope="col">Thứ 5</th>
                            <th scope="col">Thứ 6</th>
                            <th scope="col">Thứ 7</th>
                            <th scope="col">Chủ nhật</th>
                        </tr>
                    </thead>

                    <tbody>

                        <!-- TIẾT 1 -->

                        <tr>
                            <th scope="row" class="tiet">1</th>

                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>

                            <!-- Thiết kế và lập trình web: tiết 1-3 -->

                            <td rowspan="3" class="mon-hoc">
                                31231755 - 24-0102<br>
                                Thiết kế và lập trình web<br>
                                Phòng: B3-303
                            </td>

                            <td></td>
                        </tr>

                        <!-- TIẾT 2 -->

                        <tr>
                            <th scope="row" class="tiet">2</th>

                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>

                            <td></td>
                        </tr>

                        <!-- TIẾT 3 -->

                        <tr>
                            <th scope="row" class="tiet">3</th>

                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>

                            <td></td>
                        </tr>

                        <!-- TIẾT 4 -->

                        <tr>
                            <th scope="row" class="tiet">4</th>

                            <td></td>
                            <td></td>
                            <td></td>

                            <!-- Khai phá dữ liệu: tiết 4-6 -->

                            <td rowspan="3" class="mon-hoc">
                                31231330 - 24-0102<br>
                                Khai phá dữ liệu<br>
                                Phòng: A5-404B
                            </td>

                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        <!-- TIẾT 5 -->

                        <tr>
                            <th scope="row" class="tiet">5</th>

                            <td></td>
                            <td></td>
                            <td></td>

                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        <!-- TIẾT 6 -->

                        <tr>
                            <th scope="row" class="tiet">6</th>

                            <td></td>
                            <td></td>
                            <td></td>

                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                    </tbody>

                    <!-- KHOẢNG NGHỈ -->

                    <tbody>
                        <tr>
                            <td class="nghi" colspan="8"></td>
                        </tr>
                    </tbody>

                    <tbody>

                        <!-- TIẾT 7 -->

                        <tr>
                            <th scope="row" class="tiet">7</th>

                            <td></td>
                            <td></td>

                            <!-- Hệ quản trị CSDL: tiết 7-10 -->

                            <td rowspan="4" class="mon-hoc">
                                31241283 - 24-0301<br>
                                Hệ quản trị cơ sở dữ liệu<br>
                                Phòng: B1-104
                            </td>

                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        <!-- TIẾT 8 -->

                        <tr>
                            <th scope="row" class="tiet">8</th>

                            <td></td>
                            <td></td>

                            <!-- Thứ 4 đã rowspan -->

                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        <!-- TIẾT 9 -->

                        <tr>
                            <th scope="row" class="tiet">9</th>

                            <td></td>
                            <td></td>

                            <!-- Thứ 4 đã rowspan -->

                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        <!-- TIẾT 10 -->

                        <tr>
                            <th scope="row" class="tiet">10</th>

                            <td></td>
                            <td></td>

                            <!-- Thứ 4 đã rowspan -->

                            <!-- Công nghệ phần mềm: tiết 10-12 -->

                            <td rowspan="3" class="mon-hoc">
                                31231016 - 24-0103<br>
                                Công nghệ phần mềm<br>
                                Phòng: B3-304
                            </td>

                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>

                        <!-- TIẾT 11 -->

                        <tr>
                            <th scope="row" class="tiet">11</th>

                            <td></td>
                            <td></td>

                            <!-- Lịch sử Đảng: tiết 11-12 -->

                            <td rowspan="2" class="mon-hoc">
                                21221904 - 24-0318<br>
                                Lịch sử Đảng Cộng sản Việt Nam<br>
                                Phòng: A6-502
                            </td>

                            <!-- Thứ 5 đã rowspan Công nghệ phần mềm -->

                            <td></td>

                            <!-- An toàn thông tin: tiết 11-12 -->

                            <td rowspan="2" class="mon-hoc">
                                31221010 - 24-0103<br>
                                An toàn thông tin<br>
                                Phòng: B3-506
                            </td>

                            <td></td>
                        </tr>

                        <!-- TIẾT 12 -->

                        <tr>
                            <th scope="row" class="tiet">12</th>

                            <td></td>
                            <td></td>

                            <!-- Thứ 4 đã rowspan Lịch sử Đảng -->

                            <!-- Thứ 5 đã rowspan Công nghệ phần mềm -->

                            <td></td>

                            <!-- Thứ 7 đã rowspan An toàn thông tin -->

                            <td></td>
                        </tr>

                    </tbody>

                </table>

            </div>
        </section>

    </main>


<!-- Giữ hai tương tác JavaScript của Bài tập 4 (tùy chọn khi bật JS). -->
<script type="module" src="js/canhan.js"></script>
<?php require __DIR__ . '/../../inc/footer.php'; ?>

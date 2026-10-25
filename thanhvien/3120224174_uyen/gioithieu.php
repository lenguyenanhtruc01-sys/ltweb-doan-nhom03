<?php
declare(strict_types=1);
/**
 * gioithieu.php – Trang cá nhân Huỳnh Phương Uyên, Nhóm 03.
 * CSDL: tìm/lọc kỹ năng và tìm môn học từ hai bảng tv_uyen_*.
 * PHP: tính điểm A1/A2/A3 bằng POST, kiểm tra máy chủ và PRG.
 * Thử: lọc kỹ năng, tìm môn theo tên/phòng; nhập điểm; tắt JavaScript.
 */
require __DIR__ . '/../../inc/config.php';

const MSSV_UYEN = '3120224174';

// Chức năng 1: tìm và lọc kỹ năng từ CSDL, không dựa vào JavaScript.
$nhomKyNang = [
    'tat-ca' => 'Tất cả kỹ năng',
    'web' => 'Thiết kế website',
    'du-lieu' => 'Cơ sở dữ liệu',
    'lam-viec' => 'Làm việc và công cụ',
];
$nhomDangChon = layChuoiDauVao($_GET['nhom'] ?? 'tat-ca', 'tat-ca');
if (!array_key_exists($nhomDangChon, $nhomKyNang)) {
    $nhomDangChon = 'tat-ca';
}
$sqlKyNang = 'SELECT ten FROM tv_uyen_ky_nang WHERE mssv = ?';
$thamSoKyNang = [MSSV_UYEN];
if ($nhomDangChon !== 'tat-ca') {
    $sqlKyNang .= ' AND nhom = ?';
    $thamSoKyNang[] = $nhomDangChon;
}
$sqlKyNang .= ' ORDER BY id';
$truyVanKyNang = db()->prepare($sqlKyNang);
$truyVanKyNang->execute($thamSoKyNang);
$kyNangLoc = $truyVanKyNang->fetchAll();

// Chức năng 2: tìm môn trong thời khóa biểu trực tiếp từ CSDL.
$tuKhoaMon = layChuoiDauVao($_GET['q'] ?? '');
$loiTimMon = '';
$dsMonHoc = [];
if (mb_strlen($tuKhoaMon) > 100) {
    $loiTimMon = 'Từ khóa tìm môn học không được vượt quá 100 ký tự.';
} else {
    $sqlMonHoc = 'SELECT ma_mon, ten_mon, ma_lop, thu, tiet_bat_dau, tiet_ket_thuc, phong
                  FROM tv_uyen_thoi_khoa_bieu
                  WHERE mssv = ?';
    $thamSoMonHoc = [MSSV_UYEN];
    if ($tuKhoaMon !== '') {
        $tuKhoaAnToan = '%' . addcslashes($tuKhoaMon, '%_\\') . '%';
        $sqlMonHoc .= ' AND (ma_mon LIKE ? OR ten_mon LIKE ? OR ma_lop LIKE ? OR phong LIKE ?)';
        array_push($thamSoMonHoc, $tuKhoaAnToan, $tuKhoaAnToan, $tuKhoaAnToan, $tuKhoaAnToan);
    }
    $sqlMonHoc .= ' ORDER BY thu, tiet_bat_dau, ten_mon';
    $truyVanMonHoc = db()->prepare($sqlMonHoc);
    $truyVanMonHoc->execute($thamSoMonHoc);
    $dsMonHoc = $truyVanMonHoc->fetchAll();
}

$lichTheoThu = [];
foreach ($dsMonHoc as $monHoc) {
    $lichTheoThu[(int) $monHoc['thu']][] = $monHoc;
}

// Máy tính điểm: xác thực 0–10 và Post/Redirect/Get.
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
$cssTrang = 'css/style.css';
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
            Có thể bật JavaScript để đổi cỡ chữ và lọc nhanh lịch đã tải.
            Tìm môn từ CSDL vẫn hoạt động khi tắt JavaScript.
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
    <h2 class="the-noi-dung__tieu-de">Kỹ năng – tìm và lọc từ MySQL</h2>
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
    <p class="uyen-huong-dan">Kỹ năng được đọc và lọc trên máy chủ từ bảng tv_uyen_ky_nang.</p>
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

        <section class="the-noi-dung lich-hoc" id="thoi-khoa-bieu">
            <h2 class="the-noi-dung__tieu-de">Thời khóa biểu trong tuần</h2>
            <div class="tim-mon-hoc">
                <form method="get" action="gioithieu.php#thoi-khoa-bieu" class="uyen-form">
                    <label for="o-tim-mon-hoc">Tìm môn trong CSDL theo tên, mã môn, lớp hoặc phòng</label>
                    <div class="tim-mon-hoc__nhom">
                        <input
                            type="search"
                            id="o-tim-mon-hoc"
                            name="q"
                            value="<?= e($tuKhoaMon) ?>"
                            placeholder="Ví dụ: Khai phá dữ liệu hoặc B3-303"
                            autocomplete="off"
                        >
                        <button type="submit">Tìm môn học</button>
                        <button id="nut-xoa-tim" type="button">Xóa tìm kiếm nhanh</button>
                    </div>
                </form>
                <p><a href="gioithieu.php#thoi-khoa-bieu">Xóa bộ lọc CSDL và hiện lại toàn bộ lịch</a></p>
                <p id="ket-qua-tim-mon" class="thong-bao-tuong-tac" aria-live="polite">
                    <?= e((string) count($dsMonHoc)) ?> môn học được tải từ CSDL.
                </p>
            </div>
            <?php if ($loiTimMon !== ''): ?>
                <p class="uyen-loi" role="alert"><?= e($loiTimMon) ?></p>
            <?php elseif ($dsMonHoc === []): ?>
                <p class="thong-bao-tuong-tac" role="status">Không tìm thấy môn học phù hợp.</p>
            <?php endif; ?>
            <p class="lich-hoc__huong-dan">
                Dữ liệu thời khóa biểu được truy vấn từ bảng tv_uyen_thoi_khoa_bieu.
            </p>
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
                    <?php for ($tiet = 1; $tiet <= 12; $tiet++): ?>
                        <?php if ($tiet === 7): ?>
                    </tbody>
                    <tbody>
                        <tr><td class="nghi" colspan="8"></td></tr>
                    </tbody>
                    <tbody>
                        <?php endif; ?>
                            <tr>
                                <th scope="row" class="tiet"><?= e((string) $tiet) ?></th>
                                <?php for ($thu = 2; $thu <= 8; $thu++): ?>
                                    <?php
                                    $monBatDau = null;
                                    $tietBiChiem = false;
                                    foreach ($lichTheoThu[$thu] ?? [] as $monTrongNgay) {
                                        if ((int) $monTrongNgay['tiet_bat_dau'] === $tiet) {
                                            $monBatDau = $monTrongNgay;
                                            break;
                                        }
                                        if (
                                            (int) $monTrongNgay['tiet_bat_dau'] < $tiet
                                            && (int) $monTrongNgay['tiet_ket_thuc'] >= $tiet
                                        ) {
                                            $tietBiChiem = true;
                                            break;
                                        }
                                    }
                                    ?>
                                    <?php if ($monBatDau !== null): ?>
                                        <td
                                            rowspan="<?= e((string) ((int) $monBatDau['tiet_ket_thuc'] - (int) $monBatDau['tiet_bat_dau'] + 1)) ?>"
                                            class="mon-hoc"
                                        >
                                            <?= e($monBatDau['ma_mon']) ?> - <?= e($monBatDau['ma_lop']) ?><br>
                                            <?= e($monBatDau['ten_mon']) ?><br>
                                            Phòng: <?= e($monBatDau['phong']) ?>
                                        </td>
                                    <?php elseif (!$tietBiChiem): ?>
                                        <td></td>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </tr>
                    <?php endfor; ?>
                    </tbody>

                </table>

            </div>
        </section>

    </main>


<!-- Giữ hai tương tác JavaScript của Bài tập 4 (tùy chọn khi bật JS). -->
<script type="module" src="js/canhan.js"></script>
<?php require __DIR__ . '/../../inc/footer.php'; ?>

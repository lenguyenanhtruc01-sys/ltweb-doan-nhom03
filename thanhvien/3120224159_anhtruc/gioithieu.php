<?php
/**
 * File: thanhvien/3120224159_anhtruc/gioithieu.php
 * Chức năng: Giới thiệu thành viên và tính chi phí học kỳ với mục tiêu GPA.
 * Xử lý: PHP server-side, validation, POST/PRG.
 * Kiểm thử: GPA hợp lệ/không hợp lệ, số tín chỉ, học phí và mục tiêu GPA.
 */

require_once __DIR__ . '/../../inc/config.php';

$pageTitle = 'Giới thiệu - Lê Nguyễn Anh Trúc';
$tieuDe = $pageTitle;

/*
 * Khởi tạo session nếu hệ thống chưa khởi tạo.
 * Bình thường inc/config.php của project đã xử lý session.
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* ==============================
   XỬ LÝ KẾT QUẢ SAU POST/PRG
   ============================== */

$gpaResult = $_SESSION['gpa_result'] ?? null;
$gpaErrors = $_SESSION['gpa_errors'] ?? [];

unset($_SESSION['gpa_result'], $_SESSION['gpa_errors']);


/* ==============================
   CHỨC NĂNG:
   CHI PHÍ HỌC KỲ VỚI MỤC TIÊU GPA
   ============================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['tinh_chi_phi_gpa'])) {

    /*
     * Lấy dữ liệu từ POST.
     * Không tin tưởng dữ liệu người dùng gửi lên.
     */
    $gpaHienTai = filter_input(
        INPUT_POST,
        'gpa_hien_tai',
        FILTER_VALIDATE_FLOAT
    );

    $gpaMucTieu = filter_input(
        INPUT_POST,
        'gpa_muc_tieu',
        FILTER_VALIDATE_FLOAT
    );

    $tinChiDaHoc = filter_input(
        INPUT_POST,
        'tin_chi_da_hoc',
        FILTER_VALIDATE_INT
    );

    $tinChiHocKy = filter_input(
        INPUT_POST,
        'tin_chi_hoc_ky',
        FILTER_VALIDATE_INT
    );

    $hocPhiTinChi = filter_input(
        INPUT_POST,
        'hoc_phi_tin_chi',
        FILTER_VALIDATE_INT
    );

    $errors = [];


    /* ==============================
       KIỂM TRA GPA HIỆN TẠI
       ============================== */

    if (
        $gpaHienTai === false
        || $gpaHienTai === null
        || $gpaHienTai < 0
        || $gpaHienTai > 4
    ) {
        $errors[] = 'GPA hiện tại phải nằm trong khoảng từ 0 đến 4.';
    }


    /* ==============================
       KIỂM TRA GPA MỤC TIÊU
       ============================== */

    if (
        $gpaMucTieu === false
        || $gpaMucTieu === null
        || $gpaMucTieu < 0
        || $gpaMucTieu > 4
    ) {
        $errors[] = 'GPA mục tiêu phải nằm trong khoảng từ 0 đến 4.';
    }


    /* ==============================
       KIỂM TRA SỐ TÍN CHỈ ĐÃ HỌC
       ============================== */

    if (
        $tinChiDaHoc === false
        || $tinChiDaHoc === null
        || $tinChiDaHoc < 0
    ) {
        $errors[] = 'Số tín chỉ đã học phải là số nguyên không âm.';
    }


    /* ==============================
       KIỂM TRA SỐ TÍN CHỈ HỌC KỲ
       ============================== */

    if (
        $tinChiHocKy === false
        || $tinChiHocKy === null
        || $tinChiHocKy <= 0
    ) {
        $errors[] = 'Số tín chỉ học kỳ phải lớn hơn 0.';
    }


    /* ==============================
       KIỂM TRA HỌC PHÍ
       ============================== */

    if (
        $hocPhiTinChi === false
        || $hocPhiTinChi === null
        || $hocPhiTinChi <= 0
    ) {
        $errors[] = 'Học phí mỗi tín chỉ phải lớn hơn 0.';
    }


    /* ==============================
       KIỂM TRA MỤC TIÊU GPA
       ============================== */

    if (
        empty($errors)
        && $gpaMucTieu < $gpaHienTai
    ) {
        $errors[] = 'GPA mục tiêu phải lớn hơn hoặc bằng GPA hiện tại.';
    }


    /* ==============================
       NẾU DỮ LIỆU KHÔNG HỢP LỆ
       ============================== */

    if (!empty($errors)) {

        $_SESSION['gpa_errors'] = $errors;

        /*
         * POST/PRG:
         * Sau POST chuyển hướng về trang.
         */
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }


    /* ==============================
       TÍNH TOÁN
       ============================== */

    /*
     * Công thức:
     *
     * GPA mục tiêu =
     * (GPA hiện tại × tín chỉ đã học
     *  + GPA học kỳ × tín chỉ học kỳ)
     * / tổng tín chỉ
     *
     * Suy ra:
     *
     * GPA học kỳ cần đạt =
     * [GPA mục tiêu × tổng tín chỉ
     *  - GPA hiện tại × tín chỉ đã học]
     * / tín chỉ học kỳ
     */

    $tongTinChi = $tinChiDaHoc + $tinChiHocKy;

    $gpaCanDat = (
        ($gpaMucTieu * $tongTinChi)
        - ($gpaHienTai * $tinChiDaHoc)
    ) / $tinChiHocKy;


    /*
     * Tổng học phí học kỳ.
     */
    $tongHocPhi = $tinChiHocKy * $hocPhiTinChi;


    /*
     * GPA tối đa theo hệ 4.0.
     */
    $coTheDat = $gpaCanDat <= 4;


    /*
     * Trường hợp GPA cần đạt nhỏ hơn 0
     * thì xem như chỉ cần đạt tối thiểu 0.
     */
    if ($gpaCanDat < 0) {
        $gpaCanDat = 0;
    }


    /* ==============================
       LƯU KẾT QUẢ VÀO SESSION
       ============================== */

    $_SESSION['gpa_result'] = [
        'gpa_hien_tai' => $gpaHienTai,
        'gpa_muc_tieu' => $gpaMucTieu,
        'tin_chi_da_hoc' => $tinChiDaHoc,
        'tin_chi_hoc_ky' => $tinChiHocKy,
        'hoc_phi_tin_chi' => $hocPhiTinChi,
        'gpa_can_dat' => round($gpaCanDat, 2),
        'tong_hoc_phi' => $tongHocPhi,
        'co_the_dat' => $coTheDat
    ];


    /*
     * POST/PRG:
     * Chuyển hướng sau khi xử lý thành công.
     */
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

require_once __DIR__ . '/../../inc/header.php';
?>

<main class="container">

    <!-- ==============================
         GIỚI THIỆU THÀNH VIÊN
         ============================== -->

    <section class="profile-section">
        <h1>Giới thiệu thành viên</h1>

        <h2>Lê Nguyễn Anh Trúc</h2>

        <p>
            Thành viên Nhóm 03 - Hệ thống EduGPA.
        </p>

        <p>
            Phụ trách phát triển một số chức năng PHP
            phía server và hỗ trợ hoàn thiện hệ thống.
        </p>
    </section>


    <!-- ==============================
         CHỨC NĂNG TÍNH CHI PHÍ GPA
         ============================== -->

    <section class="profile-section">

        <h2>Chi phí học kỳ với mục tiêu GPA</h2>

        <p>
            Nhập thông tin học tập để tính học phí dự kiến
            và GPA cần đạt trong học kỳ.
        </p>


        <!-- HIỂN THỊ LỖI -->

        <?php if (!empty($gpaErrors)): ?>

            <div class="form-error" role="alert">

                <strong>Không thể tính toán:</strong>

                <ul>
                    <?php foreach ($gpaErrors as $error): ?>

                        <li>
                            <?= e($error) ?>
                        </li>

                    <?php endforeach; ?>
                </ul>

            </div>

        <?php endif; ?>


        <!-- FORM -->

        <form
            method="post"
            action=""
            class="gpa-form"
        >

            <div class="form-group">

                <label for="gpa_hien_tai">
                    GPA hiện tại
                </label>

                <input
                    type="number"
                    id="gpa_hien_tai"
                    name="gpa_hien_tai"
                    min="0"
                    max="4"
                    step="0.01"
                    placeholder="Ví dụ: 3.00"
                    required
                >

            </div>


            <div class="form-group">

                <label for="gpa_muc_tieu">
                    GPA mục tiêu
                </label>

                <input
                    type="number"
                    id="gpa_muc_tieu"
                    name="gpa_muc_tieu"
                    min="0"
                    max="4"
                    step="0.01"
                    placeholder="Ví dụ: 3.30"
                    required
                >

            </div>


            <div class="form-group">

                <label for="tin_chi_da_hoc">
                    Số tín chỉ đã học
                </label>

                <input
                    type="number"
                    id="tin_chi_da_hoc"
                    name="tin_chi_da_hoc"
                    min="0"
                    placeholder="Ví dụ: 60"
                    required
                >

            </div>


            <div class="form-group">

                <label for="tin_chi_hoc_ky">
                    Số tín chỉ học kỳ này
                </label>

                <input
                    type="number"
                    id="tin_chi_hoc_ky"
                    name="tin_chi_hoc_ky"
                    min="1"
                    placeholder="Ví dụ: 18"
                    required
                >

            </div>


            <div class="form-group">

                <label for="hoc_phi_tin_chi">
                    Học phí mỗi tín chỉ (VNĐ)
                </label>

                <input
                    type="number"
                    id="hoc_phi_tin_chi"
                    name="hoc_phi_tin_chi"
                    min="1"
                    placeholder="Ví dụ: 500000"
                    required
                >

            </div>


            <button
                type="submit"
                name="tinh_chi_phi_gpa"
            >
                Tính chi phí &amp; GPA
            </button>

        </form>

    </section>


    <!-- ==============================
         KẾT QUẢ
         ============================== -->

    <?php if ($gpaResult !== null): ?>

        <section class="profile-section">

            <h2>Kết quả tính toán</h2>

            <div class="result-box">

                <p>
                    <strong>GPA hiện tại:</strong>
                    <?= e(number_format(
                        $gpaResult['gpa_hien_tai'],
                        2
                    )) ?>
                </p>


                <p>
                    <strong>GPA mục tiêu:</strong>
                    <?= e(number_format(
                        $gpaResult['gpa_muc_tieu'],
                        2
                    )) ?>
                </p>


                <p>
                    <strong>Số tín chỉ đã học:</strong>
                    <?= e($gpaResult['tin_chi_da_hoc']) ?>
                </p>


                <p>
                    <strong>Số tín chỉ học kỳ:</strong>
                    <?= e($gpaResult['tin_chi_hoc_ky']) ?>
                </p>


                <p>
                    <strong>Học phí mỗi tín chỉ:</strong>
                    <?= e(number_format(
                        $gpaResult['hoc_phi_tin_chi'],
                        0,
                        ',',
                        '.'
                    )) ?>
                    VNĐ
                </p>


                <p>
                    <strong>
                        Tổng học phí học kỳ:
                    </strong>

                    <?= e(number_format(
                        $gpaResult['tong_hoc_phi'],
                        0,
                        ',',
                        '.'
                    )) ?>

                    VNĐ
                </p>


                <p>
                    <strong>
                        GPA cần đạt trong học kỳ:
                    </strong>

                    <?= e(number_format(
                        $gpaResult['gpa_can_dat'],
                        2
                    )) ?>
                </p>


                <?php if ($gpaResult['co_the_dat']): ?>

                    <p>
                        Mục tiêu GPA có thể đạt được
                        nếu kết quả học tập trong học kỳ
                        đạt mức GPA yêu cầu.
                    </p>

                <?php else: ?>

                    <p>
                        GPA cần đạt vượt quá 4.0.
                        Mục tiêu GPA này không thể đạt
                        chỉ trong một học kỳ.
                    </p>

                <?php endif; ?>

            </div>

        </section>

    <?php endif; ?>

</main>

<?php
require_once __DIR__ . '/../../inc/footer.php';
?>

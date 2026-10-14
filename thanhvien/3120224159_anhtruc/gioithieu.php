<?php
/**
 * File: thanhvien/3120224159_anhtruc/gioithieu.php
 * Chức năng:
 * - Giới thiệu thành viên.
 * - Tính chi phí học kỳ với mục tiêu GPA.
 * - Mini Quiz kiến thức IT xử lý bằng PHP.
 * Xử lý: PHP server-side, validation và POST/PRG.
 * Kiểm thử: dữ liệu GPA, học phí, tín chỉ và câu trả lời Quiz.
 */

require_once __DIR__ . '/../../inc/config.php';

$pageTitle = 'Giới thiệu - Lê Nguyễn Anh Trúc';
$tieuDe = $pageTitle;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


/* =========================================================
   DỮ LIỆU MINI QUIZ
   ========================================================= */

$quizQuestions = [
    [
        'question' => 'HTML được sử dụng chủ yếu để làm gì?',
        'options' => [
            'a' => 'Quản lý cơ sở dữ liệu',
            'b' => 'Tạo cấu trúc nội dung trang web',
            'c' => 'Biên dịch chương trình',
            'd' => 'Quản lý hệ điều hành'
        ],
        'answer' => 'b'
    ],
    [
        'question' => 'CSS được sử dụng chủ yếu để làm gì?',
        'options' => [
            'a' => 'Tạo và quản lý cơ sở dữ liệu',
            'b' => 'Xử lý yêu cầu HTTP',
            'c' => 'Định dạng và trình bày giao diện trang web',
            'd' => 'Lưu trữ phiên đăng nhập'
        ],
        'answer' => 'c'
    ],
    [
        'question' => 'PHP là ngôn ngữ được thực thi chủ yếu ở đâu?',
        'options' => [
            'a' => 'Phía máy chủ',
            'b' => 'Phía trình duyệt',
            'c' => 'Trong cơ sở dữ liệu',
            'd' => 'Trong CSS'
        ],
        'answer' => 'a'
    ],
    [
        'question' => 'HTTP status code 404 thường có ý nghĩa gì?',
        'options' => [
            'a' => 'Yêu cầu thành công',
            'b' => 'Không có quyền truy cập',
            'c' => 'Không tìm thấy tài nguyên',
            'd' => 'Lỗi máy chủ'
        ],
        'answer' => 'c'
    ],
    [
        'question' => 'SQL được sử dụng chủ yếu để làm gì?',
        'options' => [
            'a' => 'Thiết kế giao diện',
            'b' => 'Làm việc với cơ sở dữ liệu',
            'c' => 'Tạo hình ảnh',
            'd' => 'Quản lý CSS'
        ],
        'answer' => 'b'
    ]
];


/* =========================================================
   KHỞI TẠO CÂU HỎI QUIZ
   ========================================================= */

if (!isset($_SESSION['quiz_current'])) {
    $_SESSION['quiz_current'] = array_rand($quizQuestions);
}


/* =========================================================
   LẤY KẾT QUẢ SAU POST/PRG
   ========================================================= */

$gpaResult = $_SESSION['gpa_result'] ?? null;
$gpaErrors = $_SESSION['gpa_errors'] ?? [];

$quizResult = $_SESSION['quiz_result'] ?? null;
$quizErrors = $_SESSION['quiz_errors'] ?? [];

unset(
    $_SESSION['gpa_result'],
    $_SESSION['gpa_errors'],
    $_SESSION['quiz_result'],
    $_SESSION['quiz_errors']
);


/* =========================================================
   CHỨC NĂNG 1:
   CHI PHÍ HỌC KỲ VỚI MỤC TIÊU GPA
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['tinh_chi_phi_gpa'])
) {
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


    if (
        $gpaHienTai === false
        || $gpaHienTai === null
        || $gpaHienTai < 0
        || $gpaHienTai > 4
    ) {
        $errors[] = 'GPA hiện tại phải nằm trong khoảng từ 0 đến 4.';
    }


    if (
        $gpaMucTieu === false
        || $gpaMucTieu === null
        || $gpaMucTieu < 0
        || $gpaMucTieu > 4
    ) {
        $errors[] = 'GPA mục tiêu phải nằm trong khoảng từ 0 đến 4.';
    }


    if (
        $tinChiDaHoc === false
        || $tinChiDaHoc === null
        || $tinChiDaHoc < 0
    ) {
        $errors[] = 'Số tín chỉ đã học phải là số nguyên không âm.';
    }


    if (
        $tinChiHocKy === false
        || $tinChiHocKy === null
        || $tinChiHocKy <= 0
    ) {
        $errors[] = 'Số tín chỉ học kỳ phải lớn hơn 0.';
    }


    if (
        $hocPhiTinChi === false
        || $hocPhiTinChi === null
        || $hocPhiTinChi <= 0
    ) {
        $errors[] = 'Học phí mỗi tín chỉ phải lớn hơn 0.';
    }


    if (
        empty($errors)
        && $gpaMucTieu < $gpaHienTai
    ) {
        $errors[] = 'GPA mục tiêu phải lớn hơn hoặc bằng GPA hiện tại.';
    }


    if (!empty($errors)) {
        $_SESSION['gpa_errors'] = $errors;

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }


    $tongTinChi = $tinChiDaHoc + $tinChiHocKy;

    $gpaCanDat = (
        ($gpaMucTieu * $tongTinChi)
        - ($gpaHienTai * $tinChiDaHoc)
    ) / $tinChiHocKy;


    $tongHocPhi = $tinChiHocKy * $hocPhiTinChi;

    $coTheDat = $gpaCanDat <= 4;

    if ($gpaCanDat < 0) {
        $gpaCanDat = 0;
    }


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


    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


/* =========================================================
   CHỨC NĂNG 2:
   MINI QUIZ KIẾN THỨC IT
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['tra_loi_quiz'])
) {
    $questionIndex = $_SESSION['quiz_current'] ?? null;
    $selectedAnswer = $_POST['quiz_answer'] ?? '';

    $errors = [];


    /* Kiểm tra câu hỏi hiện tại */

    if (
        $questionIndex === null
        || !isset($quizQuestions[$questionIndex])
    ) {
        $errors[] = 'Không xác định được câu hỏi. Vui lòng thử lại.';
    }


    /* Kiểm tra câu trả lời */

    $allowedAnswers = ['a', 'b', 'c', 'd'];

    if (
        !is_string($selectedAnswer)
        || !in_array($selectedAnswer, $allowedAnswers, true)
    ) {
        $errors[] = 'Vui lòng chọn một đáp án hợp lệ.';
    }


    if (!empty($errors)) {
        $_SESSION['quiz_errors'] = $errors;

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }


    $currentQuestion = $quizQuestions[$questionIndex];

    $isCorrect = $selectedAnswer === $currentQuestion['answer'];


    /* Khởi tạo điểm Quiz */

    if (!isset($_SESSION['quiz_score'])) {
        $_SESSION['quiz_score'] = 0;
    }

    if (!isset($_SESSION['quiz_total'])) {
        $_SESSION['quiz_total'] = 0;
    }


    $_SESSION['quiz_total']++;

    if ($isCorrect) {
        $_SESSION['quiz_score']++;
    }


    $quizResult = [
        'correct' => $isCorrect,
        'correct_answer' => $currentQuestion['answer'],
        'score' => $_SESSION['quiz_score'],
        'total' => $_SESSION['quiz_total']
    ];

    $_SESSION['quiz_result'] = $quizResult;


    /*
     * Chọn câu hỏi mới sau khi trả lời.
     * Không chọn lại ngay câu vừa làm.
     */

    $availableQuestions = array_keys($quizQuestions);

    if (count($availableQuestions) > 1) {
        $availableQuestions = array_values(
            array_diff($availableQuestions, [$questionIndex])
        );
    }

    $_SESSION['quiz_current'] = $availableQuestions[
        array_rand($availableQuestions)
    ];


    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


/* =========================================================
   RESET ĐIỂM QUIZ
   ========================================================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['reset_quiz'])
) {
    $_SESSION['quiz_score'] = 0;
    $_SESSION['quiz_total'] = 0;
    $_SESSION['quiz_current'] = array_rand($quizQuestions);

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}


/* =========================================================
   LẤY CÂU HỎI HIỆN TẠI
   ========================================================= */

$currentQuestionIndex = $_SESSION['quiz_current'];

$currentQuestion = $quizQuestions[$currentQuestionIndex];

$currentScore = $_SESSION['quiz_score'] ?? 0;
$currentTotal = $_SESSION['quiz_total'] ?? 0;


require_once __DIR__ . '/../../inc/header.php';
?>


<main class="container">

    <!-- =====================================================
         GIỚI THIỆU THÀNH VIÊN
         ===================================================== -->

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


    <!-- =====================================================
         CHỨC NĂNG 1: CHI PHÍ HỌC KỲ VỚI MỤC TIÊU GPA
         ===================================================== -->

    <section class="profile-section">

        <h2>Chi phí học kỳ với mục tiêu GPA</h2>

        <p>
            Nhập thông tin học tập để tính học phí dự kiến
            và GPA cần đạt trong học kỳ.
        </p>


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


        <?php if ($gpaResult !== null): ?>

            <div class="result-box">

                <h3>Kết quả</h3>

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
                    <strong>Tổng học phí học kỳ:</strong>
                    <?= e(number_format(
                        $gpaResult['tong_hoc_phi'],
                        0,
                        ',',
                        '.'
                    )) ?>
                    VNĐ
                </p>

                <p>
                    <strong>GPA cần đạt trong học kỳ:</strong>
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
                        Mục tiêu này không thể đạt
                        chỉ trong một học kỳ.
                    </p>

                <?php endif; ?>

            </div>

        <?php endif; ?>

    </section>


    <!-- =====================================================
         CHỨC NĂNG 2: MINI QUIZ KIẾN THỨC IT
         ===================================================== -->

    <section class="profile-section">

        <h2>Mini Quiz kiến thức IT</h2>

        <p>
            Trả lời câu hỏi để kiểm tra kiến thức cơ bản
            về công nghệ thông tin.
        </p>


        <?php if (!empty($quizErrors)): ?>

            <div class="form-error" role="alert">

                <strong>Có lỗi:</strong>

                <ul>

                    <?php foreach ($quizErrors as $error): ?>

                        <li>
                            <?= e($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <?php if ($quizResult !== null): ?>

            <div class="result-box">

                <?php if ($quizResult['correct']): ?>

                    <p>
                        <strong>🎉 Chính xác!</strong>
                        Bạn đã chọn đúng đáp án.
                    </p>

                <?php else: ?>

                    <p>
                        <strong>Chưa chính xác.</strong>
                        Đáp án đúng là:
                        <?= e(
                            strtoupper(
                                $quizResult['correct_answer']
                            )
                        ) ?>
                    </p>

                <?php endif; ?>


                <p>
                    <strong>Điểm hiện tại:</strong>
                    <?= e($quizResult['score']) ?>
                    /
                    <?= e($quizResult['total']) ?>
                </p>

            </div>

        <?php endif; ?>


        <div class="quiz-box">

            <h3>
                <?= e($currentQuestion['question']) ?>
            </h3>


            <form
                method="post"
                action=""
            >

                <?php foreach (
                    $currentQuestion['options'] as $key => $option
                ): ?>

                    <div class="form-group">

                        <label>

                            <input
                                type="radio"
                                name="quiz_answer"
                                value="<?= e($key) ?>"
                                required
                            >

                            <?= e(strtoupper($key)) ?>.
                            <?= e($option) ?>

                        </label>

                    </div>

                <?php endforeach; ?>


                <button
                    type="submit"
                    name="tra_loi_quiz"
                >
                    Trả lời
                </button>

            </form>


            <form
                method="post"
                action=""
            >

                <button
                    type="submit"
                    name="reset_quiz"
                >
                    Đặt lại điểm
                </button>

            </form>


            <p>
                <strong>Điểm:</strong>
                <?= e($currentScore) ?>
                /
                <?= e($currentTotal) ?>
            </p>

        </div>

    </section>

</main>


<?php
require_once __DIR__ . '/../../inc/footer.php';
?>

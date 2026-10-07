<?php
/**
 * inc/header.php
 * Khung đầu trang dùng chung cho website EduGPA.
 * Hiển thị menu, giỏ hàng và trạng thái đăng nhập.
 * Đánh dấu trang đang xem bằng aria-current.
 * Được nạp sau inc/config.php.
 */

// Các biến dùng chung cho mọi trang.
$tieuDe ??= 'Trang chủ';
$trang ??= '';
$goc ??= '';

// Số món trong giỏ hàng.
$soMonTrongGio = 0;

// Tự kết nối lớp GioHang khi nhóm hoàn thành.
if (class_exists(\App\Services\GioHang::class)) {
    $gioHeader = new \App\Services\GioHang();
    $soMonTrongGio = $gioHeader->soMon();
}

// Lấy tên người dùng đã đăng nhập từ session.
$nguoiDung = $_SESSION['user'] ?? null;

if (!is_string($nguoiDung) || $nguoiDung === '') {
    $nguoiDung = null;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="EduGPA hỗ trợ sinh viên tra cứu môn học, theo dõi trạng thái học tập và lưu môn yêu thích.">

    <title><?= e($tieuDe) ?> | EduGPA</title>

    <link
        rel="stylesheet"
        href="<?= e($goc) ?>css/style.css?v=4">
    <?php if (!empty($cssTrang)): ?>
    <link rel="stylesheet" href="<?= e($cssTrang) ?>">
    <?php endif; ?>
</head>


<body class="trang<?= !empty($lopBody) ? ' ' . e($lopBody) : '' ?>">

    <!-- HEADER DÙNG CHUNG -->
    <header class="dau-trang">
        <div class="bao dau-trang__noi-dung">

            <div class="dau-trang__cum-thuong-hieu">

                <a
                    class="thuong-hieu"
                    href="<?= e($goc) ?>index.php">
                    EduGPA
                </a>

                <!-- Giữ chức năng yêu thích của Bài 4 -->
                <a
                    class="dau-trang__yeu-thich"
                    href="<?= e($goc) ?>danh-sach.php">

                    <span aria-hidden="true">♥</span>

                    <span>Yêu thích:</span>

                    <span
                        data-so-luong-yeu-thich
                        aria-live="polite"
                        aria-atomic="true">0</span>

                </a>

            </div>

            <p class="dau-trang__mo-ta">
                Hệ thống hỗ trợ tra cứu và theo dõi
                thông tin học tập cho sinh viên
            </p>

        </div>
    </header>

    <!-- MENU CHÍNH -->
    <nav
        class="dieu-huong"
        aria-label="Điều hướng chính">

        <button
            class="nut-menu"
            type="button"
            aria-controls="menu-chinh"
            aria-expanded="false"
            aria-label="Mở menu chính">

            <span aria-hidden="true">☰</span>
            <span>Menu</span>

        </button>

        <ul
            id="menu-chinh"
            class="dieu-huong__danh-sach menu-chinh">

            <!-- Trang chủ -->
            <li class="dieu-huong__muc">
                <a
                    class="dieu-huong__lien-ket <?= $trang === 'index' ? 'dieu-huong__lien-ket--hien-tai' : '' ?>"
                    href="<?= e($goc) ?>index.php"
                    <?= $trang === 'index' ? 'aria-current="page"' : '' ?>>
                    Trang chủ
                </a>
            </li>

            <!-- Danh sách -->
            <li class="dieu-huong__muc">
                <a
                    class="dieu-huong__lien-ket <?= $trang === 'danh-sach' ? 'dieu-huong__lien-ket--hien-tai' : '' ?>"
                    href="<?= e($goc) ?>danh-sach.php"
                    <?= $trang === 'danh-sach' ? 'aria-current="page"' : '' ?>>
                    Danh sách
                </a>
            </li>

            <!-- Giới thiệu -->
            <li class="dieu-huong__muc">
                <a
                    class="dieu-huong__lien-ket <?= $trang === 'gioi-thieu' ? 'dieu-huong__lien-ket--hien-tai' : '' ?>"
                    href="<?= e($goc) ?>gioi-thieu.php"
                    <?= $trang === 'gioi-thieu' ? 'aria-current="page"' : '' ?>>
                    Giới thiệu
                </a>
            </li>

            <!-- Liên hệ -->
            <li class="dieu-huong__muc">
                <a
                    class="dieu-huong__lien-ket <?= $trang === 'lien-he' ? 'dieu-huong__lien-ket--hien-tai' : '' ?>"
                    href="<?= e($goc) ?>lien-he.php"
                    <?= $trang === 'lien-he' ? 'aria-current="page"' : '' ?>>
                    Liên hệ
                </a>
            </li>

            <!-- Giỏ hàng -->
            <li class="dieu-huong__muc">
                <a
                    class="dieu-huong__lien-ket <?= $trang === 'gio-hang' ? 'dieu-huong__lien-ket--hien-tai' : '' ?>"
                    href="<?= e($goc) ?>gio-hang.php"
                    <?= $trang === 'gio-hang' ? 'aria-current="page"' : '' ?>>
                    Giỏ hàng (<?= (int) $soMonTrongGio ?>)
                </a>
            </li>

            <!-- Đăng nhập hoặc người dùng -->
            <?php if ($nguoiDung !== null): ?>

                <li class="dieu-huong__muc">
                    <span class="dieu-huong__lien-ket">
                        <?= e($nguoiDung) ?>
                    </span>
                </li>

                <li class="dieu-huong__muc">
                    <a
                        class="dieu-huong__lien-ket"
                        href="<?= e($goc) ?>dang-xuat.php">
                        Đăng xuất
                    </a>
                </li>

            <?php else: ?>

                <li class="dieu-huong__muc">
                    <a
                        class="dieu-huong__lien-ket <?= $trang === 'dang-nhap' ? 'dieu-huong__lien-ket--hien-tai' : '' ?>"
                        href="<?= e($goc) ?>dang-nhap.php"
                        <?= $trang === 'dang-nhap' ? 'aria-current="page"' : '' ?>>
                        Đăng nhập
                    </a>
                </li>

            <?php endif; ?>

        </ul>
    </nav>

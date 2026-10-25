<?php
/**
 * inc/header.php
 * Khung đầu trang dùng chung cho website EduGPA.
 */
$tieuDe ??= 'Trang chủ';
$trang ??= '';
$goc ??= '';
$phienBanCss = filemtime(__DIR__ . '/../css/style.css') ?: 1;
if (!is_string($_SESSION['csrf_gio_hang'] ?? null) || $_SESSION['csrf_gio_hang'] === '') {
    $_SESSION['csrf_gio_hang'] = bin2hex(random_bytes(32));
}

$soMonTrongGio = 0;
try {
    $keHoachHeader = new \App\Services\KeHoachHocTap();
    $duLieuHeader = $keHoachHeader->layDanhSachChiTiet();
    $soMonTrongGio = $duLieuHeader['tong_so_mon'] ?? 0;
} catch (\Throwable $e) {
    $soMonTrongGio = 0;
}

$ndHeader  = nguoiDung();
$nguoiDung = $ndHeader['ho_ten'] ?? $ndHeader['ten_dang_nhap'] ?? null;

if (!is_string($nguoiDung) || $nguoiDung === '') {
    $nguoiDung = null;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-gio-hang" content="<?= e($_SESSION['csrf_gio_hang']) ?>">
    <meta name="edugpa-uid" content="<?= e((string) ($ndHeader['id'] ?? '')) ?>">
    <meta name="yeu-thich-api" content="<?= e($goc) ?>api/yeu-thich.php">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="EduGPA hỗ trợ sinh viên tra cứu môn học, theo dõi trạng thái học tập và lưu môn yêu thích.">
    <title><?= e($tieuDe) ?> | EduGPA</title>
    <link rel="stylesheet" href="<?= e($goc) ?>css/style.css?v=<?= (int) $phienBanCss ?>">
    <?php if (!empty($cssTrang)): ?>
    <link rel="stylesheet" href="<?= e($cssTrang) ?>">
    <?php endif; ?>
</head>

<body class="trang<?= !empty($lopBody) ? ' ' . e($lopBody) : '' ?>">

    <!-- HEADER TRÊN CÙNG: Chứa Logo và Thông tin User (góc phải) -->
    <header class="dau-trang">
        <div class="bao dau-trang__noi-dung" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            
            <!-- Bên trái: Logo & Yêu thích -->
            <div class="dau-trang__trai">
                <div class="dau-trang__cum-thuong-hieu">
                    <a class="thuong-hieu" href="<?= e($goc) ?>index.php">EduGPA</a>
                    <a class="dau-trang__yeu-thich" href="<?= e($goc) ?>danh-sach.php">
                        <span aria-hidden="true">♥</span>
                        <span>Yêu thích:</span>
                        <span data-so-luong-yeu-thich aria-live="polite" aria-atomic="true">0</span>
                    </a>
                </div>
                <p class="dau-trang__mo-ta" style="margin-top: 5px;">
                    Hệ thống hỗ trợ tra cứu và theo dõi thông tin học tập cho sinh viên
                </p>
            </div>

            <!-- Bên phải: Tài khoản / Đăng nhập -->
            <div class="dau-trang__phai" style="display: flex; align-items: center; gap: 10px;">
                <?php if ($nguoiDung !== null): ?>
                    <span style="color: #fff; font-weight: bold; font-size: 1.1em; margin-right: 10px;">
                        Xin chào, <?= e($nguoiDung) ?>
                    </span>
                    <?php if (($ndHeader['vai_tro'] ?? null) === 'quan_tri'): ?>
                        <a href="<?= e($goc) ?>quan-tri.php" style="background: #6f42c1; color: white; padding: 8px 12px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 0.9em;">Quản trị</a>
                    <?php endif; ?>
                    <a href="<?= e($goc) ?>don-hang.php" style="background: #0d6efd; color: white; padding: 8px 12px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 0.9em;">Kế hoạch của tôi</a>
                    <form action="<?= e($goc) ?>dang-xuat.php" method="post">
                        <input type="hidden" name="_csrf" value="<?= e(csrfToken()) ?>">
                        <button type="submit" style="background: #dc3545; color: white; padding: 8px 12px; border: 0; border-radius: 5px; font-weight: bold; font-size: 0.9em; cursor: pointer;">Đăng xuất</button>
                    </form>
                <?php else: ?>
                    <a href="<?= e($goc) ?>dang-nhap.php" style="background: #198754; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: bold;">Đăng nhập</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- THANH ĐIỀU HƯỚNG: Chỉ chứa các trang chung -->
    <nav class="dieu-huong" aria-label="Điều hướng chính">
        <button class="nut-menu" type="button" aria-controls="menu-chinh" aria-expanded="false" aria-label="Mở menu chính">
            <span aria-hidden="true">☰</span>
            <span>Menu</span>
        </button>

        <ul id="menu-chinh" class="dieu-huong__danh-sach menu-chinh">
            <li class="dieu-huong__muc">
                <a class="dieu-huong__lien-ket <?= $trang === 'index' ? 'dieu-huong__lien-ket--hien-tai' : '' ?>" href="<?= e($goc) ?>index.php" <?= $trang === 'index' ? 'aria-current="page"' : '' ?>>Trang chủ</a>
            </li>
            <li class="dieu-huong__muc">
                <a class="dieu-huong__lien-ket <?= $trang === 'danh-sach' ? 'dieu-huong__lien-ket--hien-tai' : '' ?>" href="<?= e($goc) ?>danh-sach.php" <?= $trang === 'danh-sach' ? 'aria-current="page"' : '' ?>>Danh sách</a>
            </li>
            <li class="dieu-huong__muc">
                <a class="dieu-huong__lien-ket <?= $trang === 'gioi-thieu' ? 'dieu-huong__lien-ket--hien-tai' : '' ?>" href="<?= e($goc) ?>gioi-thieu.php" <?= $trang === 'gioi-thieu' ? 'aria-current="page"' : '' ?>>Giới thiệu</a>
            </li>
            <li class="dieu-huong__muc">
                <a class="dieu-huong__lien-ket <?= $trang === 'lien-he' ? 'dieu-huong__lien-ket--hien-tai' : '' ?>" href="<?= e($goc) ?>lien-he.php" <?= $trang === 'lien-he' ? 'aria-current="page"' : '' ?>>Liên hệ</a>
            </li>
            <li class="dieu-huong__muc">
                <a class="dieu-huong__lien-ket <?= $trang === 'gio-hang' ? 'dieu-huong__lien-ket--hien-tai' : '' ?>" href="<?= e($goc) ?>gio-hang.php" <?= $trang === 'gio-hang' ? 'aria-current="page"' : '' ?>>Giỏ hàng (<span id="cart-count" aria-live="polite" aria-atomic="true"><?= e((string) $soMonTrongGio) ?></span>)</a>
            </li>
        </ul>
    </nav>

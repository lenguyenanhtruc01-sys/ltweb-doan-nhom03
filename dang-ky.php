<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';

$tieuDe = 'Đăng ký';
$trang = 'dang-ky';
$goc = '';

$thongBaoLoi = '';
$tenDangNhap = '';
$email = '';
$hoTen = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tenDangNhapRaw = $_POST['ten_dang_nhap'] ?? '';
    $emailRaw = $_POST['email'] ?? '';
    $hoTenRaw = $_POST['ho_ten'] ?? '';
    $matKhauRaw = $_POST['mat_khau'] ?? '';
    $xacNhanRaw = $_POST['xac_nhan_mat_khau'] ?? '';

    $tenDangNhap = is_string($tenDangNhapRaw) ? trim($tenDangNhapRaw) : '';
    $email = is_string($emailRaw) ? trim($emailRaw) : '';
    $hoTen = is_string($hoTenRaw) ? trim($hoTenRaw) : '';
    $matKhau = is_string($matKhauRaw) ? $matKhauRaw : '';
    $xacNhan = is_string($xacNhanRaw) ? $xacNhanRaw : '';

    // ĐÃ SỬA: Thêm điều kiện mb_strlen($matKhau) >= 6
    $duLieuHopLe =
        mb_strlen($tenDangNhap) >= 3
        && mb_strlen($tenDangNhap) <= 50
        && filter_var($email, FILTER_VALIDATE_EMAIL)
        && mb_strlen($email) <= 254
        && mb_strlen($hoTen) >= 2
        && mb_strlen($hoTen) <= 100
        && mb_strlen($matKhau) >= 6 // Kiểm tra độ dài >= 6
        && $matKhau === $xacNhan;

    if (!$duLieuHopLe) {
        $thongBaoLoi = 'Thông tin không hợp lệ (Mật khẩu tối thiểu 6 ký tự) hoặc tên đăng nhập/email đã được sử dụng.';
    } else {
        try {
            $st = db()->prepare(
                "INSERT INTO nguoi_dung
                    (ten_dang_nhap, email, mat_khau, ho_ten, vai_tro)
                 VALUES (?, ?, ?, ?, 'khach_hang')"
            );

            $st->execute([
                $tenDangNhap,
                $email,
                password_hash($matKhau, PASSWORD_DEFAULT),
                $hoTen,
            ]);

            header('Location: dang-nhap.php?dang_ky=1');
            exit;
        } catch (PDOException $loi) {
            if ((int) ($loi->errorInfo[1] ?? 0) === 1062) {
                $thongBaoLoi = 'Tên đăng nhập hoặc email này đã được sử dụng.';
            } else {
                throw $loi;
            }
        }
    }
}

require __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh">
    <div class="bao" style="max-width: 500px; margin: 2rem auto;">
        <h1>Đăng ký tài khoản</h1>

        <?php if ($thongBaoLoi !== ''): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 1rem;">
                <?= e($thongBaoLoi) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="dang-ky.php" class="form-lien-he">
            <div>
                <label for="ho_ten">Họ tên:</label>
                <input
                    type="text"
                    id="ho_ten"
                    name="ho_ten"
                    maxlength="100"
                    value="<?= e($hoTen) ?>"
                    required>
            </div>

            <div>
                <label for="ten_dang_nhap">Tên đăng nhập:</label>
                <input
                    type="text"
                    id="ten_dang_nhap"
                    name="ten_dang_nhap"
                    maxlength="50"
                    value="<?= e($tenDangNhap) ?>"
                    required>
            </div>

            <div>
                <label for="email">Email:</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="254"
                    value="<?= e($email) ?>"
                    required>
            </div>

            <div>
                <!-- ĐÃ SỬA: Cập nhật label -->
                <label for="mat_khau">Mật khẩu (tối thiểu 6 ký tự):</label>
                <input
                    type="password"
                    id="mat_khau"
                    name="mat_khau"
                    minlength="6"
                    autocomplete="new-password"
                    required>
            </div>

            <div>
                <label for="xac_nhan_mat_khau">Nhập lại mật khẩu:</label>
                <input
                    type="password"
                    id="xac_nhan_mat_khau"
                    name="xac_nhan_mat_khau"
                    minlength="6"
                    autocomplete="new-password"
                    required>
            </div>

            <button type="submit" class="nut nut--chinh">Tạo tài khoản</button>
        </form>

        <p>Đã có tài khoản? <a href="dang-nhap.php">Đăng nhập</a></p>
    </div>
</main>

<?php require __DIR__ . '/inc/footer.php'; ?>
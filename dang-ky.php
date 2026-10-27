<?php
declare(strict_types=1);

/**
 * Đăng ký tài khoản: kiểm tra đầu vào và băm mật khẩu trước khi gọi lớp dữ liệu PDO.
 * Báo lỗi trùng email/tên từ mã 1062; thành công PRG 303 với thông báo session.
 * Thử form hợp lệ, mật khẩu dưới 8 ký tự, dữ liệu mảng và vai trò giả mạo.
 */
require_once __DIR__ . '/inc/config.php';

use App\Data\KhoNguoiDung;

$tieuDe = 'Đăng ký';
$trang = 'dang-ky';
$goc = '';

$loi = []; // Mảng chứa lỗi của từng ô
// Giữ lại dữ liệu người dùng đã nhập để không bị mất khi load lại trang
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

    // 1. Kiểm tra dữ liệu và gán lỗi cạnh từng ô
    if (mb_strlen($tenDangNhap) < 3 || mb_strlen($tenDangNhap) > 50) {
        $loi['ten_dang_nhap'] = 'Tên đăng nhập phải từ 3 đến 50 ký tự.';
    } elseif (str_contains($tenDangNhap, '@')) {
        $loi['ten_dang_nhap'] = 'Tên đăng nhập không được chứa ký tự @. Hãy nhập email ở ô Email.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
        $loi['email'] = 'Email không hợp lệ.';
    }

    if (mb_strlen($hoTen) < 2 || mb_strlen($hoTen) > 100) {
        $loi['ho_ten'] = 'Họ tên phải từ 2 đến 100 ký tự.';
    }

    if (mb_strlen($matKhau) < 8) {
        $loi['mat_khau'] = 'Mật khẩu phải tối thiểu 8 ký tự.';
    } elseif ($matKhau !== $xacNhan) {
        $loi['xac_nhan_mat_khau'] = 'Mật khẩu xác nhận không khớp.';
    }

    // 2. Giao thao tác ghi CSDL cho lớp dữ liệu khi đầu vào đã hợp lệ.
    if (empty($loi)) {
        try {
            (new KhoNguoiDung(db()))->dangKy(
                $tenDangNhap,
                $email,
                password_hash($matKhau, PASSWORD_DEFAULT),
                $hoTen,
            );

            $_SESSION['flash_dang_ky'] = 'Đăng ký thành công. Hãy đăng nhập bằng tài khoản vừa tạo.';
            header('Location: dang-nhap.php', true, 303);
            exit;
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                // Bắt lỗi trùng lặp, kiểm tra xem trùng email hay tên đăng nhập
                if (str_contains($e->getMessage(), 'email')) {
                    $loi['email'] = 'Email này đã được đăng ký.';
                } elseif (str_contains($e->getMessage(), 'ten_dang_nhap')) {
                    $loi['ten_dang_nhap'] = 'Tên đăng nhập này đã có người sử dụng.';
                } else {
                    $loi['chung'] = 'Thông tin đăng ký đã tồn tại.';
                }
            } else {
                throw $e;
            }
        }
    }
}

require __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh">
    <div class="bao" style="max-width: 500px; margin: 2rem auto;">
        <h1>Đăng ký tài khoản</h1>

        <?php if (isset($loi['chung'])): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 1rem;">
                <?= e($loi['chung']) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="dang-ky.php" class="form-lien-he">
            <div>
                <label for="ho_ten">Họ tên:</label>
                <input type="text" id="ho_ten" name="ho_ten" maxlength="100" value="<?= e($hoTen) ?>" required
                    <?= isset($loi['ho_ten']) ? 'aria-invalid="true" aria-describedby="loi-ho-ten"' : '' ?>>
                <?php if (isset($loi['ho_ten'])): ?>
                    <span id="loi-ho-ten" role="alert" style="color: red; font-size: 0.9em;"><?= e($loi['ho_ten']) ?></span>
                <?php endif; ?>
            </div>

            <div>
                <label for="ten_dang_nhap">Tên đăng nhập:</label>
                <input type="text" id="ten_dang_nhap" name="ten_dang_nhap" maxlength="50" value="<?= e($tenDangNhap) ?>" required
                    <?= isset($loi['ten_dang_nhap']) ? 'aria-invalid="true" aria-describedby="loi-ten-dang-nhap"' : '' ?>>
                <?php if (isset($loi['ten_dang_nhap'])): ?>
                    <span id="loi-ten-dang-nhap" role="alert" style="color: red; font-size: 0.9em;"><?= e($loi['ten_dang_nhap']) ?></span>
                <?php endif; ?>
            </div>

            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" maxlength="100" value="<?= e($email) ?>" required
                    <?= isset($loi['email']) ? 'aria-invalid="true" aria-describedby="loi-email"' : '' ?>>
                <?php if (isset($loi['email'])): ?>
                    <span id="loi-email" role="alert" style="color: red; font-size: 0.9em;"><?= e($loi['email']) ?></span>
                <?php endif; ?>
            </div>

            <div>
                <label for="mat_khau">Mật khẩu (tối thiểu 8 ký tự):</label>
                <input type="password" id="mat_khau" name="mat_khau" minlength="8" autocomplete="new-password" required
                    <?= isset($loi['mat_khau']) ? 'aria-invalid="true" aria-describedby="loi-mat-khau"' : '' ?>>
                <?php if (isset($loi['mat_khau'])): ?>
                    <span id="loi-mat-khau" role="alert" style="color: red; font-size: 0.9em;"><?= e($loi['mat_khau']) ?></span>
                <?php endif; ?>
            </div>

            <div>
                <label for="xac_nhan_mat_khau">Nhập lại mật khẩu:</label>
                <input type="password" id="xac_nhan_mat_khau" name="xac_nhan_mat_khau" minlength="8" autocomplete="new-password" required
                    <?= isset($loi['xac_nhan_mat_khau']) ? 'aria-invalid="true" aria-describedby="loi-xac-nhan-mat-khau"' : '' ?>>
                <?php if (isset($loi['xac_nhan_mat_khau'])): ?>
                    <span id="loi-xac-nhan-mat-khau" role="alert" style="color: red; font-size: 0.9em;"><?= e($loi['xac_nhan_mat_khau']) ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="nut nut--chinh">Tạo tài khoản</button>
        </form>

        <p>Đã có tài khoản? <a href="dang-nhap.php">Đăng nhập</a></p>
    </div>
</main>

<?php require __DIR__ . '/inc/footer.php'; ?>

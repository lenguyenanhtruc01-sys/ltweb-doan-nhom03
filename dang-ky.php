<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';

$thongBao = '';
$emailNhap = '';
$hoTenNhap = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $emailRaw = $_POST['email'] ?? '';
    $hoTenRaw = $_POST['ho_ten'] ?? '';
    $matKhauRaw = $_POST['mat_khau'] ?? '';
    $emailNhap = is_string($emailRaw) ? trim($emailRaw) : '';
    $hoTenNhap = is_string($hoTenRaw) ? trim($hoTenRaw) : '';
    $matKhau = is_string($matKhauRaw) ? $matKhauRaw : '';

    if (!filter_var($emailNhap, FILTER_VALIDATE_EMAIL) || strlen($emailNhap) > 100) {
        $thongBao = 'Vui lòng nhập email hợp lệ';
    } elseif ($hoTenNhap === '' || mb_strlen($hoTenNhap, 'UTF-8') > 100 || $matKhau === '') {
        $thongBao = 'Vui lòng nhập họ tên và mật khẩu hợp lệ';
    } else {
        try {
            $truyVan = db()->prepare('INSERT INTO nguoi_dung (email, ten_dang_nhap, mat_khau, ho_ten, vai_tro) VALUES (:email, :ten_dang_nhap, :mat_khau, :ho_ten, :vai_tro)');
            $truyVan->execute([
                'email' => $emailNhap,
                'ten_dang_nhap' => $emailNhap,
                'mat_khau' => password_hash($matKhau, PASSWORD_DEFAULT),
                'ho_ten' => $hoTenNhap,
                'vai_tro' => 'khach_hang',
            ]);
            $_SESSION['flash_dang_ky'] = 'Đăng ký thành công. Vui lòng đăng nhập.';
            header('Location: dang-nhap.php', true, 303);
            exit;
        } catch (PDOException $loi) {
            $maLoi = $loi->errorInfo[1] ?? null;
            if ($maLoi === 1062) {
                $thongBao = 'Email đã được đăng ký';
            } else {
                error_log((string) $loi);
                $thongBao = 'Không thể tạo tài khoản lúc này. Vui lòng thử lại.';
            }
        }
    }
}

$tieuDe = 'Đăng ký';
$trang = 'dang-ky';
$goc = '';
require __DIR__ . '/inc/header.php';
?>
<main class="noi-dung-chinh">
    <div class="bao" style="max-width: 500px; margin: 2rem auto;">
        <h1>Đăng ký tài khoản</h1>
        <?php if ($thongBao !== ''): ?>
            <p role="alert"><?= e($thongBao) ?></p>
        <?php endif; ?>
        <form method="post" action="dang-ky.php" class="form-lien-he">
            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" maxlength="100" autocomplete="email" required value="<?= e($emailNhap) ?>">
            </div>
            <div>
                <label for="ho-ten">Họ tên</label>
                <input id="ho-ten" name="ho_ten" type="text" maxlength="100" autocomplete="name" required value="<?= e($hoTenNhap) ?>">
            </div>
            <div>
                <label for="mat-khau">Mật khẩu</label>
                <input id="mat-khau" name="mat_khau" type="password" autocomplete="new-password" required>
            </div>
            <button type="submit" class="nut nut--chinh">Đăng ký</button>
        </form>
        <p><a href="dang-nhap.php">Đã có tài khoản? Đăng nhập</a></p>
    </div>
</main>
<?php require __DIR__ . '/inc/footer.php'; ?>

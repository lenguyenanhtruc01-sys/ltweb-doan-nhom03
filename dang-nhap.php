<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';

$thongBao = '';
$emailNhap = '';
$flashDangKy = $_SESSION['flash_dang_ky'] ?? '';
unset($_SESSION['flash_dang_ky']);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $emailRaw = $_POST['email'] ?? '';
    $matKhauRaw = $_POST['mat_khau'] ?? '';
    $emailNhap = is_string($emailRaw) ? trim($emailRaw) : '';
    $matKhau = is_string($matKhauRaw) ? $matKhauRaw : '';

    try {
        $truyVan = db()->prepare('SELECT id, mat_khau FROM nguoi_dung WHERE email = :email LIMIT 1');
        $truyVan->execute(['email' => $emailNhap]);
        $nguoiDung = $truyVan->fetch();

        if (is_array($nguoiDung) && password_verify($matKhau, $nguoiDung['mat_khau'])) {
            session_regenerate_id(true);
            $_SESSION = ['uid' => (int) $nguoiDung['id']];
            header('Location: index.php', true, 303);
            exit;
        }
    } catch (PDOException $loi) {
        error_log((string) $loi);
    }

    $thongBao = 'Email hoặc mật khẩu không đúng';
}

$tieuDe = 'Đăng nhập';
$trang = 'dang-nhap';
$goc = '';
require __DIR__ . '/inc/header.php';
?>
<main class="noi-dung-chinh">
    <div class="bao" style="max-width: 500px; margin: 2rem auto;">
        <h1>Đăng nhập</h1>
        <?php if ($thongBao !== ''): ?>
            <p role="alert"><?= e($thongBao) ?></p>
        <?php endif; ?>
        <?php if (is_string($flashDangKy) && $flashDangKy !== ''): ?>
            <p role="status"><?= e($flashDangKy) ?></p>
        <?php endif; ?>
        <form method="post" action="dang-nhap.php" class="form-lien-he">
            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" maxlength="100" autocomplete="email" required value="<?= e($emailNhap) ?>">
            </div>
            <div>
                <label for="mat-khau">Mật khẩu</label>
                <input id="mat-khau" name="mat_khau" type="password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="nut nut--chinh">Đăng nhập</button>
        </form>
        <p><a href="dang-ky.php">Tạo tài khoản</a></p>
    </div>
</main>
<?php require __DIR__ . '/inc/footer.php'; ?>

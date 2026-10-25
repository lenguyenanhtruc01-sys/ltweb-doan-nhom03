<?php
/**
 * Tệp: dang-nhap.php
 * Chức năng: Xác thực người dùng từ MySQL (Slide 38 - 41)
 */
declare(strict_types=1);
require_once __DIR__ . '/inc/config.php';

// Nếu đã đăng nhập thì chuyển hướng về trang chủ
$nguoiDungHienTai = nguoiDung();

if ($nguoiDungHienTai !== null) {
    $trangDich = $nguoiDungHienTai['vai_tro'] === 'quan_tri'
        ? 'quan-tri.php'
        : 'index.php';

    header('Location: ' . $trangDich);
    exit;
}

$thongBaoLoi = '';
$thongBaoThanhCong = (($_GET['dang_ky'] ?? '') === '1')
    ? 'Đăng ký thành công. Hãy đăng nhập.'
    : '';

// Cấu hình trang
$tieuDe = 'Đăng nhập';
$trang = 'dang-nhap';
$goc = '';

if (($_GET['dat_lai'] ?? '') === '1') {
    $thongBaoThanhCong = 'Đã đổi mật khẩu. Hãy đăng nhập với mật khẩu mới.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $thongBaoLoi = 'Email hoặc mật khẩu không chính xác.';

    $emailRaw = $_POST['email'] ?? '';
    $passwordRaw = $_POST['password'] ?? '';
    $ghiNhoRaw = $_POST['ghi_nho'] ?? '';

    $email = is_string($emailRaw) ? trim($emailRaw) : '';
    $password = is_string($passwordRaw) ? $passwordRaw : '';
    $ghiNho = $ghiNhoRaw === '1';

    if (filter_var($email, FILTER_VALIDATE_EMAIL) && $password !== '') {
        $st = db()->prepare(
            'SELECT id, mat_khau, vai_tro
            FROM nguoi_dung
             WHERE email = ?'
        );
        $st->execute([$email]);
        $nd = $st->fetch();

        if ($nd && password_verify($password, $nd['mat_khau'])) {
            if (password_needs_rehash($nd['mat_khau'], PASSWORD_DEFAULT)) {
                $hashMoi = password_hash($password, PASSWORD_DEFAULT);

                $capNhat = db()->prepare(
                    'UPDATE nguoi_dung
                     SET mat_khau = ?
                     WHERE id = ?'
                );
                $capNhat->execute([$hashMoi, (int) $nd['id']]);
            }

            session_regenerate_id(true);
            $_SESSION['uid'] = (int) $nd['id'];

            if ($ghiNho) {
                taoPhienGhiNho((int) $nd['id']);
            } else {
                xoaGhiNhoHienTai();
            }

            $trangDich = $nd['vai_tro'] === 'quan_tri'
                ? 'quan-tri.php'
                : 'index.php';

            header('Location: ' . $trangDich);
            exit;
        }
    }
}

require __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh">
    <div class="bao" style="max-width: 500px; margin: 2rem auto;">
        <h1>Đăng nhập</h1>
        <?php if ($thongBaoThanhCong !== ''): ?>
    <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 1rem;">
        <?= e($thongBaoThanhCong) ?>
    </div>
    <?php endif; ?>
        <?php if (!empty($thongBaoLoi)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 1rem;">
                <?= e($thongBaoLoi) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="dang-nhap.php" class="form-lien-he" style="background: #f8f9fa; padding: 2rem; border-radius: 8px;">
            <div style="margin-bottom: 1rem;">
                <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Email:</label>
                <input type="email" id="email" name="email" required style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;" placeholder="Nhập email...">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="password" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Mật khẩu:</label>
                <input type="password" id="password" name="password" required style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;" placeholder="Mật khẩu mặc định: 123456">
            </div>

            <button type="submit" class="nut nut--chinh" style="width: 100%; padding: 0.75rem; cursor: pointer;">Đăng nhập</button>
            <p>Chưa có tài khoản? <a href="dang-ky.php">Đăng ký ngay</a></p>
            <div style="margin-top: 1rem;">
                <label>
                    <input type="checkbox" name="ghi_nho" value="1">
                    Ghi nhớ đăng nhập trong 30 ngày
                </label>
            </div>

            <p><a href="quen-mat-khau.php">Quên mật khẩu?</a></p>
        </form>
    </div>
</main>

<?php require __DIR__ . '/inc/footer.php'; ?>

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $thongBaoLoi = 'Tên đăng nhập/email hoặc mật khẩu không chính xác.';

    $emailRaw = $_POST['email'] ?? '';
    $passwordRaw = $_POST['password'] ?? '';

    $email = is_string($emailRaw) ? trim($emailRaw) : '';
    $password = is_string($passwordRaw) ? $passwordRaw : '';

    if ($email !== '' && mb_strlen($email) <= 254 && $password !== '') {
        $st = db()->prepare(
            'SELECT id, mat_khau, vai_tro
            FROM nguoi_dung
             WHERE email = ? OR ten_dang_nhap = ?
             LIMIT 2'
        );
        $st->execute([$email, $email]);
        $ketQua = $st->fetchAll();
        // Không chọn tùy ý nếu tên của một tài khoản trùng email tài khoản khác.
        $nd = count($ketQua) === 1 ? $ketQua[0] : null;

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
                <label for="email" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Tên đăng nhập hoặc email:</label>
                <input type="text" id="email" name="email" autocomplete="username" maxlength="254" required style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;" placeholder="Nhập tên đăng nhập hoặc email...">
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="password" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Mật khẩu:</label>
                <input type="password" id="password" name="password" required style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;" placeholder="Mật khẩu mặc định: 123456">
            </div>

            <button type="submit" class="nut nut--chinh" style="width: 100%; padding: 0.75rem; cursor: pointer;">Đăng nhập</button>
            <p>Chưa có tài khoản? <a href="dang-ky.php">Đăng ký ngay</a></p>
        </form>
    </div>
</main>

<?php require __DIR__ . '/inc/footer.php'; ?>

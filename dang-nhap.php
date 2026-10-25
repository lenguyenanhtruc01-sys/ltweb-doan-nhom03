<?php
declare(strict_types=1);

/**
 * Dang nhap bang ten hoac email, chi mot luong POST va tao lai ma phien.
 * Khoi phuc gio MySQL, gop gio khach, khong xoa du lieu khi dang nhap.
 * Thu: dang xuat/dang nhap lai, doi tai khoan va dau vao dang mang.
 */
require_once __DIR__ . '/inc/config.php';

$thongBao = '';
$emailNhap = '';
$flashDangKy = $_SESSION['flash_dang_ky'] ?? '';
unset($_SESSION['flash_dang_ky']);
if (($_GET['dang_ky'] ?? '') === '1') $flashDangKy = 'Đăng ký thành công. Hãy đăng nhập.';
if (($_GET['dat_lai'] ?? '') === '1') $flashDangKy = 'Đã đổi mật khẩu. Hãy đăng nhập với mật khẩu mới.';

$tieuDe = 'Đăng nhập';
$trang = 'dang-nhap';
$goc = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $emailRaw = $_POST['email'] ?? '';
    $matKhauRaw = $_POST['mat_khau'] ?? $_POST['password'] ?? '';
    $emailNhap = is_string($emailRaw) ? trim($emailRaw) : '';
    $matKhau = is_string($matKhauRaw) ? $matKhauRaw : '';
    $ghiNho = ($_POST['ghi_nho'] ?? '') === '1';
    $thongBao = 'Tên đăng nhập/email hoặc mật khẩu không chính xác.';
    if ($emailNhap !== '' && mb_strlen($emailNhap) <= 254 && $matKhau !== '') {
        $st = db()->prepare('SELECT id, mat_khau, vai_tro FROM nguoi_dung WHERE email = ? OR ten_dang_nhap = ? LIMIT 2');
        $st->execute([$emailNhap, $emailNhap]);
        $ketQua = $st->fetchAll();
        $nd = count($ketQua) === 1 ? $ketQua[0] : null;
        if ($nd !== null && password_verify($matKhau, $nd['mat_khau'])) {
            if (password_needs_rehash($nd['mat_khau'], PASSWORD_DEFAULT)) {
                $capNhat = db()->prepare('UPDATE nguoi_dung SET mat_khau = ? WHERE id = ?');
                $capNhat->execute([password_hash($matKhau, PASSWORD_DEFAULT), (int) $nd['id']]);
            }
            $uidCu = $_SESSION['uid'] ?? null;
            if (is_int($uidCu) && $uidCu !== (int) $nd['id']) {
                $_SESSION['ke_hoach_dang_ky'] = [];
                unset($_SESSION['chu_gio_hang']);
            }
            $_SESSION['uid'] = (int) $nd['id'];
            new \App\Services\KeHoachHocTap();
            session_regenerate_id(true);
            unset($_SESSION['csrf_gio_hang'], $_SESSION['_csrf_token']);
            xoaGhiNhoHienTai();
            if ($ghiNho) taoPhienGhiNho((int) $nd['id']);
            header('Location: ' . ($nd['vai_tro'] === 'quan_tri' ? 'quan-tri.php' : 'index.php'), true, 303);
            exit;
        }
    }
}

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
                <label for="email">Tên đăng nhập hoặc email</label>
                <input id="email" name="email" type="text" maxlength="254" autocomplete="username" required value="<?= e($emailNhap) ?>">
            </div>
            <div>
                <label for="mat-khau">Mật khẩu</label>
                <input id="mat-khau" name="mat_khau" type="password" autocomplete="current-password" required>
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
        <p><a href="dang-ky.php">Tạo tài khoản</a></p>
    </div>
</main>
<?php require __DIR__ . '/inc/footer.php'; ?>

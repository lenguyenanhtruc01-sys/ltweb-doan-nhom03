<?php
declare(strict_types=1);


/**
 * Kiểm tra mã đặt lại mật khẩu và lưu mật khẩu mới dưới dạng băm.
 * Mã chỉ dùng một lần, có hạn dùng; lỗi đầu vào được hiển thị tại biểu mẫu.
 * Thử mã đúng, mã sai/hết hạn và gửi lại mã đã sử dụng trên CSDL kiểm thử.
 */
require_once __DIR__ . '/inc/config.php';

use App\Data\KhoNguoiDung;

$tieuDe = 'Đặt lại mật khẩu';
$trang = '';
$goc = '';

$thongBaoLoi = '';
$tokenRaw = $_POST['token'] ?? $_GET['token'] ?? '';
$token = is_string($tokenRaw) ? $tokenRaw : '';

$khoNguoiDung = new KhoNguoiDung(db());
$yeuCau = $khoNguoiDung->timYeuCauDatLai($token);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matKhauRaw = $_POST['mat_khau'] ?? '';
    $xacNhanRaw = $_POST['xac_nhan_mat_khau'] ?? '';
    $matKhau = is_string($matKhauRaw) ? $matKhauRaw : '';
    $xacNhan = is_string($xacNhanRaw) ? $xacNhanRaw : '';

    if ($yeuCau === null) {
        $thongBaoLoi = 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.';
    } elseif (mb_strlen($matKhau) < 6 || $matKhau !== $xacNhan) {
        $thongBaoLoi = 'Mật khẩu phải có ít nhất 6 ký tự và hai lần nhập phải giống nhau.';
    } else {
        if ($khoNguoiDung->datLaiMatKhau($token, password_hash($matKhau, PASSWORD_DEFAULT))) {
            xoaCookieGhiNho();
            $_SESSION = [];
            session_regenerate_id(true);
            header('Location: dang-nhap.php?dat_lai=1', true, 303);
            exit;
        }
        $yeuCau = null;
        $thongBaoLoi = 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.';
    }
}

require __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh">
    <div class="bao" style="max-width: 500px; margin: 2rem auto;">
        <h1>Đặt lại mật khẩu</h1>

        <?php if ($thongBaoLoi !== ''): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 1rem;">
                <?= e($thongBaoLoi) ?>
            </div>
        <?php endif; ?>

        <?php if ($yeuCau !== null): ?>
            <form method="POST" action="dat-lai-mat-khau.php" class="form-lien-he">
                <input type="hidden" name="token" value="<?= e($token) ?>">

                <div>
                    <label for="mat_khau">Mật khẩu mới (ít nhất 6 ký tự):</label>
                    <input
                        type="password"
                        id="mat_khau"
                        name="mat_khau"
                        minlength="6"
                        autocomplete="new-password"
                        required>
                </div>

                <div>
                    <label for="xac_nhan_mat_khau">Nhập lại mật khẩu mới:</label>
                    <input
                        type="password"
                        id="xac_nhan_mat_khau"
                        name="xac_nhan_mat_khau"
                        minlength="6"
                        autocomplete="new-password"
                        required>
                </div>

                <button type="submit" class="nut nut--chinh">Đổi mật khẩu</button>
            </form>
        <?php else: ?>
            <p><a href="quen-mat-khau.php">Yêu cầu một link mới</a></p>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/inc/footer.php'; ?>

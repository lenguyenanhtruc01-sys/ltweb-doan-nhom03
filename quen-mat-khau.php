<?php
declare(strict_types=1);


/**
 * Nhận email, tạo mã có thời hạn qua KhoNguoiDung; POST thành công dùng PRG 303.
 * Phản hồi chung; link demo ở dev được chuyển qua session và chỉ hiện một lần.
 * Thử email có/không có tài khoản và email dạng mảng trên CSDL kiểm thử.
 */
require_once __DIR__ . '/inc/config.php';

use App\Data\KhoNguoiDung;

$tieuDe = 'Quên mật khẩu';
$trang = '';
$goc = '';

$flash = $_SESSION['flash_quen_mat_khau'] ?? [];
unset($_SESSION['flash_quen_mat_khau']);
if (!is_array($flash)) $flash = [];
$thongBao = is_string($flash['thong_bao'] ?? null) ? $flash['thong_bao'] : '';
$email = is_string($flash['email'] ?? null) ? $flash['email'] : '';
$lienKetDatLai = is_string($flash['lien_ket'] ?? null) ? $flash['lien_ket'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailRaw = $_POST['email'] ?? '';
    $email = is_string($emailRaw) ? trim($emailRaw) : '';

    // Luôn trả cùng một thông báo để không tiết lộ email nào đang có tài khoản.
    $thongBao = 'Nếu email tồn tại, hệ thống đã tạo hướng dẫn đặt lại mật khẩu.';

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $token = bin2hex(random_bytes(32));
        if ((new KhoNguoiDung(db()))->taoYeuCauDatLai($email, hash('sha256', $token))) {
            $duongDan = 'dat-lai-mat-khau.php?token=' . urlencode($token);
            // Không ghi URL hoặc mã đặt lại mật khẩu vào log.

            // Chưa cấu hình SMTP; ở môi trường dev hiển thị link để demo luồng.
            if (MOI_TRUONG === 'dev') {
                $lienKetDatLai = $duongDan;
            }
        }
    }
    $_SESSION['flash_quen_mat_khau'] = [
        'thong_bao' => $thongBao, 'email' => $email, 'lien_ket' => $lienKetDatLai,
    ];
    header('Location: quen-mat-khau.php', true, 303);
    exit;
}

require __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh">
    <div class="bao bao--hep">
        <h1>Quên mật khẩu</h1>
        <p>Nhập email tài khoản để nhận hướng dẫn đặt lại mật khẩu.</p>

        <?php if ($thongBao !== ''): ?>
            <div class="khoi-thong-bao khoi-thong-bao--thanh-cong">
                <?= e($thongBao) ?>
            </div>
        <?php endif; ?>

        <?php if ($lienKetDatLai !== ''): ?>
            <p><strong>Link demo (có hiệu lực 30 phút):</strong></p>
            <p><a href="<?= e($lienKetDatLai) ?>">Đặt lại mật khẩu</a></p>
        <?php endif; ?>

        <form method="POST" action="quen-mat-khau.php" class="form-lien-he">
            <?= csrfField() ?>
            <div>
                <label for="email">Email:</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= e($email) ?>"
                    autocomplete="email"
                    required>
            </div>

            <button type="submit" class="nut nut--chinh">Gửi hướng dẫn</button>
        </form>

        <p><a href="dang-nhap.php">Quay về đăng nhập</a></p>
    </div>
</main>

<?php require __DIR__ . '/inc/footer.php'; ?>

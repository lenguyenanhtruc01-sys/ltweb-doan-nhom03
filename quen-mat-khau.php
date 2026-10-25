<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';

$tieuDe = 'Quên mật khẩu';
$trang = '';
$goc = '';

$thongBao = '';
$email = '';
$lienKetDatLai = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailRaw = $_POST['email'] ?? '';
    $email = is_string($emailRaw) ? trim($emailRaw) : '';

    // Luôn trả cùng một thông báo để không tiết lộ email nào đang có tài khoản.
    $thongBao = 'Nếu email tồn tại, hệ thống đã tạo hướng dẫn đặt lại mật khẩu.';

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $timNguoiDung = db()->prepare('SELECT id FROM nguoi_dung WHERE email = ?');
        $timNguoiDung->execute([$email]);
        $nguoiDung = $timNguoiDung->fetch();

        if ($nguoiDung) {
            $uid = (int) $nguoiDung['id'];
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            // Mỗi user chỉ có một yêu cầu còn hiệu lực.
            $huyYeuCauCu = db()->prepare(
                'UPDATE yeu_cau_dat_lai_mat_khau
                 SET da_dung_luc = NOW()
                 WHERE nguoi_dung_id = ? AND da_dung_luc IS NULL'
            );
            $huyYeuCauCu->execute([$uid]);

            $taoYeuCau = db()->prepare(
                'INSERT INTO yeu_cau_dat_lai_mat_khau
                    (nguoi_dung_id, token_hash, het_han)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))'
            );
            $taoYeuCau->execute([$uid, $tokenHash]);

            $duongDan = 'dat-lai-mat-khau.php?token=' . urlencode($token);
            error_log('Yêu cầu đặt lại mật khẩu: ' . $duongDan);

            // Chưa cấu hình SMTP; ở môi trường dev hiển thị link để demo luồng.
            if (MOI_TRUONG === 'dev') {
                $lienKetDatLai = $duongDan;
            }
        }
    }
}

require __DIR__ . '/inc/header.php';
?>

<main class="noi-dung-chinh">
    <div class="bao" style="max-width: 500px; margin: 2rem auto;">
        <h1>Quên mật khẩu</h1>
        <p>Nhập email tài khoản để nhận hướng dẫn đặt lại mật khẩu.</p>

        <?php if ($thongBao !== ''): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 1rem;">
                <?= e($thongBao) ?>
            </div>
        <?php endif; ?>

        <?php if ($lienKetDatLai !== ''): ?>
            <p><strong>Link demo (có hiệu lực 30 phút):</strong></p>
            <p><a href="<?= e($lienKetDatLai) ?>">Đặt lại mật khẩu</a></p>
        <?php endif; ?>

        <form method="POST" action="quen-mat-khau.php" class="form-lien-he">
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

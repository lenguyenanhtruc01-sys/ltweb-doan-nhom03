<?php
require_once __DIR__ . '/inc/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu đã đăng nhập rồi thì chuyển hướng thẳng vào trang quản trị
if (isset($_SESSION['da_dang_nhap']) && $_SESSION['da_dang_nhap'] === true) {
    header('Location: quan-tri.php');
    exit;
}

$thongBaoLoi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $danhSachTaiKhoan = require __DIR__ . '/inc/tai-khoan.php';

    if (isset($danhSachTaiKhoan[$username]) && password_verify($password, $danhSachTaiKhoan[$username]['password'])) {
        // ĐĂNG NHẬP THÀNH CÔNG
        // Cấp mã phiên mới (chống tấn công Session Fixation)
        session_regenerate_id(true);

        $_SESSION['da_dang_nhap'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['ho_ten'] = $danhSachTaiKhoan[$username]['ho_ten'];

        header('Location: quan-tri.php');
        exit;
    } else {
        // ĐĂNG NHẬP THẤT BẠI -> Báo lỗi chung và ghi log chuẩn đề vào logs/php-error.log
        $thongBaoLoi = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $noiDungLog = "Đăng nhập thất bại với tài khoản: '{$username}' từ IP: {$ip}";
        
        error_log($noiDungLog);
    }
}

require __DIR__ . '/inc/header.php';
?>

    <main class="noi-dung-chinh">
        <div class="bao" style="max-width: 500px; margin: 2rem auto;">
            <h1>Đăng nhập hệ thống quản trị</h1>
            
            <?php if (!empty($thongBaoLoi)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 1rem;">
                    <?= htmlspecialchars($thongBaoLoi, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="dang-nhap.php" class="form-lien-he" style="background: #f8f9fa; padding: 2rem; border-radius: 8px;">
                <div style="margin-bottom: 1rem;">
                    <label for="username" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Tên đăng nhập:</label>
                    <input type="text" id="username" name="username" required style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;" placeholder="Nhập admin...">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="password" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Mật khẩu:</label>
                    <input type="password" id="password" name="password" required style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;" placeholder="Mật khẩu mặc định: 123456">
                </div>

                <button type="submit" class="nut nut--chinh" style="width: 100%; padding: 0.75rem; cursor: pointer;">Đăng nhập</button>
            </form>
        </div>
    </main>

<?php
require __DIR__ . '/inc/footer.php';
?>
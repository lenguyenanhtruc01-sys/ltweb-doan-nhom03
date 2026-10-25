<?php
/**
 * Tệp: dang-nhap.php
 * Chức năng: xác thực tài khoản quản trị và thiết lập phiên đăng nhập.
 */

require_once __DIR__ . '/inc/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nếu đã đăng nhập rồi thì chuyển hướng thẳng vào trang quản trị
if (($taiKhoanHienTai = nguoiDung()) !== null) {
    header('Location: ' . ($taiKhoanHienTai['vai_tro'] === 'quan_tri'
        ? 'quan-tri.php' : 'thanhvien/3120224064_hop/gioithieu.php#binh-chon'));
    exit;
}

$thongBaoLoi = '';

// Dữ liệu cho thanh điều hướng dùng chung.
$tieuDe = 'Đăng nhập';
$trang = 'dang-nhap';
$goc = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usernameRaw = $_POST['username'] ?? '';
    $passwordRaw = $_POST['password'] ?? '';

    // Chỉ chấp nhận dữ liệu chuỗi; username[]=admin là mảng và không được
    // phép đi vào trim() hoặc password_verify().
    $username = is_string($usernameRaw) ? trim($usernameRaw) : '';
    $password = is_string($passwordRaw) ? $passwordRaw : '';

    $stmt = db()->prepare('SELECT id, mat_khau, vai_tro FROM nguoi_dung WHERE ten_dang_nhap = ?');
    $stmt->execute([$username]);
    $taiKhoan = $stmt->fetch();

    if (is_array($taiKhoan) && password_verify($password, $taiKhoan['mat_khau'])) {
        // ĐĂNG NHẬP THÀNH CÔNG
        // Cấp mã phiên mới (chống tấn công Session Fixation)
        session_regenerate_id(true);

        unset($_SESSION['da_dang_nhap'], $_SESSION['username'], $_SESSION['ho_ten'], $_SESSION['user']);
        $_SESSION['uid'] = (int) $taiKhoan['id'];
        if (password_needs_rehash($taiKhoan['mat_khau'], PASSWORD_DEFAULT)) {
            $rehash = db()->prepare('UPDATE nguoi_dung SET mat_khau = ? WHERE id = ?');
            $rehash->execute([password_hash($password, PASSWORD_DEFAULT), $taiKhoan['id']]);
        }

        header('Location: ' . ($taiKhoan['vai_tro'] === 'quan_tri'
            ? 'quan-tri.php' : 'thanhvien/3120224064_hop/gioithieu.php#binh-chon'), true, 303);
        exit;
    } else {
        // ĐĂNG NHẬP THẤT BẠI -> Báo lỗi chung và ghi log chuẩn đề vào logs/php-error.log
        $thongBaoLoi = 'Tên đăng nhập hoặc mật khẩu không chính xác.';
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        $tenDangNhapLog = json_encode(mb_substr($username, 0, 100), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        $noiDungLog = "Đăng nhập thất bại với tài khoản: '{$tenDangNhapLog}' từ IP: {$ip}";
        
        error_log($noiDungLog);
    }
}

require __DIR__ . '/inc/header.php';
?>

    <main class="noi-dung-chinh">
        <div class="bao" style="max-width: 500px; margin: 2rem auto;">
            <h1>Đăng nhập EduGPA</h1>
            
            <?php if (!empty($thongBaoLoi)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 1rem;">
                    <?= e($thongBaoLoi) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="dang-nhap.php" class="form-lien-he" style="background: #f8f9fa; padding: 2rem; border-radius: 8px;">
                <div style="margin-bottom: 1rem;">
                    <label for="username" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Tên đăng nhập:</label>
                    <input type="text" id="username" name="username" autocomplete="username" required style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;" placeholder="Nhập tên đăng nhập">
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <label for="password" style="display: block; margin-bottom: 0.5rem; font-weight: bold;">Mật khẩu:</label>
                    <input type="password" id="password" name="password" autocomplete="current-password" required style="width: 100%; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px;" placeholder="Nhập mật khẩu">
                </div>

                <button type="submit" class="nut nut--chinh" style="width: 100%; padding: 0.75rem; cursor: pointer;">Đăng nhập</button>
            </form>
        </div>
    </main>

<?php
require __DIR__ . '/inc/footer.php';
?>

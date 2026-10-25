<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/config.php';

use App\Data\KhoKeHoach;
use App\Data\MonKhongTonTaiException;

if (!function_exists('csrfKiemTra')) {
    function csrfKiemTra(?string $token): bool {
        return is_string($token) && $token !== '' && isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
    }
}

// 1. Kiểm tra đăng nhập
$nd = yeuCauDangNhap();

// 2. Kiểm tra phương thức POST và CSRF (Gộp làm 1 lần cho gọn)
$token = $_POST['_csrf'] ?? null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !csrfKiemTra($token)) {
    $_SESSION['flash_error'] = 'Phiên làm việc đã hết hạn hoặc sai phương thức.';
    header('Location: gio-hang.php', true, 303); 
    exit;
}

// 3. Đồng bộ lại giỏ hàng từ session
$gioHienTai = new \App\Services\KeHoachHocTap();
$gioHienTai->layDanhSachChiTiet();
$gioSession = $_SESSION['ke_hoach_dang_ky'] ?? [];

if (!is_array($gioSession) || $gioSession === []) {
    $_SESSION['flash_error'] = 'Kế hoạch đang trống, không thể gửi.';
    header('Location: gio-hang.php', true, 303);
    exit;
}

$ghiChuRaw = $_POST['ghi_chu'] ?? '';
$ghiChu = is_string($ghiChuRaw) ? trim($ghiChuRaw) : '';

try {
    $kho = new KhoKeHoach(db());
    
    // FIX LỖI TẠI ĐÂY: Đổi $gio thành $gioSession
    $id = $kho->taoKeHoach((int)$nd['id'], $gioSession, $ghiChu);
    
    // Xóa giỏ hàng sau khi gửi thành công
    $_SESSION['ke_hoach_dang_ky'] = [];
    $_SESSION['flash_success'] = 'Đã gửi kế hoạch #' . $id;
    header('Location: don-hang.php?id=' . $id); 
    exit;
} catch (MonKhongTonTaiException $e) {
    $_SESSION['flash_error'] = 'Có môn không tồn tại, vui lòng kiểm tra lại.';
} catch (RuntimeException $e) {
    // Bắt lỗi "Môn XYZ đã hết chỗ đăng ký" từ KhoKeHoach
    $_SESSION['flash_error'] = $e->getMessage(); 
} catch (Throwable $e) {
    error_log($e->getMessage());
    $_SESSION['flash_error'] = 'Lỗi hệ thống, vui lòng thử lại.';
}

// Nếu có lỗi, quay lại giỏ hàng để người dùng sửa
header('Location: gio-hang.php', true, 303);
exit;
<?php
declare(strict_types=1);
/**
 * Gửi kế hoạch của tài khoản đăng nhập bằng POST và CSRF.
 * Giao dịch thiếu chỗ giữ giỏ; thành công chuyển hướng 303 tới chi tiết.
 * Thử: gửi hai môn, F5 sau thành công và gửi lại khi môn thứ hai hết chỗ.
 */
require_once __DIR__ . '/inc/config.php';

use App\Data\KhoKeHoach;
use App\Data\MonKhongTonTaiException;
use App\Data\MonKhongDuChoException;

// 1. Kiểm tra đăng nhập
$nd = yeuCauDangNhap();

// 2. Kiểm tra phương thức POST và CSRF.
$token = $_POST['_csrf'] ?? null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !is_string($token) || !csrfKiemTra($token)) {
    $_SESSION['flash_error'] = 'Phiên làm việc đã hết hạn hoặc sai phương thức.';
    header('Location: gio-hang.php', true, 303);
    exit;
}

// 3. Đọc lại giỏ và loại bỏ môn không còn tồn tại.
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
if (mb_strlen($ghiChu) > 500) {
    $ghiChu = mb_substr($ghiChu, 0, 500);
}

try {
    $kho = new KhoKeHoach(db());
    
    $id = $kho->taoKeHoach((int)$nd['id'], $gioSession, $ghiChu);
    
    // Xóa giỏ hàng sau khi gửi thành công
    $_SESSION['ke_hoach_dang_ky'] = [];
    $_SESSION['flash_success'] = 'Đã gửi kế hoạch #' . $id;
    header('Location: don-hang.php?id=' . $id, true, 303);
    exit;
} catch (MonKhongTonTaiException $e) {
    $_SESSION['flash_error'] = 'Có môn không tồn tại, vui lòng kiểm tra lại.';
} catch (MonKhongDuChoException $e) {
    // Bắt lỗi "Môn XYZ đã hết chỗ đăng ký" từ KhoKeHoach
    $_SESSION['flash_error'] = $e->getMessage(); 
} catch (Throwable $e) {
    error_log($e->getMessage());
    $_SESSION['flash_error'] = 'Lỗi hệ thống, vui lòng thử lại.';
}

// Nếu có lỗi, quay lại giỏ hàng để người dùng sửa
header('Location: gio-hang.php', true, 303);
exit;

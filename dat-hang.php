<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/config.php';

// Định nghĩa hàm CSRF dự phòng
if (!function_exists('csrfKiemTra')) {
    function csrfKiemTra(?string $token): bool {
        return is_string($token) && $token !== '' && isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], $token);
    }
}

use App\Data\KhoKeHoach;
use App\Data\MonKhongTonTaiException;

$nd  = yeuCauDangNhap();
$uid = (int) $nd['id'];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: gio-hang.php', true, 303);
    exit;
}

$token = $_POST['_csrf'] ?? null;
if (!is_string($token) || !csrfKiemTra($token)) {
    $_SESSION['flash_error'] = 'Phiên làm việc đã hết hạn, vui lòng thử lại.';
    header('Location: gio-hang.php', true, 303);
    exit;
}

// Lấy giỏ mới nhất của tài khoản, không gửi bản sao cũ từ trình duyệt khác.
$gioHienTai = new \App\Services\KeHoachHocTap();
$gioHienTai->layDanhSachChiTiet();
$gioSession = $_SESSION['ke_hoach_dang_ky'] ?? [];
if (!is_array($gioSession) || $gioSession === []) {
    $_SESSION['flash_error'] = 'Kế hoạch đang trống, không thể gửi.';
    header('Location: gio-hang.php', true, 303);
    exit;
}

$ghiChuRaw = $_POST['ghi_chu'] ?? '';
$ghiChu    = is_string($ghiChuRaw) ? trim($ghiChuRaw) : '';
if (mb_strlen($ghiChu) > 500) {
    $ghiChu = mb_substr($ghiChu, 0, 500);
}

try {
    $kho = new KhoKeHoach(db());
    $id  = $kho->taoKeHoach($uid, $gioSession, $ghiChu);

    $_SESSION['ke_hoach_dang_ky'] = [];
    $_SESSION['flash_success'] = 'Đã gửi kế hoạch học tập #' . $id . ' thành công.';
    header('Location: don-hang.php?id=' . $id, true, 303);
    exit;
} catch (MonKhongTonTaiException $e) {
    $_SESSION['flash_error'] = 'Một số môn trong kế hoạch không còn tồn tại. Vui lòng cập nhật giỏ.';
} catch (Throwable $e) {
    error_log('dat-hang: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'Không gửi được kế hoạch, vui lòng thử lại. Giỏ hàng vẫn được giữ nguyên.';
}

header('Location: gio-hang.php', true, 303);
exit;

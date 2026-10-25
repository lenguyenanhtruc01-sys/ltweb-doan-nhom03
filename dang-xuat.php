<?php
declare(strict_types=1);

/**
 * Lưu giỏ vào tài khoản trước khi đăng xuất và đổi mã phiên.
 * Thử: đăng nhập lại thấy giỏ cũ, tài khoản khác có giỏ riêng.
 */

require_once __DIR__ . '/inc/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Phương thức không được hỗ trợ.');
}
$token = $_POST['_csrf'] ?? null;
if (!is_string($token) || !csrfKiemTra($token)) {
    http_response_code(403);
    exit('Yêu cầu không hợp lệ.');
}

$nd = nguoiDung();
if ($nd !== null) {
    $gio = new \App\Services\KeHoachHocTap();
    $gio->layDanhSachChiTiet();
    (new \App\Data\KhoTrangThaiNguoiDung(db()))->luuGioHang(
        (int) $nd['id'], $_SESSION['ke_hoach_dang_ky'] ?? []
    );
}
xoaGhiNhoHienTai();

$_SESSION = [];
session_regenerate_id(true);
$_SESSION['ke_hoach_dang_ky'] = [];
header('Location: index.php', true, 303);
exit;

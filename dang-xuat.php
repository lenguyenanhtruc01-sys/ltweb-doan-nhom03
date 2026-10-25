<?php
declare(strict_types=1);

/**
 * Lưu giỏ vào tài khoản trước khi đăng xuất và đổi mã phiên.
 * Thử: đăng nhập lại thấy giỏ cũ, tài khoản khác có giỏ riêng.
 */

require_once __DIR__ . '/inc/config.php';

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

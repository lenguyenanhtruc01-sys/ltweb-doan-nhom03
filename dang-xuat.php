<?php
/**
 * Tệp: dang-xuat.php
 * Chức năng: xóa phiên quản trị, đổi mã phiên và giữ kế hoạch học tập độc lập.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Giỏ hàng là dữ liệu độc lập với tài khoản quản trị nên vẫn được giữ lại.
$gioHang = $_SESSION['ke_hoach_dang_ky'] ?? [];

if (!is_array($gioHang)) {
    $gioHang = [];
}

// Xóa trạng thái đăng nhập và đổi mã phiên để đăng xuất an toàn.
$_SESSION = [];
session_regenerate_id(true);
$_SESSION['ke_hoach_dang_ky'] = $gioHang;

// Dùng đường dẫn tuyệt đối hoặc tương đối rõ ràng để về trang đăng nhập
header("Location: dang-nhap.php");
exit;

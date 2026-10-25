<?php
declare(strict_types=1);

/**
 * Đăng xuất và đổi mã phiên, giữ riêng kế hoạch học tập.
 * Thử: thêm môn, đăng nhập rồi đăng xuất; môn vẫn còn nhưng uid bị xóa.
 */

require_once __DIR__ . '/inc/config.php';

xoaGhiNhoHienTai();

$_SESSION = [];
session_regenerate_id(true);
$_SESSION['ke_hoach_dang_ky'] = is_array($gioHang) ? $gioHang : [];
header('Location: index.php', true, 303);
exit;

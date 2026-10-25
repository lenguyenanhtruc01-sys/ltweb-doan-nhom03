<?php
declare(strict_types=1);

/**
 * Đăng xuất và đổi mã phiên, giữ riêng kế hoạch học tập.
 * Thử: thêm môn, đăng nhập rồi đăng xuất; môn vẫn còn nhưng uid bị xóa.
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

xoaGhiNhoHienTai();

$keHoachDangKy = $_SESSION['ke_hoach_dang_ky'] ?? [];
$_SESSION = [];
session_regenerate_id(true);
$_SESSION['ke_hoach_dang_ky'] = is_array($keHoachDangKy) ? $keHoachDangKy : [];
header('Location: index.php', true, 303);
exit;

<?php
declare(strict_types=1);

/**
 * Đăng xuất và đổi mã phiên; giỏ đã được lưu theo từng thao tác.
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

// Không ghi giỏ session cũ đè lên dữ liệu do phiên khác vừa cập nhật.
xoaGhiNhoHienTai();

$_SESSION = [];
session_regenerate_id(true);
$_SESSION['ke_hoach_dang_ky'] = [];
header('Location: index.php', true, 303);
exit;

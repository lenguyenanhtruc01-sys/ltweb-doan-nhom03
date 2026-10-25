<?php
declare(strict_types=1);
/**
 * URL tương thích cũ dùng chung validation, session và PRG của gio-hang.php.
 * GET không được thay đổi kế hoạch; action cũ được ánh xạ sang hanh_dong.
 * Thử: gửi POST action cũ và GET; chỉ POST được thay đổi giỏ.
 */
require_once __DIR__ . '/inc/config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['hanh_dong'])) {
    $_POST['hanh_dong'] = $_POST['action'] ?? '';
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gio-hang.php');
    exit;
}
require __DIR__ . '/gio-hang.php';

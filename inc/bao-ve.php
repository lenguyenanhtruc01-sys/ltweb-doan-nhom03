<?php
/**
 * Tệp: inc/bao-ve.php
 * Chức năng: chặn truy cập trang quản trị khi phiên chưa được xác thực.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (!isset($_SESSION['da_dang_nhap']) || $_SESSION['da_dang_nhap'] !== true) {
    header('Location: dang-nhap.php');
    exit;
}
